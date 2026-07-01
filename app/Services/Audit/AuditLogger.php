<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public function record(
        string $action,
        ?object $entity = null,
        ?array $before = null,
        ?array $after = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'business_id' => $request->attributes->get('business')?->id,
            'branch_id' => $request->attributes->get('branch')?->id,
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity->id ?? null,
            'before_values' => $before,
            'after_values' => $after,
            'ip_address' => $request->ip(),
        ]);
    }
}
