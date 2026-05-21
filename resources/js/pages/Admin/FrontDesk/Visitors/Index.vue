<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Users, UserPlus, Phone, Briefcase, UserCheck, Clock, Trash2, Edit } from 'lucide-vue-next';
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
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Visitors Log</h1>
                    <p class="text-slate-500 dark:text-slate-400">Track and manage campus access.</p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative w-full md:w-64">
                        <Input 
                            v-model="search" 
                            placeholder="Search by ID, name..." 
                            @input="handleSearch"
                            class="pl-9"
                        />
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                    </div>
                    
                    <Dialog v-model:open="isAddingFormOpen">
                    <DialogTrigger as-child>
                        <Button class="bg-indigo-600 hover:bg-indigo-700">
                            <UserPlus class="w-4 h-4 mr-2" /> Register New Visitor
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Visitor Registration</DialogTitle>
                            <DialogDescription>Enter visitor details to check them in.</DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="name">Visitor Name</Label>
                                <Input id="name" v-model="form.visitor_name" placeholder="John Doe" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="phone">Phone Number</Label>
                                <Input id="phone" v-model="form.phone" placeholder="+234 800 000 0000" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="whom">Whom to See</Label>
                                <Input id="whom" v-model="form.whom_to_see" placeholder="Registrar" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="purpose">Purpose of Visit</Label>
                                <Input id="purpose" v-model="form.purpose" placeholder="Enquiry about admission" />
                            </div>
                            <DialogFooter>
                                <Button type="submit" :disabled="form.processing">Check In Visitor</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>

        <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Visitor</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Whom to See</TableHead>
                                <TableHead>Purpose</TableHead>
                                <TableHead>Time Info</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="visitor in visitors.data" :key="visitor.id">
                                <TableCell class="font-mono text-xs">{{ visitor.reference_id }}</TableCell>
                                <TableCell class="font-medium">{{ visitor.visitor_name }}</TableCell>
                                <TableCell>{{ visitor.phone }}</TableCell>
                                <TableCell>{{ visitor.whom_to_see || 'N/A' }}</TableCell>
                                <TableCell>{{ visitor.purpose }}</TableCell>
                                <TableCell>
                                    <div class="flex flex-col text-xs text-slate-500">
                                        <span class="flex items-center gap-1"><Clock class="w-3 h-3" /> In: {{ format(new Date(visitor.check_in), 'MMM dd, HH:mm') }}</span>
                                        <span v-if="visitor.check_out" class="flex items-center gap-1 text-emerald-600 font-medium"><Clock class="w-3 h-3" /> Out: {{ format(new Date(visitor.check_out), 'MMM dd, HH:mm') }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="visitor.check_out ? 'secondary' : 'default'">
                                        {{ visitor.check_out ? 'Checked Out' : 'On Campus' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button v-if="!visitor.check_out" variant="outline" size="sm" @click="checkOut(visitor.id)" :disabled="form.processing">
                                            Check Out
                                        </Button>
                                        <Button variant="ghost" size="icon" class="text-rose-600" @click="deleteRecord(visitor.id)">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="visitors.data.length === 0">
                                <TableCell colspan="8" class="h-24 text-center">No visitors found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
