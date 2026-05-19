<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { 
    LifeBuoy, Plus, Search, MessageSquare, Clock, 
    CheckCircle2, AlertCircle, X, ChevronRight 
} from 'lucide-vue-next';

interface Ticket {
    id: number;
    category: string;
    subject: string;
    priority: 'low' | 'medium' | 'high';
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    created_at: string;
    latest_message?: {
        message: string;
        created_at: string;
    };
}

const props = defineProps<{
    tickets: Ticket[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user as any);

// Dynamically select layout based on user role
const layoutComponent = computed(() => {
    const isStudent = user.value.roles?.some((role: any) => role.name === 'student') || 
                      user.value.permissions?.some((p: any) => p.name === 'access_student_portal');
    return isStudent ? StudentLayout : AdminLayout;
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Support Tickets', href: '/support' }
];

const showCreateModal = ref(false);
const searchQuery = ref('');

const categories = [
    { value: 'it_support', label: 'IT Support' },
    { value: 'finance', label: 'Finance & Payments' },
    { value: 'academic', label: 'Academic & Courses' },
    { value: 'inventory', label: 'Inventory & Assets' },
    { value: 'general', label: 'General Inquiry' },
];

const form = useForm({
    category: 'general',
    subject: '',
    priority: 'medium',
    message: '',
});

const submitTicket = () => {
    form.post('/support', {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const filteredTickets = computed(() => {
    if (!searchQuery.value) return props.tickets;
    return props.tickets.filter(ticket => 
        ticket.subject.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        ticket.category.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const getStatusColor = (status: string) => {
    switch (status) {
        case 'open': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'in_progress': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'resolved': return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
        case 'closed': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    }
};

const getPriorityColor = (priority: string) => {
    switch (priority) {
        case 'high': return 'text-red-600 dark:text-red-400';
        case 'medium': return 'text-yellow-600 dark:text-yellow-400';
        case 'low': return 'text-green-600 dark:text-green-400';
        default: return 'text-gray-600 dark:text-gray-400';
    }
};

const formatCategory = (cat: string) => {
    return categories.find(c => c.value === cat)?.label || cat;
};
</script>

<template>
    <Head title="Support Tickets" />

    <component :is="layoutComponent" :breadcrumbs="breadcrumbs">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <LifeBuoy class="h-8 w-8 text-indigo-600" />
                            Help & Support Center
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Submit a support ticket and track its progress. Our team will get back to you shortly.
                        </p>
                    </div>
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <Plus class="h-4 w-4 mr-2" />
                        Create New Ticket
                    </button>
                </div>

                <!-- Main Content -->
                <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden">
                    <!-- Search & Filter Header -->
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center">
                        <div class="relative flex-1 max-w-md">
                            <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search tickets..."
                                class="pl-10 w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm py-2"
                            />
                        </div>
                    </div>

                    <!-- Tickets List -->
                    <div v-if="filteredTickets.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                        <Link
                            v-for="ticket in filteredTickets"
                            :key="ticket.id"
                            :href="`/support/${ticket.id}`"
                            class="block hover:bg-gray-50 dark:hover:bg-gray-750 transition duration-150"
                        >
                            <div class="p-6 flex items-center justify-between">
                                <div class="flex-1 min-w-0 pr-4">
                                    <div class="flex items-center gap-3 mb-1.5 flex-wrap">
                                        <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider', getStatusColor(ticket.status)]">
                                            {{ ticket.status.replace('_', ' ') }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatCategory(ticket.category) }}
                                        </span>
                                        <span :class="['text-xs font-medium flex items-center gap-1', getPriorityColor(ticket.priority)]">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            {{ ticket.priority }} Priority
                                        </span>
                                    </div>
                                    <h2 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                        {{ ticket.subject }}
                                    </h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-1">
                                        {{ ticket.latest_message?.message || 'No messages yet.' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right hidden sm:block">
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Last updated</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ new Date(ticket.created_at).toLocaleDateString() }}
                                        </p>
                                    </div>
                                    <ChevronRight class="h-5 w-5 text-gray-400" />
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-16 px-4">
                        <LifeBuoy class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">No tickets found</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ searchQuery ? 'Try adjusting your search criteria.' : 'Create a ticket to get support from our staff.' }}
                        </p>
                        <button
                            v-if="!searchQuery"
                            @click="showCreateModal = true"
                            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                        >
                            <Plus class="h-4 w-4 mr-2" />
                            Create Your First Ticket
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Ticket Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-950 bg-opacity-75 dark:bg-opacity-80 transition-opacity" @click="showCreateModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <Plus class="h-5 w-5 text-indigo-600" />
                            Create Support Ticket
                        </h3>
                        <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-500">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitTicket" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                            <select
                                v-model="form.category"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                            <input
                                v-model="form.subject"
                                type="text"
                                required
                                placeholder="Brief summary of the issue..."
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                            <select
                                v-model="form.priority"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="low">Low Priority</option>
                                <option value="medium">Medium Priority</option>
                                <option value="high">High Priority</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message Description</label>
                            <textarea
                                v-model="form.message"
                                rows="4"
                                required
                                placeholder="Explain your issue in detail so we can help you best..."
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            ></textarea>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-2">
                            <button
                                type="button"
                                @click="showCreateModal = false"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Submit Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </component>
</template>
