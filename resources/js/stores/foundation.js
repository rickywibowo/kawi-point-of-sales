import { defineStore } from 'pinia';
import { ApiError, apiGet, apiPost, setApiToken, setTenantContext } from '../services/api';

export const useFoundationStore = defineStore('foundation', {
    state: () => ({
        cashier: 'KAWI Owner',
        business: 'KAWI Demo Business',
        branch: 'Cabang Utama',
        shift: 'Belum dibuka',
        isOnline: navigator.onLine,
        unsyncedTransactions: 0,
        apiStatus: 'demo',
        apiMessage: 'Demo data aktif',
        user: null,
    }),

    actions: {
        setOnlineStatus(isOnline) {
            this.isOnline = isOnline;
        },

        async login(email = 'owner@kawi.test', password = 'password') {
            const response = await apiPost('/auth/login', {
                email,
                password,
                device_name: 'kawi-dashboard',
            });

            setApiToken(response.token);
            const business = response.user?.businesses?.[0];
            const branch = business?.branches?.[0];

            setTenantContext({
                businessId: business?.uuid,
                branchId: branch?.uuid,
            });

            await this.loadSession();
        },

        async loadSession() {
            try {
                const response = await apiGet('/auth/me');
                this.user = response.user;
                this.cashier = response.user?.name ?? this.cashier;
                this.business = response.business?.name ?? this.business;
                this.branch = response.branch?.name ?? this.branch;
                this.apiStatus = 'connected';
                this.apiMessage = 'API connected';
            } catch (error) {
                this.user = null;
                this.apiStatus = error instanceof ApiError && [401, 403, 422].includes(error.status) ? 'demo' : 'offline';
                this.apiMessage = this.apiStatus === 'demo' ? 'Demo data aktif' : 'API belum tersedia';
            }
        },
    },
});
