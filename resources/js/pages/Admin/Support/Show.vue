<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    LifeBuoy, ArrowLeft, Send, User, Calendar, 
    ShieldAlert, RefreshCw 
} from 'lucide-vue-next';

interface Message {
    id: number;
    user_id: string;
    message: string;
    created_at: string;
    user: {
        name: string;
        roles?: Array<{ name: string }>;
    };
}

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
    messages: Message[];
}

const props = defineProps<{
    ticket: Ticket;
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Support Tickets', href: '/admin/support-tickets' },
    { title: `#${props.ticket.id}`, href: `/admin/support-tickets/${props.ticket.id}` }
];

const updateForm = useForm({
    status: props.ticket.status,
    priority: props.ticket.priority,
});

const replyForm = useForm({
    message: '',
});

const submitUpdate = () => {
    updateForm.put(`/admin/support-tickets/${props.ticket.id}`, {
        preserveScroll: true,
    });
};

const submitReply = () => {
    replyForm.post(`/admin/support-tickets/${props.ticket.id}/reply`, {
        onSuccess: () => {
            replyForm.reset();
        },
    });
};

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

const categories = [
    { value: 'it_support', label: 'IT Support' },
    { value: 'finance', label: 'Finance & Payments' },
    { value: 'academic', label: 'Academic & Courses' },
    { value: 'inventory', label: 'Inventory & Assets' },
    { value: 'general', label: 'General Inquiry' },
];

const formatCategory = (cat: string) => {
    return categories.find(c => c.value === cat)?.label || cat;
};

const isMessageFromAdmin = (msg: Message) => {
    return msg.user.roles?.some(role => role.name === 'admin');
};
</script>

<template>
    <Head :title="`Admin - Ticket #${ticket.id}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Back Button -->
                <a href="/admin/support-tickets" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-6">
                    <ArrowLeft class="h-4 w-4 mr-1" />
                    Back to Tickets List
                </a>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Thread and Reply (Left/Main Column) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Main Ticket Header -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-2">
                                <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider', getStatusColor(ticket.status)]">
                                    {{ ticket.status.replace('_', ' ') }}
                                </span>
                                <span :class="['text-xs font-medium flex items-center gap-1', getPriorityColor(ticket.priority)]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ ticket.priority }} Priority
                                </span>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ ticket.subject }}
                            </h1>
                            <p class="text-xs text-gray-400 mt-1">
                                Category: {{ formatCategory(ticket.category) }} | Ref: #{{ ticket.id }}
                            </p>
                        </div>

                        <!-- Conversation Thread -->
                        <div class="space-y-4">
                            <div 
                                v-for="msg in ticket.messages" 
                                :key="msg.id"
                                :class="['flex', isMessageFromAdmin(msg) ? 'justify-end' : 'justify-start']"
                            >
                                <div 
                                    :class="[
                                        'max-w-[80%] rounded-2xl px-4 py-3 shadow-sm',
                                        isMessageFromAdmin(msg) 
                                            ? 'bg-indigo-600 text-white rounded-br-none' 
                                            : 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-900 dark:text-white rounded-bl-none'
                                    ]"
                                >
                                    <div class="flex items-center justify-between gap-4 mb-1">
                                        <span :class="['text-xs font-semibold', isMessageFromAdmin(msg) ? 'text-indigo-200' : 'text-gray-700 dark:text-gray-300']">
                                            {{ msg.user.name }}
                                            <span v-if="isMessageFromAdmin(msg)" class="ml-1 text-[10px] uppercase bg-indigo-500 text-white px-1 rounded">Staff</span>
                                        </span>
                                        <span :class="['text-[10px]', isMessageFromAdmin(msg) ? 'text-indigo-200' : 'text-gray-400 dark:text-gray-500']">
                                            {{ new Date(msg.created_at).toLocaleString([], { dateStyle: 'short', timeStyle: 'short' }) }}
                                        </span>
                                    </div>
                                    <div class="text-sm whitespace-pre-wrap">
                                        {{ msg.message }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reply Box -->
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                            <form @submit.prevent="submitReply">
                                <textarea
                                    v-model="replyForm.message"
                                    rows="4"
                                    required
                                    placeholder="Type your official reply here..."
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                ></textarea>
                                <div class="flex justify-end mt-3">
                                    <button
                                        type="submit"
                                        :disabled="replyForm.processing || !replyForm.message.trim()"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        <Send class="h-4 w-4 mr-2" />
                                        Send Official Reply
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar Info / Actions (Right Column) -->
                    <div class="space-y-6">
                        <!-- User Meta Info -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                                Submitter Information
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <User class="h-5 w-5 text-gray-400 mt-0.5" />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ticket.user.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ ticket.user.email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Calendar class="h-5 w-5 text-gray-400" />
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Created At</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ new Date(ticket.created_at).toLocaleDateString() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ticket Status Control -->
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                                Ticket Controls
                            </h3>
                            <form @submit.prevent="submitUpdate" class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Status</label>
                                    <select
                                        v-model="updateForm.status"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none"
                                    >
                                        <option value="open">Open</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Priority</label>
                                    <select
                                        v-model="updateForm.priority"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none"
                                    >
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="updateForm.processing"
                                    class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none disabled:opacity-50"
                                >
                                    <RefreshCw class="h-4 w-4 mr-2" />
                                    Update Ticket Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
