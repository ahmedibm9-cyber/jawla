<?php

namespace App\Http\Middleware;

use App\Support\ActiveCompanyContext;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveCompanyContext
{
    public function __construct(private readonly ActiveCompanyContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->enforce();

        try {
            $user = $request->user();

            if ($user === null) {
                $this->context->allowUnscoped();

                return $next($request);
            }

            $requested = $request->header('X-Jawla-Company');

            if ($requested === null && $request->hasSession()) {
                $requested = $request->session()->get('active_company_id');
            }

            if ($requested !== null && ! filter_var($requested, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                abort(400, 'Invalid company selector.');
            }

            $companyId = $requested === null ? (int) $user->company_id : (int) $requested;

            try {
                $this->context->setFromUser($user, $companyId);
            } catch (AuthorizationException $e) {
                \Log::error('SetActiveCompanyContext: company access denied', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_company_id' => $user->company_id,
                    'requested_company_id' => $companyId,
                    'url' => $request->url(),
                    'is_livewire' => $request->hasHeader('X-Livewire'),
                ]);
                throw $e;
            }

            if ($request->hasSession()) {
                $request->session()->put('active_company_id', $companyId);
            }

            view()->share('activeCompany', $this->context->company());

            return $next($request);
        } finally {
            $this->context->disable();
        }
    }
}
