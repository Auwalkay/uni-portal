<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    UserCheck, UserPlus, Search, Filter, RotateCcw, 
    X, CheckCircle2, AlertTriangle, Monitor, Calendar,
    Laptop, Sparkles, Building2, Package, ShieldCheck, Check,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps<{
    assignments: {
        data: Array<{
            id: number;
            inventory_item_id: number;
            assigned_at: string;
            expected_return_date?: string;
            returned_at?: string;
            status: 'assigned' | 'returned' | 'lost' | 'damaged';
            condition_on_assignment?: string;
            condition_on_return?: string;
            item?: { id: number; name: string; sku?: string; available_quantity?: number; total_quantity?: number };
            assignable?: { id: string; user?: { name: string; email: string }; department?: { name: string } };
        }>;
        links: Array<any>;
        total: number;
    };
    items?: Array<{
        id: number;
        name: string;
        sku?: string;
        available_quantity: number;
        total_quantity: number;
    }>;
    filters?: {
        search?: string;
        status?: string;
    };
    permissions?: {
        can_manage?: boolean;
    };
}>();

const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'ALL');

const stats = computed(() => {
    const list = props.assignments.data || [];
    return {
        total: props.assignments.total || list.length,
        assigned: list.filter(a => a.status === 'assigned').length,
        returned: list.filter(a => a.status === 'returned').length,
        damaged_lost: list.filter(a => a.status === 'damaged' || a.status === 'lost').length,
    };
});

const handleSearch = () => {
    router.get(
        route('admin.inventory.assignments.index'),
        { search: searchQuery.value, status: statusFilter.value },
        { preserveState: true, replace: true }
    );
};

// Modal State
const showAssignModal = ref(false);
const showReturnModal = ref(false);
const selectedAssignment = ref<any>(null);

// Assign Form
const assignForm = useForm({
    inventory_item_id: props.items && props.items.length > 0 ? props.items[0].id : '',
    staff_id: '',
    expected_return_date: '',
    condition_on_assignment: 'good',
});

// Staff Search State
const staffSearchQuery = ref('');
const staffSearchResults = ref<any[]>([]);
const selectedStaff = ref<any>(null);

const searchStaff = async () => {
    if (staffSearchQuery.value.length < 2) {
        staffSearchResults.value = [];
        return;
    }
    const response = await fetch(`/admin/inventory/staff/search?query=${staffSearchQuery.value}`);
    const data = await response.json();
    staffSearchResults.value = data;
};

const selectStaffMember = (st: any) => {
    assignForm.staff_id = st.id;
    selectedStaff.value = st;
    staffSearchQuery.value = st.name;
    staffSearchResults.value = [];
};

const clearSelectedStaff = () => {
    assignForm.staff_id = '';
    selectedStaff.value = null;
    staffSearchQuery.value = '';
    staffSearchResults.value = [];
};

const openAssignModal = () => {
    assignForm.reset();
    selectedStaff.value = null;
    staffSearchQuery.value = '';
    staffSearchResults.value = [];
    showAssignModal.value = true;
};

const submitAssign = () => {
    assignForm.post(route('admin.inventory.assignments.store'), {
        onSuccess: () => {
            showAssignModal.value = false;
            assignForm.reset();
        },
    });
};

// Return Form
const returnForm = useForm({
    status: 'returned',
    condition_on_return: 'good',
});

const openReturnModal = (asg: any) => {
    selectedAssignment.value = asg;
    returnForm.status = 'returned';
    returnForm.condition_on_return = asg.condition_on_assignment || 'good';
    showReturnModal.value = true;
};

const submitReturnItem = () => {
    returnForm.put(route('admin.inventory.assignments.return', selectedAssignment.value.id), {
        onSuccess: () => {
            showReturnModal.value = false;
        },
    });
};

const formatDate = (d: string) => {
    if (!d) return 'N/A';
    return new Date(d).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Staff Equipment Allocations — Central Store" />

    <AdminLayout>
        <div class="py-8 min-h-screen bg-slate-50/50 dark:bg-slate-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Hero Header -->
                <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-2xl border border-slate-800">
                    <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-semibold">
                                <UserCheck class="w-3.5 h-3.5 text-indigo-400" />
                                Staff Asset Custody Management
                            </div>
                            <h1 class="text-3xl sm:text-4xl font-black tracking-tight">
                                Staff Equipment Allocations
                            </h1>
                            <p class="text-slate-300 text-sm max-w-2xl leading-relaxed">
                                Manage laptops, desktop computers, printers, and laboratory devices assigned to individual university staff members.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <button
                                v-if="permissions?.can_manage || true"
                                @click="openAssignModal"
                                class="inline-flex items-center px-5 py-3 rounded-2xl text-xs font-extrabold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 shadow-xl shadow-indigo-500/20 active:scale-95 transition-all border border-indigo-400/30 gap-2"
                            >
                                <UserPlus class="w-4 h-4" />
                                Assign Equipment to Staff
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Allocations</p>
                                <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                                <Laptop class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Currently Issued</p>
                                <p class="text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ stats.assigned }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                                <UserCheck class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Returned to Store</p>
                                <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.returned }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold">
                                <RotateCcw class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Damaged / Lost</p>
                                <p class="text-3xl font-black text-red-600 dark:text-red-400 mt-1">{{ stats.damaged_lost }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-red-50 dark:bg-red-950/50 flex items-center justify-center text-red-600 dark:text-red-400 font-bold">
                                <AlertTriangle class="w-6 h-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Bar -->
                <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text"
                            placeholder="Search equipment or staff member..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-none"
                        />
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="flex items-center gap-2">
                            <Filter class="w-3.5 h-3.5 text-slate-400" />
                            <select
                                v-model="statusFilter"
                                @change="handleSearch"
                                class="px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition-all outline-none"
                            >
                                <option value="ALL">All Statuses</option>
                                <option value="assigned">Currently Assigned</option>
                                <option value="returned">Returned to Store</option>
                                <option value="damaged">Damaged / Repair Needed</option>
                                <option value="lost">Lost / Unreturned</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Datatable -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50/80 dark:bg-slate-900/80">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Equipment / Asset</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Assigned Staff Member</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Condition</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Issue & Return Schedule</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Status</th>
                                    <th class="px-6 py-4 text-right text-xs font-extrabold text-slate-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <tr v-for="asg in assignments.data" :key="asg.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0 border border-indigo-200 dark:border-indigo-800">
                                                💻
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-slate-900 dark:text-white text-xs">{{ asg.item?.name || 'Equipment' }}</p>
                                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">SKU: {{ asg.item?.sku || 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white text-xs">{{ asg.assignable?.user?.name || 'Staff Member' }}</p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">{{ asg.assignable?.department?.name || 'University Staff' }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-slate-700 dark:text-slate-300 capitalize">
                                        {{ asg.condition_on_assignment || 'Good' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 font-medium">
                                        <div>Issued: <strong>{{ formatDate(asg.assigned_at) }}</strong></div>
                                        <div v-if="asg.expected_return_date" class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold mt-0.5">
                                            Return By: {{ formatDate(asg.expected_return_date) }}
                                        </div>
                                        <div v-else class="text-[10px] text-slate-400">Permanent Issue</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            class="px-3 py-1 rounded-full text-xs font-extrabold capitalize border"
                                            :class="{
                                                'bg-indigo-50 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800': asg.status === 'assigned',
                                                'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800': asg.status === 'returned',
                                                'bg-red-50 text-red-800 dark:bg-red-950/60 dark:text-red-300 border-red-200 dark:border-red-800': asg.status === 'lost',
                                                'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800': asg.status === 'damaged',
                                            }"
                                        >
                                            {{ asg.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium">
                                        <button 
                                            v-if="asg.status === 'assigned' && (permissions?.can_manage || true)" 
                                            @click="openReturnModal(asg)" 
                                            class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-200 font-bold transition-all border border-slate-200 dark:border-slate-600"
                                        >
                                            Process Return
                                        </button>
                                        <span v-else class="text-slate-400 text-xs">—</span>
                                    </td>
                                </tr>
                                <tr v-if="!assignments.data || assignments.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
                                        No staff equipment assignments found matching your filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="assignments.links && assignments.links.length > 3" class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <div class="flex gap-1">
                            <component
                                v-for="(link, i) in assignments.links"
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

        <!-- ASSIGN EQUIPMENT MODAL -->
        <div v-if="showAssignModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-6 relative">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-lg shadow-indigo-500/30">
                            <UserPlus class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white">Assign Equipment to Staff</h3>
                            <p class="text-xs text-slate-500">Allocate reusable store assets to an individual staff member.</p>
                        </div>
                    </div>
                    <button @click="showAssignModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>

                <form @submit.prevent="submitAssign" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Equipment / Item *</label>
                        <select v-model="assignForm.inventory_item_id" required class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-bold">
                            <option v-for="it in items" :key="it.id" :value="it.id">
                                {{ it.name }} (SKU: {{ it.sku }}) — {{ it.available_quantity }} Available
                            </option>
                        </select>
                    </div>

                    <!-- Staff Search Input -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Select Assignee Staff Member *</label>
                        <div v-if="selectedStaff" class="p-3 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 rounded-xl flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <CheckCircle2 class="w-5 h-5 text-indigo-600" />
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white text-xs">{{ selectedStaff.name }}</p>
                                    <p class="text-[10px] text-slate-500">{{ selectedStaff.email }}</p>
                                </div>
                            </div>
                            <button type="button" @click="clearSelectedStaff" class="text-xs text-indigo-600 font-bold hover:underline">✕ Change</button>
                        </div>
                        <div v-else class="relative">
                            <input
                                v-model="staffSearchQuery"
                                @input="searchStaff"
                                type="text"
                                placeholder="Type staff name or email to search..."
                                class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs"
                            />
                            <div v-if="staffSearchResults.length > 0" class="absolute z-10 top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                <div
                                    v-for="st in staffSearchResults"
                                    :key="st.id"
                                    @click="selectStaffMember(st)"
                                    class="p-2.5 hover:bg-indigo-50 dark:hover:bg-slate-700 cursor-pointer border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center"
                                >
                                    <div>
                                        <p class="font-bold text-xs text-slate-900 dark:text-white">{{ st.name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ st.email }}</p>
                                    </div>
                                    <span class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold">Select →</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Condition on Issue</label>
                            <select v-model="assignForm.condition_on_assignment" class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs">
                                <option value="brand_new">Brand New</option>
                                <option value="good">Good Condition</option>
                                <option value="fair">Fair / Operational</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Expected Return Date</label>
                            <input v-model="assignForm.expected_return_date" type="date" class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs" />
                            <span class="text-[10px] text-slate-400">Leave blank for permanent issue</span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="showAssignModal = false" class="px-4 py-2 font-bold text-slate-500">Cancel</button>
                        <button type="submit" :disabled="!assignForm.staff_id" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-lg disabled:opacity-50 hover:bg-indigo-500">Confirm Assignment</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- RETURN ITEM MODAL -->
        <div v-if="showReturnModal && selectedAssignment" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="font-black text-slate-900 dark:text-white text-base">Process Equipment Return</h3>
                    <button @click="showReturnModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="submitReturnItem" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Return Outcome *</label>
                        <select v-model="returnForm.status" required class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-bold">
                            <option value="returned">Returned to Central Store (Stock +1)</option>
                            <option value="damaged">Damaged / Requires Repair</option>
                            <option value="lost">Lost / Unreturned</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Condition on Return</label>
                        <input v-model="returnForm.condition_on_return" type="text" placeholder="e.g. Good condition, minor scratch" class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showReturnModal = false" class="px-4 py-2 text-xs font-bold text-slate-500">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-md hover:bg-emerald-500">Complete Return</button>
                    </div>
                </form>
            </div>
        </div>

    </AdminLayout>
</template>
