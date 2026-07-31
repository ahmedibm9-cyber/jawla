<?php

namespace App\Http\Controllers\App;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $user = Auth::user();

        PushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $data['endpoint'],
            ],
            [
                'company_id' => $user->activeCompanyId(),
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'device_id' => $request->header('X-Device-Id'),
            ]
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => ['required', 'url']]);

        PushSubscription::where('user_id', Auth::id())
            ->where('endpoint', $request->input('endpoint'))
            ->delete();

        return response()->json(['status' => 'unsubscribed']);
    }
}
