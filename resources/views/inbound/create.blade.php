<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Form Pengajuan Inbound') }}
        </h2>
    </x-slot>

    <div class="py-12">
        @if(isset($id))
            <livewire:inbound.create-request :id="$id" />
        @else
            <livewire:inbound.create-request />
        @endif
    </div>
</x-app-layout>
