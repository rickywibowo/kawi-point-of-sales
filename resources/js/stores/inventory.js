import { defineStore } from 'pinia';
import { apiGet } from '../services/api';

export const useInventoryStore = defineStore('inventory', {
    state: () => ({
        warehouse: 'Gudang Cabang Utama',
        warehouses: [],
        warehouseId: null,
        recipes: [{ id: null, name: 'Recipe KAWI Rice Bowl', cost: 9000 }],
        stockBalances: [
            { productId: null, warehouseId: null, product: 'KAWI Rice Bowl', quantity: 25, unit: 'PCS', value: 450000 },
            { productId: null, warehouseId: null, product: 'KAWI Iced Coffee', quantity: 40, unit: 'PCS', value: 360000 },
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

    actions: {
        async loadFromApi() {
            const response = await apiGet('/inventory');
            this.warehouses = response.warehouses?.map((warehouse) => ({
                id: warehouse.id,
                name: warehouse.name,
                code: warehouse.code,
            })) ?? this.warehouses;
            this.warehouse = this.warehouses[0]?.name ?? this.warehouse;
            this.warehouseId = this.warehouses[0]?.id ?? this.warehouseId;
            this.recipes = response.recipes?.map((recipe) => ({
                id: recipe.id,
                productId: recipe.product_id,
                name: recipe.name,
                cost: Number(recipe.computed_cost ?? 0),
            })) ?? this.recipes;
            this.stockBalances = response.stock_balances?.map((balance) => ({
                productId: balance.product_id,
                warehouseId: balance.warehouse_id,
                product: balance.product?.name ?? 'Unknown product',
                quantity: Number(balance.quantity_on_hand ?? 0),
                unit: balance.product?.unit_of_measure?.code ?? 'PCS',
                value: Number(balance.stock_value ?? 0),
            })) ?? this.stockBalances;
            this.recentMovements = response.stock_ledgers?.map((ledger) => ({
                reference: ledger.reference_number,
                type: ledger.movement_type,
                quantity: Number(ledger.quantity_in ?? 0) + Number(ledger.quantity_out ?? 0),
            })) ?? this.recentMovements;
            this.stockTransfers = response.stock_transfers?.map((transfer) => ({
                number: transfer.transfer_number,
                status: transfer.status,
                quantity: Number(transfer.items_sum_quantity ?? 0),
            })) ?? this.stockTransfers;
            this.stockOpnames = response.stock_opnames?.map((opname) => ({
                number: opname.opname_number,
                status: opname.status,
                variance: 0,
            })) ?? this.stockOpnames;
            this.productionOrders = response.production_orders?.map((order) => ({
                number: order.production_number,
                product: order.product?.name ?? 'Production',
                actualQuantity: Number(order.actual_quantity ?? 0),
                wasteQuantity: Number(order.waste_quantity ?? 0),
            })) ?? this.productionOrders;
        },
    },
});
