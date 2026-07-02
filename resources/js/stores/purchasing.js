import { defineStore } from 'pinia';

export const usePurchasingStore = defineStore('purchasing', {
    state: () => ({
        purchaseOrders: [
            { number: 'PO-SEED-001', supplier: 'Supplier Bahan Baku KAWI', status: 'approved', total: 99900 },
        ],
        goodsReceipts: [],
        purchaseReturns: [
            { number: 'PR-DEMO-001', status: 'posted', total: 18000 },
        ],
        payables: [
            { number: 'AP-GR-DEMO-001', status: 'partial', amount: 99900, paidAmount: 40000 },
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
});
