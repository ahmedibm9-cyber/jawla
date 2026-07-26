<?php

namespace App\Http\Middleware;

use App\Support\ActiveCompanyContext;
use Closure;
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

            if ($user !== null) {
                $requested = $request->header('X-Jawla-Company');

                if ($requested === null && $request->hasSession()) {
                    $requested = $request->session()->get('active_company_id');
                }

                if ($requested !== null && filter_var($requested, FILTER_VALIDATE_INT) === false) {
                    abort(400, 'Invalid company selector.');
                }

                $companyId = $requested === null ? (int) $user->company_id : (int) $requested;
                $this->context->setFromUser($user, $companyId);

                if ($request->hasSession()) {
                    $request->session()->put('active_company_id', $companyId);
                }

                view()->share('activeCompany', $this->context->company());
            }

            return $next($request);
        } finally {
            $this->context->disable();
        }
    }
}
