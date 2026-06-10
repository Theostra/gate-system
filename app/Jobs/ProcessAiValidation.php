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
                $request->update([
                    'ai_validation_status' => 'COMPLETED',
                    'ai_is_valid' => $result['is_valid'],
                    'ai_extracted_vehicle' => $result['extracted_vehicle_number'],
                    'ai_extracted_driver' => $result['extracted_driver_name'],
                    'ai_reason' => $result['reason'],
                    'ai_validation_feedback' => $result['reason']
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
