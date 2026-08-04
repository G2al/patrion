<x-filament-widgets::widget>
    <x-filament::section heading="Azioni rapide">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($actions as $action)
                <x-filament::button :href="$action['url']" tag="a" color="gray" outlined>
                    {{ $action['label'] }}
                </x-filament::button>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
