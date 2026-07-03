import { defineStore } from 'pinia';

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
    }),

    getters: {
        accountCount: (state) => state.accounts.length,
        statementStatus: (state) => state.balanceSheet.isBalanced ? 'statements balanced' : 'review needed',
        expenseTotal: (state) => state.operationalExpenses.reduce((total, expense) => total + expense.amount, 0),
        settlementVarianceTotal: (state) => state.paymentSettlements.reduce((total, settlement) => total + settlement.variance, 0),
    },
});
