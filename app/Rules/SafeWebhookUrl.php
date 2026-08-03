<?php

namespace App\Rules;

use App\Services\WebhookUrlGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeWebhookUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            app(WebhookUrlGuard::class)->resolve((string) $value);
        } catch (\DomainException $exception) {
            $fail($exception->getMessage());
        }
    }
}
