<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem">
            <div>
                <p style="font-size:1.125rem;font-weight:600">Namaste{{ $name ? ', '.$name : '' }}</p>
                <p style="font-size:.875rem;opacity:.7">What would you like to do today?</p>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                @foreach ($actions as $action)
                    <x-filament::button
                        tag="a"
                        :href="$action['url']"
                        :icon="$action['icon']"
                        :color="($action['primary'] ?? false) ? 'primary' : 'gray'"
                        :outlined="! ($action['primary'] ?? false)"
                        :badge="$action['badge'] ?? null"
                        badge-color="danger"
                        :target="($action['external'] ?? false) ? '_blank' : null"
                    >
                        {{ $action['label'] }}
                    </x-filament::button>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
