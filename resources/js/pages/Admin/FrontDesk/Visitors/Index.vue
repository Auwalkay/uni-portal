<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Users, UserPlus, Phone, Briefcase, UserCheck, Clock, Trash2, Shield, Search, ArrowRight, User } from 'lucide-vue-next';
import { format } from 'date-fns';
import { ref } from 'vue';

interface Props {
    visitors: {
        data: any[];
        links: any[];
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();
const isAddingFormOpen = ref(false);
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

const checkOut = (id: string) => {
    router.put(route('admin.front-desk.visitors.update', id), {
        check_out: true
    }, {
        preserveScroll: true,
    });
};

const deleteRecord = (id: string) => {
    if (confirm('Are you sure you want to delete this record?')) {
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
</script>

<template>
    <Head title="Visitor Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-3">
                        <Users class="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
                        <span>Visitors Log</span>
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Track and authorize campus guest check-ins and departures.</p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <Input 
                            v-model="search" 
                            placeholder="Search by ID, visitor name..." 
                            @input="handleSearch"
                            class="pl-9 h-10 shadow-sm focus-visible:ring-indigo-500"
                        />
                        <Search class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    </div>
                    
                    <!-- Dialog for New Visitor -->
                    <Dialog v-model:open="isAddingFormOpen">
                        <DialogTrigger as-child>
                            <Button class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-md transition-all duration-200 h-10 font-semibold">
                                <UserPlus class="w-4 h-4 mr-2" /> Register Guest
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[450px] overflow-hidden border-indigo-100">
                            <DialogHeader class="border-b pb-4 bg-slate-50/50 -mx-6 px-6 -mt-6 pt-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                        <UserPlus class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Guest Registration</DialogTitle>
                                        <DialogDescription class="text-xs">Issue campus authorization pass for visitors.</DialogDescription>
                                    </div>
                                </div>
                            </DialogHeader>
                            <form @submit.prevent="submit" class="space-y-4 py-4">
                                <div class="space-y-1.5">
                                    <Label for="name" class="text-xs font-bold uppercase tracking-wider text-slate-500">Visitor Name</Label>
                                    <div class="relative">
                                        <Input id="name" v-model="form.visitor_name" placeholder="John Doe" class="pl-9" required />
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
                                        <Input id="whom" v-model="form.whom_to_see" placeholder="E.g. Vice Chancellor, Registrar" class="pl-9" required />
                                        <UserCheck class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="purpose" class="text-xs font-bold uppercase tracking-wider text-slate-500">Purpose of Visit</Label>
                                    <div class="relative">
                                        <Input id="purpose" v-model="form.purpose" placeholder="Admission Enquiry, Meeting" class="pl-9" required />
                                        <Briefcase class="absolute left-3 top-3 w-4 h-4 text-slate-450 text-slate-400" />
                                    </div>
                                </div>

                                <div class="p-3 bg-amber-50/60 dark:bg-amber-950/20 border border-amber-100 rounded-lg flex items-start gap-2.5 text-xs text-amber-850 dark:text-amber-300">
                                    <Shield class="w-4.5 h-4.5 text-amber-600 shrink-0 mt-0.5" />
                                    <p>By registering, this visit will be logged and timestamped under your active Receptionist account.</p>
                                </div>

                                <DialogFooter class="border-t pt-4 bg-slate-50/50 -mx-6 px-6 -mb-6 pb-6">
                                    <Button type="button" variant="ghost" @click="isAddingFormOpen = false">Cancel</Button>
                                    <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700" :disabled="form.processing">
                                        {{ form.processing ? 'Checking in...' : 'Confirm & Check In' }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Visitors Table Card -->
            <Card class="border shadow-md rounded-xl overflow-hidden bg-white dark:bg-slate-900">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-slate-50 dark:bg-slate-950/40">
                            <TableRow>
                                <TableHead class="font-bold py-4 pl-6 text-xs uppercase tracking-wider">Pass ID</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Visitor Details</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Contact</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Whom to See</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Purpose</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Status</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Timeline</TableHead>
                                <TableHead class="font-bold py-4 pr-6 text-right text-xs uppercase tracking-wider">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="visitor in visitors.data" :key="visitor.id" class="hover:bg-slate-50/40 dark:hover:bg-slate-950/20 transition-all border-b">
                                <TableCell class="font-mono text-xs font-bold text-slate-600 dark:text-slate-400 pl-6">{{ visitor.reference_id }}</TableCell>
                                <TableCell>
                                    <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm block">{{ visitor.visitor_name }}</span>
                                </TableCell>
                                <TableCell class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ visitor.phone }}</TableCell>
                                <TableCell class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    <div class="inline-flex items-center gap-1.5">
                                        <UserCheck class="w-3.5 h-3.5 text-slate-400" />
                                        <span>{{ visitor.whom_to_see || 'N/A' }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-sm text-slate-600 dark:text-slate-350 max-w-xs truncate">{{ visitor.purpose }}</TableCell>
                                <TableCell>
                                    <Badge :class="[
                                        'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 w-fit border',
                                        visitor.check_out 
                                            ? 'bg-slate-100/80 text-slate-700 border-slate-200' 
                                            : 'bg-emerald-50 text-emerald-800 border-emerald-100'
                                    ]">
                                        <span v-if="!visitor.check_out" class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                        <span>{{ visitor.check_out ? 'Checked Out' : 'On Campus' }}</span>
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col text-[11px] text-slate-500 space-y-1">
                                        <span class="flex items-center gap-1"><Clock class="w-3 h-3 text-indigo-400" /> In: {{ format(new Date(visitor.check_in), 'MMM dd, HH:mm') }}</span>
                                        <span v-if="visitor.check_out" class="flex items-center gap-1 text-slate-400"><Clock class="w-3 h-3 text-slate-400" /> Out: {{ format(new Date(visitor.check_out), 'MMM dd, HH:mm') }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right pr-6">
                                    <div class="flex justify-end items-center gap-2">
                                        <Button v-if="!visitor.check_out" size="sm" variant="outline" class="text-emerald-700 border-emerald-100 hover:bg-emerald-50 h-8" @click="checkOut(visitor.id)" :disabled="form.processing">
                                            <UserCheck class="w-3.5 h-3.5 mr-1" /> Check Out
                                        </Button>
                                        <Button variant="ghost" size="icon" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 h-8 w-8" @click="deleteRecord(visitor.id)" title="Delete record">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="visitors.data.length === 0">
                                <TableCell colspan="8" class="h-28 text-center text-slate-400 font-medium">No visitor logs found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
