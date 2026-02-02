<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Printer, 
    ArrowLeft, 
    CreditCard, 
    Calendar, 
    User, 
    Hash,
    CheckCircle,
    XCircle,
    Building2,
    Briefcase
} from 'lucide-vue-next';

import { route } from 'ziggy-js';

defineProps({
    payment: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const formatCurrency = (amount) => {
    return '₦' + Number(amount).toLocaleString();
};
</script>

<template>
    <Head title="Payment Details" />

    <AdminLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-6">
            
            <!-- Header & Actions -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.payments.index')" class="p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <ArrowLeft class="w-5 h-5 text-gray-500" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Payment Details</h1>
                        <p class="text-sm text-gray-500 font-mono">{{ payment.reference }}</p>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <Printer class="w-4 h-4 mr-2" />
                        Print Receipt
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Student & Payment Info -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Payment Status Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <CreditCard class="w-5 h-5 text-gray-400" /> Payment Summary
                            </h3>
                            <span 
                                class="px-3 py-1 rounded-full text-sm font-medium border"
                                :class="{
                                    'bg-green-50 text-green-700 border-green-200': payment.status === 'paid',
                                    'bg-yellow-50 text-yellow-700 border-yellow-200': payment.status === 'pending',
                                    'bg-red-50 text-red-700 border-red-200': payment.status === 'failed',
                                }"
                            >
                                {{ payment.status.toUpperCase() }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-xs text-uppercase text-gray-500 font-semibold uppercase tracking-wider">Amount Paid</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(payment.amount) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-uppercase text-gray-500 font-semibold uppercase tracking-wider">Paid Date</p>
                                <p class="text-sm font-medium text-gray-900 mt-1 flex items-center gap-1">
                                    <Calendar class="w-4 h-4 text-gray-400" />
                                    {{ formatDate(payment.paid_at) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-uppercase text-gray-500 font-semibold uppercase tracking-wider">Method</p>
                                <p class="text-sm font-medium text-gray-900 mt-1 capitalize">{{ payment.channel || 'Card' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-uppercase text-gray-500 font-semibold uppercase tracking-wider">Reference</p>
                                <p class="text-sm font-medium text-gray-900 mt-1 font-mono select-all">{{ payment.reference }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Breakdown -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-900">Invoice Items</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="payment.invoice?.items?.length > 0" class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <th class="pb-3 pl-2">Description</th>
                                            <th class="pb-3 text-right pr-2">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="item in payment.invoice.items" :key="item.id">
                                            <td class="py-3 pl-2 text-sm text-gray-900">{{ item.description }}</td>
                                            <td class="py-3 pr-2 text-sm text-gray-900 text-right font-medium">{{ formatCurrency(item.amount) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500 text-sm">
                                No invoice items breakdown available.
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center bg-gray-50/50 p-4 rounded-lg">
                                <span class="text-sm font-medium text-gray-900">Total Invoice Amount</span>
                                <span class="text-lg font-bold text-gray-900">{{ formatCurrency(payment.invoice?.amount || payment.amount) }}</span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: Student Details -->
                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                             <User class="w-4 h-4 text-gray-500" />
                            <h3 class="text-sm font-semibold text-gray-900">Student Information</h3>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center mb-6">
                                <img 
                                    class="h-16 w-16 rounded-full border-2 border-white shadow-sm" 
                                    :src="`https://ui-avatars.com/api/?name=${payment.user.name}&background=random`" 
                                    alt="" 
                                />
                                <div class="ml-4">
                                    <h4 class="text-lg font-bold text-gray-900">{{ payment.user.name }}</h4>
                                    <p class="text-sm text-gray-500">{{ payment.user.email }}</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div v-if="payment.user.student">
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Matriculation Number</p>
                                    <p class="text-sm font-medium text-gray-900 flex items-center gap-1 mt-1">
                                        <Hash class="w-3 h-3 text-gray-400" />
                                        {{ payment.user.student.matriculation_number }}
                                    </p>
                                </div>

                                <div v-if="payment.user.student?.academic_department">
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Department</p>
                                    <p class="text-sm font-medium text-gray-900 flex items-center gap-1 mt-1">
                                        <Briefcase class="w-3 h-3 text-gray-400" />
                                        {{ payment.user.student.academic_department.name }}
                                    </p>
                                </div>

                                <div v-if="payment.user.student?.academic_department?.faculty">
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Faculty</p>
                                    <p class="text-sm font-medium text-gray-900 flex items-center gap-1 mt-1">
                                        <Building2 class="w-3 h-3 text-gray-400" />
                                        {{ payment.user.student.academic_department.faculty.name }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Academic Session</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 mt-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ payment.invoice?.session?.name || 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                             <Link :href="route('admin.students.show', payment.user.student?.id || '#')" class="text-sm font-medium text-blue-600 hover:text-blue-500 block text-center">
                                View Student Profile &toea;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
