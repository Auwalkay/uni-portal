<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    LifeBuoy, Search, ChevronRight, Filter, Clock 
} from 'lucide-vue-next';

interface Ticket {
    id: number;
    category: string;
    subject: string;
    priority: 'low' | 'medium' | 'high';
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    created_at: string;
    user: {
        name: string;
        email: string;
    };
}

const props = defineProps<{
    tickets: {
        data: Ticket[];
        links: any;
        total: number;
    };
    filters: {
        status?: string;
        category?: string;
    };
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Support Tickets', href: '/admin/support-tickets' }
];

const selectedStatus = ref(props.filters.status || '');
const selectedCategory = ref(props.filters.category || '');

const categories = [
    { value: 'it_support', label: 'IT Support' },
    { value: 'finance', label: 'Finance & Payments' },
    { value: 'academic', label: 'Academic & Courses' },
    { value: 'inventory', label: 'Inventory & Assets' },
    { value: 'general', label: 'General Inquiry' },
];

const statuses = [
    { value: 'open', label: 'Open' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'closed', label: 'Closed' },
];

const applyFilters = () => {
    router.get('/admin/support-tickets', {
        status: selectedStatus.value,
        category: selectedCategory.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch([selectedStatus, selectedCategory], () => {
    applyFilters();
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
        case 'high': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'medium': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'low': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        default: return 'bg-gray-100 text-gray-850';
    }
};

const formatCategory = (cat: string) => {
    return categories.find(c => c.value === cat)?.label || cat;
};
</script>

<template>
    <Head title="Admin - Support Tickets" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <LifeBuoy class="h-8 w-8 text-indigo-600" />
                        Admin Support Center
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Review, manage, and reply to all support tickets from students and staff.
                    </p>
                </div>

                <!-- Main Content -->
                <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden">
                    <!-- Filters Header -->
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap gap-4 items-center">
                        <div class="flex items-center gap-2">
                            <Filter class="h-4 w-4 text-gray-400" />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filters:</span>
                        </div>
                        
                        <!-- Status Filter -->
                        <div>
                            <select
                                v-model="selectedStatus"
                                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">All Statuses</option>
                                <option v-for="status in statuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <select
                                v-model="selectedCategory"
                                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Tickets List -->
                    <div v-if="tickets.data.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                        <Link
                            v-for="ticket in tickets.data"
                            :key="ticket.id"
                            :href="`/admin/support-tickets/${ticket.id}`"
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
                                        <span :class="['px-2 py-0.5 rounded text-xs font-medium uppercase', getPriorityColor(ticket.priority)]">
                                            {{ ticket.priority }}
                                        </span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            #{{ ticket.id }}
                                        </span>
                                    </div>
                                    <h2 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                        {{ ticket.subject }}
                                    </h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Submitted by <span class="font-medium text-gray-700 dark:text-gray-305">{{ ticket.user.name }}</span> ({{ ticket.user.email }})
                                    </p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right hidden sm:block">
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Submitted at</p>
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
                            There are currently no tickets matching your filters.
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links" class="mt-6 flex justify-center">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <Component
                            :is="link.url ? Link : 'span'"
                            v-for="(link, i) in tickets.links"
                            :key="i"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400' : 'bg-white border-gray-300 text-gray-550 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400',
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                i === 0 ? 'rounded-l-md' : '',
                                i === tickets.links.length - 1 ? 'rounded-r-md' : ''
                            ]"
                        />
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
