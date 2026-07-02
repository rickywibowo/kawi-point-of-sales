import { defineStore } from 'pinia';

export const useReportsStore = defineStore('reports', {
    state: () => ({
        period: 'Bulan berjalan',
        sales: {
            transactionCount: 0,
            grandTotal: 0,
            taxTotal: 0,
            discountTotal: 0,
        },
        stock: {
            skuCount: 2,
            stockValue: 810000,
            minimumStockAlerts: 0,
        },
        accounting: {
            revenue: 0,
            expenses: 0,
            netProfit: 0,
        },
    }),

    getters: {
        reportCards: (state) => [
            { label: 'Sales', value: state.sales.grandTotal },
            { label: 'Stock Value', value: state.stock.stockValue },
            { label: 'Net Profit', value: state.accounting.netProfit },
        ],
    },
});
