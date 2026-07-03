import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

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

    actions: {
        async loadFromApi() {
            const response = await apiGet('/audit-logs');
            this.totalEvents = response.summary?.total_events ?? this.totalEvents;
            this.uniqueUsers = response.summary?.unique_users ?? this.uniqueUsers;
            this.topActions = response.summary?.top_actions ?? this.topActions;
            this.securityEvents = response.security_events?.map((event) => ({
                action: event.action,
                actor: event.user?.name ?? 'System',
            })) ?? this.securityEvents;
        },
    },
});
