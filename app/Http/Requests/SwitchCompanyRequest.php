<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwitchCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $companyId = (int) $this->input('company_id');

        return $this->user()?->is_active === true
            && $companyId > 0
            && $this->user()->hasCompanyAccess($companyId);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ];
    }
}
