import { defineStore } from 'pinia';

export const useMasterDataStore = defineStore('masterData', {
    state: () => ({
        categories: ['Makanan', 'Minuman', 'Kopi'],
        products: [
            { sku: 'KAWI-RICE-001', name: 'KAWI Rice Bowl', price: 35000, type: 'food' },
            { sku: 'KAWI-COFFEE-001', name: 'KAWI Iced Coffee', price: 25000, type: 'beverage' },
        ],
        modifiers: ['Extra Sambal', 'Extra Shot'],
        taxes: ['PPN 11%'],
    }),

    getters: {
        activeProductCount: (state) => state.products.length,
        categoryCount: (state) => state.categories.length,
    },
});
