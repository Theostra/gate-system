<?php

use Livewire\Component;
use App\Models\GateRequest;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $showQrModal = false;
    public $showDetailModal = false;
    public $activeBarcode = '';
    public $activeVehicle = '';
    public $selectedRequest = null;
    public $qrCodeSvg = '';

    public function showQr($barcode, $vehicle)
    {
        $this->activeBarcode = $barcode;
        $this->activeVehicle = $vehicle;
        $this->qrCodeSvg = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($barcode);
        $this->showQrModal = true;
    }

    public function closeQr()
    {
        $this->showQrModal = false;
        $this->activeBarcode = '';
        $this->qrCodeSvg = '';
    }

    public function showDetail($requestId)
    {
        $this->selectedRequest = GateRequest::findOrFail($requestId);
        $this->showDetailModal = true;
    }

    public function closeDetail()
    {
        $this->showDetailModal = false;
        $this->selectedRequest = null;
    }

    public function with(): array
    {
        $userId = auth()->id();
        
        $stats = [
            'total' => GateRequest::where('user_id', $userId)->count(),
            'approved' => GateRequest::where('user_id', $userId)->whereIn('status', ['APPROVED', 'APPROVED_OUTBOUND'])->count(),
            'pending' => GateRequest::where('user_id', $userId)->whereIn('status', ['PENDING', 'VALIDATING'])->count(),
            'rejected' => GateRequest::where('user_id', $userId)->where('status', 'REJECTED')->count(),
        ];

        return [
            'requests' => GateRequest::where('user_id', $userId)
                            ->orderBy('created_at', 'desc')
                            ->paginate(10),
            'stats' => $stats,
        ];
    }
};
?>

<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    
    <!-- Action Buttons -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <a href="{{ route('inbound.create') }}" class="flex items-center justify-center p-6 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-500 rounded-xl shadow-sm hover:shadow-md transition duration-200 group relative overflow-hidden">
            <div class="absolute top-0 left-0 h-full w-1.5 bg-indigo-600"></div>
            <svg class="w-12 h-12 mr-4 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <div class="text-left">
                <h3 class="text-2xl font-bold">Pengajuan Masuk</h3>
                <p class="text-gray-500 dark:text-gray-400">Barang / Kendaraan Inbound</p>
            </div>
        </a>
        <a href="{{ route('outbound.create') }}" class="flex items-center justify-center p-6 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-700 hover:border-orange-500 dark:hover:border-orange-500 rounded-xl shadow-sm hover:shadow-md transition duration-200 group relative overflow-hidden">
            <div class="absolute top-0 left-0 h-full w-1.5 bg-orange-600"></div>
            <svg class="w-12 h-12 mr-4 text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <div class="text-left">
                <h3 class="text-2xl font-bold">Pengajuan Keluar</h3>
                <p class="text-gray-500 dark:text-gray-400">Barang / Kendaraan Outbound</p>
            </div>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gray-300 dark:bg-gray-600"></div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 pl-2">Total Pengajuan</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 pl-2 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500"></div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 pl-2">Menunggu Validasi</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 pl-2 mt-1">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500"></div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 pl-2">Disetujui</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 pl-2 mt-1">{{ $stats['approved'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500"></div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 pl-2">Ditolak</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 pl-2 mt-1">{{ $stats['rejected'] }}</p>
        </div>
    </div>

    <div id="history-section" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">Riwayat Pengajuan Saya</h2>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kendaraan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keperluan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($requests as $request)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $request->scheduled_date ? \Carbon\Carbon::parse($request->scheduled_date)->format('d M Y') : $request->created_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-0.5 inline-flex text-xs font-medium rounded border {{ $request->type === 'INBOUND' ? 'border-indigo-150 bg-indigo-50/30 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300' : 'border-orange-100 bg-orange-50 text-orange-700 dark:border-orange-900/30 dark:bg-orange-950/20 dark:text-orange-400' }}">
                                        {{ $request->type === 'INBOUND' ? 'MASUK' : 'KELUAR' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-semibold">{{ $request->vehicle_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ \Illuminate\Support\Str::limit($request->purpose, 30) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 inline-flex items-center gap-1.5 text-xs font-semibold rounded-md border 
                                        @if($request->status === 'PENDING') border-amber-100 bg-amber-50 text-amber-700 dark:border-amber-900/30 dark:bg-amber-950/20 dark:text-amber-400
                                        @elseif(str_starts_with($request->status, 'APPROVED')) border-emerald-100 bg-emerald-50 text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-950/20 dark:text-emerald-400
                                        @elseif($request->status === 'REJECTED') border-red-150 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300
                                        @elseif($request->status === 'IN_LOCATION') border-indigo-150 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300
                                        @else border-gray-200 bg-gray-100 text-gray-800 dark:border-gray-650 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        <span class="h-1.5 w-1.5 rounded-full 
                                            @if($request->status === 'PENDING') bg-amber-500
                                            @elseif(str_starts_with($request->status, 'APPROVED')) bg-emerald-500
                                            @elseif($request->status === 'REJECTED') bg-red-500
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button type="button" wire:click.prevent="showDetail({{ $request->id }})" class="text-gray-500 hover:text-corporate-blue dark:text-gray-400 dark:hover:text-blue-400 transition-colors" title="Lihat Detail">
                                        <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                    @if(str_starts_with($request->status, 'APPROVED') && $request->barcode)
                                        <button wire:click="showQr('{{ $request->barcode }}', '{{ $request->vehicle_number }}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-bold">
                                            Barcode
                                        </button>
                                    @endif
                                    @if($request->status === 'REJECTED')
                                        <a href="{{ $request->type === 'INBOUND' ? route('inbound.edit', $request->id) : route('outbound.edit', $request->id) }}" class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 font-bold ml-2">
                                            Revisi
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Belum ada pengajuan.</td>
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

    <!-- QR Modal -->
    @if($showQrModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeQr" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    Akses Gerbang
                                </h3>
                                <div class="mt-2 flex flex-col items-center justify-center py-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Tunjukkan kode ini kepada Security gerbang.</p>
                                    
                                    <div class="p-4 bg-white rounded shadow-inner flex justify-center">
                                        {!! $qrCodeSvg !!}
                                    </div>
                                    
                                    <p class="mt-4 font-bold text-gray-800 dark:text-gray-200">{{ $activeVehicle }}</p>
                                    <p class="text-xs text-gray-500 mt-1">ID: {{ $activeBarcode }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="closeQr" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Detail Modal (Elegant Design) -->
    @if($showDetailModal && $selectedRequest)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeDetail" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-gray-200 dark:border-gray-700">
                    
                    <!-- Header -->
                    <div class="bg-gray-900 dark:bg-gray-950 px-6 py-5 flex justify-between items-center border-b border-gray-800">
                        <div>
                            <h3 class="text-xl font-bold text-white tracking-wide" id="modal-title">
                                Ringkasan Pengajuan
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">ID Transaksi: #{{ str_pad($selectedRequest->id, 5, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        <button wire:click="closeDetail" class="text-white hover:text-gray-200 bg-white/10 hover:bg-white/20 rounded-full p-2 transition">
                            <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="px-6 py-6 max-h-[70vh] overflow-y-auto bg-gray-50/50 dark:bg-gray-800/50">
                        
                        <!-- Status Banner -->
                        @if($selectedRequest->status === 'REJECTED')
                            <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 rounded-r-lg p-4 mb-6 shadow-sm">
                                <div class="flex items-start">
                                    <svg class="h-6 w-6 text-red-500 mt-0.5 mr-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                    <div>
                                        <h3 class="text-sm font-bold text-red-800 dark:text-red-300">Ditolak oleh General Affairs</h3>
                                        <p class="mt-1 text-sm text-red-700 dark:text-red-400">Catatan Revisi: <strong>{{ $selectedRequest->ga_notes ?? 'Tidak ada catatan.' }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
                                <div class="flex items-center gap-3">
                                    <span class="p-2 rounded-lg border {{ $selectedRequest->type === 'INBOUND' ? 'border-indigo-150 bg-indigo-50/30 text-indigo-650 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-400' : 'border-orange-100 bg-orange-50 text-orange-650 dark:border-orange-900/30 dark:bg-orange-950/20 dark:text-orange-400' }}">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    </span>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Tipe Akses</p>
                                        <p class="font-bold text-gray-900 dark:text-gray-100">{{ $selectedRequest->type === 'INBOUND' ? 'MASUK' : 'KELUAR' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Status Saat Ini</p>
                                    <span class="inline-flex mt-1 px-2.5 py-1 items-center gap-1.5 text-xs font-semibold rounded-md border 
                                        @if($selectedRequest->status === 'PENDING') border-amber-100 bg-amber-50 text-amber-700 dark:border-amber-900/30 dark:bg-amber-950/20 dark:text-amber-400
                                        @elseif(str_starts_with($selectedRequest->status, 'APPROVED')) border-emerald-100 bg-emerald-50 text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-950/20 dark:text-emerald-400
                                        @elseif($selectedRequest->status === 'REJECTED') border-red-150 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300
                                        @elseif($selectedRequest->status === 'IN_LOCATION') border-indigo-150 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300
                                        @else border-gray-200 bg-gray-100 text-gray-800 dark:border-gray-650 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        <span class="h-1.5 w-1.5 rounded-full 
                                            @if($selectedRequest->status === 'PENDING') bg-amber-500
                                            @elseif(str_starts_with($selectedRequest->status, 'APPROVED')) bg-emerald-500
                                            @elseif($selectedRequest->status === 'REJECTED') bg-red-500
                                            @elseif($selectedRequest->status === 'IN_LOCATION') bg-indigo-500
                                            @else bg-gray-500 @endif"></span>
                                        @if($selectedRequest->status === 'PENDING')
                                            Menunggu Validasi
                                        @elseif(str_starts_with($selectedRequest->status, 'APPROVED'))
                                            Disetujui
                                        @elseif($selectedRequest->status === 'REJECTED')
                                            Ditolak
                                        @elseif($selectedRequest->status === 'IN_LOCATION')
                                            Di Lokasi
                                        @elseif($selectedRequest->status === 'COMPLETED' || $selectedRequest->status === 'CHECKED_OUT')
                                            Selesai
                                        @else
                                            {{ str_replace('_', ' ', $selectedRequest->status) }}
                                        @endif
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
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedRequest->company_name }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Nomor Kendaraan</span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $selectedRequest->vehicle_number }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Nama PIC (Supir)</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedRequest->driver_name }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Alamat Perusahaan</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedRequest->company_address ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Tipe Gudang / Tujuan</span>
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                        @if($selectedRequest->warehouse_type === 'RAW_MATERIAL') Gudang Bahan Baku
                                        @elseif($selectedRequest->warehouse_type === 'FINISHED_GOODS') Gudang Barang Jadi
                                        @elseif($selectedRequest->warehouse_type === 'PACKAGING') Gudang Bahan Pengemas
                                        @elseif($selectedRequest->warehouse_type === 'GENERAL') Gudang Umum
                                        @elseif($selectedRequest->warehouse_type === 'OTHER') Pengiriman Lain / Lainnya
                                        @else Bukan Ke Gudang
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Nomor PO / DO</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100 font-mono">{{ $selectedRequest->po_number ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Rencana Tanggal</span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $selectedRequest->scheduled_date ? \Carbon\Carbon::parse($selectedRequest->scheduled_date)->format('d M Y') : '-' }}</span>
                                </div>
                                <div class="md:col-span-2 pt-2 mt-2 border-t border-gray-100 dark:border-gray-700">
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Keperluan</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedRequest->purpose }}</span>
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
                                @if(is_array($selectedRequest->items) && count($selectedRequest->items) > 0)
                                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach($selectedRequest->items as $item)
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
                                    @if($selectedRequest->$field)
                                        <div class="group relative bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-md transition">
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity z-10 flex items-center justify-center backdrop-blur-[2px]">
                                                <a href="{{ \Illuminate\Support\Facades\Storage::url($selectedRequest->$field) }}" target="_blank" class="bg-white text-gray-900 text-xs font-bold px-3 py-1.5 rounded-full shadow-lg transform translate-y-2 group-hover:translate-y-0 transition-transform">Lihat Penuh</a>
                                            </div>
                                            <div class="h-8 bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center border-b border-gray-200 dark:border-gray-700">
                                                <span class="text-[11px] font-bold text-gray-600 dark:text-gray-300 uppercase tracking-widest">{{ $label }}</span>
                                            </div>
                                            <div class="h-32 bg-gray-50 dark:bg-gray-900 p-2 relative flex items-center justify-center">
                                                @if(\Illuminate\Support\Str::endsWith(strtolower($selectedRequest->$field), ['.pdf']))
                                                    <svg class="w-10 h-10 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                                                @else
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($selectedRequest->$field) }}" class="object-cover w-full h-full rounded" alt="{{ $label }}" />
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
                                <div><span class="font-bold opacity-80 block mb-0.5">Dibuat Pada:</span> {{ $selectedRequest->created_at->format('d M Y H:i:s') }}</div>
                                <div><span class="font-bold opacity-80 block mb-0.5">Pemohon:</span> {{ optional($selectedRequest->user)->name ?? 'Sistem' }} @if(optional($selectedRequest->user)->department) <span class="text-xs text-gray-500 font-normal">({{ $selectedRequest->user->department }})</span> @endif</div>
                                
                                @php $lastLog = $selectedRequest->gateLogs()->latest()->first(); @endphp
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
                        <button type="button" wire:click="closeDetail" class="px-6 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                            Tutup Detail
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
