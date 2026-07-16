# Jawla (جولة) — Beta Product Requirements Document
## Extracted from Client Voice Messages (9 audio messages, translated)

**Client:** اللدائن العالمية — Global Plastic Company (GPC) · **Contact/Admin:** Amr Hakim · **Executive:** Mohamed Taha
**Source of truth:** Audio Messages AM1–AM9 (client's own words). Cross-referenced at the end against the internal *Jawla Build Guide v1*.
**Deadline signal:** Client expects a progress answer by **Wednesday, Thursday at the latest** (AM8).

**Requirement ID convention:** `REQ-<AREA>-<n>` · Priority: **M** = Must-have for beta (explicitly demanded) · **S** = Should-have (implied/supporting) · **L** = Later (client deferred it themselves).
Every requirement cites its source message (AM#) for traceability.

---

## 1. Users & Roles (source: AM1, AM2, AM4)

| ID | Role | Responsibilities stated by client | Priority |
|---|---|---|---|
| REQ-ROL-1 | **Sales Representative (مندوب)** | Executes assigned visits; adds new customers (pending); submits visit reports, quotation requests, purchase offers, out-of-stock requests, complaints; issues proforma invoices within authorized range | M (AM1, AM3, AM4, AM6, AM7, AM9) |
| REQ-ROL-2 | **Sales Manager** | Assigns daily visit tasks per rep; approves/rejects new customers; sets approved price + negotiation range per quotation; owns alarm handling (complaints, out-of-stock) | M (AM1, AM6, AM7) |
| REQ-ROL-3 | **Finance (مالية)** | Own section in system; sets **base prices**; defines the manager's allowed adjustment range; views visit reports, quotation reports, proformas; receives out-of-stock alarms | M (AM2, AM4) |
| REQ-ROL-4 | **Purchasing Department** | Reviews rep purchase offers jointly with Sales Management; optimizes purchase price | M (AM3) |
| REQ-ROL-5 | **Executive / Management** (e.g. Mohamed Taha) | Views reports; receives out-of-stock alarms. Read-only consumption implied | M (AM2, AM4) |
| REQ-ROL-6 | **Warehouse Keeper** | Dedicated page to import/export inventory; performs daily stock file import | M (AM2) |
| REQ-ROL-7 | **System Administrator = Amr** | Creates all users (reps, finance managers, sales managers, executives); assigns per-user viewing permissions | M (AM2) |

**Constraint (REQ-ROL-8, M):** Permissions are *view-scoped* per person — Admin "assigns permissions to everyone according to what they are allowed to view" (AM2). The system needs granular, admin-managed visibility control, not just fixed roles.

---

## 2. Core Features

### 2.1 Daily Visit Planning & Execution (AM1)

| ID | Requirement | Detail / business rule | Priority |
|---|---|---|---|
| REQ-VST-1 | Daily task assignment | Sales Manager assigns each rep a set of customer visits per day (example given: 5 visits) | M |
| REQ-VST-2 | Master schedule | A schedule view containing **every rep and the customers assigned to them** | M |
| REQ-VST-3 | Rep's day view | Rep starts the day seeing their assigned visits | M |
| REQ-VST-4 | Customer GPS on record | Customer's GPS location stored as part of the customer record | M |
| REQ-VST-5 | Arrival verification | System compares rep's live GPS against stored customer location; arrival considered valid within a **radius of 1–2 km** | M |
| REQ-VST-6 | "Confirmed Arrival" action | A button/action the rep presses when within range, proving the visit physically happened | M |
| REQ-VST-7 | Visit report | After the visit, rep writes and submits a report describing what happened | M |

**Client's own uncertainty (flagged):** "I'm not sure exactly how the application will do this… please think about how this part should work" (AM1). → The geofence mechanism is **delegated to us**; the client must still sign off on radius and behavior when out of range. See Open Question Q3.

### 2.2 Customer Management (AM1)

| ID | Requirement | Detail / business rule | Priority |
|---|---|---|---|
| REQ-CUS-1 | Field customer creation | Rep can add a new customer while out on visits (e.g., neighboring factory) | M |
| REQ-CUS-2 | Pending-approval state | New customer is **not active** on creation; rep enters info only | M |
| REQ-CUS-3 | Manager approval gate | Sales Manager approves or rejects; stated reason: the customer **may already belong to another rep** | M |
| REQ-CUS-4 | Ownership model (implied) | Customers are assigned to reps; conflict detection on ownership is the approval's purpose | S |

### 2.3 Pricing & Quotation Workflow (AM2, AM6)

| ID | Requirement | Detail / business rule | Priority |
|---|---|---|---|
| REQ-PRC-1 | Finance sets base price | Fixed base price entered by Finance only | M |
| REQ-PRC-2 | Manager adjusts within range | Sales Manager may modify price **within a Finance-approved range** (example: ±200) | M |
| REQ-PRC-3 | Nested range delegation | Manager may pass a *narrower* range to the rep (example: rep gets ±100 of the manager's ±200, manager keeps the remainder) — client: "we'll discuss those details later" | **L** (client-deferred) |
| REQ-PRC-4 | Quotation request | Rep submits request containing customer info, product, quantity, other details | M |
| REQ-PRC-5 | "Requested" queue | Manager sees incoming quotation requests in a dedicated *Requested* section | M |
| REQ-PRC-6 | Manager response | Manager assigns **approved price + negotiation range (±)** per request | M |
| REQ-PRC-7 | Negotiation rule | Rep starts from the highest acceptable value and may reduce, but **never below the minimum authorized price** | M |
| REQ-PRC-8 | Configurable limits | Exact plus/minus limits "will later be configured" — build them as configuration, not constants | S |

⚠️ **Internal contradiction in AM6** — the example doesn't add up: Base = 1000, Range = ±100, but "start at 1200." 1200 exceeds 1000+100 = 1100. Either the range in the example is ±200, or the starting ask may exceed the range while only the *floor* is enforced. **The enforcement engine cannot be finalized until this is resolved.** See Q1.

### 2.4 Proforma Invoice (AM9)

| ID | Requirement | Detail / business rule | Priority |
|---|---|---|---|
| REQ-INV-1 | Acceptance recording | When customer accepts, rep records that price is confirmed and invoice sent | M |
| REQ-INV-2 | Rep issues proforma directly | No Finance involvement — pricing already manager-approved; goal explicitly stated: **avoid delays** | M |
| REQ-INV-3 | Hard price enforcement | System **must reject** a proforma priced below the authorized minimum (example: approved 1000, min 900 → 850 is rejected) | M |
| REQ-INV-4 | Bank details auto-included | Proforma automatically includes company bank account info, predefined and maintained in the system database | M |

⚠️ **Ambiguity:** AM9 says any price "between 900 **and above**" is allowed — no upper bound is stated for the proforma, while AM6 implies a ± (two-sided) range. Is overcharging above the ceiling allowed? See Q2.

### 2.5 Inventory Visibility (AM2)

| ID | Requirement | Detail / business rule | Priority |
|---|---|---|---|
| REQ-STK-1 | Warehouse keeper import page | Page to import/export inventory | M |
| REQ-STK-2 | Daily file import | They already have a stock report "in a specific format"; imported **every day** into the system | M |
| REQ-STK-3 | Import automation | "Until we later automate the process" — manual import now, integration later | **L** (client-deferred) |
| REQ-STK-4 | Rep stock lookup | Reps see current stock availability **at any time** (example: customer asks for *Material 952* → rep checks live) | M |
| REQ-STK-5 | Purpose constraint | Stated business goal: stock knowledge gives the rep **confidence during negotiation** — lookup must be fast and usable mid-conversation (mobile, low friction) | M |

⚠️ The import file's "specific format" is undefined — a sample file is required before the importer can be built. See Q4.

### 2.6 Purchasing (AM3)

| ID | Requirement | Detail / business rule | Priority |
|---|---|---|---|
| REQ-PUR-1 | Supplier concept | A visited company offering materials is recorded as a **supplier**, not a customer | M |
| REQ-PUR-2 | Rep purchase offer | Rep submits: "found supplier offering Material X at Price Y" → goes to management | M |
| REQ-PUR-3 | Dual review | Purchase offers reviewed by **both** Sales Management and Purchasing Management | M |
| REQ-PUR-4 | Veto logic | Sales may reject large-quantity buys of slow-selling products; Purchasing focuses on best price | M |

⚠️ Dual review is stated but the mechanics aren't: sequential or parallel? Can either side unilaterally kill an offer? See Q5.

### 2.7 Alarms & Out-of-Stock Requests (AM4, AM7)

| ID | Requirement | Detail / business rule | Priority |
|---|---|---|---|
| REQ-ALM-1 | Urgent out-of-stock request | If a requested product is unavailable, rep creates an **urgent request** | M |
| REQ-ALM-2 | Alarm broadcast | Appears as an alarm notifying **Finance + Sales Manager + Executives** (Mohamed Taha named) | M |
| REQ-ALM-3 | Alarm content | Must clearly state: **Rep X requested Material Y, currently unavailable** | M |
| REQ-ALM-4 | Alarm ownership | Sales Manager is the responsible handler for alarms (complaints + out-of-stock) | M (AM7) |

### 2.8 CRM / Complaints (AM7)

| ID | Requirement | Detail / business rule | Priority |
|---|---|---|---|
| REQ-CRM-1 | Complaint intake via rep | Customer contacts their rep with issues (wrong materials delivered, quality problems, anything else); rep records it | M |
| REQ-CRM-2 | Complaint → alarm | System generates an alarm on complaint creation | M |
| REQ-CRM-3 | Manager responsibility | Sales Manager handles/responds to complaint alarms | M |

⚠️ No lifecycle defined (statuses, resolution, closure, customer feedback loop). See Q6.

### 2.9 Reporting & Visibility (AM2)

| ID | Requirement | Detail / business rule | Priority |
|---|---|---|---|
| REQ-RPT-1 | Cross-role visibility | **Visit reports, quotation reports, and proforma invoices** must be viewable by Finance, Management, and Sales Manager | M |
| REQ-RPT-2 | Data-entry roles | Primary data entry limited to Sales Manager + Reps; other roles are consumers | M |
| REQ-RPT-3 | Finance section | Finance has "its own section" in the system | M |

---

## 3. Technical Specifications (stated or directly implied)

| ID | Spec | Source |
|---|---|---|
| REQ-TEC-1 | **GPS/geolocation subsystem:** store coordinates per customer; live rep positioning; geofence matching at 1–2 km radius; arrival confirmation event | AM1 |
| REQ-TEC-2 | **Pricing enforcement engine:** hard validation at proforma creation — reject below authorized floor; limits configurable per quotation | AM6, AM9 |
| REQ-TEC-3 | **Notification/alarm subsystem:** multi-role broadcast (Finance, Sales Manager, Executive) with typed alarms (out-of-stock, complaint) and clear payload (rep, material) | AM4, AM7 |
| REQ-TEC-4 | **File import pipeline:** daily inventory import in the client's existing format; designed for later replacement by automated integration | AM2 |
| REQ-TEC-5 | **Currency:** single currency, **EGP only** — "Finance handles all of that. You don't need to worry about it. Buying and selling are done only in Egyptian Pounds" | AM3, AM5 |
| REQ-TEC-6 | **Reference data:** company bank account details stored centrally, injected into proformas automatically | AM9 |
| REQ-TEC-7 | **Permission system:** admin-managed, per-user view permissions | AM2 |
| REQ-TEC-8 | **Mobile field usage** implied throughout: reps operate "out on visits" with GPS — the rep experience must work on a phone in the field | AM1 |

**Performance/constraint notes stated by client:** proforma flow exists specifically to **eliminate delay** (AM9); stock lookup must be usable live during negotiation (AM2). These are the two speed-sensitive paths.

---

## 4. User Experience Requirements

- **Rep experience is mobile-first and field-first:** day view of assigned visits → arrive → one-tap Confirmed Arrival → visit report → quick stock search → quotation request → proforma issue. Each step happens standing in a factory, not at a desk (AM1, AM2, AM9).
- **Manager experience is queue-driven:** pending customers, "Requested" quotations, alarms — the manager's UI is a set of inboxes with approve/respond actions (AM1, AM6, AM7).
- **Alarms must be loud:** the client repeatedly says "alarm," not "notification" — urgent items need visually distinct treatment and multi-role reach (AM4, AM7).
- **Finance/Executive experience is read-heavy:** dedicated sections, report visibility, minimal input (AM2).

---

## 5. Explicitly Out of Scope for Beta (client's own words)

1. **Multi-currency handling** — "Finance handles all of that. You don't need to worry" (AM3, AM5).
2. **Nested range delegation mechanics** (manager keeping part of the Finance range) — "we'll discuss those details later" (AM2).
3. **Automated inventory integration** — daily manual file import is the accepted interim (AM2).
4. **Exact plus/minus limit values** — "will later be configured" (AM6).

---

## 6. Open Questions Requiring Client Follow-up (blocking → ⛔, non-blocking → ▫)

| # | Question | Why it matters | Blocks |
|---|---|---|---|
| ⛔ **Q1** | **The AM6 pricing example contradicts itself:** base 1000, range ±100, but "start at 1200." Is the range actually ±200? Or is only the *floor* enforced while the opening ask is free? | The proforma rejection engine (REQ-INV-3) cannot be coded two ways | Pricing engine |
| ⛔ **Q2** | Is there an **upper limit** on the proforma price? AM9 allows "900 and above" with no ceiling | Overcharging risk vs. two-sided range in AM6 | Pricing engine |
| ⛔ **Q3** | Geofence: confirm **1 km or 2 km** radius, and define behavior when GPS says the rep is out of range (block confirmation? allow with flag? manager override?) | Client explicitly delegated the design but must sign off; affects rep trust and edge cases (large industrial zones, GPS drift) | Visit flow |
| ⛔ **Q4** | Provide a **sample of the daily inventory report file** (the "specific format") | Importer cannot be specified without it | Stock import |
| ▫ **Q5** | Purchase-offer dual review: do Sales and Purchasing approve **in sequence or in parallel**? Does either side's rejection kill the offer? | Determines workflow states | Purchasing module |
| ▫ **Q6** | Complaint lifecycle: what statuses/closure? Does the customer get feedback? | CRM module depth | CRM |
| ▫ **Q7** | Customer rejection path: when a manager rejects a new customer (e.g., belongs to another rep), what does the rep see? Is the record reassigned or deleted? | Customer approval UX | Customers |
| ▫ **Q8** | "Quotation **reports**" viewable by Finance (AM2) — is this the quotation documents themselves, or an analytical report? | Reporting scope | Reports |
| ▫ **Q9** | What must the **visit report** contain — free text only, or structured fields (outcome, next action, products discussed)? | Report value for management | Visit flow |
| ▫ **Q10** | For beta demo (Wed/Thu deadline, AM8): which workflows must be demonstrable first? Suggest: visits + customer approval + quotation→proforma | Sprint planning | Planning |

---

## 7. Contradictions & Conflicts Register

| # | Conflict | Between | Resolution needed |
|---|---|---|---|
| C1 | Pricing math (1000 ±100 vs start 1200) | AM6 internal | Q1 |
| C2 | One-sided floor vs two-sided range | AM9 vs AM6 | Q2 |
| C3 | **EGP-only (AM3, AM5) vs multi-currency purchasing (USD/CNY) in the internal Build Guide v1 §1.16** | Client voice vs internal spec | Decide: beta = EGP-only per client's latest word; keep multi-currency schema fields dormant for v2. Confirm with client that *purchase orders* in foreign currency are genuinely out of beta scope |
| C4 | Purchase offers "submitted to management" (AM3 early) vs "reviewed by both Sales and Purchasing" (AM3 later) | AM3 internal | Client corrected himself mid-message; the dual-review version is the requirement. Confirm |

---

## 8. Alignment with Internal Build Guide v1 (cross-check)

Where the voice messages and the locked build guide agree, disagree, or the guide already answers an open question:

- **Agree (no action):** daily visit assignment, GPS geofence 1–2 km, pending-customer approval, quotation base+range flow, proforma with hard floor enforcement, out-of-stock red alarms to manager/finance/executive (فيور = Mohamed Taha), complaints-as-alarms, daily warehouse import, roles incl. Amr as full-access admin. The guide's §12 permission matrix covers every role the client named.
- **Guide answers Q3 partially:** geofence "1–2 km radius" adopted (§1.3) — still get the client's sign-off on the out-of-range behavior.
- **Conflict C3 (currency):** guide §1.16 includes USD/CNY purchasing and inter-company v2; client's latest word is EGP-only for now. Recommended posture: build money columns + exchange-rate fields per the guide's schema, but expose EGP-only UI in beta.
- **Guide exceeds voice scope:** van stock, cash box, returns, expenses, batches/COA, goods-in-transit, landed cost — none of these appear in the voice messages. They came from your discovery with the client company profile. For the **Wednesday/Thursday beta answer**, the voice-message scope (Sections 2.1–2.9 above) is the client's definition of done; the guide's extra modules are roadmap, not beta.

---

## 9. Suggested Beta Cut (for the Wed/Thu commitment)

**Demonstrable slice, in build order:** Roles/users (Amr as admin) → customer records with GPS + pending approval → daily visit assignment + rep day view → Confirmed Arrival (geofence) + visit report → quotation request → manager "Requested" queue with price+range → proforma with floor enforcement + bank details → stock import + rep stock lookup → alarms (out-of-stock + complaints).
Everything else (purchasing dual-review, nested ranges, automation, multi-currency) is explicitly post-beta per the client's own deferrals.

---

*Traceability: every REQ cites its AM source. Nothing in this document is invented; inferences are marked "implied" and contradictions are flagged rather than silently resolved.*
