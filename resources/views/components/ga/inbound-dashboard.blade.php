<?php
 
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GateRequest;
use App\Models\GateLog;
use App\Services\GeminiAiService;
 
new class extends Component
{
    use WithPagination;
 
    public $selectedRequestId = null;
    public $selectedRequestDetails = null;
    
    public $search = '';
    public $typeFilter = '';
 
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingTypeFilter()
    {
        $this->resetPage();
    }
    
    public $showRejectModal = false;
    public $rejectNotes = '';
    public $requestToReject = null;

    public $showApproveModal = false;
    public $approveNotes = '';
    public $requestToApprove = null;
 
    public function showDetails($requestId)
    {
        $this->selectedRequestId = $requestId;
        $this->selectedRequestDetails = GateRequest::findOrFail($requestId);
    }
 
    public function closeDetails()
    {
        $this->selectedRequestId = null;
        $this->selectedRequestDetails = null;
    }
 
    public function showApproveForm($id)
    {
        $this->requestToApprove = $id;
        $this->approveNotes = '';
        $this->showApproveModal = true;
    }

    public function closeApproveForm()
    {
        $this->showApproveModal = false;
        $this->requestToApprove = null;
        $this->approveNotes = '';
    }

    public function approveRequest()
    {
        $this->validate([
            'approveNotes' => 'required|string|min:5',
        ], [
            'approveNotes.required' => 'Catatan persetujuan wajib diisi.',
            'approveNotes.min' => 'Catatan persetujuan minimal 5 karakter.'
        ]);

        $request = GateRequest::findOrFail($this->requestToApprove);
        
        // Generate an unguessable barcode using UUID/random string
        $randomHash = strtoupper(\Illuminate\Support\Str::random(10));
        $barcode = 'INV-' . $request->id . '-' . $randomHash;
        
        // Fix for Data truncated issue: use 'APPROVED' for inbound, 'APPROVED_OUTBOUND' for outbound
        $newStatus = $request->type === 'OUTBOUND' ? 'APPROVED_OUTBOUND' : 'APPROVED';

        $request->update([
            'status' => $newStatus,
            'barcode' => $barcode,
            'ga_notes' => $this->approveNotes
        ]);
        
        GateLog::create([
            'gate_request_id' => $request->id,
            'user_id' => auth()->id(),
            'action' => 'VALIDATE_APPROVE',
            'notes' => $this->approveNotes,
        ]);
        
        $this->closeApproveForm();
        $this->closeDetails();
        $this->dispatch('toast', message: 'Pengajuan disetujui. Barcode diterbitkan.', type: 'success');
    }
 
    public function showRejectForm($id)
    {
        $this->requestToReject = $id;
        $this->rejectNotes = '';
        $this->showRejectModal = true;
    }
 
    public function closeRejectForm()
    {
        $this->showRejectModal = false;
        $this->requestToReject = null;
        $this->rejectNotes = '';
    }
 
    public function rejectRequest()
    {
        $this->validate([
            'rejectNotes' => 'required|string|min:5',
        ]);
 
        $request = GateRequest::findOrFail($this->requestToReject);
        $request->update([
            'status' => 'REJECTED',
            'ga_notes' => $this->rejectNotes
        ]);
        
        GateLog::create([
            'gate_request_id' => $request->id,
            'user_id' => auth()->id(),
            'action' => 'VALIDATE_REJECT',
            'notes' => $this->rejectNotes,
        ]);
        
        $this->closeRejectForm();
        $this->closeDetails();
        $this->dispatch('toast', message: 'Pengajuan ditolak.', type: 'error');
    }
 
    public function getStats()
    {
        return [
            'total' => GateRequest::count(),
            'pending' => GateRequest::whereIn('status', ['PENDING', 'VALIDATING'])->count(),
            'approved' => GateRequest::whereIn('status', ['APPROVED', 'APPROVED_OUTBOUND'])->count(),
            'in_location' => GateRequest::where('status', 'IN_LOCATION')->count(),
            'completed' => GateRequest::whereIn('status', ['COMPLETED', 'CHECKED_OUT'])->count(),
            'rejected' => GateRequest::where('status', 'REJECTED')->count(),
        ];
    }
 
    public function getWarehouseStats()
    {
        $types = ['RAW_MATERIAL', 'FINISHED_GOODS', 'PACKAGING', 'GENERAL', 'NON_WAREHOUSE', 'OTHER'];
        $counts = [];
        $total = GateRequest::count() ?: 1;
        
        foreach ($types as $type) {
            $count = GateRequest::where('warehouse_type', $type)->count();
            $label = '';
            if($type === 'RAW_MATERIAL') $label = 'Gudang Bahan Baku';
            elseif($type === 'FINISHED_GOODS') $label = 'Gudang Barang Jadi';
            elseif($type === 'PACKAGING') $label = 'Gudang Bahan Pengemas';
            elseif($type === 'GENERAL') $label = 'Gudang Umum (Sparepart/ATK)';
            elseif($type === 'OTHER') $label = 'Pengiriman Lain / Lainnya';
            else $label = 'Bukan Ke Gudang';
            
            $counts[] = [
                'type' => $type,
                'label' => $label,
                'count' => $count,
                'percentage' => round(($count / $total) * 100),
            ];
        }
        
        usort($counts, fn($a, $b) => $b['count'] <=> $a['count']);
        return $counts;
    }
 
    public function getTypeStats()
    {
        $inbound = GateRequest::where('type', 'INBOUND')->count();
        $outbound = GateRequest::where('type', 'OUTBOUND')->count();
        $total = ($inbound + $outbound) ?: 1;
        
        return [
            'inbound' => $inbound,
            'inbound_percent' => round(($inbound / $total) * 100),
            'outbound' => $outbound,
            'outbound_percent' => round(($outbound / $total) * 100),
        ];
    }
 
    public function with(): array
    {
        $query = GateRequest::whereIn('status', ['PENDING', 'VALIDATING']);
 
        if ($this->search) {
            $query->where(function($q) {
                $q->where('company_name', 'like', '%' . $this->search . '%')
                  ->orWhere('vehicle_number', 'like', '%' . $this->search . '%')
                  ->orWhere('driver_name', 'like', '%' . $this->search . '%');
            });
        }
 
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }
 
        return [
            'requests' => $query->orderBy('created_at', 'desc')->paginate(10),
            'stats' => $this->getStats(),
            'warehouseStats' => $this->getWarehouseStats(),
            'typeStats' => $this->getTypeStats(),
        ];
    }
};
?>
 
<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 space-y-8 animate-fade-in">
    
    <!-- Welcome section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 rounded-2xl p-6 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold tracking-wide">Analitik & Validasi Gerbang (GA)</h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Sistem Manajemen Keluar-Masuk Logistik PT Bio Farma.</p>
        </div>
        <div class="mt-4 md:mt-0 bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-900/30 px-4 py-2 rounded-xl text-xs font-semibold font-mono">
            {{ now()->format('l, d M Y') }}
        </div>
    </div>
 
    <!-- Dashboard Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Card 1: Menunggu Validasi -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 flex items-center space-x-4 transition shadow-sm hover:shadow-md relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-500"></div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 text-amber-500 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <span class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider pl-1">Menunggu Validasi</span>
                <span class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 font-mono pl-1">
                    {{ $stats['pending'] }}
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">pengajuan</span>
                </span>
            </div>
        </div>
 
        <!-- Card 2: Kendaraan di Lokasi -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 flex items-center space-x-4 transition shadow-sm hover:shadow-md relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-indigo-500"></div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 text-indigo-500 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <div>
                <span class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider pl-1">Kendaraan di Lokasi</span>
                <span class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 font-mono pl-1">
                    {{ $stats['in_location'] }}
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">aktif</span>
                </span>
            </div>
        </div>
 
        <!-- Card 3: Transaksi Selesai -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 flex items-center space-x-4 transition shadow-sm hover:shadow-md relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-slate-500"></div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 text-slate-500 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <span class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider pl-1">Transaksi Selesai</span>
                <span class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 font-mono pl-1">
                    {{ $stats['completed'] }}
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">selesai</span>
                </span>
            </div>
        </div>
 
        <!-- Card 4: Total Pengajuan -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 flex items-center space-x-4 transition shadow-sm hover:shadow-md relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-indigo-600"></div>
            <div class="p-3 bg-gray-50 dark:bg-gray-900/50 text-indigo-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            </div>
            <div>
                <span class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider pl-1">Total Pengajuan</span>
                <span class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 font-mono pl-1">
                    {{ $stats['total'] }}
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">total</span>
                </span>
            </div>
        </div>
    </div>
 
    <!-- Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Warehouse / Destination Stats -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm">
            <h3 class="text-sm font-bold text-gray-850 dark:text-gray-200 uppercase tracking-wider mb-5 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Arus Distribusi per Gudang
            </h3>
            
            <div class="space-y-4">
                @foreach($warehouseStats as $stat)
                    <div>
                        <div class="flex justify-between items-center text-xs font-bold mb-1.5 text-gray-600 dark:text-gray-400">
                            <span>{{ $stat['label'] }}</span>
                            <span class="font-mono text-gray-900 dark:text-gray-100">
                                {{ $stat['count'] }}
                                <span class="font-normal text-gray-500 text-[10px]">({{ $stat['percentage'] }}%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-750 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-indigo-600 dark:bg-indigo-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $stat['percentage'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
 
        <!-- Inbound vs Outbound Comparison -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-bold text-gray-850 dark:text-gray-200 uppercase tracking-wider mb-5 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l-4-4"></path></svg>
                    Aktivitas Keluar-Masuk Gerbang
                </h3>
 
                <!-- Bar split -->
                <div class="mb-6 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                    <div class="flex justify-between text-xs font-bold text-gray-600 dark:text-gray-400 mb-2.5">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 dark:bg-indigo-500"></span>
                            INBOUND ({{ $typeStats['inbound'] }})
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-gray-400 dark:bg-gray-600"></span>
                            OUTBOUND ({{ $typeStats['outbound'] }})
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden flex">
                        <div class="bg-indigo-600 dark:bg-indigo-500 h-4 transition-all duration-500" style="width: {{ $typeStats['inbound_percent'] }}%"></div>
                        <div class="bg-gray-400 dark:bg-gray-600 h-4 transition-all duration-500" style="width: {{ $typeStats['outbound_percent'] }}%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-gray-500 dark:text-gray-400 mt-2 font-medium">
                        <span>{{ $typeStats['inbound_percent'] }}% Masuk</span>
                        <span>{{ $typeStats['outbound_percent'] }}% Keluar</span>
                    </div>
                </div>
            </div>
 
            <!-- Status breakdown indicators -->
            <div class="border-t border-gray-150 dark:border-gray-700 pt-5 grid grid-cols-2 gap-4">
                <div class="p-4 bg-indigo-50/40 dark:bg-indigo-950/10 rounded-xl border border-indigo-100/50 dark:border-indigo-900/20 text-center">
                    <span class="block text-[10px] font-bold text-indigo-800 dark:text-indigo-400 uppercase tracking-wider mb-1">Disetujui GA</span>
                    <span class="text-2xl font-extrabold text-indigo-650 dark:text-indigo-400 font-mono">{{ $stats['approved'] }}</span>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
                    <span class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Ditolak GA</span>
                    <span class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 font-mono">{{ $stats['rejected'] }}</span>
                </div>
            </div>
        </div>
    </div>
 
    <!-- Table Section -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <h2 class="text-lg font-bold mb-4 text-gray-900 dark:text-gray-100">Daftar Pengajuan Menunggu Validasi</h2>
 
            <!-- Search & Filter -->
            <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4 mb-6">
                <div class="flex-1">
                    <x-text-input wire:model.live.debounce.300ms="search" type="text" class="block w-full" placeholder="Cari Perusahaan, No Kendaraan, atau Supir..." />
                </div>
                <div class="w-full md:w-64">
                    <select wire:model.live="typeFilter" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full">
                        <option value="">Semua Tipe</option>
                        <option value="INBOUND">INBOUND</option>
                        <option value="OUTBOUND">OUTBOUND</option>
                    </select>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Karyawan/PIC</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kendaraan & PIC</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($requests as $request)
                            <tr wire:key="request-{{ $request->id }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $request->created_at->format('d M Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-0.5 inline-flex text-xs font-medium rounded border {{ $request->type === 'INBOUND' ? 'border-indigo-150 bg-indigo-50/30 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300' : 'border-orange-100 bg-orange-50 text-orange-700 dark:border-orange-900/30 dark:bg-orange-950/20 dark:text-orange-400' }}">
                                        {{ $request->type === 'INBOUND' ? 'MASUK' : 'KELUAR' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <div>{{ optional($request->user)->name ?? 'Unknown' }}</div>
                                    @if(optional($request->user)->department)
                                        <div class="text-xs text-gray-500">{{ $request->user->department }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <div>{{ $request->vehicle_number }}</div>
                                    <div class="text-xs text-gray-500">{{ $request->driver_name }}</div>
                                </td>
                                <td class="px-4 py-4 text-sm font-medium space-x-2">
                                    <button type="button" wire:click.prevent="showDetails({{ $request->id }})" class="text-gray-500 hover:text-corporate-blue dark:text-gray-400 dark:hover:text-blue-400 transition-colors" title="Detail & Validasi">
                                        <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada antrean validasi.</td>
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
    @if($selectedRequestId && $selectedRequestDetails && !$showRejectModal && !$showApproveModal)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeDetails" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full border border-gray-200 dark:border-gray-700">
                    
                    <!-- Header -->
                    <div class="bg-gray-900 dark:bg-gray-950 px-6 py-5 flex justify-between items-center border-b border-gray-800">
                        <div>
                            <h3 class="text-xl font-bold text-white tracking-wide" id="modal-title">
                                Detail Validasi Pengajuan
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">ID Transaksi: #{{ str_pad($selectedRequestDetails->id, 5, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        <button wire:click="closeDetails" class="text-white hover:text-gray-200 bg-white/10 hover:bg-white/20 rounded-full p-2 transition">
                            <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
 
                    <div class="px-6 py-6 max-h-[70vh] overflow-y-auto bg-gray-50/50 dark:bg-gray-800/50">
                        
                        <!-- Panel Saran AI -->
                        <div class="mb-6 relative overflow-hidden rounded-xl border {{ $selectedRequestDetails->ai_validation_status === 'COMPLETED' ? ($selectedRequestDetails->ai_is_valid ? 'bg-emerald-50/50 border-emerald-200 dark:bg-emerald-900/10 dark:border-emerald-800/30' : 'bg-amber-50/50 border-amber-200 dark:bg-amber-900/10 dark:border-amber-800/30') : 'bg-indigo-50/50 border-indigo-200 dark:bg-indigo-900/10 dark:border-indigo-800/30' }}">
                            <div class="absolute top-0 left-0 w-1 h-full {{ $selectedRequestDetails->ai_validation_status === 'COMPLETED' ? ($selectedRequestDetails->ai_is_valid ? 'bg-emerald-500' : 'bg-amber-500') : 'bg-indigo-500' }}"></div>
                            <div class="p-5">
                                <div class="flex items-center justify-between mb-3">
                                    <h5 class="font-bold flex items-center gap-2 {{ $selectedRequestDetails->ai_validation_status === 'COMPLETED' ? ($selectedRequestDetails->ai_is_valid ? 'text-emerald-800 dark:text-emerald-400' : 'text-amber-800 dark:text-amber-400') : 'text-indigo-800 dark:text-indigo-400' }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                                        Hasil Validasi Asisten AI (Gemini)
                                    </h5>
                                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider
                                        {{ $selectedRequestDetails->ai_validation_status === 'COMPLETED' ? ($selectedRequestDetails->ai_is_valid ? 'bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-900/60 dark:border-emerald-800 dark:text-emerald-300' : 'bg-amber-50 border border-amber-200 text-amber-800 dark:bg-amber-900/60 dark:border-amber-800 dark:text-amber-300') : 'bg-indigo-50 border border-indigo-200 text-indigo-800 dark:bg-indigo-900/60 dark:border-indigo-800 dark:text-indigo-300' }}">
                                        {{ $selectedRequestDetails->ai_validation_status === 'COMPLETED' ? ($selectedRequestDetails->ai_is_valid ? 'Sesuai' : 'Perlu Atensi') : 'Menganalisis...' }}
                                    </span>
                                </div>
                                <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                    {!! $selectedRequestDetails->ai_validation_feedback ?: '<span class="italic text-gray-500">Asisten AI sedang memeriksa kecocokan foto dan surat jalan...</span>' !!}
                                </div>
                            </div>
                        </div>
 
                        <!-- Grid Data -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-150 dark:border-gray-700 mb-6 overflow-hidden">
                            <div class="bg-gray-50/80 dark:bg-gray-900/50 px-5 py-3 border-b border-gray-150 dark:border-gray-700">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Informasi Kendaraan & Dokumen
                                </h4>
                            </div>
                            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Perusahaan</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $selectedRequestDetails->company_name }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Pemohon (Karyawan)</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ optional($selectedRequestDetails->user)->name ?? 'Unknown' }}
                                        @if(optional($selectedRequestDetails->user)->department)
                                            <span class="text-xs text-gray-500">({{ $selectedRequestDetails->user->department }})</span>
                                        @endif
                                    </span>
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
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">
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
                        <div class="mb-6">
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
                                                    <svg class="w-10 h-10 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586a2 2 0 011 1.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                                                @else
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($selectedRequestDetails->$field) }}" class="object-cover w-full h-full rounded" alt="{{ $label }}" />
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
 
                    </div>
                    
                    <!-- Actions -->
                    <div class="bg-gray-50 dark:bg-gray-800/90 px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-3 justify-end items-center">
                        <button wire:click="closeDetails" class="px-6 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition order-3 sm:order-1">
                            Tutup Detail
                        </button>
                        <button wire:click="showRejectForm({{ $selectedRequestDetails->id }})" class="px-6 py-2.5 bg-white dark:bg-gray-800 border border-red-200 dark:border-red-900/50 hover:bg-red-50 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 font-bold rounded-lg text-sm shadow-sm transition order-2">
                            &#10006; Tolak
                        </button>
                        <button wire:click="showApproveForm({{ $selectedRequestDetails->id }})" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-sm shadow-md transition transform hover:-translate-y-0.5 order-1 sm:order-3">
                            &#10004; Setujui (Approve)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
 
    <!-- Modal Reject -->
    @if($showRejectModal)
        <div class="fixed inset-0 z-[110] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" wire:click="closeRejectForm" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-gray-200 dark:border-gray-700">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-gray-100" id="modal-title">
                                    Tolak Pengajuan & Kirim Catatan
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Catatan ini akan dilihat oleh karyawan untuk direvisi.</p>
                                    <textarea wire:model="rejectNotes" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" rows="4" placeholder="Misal: Foto barang tidak jelas, atau nomor kendaraan tidak sesuai..." required></textarea>
                                    <x-input-error :messages="$errors->get('rejectNotes')" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="rejectRequest" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Kirim Tolakan
                        </button>
                        <button type="button" wire:click="closeRejectForm" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Approve -->
    @if($showApproveModal)
        <div class="fixed inset-0 z-[110] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity" wire:click="closeApproveForm" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-gray-200 dark:border-gray-700">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-gray-100" id="modal-title">
                                    Setujui Pengajuan & Tulis Catatan (Wajib)
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Tuliskan instruksi atau catatan persetujuan untuk pengajuan ini.</p>
                                    <textarea wire:model="approveNotes" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" rows="4" placeholder="Misal: Dokumen valid, silakan masuk ke gudang bahan baku..." required></textarea>
                                    <x-input-error :messages="$errors->get('approveNotes')" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="approveRequest" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Setujui Pengajuan
                        </button>
                        <button type="button" wire:click="closeApproveForm" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
