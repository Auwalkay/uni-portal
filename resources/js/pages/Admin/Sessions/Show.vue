<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ArrowLeft, Save, CheckCircle, AlertCircle, Plus, Edit, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { route } from 'ziggy-js';

interface Session {
    id: string;
    name: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
    registration_enabled: boolean;
    applications_enabled: boolean;
    admissions_enabled: boolean;
    semesters: any[];
}

interface FeeType {
    id: number;
    name: string;
}

interface FeeConfiguration {
    id: number;
    fee_type_id: number;
    amount: number;
    fee_type: FeeType;
}

const props = defineProps<{
    session: Session;
    feeConfigurations: FeeConfiguration[];
    feeTypes: FeeType[];
}>();

const settingsForm = useForm({
    registration_enabled: props.session.registration_enabled,
    applications_enabled: props.session.applications_enabled,
    admissions_enabled: props.session.admissions_enabled,
});

const isFeeModalOpen = ref(false);
const feeForm = useForm({
    id: null as number | null,
    fee_type_id: '' as string | number,
    amount: '' as string | number,
});

const isSemesterModalOpen = ref(false);
const semesterForm = useForm({
    id: '',
    name: '',
    registration_starts_at: '',
    registration_ends_at: '',
});

const openSemesterModal = (semester: any) => {
    semesterForm.id = semester.id;
    semesterForm.name = semester.name;
    semesterForm.registration_starts_at = semester.registration_starts_at;
    semesterForm.registration_ends_at = semester.registration_ends_at;
    isSemesterModalOpen.value = true;
};

const saveSemester = () => {
    semesterForm.put(route('admin.sessions.semesters.update', [props.session.id, semesterForm.id]), {
        onSuccess: () => {
             isSemesterModalOpen.value = false;
             Swal.fire('Saved', 'Semester dates updated.', 'success');
        }
    });
};

const openFeeModal = (config?: FeeConfiguration) => {
    feeForm.reset();
    feeForm.clearErrors();
    if (config) {
        feeForm.id = config.id;
        feeForm.fee_type_id = config.fee_type_id;
        feeForm.amount = config.amount;
    }
    isFeeModalOpen.value = true;
};

const saveFee = () => {
    feeForm.post(route('admin.sessions.fees.store', props.session.id), {
        onSuccess: () => {
            isFeeModalOpen.value = false;
            Swal.fire('Saved', 'Fee configuration saved.', 'success');
        },
    });
};

const deleteFee = (config: FeeConfiguration) => {
    Swal.fire({
        title: 'Remove Fee?',
        text: 'Are you sure you want to remove this fee configuration?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.sessions.fees.destroy', [props.session.id, config.id]), {
                onSuccess: () => Swal.fire('Deleted', 'Fee configuration removed.', 'success'),
            });
        }
    });
};

const saveSettings = () => {
    settingsForm.put(route('admin.sessions.settings', props.session.id), {
        onSuccess: () => Swal.fire('Saved', 'Session settings updated.', 'success'),
    });
};

const activateSession = () => {
    Swal.fire({
        title: 'Activate Session?',
        text: `Set ${props.session.name} as current? This will promote students and activate the First Semester.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Activate',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.sessions.activate', props.session.id));
        }
    });
};

console.log('SessionSettings mounted', props);
if (typeof route !== 'function') {
    console.error('Ziggy route function is not available!');
}
</script>

<template>
    <Head :title="`Session Settings - ${session.name}`" />
    <AdminLayout>
        <div class="p-6 space-y-6 max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as="a" :href="route('admin.sessions.index')">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">{{ session.name }}</h2>
                        <p class="text-muted-foreground">Session Configuration Dashboard</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge v-if="session.is_current" variant="default" class="bg-green-600 text-base px-3 py-1">Current Session</Badge>
                    <Button v-else variant="outline" @click="activateSession">
                        <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                        Activate Session
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Toggles Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>Feature Toggles</CardTitle>
                        <CardDescription>Control availability of key features for this session.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="flex items-center justify-between space-x-2">
                            <div class="grid gap-1">
                                <Label class="text-base font-semibold">Course Registration</Label>
                                <p class="text-sm text-muted-foreground">Allow students to register for courses.</p>
                            </div>
                            <Switch :checked="settingsForm.registration_enabled" @update:checked="val => settingsForm.registration_enabled = val" />
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between space-x-2">
                            <div class="grid gap-1">
                                <Label class="text-base font-semibold">Applications</Label>
                                <p class="text-sm text-muted-foreground">Allow new applicants to apply for this session.</p>
                            </div>
                            <Switch :checked="settingsForm.applications_enabled" @update:checked="val => settingsForm.applications_enabled = val" />
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between space-x-2">
                            <div class="grid gap-1">
                                <Label class="text-base font-semibold">Admissions</Label>
                                <p class="text-sm text-muted-foreground">Allow applicants to check admission status.</p>
                            </div>
                            <Switch :checked="settingsForm.admissions_enabled" @update:checked="val => settingsForm.admissions_enabled = val" />
                        </div>
                    </CardContent>
                    <CardFooter class="bg-muted/20 border-t p-4 flex justify-end">
                        <Button @click="saveSettings" :disabled="settingsForm.processing">
                            <Save class="mr-2 h-4 w-4" /> Save Changes
                        </Button>
                    </CardFooter>
                </Card>

                <!-- Financial Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Session Fees</CardTitle>
                            <CardDescription>Manage base fees applicable to all students in this session.</CardDescription>
                        </div>
                        <Button size="sm" @click="openFeeModal()">
                            <Plus class="mr-2 h-4 w-4" /> Add Fee
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Fee Type</TableHead>
                                    <TableHead>Amount (₦)</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="config in feeConfigurations" :key="config.id">
                                    <TableCell class="font-medium">{{ config.fee_type?.name }}</TableCell>
                                    <TableCell>{{ Number(config.amount).toLocaleString() }}</TableCell>
                                    <TableCell class="text-right">
                                        <Button variant="ghost" size="icon" @click="openFeeModal(config)">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                         <Button variant="ghost" size="icon" class="text-red-500 hover:text-red-700" @click="deleteFee(config)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="feeConfigurations.length === 0">
                                    <TableCell colspan="3" class="text-center text-muted-foreground py-4">
                                        No fees configured for this session yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <!-- Semesters Info (Read Only/Quick View) -->
            <!-- Semesters Info (Read Only/Quick View) -->
             <Card>
                <CardHeader>
                    <CardTitle>Semesters</CardTitle>
                    <CardDescription>Manage registration dates per semester.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-for="semester in session.semesters" :key="semester.id" class="p-4 border rounded-lg flex justify-between items-center bg-card">
                            <div class="grid gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ semester.name }}</span>
                                    <Badge v-if="semester.is_current" variant="secondary" class="bg-green-100 text-green-800 text-xs">Active</Badge>
                                </div>
                                <div class="text-sm text-muted-foreground flex gap-4">
                                    <span v-if="semester.registration_starts_at">Opens: {{ semester.registration_starts_at }}</span>
                                    <span v-else>Opens: Always</span>

                                    <span v-if="semester.registration_ends_at">Closes: {{ semester.registration_ends_at }}</span>
                                    <span v-else>Closes: Never</span>
                                </div>
                            </div>
                            <Button variant="ghost" size="sm" @click="openSemesterModal(semester)">
                                <Edit class="h-4 w-4" /> Edit
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Add/Edit Fee Dialog -->
        <Dialog :open="isFeeModalOpen" @update:open="isFeeModalOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ feeForm.id ? 'Edit Fee' : 'Add Fee' }}</DialogTitle>
                    <DialogDescription>
                        Configure a fee for this session. It will apply globally unless overridden.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="fee_type">Fee Type</Label>
                        <select id="fee_type" v-model="feeForm.fee_type_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <option value="">Select a Fee Type...</option>
                            <option v-for="type in feeTypes" :key="type.id" :value="type.id">
                                {{ type.name }}
                            </option>
                        </select>
                        <p v-if="feeForm.errors.fee_type_id" class="text-sm text-red-500">{{ feeForm.errors.fee_type_id }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="amount">Amount (₦)</Label>
                        <Input id="amount" type="number" step="0.01" v-model="feeForm.amount" placeholder="0.00" />
                        <p v-if="feeForm.errors.amount" class="text-sm text-red-500">{{ feeForm.errors.amount }}</p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="isFeeModalOpen = false">Cancel</Button>
                    <Button @click="saveFee" :disabled="feeForm.processing">
                        {{ feeForm.processing ? 'Saving...' : 'Save' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Edit Semester Dialog -->
        <Dialog :open="isSemesterModalOpen" @update:open="isSemesterModalOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit {{ semesterForm.name }} Dates</DialogTitle>
                    <DialogDescription>
                        Set the registration window for this semester. Leave empty for continuous access.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="reg_start">Registration Opens</Label>
                        <Input id="reg_start" type="date" v-model="semesterForm.registration_starts_at" />
                        <p v-if="semesterForm.errors.registration_starts_at" class="text-sm text-red-500">{{ semesterForm.errors.registration_starts_at }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="reg_end">Registration Closes</Label>
                        <Input id="reg_end" type="date" v-model="semesterForm.registration_ends_at" />
                        <p v-if="semesterForm.errors.registration_ends_at" class="text-sm text-red-500">{{ semesterForm.errors.registration_ends_at }}</p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="isSemesterModalOpen = false">Cancel</Button>
                    <Button @click="saveSemester" :disabled="semesterForm.processing">
                        {{ semesterForm.processing ? 'Saving...' : 'Save' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
