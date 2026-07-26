<?php

namespace App\Http\Controllers\Api\V1;

use App\Support\ApiAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Identity + capability discovery for an authenticated API token. Requires only
 * a valid token (no specific ability) so integrators can introspect what their
 * token is allowed to do.
 */
class MetaController
{
    public function whoami(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'company_id' => $user->activeCompanyId(),
                'token_name' => $token->name,
                'abilities' => $token->abilities,
                'available_abilities' => ApiAbilities::keys(),
            ],
        ]);
    }
}
