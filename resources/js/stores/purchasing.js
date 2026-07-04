import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

export const usePurchasingStore = defineStore('purchasing', {
    state: () => ({
        purchaseOrders: [
            { id: null, number: 'PO-SEED-001', supplier: 'Supplier Bahan Baku KAWI', status: 'approved', total: 99900 },
        ],
        goodsReceipts: [
            { id: null, supplierId: null, number: 'GR-DEMO-001', supplier: 'Supplier Bahan Baku KAWI', status: 'posted', total: 99900, firstItem: null },
        ],
        purchaseReturns: [
            { number: 'PR-DEMO-001', status: 'posted', total: 18000 },
        ],
        payables: [
            { id: null, number: 'AP-GR-DEMO-001', status: 'partial', amount: 99900, paidAmount: 40000 },
        ],
        supplierPayments: [
            { number: 'PAY-DEMO-001', supplier: 'Supplier Bahan Baku KAWI', amount: 40000 },
        ],
    }),

    getters: {
        openOrderCount: (state) => state.purchaseOrders.filter((order) => order.status !== 'closed').length,
        payableTotal: (state) => state.payables.reduce((total, payable) => total + payable.amount, 0),
        payableRemaining: (state) => state.payables.reduce((total, payable) => total + payable.amount - payable.paidAmount, 0),
        supplierPaymentTotal: (state) => state.supplierPayments.reduce((total, payment) => total + payment.amount, 0),
        returnTotal: (state) => state.purchaseReturns.reduce((total, purchaseReturn) => total + purchaseReturn.total, 0),
    },

    actions: {
        async loadFromApi() {
            const response = await apiGet('/purchasing');
            this.purchaseOrders = response.purchase_orders?.map((order) => ({
                id: order.id,
                number: order.order_number,
                supplier: order.supplier?.name,
                status: order.status,
                total: Number(order.grand_total ?? 0),
            })) ?? this.purchaseOrders;
            this.goodsReceipts = response.goods_receipts?.map((receipt) => ({
                id: receipt.id,
                supplierId: receipt.supplier_id,
                number: receipt.receipt_number,
                supplier: receipt.supplier?.name,
                status: receipt.status,
                total: Number(receipt.grand_total ?? 0),
                firstItem: receipt.items?.[0] ? {
                    id: receipt.items[0].id,
                    productId: receipt.items[0].product_id,
                    quantity: Number(receipt.items[0].quantity_received ?? 0),
                    unitCost: Number(receipt.items[0].unit_cost ?? 0),
                    taxRate: Number(receipt.items[0].tax_rate ?? 0),
                } : null,
            })) ?? this.goodsReceipts;
            this.purchaseReturns = response.purchase_returns?.map((purchaseReturn) => ({
                id: purchaseReturn.id,
                number: purchaseReturn.return_number,
                status: purchaseReturn.status,
                total: Number(purchaseReturn.grand_total ?? 0),
            })) ?? this.purchaseReturns;
            this.payables = response.supplier_payables?.map((payable) => ({
                id: payable.id,
                number: payable.payable_number,
                status: payable.status,
                amount: Number(payable.amount ?? 0),
                paidAmount: Number(payable.paid_amount ?? 0),
            })) ?? this.payables;
            this.supplierPayments = response.supplier_payments?.map((payment) => ({
                number: payment.payment_number,
                supplier: payment.supplier?.name,
                amount: Number(payment.amount ?? 0),
            })) ?? this.supplierPayments;
        },
    },
});
