{{-- Automotive dealership cockpit — renders on company / owner HRM dashboard. Uses existing KPIs from DashboardController. --}}
<div class="row g-4 mb-4 motor-dealership-hub">
    <div class="col-12">
        <div class="motor-surface-flat relative overflow-hidden rounded-motor border border-motor-border bg-gradient-to-br from-motor-elevated via-motor-canvas to-motor-elevated px-8 py-7 shadow-motor lg:py-10">
            <div class="pointer-events-none absolute inset-0 opacity-[0.14]"
                style="
                    background-image:
                        radial-gradient(ellipse 680px 180px at 12% -20%, rgb(var(--motor-accent)), transparent 72%),
                        radial-gradient(circle at 94% 8%, rgb(var(--motor-accent-2)), transparent 54%);
                    ">
            </div>
            <div class="relative grid gap-8 lg:grid-cols-[1.3fr_auto] lg:items-end">
                <div>
                    <p class="motor-xs mb-3 font-semibold uppercase tracking-[0.16em] text-motor-accent">
                        {{ __('Operations overview') }}</p>
                    <h2 class="text-balance font-sans text-3xl font-semibold tracking-tight text-motor-ink sm:text-[2rem] lg:leading-tight">
                        {{ __('Run the dealership from one calm command surface.') }}</h2>
                    <p class="mt-4 max-w-2xl text-motor-lg leading-relaxed text-motor-muted">
                        {{ __('Track people, deliveries, workshop throughput, and the customer handshake — without juggling separate tools.') }}
                    </p>
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <span class="motor-text-vin border-motor-border/80 bg-motor-elevated/90 text-motor-ink">
                            {{ __('Sample VIN') }}: {{ '1HGCM82633A004352' }}</span>
                        @canany(['manage lead', 'manage deal', 'manage contact'])
                            <a href="{{ route('crm.dashboard') }}" class="btn btn-light-primary px-5 py-2.5">{{ __('CRM workspace') }}</a>
                        @endcanany
                    </div>
                </div>
                <div class="motor-surface-flat hidden max-w-xl rounded-xl border border-motor-border bg-motor-elevated/90 p-5 shadow-motor md:block lg:justify-self-end">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="motor-sm font-semibold text-motor-ink">{{ __('Service lane') }}</p>
                            <p class="motor-sm text-motor-muted">
                                {{ __('From intake diagnostics to fulfilled delivery.') }}</p>
                        </div>
                        <span
                            class="rounded-full bg-motor-accent/12 px-2.5 py-1 motor-xs font-semibold text-motor-accent">{{ __('Live') }}</span>
                    </div>
                    <div class="mt-6">
                        <x-motor.workflow :steps="[
                            __('Intake'),
                            __('Diagnostics'),
                            __('Parts'),
                            __('Delivery'),
                            __('Closed loop'),
                        ]" :active-index="2" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <x-motor.stat :label="__('People on the floor')" :value="$countUser + $countClient" :hint="__('Staff + showroom guests')"
            icon-class="ti ti-users" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-motor.stat :label="__('Active openings')" :value="$activeJob" :hint="__('Roles you are recruiting for')"
            icon-class="ti ti-briefcase" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-motor.stat :label="__('Paused reqs')" :value="$inActiveJOb" :hint="__('Bench / onboarding holds')"
            icon-class="ti ti-player-pause" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-motor.stat :label="__('Training in bay')" :value="$onGoingTraining" :hint="__('Certifications in flight')"
            icon-class="ti ti-school" />
    </div>

    <div class="col-lg-5">
        <div class="motor-surface-flat h-full rounded-motor border border-motor-border bg-motor-elevated p-6 shadow-motor">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-motor-lg font-semibold text-motor-ink">{{ __('Showroom vignette') }}</h3>
                    <p class="mt-2 motor-sm text-motor-muted">
                        {{ __('A gallery layout you can reuse on inventory routes — vehicle cards adapt to photo or placeholder.') }}</p>
                </div>
            </div>
            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <x-motor.vehicle-card :title="__('2026 Aurora EV Tourer')" :subtitle="__('Premium trim · inbound port')" :status="__('Inbound')" status-variant="warning"
                    image="https://images.unsplash.com/photo-1617788138017-80ad40651399?auto=format&fit=crop&w=960&q=80"
                    :meta="[
                        __('Odometer') => '142 km',
                        __('Margin band') => __('Medium'),
                        __('Buyer stage') => __('Qualified'),
                    ]" />
                <x-motor.vehicle-card :title="__('Urban X3 Sport')" :subtitle="__('Certified pre-owned')" :status="__('Retail ready')" status-variant="success"
                    :meta="[
                        __('VIN snippet') => '…33A004',
                        __('Lot') => 'North C14',
                        __('Photos') => '42',
                    ]" />
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="motor-surface-flat h-full rounded-motor border border-motor-border bg-motor-elevated p-6 shadow-motor">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-motor-border pb-5">
                <div>
                    <h3 class="text-motor-lg font-semibold text-motor-ink">{{ __('Deal timeline preview') }}</h3>
                    <p class="mt-2 motor-sm text-motor-muted">
                        {{ __('Pair this pattern with CRM deals to visualize the customer journey.') }}</p>
                </div>
                <span class="rounded-full bg-motor-canvas px-4 py-1.5 motor-sm font-semibold text-motor-ink">{{ __('Purchase path') }}</span>
            </div>
            <ul class="relative mt-6 space-y-0 ps-0">
                <x-motor.timeline-event :accent="false" :title="__('Finance approved')" :time="__('Tue · 09:24')" />
                <x-motor.timeline-event :accent="false" :title="__('Accessory bundle resolved')" :time="__('Tue · 11:06')" />
                <x-motor.timeline-event :accent="true" :title="__('Vehicle detailed & staged')" :time="__('Wed · 14:40')" />
                <x-motor.timeline-event :accent="false" :title="__('Customer delivery window locked')" :time="__('Thu · 10:15')" />
            </ul>
        </div>
    </div>
</div>
