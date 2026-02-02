<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    ArrowLeft, Save, CheckCircle, AlertCircle, Plus, Edit, Trash2, Settings as SettingsIcon, 
    Calendar, CreditCard, BookOpen, LayoutDashboard, Sliders, Info
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { route } from 'ziggy-js';
import { format } from 'date-fns';

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

const activateSemester = (semester: any) => {
    if (semester.is_current) return;

    Swal.fire({
        title: `Activate ${semester.name}?`,
        text: `Switching active semester for ${props.session.name}.`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes, Switch',
    }).then((result) => {
        if (result.isConfirmed) {
             router.post(route('admin.sessions.semesters.activate', [props.session.id, semester.id]));
        }
    });
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(val);
};

console.log('SessionSettings mounted', props);
if (typeof route !== 'function') {
    console.error('Ziggy route function is not available!');
}
</script>

<template>
    <Head :title="`Session Settings - ${session.name}`" />
    <AdminLayout>
        <div class="p-6 space-y-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as="a" :href="route('admin.sessions.index')">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-3xl font-bold tracking-tight text-gray-900">{{ session.name }}</h2>
                            <Badge v-if="session.is_current" variant="default" class="bg-green-600 hover:bg-green-700 text-sm px-2.5 py-0.5 shadow-sm">
                                Current
                            </Badge>
                             <Badge v-else variant="outline" class="text-gray-500">
                                Inactive
                            </Badge>
                        </div>
                        <p class="text-muted-foreground mt-1 flex items-center gap-2">
                             <Calendar class="h-4 w-4" />
                             {{ format(new Date(session.start_date), 'MMM yyyy') }} - {{ format(new Date(session.end_date), 'MMM yyyy') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Button v-if="!session.is_current" @click="activateSession" class="bg-blue-600 hover:bg-blue-700">
                        <CheckCircle class="mr-2 h-4 w-4" />
                        Set as Current
                    </Button>
                </div>
            </div>

            <Separator />

            <!-- Tabbed Content -->
            <Tabs default-value="overview" class="space-y-6">
                <TabsList class="grid w-full grid-cols-2 md:w-auto md:inline-grid md:grid-cols-4 bg-muted/50 p-1">
                    <TabsTrigger value="overview" class="gap-2">
                        <LayoutDashboard class="h-4 w-4" /> 
                        Overview
                    </TabsTrigger>
                    <TabsTrigger value="configuration" class="gap-2">
                        <Sliders class="h-4 w-4" /> 
                        Settings
                    </TabsTrigger>
                    <TabsTrigger value="academics" class="gap-2">
                        <BookOpen class="h-4 w-4" /> 
                        Academics
                    </TabsTrigger>
                     <TabsTrigger value="financials" class="gap-2">
                        <CreditCard class="h-4 w-4" /> 
                        Financials
                    </TabsTrigger>
                </TabsList>

                <!-- Overview Tab -->
                <TabsContent value="overview" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                         <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">Total Semesters</CardTitle>
                                <BookOpen class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">{{ session.semesters.length }}</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">Configured Fees</CardTitle>
                                <CreditCard class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">{{ feeConfigurations.length }}</div>
                            </CardContent>
                        </Card>
                         <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">Registration Status</CardTitle>
                                <Activity class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    <span v-if="session.registration_enabled" class="text-green-600">Active</span>
                                    <span v-else class="text-red-500">Closed</span>
                                </div>
                            </CardContent>
                        </Card>
                         <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">Admissions</CardTitle>
                                <Info class="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">
                                    <span v-if="session.admissions_enabled" class="text-green-600">Open</span>
                                    <span v-else class="text-gray-500">Closed</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <Card>
                        <CardHeader>
                             <CardTitle>Quick Summary</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-sm text-muted-foreground">
                                This academic session spans from <span class="font-medium text-foreground">{{ format(new Date(session.start_date), 'MMMM d, yyyy') }}</span> to <span class="font-medium text-foreground">{{ format(new Date(session.end_date), 'MMMM d, yyyy') }}</span>. 
                                It is currently <span :class="session.is_current ? 'text-green-600 font-medium' : 'text-gray-600 font-medium'">{{ session.is_current ? 'active' : 'inactive' }}</span>.
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Settings Tab -->
                <TabsContent value="configuration">
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
                                    <Label class="text-base font-semibold">Admissions Status</Label>
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
                </TabsContent>

                <!-- Academics Tab -->
                <TabsContent value="academics">
                     <Card>
                        <CardHeader>
                            <CardTitle>Semesters Management</CardTitle>
                            <CardDescription>Manage registration dates and status for semesters in this session.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div v-for="semester in session.semesters" :key="semester.id" class="p-4 border rounded-lg flex justify-between items-center bg-card hover:bg-muted/20 transition-colors">
                                    <div class="flex items-center gap-4">
                                         <div class="bg-primary/10 p-2.5 rounded-full">
                                            <Calendar class="h-5 w-5 text-primary" />
                                        </div>
                                        <div class="grid gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-base">{{ semester.name }}</span>
                                                <Badge v-if="semester.is_current" variant="secondary" class="bg-green-100 text-green-800 text-xs">Active Semester</Badge>
                                            </div>
                                            <div class="text-sm text-muted-foreground flex flex-col md:flex-row gap-1 md:gap-4">
                                                <span class="flex items-center gap-1">
                                                    <span class="font-medium">Opens:</span> 
                                                    {{ semester.registration_starts_at ? format(new Date(semester.registration_starts_at), 'MMM d, yyyy') : 'Always Open' }}
                                                </span>
                                                <span class="hidden md:inline text-gray-300">|</span>
                                                 <span class="flex items-center gap-1">
                                                    <span class="font-medium">Closes:</span> 
                                                    {{ semester.registration_ends_at ? format(new Date(semester.registration_ends_at), 'MMM d, yyyy') : 'Never' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button 
                                            v-if="!semester.is_current" 
                                            size="sm" 
                                            variant="secondary"
                                            class="bg-green-100 text-green-700 hover:bg-green-200"
                                            @click="activateSemester(semester)"
                                        >
                                            Activate
                                        </Button>
                                        <Button variant="outline" size="sm" @click="openSemesterModal(semester)">
                                            <Edit class="h-4 w-4 mr-2" /> Edit Dates
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Financials Tab -->
                <TabsContent value="financials">
                     <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Session Fees</CardTitle>
                                <CardDescription>Manage base fees and global configuration.</CardDescription>
                            </div>
                            <div class="flex gap-2">
                                <Link :href="route('admin.finance.session.fees', session.id)">
                                    <Button variant="outline" size="sm">
                                        <SettingsIcon class="mr-2 h-4 w-4" /> Manage All Rules
                                    </Button>
                                </Link>
                                <Button size="sm" @click="openFeeModal()">
                                    <Plus class="mr-2 h-4 w-4" /> Add Quick Fee
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Fee Type</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="config in feeConfigurations" :key="config.id">
                                        <TableCell class="font-medium">
                                            <div class="flex items-center gap-2">
                                                <div class="bg-muted p-1.5 rounded-md">
                                                    <CreditCard class="h-4 w-4 text-muted-foreground" />
                                                </div>
                                                {{ config.fee_type?.name }}
                                            </div>
                                        </TableCell>
                                        <TableCell class="font-mono font-medium">{{ formatCurrency(config.amount) }}</TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="ghost" size="icon" @click="openFeeModal(config)">
                                                <Edit class="h-4 w-4 text-gray-500" />
                                            </Button>
                                             <Button variant="ghost" size="icon" class="text-red-500 hover:text-red-700 hover:bg-red-50" @click="deleteFee(config)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="feeConfigurations.length === 0">
                                        <TableCell colspan="3" class="text-center text-muted-foreground py-8">
                                            <div class="flex flex-col items-center gap-2">
                                                <AlertCircle class="h-8 w-8 text-gray-300" />
                                                <p>No fees configured for this session yet.</p>
                                                <Button variant="link" size="sm" class="text-blue-600" @click="openFeeModal()">Add First Fee</Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
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
                         <div class="relative">
                            <select id="fee_type" v-model="feeForm.fee_type_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 appearance-none">
                                <option value="">Select a Fee Type...</option>
                                <option v-for="type in feeTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-muted-foreground">
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                            </div>
                        </div>
                        <p v-if="feeForm.errors.fee_type_id" class="text-sm text-red-500">{{ feeForm.errors.fee_type_id }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="amount">Amount (₦)</Label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-muted-foreground">₦</span>
                            <Input id="amount" type="number" step="0.01" v-model="feeForm.amount" placeholder="0.00" class="pl-8" />
                        </div> 
                        <div v-if="feeForm.amount" class="text-xs text-muted-foreground mt-1">
                            Preview: {{ formatCurrency(Number(feeForm.amount)) }}
                        </div>
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
