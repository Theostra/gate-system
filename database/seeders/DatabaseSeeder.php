<?php
 
namespace Database\Seeders;
 
use App\Models\User;
use App\Models\GateRequest;
use App\Models\GateLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
 
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $karyawan = User::firstOrCreate(['email' => 'karyawan@biofarma.co.id'], [
            'name' => 'Akun Karyawan (PIC)',
            'password' => Hash::make('password'),
            'role' => 'KARYAWAN',
            'department' => 'Produksi'
        ]);
 
        $ga = User::firstOrCreate(['email' => 'ga@biofarma.co.id'], [
            'name' => 'Akun General Affairs',
            'password' => Hash::make('password'),
            'role' => 'GA'
        ]);
 
        $security = User::firstOrCreate(['email' => 'security@biofarma.co.id'], [
            'name' => 'Akun Security Gerbang',
            'password' => Hash::make('password'),
            'role' => 'SECURITY'
        ]);
 
        User::firstOrCreate(['email' => 'management@biofarma.co.id'], [
            'name' => 'Akun Management',
            'password' => Hash::make('password'),
            'role' => 'MANAGEMENT'
        ]);
 
        // Clear existing requests/logs before seeding
        GateLog::truncate();
        GateRequest::truncate();
 
        // 1. INBOUND Request - Completed (Check-in & Check-out complete)
        $req1 = GateRequest::create([
            'user_id' => $karyawan->id,
            'type' => 'INBOUND',
            'warehouse_type' => 'RAW_MATERIAL',
            'po_number' => 'PO-2026-0001',
            'vehicle_number' => 'D 1234 AB',
            'driver_name' => 'Ahmad Sopian',
            'company_name' => 'PT Sumber Kimia Tama',
            'company_address' => 'Jl. Industri No. 45, Bandung',
            'phone_number' => '081234567890',
            'purpose' => 'Pengiriman Bahan Baku Vaksin BCG',
            'items' => [
                ['name' => 'Bahan Baku Active BCG', 'qty' => 50, 'unit' => 'Vial'],
                ['name' => 'Larutan Buffer A', 'qty' => 10, 'unit' => 'Botol']
            ],
            'vehicle_photo_path' => 'vehicle_photos/mock_truck.jpg',
            'item_photo_path' => 'item_photos/mock_cargo.jpg',
            'status' => 'COMPLETED',
            'barcode' => 'BCG-' . strtoupper(Str::random(6)),
        ]);
 
        // Logs for BCG request
        GateLog::create([
            'gate_request_id' => $req1->id,
            'user_id' => $ga->id,
            'action' => 'VALIDATE_APPROVE',
            'notes' => 'Dokumen lengkap dan sesuai.',
            'created_at' => now()->subHours(4),
        ]);
 
        GateLog::create([
            'gate_request_id' => $req1->id,
            'user_id' => $security->id,
            'action' => 'CHECK_IN_APPROVE',
            'scanned_barcode' => $req1->barcode,
            'notes' => 'Kendaraan masuk. Fisik sesuai.',
            'checked_items' => [true, true],
            'security_photo_path' => 'security_photos/mock_gate.jpg',
            'created_at' => now()->subHours(3),
        ]);
 
        // 2. OUTBOUND Request - Approved/Ready for Scan
        $req2 = GateRequest::create([
            'user_id' => $karyawan->id,
            'type' => 'OUTBOUND',
            'warehouse_type' => 'FINISHED_GOODS',
            'po_number' => 'DO-2026-0099',
            'vehicle_number' => 'B 9999 XYZ',
            'driver_name' => 'Budi Santoso',
            'company_name' => 'PT Bio Farma (Divisi Logistik)',
            'company_address' => 'Jl. Pasteur No. 28, Bandung',
            'phone_number' => '087766554433',
            'purpose' => 'Distribusi Vaksin Polio ke Dinkes Jabar',
            'items' => [
                ['name' => 'Vaksin Polio Cair', 'qty' => 200, 'unit' => 'Boks'],
                ['name' => 'Ice Pack Cooler', 'qty' => 4, 'unit' => 'Palet']
            ],
            'vehicle_photo_path' => 'vehicle_photos/mock_van.jpg',
            'item_photo_path' => 'item_photos/mock_boxes.jpg',
            'status' => 'APPROVED',
            'barcode' => 'POLIO-' . strtoupper(Str::random(6)),
        ]);
 
        GateLog::create([
            'gate_request_id' => $req2->id,
            'user_id' => $ga->id,
            'action' => 'VALIDATE_APPROVE',
            'notes' => 'Distribusi disetujui.',
            'created_at' => now()->subMinutes(30),
        ]);
 
        // 3. INBOUND Request - Rejected (For testing revision flows)
        $req3 = GateRequest::create([
            'user_id' => $karyawan->id,
            'type' => 'INBOUND',
            'warehouse_type' => 'PACKAGING',
            'po_number' => 'PO-2026-0005',
            'vehicle_number' => 'D 5555 FGH',
            'driver_name' => 'Dadang Hermawan',
            'company_name' => 'CV Karton Mandiri',
            'company_address' => 'Jl. Kopo Sayati No. 12, Bandung',
            'phone_number' => '089988776655',
            'purpose' => 'Kirim Dus Kemasan Ampul',
            'items' => [
                ['name' => 'Kemasan Box Ampul 5ml', 'qty' => 1000, 'unit' => 'Karton']
            ],
            'vehicle_photo_path' => 'vehicle_photos/mock_truck2.jpg',
            'item_photo_path' => 'item_photos/mock_cargo2.jpg',
            'status' => 'REJECTED',
            'ga_notes' => 'Mohon maaf, foto barang buram / tidak terlihat jelas. Tolong diunggah ulang.',
            'barcode' => 'KARTON-' . strtoupper(Str::random(6)),
        ]);
 
        GateLog::create([
            'gate_request_id' => $req3->id,
            'user_id' => $ga->id,
            'action' => 'VALIDATE_REJECT',
            'notes' => 'Foto barang buram / tidak terlihat jelas. Tolong diunggah ulang.',
            'created_at' => now()->subHours(1),
        ]);
 
        // 4. Legacy Request with Flat String Items (for testing backward compatibility)
        $req4 = GateRequest::create([
            'user_id' => $karyawan->id,
            'type' => 'INBOUND',
            'warehouse_type' => 'GENERAL',
            'po_number' => null,
            'vehicle_number' => 'D 8888 BB',
            'driver_name' => 'Eman Sulaeman',
            'company_name' => 'CV Sapu Bersih',
            'company_address' => 'Jl. Sukajadi No. 9, Bandung',
            'phone_number' => '085544332211',
            'purpose' => 'Kirim Alat Kebersihan & ATK Kantor',
            'items' => [
                'Sapu Ijuk Super',
                'Kertas HVS A4'
            ],
            'vehicle_photo_path' => 'vehicle_photos/mock_van2.jpg',
            'item_photo_path' => 'item_photos/mock_atk.jpg',
            'status' => 'COMPLETED',
            'barcode' => 'ATK-' . strtoupper(Str::random(6)),
        ]);
 
        GateLog::create([
            'gate_request_id' => $req4->id,
            'user_id' => $ga->id,
            'action' => 'VALIDATE_APPROVE',
            'notes' => 'Peralatan umum disetujui.',
            'created_at' => now()->subDays(1),
        ]);
 
        GateLog::create([
            'gate_request_id' => $req4->id,
            'user_id' => $security->id,
            'action' => 'CHECK_IN_APPROVE',
            'scanned_barcode' => $req4->barcode,
            'notes' => 'Barang ATK sesuai.',
            'checked_items' => [true, true],
            'created_at' => now()->subDays(1)->addHours(2),
        ]);
    }
}
