<div class="main-content"
     x-data="{
        step: @js($step),
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
                        $wire.set('errorMessage', 'GPS {{ l("غير متوفر", "unavailable") }}');
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
        },
        destroy() {
            if (this.draftInterval) clearInterval(this.draftInterval);
        }
     }">

    <x-page-header
        :title="$customer->name_ar"
        :subtitle="$customer->address"
    />

    <div class="page-body" x-effect="step = $wire.step; window.scrollTo(0,0)">
    <div x-show="!online" x-cloak class="card bg-amber-50 text-amber-800 dark:bg-amber-900/20 dark:text-amber-400 mb-3 flex items-center gap-2">
        <x-heroicon-o-signal-slash width="18" height="18" aria-hidden="true" />
        <span>{{ l('غير متصل — سيتم حفظ المسودة', 'Offline — draft will be saved') }}</span>
    </div>

    {{-- Stepper --}}
    <div class="stepper" role="list" aria-label="{{ l('خطوات', 'Steps') }}">
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
            <div class="card bg-danger/10 text-danger mb-4 flex justify-between items-center" aria-live="polite">
                <span>{{ $errorMessage }}</span>
                <button type="button" wire:click="$set('errorMessage', '')" aria-label="{{ __('app.clear') }}" class="btn-ghost-close">&times;</button>
            </div>
        @endif

        @if($gpsDenied)
            <div class="card mb-4 border-2 border-danger bg-danger/10" role="alert">
                <p class="m-0 mb-1 text-danger font-bold">{{ __('app.gps_required_title') }}</p>
                <p class="m-0 mb-3 text-sm text-text-secondary">{{ __('app.gps_required_help') }}</p>
                <button type="button" class="btn btn-outline w-full" @click="requestGps()">
                    {{ __('app.retry') }}
                </button>
            </div>
        @elseif(!$withinRange && $distanceMeters !== null)
            <div class="card mb-4 border-2 border-danger bg-danger/10" role="alert">
                <p class="m-0 mb-1 text-danger font-bold">{{ __('app.out_of_range') }}</p>
                <p class="m-0 mb-3 text-sm text-text-secondary">
                    {{ __('app.out_of_range_blocked', ['distance' => round($distanceMeters), 'radius' => $customer->company?->geofence_radius_m ?? 500]) }}
                </p>
                <button type="button" class="btn btn-outline w-full" @click="requestGps()">
                    {{ __('app.retry') }}
                </button>
            </div>
        @elseif($withinRange)
            <div class="card text-center p-6 bg-success/10 border-2 border-success">
                <x-heroicon-o-check-circle class="size-12 text-success mx-auto mb-2" stroke-width="2.5" aria-hidden="true" />
                <p class="font-bold text-success my-2">{{ __('app.arrived_confirmed') }}</p>
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
            <textarea wire:model="summary" rows="3" autocomplete="off" id="summary" class="form-textarea" required @error('summary') aria-invalid="true" aria-describedby="summary-error" @enderror></textarea>
            @error('summary') <small id="summary-error" class="text-danger">{{ $message }}</small> @enderror
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
            <label for="followUpNeeded" class="flex items-center gap-2">
                <input type="checkbox" wire:model="followUpNeeded" id="followUpNeeded">
                <span class="font-semibold">{{ __('app.follow_up_needed') }}</span>
            </label>
            @if($followUpNeeded)
                <textarea wire:model="followUpNote" rows="2" class="form-textarea mt-2" aria-label="{{ __('app.follow_up_needed') }}" placeholder="{{ __('app.follow_up_placeholder') }}"></textarea>
            @endif
        </div>

        {{-- Photos (online only — capture uploads immediately) --}}
        <div class="card">
            <label class="font-semibold block mb-1">{{ l('صور', 'Photos') }}</label>
            <livewire:app.photo-capture />
        </div>

        {{-- Signature --}}
        <div class="card">
            <label class="font-semibold block mb-1">{{ __('app.signature') }}</label>
            <p class="m-0 mb-2 text-xs text-text-muted">{{ l('التوقيع اختياري — إذا تعذر التوقيع باللمس، دوّن اسم العميل في ملخص الزيارة.', 'Signature is optional — if touch signing is not possible, note the customer name in the visit summary.') }}</p>
            {{-- bg-white is intentional in both themes: the signature area is "paper"
                 so the dark ink stays visible while drawing on dark mode. The stored
                 PNG (toDataURL) is transparent+ink and renders fine on the white PDF. --}}
            <canvas id="sigCanvas" aria-label="{{ __('app.signature') }}" role="img"
                class="border-2 border-dashed border-border rounded-lg block w-full touch-none bg-white"
                x-data="{ drawing:false, ctx:null }"
                x-init="
                    const r = $el.getBoundingClientRect();
                    const dpr = window.devicePixelRatio || 1;
                    $el.width = r.width * dpr;
                    $el.height = 140 * dpr;
                    $el.style.width = r.width + 'px';
                    $el.style.height = '140px';
                    ctx = $el.getContext('2d');
                    ctx.scale(dpr, dpr);
                    ctx.strokeStyle='#1f2937';
                    ctx.lineWidth=2;
                    ctx.lineCap='round';
                "
                @mousedown="drawing=true;ctx.beginPath();ctx.moveTo($event.offsetX,$event.offsetY)"
                @mousemove="if(drawing){ctx.lineTo($event.offsetX,$event.offsetY);ctx.stroke()}"
                @mouseup="drawing=false;$wire.set('signature', $el.toDataURL())"
                @mouseleave="drawing=false"
                @touchstart.prevent="drawing=true;ctx.beginPath();ctx.moveTo($event.touches[0].clientX-$el.getBoundingClientRect().left,$event.touches[0].clientY-$el.getBoundingClientRect().top)"
                @touchmove.prevent="if(drawing){ctx.lineTo($event.touches[0].clientX-$el.getBoundingClientRect().left,$event.touches[0].clientY-$el.getBoundingClientRect().top);ctx.stroke()}"
                @touchend="drawing=false;$wire.set('signature', $el.toDataURL())">
            </canvas>
            <button class="btn btn-outline mt-2 text-sm" x-on:click="()=>{let c=$event.target.closest('div').querySelector('#sigCanvas').getContext('2d');c.clearRect(0,0,$event.target.closest('div').querySelector('#sigCanvas').width,$event.target.closest('div').querySelector('#sigCanvas').height);$wire.set('signature','')}" aria-label="{{ __('app.clear') }}">{{ __('app.clear') }}</button>
        </div>

        <x-ds.modal :title="__('app.confirm_report_title') ?? 'Submit visit report?'" :message="__('app.confirm_report_msg') ?? 'This visit report will be submitted permanently. You cannot edit it after submission.'">
            <x-slot:trigger>
                <button type="button" class="btn btn-primary w-full">
                    <span wire:loading.remove>{{ __('app.submit_report') }}</span>
                    <span wire:loading>{{ __('app.saving') }}&hellip;</span>
                </button>
            </x-slot:trigger>
            <x-slot:confirm>
                {{-- Online: submit normally. Offline: queue the report (with signature) to the outbox (CG2). --}}
                <button type="button" wire:loading.attr="disabled" class="btn btn-primary w-full"
                    x-data
                    x-on:click="
                        if (navigator.onLine) {
                            $wire.submitReport();
                        } else if (($wire.summary || '').trim().length < 5) {
                            alert(@js(l('يرجى كتابة ملخص الزيارة (5 أحرف على الأقل).', 'Please write a visit summary (at least 5 characters).')));
                        } else {
                            window.jawlaSync.enqueue('visit_report', {
                                visit_id: {{ $visit->id }},
                                summary: $wire.summary,
                                customer_feedback: $wire.customerFeedback,
                                action_taken: $wire.actionTaken,
                                follow_up_needed: $wire.followUpNeeded,
                                follow_up_note: $wire.followUpNote,
                                signature: $wire.signature,
                            });
                            $wire.queueOffline();
                        }
                    ">{{ __('app.confirm') }}</button>
            </x-slot:confirm>
        </x-ds.modal>
    @endif

    {{-- Step: Done --}}
    @if($step === 'done')
        <div class="card text-center p-8 {{ $queuedOffline ? 'bg-warning/10' : 'bg-success/10' }}">
            @if($queuedOffline)
                <x-heroicon-o-cloud-arrow-up class="size-16 text-warning mx-auto mb-2" stroke-width="2.5" aria-hidden="true" />
                <h2 class="text-warning my-3 mb-1" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">{{ l('بانتظار المزامنة', 'Queued to sync') }}</h2>
                <p class="text-text-secondary m-0">{{ l('تم حفظ تقرير الزيارة دون اتصال وستتم مزامنته تلقائيًا عند عودة الاتصال.', 'Visit report saved offline — it will sync automatically when you are back online.') }}</p>
            @else
                <x-heroicon-o-check-circle class="size-16 text-success mx-auto mb-2" stroke-width="2.5" aria-hidden="true" />
                <h2 class="text-success my-3 mb-1" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())">{{ __('app.report_submitted') }}</h2>
                <p class="text-text-secondary m-0">{{ __('app.visit_complete') }}</p>
            @endif
            <a href="/app" class="btn btn-primary inline-block mt-4 no-underline">{{ __('app.back_home') }}</a>
        </div>
    @endif
    </div>
</div>