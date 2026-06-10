<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GateRequest;

new class extends Component
{
    use WithPagination;

    public $selectedRequestId = null;
    public $selectedRequestDetails = null;

    public $startDate = null;
    public $endDate = null;

    public function showDetails($requestId)
    {
        \Illuminate\Support\Facades\Log::info("showDetails clicked for ID: " . $requestId);
        $this->selectedRequestId = $requestId;
        $this->selectedRequestDetails = GateRequest::findOrFail($requestId);
    }

    public function closeDetails()
    {
        $this->selectedRequestId = null;
        $this->selectedRequestDetails = null;
    }

    private function getFilteredQuery()
    {
        $query = GateRequest::whereNotIn('status', ['PENDING', 'VALIDATING']);
        
        if ($this->startDate) {
            $query->whereDate('updated_at', '>=', $this->startDate);
        }
        
        if ($this->endDate) {
            $query->whereDate('updated_at', '<=', $this->endDate);
        }
        
        return $query;
    }

    public function with(): array
    {
        // Show requests that have been processed by GA
        return [
            'requests' => $this->getFilteredQuery()
                            ->orderBy('updated_at', 'desc')
                            ->paginate(15),
        ];
    }

    public function exportPdf()
    {
        $requests = $this->getFilteredQuery()
                        ->orderBy('updated_at', 'desc')
                        ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.gate-requests-pdf', ['requests' => $requests]);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Laporan_GA_' . date('Y-m-d') . '.pdf');
    }

    public function exportExcel()
    {
        $requests = $this->getFilteredQuery()
                        ->orderBy('updated_at', 'desc')
                        ->get();

        $filename = 'Laporan_GA_' . date('Y-m-d') . '.csv';

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal', 'Perusahaan', 'No. Kendaraan', 'Supir', 'Status', 'Barcode', 'Tujuan']);

            foreach ($requests as $request) {
                fputcsv($file, [
                    $request->updated_at->format('d M Y H:i'),
                    $request->company_name,
                    $request->vehicle_number,
                    $request->driver_name,
                    $request->status,
                    $request->barcode,
                    $request->warehouse_type,
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
};
?>

<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Histori Validasi (GA)</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Daftar riwayat persetujuan atau penolakan pengajuan oleh GA.</p>
                </div>
                
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full lg:w-auto">
                    <!-- Date Filter Group -->
                    <div class="flex items-center bg-gray-50 dark:bg-gray-700/50 p-1 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm w-full sm:w-auto transition-all focus-within:ring-2 focus-within:ring-indigo-500/50">
                        <div class="flex items-center pl-3 pr-2 border-r border-gray-200 dark:border-gray-600">
                            <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="flex items-center flex-1 sm:flex-none">
                            <input type="date" wire:model.live="startDate" class="w-full sm:w-auto border-0 bg-transparent text-sm text-gray-700 dark:text-gray-200 focus:ring-0 p-2 cursor-pointer font-medium" title="Tanggal Mulai">
                        </div>
                        <span class="text-gray-300 dark:text-gray-500 font-bold px-1">-</span>
                        <div class="flex items-center flex-1 sm:flex-none">
                            <input type="date" wire:model.live="endDate" class="w-full sm:w-auto border-0 bg-transparent text-sm text-gray-700 dark:text-gray-200 focus:ring-0 p-2 cursor-pointer font-medium" title="Tanggal Akhir">
                        </div>
                    </div>
                    
                    <!-- Export Buttons -->
                    <div class="flex gap-2">
                        <button wire:click="exportPdf" wire:loading.attr="disabled" class="flex-1 sm:flex-none justify-center inline-flex items-center px-4 py-2.5 bg-rose-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-rose-500 focus:bg-rose-700 active:bg-rose-900 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm disabled:opacity-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            PDF
                        </button>
                        <button wire:click="exportExcel" wire:loading.attr="disabled" class="flex-1 sm:flex-none justify-center inline-flex items-center px-4 py-2.5 bg-emerald-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-emerald-500 focus:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm disabled:opacity-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Excel
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Terakhir Update</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Perusahaan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Kendaraan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Barcode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($requests as $request)
                            <tr wire:key="request-{{ $request->id }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $request->updated_at->format('d M Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $request->company_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $request->vehicle_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 inline-flex items-center gap-1.5 text-xs font-semibold rounded-md border 
                                        @if(str_starts_with($request->status, 'APPROVED')) border-emerald-100 bg-emerald-50 text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-950/20 dark:text-emerald-400
                                        @elseif($request->status === 'REJECTED') border-red-150 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300
                                        @elseif($request->status === 'PENDING') border-amber-100 bg-amber-50 text-amber-700 dark:border-amber-900/30 dark:bg-amber-950/20 dark:text-amber-400
                                        @elseif($request->status === 'IN_LOCATION') border-indigo-150 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300
                                        @else border-gray-200 bg-gray-100 text-gray-800 dark:border-gray-650 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        <span class="h-1.5 w-1.5 rounded-full 
                                            @if(str_starts_with($request->status, 'APPROVED')) bg-emerald-500
                                            @elseif($request->status === 'REJECTED') bg-red-500
                                            @elseif($request->status === 'PENDING') bg-amber-500
                                            @elseif($request->status === 'IN_LOCATION') bg-indigo-500
                                            @else bg-gray-500 @endif"></span>
                                        @if($request->status === 'PENDING')
                                            Menunggu Validasi
                                        @elseif(str_starts_with($request->status, 'APPROVED'))
                                            Disetujui
                                        @elseif($request->status === 'REJECTED')
                                            Ditolak
                                        @elseif($request->status === 'IN_LOCATION')
                                            Di Lokasi
                                        @elseif($request->status === 'COMPLETED' || $request->status === 'CHECKED_OUT')
                                            Selesai
                                        @else
                                            {{ str_replace('_', ' ', $request->status) }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-mono font-bold">
                                    {{ $request->barcode ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button type="button" wire:click.prevent="showDetails({{ $request->id }})" class="text-gray-500 hover:text-corporate-blue dark:text-gray-400 dark:hover:text-blue-400 transition-colors" title="Lihat Detail">
                                        <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">Belum ada riwayat validasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $requests->links() }}
            </div>
        </div>
    </div>

    <!-- Detail Modal (Elegant Design) -->
    @if($selectedRequestId && $selectedRequestDetails)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeDetails" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-gray-200 dark:border-gray-700">
                    
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-5 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-white tracking-wide" id="modal-title">
                                Ringkasan Pengajuan
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">ID Transaksi: #{{ str_pad($selectedRequestDetails->id, 5, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        <button wire:click="closeDetails" class="text-white hover:text-gray-200 bg-white/10 hover:bg-white/20 rounded-full p-2 transition">
                            <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="px-6 py-6 max-h-[70vh] overflow-y-auto bg-gray-50/50 dark:bg-gray-800/50">
                        
                        <!-- Status Banner -->
                        @if($selectedRequestDetails->status === 'REJECTED')
                            <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 rounded-r-lg p-4 mb-6 shadow-sm">
                                <div class="flex items-start">
                                    <svg class="h-6 w-6 text-red-500 mt-0.5 mr-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                    <div>
                                        <h3 class="text-sm font-bold text-red-800 dark:text-red-300">Ditolak oleh General Affairs</h3>
                                        <p class="mt-1 text-sm text-red-700 dark:text-red-400">Catatan Revisi: <strong>{{ $selectedRequestDetails->ga_notes ?? 'Tidak ada catatan.' }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
                                <div class="flex items-center gap-3">
                                    <span class="p-2 rounded-lg border {{ $selectedRequestDetails->type === 'INBOUND' ? 'border-indigo-150 bg-indigo-50/30 text-indigo-650 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-450' : 'border-orange-100 bg-orange-50 text-orange-650 dark:border-orange-900/30 dark:bg-orange-950/20 dark:text-orange-450' }}">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    </span>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Tipe Akses</p>
                                        <p class="font-bold text-gray-900 dark:text-gray-100">{{ $selectedRequestDetails->type === 'INBOUND' ? 'MASUK' : 'KELUAR' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Status Saat Ini</p>
                                    <span class="inline-flex mt-1 px-2.5 py-1 items-center gap-1.5 text-xs font-semibold rounded-md border 
                                        @if($selectedRequestDetails->status === 'PENDING') border-gray-200 bg-gray-100 text-gray-750 dark:border-gray-650 dark:bg-gray-700 dark:text-gray-300
                                        @elseif(str_starts_with($selectedRequestDetails->status, 'APPROVED') || str_starts_with($selectedRequestDetails->status, 'CHECKED')) border-indigo-150 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300
                                        @elseif($selectedRequestDetails->status === 'REJECTED') border-red-155 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300
                                        @else border-gray-200 bg-gray-100 text-gray-800 dark:border-gray-650 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        <span class="h-1.5 w-1.5 rounded-full 
                                            @if($selectedRequestDetails->status === 'PENDING') bg-gray-400
                                            @elseif(str_starts_with($selectedRequestDetails->status, 'APPROVED') || str_starts_with($selectedRequestDetails->status, 'CHECKED')) bg-indigo-500
                                            @elseif($selectedRequestDetails->status === 'REJECTED') bg-red-500
                                            @else bg-gray-500 @endif"></span>
                                        {{ str_replace('_', ' ', $selectedRequestDetails->status) }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        <!-- Informasi Utama -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 overflow-hidden">
                            <div class="bg-gray-50/80 dark:bg-gray-900/50 px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    Detail Kendaraan & Pemohon
                                </h4>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Perusahaan</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedRequestDetails->company_name }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Nomor Kendaraan</span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $selectedRequestDetails->vehicle_number }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Nama PIC (Supir)</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedRequestDetails->driver_name }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Alamat Perusahaan</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedRequestDetails->company_address ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Tipe Gudang / Tujuan</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                        @if($selectedRequestDetails->warehouse_type === 'RAW_MATERIAL') Gudang Bahan Baku
                                        @elseif($selectedRequestDetails->warehouse_type === 'FINISHED_GOODS') Gudang Barang Jadi
                                        @elseif($selectedRequestDetails->warehouse_type === 'PACKAGING') Gudang Bahan Pengemas
                                        @elseif($selectedRequestDetails->warehouse_type === 'GENERAL') Gudang Umum
                                        @elseif($selectedRequestDetails->warehouse_type === 'OTHER') Pengiriman Lain / Lainnya
                                        @else Bukan Ke Gudang
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Nomor PO / DO</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100 font-mono">{{ $selectedRequestDetails->po_number ?? '-' }}</span>
                                </div>
                                <div class="md:col-span-2 pt-2 mt-2 border-t border-gray-100 dark:border-gray-700">
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Keperluan</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedRequestDetails->purpose }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Daftar Barang -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 overflow-hidden">
                            <div class="bg-gray-50/80 dark:bg-gray-900/50 px-5 py-3 border-b border-gray-100 dark:border-gray-700">
                                <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                    Daftar Barang Bawaan
                                </h4>
                            </div>
                            <div class="p-5">
                                @if(is_array($selectedRequestDetails->items) && count($selectedRequestDetails->items) > 0)
                                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach($selectedRequestDetails->items as $item)
                                            <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                @if(is_array($item))
                                                    <span class="font-medium">{{ $item['name'] ?? '-' }}</span>
                                                    @if(!empty($item['qty']))
                                                        <span class="text-gray-500 dark:text-gray-400">({{ $item['qty'] }} {{ $item['unit'] ?? '' }})</span>
                                                    @endif
                                                @else
                                                    <span>{{ $item }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-gray-500 italic">Tidak ada rincian barang.</p>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Lampiran -->
                        <div class="mb-8">
                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2 uppercase tracking-wider">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Dokumen & Bukti Fisik
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                @foreach(['manifest_document_path' => 'Surat Jalan', 'vehicle_photo_path' => 'Foto Kendaraan', 'item_photo_path' => 'Foto Barang'] as $field => $label)
                                    @if($selectedRequestDetails->$field)
                                        <div class="group relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-md transition">
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity z-10 flex items-center justify-center backdrop-blur-[2px]">
                                                <a href="{{ \Illuminate\Support\Facades\Storage::url($selectedRequestDetails->$field) }}" target="_blank" class="bg-white text-gray-900 text-xs font-bold px-3 py-1.5 rounded-full shadow-lg transform translate-y-2 group-hover:translate-y-0 transition-transform">Lihat Penuh</a>
                                            </div>
                                            <div class="h-8 bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center border-b border-gray-200 dark:border-gray-700">
                                                <span class="text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-widest">{{ $label }}</span>
                                            </div>
                                            <div class="h-32 bg-gray-50 dark:bg-gray-900 p-2 relative flex items-center justify-center">
                                                @if(\Illuminate\Support\Str::endsWith(strtolower($selectedRequestDetails->$field), ['.pdf']))
                                                    <svg class="w-10 h-10 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                                                @else
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($selectedRequestDetails->$field) }}" class="object-cover w-full h-full rounded" alt="{{ $label }}" />
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <!-- Audit Trail -->
                        <div class="bg-indigo-50/50 dark:bg-indigo-900/10 rounded-xl p-4 border border-indigo-100 dark:border-indigo-900/30">
                            <h4 class="text-xs font-bold text-indigo-800 dark:text-indigo-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                Jejak Sistem (Audit Trail)
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-indigo-900/70 dark:text-indigo-300/70 font-mono">
                                <div><span class="font-bold opacity-80 block mb-0.5">Dibuat Pada:</span> {{ $selectedRequestDetails->created_at->format('d M Y H:i:s') }}</div>
                                <div><span class="font-bold opacity-80 block mb-0.5">Pemohon:</span> {{ optional($selectedRequestDetails->user)->name ?? 'Sistem' }} @if(optional($selectedRequestDetails->user)->department) <span class="text-xs text-gray-500 font-normal">({{ $selectedRequestDetails->user->department }})</span> @endif</div>
                                
                                @php $lastLog = $selectedRequestDetails->gateLogs()->latest()->first(); @endphp
                                @if($lastLog)
                                    <div class="sm:col-span-2 mt-2 pt-2 border-t border-indigo-200 dark:border-indigo-800/50">
                                        <span class="font-bold opacity-80 block mb-0.5">Update Terakhir:</span> 
                                        Oleh {{ optional($lastLog->user)->name }} &rarr; <span class="bg-indigo-200 dark:bg-indigo-800 text-indigo-900 dark:text-indigo-200 px-1.5 py-0.5 rounded text-[10px]">{{ str_replace('_', ' ', $lastLog->action) }}</span> pada <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $lastLog->created_at->format('d M Y H:i:s') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-800/90 px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                        <button type="button" wire:click="closeDetails" class="px-6 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                            Tutup Detail
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

