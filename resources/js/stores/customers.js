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
        customerProfile: {
            id: null,
            name: 'Walk-in Customer',
            transactionCount: 0,
            lifetimeSpend: 0,
            averageOrderValue: 0,
            lastPurchaseAt: null,
            receivableBalance: 0,
            loyaltyPoints: 0,
        },
        recentSales: [
            { id: null, number: 'SALE-DEMO-001', status: 'completed', total: 60000, soldAt: null },
        ],
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

            if (this.customers[0]?.id) {
                await this.loadProfile(this.customers[0].id);
            }
        },

        async loadProfile(customerId) {
            const response = await apiGet(`/customers/${customerId}`);
            const summary = response.summary ?? {};

            this.customerProfile = {
                id: response.customer?.id,
                name: response.customer?.name ?? this.selectedCustomer,
                transactionCount: summary.transaction_count ?? 0,
                lifetimeSpend: Number(summary.lifetime_spend ?? 0),
                averageOrderValue: Number(summary.average_order_value ?? 0),
                lastPurchaseAt: summary.last_purchase_at,
                receivableBalance: Number(summary.receivable_balance ?? 0),
                loyaltyPoints: summary.loyalty_points ?? 0,
            };
            this.recentSales = response.recent_sales?.map((sale) => ({
                id: sale.id,
                number: sale.sale_number,
                status: sale.status,
                total: Number(sale.grand_total ?? 0),
                soldAt: sale.sold_at,
            })) ?? this.recentSales;
            this.loyaltyTransactions = response.loyalty_transactions?.map((transaction) => ({
                type: transaction.type,
                points: transaction.points_delta,
                note: transaction.notes,
            })) ?? this.loyaltyTransactions;
        },
    },
});
