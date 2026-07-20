<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use App\Support\ApiAbilities;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Issues and revokes public-API tokens. Token issuance and revocation are
 * security-relevant events, so both are wrapped in a transaction and written to
 * the activity log. Abilities are validated against the canonical catalogue —
 * an unknown ability is rejected rather than silently granted.
 */
class ApiTokenService
{
    /**
     * @param  list<string>  $abilities
     * @return array{token: PersonalAccessToken, plainTextToken: string}
     */
    public function issue(User $user, string $name, array $abilities): array
    {
        $name = trim($name);
        throw_if($name === '', new \InvalidArgumentException('Token name is required.'));

        $abilities = array_values(array_unique($abilities));
        $unknown = array_diff($abilities, ApiAbilities::keys());
        throw_if($unknown !== [], new \InvalidArgumentException('Unknown ability: '.implode(', ', $unknown)));
        throw_if($abilities === [], new \InvalidArgumentException('At least one ability is required.'));

        return DB::transaction(function () use ($user, $name, $abilities) {
            $newToken = $user->createToken($name, $abilities);
            /** @var PersonalAccessToken $model */
            $model = $newToken->accessToken;

            Activity::log('api_token_issued', $model, "API token issued: {$name}", [
                'user_id' => $user->id,
                'abilities' => $abilities,
            ]);

            return ['token' => $model, 'plainTextToken' => $newToken->plainTextToken];
        });
    }

    public function revoke(PersonalAccessToken $token): void
    {
        DB::transaction(function () use ($token) {
            $name = $token->name;
            $tokenableId = $token->tokenable_id;
            $token->delete();

            Activity::log('api_token_revoked', null, "API token revoked: {$name}", [
                'user_id' => $tokenableId,
            ]);
        });
    }
}
