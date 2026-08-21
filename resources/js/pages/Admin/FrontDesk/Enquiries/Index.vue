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
import { MessageSquare, Plus, Info, CheckCircle, Trash2, Mail, Phone, Search, Clock, Calendar, Check, Send, User } from 'lucide-vue-next';
import { format } from 'date-fns';
import { ref } from 'vue';

interface Props {
    enquiries: {
        data: any[];
        links: any[];
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();
const isAddingFormOpen = ref(false);
const selectedEnquiry = ref<any>(null);
const isResponseOpen = ref(false);
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

const submit = () => {
    form.post(route('admin.front-desk.enquiries.store'), {
        onSuccess: () => {
            isAddingFormOpen.value = false;
            form.reset();
        },
    });
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
    if (confirm('Are you sure?')) {
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
    <Head title="Enquiry Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-3">
                        <MessageSquare class="w-8 h-8 text-amber-600 dark:text-amber-450" />
                        <span>Public Enquiries</span>
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Track and respond to general enquiries from the public and prospective students.</p>
                </div>
                
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <Input 
                            v-model="search" 
                            placeholder="Search by ID, name..." 
                            @input="handleSearch"
                            class="pl-9 h-10 shadow-sm focus-visible:ring-amber-500"
                        />
                        <Search class="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                    </div>

                    <!-- Dialog to Record Enquiry -->
                    <Dialog v-model:open="isAddingFormOpen">
                        <DialogTrigger as-child>
                            <Button class="bg-amber-600 hover:bg-amber-700 text-white shadow-md transition-all duration-200 h-10 font-semibold">
                                <Plus class="w-4 h-4 mr-2" /> Log Enquiry
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[500px] overflow-hidden border-amber-100">
                            <DialogHeader class="border-b pb-4 bg-slate-50/50 -mx-6 px-6 -mt-6 pt-6">
                                <div class="flex items-center gap-3">
                                    <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                                        <MessageSquare class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <DialogTitle class="text-lg font-bold text-slate-900 dark:text-slate-100">Log New Enquiry</DialogTitle>
                                        <DialogDescription class="text-xs">Record contact details and enquiry contents.</DialogDescription>
                                    </div>
                                </div>
                            </DialogHeader>
                            <form @submit.prevent="submit" class="space-y-4 py-4">
                                <div class="space-y-1.5">
                                    <Label for="name" class="text-xs font-bold uppercase tracking-wider text-slate-500">Inquirer Name</Label>
                                    <div class="relative">
                                        <Input id="name" v-model="form.inquirer_name" placeholder="John Doe" class="pl-9" required />
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
                                    <Textarea id="inquiry" v-model="form.inquiry" rows="5" placeholder="Document the inquiry or question here..." required />
                                </div>

                                <div class="p-3 bg-slate-50 dark:bg-slate-900 border rounded-lg flex items-start gap-2.5 text-xs text-slate-500">
                                    <Info class="w-4.5 h-4.5 text-indigo-500 shrink-0 mt-0.5" />
                                    <p>Enquiries are set to "Open" status. You can record response notes and mark them as resolved / closed later.</p>
                                </div>

                                <DialogFooter class="border-t pt-4 bg-slate-50/50 -mx-6 px-6 -mb-6 pb-6">
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

            <!-- Enquiries Table -->
            <Card class="border shadow-md rounded-xl overflow-hidden bg-white dark:bg-slate-900">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-slate-50 dark:bg-slate-950/40">
                            <TableRow>
                                <TableHead class="font-bold py-4 pl-6 text-xs uppercase tracking-wider">Ticket ID</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Inquirer</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Inquiry</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Date Reported</TableHead>
                                <TableHead class="font-bold py-4 text-xs uppercase tracking-wider">Status</TableHead>
                                <TableHead class="font-bold py-4 pr-6 text-right text-xs uppercase tracking-wider">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="enquiry in enquiries.data" :key="enquiry.id" class="hover:bg-slate-50/40 dark:hover:bg-slate-950/20 transition-all border-b">
                                <TableCell class="font-mono text-xs font-bold text-slate-600 dark:text-slate-400 pl-6">{{ enquiry.reference_id }}</TableCell>
                                <TableCell>
                                    <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm block">{{ enquiry.inquirer_name }}</span>
                                    <div class="flex flex-col text-[11px] text-slate-500 mt-1 space-y-0.5">
                                        <span class="flex items-center gap-1 font-medium"><Phone class="w-2.5 h-2.5" /> {{ enquiry.phone }}</span>
                                        <span v-if="enquiry.email" class="flex items-center gap-1 font-medium"><Mail class="w-2.5 h-2.5" /> {{ enquiry.email }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-sm text-slate-700 dark:text-slate-300 max-w-xs truncate font-medium">{{ enquiry.inquiry }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                        <Calendar class="w-3.5 h-3.5 text-slate-400" />
                                        <span>{{ format(new Date(enquiry.created_at), 'MMM dd, yyyy') }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="[
                                        'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1 w-fit border',
                                        enquiry.status === 'closed' 
                                            ? 'bg-slate-100 text-slate-750 border-slate-200' 
                                            : 'bg-amber-50 text-amber-850 border-amber-100'
                                    ]">
                                        <CheckCircle v-if="enquiry.status === 'closed'" class="w-3 h-3 text-slate-500" />
                                        <Clock v-else class="w-3 h-3 text-amber-600" />
                                        <span>{{ enquiry.status }}</span>
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right pr-6">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="sm" class="h-8 border-slate-200 text-slate-700 hover:bg-slate-50" @click="openResponse(enquiry)">
                                            {{ enquiry.status === 'closed' ? 'View details' : 'Respond / Resolve' }}
                                        </Button>
                                        <Button variant="ghost" size="icon" class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 h-8 w-8" @click="deleteRecord(enquiry.id)" title="Delete record">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="enquiries.data.length === 0">
                                <TableCell colspan="6" class="h-28 text-center text-slate-400 font-medium">No enquiries recorded yet.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Response Dialog -->
        <Dialog v-model:open="isResponseOpen">
            <DialogContent class="sm:max-w-[500px] overflow-hidden border-indigo-50">
                <DialogHeader class="border-b pb-4 bg-slate-50/50 -mx-6 px-6 -mt-6 pt-6">
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
                    <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-100 text-xs space-y-1.5">
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
                <DialogFooter class="border-t pt-4 bg-slate-50/50 -mx-6 px-6 -mb-6 pb-6">
                    <Button variant="ghost" @click="isResponseOpen = false">Close</Button>
                    <Button class="bg-indigo-650 bg-indigo-600 hover:bg-indigo-750" @click="saveResponse" :disabled="form.processing">
                        <Check class="w-4 h-4 mr-2" /> Save Response
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
