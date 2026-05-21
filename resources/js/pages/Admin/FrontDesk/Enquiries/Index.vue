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
import { MessageSquare, Plus, Info, CheckCircle, Trash2, Mail, Phone } from 'lucide-vue-next';
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
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Public Enquiries</h1>
                    <p class="text-slate-500 dark:text-slate-400">Track and respond to general inquiries from the public.</p>
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
                            <Button class="bg-amber-600 hover:bg-amber-700">
                                <Plus class="w-4 h-4 mr-2" /> Record New Enquiry
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-[500px]">
                            <DialogHeader>
                                <DialogTitle>Enquiry Registration</DialogTitle>
                            </DialogHeader>
                            <form @submit.prevent="submit" class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label for="name">Inquirer Name</Label>
                                    <Input id="name" v-model="form.inquirer_name" required />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="grid gap-2">
                                        <Label for="phone">Phone</Label>
                                        <Input id="phone" v-model="form.phone" required />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="email">Email (Optional)</Label>
                                        <Input id="email" v-model="form.email" type="email" />
                                    </div>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="inquiry">The Inquiry</Label>
                                    <Textarea id="inquiry" v-model="form.inquiry" rows="6" required />
                                </div>
                                <DialogFooter>
                                    <Button type="submit" class="bg-amber-600 hover:bg-amber-700" :disabled="form.processing">Record Enquiry</Button>
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
                                <TableHead>Inquirer</TableHead>
                                <TableHead>Inquiry</TableHead>
                                <TableHead>Date Reported</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="enquiry in enquiries.data" :key="enquiry.id">
                                <TableCell class="font-mono text-xs">{{ enquiry.reference_id }}</TableCell>
                                <TableCell>
                                    <div class="font-medium">{{ enquiry.inquirer_name }}</div>
                                    <div class="flex flex-col text-[10px] text-slate-500 mt-1">
                                        <span class="flex items-center gap-1"><Phone class="w-2.5 h-2.5" /> {{ enquiry.phone }}</span>
                                        <span v-if="enquiry.email" class="flex items-center gap-1"><Mail class="w-2.5 h-2.5" /> {{ enquiry.email }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="max-w-xs truncate">{{ enquiry.inquiry }}</TableCell>
                                <TableCell>{{ format(new Date(enquiry.created_at), 'MMM dd, yyyy') }}</TableCell>
                                <TableCell>
                                    <Badge :variant="enquiry.status === 'closed' ? 'secondary' : 'default'">
                                        {{ enquiry.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="sm" @click="openResponse(enquiry)">
                                            {{ enquiry.status === 'closed' ? 'View/Update' : 'Respond' }}
                                        </Button>
                                        <Button variant="ghost" size="icon" class="text-rose-600" @click="deleteRecord(enquiry.id)">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="enquiries.data.length === 0">
                                <TableCell colspan="6" class="h-24 text-center text-slate-400">No enquiries recorded yet.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="isResponseOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Enquiry Response</DialogTitle>
                </DialogHeader>
                <div v-if="selectedEnquiry" class="space-y-4 py-4">
                    <div class="p-4 rounded bg-slate-50 dark:bg-slate-900 text-sm">
                        <strong class="text-amber-600">Inquiry:</strong><br>
                        {{ selectedEnquiry.inquiry }}
                    </div>
                    <div class="grid gap-2">
                        <Label>Response Details</Label>
                        <Textarea v-model="form.response" rows="6" placeholder="Document the response provided..." />
                    </div>
                    <div class="grid gap-2">
                        <Label>Status</Label>
                        <select v-model="form.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <DialogFooter>
                    <Button @click="saveResponse" :disabled="form.processing">Save Changes</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
