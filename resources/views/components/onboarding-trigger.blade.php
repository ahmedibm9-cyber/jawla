@props(['role' => 'rep'])

@php
    $onboardingTranslations = [
        'tour_welcome' => __('app.tour_welcome'),
        'tour_welcome_desc' => __('app.tour_welcome_desc'),
        'tour_todays_plan' => __('app.tour_todays_plan'),
        'tour_todays_plan_desc' => __('app.tour_todays_plan_desc'),
        'tour_visits' => __('app.tour_visits'),
        'tour_visits_desc' => __('app.tour_visits_desc'),
        'tour_tab_bar' => __('app.tour_tab_bar'),
        'tour_tab_bar_desc' => __('app.tour_tab_bar_desc'),
        'tour_quotations' => __('app.tour_quotations'),
        'tour_quotations_desc' => __('app.tour_quotations_desc'),
        'tour_more_menu' => __('app.tour_more_menu'),
        'tour_more_menu_desc' => __('app.tour_more_menu_desc'),
        'tour_notifications' => __('app.tour_notifications'),
        'tour_notifications_desc' => __('app.tour_notifications_desc'),
        'tour_offline' => __('app.tour_offline'),
        'tour_offline_desc' => __('app.tour_offline_desc'),
    ];
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@14.3.0/dist/css/shepherd.css">
<style>{!! file_get_contents(resource_path('css/onboarding.css')) !!}</style>
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@14.3.0/dist/js/shepherd.min.js"></script>
<script src="{{ asset('js/onboarding.js') }}" defer></script>
<script>
    window.__onboarding = @json($onboardingTranslations);
</script>

@auth
@if(!auth()->user()->onboarding_seen)
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.JawlaOnboarding?.start('{{ $role }}');
    });
</script>
@endif
@endauth
