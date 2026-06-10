<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['gate_request_id', 'user_id', 'action', 'scanned_barcode', 'ai_validation_result', 'notes', 'security_photo_path', 'checked_items'])]
class GateLog extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'ai_validation_result' => 'array',
            'checked_items' => 'array',
        ];
    }

    public function gateRequest(): BelongsTo
    {
        return $this->belongsTo(GateRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
