<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use BelongsToBusiness, UsesUuid;

    protected $fillable = [
        'business_id',
        'from_branch_id',
        'to_branch_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'uuid',
        'transfer_number',
        'status',
        'notes',
    ];
}
