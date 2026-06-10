<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Laporan & Audit Manajemen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <livewire:management.audit-dashboard />
    </div>
</x-app-layout>
