<div class="space-y-4">
    <dl class="grid grid-cols-2 gap-3 text-sm">
        <div><dt class="font-medium">{{ l('العميل', 'Customer') }}</dt><dd>{{ $submission->customer?->name_ar }}</dd></div>
        <div><dt class="font-medium">{{ l('المبلغ', 'Amount') }}</dt><dd>{{ number_format((float) $submission->amount, 2) }}</dd></div>
        <div><dt class="font-medium">{{ l('الطريقة', 'Method') }}</dt><dd>{{ $submission->method }}</dd></div>
        <div><dt class="font-medium">{{ l('المرجع', 'Reference') }}</dt><dd>{{ $submission->reference_number ?: '—' }}</dd></div>
    </dl>

    <div class="grid grid-cols-2 gap-3">
        @foreach($submission->photos as $photo)
            <a href="{{ $photo->url() }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $photo->url() }}" alt="{{ $photo->original_name }}" class="h-48 w-full rounded-lg object-cover">
            </a>
        @endforeach
    </div>
</div>
