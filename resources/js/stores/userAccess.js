import { defineStore } from 'pinia';

export const useUserAccessStore = defineStore('userAccess', {
    state: () => ({
        users: [
            { name: 'KAWI Owner', email: 'owner@kawi.test', roles: ['Business Owner'] },
            { name: 'Kasir Demo', email: 'cashier@kawi.test', roles: ['Cashier'] },
        ],
        roles: [
            'Business Owner',
            'Branch Manager',
            'Cashier',
            'Inventory Staff',
            'Purchasing',
            'Accountant',
            'Viewer',
        ],
        permissions: [
            'sales.create',
            'inventory.adjust',
            'reports.view',
            'accounting.manage',
            'users.manage',
        ],
    }),

    getters: {
        userCount: (state) => state.users.length,
        roleCount: (state) => state.roles.length,
        permissionCount: (state) => state.permissions.length,
    },
});
