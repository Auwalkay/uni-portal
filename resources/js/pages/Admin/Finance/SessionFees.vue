<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Plus, Edit, Trash2, ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';
import Swal from 'sweetalert2';
import { format } from 'date-fns';

interface FeeType { id: number; name: string; slug: string; description: string; configurations_count?: number; }
interface Faculty { id: number; name: string; code: string; }
interface Department { id: number; name: string; code: string; }
interface Program { id: number; name: string; code: string; }
interface FeeConfiguration {
    id: number;
    fee_type_id: number;
    fee_type: FeeType;
    amount: number;
    faculty_id?: number | null;
    faculty?: Faculty;
    department_id?: number | null;
    department?: Department;
    program_id?: number | null;
    program?: Program;
    level?: string | null;
    is_compulsory: boolean;
}

const props = defineProps<{
    session: any;
    feeTypes: FeeType[];
    faculties: Faculty[];
    departments: Department[];
    programs: Program[];
}>();

const isConfigModalOpen = ref(false);
const editingConfig = ref(false);

const configForm = useForm({
    id: null as number | null,
    session_id: props.session.id,
    fee_type_id: null as number | null,
    amount: 0,
    faculty_id: null as string | null,
    department_id: null as string | null,
    program_id: null as string | null,
    level: '',
    is_compulsory: true,
});

const openCreateConfig = () => {
    configForm.reset();
    configForm.session_id = props.session.id;
    editingConfig.value = false;
    isConfigModalOpen.value = true;
};

const openEditConfig = (config: FeeConfiguration) => {
    configForm.reset();
    configForm.id = config.id;
    configForm.session_id = props.session.id; // Ensure session ID is kept
    configForm.fee_type_id = config.fee_type_id;
    configForm.amount = config.amount;
    configForm.faculty_id = config.faculty_id ? String(config.faculty_id) : null;
    configForm.department_id = config.department_id ? String(config.department_id) : null;
    configForm.program_id = config.program_id ? String(config.program_id) : null;
    configForm.level = config.level || '';
    configForm.is_compulsory = !!config.is_compulsory;
    editingConfig.value = true;
    isConfigModalOpen.value = true;
};

const submitConfig = () => {
    const form = configForm;
    if (form.faculty_id === 'all') form.faculty_id = null;
    if (form.department_id === 'all') form.department_id = null;
    if (form.program_id === 'all') form.program_id = null;

    if (editingConfig.value && configForm.id) {
        configForm.put(route('admin.finance.configurations.update', configForm.id), {
            onSuccess: () => {
                isConfigModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Fee Rule updated', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save rule', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    } else {
        configForm.post(route('admin.finance.configurations.store'), {
            onSuccess: () => {
                isConfigModalOpen.value = false;
                Swal.fire({ icon: 'success', title: 'Success', text: 'Fee Rule created', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
            },
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save rule', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    }
};

const deleteConfig = (config: FeeConfiguration) => {
    if (confirm('Delete this rule?')) {
        router.delete(route('admin.finance.configurations.destroy', config.id), {
            onSuccess: () => Swal.fire({ icon: 'success', title: 'Deleted', text: 'Rule deleted', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }),
            onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
        });
    }
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val);
};
</script>

<template>
    <Head :title="`Fees: ${session.name}`" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <Link :href="route('admin.finance.index')" class="text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                        <h2 class="text-3xl font-bold tracking-tight">{{session.name}} Session Fees</h2>
                    </div>
                    <p class="text-muted-foreground">
                        Manage fee rules for {{ session.name }} 
                        ({{ format(new Date(session.start_date), 'MMM d, yyyy') }} - {{ format(new Date(session.end_date), 'MMM d, yyyy') }}).
                    </p>
                </div>
                <Button @click="openCreateConfig"><Plus class="mr-2 h-4 w-4" /> Add Rule</Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Fee Configuration Rules</CardTitle>
                    <CardDescription>
                        Define how fees are applied for this academic session.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Type</TableHead>
                                <TableHead>Target</TableHead>
                                <TableHead>Level</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody v-if="session.fee_configurations && session.fee_configurations.length > 0">
                            <TableRow v-for="config in session.fee_configurations" :key="config.id">
                                <TableCell class="font-medium">
                                    {{ config.fee_type?.name }}
                                    <Badge v-if="config.is_compulsory" class="ml-2 text-[10px]" variant="outline">Compulsory</Badge>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground">
                                    <div v-if="config.program">Prog: <span class="text-foreground">{{ config.program.name }}</span></div>
                                    <div v-else-if="config.department">Dept: <span class="text-foreground">{{ config.department.name }}</span></div>
                                    <div v-else-if="config.faculty">Fac: <span class="text-foreground">{{ config.faculty.name }}</span></div>
                                    <div v-else class="italic">Global</div>
                                </TableCell>
                                <TableCell>{{ config.level || 'All' }}</TableCell>
                                <TableCell class="font-bold">{{ formatCurrency(config.amount) }}</TableCell>
                                <TableCell class="text-right space-x-2">
                                    <Button variant="ghost" size="icon" @click="openEditConfig(config)"><Edit class="h-4 w-4" /></Button>
                                    <Button variant="ghost" size="icon" class="text-destructive" @click="deleteConfig(config)"><Trash2 class="h-4 w-4" /></Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                        <TableBody v-else>
                            <TableRow>
                                <TableCell colspan="5" class="text-center py-8 text-muted-foreground">
                                    No fee rules defined for this session yet.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- CONFIG FORM MODAL -->
            <Dialog v-model:open="isConfigModalOpen">
                <DialogContent class="max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{{ editingConfig ? 'Edit Fee Rule' : 'New Fee Rule' }}</DialogTitle>
                        <DialogDescription>Adding rule to: {{ session.name }}</DialogDescription>
                    </DialogHeader>
                    
                    <div class="grid gap-4 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label>Fee Type</Label>
                                <Select :model-value="configForm.fee_type_id ? String(configForm.fee_type_id) : undefined" @update:model-value="(v) => configForm.fee_type_id = Number(v)" :disabled="editingConfig">
                                    <SelectTrigger><SelectValue placeholder="Select type" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="t in feeTypes" :key="t.id" :value="String(t.id)">{{ t.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                             <div class="grid gap-2">
                                <Label>Amount</Label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-muted-foreground">₦</span>
                                    <Input type="number" class="pl-8" v-model="configForm.amount" placeholder="0.00" />
                                </div>
                                <p class="text-xs text-muted-foreground" v-if="configForm.amount">
                                    {{ formatCurrency(configForm.amount) }}
                                </p>
                            </div>
                        </div>


                        <div class="p-4 border rounded-md bg-muted/20 space-y-4">
                            <h4 class="text-sm font-semibold">Target Specific Group (Optional)</h4>
                             
                             <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label>Faculty</Label>
                                    <Select :model-value="configForm.faculty_id || undefined" @update:model-value="(v) => configForm.faculty_id = v as string">
                                        <SelectTrigger><SelectValue placeholder="All Faculties" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All</SelectItem>
                                            <SelectItem v-for="f in faculties" :key="f.id" :value="String(f.id)">{{ f.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="grid gap-2">
                                    <Label>Department</Label>
                                    <Select :model-value="configForm.department_id || undefined" @update:model-value="(v) => configForm.department_id = v as string">
                                        <SelectTrigger><SelectValue placeholder="All Departments" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All</SelectItem>
                                            <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">{{ d.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                             <div class="grid grid-cols-2 gap-4">
                                <div class="grid gap-2">
                                    <Label>Program</Label>
                                    <Select :model-value="configForm.program_id || undefined" @update:model-value="(v) => configForm.program_id = v as string">
                                        <SelectTrigger><SelectValue placeholder="All Programs" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All</SelectItem>
                                            <SelectItem v-for="p in programs" :key="p.id" :value="String(p.id)">{{ p.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                 <div class="grid gap-2">
                                    <Label>Level</Label>
                                    <Input v-model="configForm.level" placeholder="e.g. 100" />
                                </div>
                             </div>
                        </div>
                          <div class="flex items-center space-x-2">
                             <Checkbox id="comp-new" :checked="configForm.is_compulsory" @update:checked="(v: boolean) => configForm.is_compulsory = v" />
                            <Label for="comp-new">Is Compulsory?</Label>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isConfigModalOpen = false">Cancel</Button>
                        <Button @click="submitConfig" :disabled="configForm.processing">Save Rule</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AdminLayout>
</template>
