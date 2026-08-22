<script setup lang="ts">
import { ref } from 'vue';
import { format } from 'date-fns';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Plus, Trash2, Edit, Settings, Copy, ArrowRight, History, Clock, User } from 'lucide-vue-next';

interface FeeType {
    id: number;
    name: string;
    slug: string;
    description: string;
    is_one_time: boolean;
    configurations_count?: number;
}

interface Item {
    id: string;
    name: string;
}

interface FeeConfiguration {
    id: number;
    amount: number;
    level: string | null;
    is_compulsory: boolean;
    fee_type: FeeType;
    session: Item;
    faculty: Item | null;
    department: Item | null;
    program: Item | null;
}

const props = defineProps<{
    feeTypes: FeeType[];
    expenseCategories: any[]; // New Prop
    sessions: any[]; // Now includes feeConfigurations
    faculties: any[];
    departments: any[];
    programs: any[];
    hostels: any[];
    hostelFees: any[];
    hostelFeeLogs: any[];
}>();

// Fee Type Form
const feeTypeForm = useForm({
    id: null as number | null,
    name: '',
    description: '',
    is_one_time: false,
});

const isFeeTypeModalOpen = ref(false);
const editingFeeType = ref(false);

const openCreateFeeType = () => {
    feeTypeForm.reset();
    feeTypeForm.id = null;
    editingFeeType.value = false;
    isFeeTypeModalOpen.value = true;
};

const openEditFeeType = (type: FeeType) => {
    feeTypeForm.name = type.name;
    feeTypeForm.description = type.description;
    feeTypeForm.is_one_time = !!type.is_one_time;
    feeTypeForm.id = type.id;
    editingFeeType.value = true;
    isFeeTypeModalOpen.value = true;
};

const submitFeeType = () => {
    if (editingFeeType.value && feeTypeForm.id) {
        feeTypeForm.put(route('admin.finance.fee_types.update', feeTypeForm.id), {
            onSuccess: () => {
                isFeeTypeModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Fee Type updated successfully', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save Fee Type', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    } else {
        feeTypeForm.post(route('admin.finance.fee_types.store'), {
            onSuccess: () => {
                isFeeTypeModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Fee Type created successfully', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save Fee Type', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    }
};

const deleteFeeType = (type: FeeType) => {
    if (confirm('Are you sure?')) {
        router.delete(route('admin.finance.fee_types.destroy', type.id), {
            onSuccess: () => Swal.fire({ icon: 'success', title: 'Deleted', text: 'Fee Type deleted', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }),
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    }
};

// Expense Category Logic
const expenseCategoryForm = useForm({
    id: null as string | null,
    name: '',
    description: '',
});

const isExpenseCategoryModalOpen = ref(false);
const editingExpenseCategory = ref(false);

const openCreateExpenseCategory = () => {
    expenseCategoryForm.reset();
    expenseCategoryForm.id = null;
    editingExpenseCategory.value = false;
    isExpenseCategoryModalOpen.value = true;
};

const openEditExpenseCategory = (category: any) => {
    expenseCategoryForm.name = category.name;
    expenseCategoryForm.description = category.description;
    expenseCategoryForm.id = category.id;
    editingExpenseCategory.value = true;
    isExpenseCategoryModalOpen.value = true;
};

const submitExpenseCategory = () => {
    if (editingExpenseCategory.value && expenseCategoryForm.id) {
        expenseCategoryForm.put(route('admin.finance.expense_categories.update', expenseCategoryForm.id), {
            onSuccess: () => {
                isExpenseCategoryModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Category updated successfully', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    } else {
        expenseCategoryForm.post(route('admin.finance.expense_categories.store'), {
            onSuccess: () => {
                isExpenseCategoryModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Category created successfully', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    }
};

const deleteExpenseCategory = (category: any) => {
     if (confirm('Are you sure?')) {
        router.delete(route('admin.finance.expense_categories.destroy', category.id), {
            onSuccess: () => Swal.fire({ icon: 'success', title: 'Deleted', text: 'Category deleted', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }),
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    }
};

// Session Cloning Logic
const cloneForm = useForm({
    source_session_id: '',
    target_session_id: '',
});

const isCloneModalOpen = ref(false);

const submitClone = () => {
    cloneForm.post(route('admin.finance.clone_fees'), {
        onSuccess: () => {
            isCloneModalOpen.value = false;
            cloneForm.reset();
            Swal.fire({ icon: 'success', title: 'Cloned', text: 'Fees cloned successfully', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        },
        onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to clone fees', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
    });
};

// --- Hostel Fee Configuration Logic ---
const isHostelFeeModalOpen = ref(false);
const editingHostelFee = ref<any>(null);

const isAllHostelsSelected = ref(true);
const selectedHostelIds = ref<string[]>([]);

const hostelFeeForm = useForm({
    session_id: '',
    hostel_id: 'all',
    amount: '',
});

const toggleAllHostels = () => {
    if (isAllHostelsSelected.value) {
        selectedHostelIds.value = [];
    }
};

const openCreateHostelFee = () => {
    editingHostelFee.value = null;
    hostelFeeForm.reset();
    hostelFeeForm.clearErrors();
    isAllHostelsSelected.value = true;
    selectedHostelIds.value = [];
    if (props.sessions && props.sessions.length > 0) {
        hostelFeeForm.session_id = props.sessions[0].id.toString();
    }
    isHostelFeeModalOpen.value = true;
};

const openEditHostelFee = (fee: any) => {
    editingHostelFee.value = fee;
    hostelFeeForm.session_id = fee.session_id.toString();
    if (fee.hostel_id) {
        isAllHostelsSelected.value = false;
        // Pre-select all hostels sharing the same session and amount
        const matchingFees = props.hostelFees.filter(
            (f: any) => f.session_id === fee.session_id && f.amount === fee.amount && f.hostel_id
        );
        selectedHostelIds.value = matchingFees.map((f: any) => f.hostel_id.toString());
    } else {
        isAllHostelsSelected.value = true;
        selectedHostelIds.value = [];
    }
    hostelFeeForm.amount = fee.amount.toString();
    hostelFeeForm.clearErrors();
    isHostelFeeModalOpen.value = true;
};

const submitHostelFee = () => {
    const payloadHostelIds = isAllHostelsSelected.value || selectedHostelIds.value.length === 0 ? ['all'] : selectedHostelIds.value;

    if (editingHostelFee.value) {
        router.put(route('admin.hostels.fees.update', editingHostelFee.value.id), {
            session_id: hostelFeeForm.session_id,
            hostel_id: payloadHostelIds[0] === 'all' ? '' : payloadHostelIds[0],
            hostel_ids: payloadHostelIds,
            amount: hostelFeeForm.amount,
        }, {
            onSuccess: () => {
                isHostelFeeModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Hostel fee updated successfully', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to update hostel fee', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    } else {
        router.post(route('admin.hostels.fees.store'), {
            session_id: hostelFeeForm.session_id,
            hostel_ids: payloadHostelIds,
            amount: hostelFeeForm.amount,
        }, {
            onSuccess: () => {
                isHostelFeeModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Hostel fee configuration saved successfully', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save hostel fee configuration', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    }
};

const deleteHostelFee = (id: string) => {
    if (confirm('Are you sure you want to remove this hostel fee configuration?')) {
        router.delete(route('admin.hostels.fees.destroy', id), {
            preserveScroll: true,
            onSuccess: () => Swal.fire({ icon: 'success', title: 'Removed', text: 'Hostel fee configuration removed', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }),
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete configuration', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    }
};

const formatCurrency = (amount: any) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(amount);
};
</script>

<template>
    <Head title="Finance Management" />
    <AdminLayout>
        <div class="p-6 space-y-6">
             <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-indigo-900">Finance Management</h2>
                    <p class="text-muted-foreground">Manage fee structures, rules, and global financial settings.</p>
                </div>
                <div class="flex gap-3">
                    <Button variant="outline" @click="isCloneModalOpen = true" class="border-indigo-200 text-indigo-700 hover:bg-indigo-50">
                        <Copy class="mr-2 h-4 w-4" /> Clone Session Fees
                    </Button>
                    <Button as-child variant="indigo" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                        <Link :href="route('admin.settings.index')"><Settings class="mr-2 h-4 w-4" /> Global Settings</Link>
                    </Button>
                </div>
            </div>

            <Tabs defaultValue="types" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="types">Fee Types</TabsTrigger>
                    <TabsTrigger value="configs">Fee Rules / Configurations</TabsTrigger>
                    <TabsTrigger value="categories">Expense Categories</TabsTrigger>
                    <TabsTrigger value="hostel-fees">Hostel Fees</TabsTrigger>
                </TabsList>

                <TabsContent value="types" class="space-y-4">
                    <!-- ... Fee Types Content (No Change) ... -->
                     <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Fee Types</CardTitle>
                                <CardDescription>Define the categories of fees (e.g., Tuition, ICT).</CardDescription>
                            </div>
                            <Button @click="openCreateFeeType"><Plus class="mr-2 h-4 w-4" /> Add Type</Button>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Slug</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Active Rules</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="type in feeTypes" :key="type.id">
                                        <TableCell class="font-medium">
                                            <div class="flex items-center gap-2">
                                                <span>{{ type.name }}</span>
                                                <Badge v-if="type.is_one_time" variant="outline" class="text-[10px] bg-amber-50 text-amber-700 border-amber-200">One-Time</Badge>
                                                <Badge v-else variant="outline" class="text-[10px] bg-slate-50 text-slate-700 border-slate-200">Recurring</Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell class="font-mono text-xs">{{ type.slug }}</TableCell>
                                        <TableCell>{{ type.description }}</TableCell>
                                        <TableCell><Badge variant="secondary">{{ type.configurations_count }} rules</Badge></TableCell>
                                        <TableCell class="text-right space-x-2">
                                            <Button variant="ghost" size="icon" @click="openEditFeeType(type)"><Edit class="h-4 w-4" /></Button>
                                            <Button variant="ghost" size="icon" class="text-destructive" @click="deleteFeeType(type)"><Trash2 class="h-4 w-4" /></Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                     <!-- Fee Type Modal (Reused) -->
                    <Dialog v-model:open="isFeeTypeModalOpen">
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{{ editingFeeType ? 'Edit Fee Type' : 'New Fee Type' }}</DialogTitle>
                            </DialogHeader>
                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label>Name</Label>
                                    <Input v-model="feeTypeForm.name" placeholder="e.g. Tuition Fee" />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Description</Label>
                                    <Input v-model="feeTypeForm.description" />
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Checkbox id="one-time-fee" v-model:checked="feeTypeForm.is_one_time" />
                                    <Label for="one-time-fee" class="cursor-pointer">Is this a One-Time Fee? (e.g. Matriculation, Acceptance, Gown Fee)</Label>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="isFeeTypeModalOpen = false">Cancel</Button>
                                <Button @click="submitFeeType" :disabled="feeTypeForm.processing">Save</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </TabsContent>

                <TabsContent value="categories" class="space-y-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Expense Categories</CardTitle>
                                <CardDescription>Define categories for tracking expenses (e.g., Utilities, Maintenance).</CardDescription>
                            </div>
                            <Button @click="openCreateExpenseCategory"><Plus class="mr-2 h-4 w-4" /> Add Category</Button>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Total Expenses</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="category in expenseCategories" :key="category.id">
                                        <TableCell class="font-medium">{{ category.name }}</TableCell>
                                        <TableCell>{{ category.description }}</TableCell>
                                        <TableCell><Badge variant="secondary">{{ category.expenses_count }}</Badge></TableCell>
                                        <TableCell class="text-right space-x-2">
                                            <Button variant="ghost" size="icon" @click="openEditExpenseCategory(category)"><Edit class="h-4 w-4" /></Button>
                                            <Button variant="ghost" size="icon" class="text-destructive" @click="deleteExpenseCategory(category)"><Trash2 class="h-4 w-4" /></Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                     <!-- Expense Category Modal -->
                    <Dialog v-model:open="isExpenseCategoryModalOpen">
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{{ editingExpenseCategory ? 'Edit Category' : 'New Category' }}</DialogTitle>
                            </DialogHeader>
                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label>Name</Label>
                                    <Input v-model="expenseCategoryForm.name" placeholder="e.g. Utilities" />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Description</Label>
                                    <Input v-model="expenseCategoryForm.description" />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="isExpenseCategoryModalOpen = false">Cancel</Button>
                                <Button @click="submitExpenseCategory" :disabled="expenseCategoryForm.processing">Save</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </TabsContent>

                <TabsContent value="configs" class="space-y-4">
                     <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Card v-for="session in sessions" :key="session.id" class="relative overflow-hidden">
                            <CardHeader>
                                <CardTitle>{{ session.name }}</CardTitle>
                                <CardDescription>{{ format(new Date(session.start_date), 'MMM d, yyyy') }} - {{ format(new Date(session.end_date), 'MMM d, yyyy') }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="flex items-center justify-between">
                                    <div class="space-y-1">
                                         <p class="text-2xl font-bold">{{ session.fee_configurations_count }}</p>
                                         <p class="text-xs text-muted-foreground">Fee Rules Configured</p>
                                    </div>
                                    <Link :href="route('admin.finance.session.fees', session.id)">
                                        <Button variant="outline" size="sm">
                                            <Settings class="mr-2 h-4 w-4" /> Manage Rules
                                        </Button>
                                    </Link>
                                </div>
                                <div v-if="session.is_current" class="absolute top-2 right-2">
                                    <Badge>Current</Badge>
                                </div>
                            </CardContent>
                        </Card>
                     </div>
                </TabsContent>

                <TabsContent value="hostel-fees" class="space-y-4">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <div class="lg:col-span-3 space-y-6">
                            <Card>
                                <CardHeader class="flex flex-row items-center justify-between pb-4">
                                    <div>
                                        <CardTitle>Hostel Fee Configurations</CardTitle>
                                        <CardDescription>Configure and manage accommodation rates for academic sessions.</CardDescription>
                                    </div>
                                    <Button @click="openCreateHostelFee" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">
                                        <Plus class="mr-2 h-4 w-4" /> Configure Hostel Fee
                                    </Button>
                                </CardHeader>
                                <CardContent class="p-0">
                                    <Table>
                                        <TableHeader class="bg-slate-50 dark:bg-slate-950/40 border-b">
                                            <TableRow>
                                                <TableHead class="font-bold py-3 pl-6">Academic Session</TableHead>
                                                <TableHead class="font-bold py-3">Hostel Name</TableHead>
                                                <TableHead class="font-bold py-3">Fee Amount</TableHead>
                                                <TableHead class="font-bold py-3 pr-6 text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="fee in hostelFees" :key="fee.id" class="hover:bg-slate-50/40 border-b">
                                                <TableCell class="font-semibold text-slate-800 dark:text-slate-200 pl-6">{{ fee.session?.name }}</TableCell>
                                                <TableCell>
                                                    <span v-if="fee.hostel" class="font-semibold text-slate-850 dark:text-slate-100">{{ fee.hostel.name }}</span>
                                                    <span v-else class="text-slate-450 italic text-xs font-semibold uppercase tracking-wider bg-slate-50 border px-2 py-0.5 rounded">Default (All Hostels)</span>
                                                </TableCell>
                                                <TableCell class="font-bold text-slate-900 dark:text-slate-100">{{ formatCurrency(fee.amount) }}</TableCell>
                                                <TableCell class="text-right pr-6">
                                                    <div class="flex justify-end items-center gap-2">
                                                        <Button variant="ghost" size="icon" @click="openEditHostelFee(fee)" class="h-8 w-8 text-indigo-650 hover:bg-indigo-50" title="Edit Rate">
                                                            <Edit class="h-4 w-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="icon" class="text-destructive hover:bg-destructive/10 h-8 w-8" @click="deleteHostelFee(fee.id)" title="Remove Rate">
                                                            <Trash2 class="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-if="hostelFees.length === 0">
                                                <TableCell colspan="4" class="h-28 text-center text-slate-450 italic">
                                                    No hostel fee configurations found.
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        </div>
                        
                        <!-- Audit Logs Sidebar -->
                        <div class="lg:col-span-1 space-y-4">
                            <Card class="border shadow-md">
                                <CardHeader class="py-4 border-b">
                                    <CardTitle class="text-sm font-bold flex items-center gap-2">
                                        <History class="w-4 h-4 text-indigo-500" />
                                        Hostel Fee Audit Logs
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="text-xs space-y-4 max-h-[450px] overflow-y-auto pr-2 mt-4">
                                    <div v-for="log in hostelFeeLogs" :key="log.id" class="border-b pb-3 space-y-1">
                                        <div class="flex justify-between text-slate-500 font-medium">
                                            <span class="flex items-center gap-1 font-bold text-slate-700 dark:text-slate-200">
                                                <User class="w-3.5 h-3.5" />
                                                {{ log.causer?.name || 'System' }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">
                                                {{ format(new Date(log.created_at), 'MMM dd, HH:mm') }}
                                            </span>
                                        </div>
                                        <p class="text-slate-600 dark:text-slate-350 leading-relaxed font-medium">
                                            {{ log.description }}
                                        </p>
                                    </div>
                                    <div v-if="hostelFeeLogs.length === 0" class="text-center py-6 text-slate-400 italic">
                                        No audit logs recorded yet.
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </TabsContent>

                <!-- Configure Hostel Fees Modal -->
                <Dialog :open="isHostelFeeModalOpen" @update:open="isHostelFeeModalOpen = $event">
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>{{ editingHostelFee ? 'Edit Hostel Fee Rate' : 'Configure Hostel Fee Rate' }}</DialogTitle>
                            <DialogDescription>
                                Set default accommodation fees per session or custom fees per building.
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submitHostelFee">
                            <div class="grid gap-4 py-4">
                                <div class="space-y-2">
                                    <Label for="fee_session">Academic Session</Label>
                                    <Select v-model="hostelFeeForm.session_id">
                                        <SelectTrigger id="fee_session">
                                            <SelectValue placeholder="Select Session" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="session in sessions" :key="session.id" :value="session.id.toString()">
                                                {{ session.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="hostelFeeForm.errors.session_id" class="text-sm text-destructive">{{ hostelFeeForm.errors.session_id }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label>Target Hostel(s)</Label>
                                    <div class="border rounded-2xl p-3.5 bg-card space-y-3 max-h-56 overflow-y-auto">
                                        <label class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-muted/50 cursor-pointer font-bold text-xs border bg-slate-50 dark:bg-slate-900 text-foreground">
                                            <input 
                                                type="checkbox" 
                                                v-model="isAllHostelsSelected" 
                                                @change="toggleAllHostels"
                                                class="h-4 w-4 rounded border-primary text-primary focus:ring-primary cursor-pointer shrink-0"
                                            />
                                            <span>-- Default for All Hostels (Global Rate) --</span>
                                        </label>
                                        
                                        <div v-if="!isAllHostelsSelected" class="pl-1 space-y-2 pt-2 border-t">
                                            <p class="text-[10px] font-black text-muted-foreground uppercase tracking-wider">Select specific hostels to share this fee:</p>
                                            <div class="grid gap-1.5 sm:grid-cols-2">
                                                <label 
                                                    v-for="hostel in hostels" 
                                                    :key="hostel.id" 
                                                    class="flex items-center gap-2.5 p-2 rounded-xl border hover:bg-muted/50 cursor-pointer text-xs font-semibold bg-background"
                                                >
                                                    <input 
                                                        type="checkbox" 
                                                        :value="hostel.id.toString()" 
                                                        v-model="selectedHostelIds" 
                                                        class="h-4 w-4 rounded border-primary text-primary focus:ring-primary cursor-pointer shrink-0"
                                                    />
                                                    <span class="truncate">{{ hostel.name }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-muted-foreground">Check "Default for All Hostels" for a global rate, or select specific hostels to share this rate.</p>
                                    <p v-if="hostelFeeForm.errors.hostel_id" class="text-sm text-destructive">{{ hostelFeeForm.errors.hostel_id }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="fee_amount">Fee Amount (₦)</Label>
                                    <Input id="fee_amount" type="number" step="0.01" min="0" v-model="hostelFeeForm.amount" placeholder="e.g. 150000" required />
                                    <p v-if="hostelFeeForm.errors.amount" class="text-sm text-destructive">{{ hostelFeeForm.errors.amount }}</p>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" @click="isHostelFeeModalOpen = false">Cancel</Button>
                                <Button type="submit" :disabled="hostelFeeForm.processing">Save Configuration</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </Tabs>

            <!-- Clone Fees Modal -->
            <Dialog v-model:open="isCloneModalOpen">
                <DialogContent class="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <Copy class="w-5 h-5 text-indigo-600" />
                            Clone Fee Configurations
                        </DialogTitle>
                        <DialogDescription>
                            Duplicate all fee rules from one academic session to another. Existing rules in the target session will not be overwritten.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-6 py-4">
                        <div class="grid gap-2">
                            <Label for="source">Source Session (Copy From)</Label>
                            <Select v-model="cloneForm.source_session_id">
                                <SelectTrigger id="source">
                                    <SelectValue placeholder="Select session" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="sess in sessions" :key="sess.id" :value="sess.id">
                                        {{ sess.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex justify-center">
                            <ArrowRight class="w-6 h-6 text-muted-foreground animate-pulse" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="target">Target Session (Copy To)</Label>
                            <Select v-model="cloneForm.target_session_id">
                                <SelectTrigger id="target">
                                    <SelectValue placeholder="Select session" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="sess in sessions" :key="sess.id" :value="sess.id">
                                        {{ sess.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isCloneModalOpen = false">Cancel</Button>
                        <Button @click="submitClone" :disabled="cloneForm.processing || !cloneForm.source_session_id || !cloneForm.target_session_id" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                            {{ cloneForm.processing ? 'Cloning...' : 'Begin Cloning' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>





        </div>
    </AdminLayout>
</template>
