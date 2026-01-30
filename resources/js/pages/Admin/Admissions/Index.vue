<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import debounce from 'lodash/debounce';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { 
    Eye, 
    Search, 
    FileText, 
    CheckCircle, 
    XCircle, 
    Clock, 
    Filter,
    ArrowUpDown,
    Download
} from 'lucide-vue-next';
import StatsCard from '@/components/StatsCard.vue';
import { format } from 'date-fns';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    applicants: {
        data: Array<{
            id: string;
            jamb_registration_number: string;
            status: string;
            created_at: string;
            user: {
                name: string;
                email: string;
            };
            programme?: {
                name: string;
            };
        }>;
        links: Array<any>;
    };
    stats: {
        total: number;
        admitted: number;
        rejected: number;
        pending: number;
    };
    filters: {
        search?: string;
        status?: string;
        programme_id?: string;
    };
    programmes: Array<{ id: string; name: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admissions', href: route('admin.admissions.index') },
];

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const programme = ref(props.filters.programme_id || '');

const updateFilters = debounce(() => {
    router.get(
        route('admin.admissions.index'),
        { 
            search: search.value, 
            status: status.value,
            programme_id: programme.value 
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch([search, status, programme], updateFilters);

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'admitted': return 'default'; // dark/success
        case 'rejected': return 'destructive';
        case 'pending': return 'secondary';
        case 'submitted': return 'outline';
        default: return 'outline';
    }
};

const getStatusColorClass = (status: string) => {
    switch (status) {
        case 'admitted': return 'bg-green-100 text-green-700 hover:bg-green-100/80 border-green-200';
        case 'rejected': return 'bg-red-100 text-red-700 hover:bg-red-100/80 border-red-200';
        case 'pending': return 'bg-yellow-100 text-yellow-700 hover:bg-yellow-100/80 border-yellow-200';
        case 'submitted': return 'bg-blue-100 text-blue-700 hover:bg-blue-100/80 border-blue-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
}

const clearFilters = () => {
    search.value = '';
    status.value = '';
    programme.value = '';
};
</script>

<template>
    <Head title="Admissions Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Admission Requests</h1>
                    <p class="text-muted-foreground">
                        Manage student applications and admission status.
                    </p>
                </div>
                <Button variant="outline" class="gap-2">
                    <Download class="h-4 w-4" />
                    Export Report
                </Button>
            </div>

            <!-- Stats Overview -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Total Applications"
                    :value="stats.total.toLocaleString()"
                    description="All time applications"
                    :icon="FileText"
                />
                <StatsCard
                    title="Pending Review"
                    :value="stats.pending.toLocaleString()"
                    description="Awaiting action"
                    :icon="Clock"
                    class="bg-yellow-50/50 dark:bg-yellow-900/10"
                />
                <StatsCard
                    title="Admitted"
                    :value="stats.admitted.toLocaleString()"
                    description="Successfully enrolled"
                    :icon="CheckCircle"
                    class="bg-green-50/50 dark:bg-green-900/10"
                />
                <StatsCard
                    title="Rejected"
                    :value="stats.rejected.toLocaleString()"
                    description="Applications turned down"
                    :icon="XCircle"
                    class="bg-red-50/50 dark:bg-red-900/10"
                />
            </div>

            <!-- Filters & Actions -->
            <div class="flex flex-col md:flex-row gap-4 items-center bg-card p-4 rounded-lg border shadow-sm">
                <div class="relative w-full md:w-96">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Search by name, email, or JAMB no..."
                        class="pl-9"
                    />
                </div>
                
                <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto">
                    <div class="flex items-center gap-2">
                         <Filter class="h-4 w-4 text-muted-foreground" />
                         <span class="text-sm font-medium">Filters:</span>
                    </div>
                    
                    <select 
                        v-model="status" 
                        class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="submitted">Submitted</option>
                        <option value="screening">Screening</option>
                        <option value="admitted">Admitted</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    <select 
                        v-model="programme" 
                        class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 max-w-[200px]"
                    >
                        <option value="">All Programs</option>
                        <option v-for="prog in programmes" :key="prog.id" :value="prog.id">
                            {{ prog.name }}
                        </option>
                    </select>

                    <Button 
                        v-if="search || status || programme" 
                        variant="ghost" 
                        size="sm" 
                        @click="clearFilters"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        Clear
                    </Button>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="rounded-md border bg-card shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Applicant Details</TableHead>
                            <TableHead>JAMB / Program</TableHead>
                            <TableHead>Date Applied</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="applicant in applicants.data" :key="applicant.id">
                            <TableCell>
                                <div class="flex flex-col">
                                    <span class="font-medium text-foreground">{{ applicant.user.name }}</span>
                                    <span class="text-xs text-muted-foreground">{{ applicant.user.email }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-col gap-1">
                                    <Badge variant="outline" class="w-fit font-mono text-xs">
                                        {{ applicant.jamb_registration_number }}
                                    </Badge>
                                    <span class="text-xs text-muted-foreground truncate max-w-[200px]" :title="applicant.programme?.name">
                                        {{ applicant.programme?.name || 'N/A' }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="text-muted-foreground text-sm">
                                {{ format(new Date(applicant.created_at), 'MMM d, yyyy') }}
                            </TableCell>
                            <TableCell>
                                <Badge :class="getStatusColorClass(applicant.status)" class="capitalize shadow-none">
                                    {{ applicant.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <Link :href="route('admin.admissions.show', applicant.id)">
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                        <Eye class="h-4 w-4 text-muted-foreground hover:text-primary" />
                                        <span class="sr-only">View</span>
                                    </Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="applicants.data.length === 0">
                            <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                No applications found matching your criteria.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AdminLayout>
</template>
