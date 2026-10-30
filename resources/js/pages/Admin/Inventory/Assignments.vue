<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Building2, Search, Filter, RotateCcw, 
    X, CheckCircle2, AlertTriangle, Calendar,
    Package, ShieldCheck, Download, Plus, ArrowUpRight,
    MapPin, Layers, Check, Info, Box, User, Users,
    UserCheck, Loader2
} from 'lucide-vue-next';

interface AssignmentItem {
    id: number;
    inventory_item_id: number;
    building_id?: string;
    staff_id?: string;
    quantity: number;
    location_details?: string;
    assigned_at: string;
    expected_return_date?: string;
    returned_at?: string;
    status: 'assigned' | 'returned' | 'lost' | 'damaged';
    condition_on_assignment?: string;
    condition_on_return?: string;
    item?: { id: number; name: string; sku?: string; available_quantity?: number; total_quantity?: number; unit_of_measure?: string };
    building?: { id: string; name: string; code: string; building_type: string };
    staff?: { id: string; staff_number?: string; user?: { name: string; email: string }; department?: { name: string } };
    assignable?: { id: string; name?: string; user?: { name: string; email: string }; department?: { name: string } };
}

const props = defineProps<{
    assignments: {
        data: AssignmentItem[];
        links: Array<any>;
        total: number;
    };
    items?: Array<{
        id: number;
        name: string;
        sku?: string;
        available_quantity: number;
        total_quantity: number;
        unit_of_measure?: string;
        condition?: string;
    }>;
    buildings?: Array<{
        id: string;
        name: string;
        code: string;
        building_type: string;
    }>;
    stats?: {
        total_deployments: number;
        active_deployed_units: number;
        building_units?: number;
        staff_custody_units?: number;
        returned_units: number;
        damaged_lost_units: number;
    };
    filters?: {
        search?: string;
        status?: string;
        building_id?: string;
        target_type?: string;
    };
    permissions?: {
        can_manage?: boolean;
    };
}>();

const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'ALL');
const buildingFilter = ref(props.filters?.building_id || 'ALL');
const targetFilter = ref(props.filters?.target_type || 'ALL');

const handleSearch = () => {
    router.get(
        route('admin.inventory.assignments.index'),
        { 
            search: searchQuery.value, 
            status: statusFilter.value,
            building_id: buildingFilter.value,
            target_type: targetFilter.value,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'ALL';
    buildingFilter.value = 'ALL';
    targetFilter.value = 'ALL';
    handleSearch();
};

// Modals State
const showDeployModal = ref(false);
const showReturnModal = ref(false);
const selectedAssignment = ref<AssignmentItem | null>(null);

// Staff Search Autocomplete State
const staffSearchQuery = ref('');
const isSearchingStaff = ref(false);
const staffSearchResults = ref<Array<{ id: string; name: string; staff_number: string; email?: string; department: string; label: string }>>([]);
const selectedStaff = ref<{ id: string; name: string; staff_number: string; email?: string; department: string; label: string } | null>(null);

let staffSearchTimeout: any = null;
const onStaffSearchInput = () => {
    if (staffSearchTimeout) clearTimeout(staffSearchTimeout);
    const q = staffSearchQuery.value ? staffSearchQuery.value.trim() : '';
    if (q.length < 1) {
        staffSearchResults.value = [];
        return;
    }
    isSearchingStaff.value = true;
    staffSearchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(route('admin.inventory.staff.search', { query: q }));
            const data = await res.json();
            staffSearchResults.value = data;
        } catch (e) {
            console.error('Failed to search staff:', e);
            staffSearchResults.value = [];
        } finally {
            isSearchingStaff.value = false;
        }
    }, 250);
};

const selectStaffMember = (staff: any) => {
    selectedStaff.value = staff;
    deployForm.staff_id = staff.id;
    staffSearchQuery.value = staff.name;
    staffSearchResults.value = [];
};

const clearSelectedStaff = () => {
    selectedStaff.value = null;
    deployForm.staff_id = '';
    staffSearchQuery.value = '';
    staffSearchResults.value = [];
};

// Deploy Form
const deployForm = useForm({
    inventory_item_id: props.items && props.items.length > 0 ? props.items[0].id : '',
    assignment_target: 'building' as 'building' | 'staff',
    building_id: props.buildings && props.buildings.length > 0 ? props.buildings[0].id : '',
    staff_id: '',
    quantity: 1,
    location_details: '',
    expected_return_date: '',
    condition_on_assignment: 'good',
});

// Selected Item for Quantity Validation
const selectedItemStock = computed(() => {
    if (!props.items) return null;
    return props.items.find(i => i.id === Number(deployForm.inventory_item_id)) || null;
});

const openDeployModal = (preselectedItemId: number | null = null) => {
    if (preselectedItemId) {
        deployForm.inventory_item_id = preselectedItemId;
    } else if (props.items && props.items.length > 0 && !deployForm.inventory_item_id) {
        deployForm.inventory_item_id = props.items[0].id;
    }
    deployForm.assignment_target = 'building';
    if (props.buildings && props.buildings.length > 0 && !deployForm.building_id) {
        deployForm.building_id = props.buildings[0].id;
    }
    deployForm.staff_id = '';
    clearSelectedStaff();
    deployForm.quantity = 1;
    deployForm.location_details = '';
    deployForm.expected_return_date = '';
    deployForm.condition_on_assignment = 'good';
    showDeployModal.value = true;
};

const submitDeploy = () => {
    deployForm.post(route('admin.inventory.assignments.store'), {
        onSuccess: () => {
            showDeployModal.value = false;
            deployForm.reset();
            clearSelectedStaff();
        },
    });
};

// Return Form
const returnForm = useForm({
    status: 'returned',
    condition_on_return: 'good',
});

const openReturnModal = (assignment: AssignmentItem) => {
    selectedAssignment.value = assignment;
    returnForm.status = 'returned';
    returnForm.condition_on_return = assignment.condition_on_assignment || 'good';
    showReturnModal.value = true;
};

const submitReturn = () => {
    if (!selectedAssignment.value) return;
    returnForm.put(route('admin.inventory.assignments.return', selectedAssignment.value.id), {
        onSuccess: () => {
            showReturnModal.value = false;
            selectedAssignment.value = null;
        },
    });
};

const formatDate = (dateStr?: string) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Asset Allocations & Custody — Inventory" />

    <AdminLayout>
        <div class="py-6 sm:py-8 min-h-screen bg-slate-50/60 dark:bg-slate-900/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                
                <!-- Sub Navigation Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500">
                    <Link :href="route('admin.inventory.index')" class="px-3.5 py-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
                        Inventory Catalog
                    </Link>
                    <Link :href="route('admin.inventory.assignments.index')" class="px-3.5 py-1.5 rounded-lg bg-indigo-600 text-white font-extrabold shadow-sm transition-all">
                        Deployments & Custody
                    </Link>
                    <Link :href="route('admin.inventory.requisitions.index')" class="px-3.5 py-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition-all">
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
                                <Layers class="w-3.5 h-3.5 text-indigo-400" />
                                Dual-Mode Asset Management
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                                Asset Allocations & Custody
                            </h1>
                            <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
                                Track, deploy, and audit university equipment, lab apparatus, and IT devices assigned either directly to campus facilities or into individual staff member custody.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                            <a
                                :href="route('admin.inventory.export-assignments')"
                                class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-200 bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700 shadow-sm transition-all gap-1.5"
                            >
                                <Download class="w-4 h-4" />
                                Export Excel
                            </a>
                            <button
                                v-if="permissions?.can_manage || true"
                                @click="openDeployModal()"
                                class="inline-flex items-center px-5 py-2.5 rounded-xl text-xs font-black text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 transition-all gap-2 active:scale-95"
                            >
                                <Plus class="w-4 h-4" />
                                Assign / Deploy Item
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Metric Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Allocations</p>
                                <p class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-1">
                                    {{ stats?.total_deployments ?? assignments.total }}
                                </p>
                            </div>
                            <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                <Box class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Building Deployments</p>
                                <p class="text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1">
                                    {{ stats?.building_units ?? 0 }}
                                </p>
                            </div>
                            <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                <Building2 class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Staff Custody</p>
                                <p class="text-2xl sm:text-3xl font-black text-violet-600 dark:text-violet-400 mt-1">
                                    {{ stats?.staff_custody_units ?? 0 }}
                                </p>
                            </div>
                            <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl bg-violet-50 dark:bg-violet-950/60 flex items-center justify-center text-violet-600 dark:text-violet-400">
                                <UserCheck class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Returned to Store</p>
                                <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                                    {{ stats?.returned_units ?? 0 }}
                                </p>
                            </div>
                            <div class="h-10 w-10 sm:h-12 sm:w-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                <RotateCcw class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search Toolbar -->
                <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-1">
                        <div class="relative w-full sm:w-64">
                            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="searchQuery"
                                @keyup.enter="handleSearch"
                                type="text"
                                placeholder="Search item, SKU, staff, or room..."
                                class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all"
                            />
                        </div>

                        <!-- Target Type Filter -->
                        <select
                            v-model="targetFilter"
                            @change="handleSearch"
                            class="w-full sm:w-44 px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all"
                        >
                            <option value="ALL">All Targets</option>
                            <option value="building">🏢 Campus Buildings</option>
                            <option value="staff">👤 Staff Custody</option>
                        </select>

                        <!-- Building Filter -->
                        <select
                            v-model="buildingFilter"
                            @change="handleSearch"
                            class="w-full sm:w-52 px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all"
                        >
                            <option value="ALL">All Buildings</option>
                            <option v-for="b in buildings" :key="b.id" :value="b.id">
                                [{{ b.code }}] {{ b.name }}
                            </option>
                        </select>

                        <!-- Status Filter -->
                        <select
                            v-model="statusFilter"
                            @change="handleSearch"
                            class="w-full sm:w-40 px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white transition-all"
                        >
                            <option value="ALL">All Statuses</option>
                            <option value="assigned">Active</option>
                            <option value="returned">Returned</option>
                            <option value="damaged">Damaged</option>
                            <option value="lost">Lost</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 self-end md:self-center">
                        <button
                            v-if="searchQuery || statusFilter !== 'ALL' || buildingFilter !== 'ALL' || targetFilter !== 'ALL'"
                            @click="resetFilters"
                            class="px-3 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white"
                        >
                            Reset
                        </button>
                        <button
                            @click="handleSearch"
                            class="px-4 py-2 bg-slate-900 text-white dark:bg-slate-700 dark:hover:bg-slate-600 rounded-xl text-xs font-bold transition-all"
                        >
                            Filter
                        </button>
                    </div>
                </div>

                <!-- Allocations Table -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200/80 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 text-[11px] font-extrabold uppercase tracking-wider">
                                    <th class="px-6 py-4">Item & SKU</th>
                                    <th class="px-6 py-4">Target & Destination</th>
                                    <th class="px-6 py-4 text-center">Quantity</th>
                                    <th class="px-6 py-4">Condition</th>
                                    <th class="px-6 py-4">Assigned Date</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50 text-xs">
                                <tr 
                                    v-for="asg in assignments.data" 
                                    :key="asg.id"
                                    class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30 transition-colors"
                                >
                                    <!-- Item Name & SKU -->
                                    <td class="px-6 py-4">
                                        <div class="font-extrabold text-slate-900 dark:text-white">
                                            {{ asg.item?.name || 'Unknown Item' }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                            SKU: {{ asg.item?.sku || 'N/A' }}
                                        </div>
                                    </td>

                                    <!-- Destination Building or Staff Member -->
                                    <td class="px-6 py-4">
                                        <!-- Case 1: Individual Staff Member -->
                                        <div v-if="asg.staff" class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-violet-50 dark:bg-violet-950/70 text-violet-700 dark:text-violet-300 font-extrabold text-[10px] border border-violet-200 dark:border-violet-800">
                                                    <User class="w-3 h-3 text-violet-500" />
                                                    Staff Custody
                                                </span>
                                                <span v-if="asg.staff.staff_number" class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold">
                                                    {{ asg.staff.staff_number }}
                                                </span>
                                            </div>
                                            <p class="font-bold text-slate-900 dark:text-white">
                                                {{ asg.staff.user?.name || 'Staff Member' }}
                                            </p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                                {{ asg.staff.department?.name || 'Department Staff' }}
                                                <span v-if="asg.location_details" class="text-slate-400"> • {{ asg.location_details }}</span>
                                            </p>
                                        </div>

                                        <!-- Case 2: Campus Building -->
                                        <div v-else-if="asg.building" class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 font-extrabold text-[10px] border border-indigo-200 dark:border-indigo-800">
                                                    <Building2 class="w-3 h-3 text-indigo-500" />
                                                    {{ asg.building.code || 'Facility' }}
                                                </span>
                                            </div>
                                            <p class="font-bold text-slate-900 dark:text-white">
                                                {{ asg.building.name }}
                                            </p>
                                            <div class="flex items-center gap-1 text-[11px] text-slate-500">
                                                <MapPin class="w-3 h-3 text-slate-400 shrink-0" />
                                                <span>{{ asg.location_details || 'General Building Area' }}</span>
                                            </div>
                                        </div>

                                        <!-- Case 3: Polymorphic fallback -->
                                        <div v-else class="space-y-1">
                                            <p class="font-bold text-slate-900 dark:text-white">
                                                {{ asg.assignable?.user?.name || asg.assignable?.name || 'University Custody' }}
                                            </p>
                                            <p v-if="asg.location_details" class="text-[11px] text-slate-500">
                                                {{ asg.location_details }}
                                            </p>
                                        </div>
                                    </td>

                                    <!-- Deployed Quantity -->
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white">
                                            {{ asg.quantity ?? 1 }} {{ asg.item?.unit_of_measure || 'units' }}
                                        </span>
                                    </td>

                                    <!-- Condition -->
                                    <td class="px-6 py-4 capitalize font-medium text-slate-600 dark:text-slate-300">
                                        {{ asg.condition_on_assignment || 'Good' }}
                                    </td>

                                    <!-- Dates -->
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">
                                            {{ formatDate(asg.assigned_at) }}
                                        </div>
                                        <div v-if="asg.expected_return_date" class="text-[11px] text-slate-400 mt-0.5">
                                            Return: {{ formatDate(asg.expected_return_date) }}
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4">
                                        <span 
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold capitalize border"
                                            :class="{
                                                'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800': asg.status === 'assigned',
                                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800': asg.status === 'returned',
                                                'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800': asg.status === 'damaged',
                                                'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700': asg.status === 'lost',
                                            }"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="{
                                                'bg-indigo-500': asg.status === 'assigned',
                                                'bg-emerald-500': asg.status === 'returned',
                                                'bg-rose-500': asg.status === 'damaged',
                                                'bg-slate-500': asg.status === 'lost',
                                            }"></span>
                                            {{ asg.status === 'assigned' ? 'In Custody' : asg.status }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            v-if="asg.status === 'assigned' && (permissions?.can_manage || true)"
                                            @click="openReturnModal(asg)"
                                            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-bold transition-all inline-flex items-center gap-1.5"
                                        >
                                            <RotateCcw class="w-3.5 h-3.5 text-indigo-500" />
                                            Process Return
                                        </button>
                                        <span v-else-if="asg.status === 'returned'" class="text-[11px] text-slate-400 font-medium">
                                            Returned on {{ formatDate(asg.returned_at) }}
                                        </span>
                                    </td>
                                </tr>

                                <tr v-if="assignments.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                        <Layers class="w-10 h-10 mx-auto mb-2 opacity-40 text-slate-400" />
                                        <p class="font-extrabold text-sm text-slate-600 dark:text-slate-300">No asset allocations found</p>
                                        <p class="text-xs text-slate-400 mt-1">Deploy items to campus buildings or assign to individual staff members using the button above.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="assignments.links && assignments.links.length > 3" class="px-6 py-4 border-t border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between">
                        <div class="text-xs text-slate-500">
                            Showing {{ assignments.data.length }} of {{ assignments.total }} allocations
                        </div>
                        <div class="flex gap-1">
                            <Component
                                :is="link.url ? Link : 'span'"
                                v-for="(link, index) in assignments.links"
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

        <!-- MODAL: DEPLOY ITEM (DUAL-MODE: BUILDING OR STAFF) -->
        <div 
            v-if="showDeployModal" 
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 md:p-6"
            @click.self="showDeployModal = false"
        >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl max-w-xl w-full shadow-2xl flex flex-col max-h-[92vh] sm:max-h-[88vh] overflow-hidden my-auto">
                <!-- Fixed Header -->
                <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 shrink-0 bg-white dark:bg-slate-900">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold shrink-0">
                            <Layers class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">Assign / Deploy Inventory Item</h3>
                            <p class="text-[11px] sm:text-xs text-slate-500">Assign to a campus building or to an individual staff member</p>
                        </div>
                    </div>
                    <button @click="showDeployModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Scrollable Body -->
                <form @submit.prevent="submitDeploy" id="assignmentDeployForm" class="flex-1 overflow-y-auto px-5 sm:px-6 py-4 space-y-4 overscroll-contain text-xs">
                    <!-- Target Selector Toggle -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Assignment Destination Type *</label>
                        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700">
                            <button
                                type="button"
                                @click="deployForm.assignment_target = 'building'"
                                class="flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-black transition-all"
                                :class="deployForm.assignment_target === 'building' 
                                    ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' 
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
                            >
                                <Building2 class="w-4 h-4" />
                                <span>Campus Building</span>
                            </button>
                            <button
                                type="button"
                                @click="deployForm.assignment_target = 'staff'"
                                class="flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-black transition-all"
                                :class="deployForm.assignment_target === 'staff' 
                                    ? 'bg-white dark:bg-slate-700 text-violet-600 dark:text-white shadow-sm' 
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
                            >
                                <User class="w-4 h-4" />
                                <span>Individual Staff</span>
                            </button>
                        </div>
                    </div>

                    <!-- Select Item -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select Store Item *</label>
                        <select
                            v-model="deployForm.inventory_item_id"
                            required
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                        >
                            <option v-for="item in items" :key="item.id" :value="item.id">
                                {{ item.name }} ({{ item.sku || 'No SKU' }}) — Available: {{ item.available_quantity }} {{ item.unit_of_measure || 'units' }}
                            </option>
                        </select>
                        <div v-if="selectedItemStock" class="mt-1 text-[11px] text-indigo-600 dark:text-indigo-400 font-medium">
                            Stock Available: <strong>{{ selectedItemStock.available_quantity }} {{ selectedItemStock.unit_of_measure || 'units' }}</strong>
                        </div>
                    </div>

                    <!-- IF BUILDING TARGET -->
                    <div v-if="deployForm.assignment_target === 'building'" class="space-y-4">
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

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Room / Location Details</label>
                            <input
                                v-model="deployForm.location_details"
                                type="text"
                                placeholder="e.g. Room 204, Lab B, Stage"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                            />
                        </div>
                    </div>

                    <!-- IF STAFF TARGET -->
                    <div v-if="deployForm.assignment_target === 'staff'" class="space-y-3">
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Search & Select Staff Member *</label>
                        
                        <!-- Selected Staff Card -->
                        <div v-if="selectedStaff" class="p-3.5 rounded-xl bg-violet-50 dark:bg-violet-950/50 border border-violet-200 dark:border-violet-800 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-violet-600 text-white flex items-center justify-center font-black text-sm shrink-0">
                                    <User class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="font-extrabold text-slate-900 dark:text-white text-xs">{{ selectedStaff.name }}</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                        ID: <strong>{{ selectedStaff.staff_number || 'N/A' }}</strong> • {{ selectedStaff.department }}
                                    </p>
                                </div>
                            </div>
                            <button 
                                type="button" 
                                @click="clearSelectedStaff"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                                title="Change staff member"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Search Autocomplete Input -->
                        <div v-else class="relative">
                            <div class="relative">
                                <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input
                                    v-model="staffSearchQuery"
                                    @input="onStaffSearchInput"
                                    type="text"
                                    placeholder="Type staff name, email, or staff number..."
                                    class="w-full pl-10 pr-9 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-violet-500 dark:text-white"
                                />
                                <div v-if="isSearchingStaff" class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <Loader2 class="w-4 h-4 text-violet-500 animate-spin" />
                                </div>
                            </div>

                            <!-- Dropdown Results -->
                            <div 
                                v-if="staffSearchResults.length > 0" 
                                class="absolute z-20 left-0 right-0 mt-1 max-h-48 overflow-y-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl divide-y divide-slate-100 dark:divide-slate-700"
                            >
                                <button
                                    v-for="staff in staffSearchResults"
                                    :key="staff.id"
                                    type="button"
                                    @click="selectStaffMember(staff)"
                                    class="w-full px-3.5 py-2.5 text-left hover:bg-violet-50 dark:hover:bg-violet-950/50 flex items-center justify-between transition-colors"
                                >
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-xs">{{ staff.name }}</p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                            {{ staff.staff_number ? `ID: ${staff.staff_number}` : '' }} • {{ staff.department }}
                                        </p>
                                    </div>
                                    <Check class="w-4 h-4 text-violet-600 opacity-0 group-hover:opacity-100" />
                                </button>
                            </div>

                            <p v-if="!isSearchingStaff && staffSearchQuery.length >= 2 && staffSearchResults.length === 0" class="text-[11px] text-amber-600 dark:text-amber-400 mt-1">
                                No staff members found matching "{{ staffSearchQuery }}".
                            </p>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Office / Desk Notes</label>
                            <input
                                v-model="deployForm.location_details"
                                type="text"
                                placeholder="e.g. Office 3A, Desk 2, Personal Custody"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-violet-500 dark:text-white"
                            />
                        </div>

                        <div class="p-2.5 rounded-xl bg-violet-50/60 dark:bg-violet-950/30 border border-violet-200/60 dark:border-violet-800/40 text-[11px] text-violet-700 dark:text-violet-300">
                            💡 Assigned items will appear on the staff member's <strong>"My Inventory"</strong> portal and an email confirmation will be sent.
                        </div>
                    </div>

                    <!-- Quantity & Initial Condition -->
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Quantity *</label>
                            <input
                                v-model.number="deployForm.quantity"
                                type="number"
                                min="1"
                                :max="selectedItemStock?.available_quantity || 9999"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white font-bold"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Condition on Handover</label>
                            <select
                                v-model="deployForm.condition_on_assignment"
                                class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white capitalize font-semibold"
                            >
                                <option value="new">Brand New</option>
                                <option value="good">Good / Functional</option>
                                <option value="fair">Fair</option>
                            </select>
                        </div>
                    </div>

                    <!-- Expected Return Date -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Expected Return Date (Optional)</label>
                        <input
                            v-model="deployForm.expected_return_date"
                            type="date"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:text-white"
                        />
                    </div>
                </form>

                <!-- Fixed Footer -->
                <div class="flex items-center justify-end gap-3 px-5 sm:px-6 py-3.5 sm:py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-900/90 shrink-0">
                    <button
                        type="button"
                        @click="showDeployModal = false"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        form="assignmentDeployForm"
                        :disabled="deployForm.processing || (deployForm.assignment_target === 'staff' && !deployForm.staff_id)"
                        class="px-6 py-2.5 rounded-xl text-xs font-black text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition-all disabled:opacity-50 active:scale-95"
                    >
                        {{ deployForm.processing ? 'Assigning...' : (deployForm.assignment_target === 'staff' ? 'Confirm Staff Assignment' : 'Confirm Deployment') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: PROCESS ITEM RETURN -->
        <div 
            v-if="showReturnModal && selectedAssignment" 
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 md:p-6"
            @click.self="showReturnModal = false"
        >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl max-w-md w-full shadow-2xl flex flex-col max-h-[92vh] sm:max-h-[88vh] overflow-hidden my-auto">
                <!-- Fixed Header -->
                <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-slate-800 shrink-0 bg-white dark:bg-slate-900">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold shrink-0">
                            <RotateCcw class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">Process Item Return</h3>
                            <p class="text-[11px] sm:text-xs text-slate-500">Restore or update status of deployed equipment</p>
                        </div>
                    </div>
                    <button @click="showReturnModal = false" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Scrollable Body -->
                <form @submit.prevent="submitReturn" id="processReturnForm" class="flex-1 overflow-y-auto px-5 sm:px-6 py-4 space-y-4 overscroll-contain text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 space-y-1">
                        <p class="font-extrabold text-slate-900 dark:text-white">{{ selectedAssignment.item?.name }}</p>
                        <p class="text-slate-500">
                            Custodian: <strong>{{ selectedAssignment.staff?.user?.name || selectedAssignment.building?.name || 'Store' }}</strong> 
                            <span v-if="selectedAssignment.location_details"> ({{ selectedAssignment.location_details }})</span>
                        </p>
                        <p class="text-slate-500">
                            Quantity: <strong>{{ selectedAssignment.quantity ?? 1 }} {{ selectedAssignment.item?.unit_of_measure || 'units' }}</strong>
                        </p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Return Status *</label>
                        <select
                            v-model="returnForm.status"
                            required
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white"
                        >
                            <option value="returned">Returned to Store (Increments available stock)</option>
                            <option value="damaged">Damaged (Requires repair / write-off)</option>
                            <option value="lost">Lost / Missing</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Condition on Return</label>
                        <select
                            v-model="returnForm.condition_on_return"
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 dark:text-white capitalize"
                        >
                            <option value="new">Brand New</option>
                            <option value="good">Good / Functional</option>
                            <option value="fair">Fair (Minor wear)</option>
                            <option value="poor">Poor (Needs attention)</option>
                            <option value="damaged">Damaged / Broken</option>
                        </select>
                    </div>
                </form>

                <!-- Fixed Footer -->
                <div class="flex items-center justify-end gap-3 px-5 sm:px-6 py-3.5 sm:py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-900/90 shrink-0">
                    <button
                        type="button"
                        @click="showReturnModal = false"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        form="processReturnForm"
                        :disabled="returnForm.processing"
                        class="px-6 py-2.5 rounded-xl text-xs font-black text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-600/30 transition-all disabled:opacity-50 active:scale-95"
                    >
                        {{ returnForm.processing ? 'Processing...' : 'Complete Return' }}
                    </button>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>
