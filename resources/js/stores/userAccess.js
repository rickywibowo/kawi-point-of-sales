import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

export const useUserAccessStore = defineStore('userAccess', {
    state: () => ({
        users: [
            { id: null, name: 'KAWI Owner', email: 'owner@kawi.test', roles: ['Business Owner'] },
            { id: null, name: 'Kasir Demo', email: 'cashier@kawi.test', roles: ['Cashier'] },
        ],
        roles: [
            { id: null, name: 'Business Owner' },
            { id: null, name: 'Branch Manager' },
            { id: null, name: 'Cashier' },
            { id: null, name: 'Inventory Staff' },
            { id: null, name: 'Purchasing' },
            { id: null, name: 'Accountant' },
            { id: null, name: 'Viewer' },
        ],
        branches: [],
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
                id: user.id,
                name: user.name,
                email: user.email,
                roles: user.roles?.map((role) => role.name) ?? [],
            })) ?? this.users;
            this.roles = response.roles?.map((role) => ({
                id: role.id,
                name: role.name,
            })) ?? this.roles;
            this.branches = response.branches?.map((branch) => ({
                id: branch.id,
                name: branch.name,
                code: branch.code,
            })) ?? this.branches;
            this.permissions = response.permissions?.map((permission) => permission.name) ?? this.permissions;
        },
    },
});
