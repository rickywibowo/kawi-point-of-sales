<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use BelongsToTenant, UsesUuid;

    protected $fillable = [
        'business_id',
        'branch_id',
        'supplier_id',
        'goods_receipt_id',
        'uuid',
        'return_number',
        'status',
        'return_date',
        'grand_total',
        'reason',
    ];
}
