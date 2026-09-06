<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { 
    Users, UserPlus, Phone, Briefcase, UserCheck, Clock, Trash2, Shield, 
    Search, ArrowRight, User, Eye, CheckCircle2, HelpCircle, Building2, Calendar
} from 'lucide-vue-next';
import { format, differenceInMinutes, differenceInHours } from 'date-fns';
import { ref, computed } from 'vue';

interface Props {
    visitors: {
        data: any[];
        links: any[];
        total?: number;
        from?: number;
        to?: number;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const isAddingFormOpen = ref(false);
const isViewModalOpen = ref(false);
const viewingVisitor = ref<any>(null);
const search = ref(props.filters.search || '');

const breadcrumbs = [
    { title: 'Front Desk', href: route('admin.front-desk.dashboard') },
    { title: 'Visitors', href: '#' },
];

const form = useForm({
    visitor_name: '',
    phone: '',
    purpose: '',
    whom_to_see: '',
});

// Computed KPI stats
const onCampusCount = computed(() => props.visitors.data.filter(v => !v.check_out).length);
const checkedOutCount = computed(() => props.visitors.data.filter(v => v.check_out).length);
const totalCount = computed(() => props.visitors.total || props.visitors.data.length);

const submit = () => {
    form.post(route('admin.front-desk.visitors.store'), {
        onSuccess: () => {
            isAddingFormOpen.value = false;
            form.reset();
        },
        onError: () => {
            console.log(form.errors);
        }
    });
};

const openViewModal = (visitor: any) => {
    viewingVisitor.value = visitor;
    isViewModalOpen.value = true;
};

const checkOut = (id: string) => {
    router.put(route('admin.front-desk.visitors.update', id), {
        check_out: true
    }, {
        preserveScroll: true,
        onSuccess: () => {
            if (viewingVisitor.value && viewingVisitor.value.id === id) {
                isViewModalOpen.value = false;
            }
        }
    });
};

const deleteRecord = (id: string) => {
    if (confirm('Are you sure you want to delete this visitor record?')) {
        form.delete(route('admin.front-desk.visitors.destroy', id), {
            preserveScroll: true,
        });
    }
};

const handleSearch = () => {
    router.get(route('admin.front-desk.visitors.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};

const formatDuration = (checkIn: string, checkOut: string) => {
    if (!checkIn || !checkOut) return 'N/A';
    const start = new Date(checkIn);
    const end = new Date(checkOut);
    const totalMinutes = differenceInMinutes(end, start);
    if (totalMinutes < 60) {
        return `${totalMinutes} min${totalMinutes !== 1 ? 's' : ''}`;
    }
    const hours = differenceInHours(end, start);
    const remainingMinutes = totalMinutes % 60;
    return `${hours} hr${hours !== 1 ? 's' : ''} ${remainingMinutes} mins`;
};
</script>

<template>
    <Head title="Visitor Management - Front Desk" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full">
            
            <!-- Hero Header & Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                            <Users class="w-6 h-6" />
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                            Visitors Log
                        </h1>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 pl-10">
                        Authorize, track, and monitor guest entry and departure across campus premises.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Dialog v-model:open="isAddingFormOpen">
                        <DialogTrigger as-child>
                            <Button class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm font-semibold gap-2">
                                <UserPlus class="w-4 h-4" />
                                <span>Register Guest</span>
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[480px] border-indigo-100">
                            <DialogHeader class="border-b pb-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mt-6 pt-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2.5 bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-xl">
                                        <UserPlus class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Guest Registration</DialogTitle>
                                        <DialogDescription class="text-xs text-slate-500">Issue campus authorization pass for visitors.</DialogDescription>
                                    </div>
                                </div>
                            </DialogHeader>
                            <form @submit.prevent="submit" class="space-y-4 py-3">
                                <div class="space-y-1.5">
                                    <Label for="name" class="text-xs font-bold uppercase tracking-wider text-slate-500">Visitor Name</Label>
                                    <div class="relative">
                                        <Input id="name" v-model="form.visitor_name" placeholder="Full name of guest" class="pl-9" required />
                                        <User class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="phone" class="text-xs font-bold uppercase tracking-wider text-slate-500">Phone Number</Label>
                                    <div class="relative">
                                        <Input id="phone" v-model="form.phone" placeholder="+234 800 000 0000" class="pl-9" required />
                                        <Phone class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="whom" class="text-xs font-bold uppercase tracking-wider text-slate-500">Whom to See</Label>
                                    <div class="relative">
                                        <Input id="whom" v-model="form.whom_to_see" placeholder="E.g. Dr. Johnson / HOD Computer Science" class="pl-9" required />
                                        <UserCheck class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="purpose" class="text-xs font-bold uppercase tracking-wider text-slate-500">Purpose of Visit</Label>
                                    <div class="relative">
                                        <Input id="purpose" v-model="form.purpose" placeholder="Admission Enquiry, Official Meeting" class="pl-9" required />
                                        <Briefcase class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                    </div>
                                </div>

                                <div class="p-3 bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-200/60 dark:border-indigo-900/40 rounded-lg flex items-start gap-2.5 text-xs text-indigo-800 dark:text-indigo-300">
                                    <Shield class="w-4 h-4 text-indigo-600 shrink-0 mt-0.5" />
                                    <p>The check-in timestamp will be recorded automatically upon registration.</p>
                                </div>

                                <DialogFooter class="border-t pt-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mb-6 pb-6">
                                    <Button type="button" variant="ghost" @click="isAddingFormOpen = false">Cancel</Button>
                                    <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white" :disabled="form.processing">
                                        {{ form.processing ? 'Checking in...' : 'Confirm & Check In' }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- KPI Metric Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Recorded Guests</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-slate-100">{{ totalCount }}</p>
                        </div>
                        <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-300">
                            <Users class="w-6 h-6" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Currently On Campus</p>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ onCampusCount }}</p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl text-emerald-600 dark:text-emerald-400">
                            <Clock class="w-6 h-6" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">Checked Out</p>
                            <p class="text-2xl font-black text-slate-700 dark:text-slate-300">{{ checkedOutCount }}</p>
                        </div>
                        <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-500 dark:text-slate-400">
                            <UserCheck class="w-6 h-6" />
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Filter & Search Toolbar -->
            <Card class="border shadow-sm bg-white dark:bg-slate-900 p-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="relative w-full sm:w-80">
                        <Input 
                            v-model="search" 
                            placeholder="Search pass ID, visitor name..." 
                            @input="handleSearch"
                            class="pl-9 h-10 focus-visible:ring-indigo-500"
                        />
                        <Search class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    </div>

                    <div class="text-xs text-slate-500 font-medium">
                        Showing {{ props.visitors.from || (props.visitors.data.length ? 1 : 0) }} to {{ props.visitors.to || props.visitors.data.length }} of {{ totalCount }} visitor logs
                    </div>
                </div>
            </Card>

            <!-- Table Card -->
            <Card class="border shadow-sm rounded-xl overflow-hidden bg-white dark:bg-slate-900">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-slate-50 dark:bg-slate-950/60">
                            <TableRow>
                                <TableHead class="font-bold py-3.5 pl-6 text-xs uppercase tracking-wider">Pass ID</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Visitor Name</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Contact</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Whom to See</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Purpose</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Status</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Timeline</TableHead>
                                <TableHead class="font-bold py-3.5 pr-6 text-right text-xs uppercase tracking-wider">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="visitor in visitors.data" 
                                :key="visitor.id" 
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-950/30 transition-all border-b"
                            >
                                <TableCell class="font-mono text-xs font-bold text-indigo-700 dark:text-indigo-400 pl-6">
                                    {{ visitor.reference_id }}
                                </TableCell>
                                <TableCell>
                                    <span class="font-bold text-slate-800 dark:text-slate-100 text-sm block">{{ visitor.visitor_name }}</span>
                                </TableCell>
                                <TableCell class="text-sm font-medium text-slate-600 dark:text-slate-400">
                                    <span class="flex items-center gap-1"><Phone class="w-3 h-3 text-slate-400" /> {{ visitor.phone }}</span>
                                </TableCell>
                                <TableCell class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    <div class="inline-flex items-center gap-1.5">
                                        <UserCheck class="w-3.5 h-3.5 text-slate-400" />
                                        <span>{{ visitor.whom_to_see || 'N/A' }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-sm text-slate-600 dark:text-slate-350 max-w-xs truncate font-medium">
                                    {{ visitor.purpose }}
                                </TableCell>
                                <TableCell>
                                    <Badge :class="[
                                        'px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider inline-flex items-center gap-1.5 border',
                                        visitor.check_out 
                                            ? 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700' 
                                            : 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40'
                                    ]">
                                        <span v-if="!visitor.check_out" class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                        <span>{{ visitor.check_out ? 'Checked Out' : 'On Campus' }}</span>
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col text-[11px] text-slate-500 space-y-0.5">
                                        <span class="flex items-center gap-1 font-medium"><Clock class="w-3 h-3 text-indigo-500" /> In: {{ format(new Date(visitor.check_in), 'MMM dd, HH:mm') }}</span>
                                        <span v-if="visitor.check_out" class="flex items-center gap-1 text-slate-400"><Clock class="w-3 h-3 text-slate-400" /> Out: {{ format(new Date(visitor.check_out), 'MMM dd, HH:mm') }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right pr-6">
                                    <div class="flex justify-end items-center gap-1.5">
                                        <!-- View Visitor Modal -->
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            class="h-8 border-slate-200 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                                            @click="openViewModal(visitor)"
                                            title="View visitor details"
                                        >
                                            <Eye class="w-3.5 h-3.5 mr-1" /> View
                                        </Button>

                                        <!-- Check Out Button -->
                                        <Button 
                                            v-if="!visitor.check_out" 
                                            size="sm" 
                                            variant="outline" 
                                            class="h-8 border-emerald-200 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30" 
                                            @click="checkOut(visitor.id)"
                                        >
                                            <UserCheck class="w-3.5 h-3.5 mr-1" /> Check Out
                                        </Button>

                                        <!-- Delete -->
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30 h-8 w-8" 
                                            @click="deleteRecord(visitor.id)" 
                                            title="Delete record"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="visitors.data.length === 0">
                                <TableCell colspan="8" class="h-32 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <HelpCircle class="w-8 h-8 text-slate-300" />
                                        <p class="font-medium text-slate-500">No visitor logs found</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination Component -->
            <Pagination :links="visitors.links" />

        </div>

        <!-- Single Visitor Detail View Dialog -->
        <Dialog v-model:open="isViewModalOpen">
            <DialogContent class="sm:max-w-[550px] border-indigo-100 overflow-hidden">
                <DialogHeader class="border-b pb-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mt-6 pt-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-xl">
                                <Shield class="w-5 h-5" />
                            </div>
                            <div>
                                <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Visitor Pass Details</DialogTitle>
                                <DialogDescription class="text-xs font-mono text-indigo-600 dark:text-indigo-400">
                                    Pass ID: {{ viewingVisitor?.reference_id }}
                                </DialogDescription>
                            </div>
                        </div>
                        <Badge v-if="viewingVisitor" :class="[
                            'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider',
                            viewingVisitor.check_out 
                                ? 'bg-slate-100 text-slate-700' 
                                : 'bg-emerald-100 text-emerald-800'
                        ]">
                            {{ viewingVisitor.check_out ? 'Checked Out' : 'On Campus' }}
                        </Badge>
                    </div>
                </DialogHeader>

                <div v-if="viewingVisitor" class="space-y-4 py-4 text-sm">
                    <!-- Visitor Info Grid -->
                    <div class="grid grid-cols-2 gap-3 p-3.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200/60 dark:border-slate-800">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Visitor Name</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ viewingVisitor.visitor_name }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Phone Number</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ viewingVisitor.phone }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Whom to See</span>
                            <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ viewingVisitor.whom_to_see || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Purpose of Visit</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ viewingVisitor.purpose }}</span>
                        </div>
                    </div>

                    <!-- Visit Timeline Card -->
                    <div class="p-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Visit Timeline & Duration</span>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center gap-2">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 rounded-lg">
                                    <Clock class="w-4 h-4" />
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase block">Check In</span>
                                    <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                        {{ format(new Date(viewingVisitor.check_in), 'MMM dd, yyyy HH:mm') }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <div :class="[
                                    'p-2 rounded-lg',
                                    viewingVisitor.check_out ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' : 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400'
                                ]">
                                    <Clock class="w-4 h-4" />
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase block">Check Out</span>
                                    <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                        {{ viewingVisitor.check_out ? format(new Date(viewingVisitor.check_out), 'MMM dd, yyyy HH:mm') : 'Pending departure' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-if="viewingVisitor.check_out" class="pt-2 border-t text-xs text-slate-500 flex items-center justify-between">
                            <span>Total Duration of Visit:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">
                                {{ formatDuration(viewingVisitor.check_in, viewingVisitor.check_out) }}
                            </span>
                        </div>
                    </div>
                </div>

                <DialogFooter class="border-t pt-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mb-6 pb-6 flex items-center justify-between">
                    <Button variant="outline" @click="isViewModalOpen = false">Close</Button>
                    <Button 
                        v-if="viewingVisitor && !viewingVisitor.check_out"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white" 
                        @click="checkOut(viewingVisitor.id)"
                    >
                        <UserCheck class="w-4 h-4 mr-2" /> Check Out Visitor
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
