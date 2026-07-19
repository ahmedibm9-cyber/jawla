<div class="main-content"
     x-data="{
        step: '{{ $step }}',
        userLat: null,
        userLng: null,
        online: navigator.onLine,
        draftKey: 'visit_draft_{{ $visit->id }}',
        draftInterval: null,
        requestGps() {
            if (!('geolocation' in navigator)) {
                $wire.markGpsDenied();
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.userLat = pos.coords.latitude;
                    this.userLng = pos.coords.longitude;
                    $wire.set('userLat', this.userLat, false);
                    $wire.set('userLng', this.userLng, false);
                    $wire.set('userAccuracy', pos.coords.accuracy, false);
                    $wire.checkGps();
                },
                (err) => {
                    if (err.code === 1) {
                        $wire.markGpsDenied();
                    } else {
                        $wire.set('errorMessage', 'GPS {{ app()->getLocale() === "ar" ? "غير متوفر" : "unavailable" }}');
                    }
                },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        },
        init() {
            window.addEventListener('online', () => this.online = true);
            window.addEventListener('offline', () => this.online = false);

            this.requestGps();

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

    <x-page-header
        :title="$customer->name_ar"
        :subtitle="$customer->address"
    />

    <div class="page-body">
    <div x-show="!online" x-cloak class="card bg-amber-50 text-amber-800 mb-3 flex items-center gap-2">
        <svg aria-hidden="true" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728M5.636 18.364a9 9 0 010-12.728M15.536 8.464a5 5 0 010 7.072M8.464 15.536a5 5 0 010-7.072"/></svg>
        <span>{{ app()->getLocale() === 'ar' ? 'غير متصل — سيتم حفظ المسودة' : 'Offline — draft will be saved' }}</span>
    </div>

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
    <div class="card mb-4 min-w-0">
        <h3 class="m-0 mb-1">{{ $customer->name_ar }}</h3>
        <p class="m-0 text-text-secondary text-sm">{{ $customer->address }}</p>
        @if($distanceMeters !== null)
            <p class="mt-2 text-sm {{ $withinRange ? 'text-success' : 'text-danger' }}">
                {{ round($distanceMeters) }}m
                {{ $withinRange ? __('app.in_range') : __('app.out_of_range') }}
            </p>
        @endif
    </div>

    {{-- Step: Check-in with GPS --}}
    @if($step === 'checkin')
        @if($errorMessage)
            <div class="card bg-red-50 text-danger mb-4 flex justify-between items-center" aria-live="polite">
                <span>{{ $errorMessage }}</span>
                <button type="button" wire:click="$set('errorMessage', '')" class="text-danger bg-transparent border-0 cursor-pointer text-lg px-2">&times;</button>
            </div>
        @endif

        @if($gpsDenied)
            <div class="card mb-4 border-2 border-danger bg-red-50" role="alert">
                <p class="m-0 mb-1 text-danger font-bold">{{ __('app.gps_required_title') }}</p>
                <p class="m-0 mb-3 text-sm text-text-secondary">{{ __('app.gps_required_help') }}</p>
                <button type="button" class="btn btn-outline w-full" @click="requestGps()">
                    {{ __('app.retry') }}
                </button>
            </div>
        @elseif(!$withinRange && $distanceMeters !== null)
            <div class="card mb-4 border-2 border-danger bg-red-50" role="alert">
                <p class="m-0 mb-1 text-danger font-bold">{{ __('app.out_of_range') }}</p>
                <p class="m-0 mb-3 text-sm text-text-secondary">
                    {{ __('app.out_of_range_blocked', ['distance' => round($distanceMeters), 'radius' => $customer->company?->geofence_radius_m ?? 500]) }}
                </p>
                <button type="button" class="btn btn-outline w-full" @click="requestGps()">
                    {{ __('app.retry') }}
                </button>
            </div>
        @elseif($withinRange)
            <div class="card text-center p-6 bg-green-50 border-2 border-success">
                <svg aria-hidden="true" class="size-12 text-success mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <p class="font-bold text-green-700 my-2">{{ __('app.arrived_confirmed') }}</p>
            </div>
        @else
            <div class="card text-center p-6 text-text-secondary">
                <p>{{ __('app.waiting_gps') }}</p>
            </div>
        @endif
    @endif

    {{-- Step: Visit Report --}}
    @if($step === 'report')
        <div class="card">
            <label for="summary" class="font-semibold block mb-1">{{ __('app.report_summary') }} *</label>
            <textarea wire:model="summary" rows="3" autocomplete="off" id="summary" class="form-textarea" required></textarea>
            @error('summary') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="card">
            <label for="customerFeedback" class="font-semibold block mb-1">{{ __('app.customer_feedback') }}</label>
            <textarea wire:model="customerFeedback" rows="2" autocomplete="off" id="customerFeedback" class="form-textarea"></textarea>
        </div>

        <div class="card">
            <label for="actionTaken" class="font-semibold block mb-1">{{ __('app.action_taken') }}</label>
            <textarea wire:model="actionTaken" rows="2" autocomplete="off" id="actionTaken" class="form-textarea"></textarea>
        </div>

        <div class="card">
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="followUpNeeded">
                <span class="font-semibold">{{ __('app.follow_up_needed') }}</span>
            </label>
            @if($followUpNeeded)
                <textarea wire:model="followUpNote" rows="2" class="form-textarea mt-2" placeholder="{{ __('app.follow_up_placeholder') }}"></textarea>
            @endif
        </div>

        {{-- Signature --}}
        <div class="card">
            <label class="font-semibold block mb-2">{{ __('app.signature') }}</label>
            <canvas id="sigCanvas" width="340" height="140" aria-label="{{ __('app.signature') }}" role="img"
                class="border-2 border-dashed border-border rounded-lg block w-full touch-none"
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
            <button class="btn btn-outline mt-2 text-sm" x-on:click="()=>{let c=$event.target.closest('div').querySelector('#sigCanvas').getContext('2d');c.clearRect(0,0,340,140);$wire.set('signature','')}" aria-label="{{ __('app.clear') }}">{{ __('app.clear') }}</button>
        </div>

        <button class="btn btn-primary w-full" wire:click="submitReport" wire:loading.attr="disabled">
            <span wire:loading.remove>{{ __('app.submit_report') }}</span>
            <span wire:loading>{{ __('app.saving') }}&hellip;</span>
        </button>
    @endif

    {{-- Step: Done --}}
    @if($step === 'done')
        <div class="card text-center p-8 bg-green-50">
            <svg aria-hidden="true" class="size-16 text-success mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <h2 class="text-green-700 my-3 mb-1">{{ __('app.report_submitted') }}</h2>
            <p class="text-text-secondary m-0">{{ __('app.visit_complete') }}</p>
            <a href="/app" class="btn btn-primary inline-block mt-4 no-underline">{{ __('app.back_home') }}</a>
        </div>
    @endif
    </div>
</div>