<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\GateRequest;
use App\Models\GateLog;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $scannedBarcode = '';
    public $manualBarcode = '';
    public $inboundRequest = null;
    
    public $checkedItems = [];
    public $securityPhoto;
    public $securityPhotoBase64;
    public $notes;
    
    public function searchBarcode()
    {
        $this->validate([
            'scannedBarcode' => 'required|string'
        ]);
        
        $this->findRequest($this->scannedBarcode);
    }

    public function searchManual()
    {
        $this->validate([
            'manualBarcode' => 'required|string'
        ]);
        
        $this->findRequest($this->manualBarcode);
    }

    private function findRequest($barcode)
    {
        $this->inboundRequest = GateRequest::whereIn('status', ['APPROVED', 'APPROVED_OUTBOUND'])
                                ->where('barcode', $barcode)
                                ->first();
                                
        if(!$this->inboundRequest) {
            $this->dispatch('play-sound', type: 'error');
            $this->dispatch('toast', message: 'Data tidak ditemukan atau belum disetujui GA.', type: 'error');
            $this->resetForm();
        } else {
            // Validate if scheduled_date is today
            $today = date('Y-m-d');
            if ($this->inboundRequest->scheduled_date && $this->inboundRequest->scheduled_date !== $today) {
                $this->dispatch('play-sound', type: 'error');
                $this->dispatch('toast', message: 'Barcode KEDALUWARSA. Hanya berlaku pada tanggal rencana: ' . \Carbon\Carbon::parse($this->inboundRequest->scheduled_date)->format('d M Y'), type: 'error');
                $this->resetForm();
                return;
            }

            if(is_array($this->inboundRequest->items)) {
                foreach($this->inboundRequest->items as $index => $item) {
                    $this->checkedItems[$index] = false;
                }
            }
            $this->dispatch('play-sound', type: 'success');
        }
    }

    public function processCheckIn($status)
    {
        $this->validate([
            'securityPhoto' => 'required_without:securityPhotoBase64|image|max:5120|nullable',
            'securityPhotoBase64' => 'required_without:securityPhoto|string|nullable',
            'notes' => 'required|string|min:5',
        ], [
            'securityPhoto.required_without' => 'Wajib mengambil foto kendaraan/barang via file atau kamera.',
            'securityPhotoBase64.required_without' => 'Wajib mengambil foto kendaraan/barang via kamera atau file.',
            'notes.required' => 'Catatan pemeriksaan wajib diisi.',
            'notes.min' => 'Catatan pemeriksaan minimal 5 karakter.'
        ]);

        $photoPath = '';
        if ($this->securityPhotoBase64) {
            $image = str_replace('data:image/jpeg;base64,', '', $this->securityPhotoBase64);
            $image = str_replace(' ', '+', $image);
            $imageName = 'security_photos/'.\Illuminate\Support\Str::random(40).'.jpg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($imageName, base64_decode($image));
            $photoPath = $imageName;
        } else {
            $photoPath = $this->securityPhoto->store('security_photos', 'public');
        }

        $actionName = '';
        if($status === 'IN_LOCATION' || $status === 'CHECKED_OUT') {
            $actionName = $this->inboundRequest->type === 'OUTBOUND' ? 'CHECK_OUT_APPROVE' : 'CHECK_IN_APPROVE';
        } else {
            $actionName = $this->inboundRequest->type === 'OUTBOUND' ? 'CHECK_OUT_REJECT' : 'CHECK_IN_REJECT';
        }

        GateLog::create([
            'gate_request_id' => $this->inboundRequest->id,
            'user_id' => auth()->id(),
            'action' => $actionName,
            'scanned_barcode' => $this->scannedBarcode ?: $this->manualBarcode,
            'notes' => $this->notes,
            'security_photo_path' => $photoPath,
            'checked_items' => $this->checkedItems
        ]);

        $finalStatus = $status;
        if($status === 'IN_LOCATION' && $this->inboundRequest->type === 'OUTBOUND') {
            $finalStatus = 'CHECKED_OUT';
        }

        $this->inboundRequest->update([
            'status' => $finalStatus
        ]);

        $messageText = 'Kendaraan diizinkan ' . ($this->inboundRequest->type === 'OUTBOUND' ? 'keluar.' : 'masuk.');
        if($status === 'REJECTED') $messageText = 'Kendaraan ditolak.';

        $this->dispatch('play-sound', type: $status === 'REJECTED' ? 'error' : 'success');
        $this->dispatch('toast', message: $messageText, type: $status === 'REJECTED' ? 'warning' : 'success');
        
        $this->resetForm();
        $this->scannedBarcode = '';
        $this->manualBarcode = '';
    }

    public function resetForm()
    {
        $this->inboundRequest = null;
        $this->checkedItems = [];
        $this->securityPhoto = null;
        $this->securityPhotoBase64 = null;
        $this->notes = null;
    }

    public function with(): array
    {
        return [
            'expectedToday' => GateRequest::whereIn('status', ['APPROVED', 'APPROVED_OUTBOUND'])
                ->where(function($q) {
                    $q->whereNull('scheduled_date')
                      ->orWhereDate('scheduled_date', date('Y-m-d'));
                })
                ->orderBy('updated_at', 'desc')
                ->get()
        ];
    }
};
?>

<div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg mb-6 overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="p-6 text-center bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100 flex items-center justify-center">
                <svg class="w-8 h-8 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                Scanner Gerbang
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-2">Pindai QR Code pengunjung atau masukkan ID secara manual.</p>
        </div>
        
        <div class="p-6">

            @if(!$inboundRequest)
                <div class="flex flex-col items-center justify-center py-8">
                    <div wire:ignore id="reader" class="w-full max-w-sm mb-6 rounded-lg overflow-hidden shadow-lg border-2 border-indigo-500 bg-gray-900 min-h-[250px] flex flex-col items-center justify-center relative">
                        <div class="absolute z-0 flex flex-col items-center text-indigo-400">
                            <svg class="w-10 h-10 mb-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span class="text-xs font-bold">Memulai Kamera...</span>
                        </div>
                    </div>
                    <div class="w-full max-w-sm flex space-x-2">
                        <x-text-input wire:model="manualBarcode" type="text" class="block w-full text-center text-lg uppercase font-mono tracking-widest" placeholder="Masukan kode unik" />
                        <button wire:click="searchManual" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-200">
                            Cari
                        </button>
                    </div>
                </div>

                @script
                <script>
                    let isScannerActive = false;
                    let html5QrCode = null;

                    const loadAndInitScanner = () => {
                        if (typeof Html5Qrcode === 'undefined') {
                            let script = document.createElement('script');
                            script.src = "https://unpkg.com/html5-qrcode";
                            script.onload = () => {
                                startScanner();
                            };
                            document.head.appendChild(script);
                        } else {
                            startScanner();
                        }
                    };

                    const startScanner = () => {
                        if (isScannerActive) return;
                        
                        html5QrCode = new Html5Qrcode("reader");
                        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

                        html5QrCode.start(
                            { facingMode: "environment" }, 
                            config,
                            (decodedText, decodedResult) => {
                                // On Success
                                $wire.set('manualBarcode', decodedText);
                                $wire.searchManual();
                                
                                html5QrCode.stop().then(() => {
                                    html5QrCode.clear();
                                    isScannerActive = false;
                                }).catch(err => console.log("Failed to stop scanner", err));
                            },
                            (errorMessage) => {
                                // parse error, ignore
                            }
                        ).then(() => {
                            isScannerActive = true;
                        }).catch((err) => {
                            console.error("Gagal memulai kamera", err);
                        });

                        Livewire.on('play-sound', (event) => {
                            let type = event[0]?.type || event.type;
                            const ctx = new (window.AudioContext || window.webkitAudioContext)();
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            if (type === 'success') {
                                osc.type = 'sine';
                                osc.frequency.setValueAtTime(800, ctx.currentTime);
                                osc.frequency.exponentialRampToValueAtTime(1200, ctx.currentTime + 0.1);
                                gain.gain.setValueAtTime(1, ctx.currentTime);
                                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                                osc.start();
                                osc.stop(ctx.currentTime + 0.2);
                            } else {
                                osc.type = 'sawtooth';
                                osc.frequency.setValueAtTime(200, ctx.currentTime);
                                gain.gain.setValueAtTime(1, ctx.currentTime);
                                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
                                osc.start();
                                osc.stop(ctx.currentTime + 0.5);
                            }
                        });
                    };
                    
                    loadAndInitScanner();
                </script>
                @endscript
                
                <!-- Expected Today -->
                <div class="mt-12 border-t border-gray-200 dark:border-gray-700 pt-8 w-full text-left">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Diharapkan Hadir (Antrean Gerbang)
                    </h3>
                    
                    @if(count($expectedToday) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($expectedToday as $expected)
                                <div wire:click="$set('manualBarcode', '{{ $expected->barcode }}'); searchManual()" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 rounded-lg shadow-sm hover:shadow-md cursor-pointer transition">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="px-2 py-0.5 inline-flex text-xs font-medium rounded border {{ $expected->type === 'INBOUND' ? 'border-indigo-150 bg-indigo-50/30 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300' : 'border-gray-200 bg-gray-100 text-gray-700 dark:border-gray-650 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $expected->type }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ $expected->updated_at->diffForHumans() }}</span>
                                    </div>
                                    <h4 class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ $expected->vehicle_number }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">{{ $expected->company_name }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Supir: {{ $expected->driver_name }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 italic">Tidak ada kendaraan yang sedang diantrekan.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if($inboundRequest)
    <div class="bg-white dark:bg-gray-800 shadow-2xl sm:rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700 transition-all duration-300">
        <!-- Header Strip -->
        <div class="bg-gray-900 dark:bg-gray-950 px-6 py-4 flex justify-between items-center border-b border-gray-800">
            <div class="flex items-center gap-3">
                <div class="bg-indigo-600 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white tracking-wide">Validasi Akses Berhasil</h3>
                    <p class="text-indigo-400 text-xs mt-0.5">ID: #{{ str_pad($inboundRequest->id, 5, '0', STR_PAD_LEFT) }} &bull; {{ $inboundRequest->company_name }}</p>
                </div>
            </div>
            <span class="px-3 py-1 text-xs font-bold rounded-full uppercase tracking-widest shadow-inner border {{ $inboundRequest->type === 'INBOUND' ? 'bg-indigo-950 text-indigo-400 border-indigo-900/50' : 'bg-orange-950 text-orange-400 border-orange-900/50' }}">
                {{ $inboundRequest->type === 'INBOUND' ? 'MASUK' : 'KELUAR' }}
            </span>
        </div>

        <div class="p-6 md:p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Data Kiri -->
                <div class="space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700 flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/40 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nomor Kendaraan</p>
                            <p class="font-bold text-xl text-gray-900 dark:text-gray-100">{{ $inboundRequest->vehicle_number }}</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700 flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/40 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nama PIC / Supir</p>
                            <p class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ $inboundRequest->driver_name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $inboundRequest->phone_number }}</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700 flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/40 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tipe Gudang / Tujuan</p>
                            <p class="font-bold text-md text-indigo-600 dark:text-indigo-400">
                                @if($inboundRequest->warehouse_type === 'RAW_MATERIAL') Gudang Bahan Baku
                                @elseif($inboundRequest->warehouse_type === 'FINISHED_GOODS') Gudang Barang Jadi
                                @elseif($inboundRequest->warehouse_type === 'PACKAGING') Gudang Bahan Pengemas
                                @elseif($inboundRequest->warehouse_type === 'GENERAL') Gudang Umum
                                @elseif($inboundRequest->warehouse_type === 'OTHER') Pengiriman Lain / Lainnya
                                @else Bukan Ke Gudang
                                @endif
                            </p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700 flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/40 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nomor PO / DO</p>
                            <p class="font-bold text-lg text-gray-900 dark:text-gray-100 font-mono">
                                {{ $inboundRequest->po_number ?? '-' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700 flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pemohon (Karyawan Internal)</p>
                            <p class="font-bold text-base text-gray-900 dark:text-gray-100">
                                {{ optional($inboundRequest->user)->name ?? 'Sistem' }}
                            </p>
                            @if(optional($inboundRequest->user)->department)
                                <p class="text-xs text-gray-500 mt-0.5 font-medium">{{ $inboundRequest->user->department }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Data Kanan -->
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-5 border border-gray-100 dark:border-gray-700 flex flex-col justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Tujuan / Keperluan
                        </p>
                        <p class="font-medium text-gray-800 dark:text-gray-200 text-sm leading-relaxed">{{ $inboundRequest->purpose }}</p>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Ceklis Fisik Barang</p>
                        @if(is_array($inboundRequest->items) && count($inboundRequest->items) > 0)
                            <div class="space-y-2 max-h-32 overflow-y-auto pr-2 custom-scrollbar">
                            @foreach($inboundRequest->items as $index => $item)
                                <label class="flex items-start space-x-3 cursor-pointer group">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input type="checkbox" wire:model="checkedItems.{{ $index }}" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition-colors">
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        @if(is_array($item))
                                            <span class="font-medium">{{ $item['name'] ?? '-' }}</span>
                                            @if(!empty($item['qty']))
                                                <span class="text-gray-500 dark:text-gray-400">({{ $item['qty'] }} {{ $item['unit'] ?? '' }})</span>
                                            @endif
                                        @else
                                            <span>{{ $item }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-500 italic bg-gray-100 dark:bg-gray-800 p-2 rounded">Tidak ada rincian barang spesifik.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Galeri Foto Referensi -->
            <div class="mb-8">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Referensi Dokumen Awal
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach([
                        'manifest_document_path' => 'Surat Jalan / Manifest',
                        'vehicle_photo_path' => 'Foto Kendaraan Awal',
                        'item_photo_path' => 'Foto Barang Awal'
                    ] as $field => $label)
                        @if($inboundRequest->$field)
                            <div class="group relative bg-gray-100 dark:bg-gray-900 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity z-10 flex items-center justify-center backdrop-blur-sm">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($inboundRequest->$field) }}" target="_blank" class="bg-white/90 text-indigo-900 text-[10px] font-bold px-3 py-1.5 rounded-full shadow-lg transform translate-y-2 group-hover:translate-y-0 transition-transform uppercase tracking-wider">Perbesar</a>
                                </div>
                                <div class="h-8 bg-gray-200/50 dark:bg-gray-800/50 flex items-center px-3 border-b border-gray-200 dark:border-gray-700">
                                    <span class="text-[10px] font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider truncate">{{ $label }}</span>
                                </div>
                                <div class="h-28 relative flex items-center justify-center p-1">
                                    @if(\Illuminate\Support\Str::endsWith(strtolower($inboundRequest->$field), ['.pdf']))
                                        <svg class="w-8 h-8 text-red-400 opacity-50" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                                    @else
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($inboundRequest->$field) }}" class="object-cover w-full h-full rounded-md" alt="{{ $label }}" />
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Form Eksekusi -->
            <div class="bg-indigo-50/50 dark:bg-indigo-900/20 rounded-2xl p-5 border border-indigo-100 dark:border-indigo-800/50">
                <h4 class="text-sm font-bold text-indigo-900 dark:text-indigo-300 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Tindakan Security
                </h4>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div x-data="{
                                mode: 'camera', 
                                stream: null,
                                takenPhoto: null,
                                cameraError: null,
                                
                                startCamera() {
                                    this.mode = 'camera';
                                    this.takenPhoto = null;
                                    this.cameraError = null;
                                    $wire.set('securityPhotoBase64', null);
                                    
                                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                                        this.cameraError = 'Kamera tidak didukung di browser Anda. Pastikan menggunakan protokol HTTPS atau localhost.';
                                        return;
                                    }
                                    
                                    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                                        .then(s => {
                                            this.stream = s;
                                            if ($refs.videoElement) {
                                                $refs.videoElement.srcObject = s;
                                            }
                                        })
                                        .catch(err => {
                                            console.error('Kamera gagal diakses:', err);
                                            if (err.name === 'NotAllowedError') {
                                                this.cameraError = 'Akses kamera ditolak. Berikan izin akses kamera pada browser Anda.';
                                            } else if (err.name === 'NotFoundError') {
                                                this.cameraError = 'Perangkat kamera tidak ditemukan.';
                                            } else {
                                                this.cameraError = 'Gagal mengakses kamera: ' + err.message;
                                            }
                                        });
                                },
                                
                                takePhoto() {
                                    if(this.stream && $refs.videoElement) {
                                        let canvas = document.createElement('canvas');
                                        canvas.width = $refs.videoElement.videoWidth || 640;
                                        canvas.height = $refs.videoElement.videoHeight || 480;
                                        let ctx = canvas.getContext('2d');
                                        ctx.drawImage($refs.videoElement, 0, 0, canvas.width, canvas.height);
                                        this.takenPhoto = canvas.toDataURL('image/jpeg', 0.8);
                                        $wire.set('securityPhotoBase64', this.takenPhoto);
                                        this.stopCamera();
                                    }
                                },
                                
                                stopCamera() {
                                    if(this.stream) {
                                        this.stream.getTracks().forEach(track => track.stop());
                                        this.stream = null;
                                    }
                                },
                                
                                init() {
                                    this.startCamera();
                                    this.$watch('mode', (value) => {
                                        if (value !== 'camera') this.stopCamera();
                                        else this.startCamera();
                                    });
                                }
                            }" 
                            @destroyed="stopCamera()">
                            
                            <div class="flex justify-between items-center mb-2">
                                <x-input-label :value="__('Ambil Foto Bukti Aktual (Wajib)')" class="text-xs font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-400" />
                                <div class="flex bg-gray-100 dark:bg-gray-800 p-0.5 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <button type="button" @click="mode = 'camera'" :class="mode === 'camera' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="text-[10px] px-3 py-1.5 rounded-md font-bold transition flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        Buka Kamera
                                    </button>
                                    <button type="button" @click="mode = 'file'" :class="mode === 'file' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-500 hover:text-gray-700'" class="text-[10px] px-3 py-1.5 rounded-md font-bold transition flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        Unggah File
                                    </button>
                                </div>
                            </div>

                            <!-- Camera View -->
                            <div x-show="mode === 'camera' && !takenPhoto" class="relative w-full bg-black rounded-xl overflow-hidden aspect-video flex items-center justify-center border-2 border-indigo-200 dark:border-indigo-800">
                                <template x-if="cameraError">
                                    <div class="p-4 text-center text-white z-10 flex flex-col items-center">
                                        <svg class="w-10 h-10 text-red-500 mb-2 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <p class="text-xs font-semibold mb-2" x-text="cameraError"></p>
                                        <button type="button" @click="mode = 'file'" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] px-3 py-1.5 rounded-lg font-bold transition">Gunakan Unggah File</button>
                                    </div>
                                </template>
                                <video x-show="!cameraError" x-ref="videoElement" autoplay playsinline class="absolute inset-0 w-full h-full object-cover"></video>
                                <button x-show="!cameraError" type="button" @click="takePhoto()" class="absolute bottom-4 z-10 bg-white text-indigo-600 rounded-full p-4 shadow-xl hover:bg-gray-100 transition transform hover:scale-105 border-4 border-indigo-200" title="Jepret Foto">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                </button>
                            </div>

                            <!-- Taken Photo Preview -->
                            <div x-show="takenPhoto" class="relative w-full bg-gray-900 rounded-xl overflow-hidden aspect-video border-2 border-green-500 shadow-lg" style="display: none;">
                                <img :src="takenPhoto" class="absolute inset-0 w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                                <div class="absolute bottom-0 left-0 right-0 p-4 flex justify-between items-center">
                                    <span class="text-xs text-white font-bold flex items-center gap-1"><svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Foto Tersimpan</span>
                                    <button type="button" @click="startCamera()" class="bg-white/20 backdrop-blur-sm text-white rounded-full px-3 py-1.5 text-xs font-bold hover:bg-white/30 transition flex items-center gap-1 border border-white/30">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Ulangi
                                    </button>
                                </div>
                            </div>

                            <!-- File Upload View -->
                            <div x-show="mode === 'file'" class="relative group cursor-pointer" style="display: none;">
                                <input wire:model="securityPhoto" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
                                <div class="w-full bg-white dark:bg-gray-800 border-2 border-dashed border-indigo-300 dark:border-indigo-700/50 rounded-xl p-4 flex flex-col items-center justify-center text-center group-hover:border-indigo-500 group-hover:bg-indigo-50 dark:group-hover:bg-indigo-900/30 transition-colors h-32">
                                    <svg class="w-8 h-8 text-indigo-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span class="text-xs text-indigo-600 dark:text-indigo-400 font-medium group-hover:text-indigo-700">Klik untuk Memilih File atau Galeri</span>
                                </div>
                                <div wire:loading wire:target="securityPhoto" class="text-xs text-indigo-600 font-medium mt-2 animate-pulse text-center">Memproses unggahan foto...</div>
                                @if($securityPhoto && !$securityPhotoBase64)
                                    <div class="text-xs text-green-600 dark:text-green-400 font-medium mt-2 flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> File siap diproses
                                    </div>
                                @endif
                            </div>

                            <x-input-error :messages="$errors->get('securityPhoto')" class="mt-2 text-xs text-center" />
                            <x-input-error :messages="$errors->get('securityPhotoBase64')" class="mt-2 text-xs text-center" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="notes" :value="__('Catatan Pemeriksaan (Wajib)')" class="text-xs font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-400 mb-1" />
                        <textarea wire:model="notes" class="border-indigo-200 dark:border-indigo-800/50 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm block w-full resize-none h-24 text-sm" placeholder="Misal: Kondisi barang aman, segel utuh..."></textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-2 text-xs" />
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mt-6">
                    <button wire:click="processCheckIn('IN_LOCATION')" wire:loading.attr="disabled" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        IZINKAN {{ $inboundRequest->type === 'OUTBOUND' ? 'KELUAR' : 'MASUK' }}
                    </button>
                    <button wire:click="processCheckIn('REJECTED')" wire:loading.attr="disabled" class="sm:w-1/3 bg-white dark:bg-gray-800 border-2 border-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 text-red-600 font-bold py-3.5 px-6 rounded-xl shadow-sm hover:shadow transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        TOLAK
                    </button>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <button wire:click="resetForm" class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:hover:text-gray-300 transition-colors inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Batal & Kembali ke Scanner
                </button>
            </div>
        </div>
    </div>
    @endif
</div>