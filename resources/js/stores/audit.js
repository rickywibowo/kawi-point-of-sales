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
        auditLogs: [
            { id: null, action: 'sale.completed', actor: 'KAWI Owner', branch: 'MAIN', entity: 'Sale', occurredAt: null },
            { id: null, action: 'role.assigned', actor: 'KAWI Owner', branch: 'MAIN', entity: 'User', occurredAt: null },
        ],
        pagination: {
            currentPage: 1,
            perPage: 25,
            total: 18,
        },
    }),

    getters: {
        topActionLabel: (state) => state.topActions[0]?.action ?? 'none',
        securityEventCount: (state) => state.securityEvents.length,
        recentAuditCount: (state) => state.auditLogs.length,
    },

    actions: {
        async loadFromApi() {
            const response = await apiGet('/audit-logs');
            this.totalEvents = response.summary?.total_events ?? this.totalEvents;
            this.uniqueUsers = response.summary?.unique_users ?? this.uniqueUsers;
            this.topActions = response.summary?.actions ?? this.topActions;
            this.securityEvents = response.summary?.recent_security_events?.map((event) => ({
                action: event.action,
                actor: event.user?.name ?? 'System',
            })) ?? this.securityEvents;
            this.auditLogs = response.audit_logs?.data?.map((log) => ({
                id: log.id,
                action: log.action,
                actor: log.user?.name ?? 'System',
                branch: log.branch?.code ?? 'Business',
                entity: log.entity_type?.split('\\').pop() ?? 'General',
                occurredAt: log.created_at,
            })) ?? this.auditLogs;
            this.pagination = response.audit_logs
                ? {
                    currentPage: response.audit_logs.current_page,
                    perPage: response.audit_logs.per_page,
                    total: response.audit_logs.total,
                }
                : this.pagination;
        },
    },
});
