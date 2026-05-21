<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Package, Plus, Search, Upload, Trash2, Edit, Filter, FolderTree, UserPlus, Check, Download, AlertCircle } from 'lucide-vue-next';

const props = defineProps<{
    items: {
        data: Array<{
            id: number;
            name: string;
            sku: string;
            category: { name: string };
            total_quantity: number;
            available_quantity: number;
            condition: string;
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
        assigned_items: number;
        categories_count: number;
        pending_complaints: number;
    };
}>();

const currentTab = ref('items');
const showAddModal = ref(false);
const showEditModal = ref(false);
const showImportModal = ref(false);
const showCategoryModal = ref(false);
const showAssignModal = ref(false);
const showComplaintModal = ref(false);
const selectedItem = ref<any>(null);
const selectedComplaint = ref<any>(null);

const form = useForm({
    name: '',
    inventory_category_id: '',
    description: '',
    sku: '',
    total_quantity: 0,
    condition: 'new',
});

const editForm = useForm({
    name: '',
    inventory_category_id: '',
    description: '',
    sku: '',
    total_quantity: 0,
    condition: 'new',
});

const importForm = useForm({
    file: null as File | null,
});

const categoryForm = useForm({
    name: '',
    description: '',
});

const assignForm = useForm({
    inventory_item_id: '',
    staff_id: '',
    expected_return_date: '',
    condition_on_assignment: 'new',
});

const complaintUpdateForm = useForm({
    status: 'pending',
    admin_notes: '',
});

const staffSearchQuery = ref('');
const staffSearchResults = ref<Array<{id: string, name: string}>>([]);
const complaints = ref<any>({ data: [] });

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category_id || '');

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
    editForm.description = item.description;
    editForm.sku = item.sku;
    editForm.total_quantity = item.total_quantity;
    editForm.condition = item.condition;
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

const deleteItem = (id: number) => {
    if (confirm('Are you sure you want to delete this item?')) {
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

const openAssignModal = (item: any) => {
    assignForm.inventory_item_id = item.id;
    assignForm.condition_on_assignment = item.condition;
    assignForm.staff_id = '';
    staffSearchQuery.value = '';
    staffSearchResults.value = [];
    showAssignModal.value = true;
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
    assignForm.post('/admin/inventory/assignments', {
        onSuccess: () => {
            showAssignModal.value = false;
            assignForm.reset();
        },
    });
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
    <Head title="Inventory Management" />

    <AdminLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <Package class="h-8 w-8 text-indigo-600" />
                            Inventory Management
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Manage school assets, track stock, and handle assignments.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="showImportModal = true"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <Upload class="h-4 w-4 mr-2" />
                            Bulk Import
                        </button>
                        <a
                            href="/admin/inventory/export"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <Download class="h-4 w-4 mr-2" />
                            Export Items
                        </a>
                        <a
                            href="/admin/inventory/export-assignments"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <Download class="h-4 w-4 mr-2" />
                            Export Assignments
                        </a>
                        <button
                            @click="showCategoryModal = true"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            <Plus class="h-4 w-4 mr-2" />
                            Add Category
                        </button>
                        <button
                            @click="openAddModal"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <Plus class="h-4 w-4 mr-2" />
                            Add New Item
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <!-- Total Items -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Total Items</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_items }}</p>
                            </div>
                            <Package class="h-10 w-10 text-indigo-600" />
                        </div>
                    </div>
                    <!-- Assigned Items -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Assigned Items</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.assigned_items }}</p>
                            </div>
                            <UserPlus class="h-10 w-10 text-green-600" />
                        </div>
                    </div>
                    <!-- Categories -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Categories</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.categories_count }}</p>
                            </div>
                            <FolderTree class="h-10 w-10 text-yellow-600" />
                        </div>
                    </div>
                    <!-- Pending Complaints -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Pending Complaints</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pending_complaints }}</p>
                            </div>
                            <AlertCircle class="h-10 w-10 text-red-600" />
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            @click="switchTab('items')"
                            :class="[
                                currentTab === 'items'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                            ]"
                        >
                            <Package class="h-5 w-5" />
                            Items
                            <span class="ml-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 py-0.5 px-2.5 rounded-full text-xs">
                                {{ items.total }}
                            </span>
                        </button>
                        <button
                            @click="switchTab('categories')"
                            :class="[
                                currentTab === 'categories'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                            ]"
                        >
                            <FolderTree class="h-5 w-5" />
                            Categories
                            <span class="ml-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 py-0.5 px-2.5 rounded-full text-xs">
                                {{ categories.length }}
                            </span>
                        </button>
                        <button
                            @click="switchTab('complaints')"
                            :class="[
                                currentTab === 'complaints'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                            ]"
                        >
                            <AlertCircle class="h-5 w-5" />
                            Complaints
                            <span class="ml-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 py-0.5 px-2.5 rounded-full text-xs">
                                {{ complaints_count }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Items Tab Content -->
                <div v-if="currentTab === 'items'">
                    <!-- Filters -->
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                v-model="search"
                                @input="handleSearch"
                                type="text"
                                placeholder="Search by name or SKU..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-900 dark:text-white"
                            />
                        </div>
                        <div class="w-full md:w-64 relative">
                            <Filter class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <select
                                v-model="categoryFilter"
                                @change="handleSearch"
                                class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-900 dark:text-white"
                            >
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Condition</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="item in items.data" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ item.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ item.category?.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ item.sku || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ item.available_quantity }}</span> / {{ item.total_quantity }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'px-2 py-1 text-xs font-semibold rounded-full': true,
                                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': item.condition === 'new' || item.condition === 'good',
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': item.condition === 'fair',
                                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': item.condition === 'poor'
                                            }">
                                                {{ item.condition.toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="openAssignModal(item)" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3" title="Assign to Staff">
                                                <UserPlus class="h-5 w-5" />
                                            </button>
                                            <button @click="openEditModal(item)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                                <Edit class="h-5 w-5" />
                                            </button>
                                            <button @click="deleteItem(item.id)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                <Trash2 class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="items.data.length === 0">
                                        <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                            No inventory items found. Click "Add New Item" to create one.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Categories Tab Content -->
                <div v-if="currentTab === 'categories'">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Items</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ cat.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ cat.description || 'No description' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ cat.items_count || 0 }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Coming soon">
                                                <Edit class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="categories.length === 0">
                                        <td colspan="4" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                            No categories found. Click "Add Category" to create one.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Complaints Tab Content -->
                <div v-if="currentTab === 'complaints'">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subject</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="complaint in complaints.data" :key="complaint.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ complaint.user?.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ complaint.item?.name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ complaint.subject }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'px-2 py-1 text-xs font-semibold rounded-full': true,
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': complaint.status === 'pending',
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': complaint.status === 'reviewing',
                                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': complaint.status === 'resolved',
                                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': complaint.status === 'rejected'
                                            }">
                                                {{ complaint.status.toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="openComplaintModal(complaint)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                <Edit class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="complaints.data.length === 0">
                                        <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                            No complaints found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <div v-if="showAddModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Add New Inventory Item</h3>
                <form @submit.prevent="submitAdd" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                        <select v-model="form.inventory_category_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white">
                            <option value="">Select Category</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                            <input v-model="form.sku" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                            <input v-model.number="form.total_quantity" type="number" min="0" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Condition</label>
                        <select v-model="form.condition" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white">
                            <option value="new">New</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea v-model="form.description" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" rows="3"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50">Save Item</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Edit Inventory Item</h3>
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input v-model="editForm.name" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                        <select v-model="editForm.inventory_category_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white">
                            <option value="">Select Category</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                            <input v-model="editForm.sku" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                            <input v-model.number="editForm.total_quantity" type="number" min="0" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Condition</label>
                        <select v-model="editForm.condition" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white">
                            <option value="new">New</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea v-model="editForm.description" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" rows="3"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="editForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Import Modal -->
        <div v-if="showImportModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Bulk Import Items</h3>
                <form @submit.prevent="submitImport" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CSV or Excel File</label>
                        <input
                            type="file"
                            @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] || null"
                            accept=".csv,.xlsx"
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white"
                        />
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        File should contain columns: <code>name</code>, <code>category</code>, <code>description</code>, <code>sku</code>, <code>quantity</code>, <code>condition</code>.
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showImportModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="importForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50">Import</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Category Modal -->
        <div v-if="showCategoryModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Add New Category</h3>
                <form @submit.prevent="submitCategory" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category Name</label>
                        <input v-model="categoryForm.name" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea v-model="categoryForm.description" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" rows="3"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showCategoryModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="categoryForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-50">Save Category</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Assign Modal -->
        <div v-if="showAssignModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Assign Item to Staff</h3>
                <form @submit.prevent="submitAssign" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Staff</label>
                        <div class="flex gap-2">
                            <input 
                                v-model="staffSearchQuery" 
                                type="text" 
                                placeholder="Type staff name..." 
                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white"
                                @input="searchStaff"
                            />
                        </div>
                        <!-- Search Results -->
                        <div v-if="staffSearchResults.length > 0" class="mt-2 border border-gray-200 dark:border-gray-700 rounded-lg max-h-40 overflow-y-auto">
                            <div 
                                v-for="staff in staffSearchResults" 
                                :key="staff.id"
                                @click="assignForm.staff_id = staff.id"
                                :class="[
                                    'p-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer flex justify-between items-center',
                                    assignForm.staff_id === staff.id ? 'bg-indigo-50 dark:bg-indigo-900/30' : ''
                                ]"
                            >
                                <span class="text-sm text-gray-900 dark:text-white">{{ staff.name }}</span>
                                <Check v-if="assignForm.staff_id === staff.id" class="h-4 w-4 text-indigo-600" />
                            </div>
                        </div>
                        <div v-else-if="staffSearchQuery.length >= 2" class="text-sm text-gray-500 mt-1">
                            No staff found.
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expected Return Date (Optional)</label>
                        <input v-model="assignForm.expected_return_date" type="date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Condition on Assignment</label>
                        <select v-model="assignForm.condition_on_assignment" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white">
                            <option value="new">New</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showAssignModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="assignForm.processing || !assignForm.staff_id" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50">Assign Item</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Complaint Modal -->
        <div v-if="showComplaintModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Update Complaint Status</h3>
                <form @submit.prevent="submitComplaintUpdate" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select v-model="complaintUpdateForm.status" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white">
                            <option value="pending">Pending</option>
                            <option value="reviewing">Reviewing</option>
                            <option value="resolved">Resolved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Admin Notes</label>
                        <textarea v-model="complaintUpdateForm.admin_notes" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" rows="3"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showComplaintModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="complaintUpdateForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
