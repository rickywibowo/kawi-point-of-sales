import { defineStore } from 'pinia';
import { ApiError, apiGet, apiPost, clearApiSession, setApiToken, setTenantContext } from '../services/api';

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
        isLoadingSession: false,
        loginError: null,
        contexts: [],
    }),

    actions: {
        setOnlineStatus(isOnline) {
            this.isOnline = isOnline;
        },

        async login(email = 'owner@kawi.test', password = 'password') {
            this.loginError = null;
            this.isLoadingSession = true;

            try {
                const response = await apiPost('/auth/login', {
                    email,
                    password,
                    device_name: 'kawi-dashboard',
                });

                setApiToken(response.token);
                const contexts = response.contexts ?? response.user?.businesses ?? [];
                const business = contexts[0];
                const branch = business?.branches?.[0];

                setTenantContext({
                    businessId: business?.uuid,
                    branchId: branch?.uuid,
                });

                await this.loadSession();
            } catch (error) {
                this.loginError = error instanceof ApiError ? error.message : 'Login gagal.';
                this.apiStatus = 'demo';
                this.apiMessage = 'Demo data aktif';

                throw error;
            } finally {
                this.isLoadingSession = false;
            }
        },

        async loadSession() {
            this.isLoadingSession = true;

            try {
                const response = await apiGet('/auth/me');
                this.user = response.user;
                this.contexts = response.contexts ?? [];
                this.cashier = response.user?.name ?? this.cashier;
                this.business = response.business?.name ?? this.business;
                this.branch = response.branch?.name ?? this.branch;
                this.apiStatus = 'connected';
                this.apiMessage = 'API connected';
            } catch (error) {
                this.user = null;
                this.apiStatus = error instanceof ApiError && [401, 403, 422].includes(error.status) ? 'demo' : 'offline';
                this.apiMessage = this.apiStatus === 'demo' ? 'Demo data aktif' : 'API belum tersedia';
            } finally {
                this.isLoadingSession = false;
            }
        },

        async switchContext(businessId, branchId = null) {
            const response = await apiPost('/auth/context', {
                business_id: businessId,
                branch_id: branchId,
            });

            setTenantContext({
                businessId: response.business?.uuid,
                branchId: response.branch?.uuid,
            });

            await this.loadSession();

            return response;
        },

        async logout() {
            try {
                if (this.apiStatus === 'connected') {
                    await apiPost('/auth/logout', {});
                }
            } catch (error) {
                this.loginError = null;
            } finally {
                clearApiSession();
                this.user = null;
                this.apiStatus = 'demo';
                this.apiMessage = 'Demo data aktif';
                this.cashier = 'KAWI Owner';
                this.business = 'KAWI Demo Business';
                this.branch = 'Cabang Utama';
            }
        },
    },
});
