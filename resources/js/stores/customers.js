import { defineStore } from 'pinia';

export const useCustomersStore = defineStore('customers', {
    state: () => ({
        customers: [
            {
                name: 'Walk-in Customer',
                phone: '080000000001',
                loyaltyPoints: 0,
                lifetimeSpend: 0,
                transactionCount: 0,
            },
            {
                name: 'Member KAWI',
                phone: '081234567899',
                loyaltyPoints: 120,
                lifetimeSpend: 388500,
                transactionCount: 10,
            },
        ],
        selectedCustomer: 'Walk-in Customer',
    }),

    getters: {
        customerCount: (state) => state.customers.length,
        memberCount: (state) => state.customers.filter((customer) => customer.loyaltyPoints > 0).length,
        totalLifetimeSpend: (state) => state.customers.reduce((total, customer) => total + customer.lifetimeSpend, 0),
    },
});
