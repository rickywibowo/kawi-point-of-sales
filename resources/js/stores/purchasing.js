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
        payables: [],
    }),

    getters: {
        openOrderCount: (state) => state.purchaseOrders.filter((order) => order.status !== 'closed').length,
        payableTotal: (state) => state.payables.reduce((total, payable) => total + payable.amount, 0),
        returnTotal: (state) => state.purchaseReturns.reduce((total, purchaseReturn) => total + purchaseReturn.total, 0),
    },
});
