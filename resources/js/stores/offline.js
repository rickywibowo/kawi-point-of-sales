import { defineStore } from 'pinia';
import { apiGet } from '../services/api';
import { enqueueSale, listQueuedSales, removeQueuedSale } from '../services/offlineDb';

export const useOfflineStore = defineStore('offline', {
    state: () => ({
        isOnline: navigator.onLine,
        queuedSales: [],
        conflicts: [],
        lastSyncStatus: 'idle',
        lastConflictCheckAt: null,
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

        async loadConflicts() {
            const response = await apiGet('/offline/conflicts');
            this.conflicts = response.conflicts?.map((conflict) => ({
                id: conflict.id,
                clientUuid: conflict.client_uuid,
                status: conflict.status,
                reason: conflict.reason,
                createdAt: conflict.created_at,
            })) ?? this.conflicts;
            this.lastConflictCheckAt = new Date().toISOString();
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
