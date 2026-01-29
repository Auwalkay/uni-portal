<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
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
import { Plus, Trash2, Edit } from 'lucide-vue-next';

interface FeeType {
    id: number;
    name: string;
    slug: string;
    description: string;
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
    configurations: FeeConfiguration[];
    sessions: any[];
    faculties: any[];
    departments: any[];
    programs: any[];
}>();





// Filtered Departments based on Faculty selection could be added here for better UX

// Fee Type Form
const feeTypeForm = useForm({
    id: null as number | null,
    name: '',
    description: '',
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
    feeTypeForm.id = type.id;
    editingFeeType.value = true;
    isFeeTypeModalOpen.value = true;
};

const submitFeeType = () => {
    if (editingFeeType.value && feeTypeForm.id) {
        feeTypeForm.put(route('admin.finance.fee_types.update', feeTypeForm.id), {
            onSuccess: () => {
                isFeeTypeModalOpen.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Fee Type updated successfully',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            },
            onError: () => Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to update Fee Type',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            })
        });
    } else {
        feeTypeForm.post(route('admin.finance.fee_types.store'), {
            onSuccess: () => {
                isFeeTypeModalOpen.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Fee Type created successfully',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            },
            onError: () => Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to create Fee Type',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            })
        });
    }
};

const deleteFeeType = (type: FeeType) => {
    if (confirm('Are you sure? This cannot be undone if used.')) {
        router.delete(route('admin.finance.fee_types.destroy', type.id), {
            onSuccess: () => Swal.fire({
                icon: 'success',
                title: 'Deleted',
                text: 'Fee Type deleted',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            }),
            onError: () => Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to delete fee type',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            })
        });
    }
};

// Configuration Form
const configForm = useForm({
    id: null as number | null,
    fee_type_id: null as number | null,
    session_id: null as string | null,
    amount: '' as string | number,
    faculty_id: null as string | null,
    department_id: null as string | null,
    program_id: null as string | null,
    level: '',
    is_compulsory: true
});

const isConfigModalOpen = ref(false);
const editingConfig = ref(false);

const openCreateConfig = () => {
    configForm.reset();
    // Default to current session if available - logic could be added
    if (props.sessions.length > 0) {
        configForm.session_id = props.sessions[0].id;
    }
    
    editingConfig.value = false;
    isConfigModalOpen.value = true;
};

// Only limited editing for configs as per controller plan
const openEditConfig = (config: FeeConfiguration) => {
    configForm.reset();
    configForm.id = config.id;
    configForm.amount = config.amount;
    configForm.level = config.level || '';
    configForm.is_compulsory = config.is_compulsory;
    
    // Set non-editable fields for display or validation purposes if needed, 
    // but the backend only updates amount/level/compulsory.
    
    editingConfig.value = true;
    isConfigModalOpen.value = true;
};

const submitConfig = () => {
    // Transform 'all' to null for backend
    const form = configForm;
    if (form.faculty_id === 'all') form.faculty_id = null;
    if (form.department_id === 'all') form.department_id = null;
    if (form.program_id === 'all') form.program_id = null;
    
    if (editingConfig.value && configForm.id) {
        configForm.put(route('admin.finance.configurations.update', configForm.id), {
            onSuccess: () => {
                isConfigModalOpen.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    text: 'Fee Rule updated',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            },
            onError: () => Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to update rule',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            })
        });
    } else {
        configForm.post(route('admin.finance.configurations.store'), {
             onSuccess: () => {
                 isConfigModalOpen.value = false;
                 Swal.fire({
                     icon: 'success',
                     title: 'Created',
                     text: 'Fee Rule created',
                     toast: true,
                     position: 'top-end',
                     showConfirmButton: false,
                     timer: 3000
                 });
             },
             onError: () => Swal.fire({
                 icon: 'error',
                 title: 'Error',
                 text: 'Failed to create rule',
                 toast: true,
                 position: 'top-end',
                 showConfirmButton: false,
                 timer: 3000
             })
        });
    }
};

const deleteConfig = (config: FeeConfiguration) => {
    if (confirm('Delete this rule?')) {
        router.delete(route('admin.finance.configurations.destroy', config.id), {
            onSuccess: () => Swal.fire({
                icon: 'success',
                title: 'Deleted',
                text: 'Rule deleted',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            }),
            onError: () => Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to delete rule',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            })
        });
    }
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val);
};
</script>

<template>
    <Head title="Finance Management" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Finance Management</h2>
                    <p class="text-muted-foreground">Manage fees types and allocation rules.</p>
                </div>
            </div>

            <Tabs defaultValue="types" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="types">Fee Types</TabsTrigger>
                    <TabsTrigger value="configs">Fee Rules / Configurations</TabsTrigger>
                </TabsList>

                <!-- FEE TYPES TAB -->
                <TabsContent value="types" class="space-y-4">
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
                                        <TableCell class="font-medium">{{ type.name }}</TableCell>
                                        <TableCell class="font-mono text-xs">{{ type.slug }}</TableCell>
                                        <TableCell>{{ type.description }}</TableCell>
                                        <TableCell><Badge variant="secondary">{{ type.configurations_count }} rules</Badge></TableCell>
                                        <TableCell class="text-right space-x-2">
                                            <Button variant="ghost" size="icon" @click="openEditFeeType(type)">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" class="text-destructive" @click="deleteFeeType(type)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <!-- Fee Type Modal -->
                    <Dialog v-model:open="isFeeTypeModalOpen">
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{{ editingFeeType ? 'Edit Fee Type' : 'New Fee Type' }}</DialogTitle>
                            </DialogHeader>
                            <div class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label for="name">Name</Label>
                                    <Input id="name" v-model="feeTypeForm.name" placeholder="e.g. Tuition Fee" />
                                    <span v-if="feeTypeForm.errors.name" class="text-xs text-destructive">{{ feeTypeForm.errors.name }}</span>
                                </div>
                                <div class="grid gap-2">
                                    <Label for="description">Description</Label>
                                    <Input id="description" v-model="feeTypeForm.description" placeholder="Short description" />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="isFeeTypeModalOpen = false">Cancel</Button>
                                <Button @click="submitFeeType" :disabled="feeTypeForm.processing">Save</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </TabsContent>

                <!-- CONFIGURATIONS TAB -->
                <TabsContent value="configs" class="space-y-4">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Fee Allocation Rules</CardTitle>
                                <CardDescription>Assign fees to sessions, faculties, departments, or levels.</CardDescription>
                            </div>
                            <Button @click="openCreateConfig"><Plus class="mr-2 h-4 w-4" /> Add Rule</Button>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Session</TableHead>
                                        <TableHead>Target (Faculty/Dept/Prog)</TableHead>
                                        <TableHead>Level</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="config in configurations" :key="config.id">
                                        <TableCell class="font-medium">
                                            {{ config.fee_type?.name }}
                                            <Badge v-if="config.is_compulsory" class="ml-2 text-[10px]" variant="outline">Compulsory</Badge>
                                        </TableCell>
                                        <TableCell>{{ config.session?.name }}</TableCell>
                                        <TableCell class="text-xs text-muted-foreground">
                                            <div v-if="config.program">Prog: <span class="text-foreground">{{ config.program.name }}</span></div>
                                            <div v-else-if="config.department">Dept: <span class="text-foreground">{{ config.department.name }}</span></div>
                                            <div v-else-if="config.faculty">Fac: <span class="text-foreground">{{ config.faculty.name }}</span></div>
                                            <div v-else class="italic">Global (All Students)</div>
                                        </TableCell>
                                        <TableCell>{{ config.level || 'All' }}</TableCell>
                                        <TableCell class="font-bold">{{ formatCurrency(config.amount) }}</TableCell>
                                        <TableCell class="text-right space-x-2">
                                            <Button variant="ghost" size="icon" @click="openEditConfig(config)">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" class="text-destructive" @click="deleteConfig(config)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <!-- Config Modal -->
                    <Dialog v-model:open="isConfigModalOpen">
                        <DialogContent class="max-w-xl">
                            <DialogHeader>
                                <DialogTitle>{{ editingConfig ? 'Edit Fee Rule' : 'New Fee Rule' }}</DialogTitle>
                            </DialogHeader>
                            
                            <!-- If Editing, only show amount/level fields as others are structural -->
                            <div v-if="editingConfig" class="grid gap-4 py-4">
                                <div class="grid gap-2">
                                    <Label>Amount (NGN)</Label>
                                    <Input type="number" v-model="configForm.amount" />
                                </div>
                                <div class="grid gap-2">
                                    <Label>Level (Optional)</Label>
                                    <Input v-model="configForm.level" placeholder="e.g. 100" />
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Checkbox id="compulsory" :checked="configForm.is_compulsory" @update:checked="(v: boolean) => configForm.is_compulsory = v" />
                                    <Label for="compulsory">Is Compulsory?</Label>
                                </div>
                            </div>

                            <!-- New Rule Form -->
                            <div v-else class="grid gap-4 py-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="grid gap-2">
                                        <Label>Fee Type</Label>
                                        <Select :model-value="configForm.fee_type_id ? String(configForm.fee_type_id) : undefined" @update:model-value="(v: string) => configForm.fee_type_id = Number(v)">
                                            <SelectTrigger><SelectValue placeholder="Select type" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="t in feeTypes" :key="t.id" :value="String(t.id)">{{ t.name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <span v-if="configForm.errors.fee_type_id" class="text-xs text-destructive">{{ configForm.errors.fee_type_id }}</span>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Session</Label>
                                        <Select :model-value="configForm.session_id || undefined" @update:model-value="(v) => configForm.session_id = v as string">
                                            <SelectTrigger><SelectValue placeholder="Select session" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <span v-if="configForm.errors.session_id" class="text-xs text-destructive">{{ configForm.errors.session_id }}</span>
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label>Amount (NGN)</Label>
                                    <Input type="number" v-model="configForm.amount" placeholder="0.00" />
                                    <span v-if="configForm.errors.amount" class="text-xs text-destructive">{{ configForm.errors.amount }}</span>
                                </div>

                                <div class="p-4 border rounded-md bg-muted/20 space-y-4">
                                    <h4 class="text-sm font-semibold">Target Specific Group (Optional)</h4>
                                    <p class="text-xs text-muted-foreground">Leave all blank to apply to EVERYONE (Global Fee).</p>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="grid gap-2">
                                            <Label>Faculty</Label>
                                            <Select :model-value="configForm.faculty_id || undefined" @update:model-value="(v) => configForm.faculty_id = v as string">
                                                <SelectTrigger><SelectValue placeholder="All Faculties" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">All</SelectItem>
                                                    <SelectItem v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div class="grid gap-2">
                                            <Label>Department</Label>
                                            <Select :model-value="configForm.department_id || undefined" @update:model-value="(v) => configForm.department_id = v as string">
                                                <SelectTrigger><SelectValue placeholder="All Departments" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">All</SelectItem>
                                                    <SelectItem v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</SelectItem>
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
                                                    <SelectItem v-for="p in programs" :key="p.id" :value="p.id">{{ p.name }}</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                         <div class="grid gap-2">
                                            <Label>Level</Label>
                                            <Input v-model="configForm.level" placeholder="e.g. 100 (Leave empty for all)" />
                                        </div>
                                     </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                     <Checkbox id="compulsory-new" :checked="configForm.is_compulsory" @update:checked="(v: boolean) => configForm.is_compulsory = v" />
                                    <Label for="compulsory-new">Is Compulsory?</Label>
                                </div>
                            </div>
                            
                            <DialogFooter>
                                <Button variant="outline" @click="isConfigModalOpen = false">Cancel</Button>
                                <Button @click="submitConfig" :disabled="configForm.processing">Save Rule</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </TabsContent>
            </Tabs>
        </div>
    </AdminLayout>
</template>
