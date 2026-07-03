import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

export const useAccountingStore = defineStore('accounting', {
    state: () => ({
        accounts: [
            { code: '1100', name: 'Kas', type: 'asset' },
            { code: '1300', name: 'Persediaan', type: 'asset' },
            { code: '4100', name: 'Penjualan', type: 'revenue' },
            { code: '5100', name: 'Harga Pokok Penjualan', type: 'cost_of_goods_sold' },
        ],
        trialBalanceStatus: 'balanced',
        profitAndLoss: {
            revenue: 0,
            expenses: 0,
            netProfit: 0,
        },
        balanceSheet: {
            assets: 0,
            liabilitiesAndEquity: 0,
            isBalanced: true,
        },
        cashFlow: {
            netCashFlow: 0,
            endingCashBalance: 0,
        },
        operationalExpenses: [
            { number: 'EXP-DEMO-001', category: 'Utilities', amount: 125000 },
            { number: 'EXP-DEMO-002', category: 'Packaging', amount: 87000 },
        ],
        paymentSettlements: [
            { number: 'SETTLE-DEMO-001', method: 'qris', expected: 27750, reported: 27750, variance: 0 },
            { number: 'SETTLE-DEMO-002', method: 'cash', expected: 38850, reported: 38800, variance: -50 },
        ],
        providerImports: [
            { number: 'IMP-QRIS-001', provider: 'QRIS Acquirer', matched: 12, unmatched: 1, variance: -2500 },
        ],
    }),

    getters: {
        accountCount: (state) => state.accounts.length,
        statementStatus: (state) => state.balanceSheet.isBalanced ? 'statements balanced' : 'review needed',
        expenseTotal: (state) => state.operationalExpenses.reduce((total, expense) => total + expense.amount, 0),
        settlementVarianceTotal: (state) => state.paymentSettlements.reduce((total, settlement) => total + settlement.variance, 0),
        providerImportReviewCount: (state) => state.providerImports.reduce((total, providerImport) => total + providerImport.unmatched, 0),
    },

    actions: {
        async loadFromApi() {
            const [accounting, settlements, providerImports] = await Promise.all([
                apiGet('/accounting'),
                apiGet('/payment-settlements'),
                apiGet('/payment-provider-imports'),
            ]);

            this.accounts = accounting.accounts?.map((account) => ({
                code: account.code,
                name: account.name,
                type: account.type,
            })) ?? this.accounts;
            this.trialBalanceStatus = accounting.trial_balance?.is_balanced === false ? 'review needed' : 'balanced';
            this.profitAndLoss = {
                revenue: Number(accounting.profit_and_loss?.revenue?.total ?? this.profitAndLoss.revenue),
                expenses: Number(accounting.profit_and_loss?.expenses?.total ?? this.profitAndLoss.expenses),
                netProfit: Number(accounting.profit_and_loss?.net_profit ?? this.profitAndLoss.netProfit),
            };
            this.balanceSheet = {
                assets: Number(accounting.balance_sheet?.assets?.total ?? this.balanceSheet.assets),
                liabilitiesAndEquity: Number(accounting.balance_sheet?.liabilities_and_equity_total ?? this.balanceSheet.liabilitiesAndEquity),
                isBalanced: Boolean(accounting.balance_sheet?.is_balanced ?? this.balanceSheet.isBalanced),
            };
            this.cashFlow = {
                netCashFlow: Number(accounting.cash_flow?.operating?.net_cash_flow ?? this.cashFlow.netCashFlow),
                endingCashBalance: Number(accounting.cash_flow?.ending_cash_balance ?? this.cashFlow.endingCashBalance),
            };
            this.operationalExpenses = accounting.operational_expenses?.map((expense) => ({
                number: expense.expense_number,
                category: expense.category,
                amount: Number(expense.amount ?? 0),
            })) ?? this.operationalExpenses;
            this.paymentSettlements = settlements.payment_settlements?.map((settlement) => ({
                number: settlement.settlement_number,
                method: settlement.method,
                expected: Number(settlement.expected_amount ?? 0),
                reported: Number(settlement.reported_amount ?? 0),
                variance: Number(settlement.variance_amount ?? 0),
            })) ?? this.paymentSettlements;
            this.providerImports = providerImports.payment_provider_imports?.map((providerImport) => ({
                number: providerImport.import_number,
                provider: providerImport.provider,
                matched: providerImport.matched_count ?? 0,
                unmatched: providerImport.unmatched_count ?? 0,
                variance: Number(providerImport.variance_to_settlement ?? 0),
            })) ?? this.providerImports;
        },
    },
});
