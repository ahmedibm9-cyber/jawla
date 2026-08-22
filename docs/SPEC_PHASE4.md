# SPEC — Phase 4: Security

## 4.1 Biometric Authentication

### Actor & Preconditions

- Rep/Admin using device with biometric capability (fingerprint, face)
- Browser supports WebAuthn API

### Behavior

**Registration Flow:**

1. User logs in with password (first time only)
2. App prompts: "Enable biometric login?"
3. User taps "Yes"
4. Browser creates WebAuthn credential
5. Credential stored server-side (credential ID + public key)
6. Future logins use biometric

**Login Flow:**

1. User opens app
2. App detects registered biometric credential
3. Browser shows biometric prompt (fingerprint/face)
4. User authenticates
5. App logs in without password

**Critical Action Confirmation:**

- Payment submission: "Confirm with fingerprint"
- Return processing: "Confirm with fingerprint"
- Any financial transaction: "Confirm with fingerprint"
- Same WebAuthn credential used

**Implementation:**

```javascript
// Client-side WebAuthn registration
async function registerBiometric() {
  const challenge = await fetch("/api/webauthn/challenge").then((r) =>
    r.json()
  );

  const credential = await navigator.credentials.create({
    publicKey: {
      challenge: challenge.value,
      rp: { name: "Jawla", id: window.location.hostname },
      user: {
        id: new TextEncoder().encode(currentUser.id),
        name: currentUser.email,
        displayName: currentUser.name,
      },
      pubKeyCredParams: [
        { alg: -7, type: "public-key" }, // ES256
        { alg: -257, type: "public-key" }, // RS256
      ],
      authenticatorSelection: {
        authenticatorAttachment: "platform",
        userVerification: "required",
      },
      timeout: 60000,
    },
  });

  await fetch("/api/webauthn/register", {
    method: "POST",
    body: JSON.stringify({
      id: credential.id,
      rawId: arrayBufferToBase64(credential.rawId),
      type: credential.type,
      response: {
        attestationObject: arrayBufferToBase64(
          credential.response.attestationObject
        ),
        clientDataJSON: arrayBufferToBase64(credential.response.clientDataJSON),
      },
    }),
  });
}
```

**Server-side:**

```php
// New WebauthnService
class WebauthnService
{
    public function generateChallenge(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function storeCredential(User $user, array $credential): void
    {
        $user->webauthnCredentials()->create([
            'credential_id' => $credential['id'],
            'public_key' => $credential['public_key'],
            'counter' => 0,
        ]);
    }

    public function verifyAuthentication(User $user, array $assertion): bool
    {
        $credential = $user->webauthnCredentials()
            ->where('credential_id', $assertion['id'])
            ->first();

        if (!$credential) return false;

        // Verify signature, update counter
        // ...
    }
}
```

**Fallback:**

- If WebAuthn unavailable: password login only
- If biometric fails: fallback to password
- User can disable biometric in settings

### Acceptance Criteria

- [ ] Biometric registration flow works
- [ ] Biometric login works on supported devices
- [ ] Critical action confirmation works
- [ ] Fallback to password on unsupported devices
- [ ] User can disable biometric in settings
- [ ] Credential stored securely (public key only)
- [ ] Works on Android Chrome, iOS Safari

### Data Model

```sql
CREATE TABLE webauthn_credentials (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  credential_id VARCHAR(255) NOT NULL UNIQUE,
  public_key TEXT NOT NULL,
  counter BIGINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT NOW(),
  last_used_at TIMESTAMP
);

CREATE INDEX idx_webauthn_credentials_user ON webauthn_credentials(user_id);
CREATE INDEX idx_webauthn_credentials_credential ON webauthn_credentials(credential_id);
```

---

## 4.2 Device Fingerprinting

### Actor & Preconditions

- Any user logging in
- Browser supports canvas, WebGL, audio context

### Behavior

**Fingerprint Generation (Client-side):**

```javascript
async function generateFingerprint() {
  const components = [];

  // Canvas fingerprint
  const canvas = document.createElement("canvas");
  const ctx = canvas.getContext("2d");
  ctx.textBaseline = "top";
  ctx.font = "14px Arial";
  ctx.fillText("Jawla", 2, 2);
  components.push(canvas.toDataURL());

  // WebGL fingerprint
  const gl = document.createElement("canvas").getContext("webgl");
  components.push(gl.getParameter(gl.RENDERER));
  components.push(gl.getParameter(gl.VENDOR));

  // Audio context fingerprint
  const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  components.push(audioCtx.sampleRate.toString());

  // Navigator properties
  components.push(navigator.userAgent);
  components.push(navigator.language);
  components.push(screen.colorDepth.toString());

  // Hash all components
  const fingerprint = await hashComponents(components);
  return fingerprint;
}
```

**Server-side Verification:**

```php
class DeviceFingerprintService
{
    public function verify(User $user, string $fingerprint): DeviceStatus
    {
        $knownDevice = $user->devices()
            ->where('fingerprint', $fingerprint)
            ->first();

        if ($knownDevice) {
            $knownDevice->touch(); // update last_seen_at
            return DeviceStatus::KNOWN;
        }

        // New device — flag for admin review
        $this->flagNewDevice($user, $fingerprint);
        return DeviceStatus::NEW_DEVICE;
    }

    private function flagNewDevice(User $user, string $fingerprint): void
    {
        Device::create([
            'user_id' => $user->id,
            'fingerprint' => $fingerprint,
            'status' => 'flagged',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Notify admin
        AlarmService::deviceNewDevice($user, $fingerprint);
    }
}
```

**Admin UI:**

- New "Device Management" page in Filament
- Shows all devices per user
- Status: Known / Flagged / Blocked
- Admin can: Approve (mark as known), Block (prevent login), Remove

### Acceptance Criteria

- [ ] Fingerprint generated client-side
- [ ] Fingerprint sent with login request
- [ ] Known devices recognized
- [ ] New devices flagged for admin
- [ ] Admin can view device list per user
- [ ] Admin can approve/block devices
- [ ] Blocked devices cannot login
- [ ] Fingerprint changes not too sensitive (minor browser updates shouldn't flag)

### Data Model

```sql
CREATE TABLE user_devices (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  fingerprint VARCHAR(64) NOT NULL,
  status VARCHAR(20) DEFAULT 'flagged', -- known, flagged, blocked
  ip_address INET,
  user_agent TEXT,
  last_seen_at TIMESTAMP DEFAULT NOW(),
  created_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(user_id, fingerprint)
);

CREATE INDEX idx_user_devices_user ON user_devices(user_id);
CREATE INDEX idx_user_devices_status ON user_devices(status);
```

---

## 4.3 Integration with Existing Auth

### Behavior

**Login Flow (Enhanced):**

```
1. User enters email + password
2. Password verified (existing)
3. Check device fingerprint
   - Known device → proceed
   - New device → flag, proceed with warning
   - Blocked device → deny login
4. Check biometric registration
   - Registered → prompt biometric
   - Not registered → proceed to dashboard
5. Biometric verified → login complete
6. Biometric failed → fallback to password-only login
```

**Critical Action Flow:**

```
1. User taps "Submit Payment"
2. App checks if biometric registered
   - Yes → prompt biometric
   - No → require password re-entry
3. Biometric/password verified → action proceeds
4. Failed → action blocked
```

### Acceptance Criteria

- [ ] Login flow handles all device/biometric states
- [ ] Critical actions require biometric or password
- [ ] Blocked devices cannot login
- [ ] New device flag visible to admin
- [ ] No regression on existing password login
