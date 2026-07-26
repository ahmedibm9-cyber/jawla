@if(config('jawla.is_demo'))
    <div
        role="status"
        aria-label="Demo / Evaluation mode"
        style="position:sticky;top:0;z-index:9999;padding:.5rem 1rem;text-align:center;background:#7c2d12;color:#fff;font-weight:700"
    >
        DEMO / EVALUATION — SAMPLE, NOT A TAX INVOICE ·
        وضع تجريبي / للتقييم — عينة، وليست فاتورة ضريبية
    </div>
@endif

@auth
    @php
        $currentCompany = $activeCompany ?? app(\App\Support\ActiveCompanyContext::class)->company();
        $availableCompanies = auth()->user()->companies()->where('companies.is_active', true)->orderBy('name_en')->get();
    @endphp

    @if($currentCompany)
        <div
            role="status"
            aria-label="{{ app()->getLocale() === 'ar' ? 'الشركة النشطة' : 'Active company' }}"
            style="padding:.4rem 1rem;text-align:center;background:#e2e8f0;color:#0f172a;font-size:.875rem"
        >
            <strong>{{ app()->getLocale() === 'ar' ? 'الشركة النشطة:' : 'Active company:' }}</strong>
            {{ app()->getLocale() === 'ar' ? $currentCompany->name_ar : $currentCompany->name_en }}

            @if($availableCompanies->count() > 1)
                <form method="POST" action="{{ route('company.switch') }}" style="display:inline-flex;gap:.4rem;margin-inline-start:.6rem">
                    @csrf
                    <label class="sr-only" for="active-company-selector">
                        {{ app()->getLocale() === 'ar' ? 'تغيير الشركة' : 'Switch company' }}
                    </label>
                    <select id="active-company-selector" name="company_id">
                        @foreach($availableCompanies as $company)
                            <option value="{{ $company->id }}" @selected($company->id === $currentCompany->id)>
                                {{ app()->getLocale() === 'ar' ? $company->name_ar : $company->name_en }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit">{{ app()->getLocale() === 'ar' ? 'تغيير' : 'Switch' }}</button>
                </form>
            @endif
        </div>
    @endif
@endauth
