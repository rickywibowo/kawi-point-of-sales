import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

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

    actions: {
        async loadFromApi() {
            const response = await apiGet('/user-access');
            this.users = response.users?.map((user) => ({
                name: user.name,
                email: user.email,
                roles: user.roles?.map((role) => role.name) ?? [],
            })) ?? this.users;
            this.roles = response.roles?.map((role) => role.name) ?? this.roles;
            this.permissions = response.permissions?.map((permission) => permission.name) ?? this.permissions;
        },
    },
});
