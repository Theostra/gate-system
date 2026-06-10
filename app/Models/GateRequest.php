<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'type', 'warehouse_type', 'po_number', 'vehicle_number', 'driver_name', 'company_name', 'company_address', 'phone_number', 'purpose', 'scheduled_date', 'items', 'manifest_document_path', 'vehicle_photo_path', 'item_photo_path', 'ga_notes', 'status', 'barcode', 'ai_validation_status', 'ai_is_valid', 'ai_extracted_vehicle', 'ai_extracted_driver', 'ai_reason', 'ai_validation_feedback'])]
class GateRequest extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gateLogs(): HasMany
    {
        return $this->hasMany(GateLog::class);
    }
}
