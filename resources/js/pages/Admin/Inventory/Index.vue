<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Package, Plus, Search, Upload, Trash2, Edit, Filter, 
    Download, AlertTriangle, RefreshCw, FileText,
    Building2, CheckCircle2, ArrowUpRight, ArrowDownRight,
    Tag, Inbox, Sparkles, ShieldCheck, X, Box, Layers, RotateCcw
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { formatCurrency, formatCompactCurrency } from '@/lib/utils';

interface InventoryItemData {
    id: number;
    name: string;
    sku: string;
    description?: string;
    inventory_category_id: number;
    category?: { id: number; name: string };
    total_quantity: number;
    available_quantity: number;
    min_stock_level: number;
    unit_of_measure: string;
    unit_cost?: number;
    item_type: 'consumable' | 'reusable';
    supplier_name?: string;
    location?: string;
    condition: string;
}

const props = defineProps<{
    items: {
        data: InventoryItemData[];
        links: Array<any>;
        total: number;
    };
    categories: Array<{ 
        id: number; 
        name: string; 
        description?: string;
        items_count?: number;
    }>;
    buildings?: Array<{
        id: string;
        name: string;
        code: string;
        building_type: string;
    }>;
    filters?: {
        search?: string;
        category_id?: string;
        stock_status?: string;
    };
    stats?: {
        total_items: number;
        available_items: number;
        assigned_items: number;
        categories_count: number;
        low_stock_count: number;
        total_valuation: number;
        pending_requisitions: number;
        pending_complaints: number;
    };
    permissions?: {
        can_manage: boolean;
        can_create: boolean;
        can_edit: boolean;
        can_delete: boolean;
        can_restock: boolean;
        can_create_requisition: boolean;
        can_approve_requisition: boolean;
    };
}>();

const searchQuery = ref(props.filters?.search || '');
const categoryFilter = ref(props.filters?.category_id || 'ALL');
const stockStatusFilter = ref(props.filters?.stock_status || 'ALL');

const handleSearch = () => {
    router.get(
        route('admin.inventory.index'),
        {
            search: searchQuery.value,
            category_id: categoryFilter.value,
            stock_status: stockStatusFilter.value === 'ALL' ? undefined : stockStatusFilter.value,
        },
        { preserveState: true, replace: true }
    );
};

const filterByQuickStatus = (status: string) => {
    stockStatusFilter.value = status;
    handleSearch();
};

const resetFilters = () => {
    searchQuery.value = '';
    categoryFilter.value = 'ALL';
    stockStatusFilter.value = 'ALL';
    handleSearch();
};

// MODALS STATE
const showAddModal = ref(false);
const showEditModal = ref(false);
const showRestockModal = ref(false);
const showDeployModal = ref(false);
const showImportModal = ref(false);

const selectedItem = ref<InventoryItemData | null>(null);

// ADD / EDIT FORM
const itemForm = useForm({
    id: null as number | null,
    inventory_category_id: props.categories && props.categories.length > 0 ? props.categories[0].id : '',
    name: '',
    sku: '',
    description: '',
    total_quantity: 1,
    min_stock_level: 5,
    unit_of_measure: 'pieces',
    unit_cost: 0,
    item_type: 'consumable' as 'consumable' | 'reusable',
    condition: 'new',
    supplier_name: '',
    location: '',
});

const openAddModal = () => {
    itemForm.reset();
    itemForm.id = null;
    if (props.categories && props.categories.length > 0) {
        itemForm.inventory_category_id = props.categories[0].id;
    }
    itemForm.condition = 'new';
    itemForm.item_type = 'consumable';
    itemForm.unit_of_measure = 'pieces';
    itemForm.min_stock_level = 5;
    showAddModal.value = true;
};

const openEditModal = (item: InventoryItemData) => {
    selectedItem.value = item;
    itemForm.id = item.id;
    itemForm.inventory_category_id = item.inventory_category_id;
    itemForm.name = item.name;
    itemForm.sku = item.sku || '';
    itemForm.description = item.description || '';
    itemForm.total_quantity = item.total_quantity;
    itemForm.min_stock_level = item.min_stock_level || 5;
    itemForm.unit_of_measure = item.unit_of_measure || 'pieces';
    itemForm.unit_cost = item.unit_cost || 0;
    itemForm.item_type = item.item_type || 'consumable';
    itemForm.condition = item.condition || 'good';
    itemForm.supplier_name = item.supplier_name || '';
    itemForm.location = item.location || '';
    showEditModal.value = true;
};

const submitAdd = () => {
    itemForm.post(route('admin.inventory.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            itemForm.reset();
        },
    });
};

const submitEdit = () => {
    if (!itemForm.id) return;
    itemForm.put(route('admin.inventory.update', itemForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
            selectedItem.value = null;
        },
    });
};

const deleteItem = (item: InventoryItemData) => {
    Swal.fire({
        title: 'Delete Inventory Item?',
        text: `Are you sure you want to delete "${item.name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete Item',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.inventory.destroy', item.id));
        }
    });
};

// RESTOCK FORM
const restockForm = useForm({
    quantity: 1,
    unit_cost: 0,
    supplier_name: '',
    notes: '',
});

const openRestockModal = (item: InventoryItemData) => {
    selectedItem.value = item;
    restockForm.quantity = 1;
    restockForm.unit_cost = item.unit_cost || 0;
    restockForm.supplier_name = item.supplier_name || '';
    restockForm.notes = '';
    showRestockModal.value = true;
};

const submitRestock = () => {
    if (!selectedItem.value) return;
    restockForm.post(route('admin.inventory.restock', selectedItem.value.id), {
        onSuccess: () => {
            showRestockModal.value = false;
            selectedItem.value = null;
            restockForm.reset();
        },
    });
};

// DEPLOY TO BUILDING FORM
const deployForm = useForm({
    inventory_item_id: null as number | null,
    building_id: props.buildings && props.buildings.length > 0 ? props.buildings[0].id : '',
    quantity: 1,
    location_details: '',
    expected_return_date: '',
    condition_on_assignment: 'good',
});

const openDeployModal = (item: InventoryItemData) => {
    selectedItem.value = item;
    deployForm.inventory_item_id = item.id;
    if (props.buildings && props.buildings.length > 0 && !deployForm.building_id) {
        deployForm.building_id = props.buildings[0].id;
    }
    deployForm.quantity = 1;
    deployForm.location_details = '';
    deployForm.expected_return_date = '';
    deployForm.condition_on_assignment = item.condition || 'good';
    showDeployModal.value = true;
};

const submitDeploy = () => {
    deployForm.post(route('admin.inventory.assignments.store'), {
        onSuccess: () => {
            showDeployModal.value = false;
            selectedItem.value = null;
            deployForm.reset();
        },
    });
};

// IMPORT FORM
const importForm = useForm({
    file: null as File | null,
});

const handleFileUpload = (e: any) => {
    const file = e.target.files[0];
    if (file) {
        importForm.file = file;
    }
};

const submitImport = () => {
    if (!importForm.file) return;
    importForm.post(route('admin.inventory.import'), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Central Store Inventory Directory" />

    <AdminLayout>
        <div class="py-6 sm:py-8 min-h-screen bg-slate-50/60 dark:bg-slate-900/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                
                <!-- Sub Navigation Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500">
                    <Link :href="route('admin.inventory.index')" class="px-3.5 py-1.5 rounded-lg bg-indigo-600 text-white font-extrabold shadow-sm transition-all">
                        Inventory Catalog
                    </Link>
                    <Link :href="route('admin.inventory.assignments.index')" class="px-3.5 py-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition-all flex items-center gap-1.5">
                        <Building2 class="w-3.5 h-3.5" />
                        Building Deployments
                    </Link>
                    <Link :href="route('admin.inventory.requisitions.index')" class="px-3.5 py-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition-all flex items-center gap-1.5">
                        <FileText class="w-3.5 h-3.5" />
                        Store Requisitions (SIV)
                    </Link>
                    <Link :href="route('admin.inventory.categories.index')" class="px-3.5 py-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                        Categories
                    </Link>
                    <Link :href="route('admin.inventory.audit-logs.index')" class="px-3.5 py-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                        Audit Logs
                    </Link>
                </div>

                <!-- Hero Section -->
                <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-slate-800">
                    <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="space-y-2 max-w-2xl">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold">
                                <Package class="w-3.5 h-3.5 text-indigo-400" />
                                Central Store & Asset Custody
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                                Store Inventory Directory
                            </h1>
                            <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
                                Complete catalog of all university physical supplies, laboratory equipment, computers, and assets. Restock, deploy to campus buildings, or adjust inventory instantly.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                            <a
                                :href="route('admin.inventory.export')"
                                class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-200 bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 shadow-sm transition-all gap-1.5"
                            >
                                <Download class="w-4 h-4" />
                                Export Excel
                            </a>
                            <button
                                v-if="permissions?.can_create"
                                @click="showImportModal = true"
                                class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-200 bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 shadow-sm transition-all gap-1.5"
                            >
                                <Upload class="w-4 h-4" />
                                Import
                            </button>
                            <button
                                v-if="permissions?.can_create"
                                @click="openAddModal"
                                class="inline-flex items-center px-5 py-2.5 rounded-xl text-xs font-black text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 transition-all gap-2 active:scale-95"
                            >
                                <Plus class="w-4 h-4" />
                                Add Store Item
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Stock Items</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ stats?.total_items ?? 0 }}</p>
                        <p class="text-[11px] text-slate-400 mt-1">{{ props.items.total }} unique catalog types</p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">In Store (Available)</p>
                        <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ stats?.available_items ?? 0 }}</p>
                        <p class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-1 font-semibold">Ready for deployment</p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Deployed in Buildings</p>
                        <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ stats?.assigned_items ?? 0 }}</p>
                        <p class="text-[11px] text-indigo-600/80 dark:text-indigo-400/80 mt-1 font-semibold">In active campus use</p>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Low Stock Alerts</p>
                        <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ stats?.low_stock_count ?? 0 }}</p>
                        <p class="text-[11px] text-amber-600/80 dark:text-amber-400/80 mt-1 font-semibold">Below min reorder level</p>
                    </div>

                    <div class="col-span-2 lg:col-span-1 bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Stock Value</p>
                        <p class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 cursor-help" :title="formatCurrency(stats?.total_valuation || 0)">
                            {{ formatCompactCurrency(stats?.total_valuation || 0) }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1">Available valuation</p>
                    </div>
                </div>

                <!-- Filters & Search Toolbar -->
                <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 space-y-3">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-3">
                        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-1">
                            <!-- Search -->
                            <div class="relative w-full sm:w-72">
                                <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input
                                    v-model="searchQuery"
                                    @keyup.enter="handleSearch"
                                    type="text"
                                    placeholder="Search item name, SKU, supplier..."
                                    class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all"
                                />
                            </div>

                            <!-- Category Filter -->
                            <select
                                v-model="categoryFilter"
                                @change="handleSearch"
                                class="w-full sm:w-52 px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all"
                            >
                                <option value="ALL">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }} ({{ cat.items_count ?? 0 }})
                                </option>
                            </select>

                            <!-- Stock Status Filter -->
                            <select
                                v-model="stockStatusFilter"
                                @change="handleSearch"
                                class="w-full sm:w-44 px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all"
                            >
                                <option value="ALL">All Stock Statuses</option>
                                <option value="in_stock">In Stock (Healthy)</option>
                                <option value="low">Low Stock Alerts</option>
                                <option value="out">Depleted (0 available)</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 self-end md:self-center">
                            <button
                                v-if="searchQuery || categoryFilter !== 'ALL' || stockStatusFilter !== 'ALL'"
                                @click="resetFilters"
                                class="px-3 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white"
                            >
                                Reset
                            </button>
                            <button
                                @click="handleSearch"
                                class="px-4 py-2 bg-slate-900 text-white dark:bg-slate-700 dark:hover:bg-slate-600 rounded-xl text-xs font-bold transition-all"
                            >
                                Filter Catalog
                            </button>
                        </div>
                    </div>

                    <!-- Quick Status Pills -->
                    <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-700/60 overflow-x-auto text-[11px] font-bold">
                        <span class="text-slate-400 shrink-0">Quick Filter:</span>
                        <button
                            @click="filterByQuickStatus('ALL')"
                            class="px-3 py-1 rounded-lg transition-all shrink-0"
                            :class="stockStatusFilter === 'ALL' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 font-extrabold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        >
                            All ({{ props.items.total }})
                        </button>
                        <button
                            @click="filterByQuickStatus('low')"
                            class="px-3 py-1 rounded-lg transition-all shrink-0 flex items-center gap-1"
                            :class="stockStatusFilter === 'low' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 font-extrabold' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        >
                            <AlertTriangle class="w-3 h-3 text-amber-500" />
                            Low Stock ({{ stats?.low_stock_count ?? 0 }})
                        </button>
                        <button
                            @click="filterByQuickStatus('out')"
                            class="px-3 py-1 rounded-lg transition-all shrink-0 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                            :class="stockStatusFilter === 'out' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 font-extrabold' : ''"
                        >
                            Depleted
                        </button>
                    </div>
                </div>

                <!-- Catalog Items Table -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200/80 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-[11px] font-extrabold uppercase tracking-wider">
                                    <th class="px-6 py-4">Item & SKU</th>
                                    <th class="px-6 py-4">Category</th>
                                    <th class="px-6 py-4">Stock Levels</th>
                                    <th class="px-6 py-4">Unit Cost</th>
                                    <th class="px-6 py-4">Type & Condition</th>
                                    <th class="px-6 py-4 text-right">Quick Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50 text-xs">
                                <tr 
                                    v-for="item in items.data" 
                                    :key="item.id"
                                    class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30 transition-colors"
                                >
                                    <!-- Item Name & SKU -->
                                    <td class="px-6 py-4">
                                        <div class="font-extrabold text-slate-900 dark:text-white text-sm">
                                            {{ item.name }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[11px] text-slate-400 font-mono">
                                                SKU: {{ item.sku || 'N/A' }}
                                            </span>
                                            <span v-if="item.supplier_name" class="text-[10px] text-slate-400">
                                                • {{ item.supplier_name }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Category -->
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-700/60 text-slate-800 dark:text-slate-200">
                                            {{ item.category?.name || 'Uncategorized' }}
                                        </span>
                                    </td>

                                    <!-- Stock Levels & Health Badge -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span 
                                                class="font-black text-sm"
                                                :class="{
                                                    'text-rose-600 dark:text-rose-400': item.available_quantity <= 0,
                                                    'text-amber-600 dark:text-amber-400': item.available_quantity > 0 && item.available_quantity <= item.min_stock_level,
                                                    'text-emerald-600 dark:text-emerald-400': item.available_quantity > item.min_stock_level,
                                                }"
                                            >
                                                {{ item.available_quantity }}
                                            </span>
                                            <span class="text-slate-400 font-medium">/ {{ item.total_quantity }} {{ item.unit_of_measure }}</span>
                                        </div>

                                        <!-- Stock bar indicator -->
                                        <div class="w-32 bg-slate-100 dark:bg-slate-700 h-1.5 rounded-full mt-1.5 overflow-hidden">
                                            <div 
                                                class="h-full rounded-full transition-all"
                                                :class="{
                                                    'bg-rose-500': item.available_quantity <= 0,
                                                    'bg-amber-500': item.available_quantity > 0 && item.available_quantity <= item.min_stock_level,
                                                    'bg-emerald-500': item.available_quantity > item.min_stock_level,
                                                }"
                                                :style="{ width: `${Math.min(100, Math.max(0, (item.available_quantity / (item.total_quantity || 1)) * 100))}%` }"
                                            ></div>
                                        </div>

                                        <div v-if="item.available_quantity <= item.min_stock_level" class="text-[10px] text-amber-600 dark:text-amber-400 font-bold mt-1 flex items-center gap-1">
                                            <AlertTriangle class="w-3 h-3 shrink-0" />
                                            Low stock alert (min: {{ item.min_stock_level }})
                                        </div>
                                    </td>

                                    <!-- Unit Cost & Valuation -->
                                    <td class="px-6 py-4">
                                        <div class="font-extrabold text-slate-800 dark:text-slate-200">
                                            {{ formatCurrency(item.unit_cost || 0) }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            Total: {{ formatCurrency((item.unit_cost || 0) * item.available_quantity) }}
                                        </div>
                                    </td>

                                    <!-- Type & Condition -->
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-700 dark:text-slate-300 capitalize">
                                            {{ item.item_type }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 capitalize mt-0.5">
                                            Condition: {{ item.condition }}
                                        </div>
                                    </td>

                                    <!-- Quick Actions -->
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Quick Deploy to Building -->
                                            <button
                                                v-if="permissions?.can_manage && item.available_quantity > 0"
                                                @click="openDeployModal(item)"
                                                title="Deploy units to a campus building"
                                                class="px-2.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-bold transition-all inline-flex items-center gap-1"
                                            >
                                                <Building2 class="w-3.5 h-3.5" />
                                                Deploy
                                            </button>

                                            <!-- Quick Restock -->
                                            <button
                                                v-if="permissions?.can_restock"
                                                @click="openRestockModal(item)"
                                                title="Quick Restock Units"
                                                class="px-2.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 font-bold transition-all inline-flex items-center gap-1"
                                            >
                                                <Plus class="w-3.5 h-3.5" />
                                                Restock
                                            </button>

                                            <!-- Edit -->
                                            <button
                                                v-if="permissions?.can_edit"
                                                @click="openEditModal(item)"
                                                title="Edit Item Details"
                                                class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all"
                                            >
                                                <Edit class="w-3.5 h-3.5" />
                                            </button>

                                            <!-- Delete -->
                                            <button
                                                v-if="permissions?.can_delete"
                                                @click="deleteItem(item)"
                                                title="Delete Item"
                                                class="p-1.5 rounded-xl text-rose-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-all"
                                            >
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="items.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                        <Inbox class="w-10 h-10 mx-auto mb-2 opacity-40 text-slate-400" />
                                        <p class="font-extrabold text-sm text-slate-600 dark:text-slate-300">No store items found</p>
                                        <p class="text-xs text-slate-400 mt-1">Try adjusting your search criteria or add new items to the central inventory catalog.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="items.links && items.links.length > 3" class="px-6 py-4 border-t border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between">
                        <div class="text-xs text-slate-500">
                            Showing {{ items.data.length }} of {{ items.total }} items
                        </div>
                        <div class="flex gap-1">
                            <Component
                                :is="link.url ? Link : 'span'"
                                v-for="(link, index) in items.links"
                                :key="index"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                                :class="{
                                    'bg-indigo-600 text-white': link.active,
                                    'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700': link.url && !link.active,
                                    'text-slate-300 dark:text-slate-600 cursor-not-allowed': !link.url
                                }"
                            />
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL: ADD NEW ITEM -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                            <Package class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Add New Store Item</h3>
                            <p class="text-xs text-slate-500">Register new equipment or consumables to store catalog</p>
                        </div>
                    </div>
                    <button @click="showAddModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Item Name *</label>
                            <input
                                v-model="itemForm.name"
                                type="text"
                                required
                                placeholder="e.g. Dell OptiPlex 7090 Desktop"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category *</label>
                            <select
                                v-model="itemForm.inventory_category_id"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            >
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">SKU / Item Code</label>
                            <input
                                v-model="itemForm.sku"
                                type="text"
                                placeholder="e.g. IT-COMP-001"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Initial Quantity *</label>
                            <input
                                v-model.number="itemForm.total_quantity"
                                type="number"
                                min="0"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Min Alert Level *</label>
                            <input
                                v-model.number="itemForm.min_stock_level"
                                type="number"
                                min="0"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Unit of Measure *</label>
                            <input
                                v-model="itemForm.unit_of_measure"
                                type="text"
                                required
                                placeholder="e.g. pieces, sets, reams, packs"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Unit Cost (₦)</label>
                            <input
                                v-model.number="itemForm.unit_cost"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Item Type *</label>
                            <select
                                v-model="itemForm.item_type"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            >
                                <option value="reusable">Reusable (Computers, Furniture, Equipment)</option>
                                <option value="consumable">Consumable (Paper, Stationery, Toner)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Condition</label>
                            <select
                                v-model="itemForm.condition"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white capitalize"
                            >
                                <option value="new">Brand New</option>
                                <option value="good">Good</option>
                                <option value="fair">Fair</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Supplier Name</label>
                            <input
                                v-model="itemForm.supplier_name"
                                type="text"
                                placeholder="e.g. Zenith Tech Ltd"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Store Shelf / Rack</label>
                            <input
                                v-model="itemForm.location"
                                type="text"
                                placeholder="e.g. Shelf A-3, Bin 12"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Description / Specifications</label>
                            <textarea
                                v-model="itemForm.description"
                                rows="2"
                                placeholder="Optional notes or technical specifications..."
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            ></textarea>
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="itemForm.processing"
                            class="px-6 py-2.5 rounded-xl text-xs font-black text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition-all disabled:opacity-50"
                        >
                            {{ itemForm.processing ? 'Saving...' : 'Add Item to Store' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: EDIT ITEM -->
        <div v-if="showEditModal && selectedItem" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                            <Edit class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Edit Store Item</h3>
                            <p class="text-xs text-slate-500">Update specifications and parameters for {{ selectedItem.name }}</p>
                        </div>
                    </div>
                    <button @click="showEditModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Item Name *</label>
                            <input
                                v-model="itemForm.name"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Category *</label>
                            <select
                                v-model="itemForm.inventory_category_id"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            >
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">SKU / Item Code</label>
                            <input
                                v-model="itemForm.sku"
                                type="text"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-mono focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Min Alert Level *</label>
                            <input
                                v-model.number="itemForm.min_stock_level"
                                type="number"
                                min="0"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Unit of Measure *</label>
                            <input
                                v-model="itemForm.unit_of_measure"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Unit Cost (₦)</label>
                            <input
                                v-model.number="itemForm.unit_cost"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Condition</label>
                            <select
                                v-model="itemForm.condition"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white capitalize"
                            >
                                <option value="new">Brand New</option>
                                <option value="good">Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="itemForm.processing"
                            class="px-6 py-2.5 rounded-xl text-xs font-black text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition-all disabled:opacity-50"
                        >
                            {{ itemForm.processing ? 'Saving...' : 'Update Item' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: QUICK RESTOCK -->
        <div v-if="showRestockModal && selectedItem" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold">
                            <Plus class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Quick Restock Units</h3>
                            <p class="text-xs text-slate-500">Replenish store inventory for {{ selectedItem.name }}</p>
                        </div>
                    </div>
                    <button @click="showRestockModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Current Available:</span>
                        <span class="font-black text-slate-900 dark:text-white">{{ selectedItem.available_quantity }} {{ selectedItem.unit_of_measure }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-slate-500">Total in System:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ selectedItem.total_quantity }} {{ selectedItem.unit_of_measure }}</span>
                    </div>
                </div>

                <form @submit.prevent="submitRestock" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Additional Quantity to Add *</label>
                        <input
                            v-model.number="restockForm.quantity"
                            type="number"
                            min="1"
                            required
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Unit Cost (₦)</label>
                        <input
                            v-model.number="restockForm.unit_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Supplier / Vendor</label>
                        <input
                            v-model="restockForm.supplier_name"
                            type="text"
                            placeholder="Supplier name..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Restock Notes / PO Ref</label>
                        <input
                            v-model="restockForm.notes"
                            type="text"
                            placeholder="e.g. PO-2026-04 Batch delivery"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                        />
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showRestockModal = false"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="restockForm.processing"
                            class="px-6 py-2.5 rounded-xl text-xs font-black text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-600/30 transition-all disabled:opacity-50"
                        >
                            {{ restockForm.processing ? 'Adding Stock...' : 'Confirm Restock' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: QUICK DEPLOY TO BUILDING -->
        <div v-if="showDeployModal && selectedItem" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                            <Building2 class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Deploy to Campus Building</h3>
                            <p class="text-xs text-slate-500">Deploy {{ selectedItem.name }} to a university facility</p>
                        </div>
                    </div>
                    <button @click="showDeployModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-xs flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white">{{ selectedItem.name }}</p>
                        <p class="text-[11px] text-slate-400 font-mono mt-0.5">SKU: {{ selectedItem.sku || 'N/A' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400">Available:</span>
                        <span class="font-black text-indigo-600 dark:text-indigo-400 text-sm ml-1">
                            {{ selectedItem.available_quantity }} {{ selectedItem.unit_of_measure }}
                        </span>
                    </div>
                </div>

                <form @submit.prevent="submitDeploy" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Campus Building *</label>
                        <select
                            v-model="deployForm.building_id"
                            required
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                        >
                            <option v-for="bldg in buildings" :key="bldg.id" :value="bldg.id">
                                [{{ bldg.code }}] {{ bldg.name }} ({{ bldg.building_type.replace('_', ' ') }})
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Room / Location Details</label>
                            <input
                                v-model="deployForm.location_details"
                                type="text"
                                placeholder="e.g. Room 204, Lab B, Stage"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Quantity to Deploy *</label>
                            <input
                                v-model.number="deployForm.quantity"
                                type="number"
                                min="1"
                                :max="selectedItem.available_quantity"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white font-bold"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Condition</label>
                            <select
                                v-model="deployForm.condition_on_assignment"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white capitalize"
                            >
                                <option value="new">Brand New</option>
                                <option value="good">Good / Functional</option>
                                <option value="fair">Fair</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Expected Return Date</label>
                            <input
                                v-model="deployForm.expected_return_date"
                                type="date"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showDeployModal = false"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="deployForm.processing"
                            class="px-6 py-2.5 rounded-xl text-xs font-black text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition-all disabled:opacity-50"
                        >
                            {{ deployForm.processing ? 'Deploying...' : 'Deploy to Building' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: IMPORT ITEMS -->
        <div v-if="showImportModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                            <Upload class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Import Inventory CSV/Excel</h3>
                            <p class="text-xs text-slate-500">Bulk upload store inventory items</p>
                        </div>
                    </div>
                    <button @click="showImportModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Select Spreadsheet (.xlsx, .csv)</label>
                        <input
                            type="file"
                            accept=".csv, .xlsx, .xls"
                            @change="handleFileUpload"
                            required
                            class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-300"
                        />
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            @click="showImportModal = false"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="importForm.processing || !importForm.file"
                            class="px-6 py-2.5 rounded-xl text-xs font-black text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition-all disabled:opacity-50"
                        >
                            {{ importForm.processing ? 'Importing...' : 'Upload & Import' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AdminLayout>
</template>
