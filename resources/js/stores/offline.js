import { defineStore } from 'pinia';
import { enqueueSale, listQueuedSales, removeQueuedSale } from '../services/offlineDb';

export const useOfflineStore = defineStore('offline', {
    state: () => ({
        isOnline: navigator.onLine,
        queuedSales: [],
        conflicts: [],
        lastSyncStatus: 'idle',
    }),

    getters: {
        queuedCount: (state) => state.queuedSales.length,
        conflictCount: (state) => state.conflicts.length,
    },

    actions: {
        setOnlineStatus(isOnline) {
            this.isOnline = isOnline;
        },

        async loadQueue() {
            if (!('indexedDB' in window)) {
                return;
            }

            this.queuedSales = await listQueuedSales();
        },

        async queueSale(payload) {
            const sale = {
                client_uuid: crypto.randomUUID(),
                payload,
                status: 'queued',
                queued_at: new Date().toISOString(),
            };

            await enqueueSale(sale);
            await this.loadQueue();
        },

        async markSynced(clientUuid) {
            await removeQueuedSale(clientUuid);
            await this.loadQueue();
        },
    },
});
