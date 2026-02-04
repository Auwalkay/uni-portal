<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { AlertCircle, Plus, Info, CheckCircle, Trash2 } from 'lucide-vue-next';
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
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Complaints & Feedback</h1>
                    <p class="text-slate-500 dark:text-slate-400">Manage and resolve institutional complaints.</p>
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
                            <Button class="bg-rose-600 hover:bg-rose-700">
                                <Plus class="w-4 h-4 mr-2" /> Record New Complaint
                            </Button>
                        </DialogTrigger>
                    <DialogContent class="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle>Complaint Registration</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submit" class="grid gap-4 py-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label for="name">Complainant Name</Label>
                                    <Input id="name" v-model="form.complainant_name" required />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="phone">Phone</Label>
                                    <Input id="phone" v-model="form.phone" required />
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="subject">Subject</Label>
                                <Input id="subject" v-model="form.subject" required />
                            </div>
                            <div class="grid gap-2">
                                <Label for="description">Description</Label>
                                <Textarea id="description" v-model="form.description" rows="4" required />
                            </div>
                            <DialogFooter>
                                <Button type="submit" class="bg-rose-600 hover:bg-rose-700" :disabled="form.processing">Submit Complaint</Button>
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
                                <TableHead>Complainant</TableHead>
                                <TableHead>Subject</TableHead>
                                <TableHead>Date Reported</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="complaint in complaints.data" :key="complaint.id">
                                <TableCell class="font-mono text-xs">{{ complaint.reference_id }}</TableCell>
                                <TableCell>
                                    <div class="font-medium">{{ complaint.complainant_name }}</div>
                                    <div class="text-xs text-slate-500">{{ complaint.phone }}</div>
                                </TableCell>
                                <TableCell>{{ complaint.subject }}</TableCell>
                                <TableCell>{{ format(new Date(complaint.created_at), 'MMM dd, yyyy HH:mm') }}</TableCell>
                                <TableCell>
                                    <Badge :variant="complaint.status === 'resolved' ? 'secondary' : 'destructive'">
                                        {{ complaint.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="sm" @click="openResolution(complaint)">
                                            Resolve / Edit
                                        </Button>
                                        <Button variant="ghost" size="icon" class="text-rose-600" @click="deleteRecord(complaint.id)">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="complaints.data.length === 0">
                                <TableCell colspan="6" class="h-24 text-center text-slate-400">No complaints recorded yet.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Resolution Dialog -->
        <Dialog v-model:open="isResolutionOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Complaint Resolution</DialogTitle>
                </DialogHeader>
                <div v-if="selectedComplaint" class="space-y-4 py-4">
                    <div class="p-4 rounded bg-slate-50 dark:bg-slate-900 text-sm">
                        <strong>Subject:</strong> {{ selectedComplaint.subject }}<br>
                        <strong>Description:</strong> {{ selectedComplaint.description }}
                    </div>
                    <div class="grid gap-2">
                        <Label>Status</Label>
                        <select v-model="form.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Resolution Notes</Label>
                        <Textarea v-model="form.resolution_notes" rows="4" placeholder="Enter details of how this was resolved..." />
                    </div>
                </div>
                <DialogFooter>
                    <Button @click="saveResolution" :disabled="form.processing">Save Changes</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
