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
import { Plus, Trash2, Edit, Settings } from 'lucide-vue-next';

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
    sessions: any[]; // Now includes feeConfigurations
    faculties: any[];
    departments: any[];
    programs: any[];
}>();

// Fee Type Form (unchanged)
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
                            </div>
                            <DialogFooter>
                                <Button variant="outline" @click="isFeeTypeModalOpen = false">Cancel</Button>
                                <Button @click="submitFeeType" :disabled="feeTypeForm.processing">Save</Button>
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
            </Tabs>





        </div>
    </AdminLayout>
</template>
