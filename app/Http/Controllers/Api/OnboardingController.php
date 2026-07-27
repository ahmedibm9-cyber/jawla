<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function complete(Request $request): JsonResponse
    {
        $request->user()->update(['onboarding_seen' => true]);

        return response()->json(['ok' => true]);
    }
}
