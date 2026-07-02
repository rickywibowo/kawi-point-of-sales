import { defineStore } from 'pinia';

export const useAuditStore = defineStore('audit', {
    state: () => ({
        totalEvents: 18,
        uniqueUsers: 2,
        topActions: [
            { action: 'sale.completed', total: 5 },
            { action: 'journal.posted', total: 4 },
            { action: 'stock_adjustment.posted', total: 2 },
        ],
        securityEvents: [
            { action: 'role.assigned', actor: 'KAWI Owner' },
            { action: 'sale.voided', actor: 'Branch Manager' },
        ],
    }),

    getters: {
        topActionLabel: (state) => state.topActions[0]?.action ?? 'none',
        securityEventCount: (state) => state.securityEvents.length,
    },
});
