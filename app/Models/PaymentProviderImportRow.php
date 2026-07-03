<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProviderImportRow extends Model
{
    protected $fillable = [
        'payment_provider_import_id',
        'sale_payment_id',
        'reference',
        'gross_amount',
        'fee_amount',
        'received_amount',
        'settled_at',
        'status',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'settled_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function providerImport(): BelongsTo
    {
        return $this->belongsTo(PaymentProviderImport::class, 'payment_provider_import_id');
    }

    public function salePayment(): BelongsTo
    {
        return $this->belongsTo(SalePayment::class);
    }
}
