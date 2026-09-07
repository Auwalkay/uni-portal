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
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { 
    AlertCircle, Plus, Info, CheckCircle, Trash2, FileText, User, Phone, 
    Search, Clock, Calendar, Check, Send, AlertTriangle, Eye, HelpCircle, ShieldAlert 
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { ref, computed } from 'vue';

interface Props {
    complaints: {
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
const selectedComplaint = ref<any>(null);
const isResolutionOpen = ref(false);
const isViewModalOpen = ref(false);
const viewingComplaint = ref<any>(null);
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

// Computed KPI stats
const pendingCount = computed(() => props.complaints.data.filter(c => c.status === 'pending').length);
const resolvedCount = computed(() => props.complaints.data.filter(c => c.status === 'resolved').length);
const totalCount = computed(() => props.complaints.total || props.complaints.data.length);

const submit = () => {
    form.post(route('admin.front-desk.complaints.store'), {
        onSuccess: () => {
            isAddingFormOpen.value = false;
            form.reset();
        },
    });
};

const openViewModal = (complaint: any) => {
    viewingComplaint.value = complaint;
    isViewModalOpen.value = true;
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
    if (confirm('Are you sure you want to delete this complaint record?')) {
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
    <Head title="Complaints & Feedback - Front Desk" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full">
            
            <!-- Hero Header & Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
                            <AlertCircle class="w-6 h-6" />
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                            Complaints & Feedback
                        </h1>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 pl-10">
                        Log, track, and resolve complaints submitted by students, visitors, or university staff.
                    </p>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <Dialog v-model:open="isAddingFormOpen">
                        <DialogTrigger as-child>
                            <Button class="bg-rose-600 hover:bg-rose-700 text-white shadow-sm font-semibold gap-2">
                                <Plus class="w-4 h-4" />
                                <span>Log New Complaint</span>
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[500px] border-rose-100">
                            <DialogHeader class="border-b pb-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mt-6 pt-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2.5 bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-xl">
                                        <AlertTriangle class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Record New Complaint</DialogTitle>
                                        <DialogDescription class="text-xs text-slate-500">Document complainant information and issue details.</DialogDescription>
                                    </div>
                                </div>
                            </DialogHeader>
                            <form @submit.prevent="submit" class="space-y-4 py-3">
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
                                    <Label for="subject" class="text-xs font-bold uppercase tracking-wider text-slate-500">Subject / Category</Label>
                                    <div class="relative">
                                        <Input id="subject" v-model="form.subject" placeholder="E.g. Hostel Water Supply Issues" class="pl-9" required />
                                        <FileText class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="description" class="text-xs font-bold uppercase tracking-wider text-slate-500">Description</Label>
                                    <Textarea id="description" v-model="form.description" rows="4" placeholder="Provide complete details of the complaint reported..." required />
                                </div>

                                <div class="p-3 bg-rose-50/60 dark:bg-rose-950/20 border border-rose-200/60 dark:border-rose-900/40 rounded-lg flex items-start gap-2.5 text-xs text-rose-800 dark:text-rose-300">
                                    <Info class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                                    <p>Complaints are saved as "Pending". You can assign resolutions and update status to "Resolved" later.</p>
                                </div>

                                <DialogFooter class="border-t pt-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mb-6 pb-6">
                                    <Button type="button" variant="ghost" @click="isAddingFormOpen = false">Cancel</Button>
                                    <Button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white" :disabled="form.processing">
                                        <Send class="w-3.5 h-3.5 mr-2" /> Submit Complaint
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
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Complaints</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-slate-100">{{ totalCount }}</p>
                        </div>
                        <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-300">
                            <AlertCircle class="w-6 h-6" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">Pending Resolution</p>
                            <p class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ pendingCount }}</p>
                        </div>
                        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 rounded-xl text-rose-600 dark:text-rose-400">
                            <Clock class="w-6 h-6" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Resolved Tickets</p>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ resolvedCount }}</p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl text-emerald-600 dark:text-emerald-400">
                            <CheckCircle class="w-6 h-6" />
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
                            placeholder="Search ticket ID, complainant..." 
                            @input="handleSearch"
                            class="pl-9 h-10 focus-visible:ring-rose-500"
                        />
                        <Search class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    </div>

                    <div class="text-xs text-slate-500 font-medium">
                        Showing {{ props.complaints.from || (props.complaints.data.length ? 1 : 0) }} to {{ props.complaints.to || props.complaints.data.length }} of {{ totalCount }} complaints
                    </div>
                </div>
            </Card>

            <!-- Table Card -->
            <Card class="border shadow-sm rounded-xl overflow-hidden bg-white dark:bg-slate-900">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-slate-50 dark:bg-slate-950/60">
                            <TableRow>
                                <TableHead class="font-bold py-3.5 pl-6 text-xs uppercase tracking-wider">Ticket ID</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Complainant</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Subject</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Date Reported</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Status</TableHead>
                                <TableHead class="font-bold py-3.5 pr-6 text-right text-xs uppercase tracking-wider">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="complaint in complaints.data" 
                                :key="complaint.id" 
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-950/30 transition-all border-b"
                            >
                                <TableCell class="font-mono text-xs font-bold text-rose-700 dark:text-rose-400 pl-6">
                                    {{ complaint.reference_id }}
                                </TableCell>
                                <TableCell>
                                    <span class="font-bold text-slate-800 dark:text-slate-100 text-sm block">{{ complaint.complainant_name }}</span>
                                    <span class="text-xs text-slate-500 block font-medium mt-0.5 flex items-center gap-1">
                                        <Phone class="w-3 h-3 text-slate-400" /> {{ complaint.phone }}
                                    </span>
                                </TableCell>
                                <TableCell class="max-w-xs">
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">
                                        {{ complaint.subject }}
                                    </p>
                                    <p class="text-xs text-slate-500 truncate mt-0.5">
                                        {{ complaint.description }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                        <Calendar class="w-3.5 h-3.5 text-slate-400" />
                                        <span>{{ format(new Date(complaint.created_at), 'MMM dd, yyyy HH:mm') }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="[
                                        'px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider inline-flex items-center gap-1 border',
                                        complaint.status === 'resolved' 
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40' 
                                            : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40'
                                    ]">
                                        <CheckCircle v-if="complaint.status === 'resolved'" class="w-3 h-3 text-emerald-600" />
                                        <Clock v-else class="w-3 h-3 text-rose-600" />
                                        <span>{{ complaint.status }}</span>
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right pr-6">
                                    <div class="flex justify-end items-center gap-1.5">
                                        <!-- View Single Complaint -->
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            class="h-8 border-slate-200 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                                            @click="openViewModal(complaint)"
                                            title="View details"
                                        >
                                            <Eye class="w-3.5 h-3.5 mr-1" /> View
                                        </Button>

                                        <!-- Resolve / Update -->
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            class="h-8 border-rose-200 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30"
                                            @click="openResolution(complaint)"
                                        >
                                            {{ complaint.status === 'resolved' ? 'Edit Notes' : 'Resolve' }}
                                        </Button>

                                        <!-- Delete -->
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30 h-8 w-8" 
                                            @click="deleteRecord(complaint.id)" 
                                            title="Delete record"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="complaints.data.length === 0">
                                <TableCell colspan="6" class="h-32 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <HelpCircle class="w-8 h-8 text-slate-300" />
                                        <p class="font-medium text-slate-500">No complaints logged yet</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination Component -->
            <Pagination :links="complaints.links" />

        </div>

        <!-- View Single Complaint Modal -->
        <Dialog v-model:open="isViewModalOpen">
            <DialogContent class="sm:max-w-[550px] border-rose-100 overflow-hidden">
                <DialogHeader class="border-b pb-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mt-6 pt-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-xl">
                                <ShieldAlert class="w-5 h-5" />
                            </div>
                            <div>
                                <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Complaint Details</DialogTitle>
                                <DialogDescription class="text-xs font-mono text-rose-600 dark:text-rose-400">
                                    Ref: {{ viewingComplaint?.reference_id }}
                                </DialogDescription>
                            </div>
                        </div>
                        <Badge v-if="viewingComplaint" :class="[
                            'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider',
                            viewingComplaint.status === 'resolved' 
                                ? 'bg-emerald-100 text-emerald-800' 
                                : 'bg-rose-100 text-rose-800'
                        ]">
                            {{ viewingComplaint.status }}
                        </Badge>
                    </div>
                </DialogHeader>

                <div v-if="viewingComplaint" class="space-y-4 py-4 text-sm">
                    <!-- Complainant Info Grid -->
                    <div class="grid grid-cols-2 gap-3 p-3.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200/60 dark:border-slate-800">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Complainant Name</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ viewingComplaint.complainant_name }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Date Reported</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">
                                {{ format(new Date(viewingComplaint.created_at), 'MMMM dd, yyyy HH:mm') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Phone</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ viewingComplaint.phone }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Subject</span>
                            <span class="font-semibold text-rose-600 dark:text-rose-400">{{ viewingComplaint.subject }}</span>
                        </div>
                    </div>

                    <!-- Description Box -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Complaint Description</Label>
                        <div class="p-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-800 dark:text-slate-200 font-medium whitespace-pre-wrap leading-relaxed">
                            {{ viewingComplaint.description }}
                        </div>
                    </div>

                    <!-- Resolution Notes Box -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Resolution & Action Taken</Label>
                        <div v-if="viewingComplaint.resolution_notes" class="p-3.5 bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40 rounded-xl text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-relaxed">
                            {{ viewingComplaint.resolution_notes }}
                        </div>
                        <div v-else class="p-3.5 bg-slate-50 dark:bg-slate-900/50 border border-dashed border-slate-300 dark:border-slate-700 rounded-xl text-slate-400 text-xs italic">
                            No resolution notes logged for this complaint yet.
                        </div>
                    </div>
                </div>

                <DialogFooter class="border-t pt-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mb-6 pb-6 flex items-center justify-between">
                    <Button variant="outline" @click="isViewModalOpen = false">Close</Button>
                    <Button 
                        v-if="viewingComplaint"
                        class="bg-rose-600 hover:bg-rose-700 text-white" 
                        @click="isViewModalOpen = false; openResolution(viewingComplaint);"
                    >
                        {{ viewingComplaint.status === 'resolved' ? 'Edit Resolution' : 'Resolve Complaint' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Resolution Dialog -->
        <Dialog v-model:open="isResolutionOpen">
            <DialogContent class="sm:max-w-[500px] overflow-hidden border-indigo-50">
                <DialogHeader class="border-b pb-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mt-6 pt-6">
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
                    <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900 border text-xs space-y-1.5">
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
                <DialogFooter class="border-t pt-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mb-6 pb-6">
                    <Button variant="ghost" @click="isResolutionOpen = false">Close</Button>
                    <Button class="bg-indigo-600 hover:bg-indigo-700 text-white" @click="saveResolution" :disabled="form.processing">
                        <Check class="w-4 h-4 mr-2" /> Save Resolution
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
