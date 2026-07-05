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
        purchasing: {
            purchaseOrderCount: 0,
            goodsReceiptTotal: 0,
            openPayableTotal: 0,
            supplierPaymentTotal: 0,
        },
        paymentMethods: [
            { method: 'cash', count: 1, amount: 60000 },
        ],
        topProducts: [
            { product: 'KAWI Rice Bowl', quantity: 1, total: 35000 },
        ],
        settlement: {
            count: 0,
            expectedAmount: 0,
            reportedAmount: 0,
            varianceAmount: 0,
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
            { label: 'Goods Receipt', value: state.purchasing.goodsReceiptTotal },
            { label: 'Settlement Variance', value: state.settlement.varianceAmount },
        ],
    },

    actions: {
        async loadFromApi() {
            const response = await apiGet('/reports');
            const reports = response.reports ?? response;

            this.period = `${reports.period?.from ?? ''} - ${reports.period?.to ?? ''}`.trim();
            this.sales = {
                transactionCount: reports.sales?.transaction_count ?? this.sales.transactionCount,
                grandTotal: Number(reports.sales?.grand_total ?? this.sales.grandTotal),
                taxTotal: Number(reports.sales?.tax_total ?? this.sales.taxTotal),
                discountTotal: Number(reports.sales?.discount_total ?? this.sales.discountTotal),
            };
            this.stock = {
                skuCount: reports.stock?.sku_count ?? this.stock.skuCount,
                stockValue: Number(reports.stock?.stock_value ?? this.stock.stockValue),
                minimumStockAlerts: reports.stock?.minimum_stock_alerts ?? this.stock.minimumStockAlerts,
            };
            this.purchasing = {
                purchaseOrderCount: reports.purchasing?.purchase_order_count ?? this.purchasing.purchaseOrderCount,
                goodsReceiptTotal: Number(reports.purchasing?.goods_receipt_total ?? this.purchasing.goodsReceiptTotal),
                openPayableTotal: Number(reports.purchasing?.open_payable_total ?? this.purchasing.openPayableTotal),
                supplierPaymentTotal: Number(reports.purchasing?.supplier_payment_total ?? this.purchasing.supplierPaymentTotal),
            };
            this.paymentMethods = reports.payment_methods?.map((method) => ({
                method: method.method,
                count: method.payment_count ?? 0,
                amount: Number(method.amount ?? 0),
            })) ?? this.paymentMethods;
            this.topProducts = reports.sales_by_product?.map((product) => ({
                product: product.product_name,
                quantity: Number(product.quantity ?? 0),
                total: Number(product.sales_total ?? 0),
            })) ?? this.topProducts;
            this.settlement = {
                count: reports.payment_settlements?.settlement_count ?? this.settlement.count,
                expectedAmount: Number(reports.payment_settlements?.expected_amount ?? this.settlement.expectedAmount),
                reportedAmount: Number(reports.payment_settlements?.reported_amount ?? this.settlement.reportedAmount),
                varianceAmount: Number(reports.payment_settlements?.variance_amount ?? this.settlement.varianceAmount),
            };
            this.accounting = {
                revenue: Number(reports.accounting?.profit_and_loss?.revenue?.total ?? this.accounting.revenue),
                expenses: Number(reports.accounting?.profit_and_loss?.expenses?.total ?? this.accounting.expenses),
                netProfit: Number(reports.accounting?.profit_and_loss?.net_profit ?? this.accounting.netProfit),
                endingCashBalance: Number(reports.accounting?.cash_flow?.ending_cash_balance ?? this.accounting.endingCashBalance),
            };
        },
    },
});
