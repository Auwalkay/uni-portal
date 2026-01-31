<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Banknote, TrendingUp, AlertCircle, Clock } from 'lucide-vue-next';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import { computed } from 'vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const props = defineProps<{
    stats: {
        totalRevenue: number;
        outstanding: number;
        totalInvoiced: number;
    };
    chartData: Array<{
        month: string;
        total: number;
    }>;
    recentPayments: Array<{
        id: string;
        amount: number;
        paid_at: string;
        user?: {
            name: string;
            email: string;
        };
        invoice?: {
            invoice_number: string;
        };
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Finance Dashboard',
        href: '/admin/finance/dashboard',
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const chartConfig = computed(() => {
    return {
        labels: props.chartData.map(d => d.month),
        datasets: [
            {
                label: 'Monthly Revenue',
                backgroundColor: '#10b981', // green-500
                data: props.chartData.map(d => d.total)
            }
        ]
    }
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

</script>

<template>
    <Head title="Finance Dashboard" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Finance Overview</h1>
                <div class="flex items-center space-x-2">
                    <!-- Date Range Picker could go here -->
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                        <Banknote class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats.totalRevenue) }}</div>
                        <p class="text-xs text-muted-foreground">Lifetime collected revenue</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Expected / Invoiced</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats.totalInvoiced) }}</div>
                        <p class="text-xs text-muted-foreground">Total value of all invoices</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Outstanding</CardTitle>
                        <AlertCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats.outstanding) }}</div>
                        <p class="text-xs text-muted-foreground">Unpaid invoice amounts</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <!-- Chart -->
                <Card class="col-span-4">
                    <CardHeader>
                        <CardTitle>Revenue Overview</CardTitle>
                    </CardHeader>
                    <CardContent class="pl-2">
                        <div class="h-[350px]">
                            <Bar :data="chartConfig" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Transactions -->
                <Card class="col-span-3">
                    <CardHeader>
                        <CardTitle>Recent Transactions</CardTitle>
                        <CardDescription>
                            Latest 5 payments received.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-8">
                            <div v-for="payment in recentPayments" :key="payment.id" class="flex items-center">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium leading-none">{{ payment.user?.name || 'Unknown User' }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ payment.user?.email }}
                                    </p>
                                </div>
                                <div class="ml-auto font-medium">
                                    +{{ formatCurrency(payment.amount) }}
                                </div>
                            </div>
                            <div v-if="recentPayments.length === 0" class="text-center text-muted-foreground text-sm">
                                No recent transactions.
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
            
            <!-- Detailed Table (Optional, for now just a placeholder or could be full list link) -->
             <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                             <CardTitle>Latest Payments</CardTitle>
                             <CardDescription>Detailed view of recent transactions.</CardDescription>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                             <Link href="/admin/payments">View All</Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                     <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Payer</TableHead>
                                <TableHead>Invoice #</TableHead>
                                <TableHead class="text-right">Amount</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                             <TableRow v-for="payment in recentPayments" :key="payment.id">
                                <TableCell>{{ formatDate(payment.paid_at) }}</TableCell>
                                <TableCell>
                                    <div class="font-medium">{{ payment.user?.name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ payment.user?.email }}</div>
                                </TableCell>
                                <TableCell>{{ payment.invoice?.invoice_number || 'N/A' }}</TableCell>
                                <TableCell class="text-right">{{ formatCurrency(payment.amount) }}</TableCell>
                            </TableRow>
                             <TableRow v-if="recentPayments.length === 0">
                                <TableCell colspan="4" class="text-center">No payments found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

        </div>
    </AdminLayout>
</template>
