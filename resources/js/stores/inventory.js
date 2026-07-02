import { defineStore } from 'pinia';

export const useInventoryStore = defineStore('inventory', {
    state: () => ({
        warehouse: 'Gudang Cabang Utama',
        recipes: [{ name: 'Recipe KAWI Rice Bowl', cost: 9000 }],
        stockBalances: [
            { product: 'KAWI Rice Bowl', quantity: 25, unit: 'PCS', value: 450000 },
            { product: 'KAWI Iced Coffee', quantity: 40, unit: 'PCS', value: 360000 },
        ],
        recentMovements: [
            { reference: 'OPENING-STOCK', type: 'opening_balance', quantity: 65 },
        ],
        stockTransfers: [
            { number: 'TRF-DEMO-001', status: 'posted', quantity: 5 },
        ],
        stockOpnames: [
            { number: 'OPN-DEMO-001', status: 'posted', variance: -2 },
        ],
        productionOrders: [
            { number: 'PROD-DEMO-001', product: 'KAWI Rice Bowl', actualQuantity: 10, wasteQuantity: 1 },
        ],
    }),

    getters: {
        totalStockValue: (state) => state.stockBalances.reduce((total, item) => total + item.value, 0),
        lowStockCount: (state) => state.stockBalances.filter((item) => item.quantity <= 5).length,
        controlDocumentCount: (state) => state.stockTransfers.length + state.stockOpnames.length,
        productionCount: (state) => state.productionOrders.length,
    },
});
