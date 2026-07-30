<x-filament-panels::page>
    <div
        class="dashboard-widget-grid"
        role="list"
        aria-label="{{ l('عناصر لوحة التحكم', 'Dashboard widgets') }}"
        x-data="{
            draggedKey: null,
            startDrag(event, key) {
                this.draggedKey = key
                event.dataTransfer.effectAllowed = 'move'
                event.dataTransfer.setData('text/plain', key)
            },
            endDrag() {
                this.draggedKey = null
            },
            drop(event, targetKey) {
                event.preventDefault()

                const draggedKey = this.draggedKey ?? event.dataTransfer.getData('text/plain')

                if (! draggedKey || draggedKey === targetKey) {
                    this.endDrag()
                    return
                }

                const order = Array.from(event.currentTarget.parentElement.querySelectorAll('[data-dashboard-widget-key]'))
                    .map((widget) => widget.dataset.dashboardWidgetKey)
                const from = order.indexOf(draggedKey)
                const to = order.indexOf(targetKey)

                if (from === -1 || to === -1) {
                    this.endDrag()
                    return
                }

                order.splice(from, 1)
                order.splice(to, 0, draggedKey)
                this.$wire.reorderDashboardWidgets(order)
                this.endDrag()
            },
        }"
    >
        @foreach ($this->getDashboardWidgetEntries() as $widget)
            <section
                wire:key="dashboard-widget-{{ $widget['key'] }}"
                data-dashboard-widget-key="{{ $widget['key'] }}"
                class="dashboard-widget-item"
                role="listitem"
                :class="{ 'is-dragging': draggedKey === @js($widget['key']) }"
                x-on:dragover.prevent
                x-on:drop="drop($event, @js($widget['key']))"
            >
                @if ($widget['url'])
                    <a
                        href="{{ $widget['url'] }}"
                        class="dashboard-widget-open-link"
                        aria-label="{{ l('فتح صفحة: ', 'Open page: ') }}{{ $widget['label'] }}"
                        title="{{ l('فتح صفحة: ', 'Open page: ') }}{{ $widget['label'] }}"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="dashboard-widget-open-icon" />
                    </a>
                @endif

                <button
                    type="button"
                    draggable="true"
                    class="dashboard-widget-drag-handle"
                    aria-label="{{ l('اسحب لإعادة ترتيب: ', 'Drag to reorder: ') }}{{ $widget['label'] }}"
                    title="{{ l('اسحب لإعادة الترتيب: ', 'Drag to reorder: ') }}{{ $widget['label'] }}"
                    x-on:dragstart="startDrag($event, @js($widget['key']))"
                    x-on:dragend="endDrag()"
                >
                    <x-filament::icon icon="heroicon-o-bars-3" class="dashboard-widget-drag-icon" />
                </button>

                @livewire($widget['class'], $widget['properties'], key('dashboard-widget-'.$widget['key']))
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
