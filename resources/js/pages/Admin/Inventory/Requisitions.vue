<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    FileText, Plus, Search, Filter, CheckCircle2, XCircle, 
    Download, Clock, Check, X, ShieldAlert, ArrowLeft,
    Sparkles, Building2, Layers, AlertTriangle, FileSpreadsheet,
    Inbox, CheckSquare, XSquare, Send
} from 'lucide-vue-next';

const props = defineProps<{
    requisitions: {
        data: Array<{
            id: number;
            requisition_number: string;
            status: 'pending' | 'approved' | 'issued' | 'rejected';
            notes?: string;
            created_at: string;
            user?: { name: string; email: string };
            department?: { name: string };
            approved_by?: { name: string };
            items?: Array<{
                id: number;
                requested_quantity: number;
                unit_of_measure: string;
                item?: { name: string; sku?: string };
            }>;
        }>;
        links: Array<any>;
        total: number;
    };
    items?: Array<{
        id: number;
        name: string;
        sku?: string;
        available_quantity: number;
        unit_of_measure: string;
    }>;
    departments?: Array<{
        id: number;
        name: string;
    }>;
    filters?: {
        search?: string;
        status?: string;
    };
    permissions?: {
        can_approve?: boolean;
        can_create?: boolean;
    };
}>();

const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'ALL');

const stats = computed(() => {
    const list = props.requisitions.data || [];
    return {
        total: props.requisitions.total || list.length,
        pending: list.filter(r => r.status === 'pending').length,
        approved: list.filter(r => r.status === 'approved' || r.status === 'issued').length,
        rejected: list.filter(r => r.status === 'rejected').length,
    };
});

const handleSearch = () => {
    router.get(
        route('admin.inventory.requisitions.index'),
        { search: searchQuery.value, status: statusFilter.value },
        { preserveState: true, replace: true }
    );
};

// Modal Controls
const showRequisitionModal = ref(false);
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedRequisition = ref<any>(null);

// Create Requisition Form
const requisitionForm = useForm({
    department_id: '',
    notes: '',
    items: [
        { inventory_item_id: props.items && props.items.length > 0 ? String(props.items[0].id) : '', requested_quantity: 1 }
    ]
});

const openRequisitionModal = () => {
    requisitionForm.reset();
    if (props.items && props.items.length > 0) {
        requisitionForm.items = [{ inventory_item_id: String(props.items[0].id), requested_quantity: 1 }];
    }
    showRequisitionModal.value = true;
};

const addRequisitionItem = () => {
    requisitionForm.items.push({ 
        inventory_item_id: props.items && props.items.length > 0 ? String(props.items[0].id) : '', 
        requested_quantity: 1 
    });
};

const removeRequisitionItem = (index: number) => {
    if (requisitionForm.items.length > 1) {
        requisitionForm.items.splice(index, 1);
    }
};

const submitRequisition = () => {
    requisitionForm.post(route('admin.inventory.requisitions.store'), {
        onSuccess: () => {
            showRequisitionModal.value = false;
            requisitionForm.reset();
        },
    });
};

// Approval & Rejection Forms
const approveForm = useForm({ admin_notes: '' });
const rejectForm = useForm({ rejection_reason: '' });

const openApprove = (req: any) => {
    selectedRequisition.value = req;
    approveForm.reset();
    showApproveModal.value = true;
};

const openReject = (req: any) => {
    selectedRequisition.value = req;
    rejectForm.reset();
    showRejectModal.value = true;
};

const submitApprove = () => {
    approveForm.post(route('admin.inventory.requisitions.approve', selectedRequisition.value.id), {
        onSuccess: () => {
            showApproveModal.value = false;
        },
    });
};

const submitReject = () => {
    rejectForm.post(route('admin.inventory.requisitions.reject', selectedRequisition.value.id), {
        onSuccess: () => {
            showRejectModal.value = false;
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
    <Head title="Store Issue Requisitions (SIV) — Central Store" />

    <AdminLayout>
        <div class="py-8 min-h-screen bg-slate-50/50 dark:bg-slate-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Hero Section -->
                <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-2xl border border-slate-800">
                    <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-semibold">
                                <FileText class="w-3.5 h-3.5 text-indigo-400" />
                                Store Issue Vouchers (SIV Ledger)
                            </div>
                            <h1 class="text-3xl sm:text-4xl font-black tracking-tight">
                                Store Issue Requisitions (SIV)
                            </h1>
                            <p class="text-slate-300 text-sm max-w-2xl leading-relaxed">
                                Review, approve, and dispatch official Store Issue Vouchers for departmental supplies. Track stock allocation and generate printable PDF vouchers.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <button
                                v-if="permissions?.can_create || true"
                                @click="openRequisitionModal"
                                class="inline-flex items-center px-5 py-3 rounded-2xl text-xs font-extrabold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 shadow-xl shadow-indigo-500/20 active:scale-95 transition-all border border-indigo-400/30 gap-2"
                            >
                                <Plus class="w-4 h-4" />
                                Raise New Store Requisition (SIV)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI Metric Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Requisitions</p>
                                <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ stats.total }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                                <Inbox class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Approval</p>
                                <p class="text-3xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ stats.pending }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400 font-bold">
                                <Clock class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Approved & Issued</p>
                                <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.approved }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold">
                                <CheckSquare class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Declined Requisitions</p>
                                <p class="text-3xl font-black text-red-600 dark:text-red-400 mt-1">{{ stats.rejected }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-red-50 dark:bg-red-950/50 flex items-center justify-center text-red-600 dark:text-red-400 font-bold">
                                <XSquare class="w-6 h-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search Bar -->
                <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="relative w-full sm:w-80">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text"
                            placeholder="Search voucher # or requester..."
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
                                <option value="pending">Pending Approval</option>
                                <option value="approved">Approved</option>
                                <option value="issued">Issued / Dispatched</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Requisitions Datatable -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50/80 dark:bg-slate-900/80">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">SIV Voucher #</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Requester & Dept</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Items & Quantity</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Requested Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Status</th>
                                    <th class="px-6 py-4 text-right text-xs font-extrabold text-slate-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <tr v-for="req in requisitions.data" :key="req.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-mono font-black text-indigo-600 dark:text-indigo-400 text-xs">
                                            {{ req.requisition_number }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5 font-medium">Official SIV Voucher</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 dark:text-white text-xs">
                                            {{ req.user?.name || 'Store Requester' }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            {{ req.department?.name || 'General University Dept' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="space-y-1">
                                            <div v-for="it in req.items" :key="it.id" class="text-xs text-slate-700 dark:text-slate-300 font-semibold flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                                <strong>{{ it.requested_quantity }} {{ it.unit_of_measure }}</strong> of {{ it.item?.name || 'Store Supply' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 font-medium">
                                        {{ formatDate(req.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            class="px-3 py-1 rounded-full text-xs font-extrabold capitalize border"
                                            :class="{
                                                'bg-amber-50 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800': req.status === 'pending',
                                                'bg-blue-50 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800': req.status === 'approved',
                                                'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800': req.status === 'issued',
                                                'bg-red-50 text-red-800 dark:bg-red-950/60 dark:text-red-300 border-red-200 dark:border-red-800': req.status === 'rejected',
                                            }"
                                        >
                                            {{ req.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                        <a
                                            :href="route('admin.inventory.requisitions.voucher', req.id)"
                                            target="_blank"
                                            class="px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-300 font-bold inline-flex items-center gap-1.5 transition-all border border-indigo-200 dark:border-indigo-800"
                                        >
                                            <Download class="w-3.5 h-3.5" /> SIV PDF
                                        </a>

                                        <template v-if="req.status === 'pending' && (permissions?.can_approve || true)">
                                            <button 
                                                @click="openApprove(req)"
                                                class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-500 font-bold transition-all shadow-sm"
                                            >
                                                Approve
                                            </button>
                                            <button 
                                                @click="openReject(req)"
                                                class="px-3 py-1.5 rounded-xl bg-red-600 text-white hover:bg-red-500 font-bold transition-all shadow-sm"
                                            >
                                                Reject
                                            </button>
                                        </template>
                                    </td>
                                </tr>
                                <tr v-if="!requisitions.data || requisitions.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
                                        No store issue requisitions found matching your filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="requisitions.links && requisitions.links.length > 3" class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <div class="flex gap-1">
                            <component
                                v-for="(link, i) in requisitions.links"
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

        <!-- NEW REQUISITION MODAL -->
        <div v-if="showRequisitionModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-6">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold shadow-lg shadow-indigo-500/30">
                            <FileText class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white">Raise New Requisition (SIV)</h3>
                            <p class="text-xs text-slate-500">Submit a Store Issue Voucher for departmental inventory dispatch.</p>
                        </div>
                    </div>
                    <button @click="showRequisitionModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>

                <form @submit.prevent="submitRequisition" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Target Department</label>
                        <select v-model="requisitionForm.department_id" class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 font-bold">
                            <option value="">General University Store</option>
                            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="font-bold text-slate-700 dark:text-slate-300">Requested Items & Quantities *</label>
                            <button type="button" @click="addRequisitionItem" class="text-xs font-bold text-indigo-600 hover:underline flex items-center gap-1">
                                <Plus class="w-3.5 h-3.5" /> Add Item Line
                            </button>
                        </div>
                        <div class="space-y-3 max-h-56 overflow-y-auto pr-1">
                            <div v-for="(it, idx) in requisitionForm.items" :key="idx" class="flex items-center gap-3 bg-slate-50 dark:bg-slate-800/60 p-3 rounded-2xl border border-slate-200 dark:border-slate-700">
                                <select v-model="it.inventory_item_id" required class="flex-1 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold">
                                    <option v-for="item in items" :key="item.id" :value="String(item.id)">
                                        {{ item.name }} (Available: {{ item.available_quantity }} {{ item.unit_of_measure }})
                                    </option>
                                </select>
                                <input v-model.number="it.requested_quantity" type="number" min="1" required placeholder="Qty" class="w-20 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-center" />
                                <button type="button" @click="removeRequisitionItem(idx)" class="text-slate-400 hover:text-red-600 p-1"><X class="w-4 h-4" /></button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Requisition Purpose / Notes</label>
                        <textarea v-model="requisitionForm.notes" rows="2" placeholder="State purpose for requisition..." class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="showRequisitionModal = false" class="px-4 py-2 font-bold text-slate-500">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-500">Submit SIV Requisition</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- APPROVE MODAL -->
        <div v-if="showApproveModal && selectedRequisition" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="font-black text-slate-900 dark:text-white text-base">Approve Requisition #{{ selectedRequisition.requisition_number }}</h3>
                    <button @click="showApproveModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <p class="text-xs text-slate-500">Confirm approval for store dispatch. Stock levels will automatically be adjusted.</p>
                <form @submit.prevent="submitApprove" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Store Officer Notes</label>
                        <textarea v-model="approveForm.admin_notes" rows="3" placeholder="Optional approval notes..." class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showApproveModal = false" class="px-4 py-2 text-xs font-bold text-slate-500">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-md">Confirm & Issue SIV</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- REJECT MODAL -->
        <div v-if="showRejectModal && selectedRequisition" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="font-black text-slate-900 dark:text-white text-base text-red-600">Reject Requisition #{{ selectedRequisition.requisition_number }}</h3>
                    <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="submitReject" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Rejection Reason *</label>
                        <textarea v-model="rejectForm.rejection_reason" required rows="3" placeholder="Explain why requisition was declined..." class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showRejectModal = false" class="px-4 py-2 text-xs font-bold text-slate-500">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-red-600 text-white rounded-xl text-xs font-bold shadow-md">Decline Requisition</button>
                    </div>
                </form>
            </div>
        </div>

    </AdminLayout>
</template>
