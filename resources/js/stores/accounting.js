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
    }),

    getters: {
        accountCount: (state) => state.accounts.length,
    },
});
