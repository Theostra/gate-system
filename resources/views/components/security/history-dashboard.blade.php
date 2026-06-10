<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GateLog;
use Carbon\Carbon;

new class extends Component
{
    use WithPagination;

    public $selectedLogId = null;
    public $selectedLogDetails = null;

    public function showDetails($logId)
    {
        $this->selectedLogId = $logId;
        $this->selectedLogDetails = GateLog::with('gateRequest')->findOrFail($logId);
    }

    public function closeDetails()
    {
        $this->selectedLogId = null;
        $this->selectedLogDetails = null;
    }

    public function with(): array
    {
        return [
            'logs' => GateLog::with('gateRequest')
                        ->where('user_id', auth()->id())
                        ->orderBy('created_at', 'desc')
                        ->paginate(15),
        ];
    }
};
?>

<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">Histori Scan Gerbang (Hari Ini)</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Waktu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tindakan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Kendaraan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Detail Ceklis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $log->created_at->format('d M Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 inline-flex items-center gap-1.5 text-xs font-semibold rounded-md border 
                                        @if(str_contains($log->action, 'APPROVE')) border-indigo-100 bg-indigo-50/50 text-indigo-700 dark:border-indigo-900/30 dark:bg-indigo-950/20 dark:text-indigo-400
                                        @elseif(str_contains($log->action, 'REJECT')) border-red-100 bg-red-50/50 text-red-700 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-400
                                        @else border-gray-200 bg-gray-100 text-gray-800 dark:border-gray-750 dark:bg-gray-800 dark:text-gray-300 @endif">
                                        <span class="h-1.5 w-1.5 rounded-full 
                                            @if(str_contains($log->action, 'APPROVE')) bg-indigo-500
                                            @elseif(str_contains($log->action, 'REJECT')) bg-red-500
                                            @else bg-gray-500 @endif"></span>
                                        {{ str_replace('_', ' ', $log->action) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ optional($log->gateRequest)->vehicle_number ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if(optional($log->gateRequest)->type)
                                        <span class="px-2 py-0.5 inline-flex text-xs font-medium rounded border {{ $log->gateRequest->type === 'INBOUND' ? 'border-indigo-150 bg-indigo-50/30 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300' : 'border-orange-100 bg-orange-50 text-orange-700 dark:border-orange-900/30 dark:bg-orange-950/20 dark:text-orange-400' }}">
                                            {{ $log->gateRequest->type === 'INBOUND' ? 'MASUK' : 'KELUAR' }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @if($log->checked_items)
                                        Dicentang: {{ count(array_filter($log->checked_items)) }} / {{ count($log->checked_items) }} barang
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button type="button" wire:click.prevent="showDetails({{ $log->id }})" class="text-gray-500 hover:text-corporate-blue dark:text-gray-400 dark:hover:text-blue-400 transition-colors" title="Lihat Detail Scan">
                                        <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">Anda belum melakukan scan apapun.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    @if($selectedLogId && $selectedLogDetails)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetails" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mt-3 text-left w-full">
                            <h3 class="text-xl leading-6 font-bold text-gray-900 dark:text-gray-100 mb-4" id="modal-title">
                                Detail Riwayat Scan ({{ $selectedLogDetails->created_at->format('d M Y H:i') }})
                            </h3>
                            
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Tindakan</p>
                                <p class="font-bold text-lg 
                                    @if(str_contains($selectedLogDetails->action, 'APPROVE')) text-green-600 dark:text-green-400 
                                    @elseif(str_contains($selectedLogDetails->action, 'REJECT')) text-red-600 dark:text-red-400 
                                    @else text-gray-800 dark:text-gray-200 @endif">
                                    {{ str_replace('_', ' ', $selectedLogDetails->action) }}
                                </p>
                                @if($selectedLogDetails->notes)
                                    <p class="mt-2 text-sm italic text-gray-700 dark:text-gray-300">"{{ $selectedLogDetails->notes }}"</p>
                                @endif
                            </div>

                            @if($selectedLogDetails->gateRequest)
                                <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                                    <div>
                                        <span class="block text-gray-500">Perusahaan</span>
                                        <span class="font-semibold">{{ $selectedLogDetails->gateRequest->company_name }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500 mb-1">Tipe</span>
                                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded border {{ $selectedLogDetails->gateRequest->type === 'INBOUND' ? 'border-indigo-150 bg-indigo-50/30 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300' : 'border-orange-100 bg-orange-50 text-orange-700 dark:border-orange-900/30 dark:bg-orange-950/20 dark:text-orange-400' }}">
                                            {{ $selectedLogDetails->gateRequest->type === 'INBOUND' ? 'MASUK' : 'KELUAR' }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500">No. Kendaraan</span>
                                        <span class="font-semibold">{{ $selectedLogDetails->gateRequest->vehicle_number }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500">Nama PIC (Supir)</span>
                                        <span class="font-semibold">{{ $selectedLogDetails->gateRequest->driver_name }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500">Pemohon (Karyawan)</span>
                                        <span class="font-semibold">
                                            {{ optional($selectedLogDetails->gateRequest->user)->name ?? '-' }}
                                            @if(optional($selectedLogDetails->gateRequest->user)->department)
                                                <span class="text-xs text-gray-400 font-normal">({{ $selectedLogDetails->gateRequest->user->department }})</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endif
                            
                            @if($selectedLogDetails->checked_items)
                                <div class="mb-4">
                                    <span class="block text-gray-500 font-bold mb-2">Hasil Ceklis Fisik (Saat Scan)</span>
                                    <ul class="list-disc pl-5 text-sm">
                                        @foreach($selectedLogDetails->checked_items as $index => $isChecked)
                                            <li>
                                                @if(isset($selectedLogDetails->gateRequest->items[$index]))
                                                    @php $item = $selectedLogDetails->gateRequest->items[$index]; @endphp
                                                    @if(is_array($item))
                                                        <span class="font-medium">{{ $item['name'] ?? '-' }}</span>
                                                        @if(!empty($item['qty']))
                                                            <span class="text-gray-500 dark:text-gray-400">({{ $item['qty'] }} {{ $item['unit'] ?? '' }})</span>
                                                        @endif
                                                    @else
                                                        <span>{{ $item }}</span>
                                                    @endif
                                                    : {!! $isChecked ? '<span class="text-green-600 font-bold">✓ Sesuai</span>' : '<span class="text-red-600 font-bold">✗ Tidak Sesuai / Tidak Ada</span>' !!}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="mb-2">
                                <span class="block text-gray-500 font-bold mb-2">Foto Security Saat Pemeriksaan</span>
                                @if($selectedLogDetails->security_photo_path)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden flex flex-col w-full sm:w-1/2">
                                        <div class="bg-gray-50 dark:bg-gray-800 flex-1 relative flex items-center justify-center min-h-[15rem]">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($selectedLogDetails->security_photo_path) }}" class="absolute inset-0 w-full h-full object-cover" alt="Foto Security" />
                                        </div>
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($selectedLogDetails->security_photo_path) }}" target="_blank" class="block w-full text-center py-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-xs font-semibold">Buka Penuh</a>
                                    </div>
                                @else
                                    <p class="text-sm italic text-gray-500">Tidak ada foto terlampir pada log ini.</p>
                                @endif
                            </div>

                            @if($selectedLogDetails->gateRequest)
                                <hr class="my-6 border-gray-200 dark:border-gray-700">
                                
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 text-xs text-gray-600 dark:text-gray-400">
                                    <h4 class="font-bold mb-2 uppercase tracking-wide text-gray-500">Informasi Sistem (Audit Trail)</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 font-mono">
                                        <p><span class="font-semibold">ID Pengajuan:</span> #{{ $selectedLogDetails->gateRequest->id }}</p>
                                        <p><span class="font-semibold">Diajukan Oleh:</span> {{ optional($selectedLogDetails->gateRequest->user)->name ?? 'System' }}</p>
                                        <p><span class="font-semibold">Waktu Dibuat:</span> {{ $selectedLogDetails->gateRequest->created_at->format('d M Y H:i:s') }}</p>
                                        <p><span class="font-semibold">Waktu Diproses (Log Ini):</span> {{ $selectedLogDetails->created_at->format('d M Y H:i:s') }}</p>
                                        <p class="sm:col-span-2">
                                            <span class="font-semibold">Petugas:</span> 
                                            {{ optional($selectedLogDetails->user)->name ?? 'System' }} ({{ $selectedLogDetails->user->role ?? 'Security' }})
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="closeDetails" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-600 text-base font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
