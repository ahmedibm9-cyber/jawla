<div
  x-data="{
    pulling: false,
    startY: 0,
    pullDistance: 0,
    threshold: 80,
    refreshing: false,
    onTouchStart(e) {
      if (window.scrollY > 10) return;
      this.startY = e.touches[0].clientY;
      this.pulling = true;
    },
    onTouchMove(e) {
      if (!this.pulling || this.refreshing) return;
      const diff = e.touches[0].clientY - this.startY;
      if (diff > 0) {
        this.pullDistance = Math.min(diff * 0.5, 120);
        if (this.pullDistance > 10) e.preventDefault();
      }
    },
    onTouchEnd() {
      if (!this.pulling) return;
      if (this.pullDistance >= this.threshold) {
        this.refreshing = true;
        this.pullDistance = 60;
        $wire.$refresh().then(() => {
          setTimeout(() => {
            this.refreshing = false;
            this.pullDistance = 0;
          }, 500);
        });
      } else {
        this.pullDistance = 0;
      }
      this.pulling = false;
    }
  }"
  x-on:touchstart="onTouchStart"
  x-on:touchmove.passive="onTouchMove"
  x-on:touchend="onTouchEnd"
>
  <div
    x-show="pullDistance > 0"
    x-transition
    aria-live="polite"
    class="flex items-center justify-center py-2 text-gray-400 text-sm"
    :style="'height:' + pullDistance + 'px'"
  >
    <span x-show="!refreshing">{{ __('app.pull_to_refresh') ?? 'Pull to refresh' }}</span>
    <span x-show="refreshing">{{ __('app.refreshing') ?? 'Refreshing…' }}</span>
  </div>
  {{ $slot }}
</div>
