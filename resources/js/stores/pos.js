import { defineStore } from 'pinia';

export const usePosStore = defineStore('pos', {
    state: () => ({
        shift: {
            number: 'SHIFT-DEMO',
            status: 'open',
            openingCash: 250000,
        },
        cart: [
            { name: 'KAWI Rice Bowl', quantity: 1, price: 35000 },
            { name: 'KAWI Iced Coffee', quantity: 1, price: 25000 },
        ],
        payments: [
            { method: 'cash', amount: 60000 },
        ],
        heldTransactions: 1,
        todayTransactions: 0,
    }),

    getters: {
        subtotal: (state) => state.cart.reduce((total, item) => total + item.quantity * item.price, 0),
        totalQuantity: (state) => state.cart.reduce((total, item) => total + item.quantity, 0),
    },
});
