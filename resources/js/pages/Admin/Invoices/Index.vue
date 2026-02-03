<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { 
    Filter, Search, CheckCircle, FileText, TrendingUp, TrendingDown, DollarSign, PieChart, Plus
} from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
  Avatar, AvatarFallback, AvatarImage,
} from '@/components/ui/avatar';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import Swal from 'sweetalert2';
import { format as formatDate } from 'date-fns';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement
} from 'chart.js';
import { Line, Doughnut } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, PointElement, LineElement, ArcElement);

const props = defineProps<{
    invoices: {
        data: any[],
        links: any[],
        meta: any
    };
    sessions: any[];
    filters: any;
    analytics: {
        total_expected: number;
        total_collected: number;
        total_outstanding: number;
        collection_rate: number;
        charts: {
            status_distribution: { labels: string[], datasets: any[] };
            revenue_trend: { labels: string[], datasets: any[] };
        };
    };
}>();

const filterForm = ref({
    search: props.filters.search || '',
    status: props.filters.status || '',
    type: props.filters.type || '',
    session_id: props.filters.session_id || '',
});

const applyFilters = () => {
    router.get(route('admin.invoices.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'paid': return 'bg-green-100 text-green-800 border-green-200';
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'partial': return 'bg-blue-100 text-blue-800 border-blue-200';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const markAsPaid = (id: string, ref: string) => {
    Swal.fire({
        title: 'Mark as Paid?',
        text: `Are you sure you want to manually mark invoice ${ref} as fully paid? This will create a manual payment record.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, mark as paid!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.invoices.mark-as-paid', id), {}, {
                onSuccess: () => {
                     Swal.fire('Paid!', 'Invoice has been marked as paid.', 'success');
                }
            });
        }
    });
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Invoices', href: '/admin/invoices' },
];
</script>

<template>
    <Head title="Invoice Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full">
            
            <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Invoice Management</h1>
                    <p class="text-muted-foreground">View and manage student invoices.</p>
                </div>
                <Button as-child>
                    <Link :href="route('admin.invoices.create')">
                        <Plus class="w-4 h-4 mr-2" /> Create Invoice
                    </Link>
                </Button>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(analytics.total_expected) }}</div>
                        <p class="text-xs text-muted-foreground">Expected from all invoices</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Collected</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ formatCurrency(analytics.total_collected) }}</div>
                        <p class="text-xs text-muted-foreground">Total payments received</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Outstanding</CardTitle>
                        <TrendingDown class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ formatCurrency(analytics.total_outstanding) }}</div>
                        <p class="text-xs text-muted-foreground">Pending payments</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Collection Rate</CardTitle>
                        <PieChart class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ analytics.collection_rate }}%</div>
                        <p class="text-xs text-muted-foreground">Of expected revenue collected</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Section -->
            <div class="grid gap-4 md:grid-cols-7">
                <Card class="col-span-4">
                    <CardHeader>
                        <CardTitle>Revenue Trend</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-[300px]">
                            <Line 
                                :data="analytics.charts.revenue_trend" 
                                :options="{ responsive: true, maintainAspectRatio: false }" 
                            />
                        </div>
                    </CardContent>
                </Card>
                <Card class="col-span-3">
                    <CardHeader>
                        <CardTitle>Invoice Status</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="h-[300px] flex items-center justify-center">
                            <Doughnut 
                                :data="analytics.charts.status_distribution" 
                                :options="{ responsive: true, maintainAspectRatio: false }" 
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="space-y-1">
                    <Label>Search</Label>
                    <div class="relative">
                        <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input v-model="filterForm.search" placeholder="Ref, Name, Matric..." class="pl-8" />
                    </div>
                </div>
                <div class="space-y-1">
                    <Label>Session</Label>
                    <Select v-model="filterForm.session_id">
                         <SelectTrigger><SelectValue placeholder="All Sessions" /></SelectTrigger>
                         <SelectContent>
                             <SelectItem value="all">All Sessions</SelectItem>
                             <SelectItem v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</SelectItem>
                         </SelectContent>
                    </Select>
                </div>
                <div class="space-y-1">
                    <Label>Status</Label>
                    <Select v-model="filterForm.status">
                         <SelectTrigger><SelectValue placeholder="All Status" /></SelectTrigger>
                         <SelectContent>
                             <SelectItem value="all">All Status</SelectItem>
                             <SelectItem value="pending">Pending</SelectItem>
                             <SelectItem value="partial">Partial</SelectItem>
                             <SelectItem value="paid">Paid</SelectItem>
                         </SelectContent>
                    </Select>
                </div>
                <Button variant="secondary" @click="applyFilters">
                    <Filter class="w-4 h-4 mr-2" /> Filter
                </Button>
            </div>

            <!-- Invoice List -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Reference</TableHead>
                            <TableHead>Student</TableHead>
                            <TableHead>Session</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead>Paid</TableHead>
                            <TableHead>Balance</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                         <TableRow v-for="invoice in invoices.data" :key="invoice.id">
                            <TableCell class="font-mono font-medium">{{ invoice.reference }}</TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-8 w-8">
                                        <AvatarImage :src="invoice.user?.profile_photo_url" :alt="invoice.user?.name" />
                                        <AvatarFallback>{{ invoice.user?.name?.charAt(0) || 'U' }}</AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <div class="font-medium">{{ invoice.user?.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ invoice.user?.student?.matriculation_number || invoice.user?.email }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>{{ invoice.session?.name }}</TableCell>
                            <TableCell class="font-bold">{{ formatCurrency(invoice.amount) }}</TableCell>
                            <TableCell class="text-green-600">{{ formatCurrency(invoice.paid_amount || 0) }}</TableCell>
                            <TableCell class="text-red-600">{{ formatCurrency(invoice.amount - (invoice.paid_amount || 0)) }}</TableCell>
                            <TableCell>
                                <Badge variant="outline" :class="getStatusColor(invoice.status)">
                                    {{ invoice.status.toUpperCase() }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Button 
                                        v-if="invoice.status !== 'paid'" 
                                        size="sm" 
                                        variant="outline"
                                        class="text-green-600 border-green-200 hover:bg-green-50 hover:text-green-700" 
                                        @click="markAsPaid(invoice.id, invoice.reference)"
                                    >
                                        <CheckCircle class="w-4 h-4 mr-2" /> Mark Paid
                                    </Button>
                                    <Button size="sm" variant="secondary" as-child>
                                        <Link :href="route('admin.invoices.show', invoice.id)">
                                            View
                                        </Link>
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="invoices.data.length === 0">
                            <TableCell colspan="8" class="h-24 text-center text-muted-foreground">
                                No invoices found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
             <!-- Pagination could go here -->
        </div>
    </AdminLayout>
</template>
