<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Package, 
    Plus, 
    Search, 
    Upload, 
    Trash2, 
    Edit, 
    Filter, 
    FolderTree, 
    UserPlus, 
    Check, 
    Download, 
    AlertTriangle,
    AlertCircle,
    TrendingUp,
    RefreshCw,
    FileText,
    DollarSign,
    Layers,
    Building2,
    CheckCircle2,
    XCircle,
    Clock,
    Activity,
    ArrowUpRight,
    ArrowDownRight,
    Tag,
    Inbox,
    Sparkles,
    ShieldCheck,
    X
} from 'lucide-vue-next';

const props = defineProps<{
    items: {
        data: Array<{
            id: number;
            name: string;
            sku: string;
            description?: string;
            inventory_category_id: number;
            category?: { name: string };
            total_quantity: number;
            available_quantity: number;
            min_stock_level: number;
            unit_of_measure: string;
            unit_cost?: number;
            item_type: 'consumable' | 'reusable';
            supplier_name?: string;
            location?: string;
            condition: string;
            is_low_stock?: boolean;
        }>;
        links: any;
        total: number;
    };
    categories: Array<{ 
        id: number; 
        name: string; 
        description: string;
        items_count?: number;
    }>;
    filters: {
        search?: string;
        category_id?: string;
    };
    complaints_count: number;
    stats: {
        total_items: number;
        available_items: number;
        assigned_items: number;
        categories_count: number;
        low_stock_count: number;
        total_valuation: number;
        pending_requisitions: number;
        pending_complaints: number;
    };
    recent_logs?: Array<{
        id: number;
        type: 'restock' | 'issue' | 'return' | 'adjustment' | 'damage';
        quantity: number;
        notes?: string;
        created_at: string;
        item?: { name: string; unit_of_measure: string };
        user?: { name: string };
    }>;
    recent_requisitions?: Array<{
        id: number;
        requisition_number: string;
        status: 'pending' | 'approved' | 'issued' | 'rejected';
        notes?: string;
        created_at: string;
        user?: { name: string; email: string };
        department?: { name: string };
        items?: Array<{
            id: number;
            requested_quantity: number;
            unit_of_measure: string;
            item?: { name: string; sku?: string };
        }>;
    }>;
    recent_assignments?: Array<{
        id: number;
        inventory_item_id: number;
        assigned_at: string;
        expected_return_date?: string;
        returned_at?: string;
        status: 'assigned' | 'returned' | 'lost' | 'damaged';
        condition_on_assignment?: string;
        condition_on_return?: string;
        item?: { id: number; name: string; sku?: string; unit_of_measure: string };
        assignable?: { id: string; user?: { name: string; email: string }; department?: { name: string } };
    }>;
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

const userPermissions = computed(() => ({
    can_manage: props.permissions?.can_manage ?? true,
    can_create: props.permissions?.can_create ?? true,
    can_edit: props.permissions?.can_edit ?? true,
    can_delete: props.permissions?.can_delete ?? true,
    can_restock: props.permissions?.can_restock ?? true,
    can_create_requisition: props.permissions?.can_create_requisition ?? true,
    can_approve_requisition: props.permissions?.can_approve_requisition ?? true,
}));

const currentTab = ref('items');
const activeQuickFilter = ref('ALL');
const showAddModal = ref(false);
const showEditModal = ref(false);
const showImportModal = ref(false);
const showCategoryModal = ref(false);
const showAssignModal = ref(false);
const showRestockModal = ref(false);
const showRequisitionModal = ref(false);
const showComplaintModal = ref(false);
const showDeploymentModal = ref(false);

const selectedItem = ref<any>(null);
const selectedComplaint = ref<any>(null);

const openDeploymentModal = (item: any) => {
    selectedItem.value = item;
    showDeploymentModal.value = true;
};

const form = useForm({
    name: '',
    inventory_category_id: '',
    description: '',
    sku: '',
    total_quantity: 0,
    unit_of_measure: 'pieces',
    min_stock_level: 5,
    unit_cost: 0,
    item_type: 'consumable',
    supplier_name: '',
    location: '',
    condition: 'new',
});

const editForm = useForm({
    name: '',
    inventory_category_id: '',
    description: '',
    sku: '',
    total_quantity: 0,
    unit_of_measure: 'pieces',
    min_stock_level: 5,
    unit_cost: 0,
    item_type: 'consumable',
    supplier_name: '',
    location: '',
    condition: 'new',
});

const restockForm = useForm({
    quantity: 10,
    unit_cost: 0,
    supplier_name: '',
    notes: '',
});

const requisitionForm = useForm({
    department_id: '',
    notes: '',
    items: [
        { inventory_item_id: '', requested_quantity: 1 }
    ]
});

const importForm = useForm({ file: null as File | null });
const categoryForm = useForm({ name: '', description: '' });
const assignForm = useForm({
    inventory_item_id: '',
    staff_id: '',
    expected_return_date: '',
    condition_on_assignment: 'new',
});
const complaintUpdateForm = useForm({ status: 'pending', admin_notes: '' });

const staffSearchQuery = ref('');
const staffSearchResults = ref<Array<{id: string, name: string}>>([]);
const complaints = ref<any>({ data: [] });

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category_id || '');

const unitsOfMeasure = [
    'bags', 'litres', 'gallons', 'cartons', 'packs', 'reams', 'pieces', 'rolls', 'meters', 'kg'
];

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val || 0);
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return 'N/A';
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const filteredItems = computed(() => {
    let list = props.items.data;
    if (activeQuickFilter.value === 'LOW_STOCK') {
        list = list.filter(i => i.available_quantity <= i.min_stock_level);
    } else if (activeQuickFilter.value === 'CONSUMABLE') {
        list = list.filter(i => i.item_type === 'consumable');
    } else if (activeQuickFilter.value === 'EQUIPMENT') {
        list = list.filter(i => i.item_type === 'reusable');
    } else if (activeQuickFilter.value === 'OUT_OF_STOCK') {
        list = list.filter(i => i.available_quantity <= 0);
    }
    return list;
});

const estimatedRestockCost = computed(() => {
    return (restockForm.quantity || 0) * (restockForm.unit_cost || 0);
});

const handleSearch = () => {
    router.get(
        '/admin/inventory',
        { search: search.value, category_id: categoryFilter.value },
        { preserveState: true, replace: true }
    );
};

const openAddModal = () => {
    form.reset();
    showAddModal.value = true;
};

const submitAdd = () => {
    form.post('/admin/inventory', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};

const openEditModal = (item: any) => {
    selectedItem.value = item;
    editForm.name = item.name;
    editForm.inventory_category_id = item.inventory_category_id;
    editForm.description = item.description || '';
    editForm.sku = item.sku || '';
    editForm.total_quantity = item.total_quantity;
    editForm.unit_of_measure = item.unit_of_measure || 'pieces';
    editForm.min_stock_level = item.min_stock_level ?? 5;
    editForm.unit_cost = item.unit_cost || 0;
    editForm.item_type = item.item_type || 'consumable';
    editForm.supplier_name = item.supplier_name || '';
    editForm.location = item.location || '';
    editForm.condition = item.condition || 'new';
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(`/admin/inventory/${selectedItem.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
        },
    });
};

const openRestockModal = (item: any) => {
    selectedItem.value = item;
    restockForm.quantity = 10;
    restockForm.unit_cost = item.unit_cost || 0;
    restockForm.supplier_name = item.supplier_name || '';
    restockForm.notes = 'Store replenishment';
    showRestockModal.value = true;
};

const submitRestock = () => {
    restockForm.post(`/admin/inventory/${selectedItem.value.id}/restock`, {
        onSuccess: () => {
            showRestockModal.value = false;
            restockForm.reset();
        },
    });
};

const deleteItem = (id: number) => {
    if (confirm('Are you sure you want to soft delete this store item? Historical records will be preserved.')) {
        router.delete(`/admin/inventory/${id}`);
    }
};

const submitImport = () => {
    importForm.post('/admin/inventory/import', {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const submitCategory = () => {
    categoryForm.post('/admin/inventory/categories', {
        onSuccess: () => {
            showCategoryModal.value = false;
            categoryForm.reset();
        },
    });
};

const selectedStaff = ref<any>(null);

const showReturnModal = ref(false);
const selectedAssignment = ref<any>(null);

const returnForm = useForm({
    status: 'returned',
    condition_on_return: 'good',
});

const openReturnModal = (assignment: any) => {
    selectedAssignment.value = assignment;
    returnForm.status = 'returned';
    returnForm.condition_on_return = assignment.condition_on_assignment || 'good';
    showReturnModal.value = true;
};

const submitReturnItem = () => {
    returnForm.put(`/admin/inventory/assignments/${selectedAssignment.value.id}/return`, {
        onSuccess: () => {
            showReturnModal.value = false;
            returnForm.reset();
        },
    });
};

const openAssignModal = (item: any = null) => {
    const targetItem = item || (items.value?.data && items.value.data.length > 0 ? items.value.data[0] : null);
    selectedItem.value = targetItem;
    if (targetItem) {
        assignForm.inventory_item_id = targetItem.id;
        assignForm.condition_on_assignment = targetItem.condition || 'good';
    }
    assignForm.staff_id = '';
    assignForm.expected_return_date = '';
    selectedStaff.value = null;
    staffSearchQuery.value = '';
    staffSearchResults.value = [];
    showAssignModal.value = true;
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

const searchStaff = async () => {
    if (staffSearchQuery.value.length < 2) {
        staffSearchResults.value = [];
        return;
    }
    const response = await fetch(`/admin/inventory/staff/search?query=${staffSearchQuery.value}`);
    staffSearchResults.value = await response.json();
};

const submitAssign = () => {
    if (!assignForm.staff_id) {
        alert('Please search and select a valid staff member first.');
        return;
    }
    assignForm.post('/admin/inventory/assignments', {
        onSuccess: () => {
            showAssignModal.value = false;
            assignForm.reset();
            selectedStaff.value = null;
        },
    });
};

const openRequisitionModal = () => {
    requisitionForm.reset();
    requisitionForm.items = [{ inventory_item_id: props.items.data[0]?.id ? String(props.items.data[0].id) : '', requested_quantity: 1 }];
    showRequisitionModal.value = true;
};

const addRequisitionItem = () => {
    requisitionForm.items.push({ inventory_item_id: '', requested_quantity: 1 });
};

const removeRequisitionItem = (index: number) => {
    if (requisitionForm.items.length > 1) {
        requisitionForm.items.splice(index, 1);
    }
};

const submitRequisition = () => {
    requisitionForm.post('/admin/inventory/requisitions', {
        onSuccess: () => {
            showRequisitionModal.value = false;
            requisitionForm.reset();
        },
    });
};

const approveRequisition = (id: number) => {
    if (confirm('Approve this store requisition and issue items? Stock will be automatically deducted.')) {
        router.post(`/admin/inventory/requisitions/${id}/approve`);
    }
};

const rejectRequisition = (id: number) => {
    const notes = prompt('Enter rejection reason:');
    if (notes !== null) {
        router.post(`/admin/inventory/requisitions/${id}/reject`, { notes });
    }
};

const downloadVoucher = (id: number) => {
    window.open(`/admin/inventory/requisitions/${id}/voucher`, '_blank');
};

const switchTab = (tab: string) => {
    currentTab.value = tab;
    if (tab === 'complaints') {
        fetchComplaints();
    }
};

const fetchComplaints = async () => {
    const response = await fetch('/admin/inventory/complaints');
    complaints.value = await response.json();
};

const openComplaintModal = (complaint: any) => {
    selectedComplaint.value = complaint;
    complaintUpdateForm.status = complaint.status;
    complaintUpdateForm.admin_notes = complaint.admin_notes || '';
    showComplaintModal.value = true;
};

const submitComplaintUpdate = () => {
    complaintUpdateForm.put(`/admin/inventory/complaints/${selectedComplaint.value.id}`, {
        onSuccess: () => {
            showComplaintModal.value = false;
            fetchComplaints();
        },
    });
};
</script>

<template>
    <Head title="Central Store & Inventory Control" />

    <AdminLayout>
        <div class="py-8 min-h-screen bg-slate-50/50 dark:bg-slate-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Top Hero Banner -->
                <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-xl mb-8 border border-slate-800">
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-semibold">
                                <Sparkles class="w-3.5 h-3.5 text-indigo-400" />
                                Central Store & Inventory System
                            </div>
                            <h1 class="text-3xl sm:text-4xl font-black tracking-tight">
                                University Store Supplies & Inventory
                            </h1>
                            <p class="text-slate-300 text-sm max-w-2xl leading-relaxed">
                                Monitor consumable supplies (Cement, Detergents, Paper Reams, Electricals), handle departmental store requisitions (SIV), and prevent stockouts with automated reorder triggers.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2.5 shrink-0">
                            <button
                                v-if="userPermissions.can_create_requisition"
                                @click="openRequisitionModal"
                                class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white/80 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all shadow-sm border border-slate-200/80 dark:border-slate-700/80"
                            >
                                <FileText class="w-4 h-4 mr-2 text-indigo-600" />
                                New Requisition (SIV)
                            </button>
                            <button
                                v-if="userPermissions.can_manage"
                                @click="openAssignModal(items.data && items.data.length > 0 ? items.data[0] : null)"
                                class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white/80 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all shadow-sm border border-slate-200/80 dark:border-slate-700/80"
                            >
                                <UserPlus class="w-4 h-4 mr-2 text-indigo-600" />
                                Assign to Staff
                            </button>
                            <button
                                v-if="userPermissions.can_create"
                                @click="openAddModal"
                                class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-all shadow-md active:scale-95 border border-indigo-400/30"
                            >
                                <Plus class="w-4 h-4 mr-2" />
                                Add Store Item
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert Banner -->
                <div v-if="stats.low_stock_count > 0" class="mb-8 p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-500/30 backdrop-blur-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="h-10 w-10 rounded-xl bg-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                            <AlertTriangle class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 dark:text-amber-200 text-sm">
                                Attention: {{ stats.low_stock_count }} store item(s) are below reorder threshold!
                            </p>
                            <p class="text-xs text-slate-600 dark:text-amber-300/80 mt-0.5">
                                Essential supplies like detergent cartons or cement bags require restocking to prevent facility delays.
                            </p>
                        </div>
                    </div>
                    <button 
                        @click="activeQuickFilter = 'LOW_STOCK'; currentTab = 'items'" 
                        class="text-xs font-bold px-3 py-1.5 rounded-lg bg-amber-500/20 text-amber-800 dark:text-amber-200 hover:bg-amber-500/30 transition-colors shrink-0 self-start sm:self-auto"
                    >
                        Filter Low Stock Items →
                    </button>
                </div>

                <!-- Metric Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                    <!-- Total Available Stock -->
                    <div class="bg-white dark:bg-slate-800/80 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Available Stock</p>
                                <p class="text-3xl font-black text-slate-900 dark:text-white">{{ stats.available_items }}</p>
                                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                    <Package class="w-3.5 h-3.5 text-indigo-500" />
                                    <span>Across {{ stats.total_items }} total units</span>
                                </div>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                <Package class="h-6 w-6" />
                            </div>
                        </div>
                    </div>

                    <!-- Store Valuation -->
                    <div class="bg-white dark:bg-slate-800/80 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Store Valuation</p>
                                <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(stats.total_valuation) }}</p>
                                <div class="flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                                    <TrendingUp class="w-3.5 h-3.5" />
                                    <span>Est. monetary inventory value</span>
                                </div>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                <DollarSign class="h-6 w-6" />
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="bg-white dark:bg-slate-800/80 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Low Stock Items</p>
                                <p class="text-3xl font-black text-amber-600 dark:text-amber-400">{{ stats.low_stock_count }}</p>
                                <div class="flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400 font-semibold">
                                    <AlertTriangle class="w-3.5 h-3.5" />
                                    <span>Reorder threshold breached</span>
                                </div>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                <AlertTriangle class="h-6 w-6" />
                            </div>
                        </div>
                    </div>

                    <!-- Pending Requisitions -->
                    <div class="bg-white dark:bg-slate-800/80 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 hover:shadow-md transition-all">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Requisitions</p>
                                <p class="text-3xl font-black text-blue-600 dark:text-blue-400">{{ stats.pending_requisitions }}</p>
                                <div class="flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 font-semibold">
                                    <Clock class="w-3.5 h-3.5" />
                                    <span>Awaiting Store SIV issuance</span>
                                </div>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                <FileText class="h-6 w-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STORE ITEMS DIRECTORY -->
                <div class="space-y-6">
                    
                    <!-- Smart Quick Filter Pills & Search -->
                    <div class="bg-white dark:bg-slate-800 p-4 sm:p-5 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 flex flex-col lg:flex-row gap-4 justify-between items-stretch lg:items-center">
                        
                        <!-- Quick Pills -->
                        <div class="flex flex-wrap items-center gap-2">
                            <button 
                                @click="activeQuickFilter = 'ALL'"
                                :class="activeQuickFilter === 'ALL' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-200'"
                                class="px-3 py-1.5 rounded-xl text-xs transition-all"
                            >
                                All Store Items
                            </button>
                            <button 
                                @click="activeQuickFilter = 'LOW_STOCK'"
                                :class="activeQuickFilter === 'LOW_STOCK' ? 'bg-amber-600 text-white font-bold' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-medium border border-amber-200 dark:border-amber-800'"
                                class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all"
                            >
                                <AlertTriangle class="w-3.5 h-3.5" />
                                Low Stock Alert ({{ stats.low_stock_count }})
                            </button>
                            <button 
                                @click="activeQuickFilter = 'CONSUMABLE'"
                                :class="activeQuickFilter === 'CONSUMABLE' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-200'"
                                class="px-3 py-1.5 rounded-xl text-xs transition-all"
                            >
                                📦 Consumables (Cement, Detergents)
                            </button>
                            <button 
                                @click="activeQuickFilter = 'EQUIPMENT'"
                                :class="activeQuickFilter === 'EQUIPMENT' ? 'bg-purple-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-200'"
                                class="px-3 py-1.5 rounded-xl text-xs transition-all"
                            >
                                🛠️ Equipment / Reusables
                            </button>
                        </div>

                        <!-- Search & Category Filters -->
                        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                            <div class="relative flex-1 sm:w-64">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <input
                                    v-model="search"
                                    @input="handleSearch"
                                    type="text"
                                    placeholder="Search by name, SKU..."
                                    class="w-full pl-9 pr-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:bg-slate-900 dark:text-white"
                                />
                            </div>
                            <select
                                v-model="categoryFilter"
                                @change="handleSearch"
                                class="px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 dark:bg-slate-900 dark:text-white"
                            >
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Items Data Table -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50/80 dark:bg-slate-900/80">
                                    <tr>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Item & Location</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider min-w-[200px]">Stock Level Bar</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Unit Cost</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40 transition-colors">
                                        
                                        <!-- Item & Location -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-sm">
                                                {{ item.name }}
                                                <span v-if="item.item_type === 'consumable'" class="text-[9px] bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200 dark:border-blue-800 px-2 py-0.5 rounded-full font-bold uppercase">Consumable</span>
                                                <span v-else class="text-[9px] bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 border border-purple-200 dark:border-purple-800 px-2 py-0.5 rounded-full font-bold uppercase">Equipment</span>
                                            </div>
                                            <div class="text-xs text-slate-400 flex items-center gap-3 mt-1 font-medium">
                                                <span v-if="item.sku" class="font-mono bg-slate-100 dark:bg-slate-700/50 px-1.5 py-0.5 rounded text-[10px]">SKU: {{ item.sku }}</span>
                                                <span v-if="item.location" class="flex items-center gap-1 text-[11px]"><Building2 class="w-3 h-3 text-slate-400" /> {{ item.location }}</span>
                                            </div>
                                        </td>

                                        <!-- Category -->
                                        <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-slate-600 dark:text-slate-300">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700/50">
                                                <Tag class="w-3 h-3 text-slate-400" />
                                                {{ item.category?.name || 'General Store' }}
                                            </span>
                                        </td>

                                        <!-- Stock Level Visual Progress Bar -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="font-extrabold text-slate-900 dark:text-white">
                                                        {{ item.available_quantity }} <span class="font-normal text-slate-500 capitalize">{{ item.unit_of_measure }}</span>
                                                    </span>
                                                    <span class="text-[10px] text-slate-400 font-mono">
                                                        Min: {{ item.min_stock_level }}
                                                    </span>
                                                </div>
                                                <!-- Visual Progress Bar -->
                                                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                                    <div 
                                                        class="h-2 rounded-full transition-all duration-500"
                                                        :class="{
                                                            'bg-emerald-500': item.available_quantity > item.min_stock_level,
                                                            'bg-amber-500': item.available_quantity > 0 && item.available_quantity <= item.min_stock_level,
                                                            'bg-red-500': item.available_quantity <= 0
                                                        }"
                                                        :style="{ width: Math.min(100, Math.max(5, (item.available_quantity / (item.total_quantity || 1)) * 100)) + '%' }"
                                                    ></div>
                                                </div>
                                                <button 
                                                    @click="openDeploymentModal(item)" 
                                                    class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 pt-0.5"
                                                    title="View lab and departmental distribution"
                                                >
                                                    <Building2 class="w-3 h-3" /> Track Lab Deployments ({{ item.total_quantity - item.available_quantity }} issued)
                                                </button>
                                            </div>
                                        </td>

                                        <!-- Unit Cost -->
                                        <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-900 dark:text-white">
                                            {{ item.unit_cost ? formatCurrency(item.unit_cost) : '—' }}
                                        </td>

                                        <!-- Stock Status Badge -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="item.available_quantity <= 0" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300 border border-red-200 dark:border-red-800">
                                                <XCircle class="w-3.5 h-3.5" /> Out of Stock
                                            </span>
                                            <span v-else-if="item.available_quantity <= item.min_stock_level" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                <AlertTriangle class="w-3.5 h-3.5" /> Low Stock
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                <CheckCircle2 class="w-3.5 h-3.5" /> In Stock
                                            </span>
                                        </td>

                                        <!-- Row Action Buttons -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium">
                                            <div class="flex items-center justify-end gap-2">
                                                <button 
                                                    v-if="userPermissions.can_restock" 
                                                    @click="openRestockModal(item)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 font-bold transition-all" 
                                                    title="Restock Item (+)"
                                                >
                                                    + Restock
                                                </button>
                                                <button 
                                                    v-if="userPermissions.can_manage" 
                                                    @click="openAssignModal(item)" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 font-bold transition-all" 
                                                    title="Assign Equipment to Staff"
                                                >
                                                    <UserPlus class="w-3.5 h-3.5" /> Assign
                                                </button>
                                                <button 
                                                    v-if="userPermissions.can_edit" 
                                                    @click="openEditModal(item)" 
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" 
                                                    title="Edit Item"
                                                >
                                                    <Edit class="w-4 h-4" />
                                                </button>
                                                <button 
                                                    v-if="userPermissions.can_delete" 
                                                    @click="deleteItem(item.id)" 
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors" 
                                                    title="Soft Delete Item"
                                                >
                                                    <Trash2 class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredItems.length === 0">
                                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
                                            <Inbox class="w-10 h-10 mx-auto mb-2 opacity-50" />
                                            <p class="font-bold text-sm text-slate-700 dark:text-slate-300">No store items match your current filter.</p>
                                            <p class="text-xs mt-1">Try clearing your filters or adding a new store item.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: STORE REQUISITIONS (SIV) -->
                <div v-if="currentTab === 'requisitions'" class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                        <div class="p-5 border-b border-slate-200/80 dark:border-slate-700/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white text-base">Departmental Store Issue Requisitions</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Approve store requests and generate official Store Issue Voucher (SIV) receipts.</p>
                            </div>
                            <button 
                                v-if="userPermissions.can_create_requisition"
                                @click="openRequisitionModal" 
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all shadow-sm"
                            >
                                + New Requisition
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50/80 dark:bg-slate-900/80">
                                    <tr>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Requisition #</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Requisitioner</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Items Requested</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <tr v-for="req in recent_requisitions" :key="req.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-indigo-600 dark:text-indigo-400 text-xs">
                                            #{{ req.requisition_number }}
                                            <div class="text-[10px] text-slate-400 font-sans font-normal mt-0.5">{{ formatDate(req.created_at) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs">
                                            <div class="font-bold text-slate-900 dark:text-white">{{ req.user?.name || 'Staff' }}</div>
                                            <div class="text-[11px] text-slate-500">{{ req.department?.name || 'General Department' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-600 dark:text-slate-300">
                                            <ul class="space-y-1">
                                                <li v-for="i in req.items" :key="i.id" class="flex items-center gap-1.5">
                                                    <span class="font-extrabold text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded text-[10px]">
                                                        {{ i.requested_quantity }} {{ i.unit_of_measure }}
                                                    </span>
                                                    <span>{{ i.item?.name }}</span>
                                                </li>
                                            </ul>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="req.status === 'issued' || req.status === 'approved'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200">
                                                <CheckCircle2 class="w-3.5 h-3.5" /> ISSUED
                                            </span>
                                            <span v-else-if="req.status === 'pending'" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-extrabold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200">
                                                <Clock class="w-3.5 h-3.5" /> PENDING
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-extrabold bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300 border border-red-200">
                                                <XCircle class="w-3.5 h-3.5" /> REJECTED
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium">
                                            <div class="flex items-center justify-end gap-2">
                                                <button 
                                                    v-if="req.status === 'pending' && userPermissions.can_approve_requisition" 
                                                    @click="approveRequisition(req.id)" 
                                                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition-all shadow-sm"
                                                >
                                                    Approve & Issue
                                                </button>
                                                <button 
                                                    v-if="req.status === 'pending' && userPermissions.can_approve_requisition" 
                                                    @click="rejectRequisition(req.id)" 
                                                    class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-bold transition-all shadow-sm"
                                                >
                                                    Reject
                                                </button>
                                                <button 
                                                    v-if="req.status === 'issued' || req.status === 'approved'" 
                                                    @click="downloadVoucher(req.id)" 
                                                    class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold transition-all flex items-center gap-1.5"
                                                >
                                                    <Download class="w-3.5 h-3.5" /> SIV PDF
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!recent_requisitions || recent_requisitions.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                            <Inbox class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                            No store requisitions recorded yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: STOCK AUDIT FEED LOG -->
                <div v-if="currentTab === 'activity'" class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white text-base flex items-center gap-2">
                                    <Activity class="w-5 h-5 text-indigo-600" />
                                    Stock In / Out Movement Audit Feed
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">Immutable record of every store stock replenishment and departmental issuance.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div v-for="log in recent_logs" :key="log.id" class="p-4 rounded-xl border border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-900/40 flex items-center justify-between">
                                <div class="flex items-center gap-3.5">
                                    <div 
                                        class="h-10 w-10 rounded-xl flex items-center justify-center font-bold shrink-0"
                                        :class="log.type === 'restock' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'"
                                    >
                                        <ArrowUpRight v-if="log.type === 'restock'" class="w-5 h-5" />
                                        <ArrowDownRight v-else class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-xs">
                                            <span class="capitalize font-extrabold">{{ log.type }}:</span> 
                                            {{ log.quantity }} {{ log.item?.unit_of_measure || 'units' }} of <strong>{{ log.item?.name }}</strong>
                                        </p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            By {{ log.user?.name || 'Store Manager' }} • {{ log.notes || 'System movement' }}
                                        </p>
                                    </div>
                                </div>
                                <span class="text-[10px] text-slate-400 font-mono shrink-0">
                                    {{ formatDate(log.created_at) }}
                                </span>
                            </div>
                            <div v-if="!recent_logs || recent_logs.length === 0" class="text-center py-8 text-slate-400 text-xs">
                                No stock movement audit records logged yet.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: CATEGORIES -->
                <div v-if="currentTab === 'categories'" class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                        <div class="p-5 border-b border-slate-200/80 dark:border-slate-700/80 flex justify-between items-center">
                            <h3 class="font-black text-slate-900 dark:text-white text-base">Store Categories</h3>
                            <button v-if="userPermissions.can_create" @click="showCategoryModal = true" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold">
                                + Add Category
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50/80 dark:bg-slate-900/80">
                                    <tr>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Category Name</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Description</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Total Items</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <tr v-for="cat in categories" :key="cat.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40">
                                        <td class="px-6 py-4 whitespace-nowrap font-bold text-slate-900 dark:text-white text-xs">
                                            {{ cat.name }}
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500">
                                            {{ cat.description || 'No description' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 font-semibold">
                                            {{ cat.items_count || 0 }} items
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: STAFF ASSIGNMENTS -->
                <div v-if="currentTab === 'assignments'" class="space-y-6">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                        <div class="p-5 border-b border-slate-200/80 dark:border-slate-700/80 flex justify-between items-center">
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white text-base">Staff Equipment Allocations</h3>
                                <p class="text-xs text-slate-500 mt-0.5">List of computers, printers, vehicles, and tools assigned to individual staff members.</p>
                            </div>
                            <button v-if="userPermissions.can_manage" @click="openAssignModal(null)" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md transition-all flex items-center gap-1.5">
                                <UserPlus class="w-4 h-4" />
                                Assign Equipment
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50/80 dark:bg-slate-900/80">
                                    <tr>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Equipment / Asset</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Assigned Staff Member</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Condition</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Issue & Return Schedule</th>
                                        <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Status</th>
                                        <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <tr v-for="asg in recent_assignments" :key="asg.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="h-9 w-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
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
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                            <div>Issued: <strong>{{ formatDate(asg.assigned_at) }}</strong></div>
                                            <div v-if="asg.expected_return_date" class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-0.5">
                                                Return By: {{ formatDate(asg.expected_return_date) }}
                                            </div>
                                            <div v-else class="text-[10px] text-slate-400">Permanent Issue</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                class="px-2.5 py-1 rounded-full text-xs font-bold capitalize"
                                                :class="{
                                                    'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300': asg.status === 'assigned',
                                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300': asg.status === 'returned',
                                                    'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300': asg.status === 'lost',
                                                    'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300': asg.status === 'damaged',
                                                }"
                                            >
                                                {{ asg.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium">
                                            <button 
                                                v-if="asg.status === 'assigned' && userPermissions.can_manage" 
                                                @click="openReturnModal(asg)" 
                                                class="px-3 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-200 font-bold transition-all"
                                            >
                                                Return Item
                                            </button>
                                            <span v-else class="text-slate-400 text-xs">—</span>
                                        </td>
                                    </tr>
                                    <tr v-if="!recent_assignments || recent_assignments.length === 0">
                                        <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
                                            No active staff equipment assignments logged yet. Click "Assign Equipment" above to assign a computer to a staff member.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL 1: ADD STORE ITEM -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800 relative space-y-6">
                
                <!-- Modal Header -->
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/25 shrink-0">
                            <Package class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Add New Store Supply Item</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Register new consumable supplies (detergents, cement, paper) or equipment into central inventory.</p>
                        </div>
                    </div>
                    <button @click="showAddModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-5 text-xs">
                    
                    <!-- Section 1: Item Basic Info -->
                    <div class="space-y-3">
                        <div class="text-[11px] font-extrabold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center gap-1.5">
                            <Tag class="w-3.5 h-3.5" /> Basic Information
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Item Name *</label>
                                <input v-model="form.name" required type="text" placeholder="e.g. Detergent Powder 1kg, Dangote Cement 50kg" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder:text-slate-400 text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Store Category *</label>
                                <select v-model="form.inventory_category_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm">
                                    <option value="">Select Store Category</option>
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Quantities & Measurement -->
                    <div class="space-y-3 pt-2">
                        <div class="text-[11px] font-extrabold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center gap-1.5">
                            <Layers class="w-3.5 h-3.5" /> Stock & Measurement Units
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Initial Stock Qty *</label>
                                <input v-model.number="form.total_quantity" required type="number" min="0" placeholder="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm font-bold" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Unit of Measure *</label>
                                <select v-model="form.unit_of_measure" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white capitalize text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm">
                                    <option v-for="u in unitsOfMeasure" :key="u" :value="u">{{ u }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Min Reorder Alert *</label>
                                <input v-model.number="form.min_stock_level" required type="number" min="0" placeholder="5" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Financial & Type -->
                    <div class="space-y-3 pt-2">
                        <div class="text-[11px] font-extrabold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center gap-1.5">
                            <DollarSign class="w-3.5 h-3.5" /> Financials & Product Type
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Unit Cost (₦)</label>
                                <input v-model.number="form.unit_cost" type="number" step="0.01" min="0" placeholder="0.00" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Item Classification</label>
                                <select v-model="form.item_type" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm">
                                    <option value="consumable">Consumable (Single-Use)</option>
                                    <option value="reusable">Equipment / Reusable</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">SKU / Code</label>
                                <input v-model="form.sku" type="text" placeholder="e.g. DET-001" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder:text-slate-400 text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm font-mono" />
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Supplier & Location -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Supplier Name</label>
                            <input v-model="form.supplier_name" type="text" placeholder="e.g. Dangote Cement / Unilever" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Store Location</label>
                            <input v-model="form.location" type="text" placeholder="e.g. Central Store Shelf B-3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200/80 dark:border-slate-800">
                        <button type="button" @click="showAddModal = false" class="px-5 py-2.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold text-xs transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-indigo-600/25 active:scale-95 transition-all">
                            Save Store Item
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: EDIT STORE ITEM -->
        <div v-if="showEditModal && selectedItem" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800 relative space-y-6">
                
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/25 shrink-0">
                            <Edit class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Edit Item: {{ selectedItem.name }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Update item specifications, unit pricing, or threshold levels.</p>
                        </div>
                    </div>
                    <button @click="showEditModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Item Name *</label>
                            <input v-model="editForm.name" required type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Store Category *</label>
                            <select v-model="editForm.inventory_category_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm">
                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Total Stock Qty *</label>
                            <input v-model.number="editForm.total_quantity" required type="number" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Unit of Measure *</label>
                            <select v-model="editForm.unit_of_measure" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white capitalize text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm">
                                <option v-for="u in unitsOfMeasure" :key="u" :value="u">{{ u }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Min Reorder Alert *</label>
                            <input v-model.number="editForm.min_stock_level" required type="number" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Unit Cost (₦)</label>
                            <input v-model.number="editForm.unit_cost" type="number" step="0.01" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Store Location</label>
                            <input v-model="editForm.location" type="text" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200/80 dark:border-slate-800">
                        <button type="button" @click="showEditModal = false" class="px-5 py-2.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold text-xs transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-indigo-600/25 active:scale-95 transition-all">
                            Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 3: RESTOCK (+50 Bags Cement) -->
        <div v-if="showRestockModal && selectedItem" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800 relative space-y-5">
                
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/25 shrink-0 font-bold text-xl">
                            +
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Restock: {{ selectedItem.name }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Current Stock: <strong>{{ selectedItem.available_quantity }} {{ selectedItem.unit_of_measure }}</strong></p>
                        </div>
                    </div>
                    <button @click="showRestockModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitRestock" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Quantity to Add ({{ selectedItem.unit_of_measure }}) *</label>
                        <input v-model.number="restockForm.quantity" required type="number" min="1" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 transition-all outline-none shadow-sm" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Unit Cost (₦)</label>
                        <input v-model.number="restockForm.unit_cost" type="number" step="0.01" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 transition-all outline-none shadow-sm" />
                    </div>

                    <!-- Live Restock Cost Calculation Preview -->
                    <div v-if="estimatedRestockCost > 0" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 flex justify-between items-center shadow-sm">
                        <span class="font-bold">Est. Purchase Cost:</span>
                        <span class="font-black text-base">{{ formatCurrency(estimatedRestockCost) }}</span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Supplier Name</label>
                        <input v-model="restockForm.supplier_name" type="text" placeholder="e.g. Dangote Cement Ltd" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 transition-all outline-none shadow-sm" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Notes / Delivery Note #</label>
                        <input v-model="restockForm.notes" type="text" placeholder="e.g. Restocked 50 bags via Delivery Note #4092" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 transition-all outline-none shadow-sm" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/80 dark:border-slate-800">
                        <button type="button" @click="showRestockModal = false" class="px-5 py-2.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold text-xs transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="restockForm.processing" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-emerald-600/25 active:scale-95 transition-all">
                            Add Stock (+)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 4: NEW STORE REQUISITION -->
        <div v-if="showRequisitionModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800 relative space-y-6">
                
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/25 shrink-0">
                            <FileText class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Create Store Issue Requisition</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Request store items for hostel maintenance, cleaning, or departmental operations.</p>
                        </div>
                    </div>
                    <button @click="showRequisitionModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitRequisition" class="space-y-5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Requisition Purpose / Remarks</label>
                        <input v-model="requisitionForm.notes" type="text" placeholder="e.g. Hostel maintenance cement & detergent supply" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                    </div>

                    <div class="space-y-3">
                        <label class="block font-bold text-slate-700 dark:text-slate-300">Requested Items</label>
                        <div v-for="(reqItem, idx) in requisitionForm.items" :key="idx" class="flex gap-2 items-center">
                            <select v-model="reqItem.inventory_item_id" required class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm">
                                <option value="">Select Store Item</option>
                                <option v-for="it in items.data" :key="it.id" :value="it.id">
                                    {{ it.name }} (Avail: {{ it.available_quantity }} {{ it.unit_of_measure }})
                                </option>
                            </select>
                            <input v-model.number="reqItem.requested_quantity" required type="number" min="1" placeholder="Qty" class="w-24 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                            <button type="button" @click="removeRequisitionItem(idx)" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded-xl transition-colors">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                        <button type="button" @click="addRequisitionItem" class="text-xs text-indigo-600 dark:text-indigo-400 font-bold hover:underline inline-flex items-center gap-1 mt-1">
                            + Add another item
                        </button>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200/80 dark:border-slate-800">
                        <button type="button" @click="showRequisitionModal = false" class="px-5 py-2.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold text-xs transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="requisitionForm.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-indigo-600/25 active:scale-95 transition-all">
                            Submit Requisition
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 5: ADD CATEGORY -->
        <div v-if="showCategoryModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800 relative space-y-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/25 shrink-0">
                            <FolderTree class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Create Store Category</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Organize items into categories (Building Materials, Cleaning Supplies, Stationery).</p>
                        </div>
                    </div>
                    <button @click="showCategoryModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCategory" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Category Name *</label>
                        <input v-model="categoryForm.name" required type="text" placeholder="e.g. Cleaning Supplies & Detergents" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                        <textarea v-model="categoryForm.description" rows="3" placeholder="Brief notes about items in this store category..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/80 dark:border-slate-800">
                        <button type="button" @click="showCategoryModal = false" class="px-5 py-2.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold text-xs transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="categoryForm.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-indigo-600/25 active:scale-95 transition-all">
                            Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 6: IMPORT CSV -->
        <div v-if="showImportModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800 relative space-y-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/25 shrink-0">
                            <Upload class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Bulk Import Store Items</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Upload a CSV or Excel file to import multiple inventory items.</p>
                        </div>
                    </div>
                    <button @click="showImportModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">CSV / Excel File (.csv, .xlsx) *</label>
                        <input type="file" required @change="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950 dark:file:text-indigo-300" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/80 dark:border-slate-800">
                        <button type="button" @click="showImportModal = false" class="px-5 py-2.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold text-xs transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="importForm.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-indigo-600/25 active:scale-95 transition-all">
                            Import Items
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 7: ASSIGN ITEM -->
        <div v-if="showAssignModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800 relative space-y-6">
                
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/25 shrink-0">
                            <UserPlus class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Assign Equipment to Staff</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Issue computer, printer, vehicle or tool to an individual staff member.</p>
                        </div>
                    </div>
                    <button @click="showAssignModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Selected Item Context Card -->
                <div v-if="selectedItem" class="p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm shrink-0">
                            💻
                        </div>
                        <div>
                            <p class="font-extrabold text-slate-900 dark:text-white text-xs">{{ selectedItem.name }}</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Tag/SKU: <span class="font-mono text-slate-700 dark:text-slate-300 font-bold">{{ selectedItem.sku || 'N/A' }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            {{ selectedItem.available_quantity }} Available
                        </span>
                    </div>
                </div>

                <form @submit.prevent="submitAssign" class="space-y-5 text-xs">
                    
                    <!-- Select Store Equipment (If switching item) -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Equipment / Asset *</label>
                        <select v-model="assignForm.inventory_item_id" @change="selectedItem = items.data.find((i: any) => i.id === assignForm.inventory_item_id) || selectedItem" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm">
                            <option v-for="it in items.data" :key="it.id" :value="it.id">
                                {{ it.name }} ({{ it.available_quantity }} {{ it.unit_of_measure }} available)
                            </option>
                        </select>
                    </div>

                    <!-- Staff Search & Selected Staff Card -->
                    <div class="space-y-2">
                        <label class="block font-bold text-slate-700 dark:text-slate-300">Assignee (Staff Member) *</label>
                        
                        <!-- Selected Staff Pill Card -->
                        <div v-if="selectedStaff" class="p-3.5 rounded-2xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-md">
                                    {{ selectedStaff.name ? selectedStaff.name.charAt(0) : 'S' }}
                                </div>
                                <div>
                                    <p class="font-black text-indigo-950 dark:text-indigo-200 text-xs flex items-center gap-1.5">
                                        <CheckCircle2 class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                        {{ selectedStaff.name }}
                                    </p>
                                    <p class="text-[11px] text-indigo-600 dark:text-indigo-400">Selected Assignee</p>
                                </div>
                            </div>
                            <button type="button" @click="clearSelectedStaff" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:text-red-600 hover:bg-white dark:hover:bg-slate-800 rounded-lg transition-all">
                                ✕ Change
                            </button>
                        </div>

                        <!-- Staff Search Input -->
                        <div v-else class="relative">
                            <input 
                                v-model="staffSearchQuery" 
                                @input="searchStaff" 
                                type="text" 
                                placeholder="Type staff member name (e.g. Dr. Auwal)..." 
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" 
                            />
                            
                            <!-- Search Autocomplete Results -->
                            <div v-if="staffSearchResults.length > 0" class="absolute z-20 top-full left-0 right-0 mt-1 border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden bg-white dark:bg-slate-800 shadow-xl max-h-48 overflow-y-auto">
                                <div 
                                    v-for="st in staffSearchResults" 
                                    :key="st.id" 
                                    @click="selectStaffMember(st)" 
                                    class="p-3 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 cursor-pointer border-b border-slate-100 dark:border-slate-700/50 last:border-0 flex items-center justify-between transition-colors"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-7 w-7 rounded-lg bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 font-bold text-xs flex items-center justify-center">
                                            {{ st.name.charAt(0) }}
                                        </div>
                                        <span class="font-bold text-slate-900 dark:text-white text-xs">{{ st.name }}</span>
                                    </div>
                                    <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold">Select →</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Condition & Return Date Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Condition on Issue</label>
                            <select v-model="assignForm.condition_on_assignment" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm capitalize">
                                <option value="new">Brand New</option>
                                <option value="good">Good Condition</option>
                                <option value="fair">Fair / Operational</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Expected Return Date</label>
                            <input v-model="assignForm.expected_return_date" type="date" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-600 transition-all outline-none shadow-sm" />
                            <span class="text-[10px] text-slate-400 mt-1 block">Leave empty for permanent allocation</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200/80 dark:border-slate-800">
                        <button type="button" @click="showAssignModal = false" class="px-5 py-2.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold text-xs transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="assignForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-indigo-600/25 active:scale-95 transition-all">
                            Confirm Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 8: LAB DEPLOYMENT BREAKDOWN -->
        <div v-if="showDeploymentModal && selectedItem" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800 relative space-y-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/25 shrink-0">
                            <Building2 class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Lab & Location Deployments</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ selectedItem.name }} (Total Acquired: <strong>{{ selectedItem.total_quantity }} {{ selectedItem.unit_of_measure }}</strong>)</p>
                        </div>
                    </div>
                    <button @click="showDeploymentModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Metric Ticker Summary -->
                <div class="grid grid-cols-3 gap-3 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 text-xs">
                    <div class="space-y-0.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Total Acquired</span>
                        <p class="text-base font-black text-slate-900 dark:text-white">{{ selectedItem.total_quantity }} {{ selectedItem.unit_of_measure }}</p>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">In Central Store</span>
                        <p class="text-base font-black text-emerald-600 dark:text-emerald-400">{{ selectedItem.available_quantity }} {{ selectedItem.unit_of_measure }}</p>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase">Issued to Labs</span>
                        <p class="text-base font-black text-indigo-600 dark:text-indigo-400">{{ selectedItem.total_quantity - selectedItem.available_quantity }} {{ selectedItem.unit_of_measure }}</p>
                    </div>
                </div>

                <!-- Deployment List per Lab/Department -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Official SIV Lab Deployments</h4>
                    <div v-if="selectedItem.requisition_items && selectedItem.requisition_items.length > 0" class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                        <div v-for="reqItem in selectedItem.requisition_items" :key="reqItem.id" class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
                                    📍
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white text-xs">
                                        {{ reqItem.requisition?.department?.name || 'Central University Lab' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Issued to: {{ reqItem.requisition?.user?.name || 'Lab Officer' }} • Voucher #{{ reqItem.requisition?.requisition_number }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-2.5 py-1 rounded-lg bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 font-extrabold text-xs">
                                    {{ reqItem.requested_quantity }} {{ reqItem.unit_of_measure }}
                                </span>
                                <div class="text-[10px] text-slate-400 font-mono mt-1">
                                    {{ formatDate(reqItem.requisition?.created_at) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-slate-400 text-xs bg-slate-50 dark:bg-slate-800/40 rounded-2xl">
                        No departmental lab issuances recorded for this store item yet.
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" @click="showDeploymentModal = false" class="px-5 py-2.5 bg-slate-900 text-white dark:bg-slate-800 rounded-xl font-bold text-xs">
                        Close Tracker
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 9: RETURN ITEM -->
        <div v-if="showReturnModal && selectedAssignment" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-200">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200/80 dark:border-slate-800 relative space-y-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/25 shrink-0">
                            <RotateCcw class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Process Item Return</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ selectedAssignment.item?.name }} (Assigned to {{ selectedAssignment.assignable?.user?.name }})</p>
                        </div>
                    </div>
                    <button @click="showReturnModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitReturnItem" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Return Outcome / Status *</label>
                        <select v-model="returnForm.status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 transition-all outline-none shadow-sm capitalize">
                            <option value="returned">Returned to Central Store (Stock +1)</option>
                            <option value="damaged">Damaged / Requires Repair</option>
                            <option value="lost">Lost / Unreturned</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Condition on Return</label>
                        <input v-model="returnForm.condition_on_return" type="text" placeholder="e.g. Good condition, minor scratch" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-600 transition-all outline-none shadow-sm" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/80 dark:border-slate-800">
                        <button type="button" @click="showReturnModal = false" class="px-5 py-2.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-bold text-xs transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="returnForm.processing" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs shadow-lg shadow-emerald-600/25 active:scale-95 transition-all">
                            Complete Return
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AdminLayout>
</template>
