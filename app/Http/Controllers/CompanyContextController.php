<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwitchCompanyRequest;
use App\Models\Activity;
use App\Models\Company;
use App\Support\ActiveCompanyContext;
use Illuminate\Http\RedirectResponse;

class CompanyContextController
{
    public function update(
        SwitchCompanyRequest $request,
        ActiveCompanyContext $context,
    ): RedirectResponse {
        $previousCompanyId = $context->id();
        $companyId = (int) $request->validated('company_id');
        $company = Company::findOrFail($companyId);

        $context->setCompanyId($companyId);
        $request->session()->put('active_company_id', $companyId);

        Activity::log(
            'company_switched',
            description: "Active company changed to {$company->name_en}",
            properties: [
                'previous_company_id' => $previousCompanyId,
                'active_company_id' => $companyId,
            ],
        );

        return back()->with('status', app()->getLocale() === 'ar'
            ? 'تم تغيير الشركة النشطة.'
            : 'Active company changed.');
    }
}
