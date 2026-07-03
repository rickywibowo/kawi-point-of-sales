import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

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
            endingCashBalance: 0,
        },
    }),

    getters: {
        reportCards: (state) => [
            { label: 'Sales', value: state.sales.grandTotal },
            { label: 'Stock Value', value: state.stock.stockValue },
            { label: 'Net Profit', value: state.accounting.netProfit },
            { label: 'Cash Balance', value: state.accounting.endingCashBalance },
        ],
    },

    actions: {
        async loadFromApi() {
            const response = await apiGet('/reports');
            this.period = `${response.period?.from ?? ''} - ${response.period?.to ?? ''}`.trim();
            this.sales = {
                transactionCount: response.sales?.transaction_count ?? this.sales.transactionCount,
                grandTotal: Number(response.sales?.grand_total ?? this.sales.grandTotal),
                taxTotal: Number(response.sales?.tax_total ?? this.sales.taxTotal),
                discountTotal: Number(response.sales?.discount_total ?? this.sales.discountTotal),
            };
            this.stock = {
                skuCount: response.stock?.sku_count ?? this.stock.skuCount,
                stockValue: Number(response.stock?.stock_value ?? this.stock.stockValue),
                minimumStockAlerts: response.stock?.minimum_stock_alerts ?? this.stock.minimumStockAlerts,
            };
            this.accounting = {
                revenue: Number(response.accounting?.profit_and_loss?.revenue?.total ?? this.accounting.revenue),
                expenses: Number(response.accounting?.profit_and_loss?.expenses?.total ?? this.accounting.expenses),
                netProfit: Number(response.accounting?.profit_and_loss?.net_profit ?? this.accounting.netProfit),
                endingCashBalance: Number(response.accounting?.cash_flow?.ending_cash_balance ?? this.accounting.endingCashBalance),
            };
        },
    },
});
