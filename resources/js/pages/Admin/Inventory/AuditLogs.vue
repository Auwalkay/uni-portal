<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Activity, Filter, ArrowUpRight, ArrowDownRight, 
    Sparkles, ShieldCheck, Clock, Layers, Package 
} from 'lucide-vue-next';

const props = defineProps<{
    logs: {
        data: Array<{
            id: number;
            type: 'restock' | 'issue' | 'return' | 'adjustment' | 'damage';
            quantity: number;
            notes?: string;
            created_at: string;
            item?: { name: string; unit_of_measure: string; sku?: string };
            user?: { name: string; email: string };
        }>;
        links: Array<any>;
        total: number;
    };
    filters?: {
        type?: string;
    };
}>();

const typeFilter = ref(props.filters?.type || 'ALL');

const stats = computed(() => {
    const list = props.logs.data || [];
    return {
        total: props.logs.total || list.length,
        restocks: list.filter(l => l.type === 'restock').length,
        dispatches: list.filter(l => l.type === 'issue').length,
    };
});

const handleFilter = () => {
    router.get(
        route('admin.inventory.audit-logs.index'),
        { type: typeFilter.value },
        { preserveState: true, replace: true }
    );
};

const formatDate = (d: string) => {
    if (!d) return 'N/A';
    return new Date(d).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Stock Movement Audit Trail — Central Store" />

    <AdminLayout>
        <div class="py-8 min-h-screen bg-slate-50/50 dark:bg-slate-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Hero Header -->
                <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-2xl border border-slate-800">
                    <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-semibold">
                                <Activity class="w-3.5 h-3.5 text-indigo-400" />
                                Immutable Store Audit Trail
                            </div>
                            <h1 class="text-3xl sm:text-4xl font-black tracking-tight">
                                Stock Movement Audit Log
                            </h1>
                            <p class="text-slate-300 text-sm max-w-2xl leading-relaxed">
                                Immutable store activity ledger tracking restocks, departmental lab dispatches, staff returns, and manual adjustments.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Audit Trail Events</p>
                                <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                                <Activity class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Stock Restocks (+ Inflow)</p>
                                <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.restocks }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold">
                                <ArrowUpRight class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Store Issues (- Outflow)</p>
                                <p class="text-3xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ stats.dispatches }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold">
                                <ArrowDownRight class="w-6 h-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Filter class="w-3.5 h-3.5 text-slate-400" />
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">Filter Movement Type:</span>
                        <select
                            v-model="typeFilter"
                            @change="handleFilter"
                            class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-none"
                        >
                            <option value="ALL">All Movements</option>
                            <option value="restock">Restock (+ Supply)</option>
                            <option value="issue">Store Issue / Dispatch</option>
                            <option value="return">Item Return</option>
                            <option value="adjustment">Stock Adjustment</option>
                            <option value="damage">Damage / Write-off</option>
                        </select>
                    </div>
                    <span class="text-xs font-bold text-slate-400 font-mono">Total {{ logs.total }} Audit Records</span>
                </div>

                <!-- Audit Ledger Table -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50/80 dark:bg-slate-900/80">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Movement Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Item Name & SKU</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Quantity Delta</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Officer / User</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Notes & Purpose</th>
                                    <th class="px-6 py-4 text-right text-xs font-extrabold text-slate-500 uppercase">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div 
                                                class="h-8 w-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 border"
                                                :class="log.type === 'restock' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800'"
                                            >
                                                <ArrowUpRight v-if="log.type === 'restock'" class="w-4 h-4" />
                                                <ArrowDownRight v-else class="w-4 h-4" />
                                            </div>
                                            <span class="font-extrabold text-slate-900 dark:text-white text-xs capitalize">{{ log.type }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 dark:text-white text-xs">{{ log.item?.name || 'Item' }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">SKU: {{ log.item?.sku || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-extrabold text-xs" :class="log.type === 'restock' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-800 dark:text-slate-200'">
                                        {{ log.type === 'restock' ? '+' : '-' }}{{ log.quantity }} {{ log.item?.unit_of_measure || 'units' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ log.user?.name || 'Store Manager' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ log.user?.email || 'System' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500 font-medium max-w-xs truncate">
                                        {{ log.notes || 'Automated stock audit ledger log' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-mono text-slate-400">
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="!logs.data || logs.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
                                        No stock audit records found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.links && logs.links.length > 3" class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <div class="flex gap-1">
                            <component
                                v-for="(link, i) in logs.links"
                                :key="i"
                                :is="link.url ? 'Link' : 'span'"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3.5 py-2 rounded-xl text-xs font-bold transition-colors"
                                :class="[
                                    link.active ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                            />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>
