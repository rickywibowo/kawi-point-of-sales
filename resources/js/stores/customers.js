import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

export const useCustomersStore = defineStore('customers', {
    state: () => ({
        customers: [
            {
                id: null,
                name: 'Walk-in Customer',
                phone: '080000000001',
                loyaltyPoints: 0,
                lifetimeSpend: 0,
                transactionCount: 0,
            },
            {
                id: null,
                name: 'Member KAWI',
                phone: '081234567899',
                loyaltyPoints: 120,
                lifetimeSpend: 388500,
                transactionCount: 10,
            },
        ],
        selectedCustomer: 'Walk-in Customer',
        loyaltyTransactions: [
            { type: 'sale_earn', points: 3, note: 'SALE-DEMO-001' },
            { type: 'manual_bonus', points: 25, note: 'Member opening bonus' },
        ],
    }),

    getters: {
        customerCount: (state) => state.customers.length,
        memberCount: (state) => state.customers.filter((customer) => customer.loyaltyPoints > 0).length,
        totalLifetimeSpend: (state) => state.customers.reduce((total, customer) => total + customer.lifetimeSpend, 0),
        loyaltyPointTotal: (state) => state.customers.reduce((total, customer) => total + customer.loyaltyPoints, 0),
    },

    actions: {
        async loadFromApi() {
            const response = await apiGet('/customers');
            const customerItems = response.customers?.data ?? response.customers ?? [];

            this.customers = customerItems.map((customer) => ({
                id: customer.id,
                name: customer.name,
                phone: customer.phone,
                loyaltyPoints: customer.loyalty_points ?? 0,
                lifetimeSpend: Number(customer.lifetime_spend ?? 0),
                transactionCount: customer.transaction_count ?? 0,
            }));
            this.selectedCustomer = this.customers[0]?.name ?? this.selectedCustomer;
        },
    },
});
