<?php

namespace App\Http\Middleware;

use App\Support\ActiveCompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveCompanyContext
{
    public function __construct(private readonly ActiveCompanyContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            $this->context->setFromUser($request->user());
        }

        return $next($request);
    }
}
