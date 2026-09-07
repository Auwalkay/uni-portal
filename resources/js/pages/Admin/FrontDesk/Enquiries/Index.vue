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
    MessageSquare, Plus, Info, CheckCircle, Trash2, Mail, Phone, Search, 
    Clock, Calendar, Check, Send, User, Eye, HelpCircle, FileText, Sparkles
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { ref, computed } from 'vue';

interface Props {
    enquiries: {
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
const selectedEnquiry = ref<any>(null);
const isResponseOpen = ref(false);
const isViewModalOpen = ref(false);
const viewingEnquiry = ref<any>(null);
const search = ref(props.filters.search || '');

const breadcrumbs = [
    { title: 'Front Desk', href: route('admin.front-desk.dashboard') },
    { title: 'Enquiries', href: '#' },
];

const form = useForm({
    inquirer_name: '',
    phone: '',
    email: '',
    inquiry: '',
    response: '',
    status: 'open',
});

// Computed KPI stats from current dataset
const openCount = computed(() => props.enquiries.data.filter(e => e.status === 'open').length);
const closedCount = computed(() => props.enquiries.data.filter(e => e.status === 'closed').length);
const totalCount = computed(() => props.enquiries.total || props.enquiries.data.length);

const submit = () => {
    form.post(route('admin.front-desk.enquiries.store'), {
        onSuccess: () => {
            isAddingFormOpen.value = false;
            form.reset();
        },
    });
};

const openViewModal = (enquiry: any) => {
    viewingEnquiry.value = enquiry;
    isViewModalOpen.value = true;
};

const openResponse = (enquiry: any) => {
    selectedEnquiry.value = enquiry;
    form.response = enquiry.response || '';
    form.status = enquiry.status;
    isResponseOpen.value = true;
};

const saveResponse = () => {
    form.put(route('admin.front-desk.enquiries.update', selectedEnquiry.value.id), {
        onSuccess: () => {
            isResponseOpen.value = false;
            selectedEnquiry.value = null;
        },
    });
};

const deleteRecord = (id: string) => {
    if (confirm('Are you sure you want to delete this enquiry record?')) {
        form.delete(route('admin.front-desk.enquiries.destroy', id));
    }
};

const handleSearch = () => {
    router.get(route('admin.front-desk.enquiries.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Public Enquiries - Front Desk" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 w-full">
            
            <!-- Hero Header & Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <MessageSquare class="w-6 h-6" />
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
                            Public Enquiries
                        </h1>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 pl-10">
                        Manage, respond to, and resolve general enquiries from visitors and prospective students.
                    </p>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <Dialog v-model:open="isAddingFormOpen">
                        <DialogTrigger as-child>
                            <Button class="bg-amber-600 hover:bg-amber-700 text-white shadow-sm font-semibold gap-2">
                                <Plus class="w-4 h-4" />
                                <span>Log New Enquiry</span>
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[500px] border-amber-100">
                            <DialogHeader class="border-b pb-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mt-6 pt-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2.5 bg-amber-100 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 rounded-xl">
                                        <MessageSquare class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Log New Enquiry</DialogTitle>
                                        <DialogDescription class="text-xs text-slate-500">Record visitor contact details and inquiry details.</DialogDescription>
                                    </div>
                                </div>
                            </DialogHeader>
                            <form @submit.prevent="submit" class="space-y-4 py-3">
                                <div class="space-y-1.5">
                                    <Label for="name" class="text-xs font-bold uppercase tracking-wider text-slate-500">Inquirer Name</Label>
                                    <div class="relative">
                                        <Input id="name" v-model="form.inquirer_name" placeholder="Full name of inquirer" class="pl-9" required />
                                        <User class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <Label for="phone" class="text-xs font-bold uppercase tracking-wider text-slate-500">Phone</Label>
                                        <div class="relative">
                                            <Input id="phone" v-model="form.phone" placeholder="+234..." class="pl-9" required />
                                            <Phone class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label for="email" class="text-xs font-bold uppercase tracking-wider text-slate-500">Email (Optional)</Label>
                                        <div class="relative">
                                            <Input id="email" v-model="form.email" type="email" placeholder="john@example.com" class="pl-9" />
                                            <Mail class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="inquiry" class="text-xs font-bold uppercase tracking-wider text-slate-500">The Enquiry</Label>
                                    <Textarea id="inquiry" v-model="form.inquiry" rows="4" placeholder="Describe the inquiry or question in detail..." required />
                                </div>

                                <div class="p-3 bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40 rounded-lg flex items-start gap-2.5 text-xs text-amber-800 dark:text-amber-300">
                                    <Info class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                                    <p>Enquiries start with "Open" status. You can provide responses and mark them as resolved anytime.</p>
                                </div>

                                <DialogFooter class="border-t pt-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mb-6 pb-6">
                                    <Button type="button" variant="ghost" @click="isAddingFormOpen = false">Cancel</Button>
                                    <Button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white" :disabled="form.processing">
                                        <Send class="w-3.5 h-3.5 mr-2" /> Log Enquiry
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
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Enquiries</p>
                            <p class="text-2xl font-black text-slate-900 dark:text-slate-100">{{ totalCount }}</p>
                        </div>
                        <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-300">
                            <MessageSquare class="w-6 h-6" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Pending / Open</p>
                            <p class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ openCount }}</p>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl text-amber-600 dark:text-amber-400">
                            <Clock class="w-6 h-6" />
                        </div>
                    </div>
                </Card>

                <Card class="border shadow-sm bg-white dark:bg-slate-900 relative overflow-hidden">
                    <div class="p-5 flex items-center justify-between">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Resolved / Closed</p>
                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ closedCount }}</p>
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
                            placeholder="Search ticket ID, inquirer name..." 
                            @input="handleSearch"
                            class="pl-9 h-10 focus-visible:ring-amber-500"
                        />
                        <Search class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    </div>

                    <div class="text-xs text-slate-500 font-medium">
                        Showing {{ props.enquiries.from || (props.enquiries.data.length ? 1 : 0) }} to {{ props.enquiries.to || props.enquiries.data.length }} of {{ totalCount }} enquiries
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
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Inquirer</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Enquiry Details</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Date Logged</TableHead>
                                <TableHead class="font-bold py-3.5 text-xs uppercase tracking-wider">Status</TableHead>
                                <TableHead class="font-bold py-3.5 pr-6 text-right text-xs uppercase tracking-wider">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="enquiry in enquiries.data" 
                                :key="enquiry.id" 
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-950/30 transition-all border-b"
                            >
                                <TableCell class="font-mono text-xs font-bold text-amber-700 dark:text-amber-400 pl-6">
                                    {{ enquiry.reference_id }}
                                </TableCell>
                                <TableCell>
                                    <span class="font-bold text-slate-800 dark:text-slate-100 text-sm block">{{ enquiry.inquirer_name }}</span>
                                    <div class="flex flex-col text-[11px] text-slate-500 mt-0.5 space-y-0.5">
                                        <span class="flex items-center gap-1 font-medium"><Phone class="w-3 h-3" /> {{ enquiry.phone }}</span>
                                        <span v-if="enquiry.email" class="flex items-center gap-1 font-medium"><Mail class="w-3 h-3" /> {{ enquiry.email }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="max-w-xs">
                                    <p class="text-sm text-slate-700 dark:text-slate-300 font-medium truncate">
                                        {{ enquiry.inquiry }}
                                    </p>
                                    <p v-if="enquiry.response" class="text-xs text-emerald-600 dark:text-emerald-400 truncate mt-0.5 flex items-center gap-1">
                                        <CheckCircle class="w-3 h-3 shrink-0" />
                                        <span>Response: {{ enquiry.response }}</span>
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                        <Calendar class="w-3.5 h-3.5 text-slate-400" />
                                        <span>{{ format(new Date(enquiry.created_at), 'MMM dd, yyyy') }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="[
                                        'px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider inline-flex items-center gap-1 border',
                                        enquiry.status === 'closed' 
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40' 
                                            : 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/40'
                                    ]">
                                        <CheckCircle v-if="enquiry.status === 'closed'" class="w-3 h-3 text-emerald-600" />
                                        <Clock v-else class="w-3 h-3 text-amber-600" />
                                        <span>{{ enquiry.status }}</span>
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right pr-6">
                                    <div class="flex justify-end items-center gap-1.5">
                                        <!-- View Single Enquiry -->
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            class="h-8 border-slate-200 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                                            @click="openViewModal(enquiry)"
                                            title="View details"
                                        >
                                            <Eye class="w-3.5 h-3.5 mr-1" /> View
                                        </Button>

                                        <!-- Respond / Resolve -->
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            class="h-8 border-amber-200 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30"
                                            @click="openResponse(enquiry)"
                                        >
                                            {{ enquiry.status === 'closed' ? 'Edit Note' : 'Respond' }}
                                        </Button>

                                        <!-- Delete -->
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30 h-8 w-8" 
                                            @click="deleteRecord(enquiry.id)" 
                                            title="Delete record"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="enquiries.data.length === 0">
                                <TableCell colspan="6" class="h-32 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <HelpCircle class="w-8 h-8 text-slate-300" />
                                        <p class="font-medium text-slate-500">No enquiries found</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination Component -->
            <Pagination :links="enquiries.links" />

        </div>

        <!-- View Single Enquiry Modal -->
        <Dialog v-model:open="isViewModalOpen">
            <DialogContent class="sm:max-w-[550px] border-amber-100 overflow-hidden">
                <DialogHeader class="border-b pb-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mt-6 pt-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-amber-100 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 rounded-xl">
                                <FileText class="w-5 h-5" />
                            </div>
                            <div>
                                <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Enquiry Record Details</DialogTitle>
                                <DialogDescription class="text-xs font-mono text-amber-600 dark:text-amber-400">
                                    Ref: {{ viewingEnquiry?.reference_id }}
                                </DialogDescription>
                            </div>
                        </div>
                        <Badge v-if="viewingEnquiry" :class="[
                            'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider',
                            viewingEnquiry.status === 'closed' 
                                ? 'bg-emerald-100 text-emerald-800' 
                                : 'bg-amber-100 text-amber-800'
                        ]">
                            {{ viewingEnquiry.status }}
                        </Badge>
                    </div>
                </DialogHeader>

                <div v-if="viewingEnquiry" class="space-y-4 py-4 text-sm">
                    <!-- Inquirer Info Grid -->
                    <div class="grid grid-cols-2 gap-3 p-3.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200/60 dark:border-slate-800">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Inquirer Name</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ viewingEnquiry.inquirer_name }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Date Logged</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-300">
                                {{ format(new Date(viewingEnquiry.created_at), 'MMMM dd, yyyy HH:mm') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Phone</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ viewingEnquiry.phone }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Email</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ viewingEnquiry.email || 'N/A' }}</span>
                        </div>
                    </div>

                    <!-- Enquiry Text Box -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Inquiry Content</Label>
                        <div class="p-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-800 dark:text-slate-200 font-medium whitespace-pre-wrap leading-relaxed">
                            {{ viewingEnquiry.inquiry }}
                        </div>
                    </div>

                    <!-- Response Note Box -->
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Official Response / Resolution</Label>
                        <div v-if="viewingEnquiry.response" class="p-3.5 bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40 rounded-xl text-slate-800 dark:text-slate-200 whitespace-pre-wrap leading-relaxed">
                            {{ viewingEnquiry.response }}
                        </div>
                        <div v-else class="p-3.5 bg-slate-50 dark:bg-slate-900/50 border border-dashed border-slate-300 dark:border-slate-700 rounded-xl text-slate-400 text-xs italic">
                            No official response recorded yet for this enquiry.
                        </div>
                    </div>
                </div>

                <DialogFooter class="border-t pt-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mb-6 pb-6 flex items-center justify-between">
                    <Button variant="outline" @click="isViewModalOpen = false">Close</Button>
                    <Button 
                        v-if="viewingEnquiry"
                        class="bg-amber-600 hover:bg-amber-700 text-white" 
                        @click="isViewModalOpen = false; openResponse(viewingEnquiry);"
                    >
                        {{ viewingEnquiry.status === 'closed' ? 'Edit Response' : 'Add Response / Resolve' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Response Dialog -->
        <Dialog v-model:open="isResponseOpen">
            <DialogContent class="sm:max-w-[500px] overflow-hidden border-indigo-50">
                <DialogHeader class="border-b pb-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mt-6 pt-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                            <CheckCircle class="w-5 h-5" />
                        </div>
                        <div>
                            <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Enquiry Response</DialogTitle>
                            <DialogDescription class="text-xs">Document responses given to visitor inquiries.</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>
                <div v-if="selectedEnquiry" class="space-y-4 py-4">
                    <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900 border text-xs space-y-1.5">
                        <p class="text-slate-500"><strong class="text-slate-800 dark:text-slate-200 uppercase">Inquirer:</strong> {{ selectedEnquiry.inquirer_name }}</p>
                        <p class="text-slate-500"><strong class="text-slate-800 dark:text-slate-200 uppercase">Inquiry:</strong> {{ selectedEnquiry.inquiry }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Response Details</Label>
                        <Textarea v-model="form.response" rows="5" placeholder="Enter details of response provided to inquirer..." required />
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</Label>
                        <select v-model="form.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <option value="open">Open</option>
                            <option value="closed">Closed (Resolved)</option>
                        </select>
                    </div>
                </div>
                <DialogFooter class="border-t pt-4 bg-slate-50/50 dark:bg-slate-900/50 -mx-6 px-6 -mb-6 pb-6">
                    <Button variant="ghost" @click="isResponseOpen = false">Close</Button>
                    <Button class="bg-indigo-600 hover:bg-indigo-700 text-white" @click="saveResponse" :disabled="form.processing">
                        <Check class="w-4 h-4 mr-2" /> Save Response
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
