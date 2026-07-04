import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

export const useMasterDataStore = defineStore('masterData', {
    state: () => ({
        categories: ['Makanan', 'Minuman', 'Kopi'],
        products: [
            { id: null, sku: 'KAWI-RICE-001', name: 'KAWI Rice Bowl', price: 35000, cost: 18000, type: 'food' },
            { id: null, sku: 'KAWI-COFFEE-001', name: 'KAWI Iced Coffee', price: 25000, cost: 9000, type: 'beverage' },
        ],
        suppliers: [{ id: null, name: 'Supplier Bahan Baku KAWI' }],
        modifiers: ['Extra Sambal', 'Extra Shot'],
        taxes: ['PPN 11%'],
    }),

    getters: {
        activeProductCount: (state) => state.products.length,
        categoryCount: (state) => state.categories.length,
    },

    actions: {
        async loadFromApi() {
            const response = await apiGet('/master-data');
            this.categories = response.categories?.map((category) => category.name) ?? this.categories;
            this.suppliers = response.suppliers?.map((supplier) => ({
                id: supplier.id,
                name: supplier.name,
            })) ?? this.suppliers;
            this.products = response.products?.map((product) => ({
                id: product.id,
                sku: product.sku,
                name: product.name,
                price: Number(product.branch_prices?.[0]?.price ?? product.base_price ?? 0),
                cost: Number(product.cost_price ?? 0),
                type: product.type,
            })) ?? this.products;
            this.modifiers = response.modifier_groups?.flatMap((group) => group.modifiers?.map((modifier) => modifier.name) ?? []) ?? this.modifiers;
            this.taxes = response.taxes?.map((tax) => tax.name) ?? this.taxes;
        },
    },
});
