import { defineStore } from 'pinia';

export const useFoundationStore = defineStore('foundation', {
    state: () => ({
        cashier: 'KAWI Owner',
        business: 'KAWI Demo Business',
        branch: 'Cabang Utama',
        shift: 'Belum dibuka',
        isOnline: navigator.onLine,
        unsyncedTransactions: 0,
    }),

    actions: {
        setOnlineStatus(isOnline) {
            this.isOnline = isOnline;
        },
    },
});
