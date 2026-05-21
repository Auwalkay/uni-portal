<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { 
    Search, 
    Edit, 
    Download, 
    Upload, 
    Users, 
    Banknote, 
    TrendingUp, 
    TrendingDown,
    Plus,
    X,
    FileSpreadsheet,
    Loader2
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import Swal from 'sweetalert2';
import Pagination from '@/components/Pagination.vue'; 

const props = defineProps<{
    staff: any;
    departments: any[];
    filters: any;
}>();

const formatCurrency = (val: any) => new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val || 0);

const search = ref(props.filters.search || '');
const department_id = ref(props.filters.department_id || '');

// Stats calculation (from current page data)
const stats = computed(() => {
    const data = props.staff.data || [];
    return {
        totalBasic: data.reduce((acc: number, s: any) => acc + Number(s.basic_salary || 0), 0),
        totalAllowances: data.reduce((acc: number, s: any) => acc + Number(s.allowances || 0), 0),
        totalDeductions: data.reduce((acc: number, s: any) => acc + Number(s.deductions || 0), 0),
        avgNet: data.length > 0 
            ? data.reduce((acc: number, s: any) => acc + (Number(s.basic_salary) + Number(s.allowances) + Number(s.bonuses) - Number(s.deductions)), 0) / data.length 
            : 0
    };
});

// Debounce search
let timeout: any;
watch([search, department_id], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.finance.salary.index'), { search: search.value, department_id: department_id.value }, { preserveState: true, replace: true });
    }, 300);
});

// Edit Modal
const isEditModalOpen = ref(false);
const editForm = useForm({
    id: null as string | null,
    basic_salary: 0,
    allowances: 0,
    deductions: 0,
    bonuses: 0,
    bank_name: '',
    account_number: '',
    account_name: '',
});

const openEdit = (member: any) => {
    editForm.id = member.id;
    editForm.basic_salary = member.basic_salary || 0;
    editForm.allowances = member.allowances || 0;
    editForm.deductions = member.deductions || 0;
    editForm.bonuses = member.bonuses || 0;
    editForm.bank_name = member.bank_name || '';
    editForm.account_number = member.account_number || '';
    editForm.account_name = member.account_name || member.user?.name || '';
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    if (!editForm.id) return;
    editForm.put(route('admin.finance.salary.update', editForm.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            Swal.fire({ icon: 'success', title: 'Updated', text: 'Salary details updated successfully', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        }
    });
};

// Export
const isExporting = ref(false);
const handleExport = () => {
    window.location.href = route('admin.finance.salary.export');
};

// Import Modal
const isImportModalOpen = ref(false);
const importForm = useForm({
    file: null as File | null,
});

const handleImport = () => {
    if (!importForm.file) return;
    importForm.post(route('admin.finance.salary.import'), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            importForm.reset();
            Swal.fire({ icon: 'success', title: 'Imported', text: 'Staff salaries have been updated via bulk upload.' });
        }
    });
};
</script>

<template>
    <Head title="Salary Management" />
    <AdminLayout>
        <div class="p-6 space-y-8 max-w-[1600px] mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-primary to-primary/60 bg-clip-text text-transparent">Salary Management</h1>
                    <p class="text-muted-foreground mt-1 text-lg">Configure staff compensation, bonuses, and deductions.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="outline" @click="handleExport" class="hover:bg-primary/5">
                        <Download class="mr-2 h-4 w-4" /> Export Excel
                    </Button>
                    <Button @click="isImportModalOpen = true" class="bg-primary hover:bg-primary/90">
                        <Upload class="mr-2 h-4 w-4" /> Bulk Upload
                    </Button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card class="bg-gradient-to-br from-card to-muted/30 border-none shadow-sm overflow-hidden relative">
                    <div class="absolute right-[-10px] top-[-10px] opacity-10">
                        <Banknote class="h-24 w-24" />
                    </div>
                    <CardHeader class="pb-2">
                        <CardDescription>Total Basic Salary</CardDescription>
                        <CardTitle class="text-2xl font-bold">{{ formatCurrency(stats.totalBasic) }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <span class="text-xs text-muted-foreground">Current filtering context</span>
                    </CardContent>
                </Card>

                <Card class="bg-gradient-to-br from-card to-muted/30 border-none shadow-sm overflow-hidden relative">
                    <div class="absolute right-[-10px] top-[-10px] opacity-10">
                        <Plus class="h-24 w-24 text-green-500" />
                    </div>
                    <CardHeader class="pb-2">
                        <CardDescription>Extra (Allowances/Bonuses)</CardDescription>
                        <CardTitle class="text-2xl font-bold">{{ formatCurrency(stats.totalAllowances) }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center text-xs text-green-600">
                             <TrendingUp class="h-3 w-3 mr-1" /> Added to payroll
                        </div>
                    </CardContent>
                </Card>

                <Card class="bg-gradient-to-br from-card to-muted/30 border-none shadow-sm overflow-hidden relative">
                    <div class="absolute right-[-10px] top-[-10px] opacity-10">
                        <X class="h-24 w-24 text-red-500" />
                    </div>
                    <CardHeader class="pb-2">
                        <CardDescription>Total Deductions</CardDescription>
                        <CardTitle class="text-2xl font-bold">{{ formatCurrency(stats.totalDeductions) }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center text-xs text-red-600">
                             <TrendingDown class="h-3 w-3 mr-1" /> Subtracted from net
                        </div>
                    </CardContent>
                </Card>

                <Card class="bg-gradient-to-br from-primary/10 to-primary/5 border-none shadow-sm overflow-hidden relative">
                    <div class="absolute right-[-10px] top-[-10px] opacity-20">
                        <TrendingUp class="h-24 w-24 text-primary" />
                    </div>
                    <CardHeader class="pb-2">
                        <CardDescription class="text-primary/70">Avg. Net Pay</CardDescription>
                        <CardTitle class="text-2xl font-bold text-primary">{{ formatCurrency(stats.avgNet) }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <span class="text-xs text-primary/60">Estimated per staff</span>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters & Table Section -->
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-4 bg-card p-4 rounded-xl border shadow-sm">
                    <div class="relative flex-1 min-w-[300px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input 
                            placeholder="Search by name, email or ID..." 
                            v-model="search" 
                            class="pl-10 h-11 border-muted bg-muted/20 focus:bg-background transition-all" 
                        />
                    </div>
                    <div class="w-[200px]">
                        <Select v-model="department_id">
                            <SelectTrigger class="h-11 border-muted bg-muted/20">
                                <SelectValue placeholder="All Departments" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Departments</SelectItem>
                                <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/30">
                                <TableHead class="font-bold py-4">Staff Details</TableHead>
                                <TableHead class="font-bold">Department</TableHead>
                                <TableHead class="font-bold text-center">Basic</TableHead>
                                <TableHead class="font-bold text-center">Extras/Ded.</TableHead>
                                <TableHead class="font-bold text-center">Net Pay</TableHead>
                                <TableHead class="font-bold">Bank Info</TableHead>
                                <TableHead class="text-right font-bold pr-6">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="member in staff.data" :key="member.id" class="hover:bg-muted/50 transition-colors group">
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                                            {{ member.user?.first_name?.[0] }}{{ member.user?.last_name?.[0] }}
                                        </div>
                                        <div>
                                            <div class="font-semibold">{{ member.user?.name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ member.user?.email }}</div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline" class="bg-muted/20 font-normal border-muted-foreground/30">
                                        {{ member.department?.name || 'N/A' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center font-medium">{{ formatCurrency(member.basic_salary) }}</TableCell>
                                <TableCell class="text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-[10px] text-green-600 font-bold" v-if="member.allowances > 0 || member.bonuses > 0">
                                            +{{ formatCurrency(Number(member.allowances) + Number(member.bonuses)) }}
                                        </span>
                                        <span class="text-[10px] text-red-600 font-bold" v-if="member.deductions > 0">
                                            -{{ formatCurrency(member.deductions) }}
                                        </span>
                                        <span class="text-[10px] text-muted-foreground" v-if="!member.allowances && !member.bonuses && !member.deductions">None</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <span class="font-bold text-primary">
                                        {{ formatCurrency(Number(member.basic_salary) + Number(member.allowances) + Number(member.bonuses) - Number(member.deductions)) }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div v-if="member.bank_name" class="flex flex-col">
                                        <span class="text-sm font-medium">{{ member.bank_name }}</span>
                                        <span class="text-xs text-muted-foreground tabular-nums tracking-widest">{{ member.account_number }}</span>
                                    </div>
                                    <span v-else class="text-muted-foreground italic text-xs">Not configured</span>
                                </TableCell>
                                <TableCell class="text-right pr-6">
                                    <div class="flex justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Button variant="outline" size="sm" @click="openEdit(member)" class="h-8 shadow-sm">
                                            <Edit class="mr-2 h-3.5 w-3.5" /> Details
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="staff.data.length === 0">
                                <TableCell colspan="7" class="h-32 text-center text-muted-foreground">
                                    No staff records found matching your criteria.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                
                <div class="pt-4">
                    <Pagination :links="staff.links" />
                </div>
            </div>

            <!-- Edit Modal -->
            <Dialog v-model:open="isEditModalOpen">
                <DialogContent class="sm:max-w-[600px] gap-0 p-0 overflow-hidden border-none shadow-2xl">
                    <div class="bg-primary p-6 text-primary-foreground relative">
                        <DialogHeader>
                            <DialogTitle class="text-2xl font-bold">Configure Compensation</DialogTitle>
                            <DialogDescription class="text-primary-foreground/80">
                                Manage salary components and bank payout details for this staff member.
                            </DialogDescription>
                        </DialogHeader>
                    </div>
                    
                    <div class="p-6 grid grid-cols-2 gap-6 bg-background">
                        <div class="space-y-4 col-span-2">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-muted-foreground">Income Components</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label class="text-xs font-semibold">Basic Salary</Label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">₦</span>
                                        <Input type="number" v-model="editForm.basic_salary" class="pl-8" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-semibold">Allowances</Label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">₦</span>
                                        <Input type="number" v-model="editForm.allowances" class="pl-8" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-semibold">Bonuses</Label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">₦</span>
                                        <Input type="number" v-model="editForm.bonuses" class="pl-8" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-semibold text-red-500 underline decoration-dotted">Deductions (Tax, etc.)</Label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm">₦</span>
                                        <Input type="number" v-model="editForm.deductions" class="pl-8 border-red-200 focus:ring-red-500" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 col-span-2 pt-4 border-t">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-muted-foreground">Payout Destination</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label class="text-xs font-semibold">Bank Name</Label>
                                    <Input v-model="editForm.bank_name" placeholder="e.g. Zenith Bank" />
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-semibold">Account Number</Label>
                                    <Input v-model="editForm.account_number" maxlength="10" placeholder="0123456789" />
                                </div>
                                <div class="space-y-2 col-span-2">
                                    <Label class="text-xs font-semibold">Account Holder Name</Label>
                                    <Input v-model="editForm.account_name" />
                                </div>
                            </div>
                        </div>

                        <!-- Final Calculation Preview -->
                        <div class="col-span-2 p-4 bg-muted/30 rounded-lg flex items-center justify-between mt-2">
                            <span class="text-sm font-medium">Estimated Net Payout:</span>
                            <span class="text-xl font-black text-primary">
                                {{ formatCurrency(Number(editForm.basic_salary) + Number(editForm.allowances) + Number(editForm.bonuses) - Number(editForm.deductions)) }}
                            </span>
                        </div>
                    </div>

                    <DialogFooter class="p-6 bg-muted/20 border-t flex flex-row justify-end space-x-2">
                        <Button variant="ghost" @click="isEditModalOpen = false">Cancel</Button>
                        <Button @click="submitEdit" :disabled="editForm.processing" class="px-8 font-bold">
                            <Loader2 v-if="editForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Update Record
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Bulk Import Modal -->
            <Dialog v-model:open="isImportModalOpen">
                <DialogContent class="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>Bulk Salary Update</DialogTitle>
                        <DialogDescription>
                            Download the template, fill in the details and upload it back to update staff salaries in bulk.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="py-6 space-y-6">
                        <div class="p-4 bg-primary/5 rounded-lg border border-primary/20 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-primary/10 rounded-full">
                                    <FileSpreadsheet class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-primary">Need a template?</div>
                                    <div class="text-xs text-muted-foreground">Export current list to use as a base.</div>
                                </div>
                            </div>
                            <Button variant="link" size="sm" @click="handleExport" class="p-0 h-auto font-bold text-primary">
                                Export Current List
                            </Button>
                        </div>

                        <div class="space-y-2">
                            <Label>Choose Excel/CSV File</Label>
                            <Input 
                                type="file" 
                                @input="importForm.file = $event.target.files[0]"
                                accept=".xlsx, .xls, .csv"
                                class="cursor-pointer file:cursor-pointer file:bg-primary file:text-primary-foreground file:border-none file:px-4 file:py-1 file:rounded-md file:mr-4 file:hover:bg-primary/90"
                            />
                        </div>

                        <ul class="text-xs space-y-2 text-muted-foreground list-disc pl-4">
                            <li>Supported formats: .xlsx, .xls, .csv</li>
                            <li>Use <b>ID</b> or <b>Email</b> columns for matching.</li>
                            <li>Only the values provided in the file will be updated.</li>
                        </ul>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isImportModalOpen = false">Cancel</Button>
                        <Button @click="handleImport" :disabled="importForm.processing || !importForm.file">
                            <Loader2 v-if="importForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Start Bulk Update
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
