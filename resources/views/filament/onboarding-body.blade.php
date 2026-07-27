@include('onboarding-translations')

<script>
    window.__onboarding = @json($onboardingTranslations);

    // Delegate click on the replay tour menu item
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href="#"]');
        if (link && link.textContent.includes(@js(__('app.tour_replay')))) {
            e.preventDefault();
            window.JawlaOnboarding?.start('admin');
        }
    });

    @if(auth()->check() && !auth()->user()->onboarding_seen)
    document.addEventListener('DOMContentLoaded', () => {
        window.JawlaOnboarding?.start('admin');
    });
    @endif
</script>
