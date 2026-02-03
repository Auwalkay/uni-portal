<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { route } from 'ziggy-js';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { ArrowUpRight, ArrowDownRight, CreditCard, DollarSign, Wallet } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button'; // Assuming button component exists

interface Transaction {
    type: 'inflow' | 'outflow';
    description: string;
    amount: string;
    date: string;
    status: string;
}

defineProps<{
    stats: {
        totalInflow: number;
        totalOutflow: number;
        netBalance: number;
    };
    chartData: {
        month: string;
        inflow: number;
        outflow: number;
    }[];
    recentTransactions: Transaction[];
}>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Head title="Finance Dashboard" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Finance Dashboard</h2>
                    <p class="text-muted-foreground">Overview of school finances and cash flow.</p>
                </div>
                <div class="flex space-x-2">
                    <Link v-if="route().has('admin.finance.index')" :href="route('admin.finance.index')">
                        <Button variant="outline">Settings</Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Inflow</CardTitle>
                        <ArrowDownRight class="h-4 w-4 text-emerald-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600">{{ formatCurrency(stats.totalInflow) }}</div>
                        <p class="text-xs text-muted-foreground">From student fees</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Outflow</CardTitle>
                        <ArrowUpRight class="h-4 w-4 text-rose-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-rose-600">{{ formatCurrency(stats.totalOutflow) }}</div>
                        <p class="text-xs text-muted-foreground">Expenses + Payroll</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Net Balance</CardTitle>
                        <Wallet class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="stats.netBalance >= 0 ? 'text-primary' : 'text-rose-600'">
                            {{ formatCurrency(stats.netBalance) }}
                        </div>
                        <p class="text-xs text-muted-foreground">Available funds</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <!-- Chart Placeholder -->
                <Card class="col-span-4">
                    <CardHeader>
                        <CardTitle>Cash Flow Overview</CardTitle>
                        <CardDescription>Monthly inflow vs outflow for the last 6 months.</CardDescription>
                    </CardHeader>
                    <CardContent class="pl-2">
                        <!-- Simple visual representation since we don't have a chart lib installed yet -->
                        <div class="space-y-4">
                            <div v-for="item in chartData" :key="item.month" class="flex items-center text-sm">
                                <span class="w-20 font-medium">{{ item.month }}</span>
                                <div class="flex-1 flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 bg-emerald-500 rounded-full" :style="{ width: Math.min((item.inflow / (Math.max(item.inflow, item.outflow) || 1)) * 100, 100) + '%' }"></div>
                                        <span class="text-xs text-muted-foreground">{{ formatCurrency(item.inflow) }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 bg-rose-500 rounded-full" :style="{ width: Math.min((item.outflow / (Math.max(item.inflow, item.outflow) || 1)) * 100, 100) + '%' }"></div>
                                        <span class="text-xs text-muted-foreground">{{ formatCurrency(item.outflow) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Transactions -->
                <Card class="col-span-3">
                    <CardHeader>
                        <CardTitle>Recent Transactions</CardTitle>
                        <CardDescription>Latest financial activities.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-8">
                            <div v-for="(transaction, i) in recentTransactions" :key="i" class="flex items-center">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium leading-none">{{ transaction.description }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDate(transaction.date) }}</p>
                                </div>
                                <div class="ml-auto font-medium" :class="transaction.type === 'inflow' ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ transaction.type === 'inflow' ? '+' : '-' }}{{ formatCurrency(Number(transaction.amount)) }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
            
             <!-- Quick Links -->
             <div class="grid gap-4 md:grid-cols-3">
                <Link v-if="route().has('admin.finance.expenses.index')" :href="route('admin.finance.expenses.index')">
                    <Card class="hover:bg-accent/50 transition-colors cursor-pointer">
                        <CardHeader>
                            <CardTitle class="text-lg">Expenses</CardTitle>
                            <CardDescription>Manage daily expenses</CardDescription>
                        </CardHeader>
                    </Card>
                </Link>
                <Link v-if="route().has('admin.finance.payroll.index')" :href="route('admin.finance.payroll.index')">
                    <Card class="hover:bg-accent/50 transition-colors cursor-pointer">
                        <CardHeader>
                            <CardTitle class="text-lg">Payroll</CardTitle>
                            <CardDescription>Generate and view payrolls</CardDescription>
                        </CardHeader>
                    </Card>
                </Link>
                 <Link v-if="route().has('admin.finance.salary.index')" :href="route('admin.finance.salary.index')">
                    <Card class="hover:bg-accent/50 transition-colors cursor-pointer">
                        <CardHeader>
                            <CardTitle class="text-lg">Staff Salaries</CardTitle>
                            <CardDescription>Configure salary details</CardDescription>
                        </CardHeader>
                    </Card>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
