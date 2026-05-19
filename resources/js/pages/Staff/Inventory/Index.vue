<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Package, AlertCircle, Check } from 'lucide-vue-next';

const props = defineProps<{
    assignments: Array<{
        id: number;
        inventory_item_id: number;
        item: {
            name: string;
            sku: string;
            condition: string;
        };
        assigned_at: string;
        expected_return_date: string;
        status: string;
    }>;
    complaints: Array<{
        id: number;
        item: { name: string };
        subject: string;
        description: string;
        status: string;
        admin_notes?: string;
        created_at: string;
    }>;
}>();

const showComplaintModal = ref(false);
const selectedAssignment = ref<any>(null);

const complaintForm = useForm({
    inventory_item_id: '',
    subject: '',
    description: '',
});

const openComplaintModal = (assignment: any) => {
    selectedAssignment.value = assignment;
    complaintForm.inventory_item_id = assignment.inventory_item_id;
    complaintForm.subject = '';
    complaintForm.description = '';
    showComplaintModal.value = true;
};

const submitComplaint = () => {
    complaintForm.post('/staff/inventory/complaints', {
        onSuccess: () => {
            showComplaintModal.value = false;
            complaintForm.reset();
        },
    });
};

const breadcrumbs = [
    { title: 'Personal', href: '#' },
    { title: 'My Inventory', href: '#' }
];
</script>

<template>
    <Head title="My Inventory" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-[1200px] mx-auto">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2">My Assigned Inventory</h1>
                    <p class="text-slate-600 dark:text-slate-400 font-medium">View items assigned to you and report any issues.</p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assigned At</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expected Return</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="assignment in assignments" :key="assignment.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ assignment.item?.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ assignment.item?.sku || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ new Date(assignment.assigned_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ assignment.expected_return_date ? new Date(assignment.expected_return_date).toLocaleDateString() : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="{
                                        'px-2 py-1 text-xs font-semibold rounded-full': true,
                                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': assignment.status === 'assigned',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400': assignment.status === 'returned',
                                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': assignment.status === 'lost' || assignment.status === 'damaged'
                                    }">
                                        {{ assignment.status.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button 
                                        v-if="assignment.status === 'assigned'"
                                        @click="openComplaintModal(assignment)" 
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 flex items-center justify-end gap-1 w-full"
                                    >
                                        <AlertCircle class="h-4 w-4" />
                                        Report Issue
                                    </button>
                                    <span v-else class="text-gray-400 dark:text-gray-600">No actions</span>
                                </td>
                            </tr>
                            <tr v-if="assignments.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                    No inventory items assigned to you.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Complaints Section -->
            <div class="mt-12">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-4">
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                            <AlertCircle class="h-6 w-6 text-red-600" />
                            My Complaints & Issues
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track the status of your reported issues.</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Admin Notes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="complaint in complaints" :key="complaint.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ complaint.item?.name }}</div>
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
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ complaint.admin_notes || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ new Date(complaint.created_at).toLocaleDateString() }}
                                    </td>
                                </tr>
                                <tr v-if="complaints.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        No complaints filed yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Complaint Modal -->
        <div v-if="showComplaintModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Report Issue with {{ selectedAssignment?.item?.name }}</h3>
                <form @submit.prevent="submitComplaint" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                        <input v-model="complaintForm.subject" type="text" placeholder="e.g., Screen cracked, Won't turn on" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea v-model="complaintForm.description" placeholder="Please provide details about the issue..." required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" rows="4"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showComplaintModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="complaintForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg disabled:opacity-50">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
