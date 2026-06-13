<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { 
    LifeBuoy, MessageSquare, Clock, CheckCircle2, 
    AlertCircle, ArrowLeft, Send 
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
    messages: Message[];
}

const props = defineProps<{
    ticket: Ticket;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user as any);

const breadcrumbs = [
    { title: 'Dashboard', href: '/student/dashboard' },
    { title: 'Support Tickets', href: '/student/support' },
    { title: `#${props.ticket.id}`, href: `/student/support/${props.ticket.id}` }
];

const form = useForm({
    message: '',
});

const submitReply = () => {
    form.post(`/student/support/${props.ticket.id}/reply`, {
        onSuccess: () => {
            form.reset();
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

const isMessageFromAdmin = (msg: Message) => {
    return msg.user.roles?.some(role => role.name === 'admin');
};
</script>

<template>
    <Head :title="`Ticket #${ticket.id} - ${ticket.subject}`" />

    <StudentLayout :breadcrumbs="breadcrumbs">
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Back Button & Header -->
                <div class="mb-6">
                    <a href="/student/support" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4">
                        <ArrowLeft class="h-4 w-4 mr-1" />
                        Back to Tickets
                    </a>
                    
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2 flex-wrap">
                                    <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider', getStatusColor(ticket.status)]">
                                        {{ ticket.status.replace('_', ' ') }}
                                    </span>
                                    <span :class="['text-xs font-medium flex items-center gap-1', getPriorityColor(ticket.priority)]">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ ticket.priority }} Priority
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Ref: #{{ ticket.id }}
                                    </span>
                                </div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ ticket.subject }}
                                </h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Opened on {{ new Date(ticket.created_at).toLocaleString() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages Thread -->
                <div class="space-y-6 mb-6">
                    <div 
                        v-for="msg in ticket.messages" 
                        :key="msg.id"
                        :class="['flex', msg.user_id === user.id ? 'justify-end' : 'justify-start']"
                    >
                        <div 
                            :class="[
                                'max-w-[75%] rounded-2xl px-4 py-3 shadow-sm',
                                msg.user_id === user.id 
                                    ? 'bg-indigo-600 text-white rounded-br-none' 
                                    : (isMessageFromAdmin(msg) 
                                        ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 text-gray-900 dark:text-white rounded-bl-none'
                                        : 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-900 dark:text-white rounded-bl-none')
                            ]"
                        >
                            <div class="flex items-center justify-between gap-4 mb-1">
                                <span :class="['text-xs font-semibold', msg.user_id === user.id ? 'text-indigo-200' : (isMessageFromAdmin(msg) ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400')]">
                                    {{ msg.user_id === user.id ? 'You' : msg.user.name }}
                                    <span v-if="isMessageFromAdmin(msg)" class="ml-1 text-[10px] uppercase bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-300 px-1 rounded">Support</span>
                                </span>
                                <span :class="['text-[10px]', msg.user_id === user.id ? 'text-indigo-200' : 'text-gray-400 dark:text-gray-500']">
                                    {{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                </span>
                            </div>
                            <div class="text-sm whitespace-pre-wrap">
                                {{ msg.message }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reply Box -->
                <div v-if="ticket.status !== 'closed'" class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <form @submit.prevent="submitReply">
                        <textarea
                            v-model="form.message"
                            rows="3"
                            required
                            placeholder="Type your reply here..."
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                        <div class="flex justify-end mt-3">
                            <button
                                type="submit"
                                :disabled="form.processing || !form.message.trim()"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
                            >
                                <Send class="h-4 w-4 mr-2" />
                                Send Reply
                            </button>
                        </div>
                    </form>
                </div>
                
                <div v-else class="bg-gray-100 dark:bg-gray-800 p-4 rounded-xl text-center border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        This ticket has been closed. If you still need help, please create a new ticket.
                    </p>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
