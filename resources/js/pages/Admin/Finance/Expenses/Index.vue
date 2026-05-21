<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import Swal from 'sweetalert2';
import { format } from 'date-fns';
import { Plus, Search, Filter, CheckCircle, XCircle, Trash2, Edit } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import Pagination from '@/components/Pagination.vue'; // Assuming you have this or standard links

const props = defineProps<{
    expenses: any;
    categories: any[];
    filters: any;
}>();

const form = useForm({
    id: null,
    title: '',
    description: '',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    expense_category_id: '' as string,
});

const isModalOpen = ref(false);
const isEditing = ref(false);

const openCreate = () => {
    form.reset();
    form.id = null;
    isEditing.value = false;
    isModalOpen.value = true;
};

const openEdit = (expense: any) => {
    form.title = expense.title;
    form.description = expense.description;
    form.amount = expense.amount;
    form.date = expense.date.split('T')[0]; // Adjust if needed
    form.expense_category_id = expense.expense_category_id;
    form.id = expense.id;
    isEditing.value = true;
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && form.id) {
        form.put(route('admin.finance.expenses.update', form.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Expense updated', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
        });
    } else {
        form.post(route('admin.finance.expenses.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Expense added', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
        });
    }
};

const deleteExpense = (id: string) => {
    if (confirm('Are you sure you want to delete this expense?')) {
        router.delete(route('admin.finance.expenses.destroy', id), {
            onSuccess: () => Swal.fire({ icon: 'success', title: 'Deleted', text: 'Expense deleted', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }),
        });
    }
};

const approve = (id: string) => {
    router.post(route('admin.finance.expenses.approve', id), {}, {
        onSuccess: () => Swal.fire({ icon: 'success', title: 'Approved', text: 'Expense approved', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }),
    });
};

const reject = (id: string) => {
    Swal.fire({
        title: 'Reject Expense',
        input: 'text',
        inputLabel: 'Reason for rejection',
        showCancelButton: true,
        confirmButtonText: 'Reject',
        preConfirm: (reason) => {
            return router.post(route('admin.finance.expenses.reject', id), { rejection_reason: reason });
        }
    });
};

// Filters
const filterForm = useForm({
    status: props.filters.status || '',
    category_id: props.filters.category_id || '',
});

watch(() => filterForm.data(), () => {
    filterForm.get(route('admin.finance.expenses.index'), { preserveState: true, preserveScroll: true });
}, { deep: true });

const formatCurrency = (val: any) => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val);

</script>

<template>
    <Head title="Expenses" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Expenses</h2>
                    <p class="text-muted-foreground">Manage and track school expenditures.</p>
                </div>
                <Button @click="openCreate"><Plus class="mr-2 h-4 w-4" /> New Expense</Button>
            </div>

            <!-- Filters -->
            <div class="flex gap-4">
                <div class="w-[200px]">
                    <Select v-model="filterForm.status" @update:modelValue="val => { filterForm.status = val; filterForm.get(route('admin.finance.expenses.index'), { preserveState: true }) }">
                        <SelectTrigger>
                            <SelectValue placeholder="Filter by Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="approved">Approved</SelectItem>
                            <SelectItem value="rejected">Rejected</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="w-[200px]">
                     <Select v-model="filterForm.category_id" @update:modelValue="val => { filterForm.category_id = val; filterForm.get(route('admin.finance.expenses.index'), { preserveState: true }) }">
                        <SelectTrigger>
                            <SelectValue placeholder="Filter by Category" />
                        </SelectTrigger>
                        <SelectContent>
                             <SelectItem value="all">All Categories</SelectItem>
                            <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Title</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Requester</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="expense in expenses.data" :key="expense.id">
                                <TableCell>{{ format(new Date(expense.date), 'MMM d, yyyy') }}</TableCell>
                                <TableCell>
                                    <div class="font-medium">{{ expense.title }}</div>
                                    <div class="text-xs text-muted-foreground truncate max-w-[200px]">{{ expense.description }}</div>
                                </TableCell>
                                <TableCell><Badge variant="outline">{{ expense.category?.name || 'Uncategorized' }}</Badge></TableCell>
                                <TableCell class="font-bold">{{ formatCurrency(expense.amount) }}</TableCell>
                                <TableCell>
                                    <Badge :variant="expense.status === 'approved' ? 'default' : (expense.status === 'rejected' ? 'destructive' : 'secondary')">
                                        {{ expense.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ expense.user?.name }}</TableCell>
                                <TableCell class="text-right space-x-2">
                                    <div v-if="expense.status === 'pending'" class="flex justify-end gap-2">
                                        <Button size="icon" variant="ghost" @click="openEdit(expense)"><Edit class="h-4 w-4" /></Button>
                                        <Button size="icon" variant="ghost" class="text-emerald-600" @click="approve(expense.id)"><CheckCircle class="h-4 w-4" /></Button>
                                        <Button size="icon" variant="ghost" class="text-rose-600" @click="reject(expense.id)"><XCircle class="h-4 w-4" /></Button>
                                        <Button size="icon" variant="ghost" class="text-destructive" @click="deleteExpense(expense.id)"><Trash2 class="h-4 w-4" /></Button>
                                    </div>
                                    <div v-else class="text-xs text-muted-foreground">
                                        {{ expense.status === 'approved' ? 'Approved by ' + (expense.approver?.name || 'Admin') : 'Rejected' }}
                                    </div>
                                </TableCell>
                            </TableRow>
                             <TableRow v-if="expenses.data.length === 0">
                                <TableCell colspan="7" class="text-center py-8 text-muted-foreground">No expenses found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Modal -->
            <Dialog v-model:open="isModalOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{{ isEditing ? 'Edit Expense' : 'New Expense' }}</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label>Title</Label>
                            <Input v-model="form.title" placeholder="Expense title" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label>Amount</Label>
                                <Input type="number" v-model="form.amount" placeholder="0.00" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Date</Label>
                                <Input type="date" v-model="form.date" />
                            </div>
                        </div>
                         <div class="grid gap-2">
                            <Label>Category</Label>
                             <Select v-model="form.expense_category_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-2">
                            <Label>Description</Label>
                            <Textarea v-model="form.description" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isModalOpen = false">Cancel</Button>
                        <Button @click="submit" :disabled="form.processing">Save</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
