<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { AlertCircle, Plus, Info, CheckCircle, Trash2, FileText, User, Phone, Search, Clock, Calendar, Check, Send, AlertTriangle } from 'lucide-vue-next';
import { format } from 'date-fns';
import { ref } from 'vue';

interface Props {
    complaints: {
        data: any[];
        links: any[];
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();
const isAddingFormOpen = ref(false);
const selectedComplaint = ref<any>(null);
const isResolutionOpen = ref(false);
const search = ref(props.filters.search || '');

const breadcrumbs = [
    { title: 'Front Desk', href: route('admin.front-desk.dashboard') },
    { title: 'Complaints', href: '#' },
];

const form = useForm({
    complainant_name: '',
    phone: '',
    subject: '',
    description: '',
    status: 'pending',
    resolution_notes: '',
});

const submit = () => {
    form.post(route('admin.front-desk.complaints.store'), {
        onSuccess: () => {
            isAddingFormOpen.value = false;
            form.reset();
        },
    });
};

const openResolution = (complaint: any) => {
    selectedComplaint.value = complaint;
    form.status = complaint.status;
    form.resolution_notes = complaint.resolution_notes || '';
    isResolutionOpen.value = true;
};

const saveResolution = () => {
    form.put(route('admin.front-desk.complaints.update', selectedComplaint.value.id), {
        onSuccess: () => {
            isResolutionOpen.value = false;
            selectedComplaint.value = null;
        },
    });
};

const deleteRecord = (id: string) => {
    if (confirm('Are you sure?')) {
        form.delete(route('admin.front-desk.complaints.destroy', id));
    }
};

const handleSearch = () => {
    router.get(route('admin.front-desk.complaints.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Complaint Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-3">
                        <AlertCircle class="w-8 h-8 text-rose-600 dark:text-rose-450" />
                        <span>Complaints & Feedback</span>
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Track, manage, and resolve complaints reported by students or public guests.</p>
                </div>
                
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <Input 
                            v-model="search" 
                            placeholder="Search by ID, complainant..." 
                            @input="handleSearch"
                            class="pl-9 h-10 shadow-sm focus-visible:ring-rose-500"
                        />
                        <Search class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    </div>

                    <!-- Dialog to Record Complaint -->
                    <Dialog v-model:open="isAddingFormOpen">
                        <DialogTrigger as-child>
                            <Button class="bg-rose-600 hover:bg-rose-700 text-white shadow-md transition-all duration-200 h-10 font-semibold">
                                <Plus class="w-4 h-4 mr-2" /> Log Complaint
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[500px] overflow-hidden border-rose-100">
                            <DialogHeader class="border-b pb-4 bg-slate-50/50 -mx-6 px-6 -mt-6 pt-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2.5 bg-rose-50 text-rose-600 rounded-xl">
                                        <AlertTriangle class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Record New Complaint</DialogTitle>
                                        <DialogDescription class="text-xs">Document complainant information and issue details.</DialogDescription>
                                    </div>
                                </div>
                            </DialogHeader>
                            <form @submit.prevent="submit" class="space-y-4 py-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <Label for="name" class="text-xs font-bold uppercase tracking-wider text-slate-500">Complainant Name</Label>
                                        <div class="relative">
                                            <Input id="name" v-model="form.complainant_name" placeholder="John Doe" class="pl-9" required />
                                            <User class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label for="phone" class="text-xs font-bold uppercase tracking-wider text-slate-500">Phone</Label>
                                        <div class="relative">
                                            <Input id="phone" v-model="form.phone" placeholder="+234..." class="pl-9" required />
                                            <Phone class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="subject" class="text-xs font-bold uppercase tracking-wider text-slate-500">Subject</Label>
                                    <div class="relative">
                                        <Input id="subject" v-model="form.subject" placeholder="E.g. Hostel Water Shortage" class="pl-9" required />
                                        <FileText class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="description" class="text-xs font-bold uppercase tracking-wider text-slate-500">Description</Label>
                                    <Textarea id="description" v-model="form.description" rows="4" placeholder="Detail the complaint or issue reported..." required />
                                </div>

                                <div class="p-3 bg-slate-50 dark:bg-slate-900 border rounded-lg flex items-start gap-2.5 text-xs text-slate-500">
                                    <Info class="w-4.5 h-4.5 text-indigo-500 shrink-0 mt-0.5" />
                                    <p>Complaints are logged with a "Pending" status. You can resolve or update resolution notes at any time.</p>
                                </div>

                                <DialogFooter class="border-t pt-4 bg-slate-50/50 -mx-6 px-6 -mb-6 pb-6">
                                    <Button type="button" variant="ghost" @click="isAddingFormOpen = false">Cancel</Button>
                                    <Button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white" :disabled="form.processing">
                                        <Send class="w-3.5 h-3.5 mr-2" /> Log Complaint
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Complaints Table -->
            <Card class="border shadow-md rounded-xl overflow-hidden bg-white dark:bg-slate-900">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-slate-50 dark:bg-slate-950/40">
                            <TableRow>
                                <TableHead class="font-bold py-4 pl-6 text-xs uppercase tracking-wider">Ticket ID</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Complainant</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Subject</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Date Reported</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Status</TableHead>
                                <TableHead class="font-bold py-4 pr-6 text-right text-xs uppercase tracking-wider">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="complaint in complaints.data" :key="complaint.id" class="hover:bg-slate-50/40 dark:hover:bg-slate-950/20 transition-all border-b">
                                <TableCell class="font-mono text-xs font-bold text-slate-600 dark:text-slate-400 pl-6">{{ complaint.reference_id }}</TableCell>
                                <TableCell>
                                    <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm block">{{ complaint.complainant_name }}</span>
                                    <span class="text-xs text-slate-500 block font-medium mt-0.5">{{ complaint.phone }}</span>
                                </TableCell>
                                <TableCell class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ complaint.subject }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                        <Calendar class="w-3.5 h-3.5 text-slate-400" />
                                        <span>{{ format(new Date(complaint.created_at), 'MMM dd, yyyy HH:mm') }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="[
                                        'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1 w-fit border',
                                        complaint.status === 'resolved' 
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-100' 
                                            : 'bg-rose-50 text-rose-800 border-rose-100'
                                    ]">
                                        <CheckCircle v-if="complaint.status === 'resolved'" class="w-3 h-3 text-emerald-650" />
                                        <Clock v-else class="w-3 h-3 text-rose-650" />
                                        <span>{{ complaint.status }}</span>
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right pr-6">
                                    <div class="flex justify-end items-center gap-2">
                                        <Button variant="outline" size="sm" class="h-8 border-slate-200 text-slate-700 hover:bg-slate-50" @click="openResolution(complaint)">
                                            Resolve / Update
                                        </Button>
                                        <Button variant="ghost" size="icon" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 h-8 w-8" @click="deleteRecord(complaint.id)" title="Delete record">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="complaints.data.length === 0">
                                <TableCell colspan="6" class="h-28 text-center text-slate-400 font-medium">No complaints logged yet.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Resolution Dialog -->
        <Dialog v-model:open="isResolutionOpen">
            <DialogContent class="sm:max-w-[500px] overflow-hidden border-indigo-50">
                <DialogHeader class="border-b pb-4 bg-slate-50/50 -mx-6 px-6 -mt-6 pt-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                            <CheckCircle class="w-5 h-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Complaint Resolution</DialogTitle>
                            <DialogDescription class="text-xs">Document details of action taken to resolve this ticket.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>
                <div v-if="selectedComplaint" class="space-y-4 py-4">
                    <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-100 text-xs space-y-1.5">
                        <p class="text-slate-500"><strong class="text-slate-800 dark:text-slate-200 uppercase">Complainant:</strong> {{ selectedComplaint.complainant_name }}</p>
                        <p class="text-slate-500"><strong class="text-slate-800 dark:text-slate-200 uppercase">Subject:</strong> {{ selectedComplaint.subject }}</p>
                        <p class="text-slate-500"><strong class="text-slate-800 dark:text-slate-200 uppercase">Description:</strong> {{ selectedComplaint.description }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</Label>
                        <select v-model="form.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Resolution Notes</Label>
                        <Textarea v-model="form.resolution_notes" rows="4" placeholder="Enter details of actions taken to resolve this complaint..." required />
                    </div>
                </div>
                <DialogFooter class="border-t pt-4 bg-slate-50/50 -mx-6 px-6 -mb-6 pb-6">
                    <Button variant="ghost" @click="isResolutionOpen = false">Close</Button>
                    <Button class="bg-indigo-650 bg-indigo-600 hover:bg-indigo-750" @click="saveResolution" :disabled="form.processing">
                        <Check class="w-4 h-4 mr-2" /> Save Resolution
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
