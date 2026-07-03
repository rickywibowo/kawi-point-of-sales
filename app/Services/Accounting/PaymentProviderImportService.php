<?php

namespace App\Services\Accounting;

use App\Models\Business;
use App\Models\PaymentProviderImport;
use App\Models\PaymentSettlement;
use App\Models\SalePayment;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentProviderImportService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function create(Business $business, ?int $branchId, array $data, Request $request): PaymentProviderImport
    {
        $settlement = PaymentSettlement::query()
            ->forTenant($business->id, $branchId)
            ->with('items.salePayment')
            ->whereKey($data['payment_settlement_id'])
            ->first();

        if (! $settlement) {
            throw ValidationException::withMessages(['payment_settlement_id' => ['The selected settlement is outside the active tenant.']]);
        }

        if ($settlement->method !== $data['method']) {
            throw ValidationException::withMessages(['method' => ['Provider method must match settlement method.']]);
        }

        $exists = PaymentProviderImport::query()
            ->where('business_id', $business->id)
            ->where('import_number', $data['import_number'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['import_number' => ['Import number already exists in this business.']]);
        }

        $rows = $this->normalizeRows($data);

        if ($rows === []) {
            throw ValidationException::withMessages(['rows' => ['Provider import requires at least one row.']]);
        }

        return DB::transaction(function () use ($business, $branchId, $settlement, $data, $rows, $request): PaymentProviderImport {
            $matchedCount = 0;
            $unmatchedCount = 0;
            $grossAmount = 0.0;
            $feeAmount = 0.0;
            $receivedAmount = 0.0;
            $paymentsByReference = $settlement->items
                ->pluck('salePayment')
                ->filter()
                ->filter(fn (SalePayment $payment) => filled($payment->reference))
                ->keyBy(fn (SalePayment $payment) => Str::lower((string) $payment->reference));

            $providerImport = PaymentProviderImport::query()->create([
                'business_id' => $business->id,
                'branch_id' => $branchId,
                'payment_settlement_id' => $settlement->id,
                'imported_by' => $request->user()?->id,
                'import_number' => $data['import_number'],
                'provider' => $data['provider'],
                'method' => $data['method'],
                'settlement_date' => $data['settlement_date'] ?? null,
                'status' => 'imported',
                'notes' => $data['notes'] ?? null,
                'imported_at' => now(),
            ]);

            foreach ($rows as $row) {
                $gross = round((float) $row['amount'], 2);
                $fee = round((float) ($row['fee_amount'] ?? 0), 2);
                $received = round($gross - $fee, 2);
                $payment = $paymentsByReference->get(Str::lower($row['reference']));
                $status = 'unmatched';

                if ($payment) {
                    $status = round((float) $payment->amount, 2) === $gross ? 'matched' : 'amount_mismatch';
                }

                $status === 'matched' ? $matchedCount++ : $unmatchedCount++;
                $grossAmount += $gross;
                $feeAmount += $fee;
                $receivedAmount += $received;

                $providerImport->rows()->create([
                    'sale_payment_id' => $payment?->id,
                    'reference' => $row['reference'],
                    'gross_amount' => $gross,
                    'fee_amount' => $fee,
                    'received_amount' => $received,
                    'settled_at' => isset($row['settled_at']) && $row['settled_at'] ? Carbon::parse($row['settled_at']) : null,
                    'status' => $status,
                    'raw_payload' => $row,
                ]);
            }

            $providerImport->update([
                'row_count' => count($rows),
                'matched_count' => $matchedCount,
                'unmatched_count' => $unmatchedCount,
                'gross_amount' => round($grossAmount, 2),
                'fee_amount' => round($feeAmount, 2),
                'received_amount' => round($receivedAmount, 2),
                'variance_to_settlement' => round($receivedAmount - (float) $settlement->reported_amount, 2),
                'status' => $unmatchedCount === 0 ? 'reconciled' : 'needs_review',
            ]);

            $providerImport->load(['settlement', 'rows.salePayment']);
            $this->audit->record('payment_provider_import.created', $providerImport, after: $providerImport->toArray(), request: $request);

            return $providerImport;
        });
    }

    private function normalizeRows(array $data): array
    {
        if (! empty($data['rows'])) {
            return collect($data['rows'])
                ->map(fn (array $row): array => [
                    'reference' => trim((string) $row['reference']),
                    'amount' => (float) $row['amount'],
                    'fee_amount' => (float) ($row['fee_amount'] ?? 0),
                    'settled_at' => $row['settled_at'] ?? null,
                ])
                ->filter(fn (array $row): bool => $row['reference'] !== '')
                ->values()
                ->all();
        }

        return $this->parseCsv($data['csv_content'] ?? '');
    }

    private function parseCsv(string $content): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $headers = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = collect($line)
                    ->map(fn (string $header): string => Str::of($header)->trim()->lower()->replace([' ', '-'], '_')->toString())
                    ->all();

                continue;
            }

            if ($line === [null] || $line === false) {
                continue;
            }

            $row = array_combine($headers, array_pad($line, count($headers), null));

            if (! $row || empty($row['reference'])) {
                continue;
            }

            $rows[] = [
                'reference' => trim((string) $row['reference']),
                'amount' => (float) ($row['amount'] ?? $row['gross_amount'] ?? 0),
                'fee_amount' => (float) ($row['fee_amount'] ?? $row['fee'] ?? 0),
                'settled_at' => $row['settled_at'] ?? $row['settlement_time'] ?? null,
            ];
        }

        fclose($handle);

        return $rows;
    }
}
