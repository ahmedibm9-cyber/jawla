<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.license_recovery') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 p-6">
    <main class="mx-auto max-w-2xl rounded-xl bg-white p-6 shadow">
        <h1 class="mb-4 text-2xl font-semibold">{{ __('app.license_recovery') }}</h1>
        @if($errors->any())<div class="mb-4 rounded bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('license.recovery.store') }}" class="space-y-4">
            @csrf
            <label class="block"><span class="mb-1 block">{{ __('app.license_document') }}</span><textarea name="document" rows="12" required class="form-textarea w-full">{{ old('document') }}</textarea></label>
            <label class="block"><span class="mb-1 block">{{ __('app.license_signature') }}</span><textarea name="signature" rows="5" required class="form-textarea w-full">{{ old('signature') }}</textarea></label>
            <button type="submit" class="btn btn-primary">{{ __('app.install_license') }}</button>
        </form>
    </main>
</body>
</html>
