<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreJournalEntryRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function index(Request $request, AccountingService $accounting): JsonResponse
    {
        $business = $request->attributes->get('business');
        $branch = $request->attributes->get('branch');

        return response()->json([
            'accounts' => Account::query()->forBusiness($business->id)->orderBy('code')->get(),
            'journal_entries' => JournalEntry::query()
                ->forTenant($business->id, $branch?->id)
                ->with('lines.account')
                ->latest('journal_date')
                ->limit(50)
                ->get(),
            'trial_balance' => $accounting->trialBalance($business->id),
            'profit_and_loss' => $accounting->profitAndLoss($business->id),
            'general_ledger' => $accounting->generalLedger(
                $business->id,
                $request->query('date_from'),
                $request->query('date_to'),
            ),
            'balance_sheet' => $accounting->balanceSheet($business->id),
            'cash_flow' => $accounting->cashFlow(
                $business->id,
                $request->query('date_from'),
                $request->query('date_to'),
            ),
        ]);
    }

    public function store(StoreJournalEntryRequest $request, AccountingService $accounting): JsonResponse
    {
        $journal = $accounting->postJournal(
            $request->attributes->get('business'),
            $request->attributes->get('branch')?->id,
            $request->validated(),
            $request,
        );

        return response()->json(['journal_entry' => $journal], 201);
    }
}
