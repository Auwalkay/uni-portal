<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { route } from 'ziggy-js';
import { 
    CreditCard, 
    Building2, 
    Calendar,
    ArrowRight,
    Search,
    CheckCircle2,
    AlertCircle,
    XCircle,
    DollarSign,
    TrendingUp
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    subscriptions: {
        data: any[];
        links: any[];
        meta: any;
    };
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN'
    }).format(amount);
};

const getStatusBadge = (status: string) => {
    switch (status.toLowerCase()) {
        case 'active':
            return { variant: 'default' as const, icon: CheckCircle2, class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
        case 'expired':
            return { variant: 'destructive' as const, icon: XCircle, class: 'bg-rose-50 text-rose-700 border-rose-200' };
        case 'pending':
            return { variant: 'outline' as const, icon: AlertCircle, class: 'bg-amber-50 text-amber-700 border-amber-200' };
        default:
            return { variant: 'secondary' as const, icon: AlertCircle, class: 'bg-slate-50 text-slate-700 border-slate-200' };
    }
};

const totalRevenue = props.subscriptions.data.reduce((acc, sub) => acc + parseFloat(sub.amount), 0);
</script>

<template>
    <Head title="Subscription Management" />

    <CentralLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-3xl text-slate-900 tracking-tight">SaaS Subscriptions</h2>
                    <p class="text-slate-500 mt-1">Platform-wide institutional payment monitoring and revenue tracking.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Financial Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card class="bg-indigo-600 text-indigo-50 border-none shadow-xl">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-xs font-bold uppercase tracking-widest opacity-80">Page Revenue (Current View)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-extrabold">{{ formatCurrency(totalRevenue) }}</div>
                            <div class="mt-2 flex items-center gap-1 text-[10px] bg-white/10 w-fit px-2 py-1 rounded-full">
                                <TrendingUp class="h-3 w-3" /> Aggregated total shown below
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="bg-white border-slate-200">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between">
                            <CardTitle class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active Subscriptions</CardTitle>
                            <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-slate-900">{{ subscriptions.data.filter(s => s.status === 'active').length }}</div>
                            <p class="text-xs text-slate-400 mt-1">Institutions with full access</p>
                        </CardContent>
                    </Card>

                    <Card class="bg-white border-slate-200">
                        <CardHeader class="pb-2 flex flex-row items-center justify-between">
                            <CardTitle class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Recent Payments</CardTitle>
                            <DollarSign class="h-4 w-4 text-indigo-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-slate-900">{{ subscriptions.data.length }}</div>
                            <p class="text-xs text-slate-400 mt-1">Total recorded transactions</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Subscriptions Table -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b bg-slate-50/30 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="relative w-full md:w-96">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <Input placeholder="Search subscriptions..." class="pl-10 h-11 bg-white border-slate-200 rounded-xl shadow-sm" />
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Institution</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Plan & Amount</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Coverage Period</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <tr v-for="sub in subscriptions.data" :key="sub.id" class="hover:bg-slate-50/50 transition duration-200">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-center overflow-hidden">
                                                <img v-if="sub.tenant?.logo_path" :src="'/storage/' + sub.tenant.logo_path" class="h-full w-full object-contain p-1.5" />
                                                <Building2 v-else class="h-5 w-5 text-indigo-500" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900">{{ sub.tenant?.school_name || 'Institution' }}</p>
                                                <p class="text-[10px] text-slate-400 uppercase tracking-tight">Ref: {{ sub.payment_reference?.substring(0, 12) }}...</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-900">{{ sub.plan_name }}</span>
                                            <span class="text-xs text-indigo-600 font-semibold">{{ formatCurrency(sub.amount) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-xs text-slate-600 font-medium">
                                            <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                            {{ formatDate(sub.start_date) }} - {{ formatDate(sub.end_date) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border', getStatusBadge(sub.status).class]">
                                            <component :is="getStatusBadge(sub.status).icon" class="h-3 w-3" />
                                            {{ sub.status }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link :href="route('central.tenants.show', sub.tenant_id)" class="text-indigo-600 hover:text-indigo-900 transition flex items-center gap-1 justify-end font-bold text-xs uppercase tracking-tight">
                                            View Logs <ArrowRight class="h-4 w-4" />
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="subscriptions.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                        <div class="bg-indigo-50 h-16 w-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <CreditCard class="h-8 w-8 text-indigo-300" />
                                        </div>
                                        <p class="font-bold text-slate-600">No subscription history found.</p>
                                        <p class="text-xs text-slate-400 mt-1">All institutional payments will appear here.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </CentralLayout>
</template>
