<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\GateRequest;
use App\Services\GeminiAiService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessAiValidation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $requestId;

    /**
     * Create a new job instance.
     */
    public function __construct($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiAiService $aiService): void
    {
        $request = GateRequest::find($this->requestId);
        if (!$request) return;

        try {
            $request->update(['ai_validation_status' => 'PROCESSING']);

            $result = $aiService->validateManifest(
                $request->manifest_document_path,
                $request->vehicle_photo_path,
                $request->item_photo_path,
                $request->vehicle_number,
                $request->driver_name
            );

            if ($result) {
                $statusBadge = $result['is_valid'] 
                    ? '<span class="px-2 py-0.5 text-xs font-semibold rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">SESUAI / VALID</span>'
                    : '<span class="px-2 py-0.5 text-xs font-semibold rounded bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">TERDETEKSI ANOMALI</span>';

                // Check string matches ignoring spaces
                $vehicleInputClean = strtolower(preg_replace('/\s+/', '', $request->vehicle_number));
                $vehicleAiClean = strtolower(preg_replace('/\s+/', '', $result['extracted_vehicle_number'] ?? ''));
                $vehicleMatch = ($vehicleInputClean === $vehicleAiClean);

                $driverInputClean = strtolower(trim($request->driver_name));
                $driverAiClean = strtolower(trim($result['extracted_driver_name'] ?? ''));
                $driverMatch = (strpos($driverAiClean, $driverInputClean) !== false || strpos($driverInputClean, $driverAiClean) !== false || $driverInputClean === $driverAiClean);

                $vehicleClass = $vehicleMatch ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/40' : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800/40';
                $driverClass = $driverMatch ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/40' : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800/40';

                $feedbackHtml = "
<div class=\"space-y-4\">
    <div class=\"flex items-center gap-2\">
        <span class=\"font-bold text-gray-800 dark:text-gray-200\">Status Hasil AI:</span>
        {$statusBadge}
    </div>
    
    <div class=\"grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm\">
        <div>
            <span class=\"block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5\">Plat Nomor (Input vs AI)</span>
            <div class=\"flex items-center gap-1.5\">
                <span class=\"px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-mono text-xs rounded border border-gray-200 dark:border-gray-700\">{$request->vehicle_number}</span>
                <span class=\"text-gray-400 text-xs\">➔</span>
                <span class=\"px-2 py-0.5 font-mono text-xs rounded border {$vehicleClass}\">" . ($result['extracted_vehicle_number'] ?: 'Tidak terdeteksi') . "</span>
            </div>
        </div>
        
        <div>
            <span class=\"block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5\">Nama Supir (Input vs AI)</span>
            <div class=\"flex items-center gap-1.5\">
                <span class=\"px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-mono text-xs rounded border border-gray-200 dark:border-gray-700\">{$request->driver_name}</span>
                <span class=\"text-gray-400 text-xs\">➔</span>
                <span class=\"px-2 py-0.5 font-mono text-xs rounded border {$driverClass}\">" . ($result['extracted_driver_name'] ?: 'Tidak terdeteksi') . "</span>
            </div>
        </div>
    </div>
    
    <div class=\"p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg text-sm border border-gray-100 dark:border-gray-800\">
        <span class=\"block font-bold text-gray-700 dark:text-gray-300 mb-1\">Analisis / Catatan AI:</span>
        <p class=\"text-gray-600 dark:text-gray-400\">{$result['reason']}</p>
    </div>
</div>
";

                $request->update([
                    'ai_validation_status' => 'COMPLETED',
                    'ai_is_valid' => $result['is_valid'],
                    'ai_extracted_vehicle' => $result['extracted_vehicle_number'],
                    'ai_extracted_driver' => $result['extracted_driver_name'],
                    'ai_reason' => $result['reason'],
                    'ai_validation_feedback' => $feedbackHtml
                ]);
            } else {
                $request->update([
                    'ai_validation_status' => 'FAILED',
                    'ai_reason' => 'Gagal memproses validasi AI.',
                    'ai_validation_feedback' => 'Gagal memproses validasi AI.'
                ]);
            }
        } catch (\Exception $e) {
            $request->update([
                'ai_validation_status' => 'FAILED',
                'ai_reason' => 'Kesalahan saat memproses AI: ' . $e->getMessage(),
                'ai_validation_feedback' => 'Kesalahan saat memproses AI: ' . $e->getMessage()
            ]);
        }
    }
}
