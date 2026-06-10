<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    /**
     * Extract and validate manifest document, vehicle photo, and item photo using Gemini API.
     */
    public function validateManifest($manifestPath, $vehiclePhotoPath, $itemPhotoPath, $vehicleNumber, $driverName)
    {
        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');

        if (!$apiKey) {
            // Fallback to simulation if API Key is not set
            return $this->mockValidation($vehicleNumber, $driverName);
        }

        try {
            $prompt = "Anda adalah sistem verifikasi gerbang berbasis AI. Anda diberikan beberapa file:
- File pertama yang Anda terima adalah dokumen Surat Jalan (Manifest).
- File kedua yang Anda terima adalah Foto Kendaraan aktual.
- File ketiga yang Anda terima adalah Foto Barang aktual.

Bandingkan dokumen dan foto tersebut dengan data input berikut:
- Nama Supir (Driver): \"{$driverName}\"
- Nomor Kendaraan (Vehicle Number): \"{$vehicleNumber}\"

Tugas verifikasi Anda:
1. Periksa Surat Jalan (file pertama): Ekstrak nama supir dan nomor kendaraan dari teks surat jalan. Apakah cocok dengan input?
2. Periksa Foto Kendaraan (file kedua): Apakah benar foto kendaraan? Jika plat nomor terlihat, apakah cocok dengan input \"{$vehicleNumber}\"?
3. Periksa Foto Barang (file ketiga): Apakah benar menampilkan fisik barang/kargo?

Berikan respons dalam format JSON dengan skema berikut:
{
  \"is_valid\": boolean (true jika semuanya valid & cocok, false jika ada anomali atau ketidakcocokan),
  \"extracted_vehicle_number\": \"string (nomor kendaraan yang diekstrak dari Surat Jalan)\",
  \"extracted_driver_name\": \"string (nama supir yang diekstrak dari Surat Jalan)\",
  \"reason\": \"string (penjelasan ringkas hasil verifikasi dalam bahasa Indonesia)\"
}";

            $parts = [];
            $parts[] = ['text' => $prompt];

            // 1. Manifest Document
            if ($manifestPath) {
                $filePath = storage_path('app/public/' . $manifestPath);
                if (file_exists($filePath)) {
                    $parts[] = [
                        'inlineData' => [
                            'mimeType' => mime_content_type($filePath),
                            'data' => base64_encode(file_get_contents($filePath)),
                        ]
                    ];
                }
            }

            // 2. Vehicle Photo
            if ($vehiclePhotoPath) {
                $filePath = storage_path('app/public/' . $vehiclePhotoPath);
                if (file_exists($filePath)) {
                    $parts[] = [
                        'inlineData' => [
                            'mimeType' => mime_content_type($filePath),
                            'data' => base64_encode(file_get_contents($filePath)),
                        ]
                    ];
                }
            }

            // 3. Item Photo
            if ($itemPhotoPath) {
                $filePath = storage_path('app/public/' . $itemPhotoPath);
                if (file_exists($filePath)) {
                    $parts[] = [
                        'inlineData' => [
                            'mimeType' => mime_content_type($filePath),
                            'data' => base64_encode(file_get_contents($filePath)),
                        ]
                    ];
                }
            }

            // If no files are valid/exist, fallback
            if (count($parts) <= 1) {
                return $this->mockValidation($vehicleNumber, $driverName);
            }

            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => $parts
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $textResult = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($textResult) {
                    $decoded = json_decode(trim($textResult), true);
                    if ($decoded) {
                        return [
                            'is_valid' => (bool)($decoded['is_valid'] ?? false),
                            'extracted_vehicle_number' => $decoded['extracted_vehicle_number'] ?? 'Tidak terdeteksi',
                            'extracted_driver_name' => $decoded['extracted_driver_name'] ?? 'Tidak terdeteksi',
                            'reason' => $decoded['reason'] ?? 'Berhasil memverifikasi dokumen.'
                        ];
                    }
                }
            }

            Log::error("Gemini API Error: " . $response->body());
            return $this->mockValidation($vehicleNumber, $driverName);

        } catch (\Exception $e) {
            Log::error("Gemini Service Exception: " . $e->getMessage());
            return $this->mockValidation($vehicleNumber, $driverName);
        }
    }

    /**
     * Fallback mock validation.
     */
    private function mockValidation($vehicleNumber, $driverName)
    {
        sleep(2);
        $isValid = rand(1, 100) > 20;

        return [
            'is_valid' => $isValid,
            'extracted_vehicle_number' => $isValid ? $vehicleNumber : 'B 1234 XYZ (Simulasi Anomali)',
            'extracted_driver_name' => $isValid ? $driverName : 'Unknown Driver (Simulasi)',
            'reason' => $isValid 
                ? 'Data cocok dengan dokumen (Simulasi).' 
                : 'Nomor kendaraan atau nama supir pada dokumen tidak sesuai dengan input (Simulasi).'
        ];
    }
}
