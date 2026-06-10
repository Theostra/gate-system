<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard Security (Pemindai Gerbang)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <livewire:security.gate-dashboard />
    </div>
</x-app-layout>
