<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Form Pengajuan Barang Keluar (Outbound)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        @if(isset($id))
            <livewire:outbound.create-request :id="$id" />
        @else
            <livewire:outbound.create-request />
        @endif
    </div>
</x-app-layout>
