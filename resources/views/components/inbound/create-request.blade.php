<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\GateRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

new class extends Component
{
    use WithFileUploads;

    public $requestId = null;
    
    public $vehicle_number;
    public $driver_name;
    public $company_name;
    public $company_address;
    public $phone_number;
    public $purpose;
    public $manifest_document;
    public $vehicle_photo;
    public $item_photo;
    public $items = [['name' => '', 'qty' => '', 'unit' => 'Pcs']]; // Array of items
    public $warehouse_type = 'GENERAL';
    public $po_number;
    public $scheduled_date;

    public function mount($id = null)
    {
        $this->scheduled_date = date('Y-m-d');
        if ($id) {
            $request = GateRequest::where('user_id', auth()->id())
                                  ->where('id', $id)
                                  ->where('status', 'REJECTED')
                                  ->firstOrFail();
                                  
            $this->requestId = $request->id;
            $this->vehicle_number = $request->vehicle_number;
            $this->driver_name = $request->driver_name;
            $this->company_name = $request->company_name;
            $this->company_address = $request->company_address;
            $this->phone_number = $request->phone_number;
            $this->purpose = $request->purpose;
            $this->scheduled_date = $request->scheduled_date;
            $this->warehouse_type = $request->warehouse_type;
            $this->po_number = $request->po_number;
            if (is_array($request->items) && count($request->items) > 0) {
                $formattedItems = [];
                foreach ($request->items as $item) {
                    if (is_array($item) && isset($item['name'])) {
                        $formattedItems[] = $item;
                    } else {
                        $formattedItems[] = ['name' => (string)$item, 'qty' => 1, 'unit' => 'Pcs'];
                    }
                }
                $this->items = $formattedItems;
            } else {
                $this->items = [['name' => '', 'qty' => '', 'unit' => 'Pcs']];
            }
        }
    }

    protected function rules()
    {
        return [
            'vehicle_number' => 'required|string|max:20',
            'warehouse_type' => 'required|string|in:RAW_MATERIAL,FINISHED_GOODS,PACKAGING,GENERAL,NON_WAREHOUSE,OTHER',
            'po_number' => 'nullable|string|max:50',
            'driver_name' => 'required|string|max:100',
            'company_name' => 'required|string|max:100',
            'company_address' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'purpose' => 'required|string|max:255',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'manifest_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'vehicle_photo' => $this->requestId ? 'nullable|file|image|max:5120' : 'required|file|image|max:5120',
            'item_photo' => $this->requestId ? 'nullable|file|image|max:5120' : 'required|file|image|max:5120',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.qty' => 'nullable|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
        ];
    }

    public function addItem()
    {
        $this->items[] = ['name' => '', 'qty' => '', 'unit' => 'Pcs'];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function submit()
    {
        $this->validate();

        $manifestPath = $this->manifest_document ? $this->manifest_document->store('manifests_inbound', 'public') : null;
        $vehiclePhotoPath = $this->vehicle_photo ? $this->vehicle_photo->store('inbound_photos', 'public') : null;
        $itemPhotoPath = $this->item_photo ? $this->item_photo->store('inbound_photos', 'public') : null;
        
        // Filter empty items
        $filteredItems = array_filter($this->items, fn($item) => is_array($item) && !empty($item['name']));

        if ($this->requestId) {
            $request = GateRequest::findOrFail($this->requestId);
            
            $data = [
                'vehicle_number' => $this->vehicle_number,
                'warehouse_type' => $this->warehouse_type,
                'po_number' => $this->po_number,
                'driver_name' => $this->driver_name,
                'company_name' => $this->company_name,
                'company_address' => $this->company_address,
                'phone_number' => $this->phone_number,
                'purpose' => $this->purpose,
                'scheduled_date' => $this->scheduled_date,
                'items' => array_values($filteredItems),
                'status' => 'PENDING', // Reset to pending
                'ga_notes' => null, // Clear notes
            ];
            
            if ($manifestPath) $data['manifest_document_path'] = $manifestPath;
            if ($vehiclePhotoPath) $data['vehicle_photo_path'] = $vehiclePhotoPath;
            if ($itemPhotoPath) $data['item_photo_path'] = $itemPhotoPath;
            
            $request->update($data);
            \App\Jobs\ProcessAiValidation::dispatch($request->id);
            session()->flash('message', 'Revisi pengajuan berhasil dikirim.');
        } else {
            $newRequest = GateRequest::create([
                'user_id' => auth()->id(),
                'type' => 'INBOUND',
                'warehouse_type' => $this->warehouse_type,
                'po_number' => $this->po_number,
                'vehicle_number' => $this->vehicle_number,
                'driver_name' => $this->driver_name,
                'company_name' => $this->company_name,
                'company_address' => $this->company_address,
                'phone_number' => $this->phone_number,
                'purpose' => $this->purpose,
                'scheduled_date' => $this->scheduled_date,
                'items' => array_values($filteredItems),
                'manifest_document_path' => $manifestPath,
                'vehicle_photo_path' => $vehiclePhotoPath,
                'item_photo_path' => $itemPhotoPath,
                'status' => 'PENDING',
            ]);
            \App\Jobs\ProcessAiValidation::dispatch($newRequest->id);
            session()->flash('message', 'Pengajuan berhasil dikirim dan menunggu validasi GA.');
        }

        return redirect()->route('dashboard');
    }
};
?>

<div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">
                {{ $requestId ? 'Revisi Pengajuan Inbound' : 'Form Pengajuan Inbound' }}
            </h2>

            <form wire:submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="company_name" :value="__('Nama Perusahaan/Vendor')" />
                        <x-text-input wire:model="company_name" id="company_name" class="block mt-1 w-full" type="text" required autofocus />
                        <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="company_address" :value="__('Alamat Perusahaan')" />
                        <x-text-input wire:model="company_address" id="company_address" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('company_address')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="warehouse_type" :value="__('Tipe Gudang / Tujuan')" />
                        <select wire:model="warehouse_type" id="warehouse_type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full" required>
                            <option value="GENERAL">Gudang Umum (Sparepart/ATK)</option>
                            <option value="RAW_MATERIAL">Gudang Bahan Baku (Cold Chain/Produksi)</option>
                            <option value="FINISHED_GOODS">Gudang Barang Jadi (Vaksin/Ekspor)</option>
                            <option value="PACKAGING">Gudang Bahan Pengemas (Ampul/Vial/Karton)</option>
                            <option value="NON_WAREHOUSE">Bukan Ke Gudang (Proyek/Kantor)</option>
                            <option value="OTHER">Pengiriman Lain (Tidak Ada di List)</option>
                        </select>
                        <x-input-error :messages="$errors->get('warehouse_type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone_number" :value="__('No. Telepon/HP')" />
                        <x-text-input wire:model="phone_number" id="phone_number" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="po_number" :value="__('Nomor PO / DO (Opsional)')" />
                        <x-text-input wire:model="po_number" id="po_number" class="block mt-1 w-full uppercase font-mono" type="text" placeholder="Contoh: PO-2026-0001" />
                        <x-input-error :messages="$errors->get('po_number')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="vehicle_number" :value="__('Nomor Kendaraan / Plat')" />
                        <x-text-input wire:model="vehicle_number" id="vehicle_number" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('vehicle_number')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="driver_name" :value="__('Nama PIC')" />
                        <x-text-input wire:model="driver_name" id="driver_name" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('driver_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="scheduled_date" :value="__('Tanggal Masuk Barang')" />
                        <x-text-input wire:model="scheduled_date" id="scheduled_date" class="block mt-1 w-full" type="date" required />
                        <x-input-error :messages="$errors->get('scheduled_date')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="purpose" :value="__('Keperluan')" />
                    <x-text-input wire:model="purpose" id="purpose" class="block mt-1 w-full" type="text" placeholder="Contoh: Pengiriman Material A" required />
                    <x-input-error :messages="$errors->get('purpose')" class="mt-2" />
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-6">
                    <div class="flex justify-between items-center mb-2">
                        <x-input-label :value="__('Daftar Barang (Opsional jika sangat banyak)')" />
                        <button type="button" wire:click="addItem" class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                            + Tambah Barang
                        </button>
                    </div>
                    
                    @foreach($items as $index => $item)
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 mb-2 items-center">
                        <div class="sm:col-span-6">
                            <x-text-input wire:model="items.{{ $index }}.name" class="block w-full text-sm" type="text" placeholder="Nama Barang" />
                            <x-input-error :messages="$errors->get('items.'.$index.'.name')" class="mt-1 text-xs" />
                        </div>
                        <div class="sm:col-span-3">
                            <x-text-input wire:model="items.{{ $index }}.qty" class="block w-full text-sm text-center" type="number" placeholder="Jumlah" />
                            <x-input-error :messages="$errors->get('items.'.$index.'.qty')" class="mt-1 text-xs" />
                        </div>
                        <div class="sm:col-span-2">
                            <select wire:model="items.{{ $index }}.unit" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block w-full text-sm">
                                <option value="Pcs">Pcs</option>
                                <option value="Boks">Boks</option>
                                <option value="Karton">Karton</option>
                                <option value="Palet">Palet</option>
                                <option value="Botol">Botol</option>
                                <option value="Vial">Vial</option>
                                <option value="Ampul">Ampul</option>
                                <option value="Kg">Kg</option>
                                <option value="Liter">Liter</option>
                            </select>
                            <x-input-error :messages="$errors->get('items.'.$index.'.unit')" class="mt-1 text-xs" />
                        </div>
                        <div class="sm:col-span-1 flex justify-center">
                            @if(count($items) > 1)
                                <button type="button" wire:click="removeItem({{ $index }})" class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kosongkan jika ingin menggunakan PDF lampiran untuk daftar barang lengkap.</p>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="vehicle_photo" :value="__('Foto Kendaraan (Wajib)')" />
                        <input wire:model="vehicle_photo" id="vehicle_photo" type="file" accept="image/*" class="block mt-1 w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600" required>
                        <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-1 font-medium">
                            * Wajib mengunggah foto tampak depan dengan Plat Nomor kendaraan terlihat jelas untuk mempermudah proses verifikasi dan validasi.
                        </p>
                        <x-input-error :messages="$errors->get('vehicle_photo')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="item_photo" :value="__('Foto Barang (Wajib)')" />
                        <input wire:model="item_photo" id="item_photo" type="file" accept="image/*" class="block mt-1 w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600" required>
                        <x-input-error :messages="$errors->get('item_photo')" class="mt-2" />
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <x-input-label for="manifest_document" :value="__('Dokumen Surat Jalan / DO (Opsional, PDF/JPG)')" />
                    <input wire:model="manifest_document" id="manifest_document" type="file" class="block mt-1 w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600">
                    <x-input-error :messages="$errors->get('manifest_document')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <x-primary-button class="ml-4" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">Kirim Pengajuan</span>
                        <span wire:loading wire:target="submit">Mengirim...</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>