import { defineStore } from 'pinia';

export const usePurchasingStore = defineStore('purchasing', {
    state: () => ({
        purchaseOrders: [
            { number: 'PO-SEED-001', supplier: 'Supplier Bahan Baku KAWI', status: 'approved', total: 99900 },
        ],
        goodsReceipts: [],
        payables: [],
    }),

    getters: {
        openOrderCount: (state) => state.purchaseOrders.filter((order) => order.status !== 'closed').length,
        payableTotal: (state) => state.payables.reduce((total, payable) => total + payable.amount, 0),
    },
});
