@auth
    @php
        $currentCompany = $activeCompany ?? app(\App\Support\ActiveCompanyContext::class)->company();
        $availableCompanies = auth()->user()->companies()->where('companies.is_active', true)->orderBy('name_en')->get();
    @endphp

    @if($currentCompany)
        <span class="active-company-chip" role="status" aria-label="{{ app()->getLocale() === 'ar' ? 'الشركة النشطة' : 'Active company' }}">
            <span class="active-company-label">{{ app()->getLocale() === 'ar' ? $currentCompany->name_ar : $currentCompany->name_en }}</span>

            @if($availableCompanies->count() > 1)
                <form method="POST" action="{{ route('company.switch') }}" class="active-company-form">
                    @csrf
                    <label class="sr-only" for="active-company-selector-{{ $panel ?? 'default' }}">
                        {{ app()->getLocale() === 'ar' ? 'تغيير الشركة' : 'Switch company' }}
                    </label>
                    <select id="active-company-selector-{{ $panel ?? 'default' }}" name="company_id" class="active-company-select">
                        @foreach($availableCompanies as $company)
                            <option value="{{ $company->id }}" @selected($company->id === $currentCompany->id)>
                                {{ app()->getLocale() === 'ar' ? $company->name_ar : $company->name_en }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="active-company-btn">{{ app()->getLocale() === 'ar' ? 'تغيير' : 'Switch' }}</button>
                </form>
            @endif
        </span>
    @endif
@endauth
