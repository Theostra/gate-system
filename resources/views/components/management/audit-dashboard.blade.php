<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GateLog;
use App\Models\GateRequest;

new class extends Component
{
    use WithPagination;

    public $filterType = '';
    public $filterDate = '';

    public function with(): array
    {
        $query = GateLog::with(['gateRequest', 'user'])->orderBy('created_at', 'desc');

        if ($this->filterType) {
            $query->whereHas('gateRequest', function ($q) {
                $q->where('type', $this->filterType);
            });
        }

        if ($this->filterDate) {
            $query->whereDate('created_at', $this->filterDate);
        }

        $stats = [
            'inbound' => GateRequest::where('type', 'INBOUND')->whereIn('status', ['IN_LOCATION', 'CHECKED_OUT'])->count(),
            'outbound' => GateRequest::where('type', 'OUTBOUND')->whereIn('status', ['IN_LOCATION', 'CHECKED_OUT'])->count(),
            'rejected' => GateRequest::where('status', 'REJECTED')->count(),
            'pending' => GateRequest::whereIn('status', ['PENDING', 'VALIDATING'])->count(),
        ];

        return [
            'logs' => $query->paginate(15),
            'stats' => $stats,
        ];
    }

    public function exportCsv()
    {
        $query = GateLog::with(['gateRequest', 'user'])->orderBy('created_at', 'desc');

        if ($this->filterType) {
            $query->whereHas('gateRequest', function ($q) {
                $q->where('type', $this->filterType);
            });
        }

        if ($this->filterDate) {
            $query->whereDate('created_at', $this->filterDate);
        }

        $logs = $query->get();

        $csvFileName = 'audit_report_' . date('Y_m_d_H_i_s') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal', 'Petugas', 'Aksi', 'Tipe', 'Kendaraan', 'Perusahaan']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user->name,
                    $log->action,
                    $log->gateRequest->type ?? '-',
                    $log->gateRequest->vehicle_number ?? '-',
                    $log->gateRequest->company_name ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $csvFileName, $headers);
    }

    public function exportPdf()
    {
        $query = GateLog::with(['gateRequest', 'user'])->orderBy('created_at', 'desc');

        if ($this->filterType) {
            $query->whereHas('gateRequest', function ($q) {
                $q->where('type', $this->filterType);
            });
        }

        if ($this->filterDate) {
            $query->whereDate('created_at', $this->filterDate);
        }

        $logs = $query->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.gate-log', ['logs' => $logs]);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'audit_report_' . date('Y_m_d_H_i_s') . '.pdf');
    }
};
?>

<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Audit & Analitik Manajemen</h2>
                
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-sm flex items-center transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Download Laporan
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 z-50 overflow-hidden">
                        <button wire:click="exportCsv" @click="open = false" class="w-full text-left px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center transition-colors">
                            <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Download Excel (.csv)
                        </button>
                        <button wire:click="exportPdf" @click="open = false" class="w-full text-left px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center transition-colors border-t border-gray-100 dark:border-gray-700">
                            <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Download PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Analitik Chart.js -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm flex flex-col items-center justify-center">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">Distribusi Status Pengajuan (Keseluruhan)</h3>
                    <div class="relative w-64 h-64">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm flex flex-col justify-center">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">Ringkasan Eksekutif</h3>
                    <ul class="space-y-4 text-sm">
                        <li class="flex justify-between items-center bg-blue-50 dark:bg-blue-900/30 p-3 rounded">
                            <span class="font-medium text-blue-800 dark:text-blue-300">Total Kendaraan Masuk (Inbound Selesai)</span>
                            <span class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['inbound'] }}</span>
                        </li>
                        <li class="flex justify-between items-center bg-orange-50 dark:bg-orange-900/30 p-3 rounded">
                            <span class="font-medium text-orange-800 dark:text-orange-300">Total Kendaraan Keluar (Outbound Selesai)</span>
                            <span class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['outbound'] }}</span>
                        </li>
                        <li class="flex justify-between items-center bg-yellow-50 dark:bg-yellow-900/30 p-3 rounded">
                            <span class="font-medium text-yellow-800 dark:text-yellow-300">Total Menunggu Validasi (Pending)</span>
                            <span class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending'] }}</span>
                        </li>
                        <li class="flex justify-between items-center bg-red-50 dark:bg-red-900/30 p-3 rounded">
                            <span class="font-medium text-red-800 dark:text-red-300">Total Ditolak (Rejected)</span>
                            <span class="text-xl font-bold text-red-600 dark:text-red-400">{{ $stats['rejected'] }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                document.addEventListener('livewire:initialized', () => {
                    const ctx = document.getElementById('statusChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Inbound Selesai', 'Outbound Selesai', 'Pending', 'Ditolak'],
                            datasets: [{
                                data: [
                                    {{ $stats['inbound'] }},
                                    {{ $stats['outbound'] }},
                                    {{ $stats['pending'] }},
                                    {{ $stats['rejected'] }}
                                ],
                                backgroundColor: [
                                    '#3B82F6', // blue
                                    '#F97316', // orange
                                    '#EAB308', // yellow
                                    '#EF4444'  // red
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                });
            </script>
            
            <hr class="border-gray-200 dark:border-gray-700 mb-6">
            
            <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-gray-100">Log Aktivitas Detail</h3>
            <!-- Filters -->
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 mb-6">
                <div class="w-full sm:w-1/3">
                    <x-input-label for="filterDate" :value="__('Tanggal')" />
                    <x-text-input wire:model.live="filterDate" id="filterDate" class="block mt-1 w-full" type="date" />
                </div>
                <div class="w-full sm:w-1/3">
                    <x-input-label for="filterType" :value="__('Tipe Pengajuan')" />
                    <select wire:model.live="filterType" id="filterType" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">Semua</option>
                        <option value="INBOUND">Inbound (Masuk)</option>
                        <option value="OUTBOUND">Outbound (Keluar)</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Waktu Eksekusi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Petugas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Detail Kendaraan / PIC</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Lampiran Bukti</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $log->user->name }}
                                    <span class="block text-xs text-gray-500">{{ $log->user->role }}</span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-bold rounded-full 
                                        @if(str_contains($log->action, 'APPROVE')) bg-green-100 text-green-800 
                                        @elseif(str_contains($log->action, 'REJECT')) bg-red-100 text-red-800 
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ str_replace('_', ' ', $log->action) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    @if($log->gateRequest)
                                        <div class="font-semibold">{{ $log->gateRequest->vehicle_number }}</div>
                                        <div class="text-xs">{{ $log->gateRequest->company_name }}</div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 mt-1">
                                            {{ $log->gateRequest->type }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">Data Dihapus</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    @if($log->security_photo_path)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($log->security_photo_path) }}" target="_blank" class="text-blue-600 hover:underline">Lihat Foto</a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                    
                                    @if($log->checked_items && count($log->checked_items) > 0)
                                        <div class="mt-1 text-xs">Ceklis: {{ count(array_filter($log->checked_items)) }} / {{ count($log->checked_items) }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-gray-500">Belum ada data log gerbang.</td>
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
</div>