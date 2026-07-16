<div class="main-content" style="padding:16px"
     x-data="{
        step: '{{ $step }}',
        userLat: null,
        userLng: null,
        online: navigator.onLine,
        draftKey: 'visit_draft_{{ $visit->id }}',
        draftInterval: null,
        init() {
            window.addEventListener('online', () => this.online = true);
            window.addEventListener('offline', () => this.online = false);

            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.userLat = pos.coords.latitude;
                        this.userLng = pos.coords.longitude;
                        $wire.set('userLat', this.userLat);
                        $wire.set('userLng', this.userLng);
                        $wire.checkGps();
                    },
                    () => $wire.set('errorMessage', 'GPS {{ app()->getLocale() === "ar" ? "غير متوفر" : "unavailable" }}')
                );
            }

            const saved = localStorage.getItem(this.draftKey);
            if (saved) {
                try {
                    const draft = JSON.parse(saved);
                    if (draft.summary && !$wire.summary) $wire.set('summary', draft.summary);
                    if (draft.customerFeedback && !$wire.customerFeedback) $wire.set('customerFeedback', draft.customerFeedback);
                    if (draft.actionTaken && !$wire.actionTaken) $wire.set('actionTaken', draft.actionTaken);
                } catch (e) {}
            }

            this.draftInterval = setInterval(() => {
                const d = {
                    summary: $wire.summary,
                    customerFeedback: $wire.customerFeedback,
                    actionTaken: $wire.actionTaken,
                };
                if (d.summary || d.customerFeedback || d.actionTaken) {
                    localStorage.setItem(this.draftKey, JSON.stringify(d));
                }
            }, 3000);
        }
     }">

    @if(!$online)
        <div class="card" style="background:#FEF3C7;color:#92400E;margin-bottom:12px;display:flex;align-items:center;gap:8px">
            <svg aria-hidden="true" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M5.636 18.364a9 9 0 010-12.728M15.536 8.464a5 5 0 010 7.072M8.464 15.536a5 5 0 010-7.072"/></svg>
            <span>{{ app()->getLocale() === 'ar' ? 'غير متصل — سيتم حفظ المسودة' : 'Offline — draft will be saved' }}</span>
        </div>
    @endif

    {{-- Stepper --}}
    <div class="stepper">
        <div class="step done">
            <div class="step-dot">&#10003;</div>
            <small>{{ __('app.scheduled') }}</small>
        </div>
        <div class="step-line done"></div>
        <div class="step {{ $step === 'checkin' ? 'active' : ($step !== 'checkin' ? 'done' : '') }}">
            <div class="step-dot">{{ $step !== 'checkin' ? '&#10003;' : '2' }}</div>
            <small>{{ __('app.arrived') }}</small>
        </div>
        <div class="step-line {{ $step === 'report' ? 'active' : ($step === 'done' ? 'done' : '') }}"></div>
        <div class="step {{ $step === 'report' ? 'active' : ($step === 'done' ? 'done' : '') }}">
            <div class="step-dot">{{ $step === 'done' ? '&#10003;' : '3' }}</div>
            <small>{{ __('app.report') }}</small>
        </div>
    </div>

    {{-- Customer Info --}}
    <div class="card" style="margin-bottom:16px">
        <h3 style="margin:0 0 4px">{{ $customer->name_ar }}</h3>
        <p style="margin:0;color:#6b7280;font-size:0.9rem">{{ $customer->address }}</p>
        @if($distanceMeters !== null)
            <p style="margin:8px 0 0;font-size:0.85rem;color:{{ $withinRange ? '#16A34A' : '#DC2626' }}">
                {{ round($distanceMeters) }}m
                {{ $withinRange ? __('app.in_range') : __('app.out_of_range') }}
            </p>
        @endif
    </div>

    {{-- Step: Check-in with GPS --}}
    @if($step === 'checkin')
        @if($errorMessage)
            <div class="card" aria-live="polite" style="background:#FEF2F2;color:#DC2626;margin-bottom:16px">{{ $errorMessage }}</div>
        @endif

        @if(!$withinRange && $distanceMeters !== null)
            <div class="card" style="margin-bottom:16px;border:2px solid #F59E0B">
                <p style="margin:0 0 12px;color:#92400E;font-weight:600">{{ __('app.out_of_range_warning') }}: {{ round($distanceMeters) }}m</p>
                <button class="btn btn-outline" style="width:100%" wire:click="skipGpsAndConfirm">
                    {{ __('app.confirm_anyway') }}
                </button>
            </div>
        @elseif($withinRange)
            <div class="card" style="text-align:center;padding:24px;background:#DCFCE7;border:2px solid #16A34A">
                <div style="font-size:3rem">&#10004;</div>
                <p style="font-weight:700;color:#15803D;margin:8px 0">{{ __('app.arrived_confirmed') }}</p>
            </div>
        @else
            <div class="card" style="text-align:center;padding:24px;color:#6b7280">
                <p>{{ __('app.waiting_gps') }}</p>
            </div>
        @endif
    @endif

    {{-- Step: Visit Report --}}
    @if($step === 'report')
        <div class="card">
            <label for="summary" style="font-weight:600;display:block;margin-bottom:4px">{{ __('app.report_summary') }} *</label>
            <textarea wire:model="summary" rows="3" autocomplete="off" id="summary" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px" required></textarea>
            @error('summary') <small style="color:#DC2626">{{ $message }}</small> @enderror
        </div>

        <div class="card">
            <label for="customerFeedback" style="font-weight:600;display:block;margin-bottom:4px">{{ __('app.customer_feedback') }}</label>
            <textarea wire:model="customerFeedback" rows="2" autocomplete="off" id="customerFeedback" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px"></textarea>
        </div>

        <div class="card">
            <label for="actionTaken" style="font-weight:600;display:block;margin-bottom:4px">{{ __('app.action_taken') }}</label>
            <textarea wire:model="actionTaken" rows="2" autocomplete="off" id="actionTaken" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px"></textarea>
        </div>

        <div class="card">
            <label style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" wire:model="followUpNeeded">
                <span style="font-weight:600">{{ __('app.follow_up_needed') }}</span>
            </label>
            @if($followUpNeeded)
                <textarea wire:model="followUpNote" rows="2" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;margin-top:8px" placeholder="{{ __('app.follow_up_placeholder') }}"></textarea>
            @endif
        </div>

        {{-- Signature --}}
        <div class="card">
            <label style="font-weight:600;display:block;margin-bottom:8px">{{ __('app.signature') }}</label>
            <canvas id="sigCanvas" width="340" height="140" aria-label="{{ __('app.signature') }}" role="img"
                style="border:2px dashed #d1d5db;border-radius:8px;display:block;width:100%;touch-action:none"
                x-data="{ drawing:false, ctx:null }"
                x-init="ctx = $el.getContext('2d'); ctx.strokeStyle='#1f2937';ctx.lineWidth=2;ctx.lineCap='round'"
                @mousedown="drawing=true;ctx.beginPath();ctx.moveTo($event.offsetX,$event.offsetY)"
                @mousemove="if(drawing){ctx.lineTo($event.offsetX,$event.offsetY);ctx.stroke()}"
                @mouseup="drawing=false;$wire.set('signature', $el.toDataURL())"
                @mouseleave="drawing=false"
                @touchstart.prevent="drawing=true;ctx.beginPath();ctx.moveTo($event.touches[0].clientX-$el.getBoundingClientRect().left,$event.touches[0].clientY-$el.getBoundingClientRect().top)"
                @touchmove.prevent="if(drawing){ctx.lineTo($event.touches[0].clientX-$el.getBoundingClientRect().left,$event.touches[0].clientY-$el.getBoundingClientRect().top);ctx.stroke()}"
                @touchend="drawing=false;$wire.set('signature', $el.toDataURL())">
            </canvas>
            <button class="btn btn-outline" style="margin-top:8px;font-size:0.85rem" onclick="document.getElementById('sigCanvas').getContext('2d').clearRect(0,0,340,140);document.getElementById('sigCanvas').dispatchEvent(new Event('touchend'))">{{ __('app.clear') }}</button>
        </div>

        <button class="btn btn-primary" style="width:100%" wire:click="submitReport" wire:loading.attr="disabled">
            <span wire:loading.remove>{{ __('app.submit_report') }}</span>
            <span wire:loading>{{ __('app.saving') }}&hellip;</span>
        </button>
    @endif

    {{-- Step: Done --}}
    @if($step === 'done')
        <div class="card" style="text-align:center;padding:32px 16px;background:#DCFCE7">
            <div style="font-size:4rem">&#10004;</div>
            <h2 style="color:#15803D;margin:12px 0 4px">{{ __('app.report_submitted') }}</h2>
            <p style="color:#6b7280;margin:0">{{ __('app.visit_complete') }}</p>
            <a href="/app" class="btn btn-primary" style="display:inline-block;margin-top:16px;text-decoration:none">{{ __('app.back_home') }}</a>
        </div>
    @endif
</div>