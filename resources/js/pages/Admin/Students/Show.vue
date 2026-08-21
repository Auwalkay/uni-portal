<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    User, 
    Users,
    GraduationCap, 
    Banknote,
    FileText,
    MapPin,
    Phone,
    Mail,
    Calendar,
    Building2,
    ArrowLeft,
    Printer,
    Edit,
    Lock,
    FileIcon,
    Download,
    Check,
    Trash2,
    TrendingUp,
    Plus
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/vue3';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion';
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
import { Label } from '@/components/ui/label';

const props = defineProps<{
    student: any;
    academicHistory: any;
    financialHistory: {
        invoices: Array<any>;
        payments: Array<any>;
    } | null;
    permissions: {
        can_view_finance: boolean;
        can_view_academics: boolean;
        can_edit_admission: boolean;
        can_edit_students?: boolean;
        can_perform_registration?: boolean;
        manage_student_registrations?: boolean;
        can_reset_password?: boolean;
    };
    sessions: Array<any>;
}>();

const printOpen = ref(false);
const printOptions = ref({
    personal: true,
    academic: props.permissions.can_view_academics,
    financial: props.permissions.can_view_finance,
});

const handlePrint = () => {
    printOpen.value = false;
    // Allow dialog to close before printing
    setTimeout(() => {
        window.print();
    }, 300);
};

const handleResetPassword = () => {
    if (confirm(`Are you sure you want to reset the password for ${props.student.user.name}? A new password will be generated and emailed to them.`)) {
        router.post(route('admin.students.reset_password', props.student.id));
    }
};

const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric'
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(amount);
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'paid': return 'default';
        case 'pending': return 'secondary';
        case 'failed': return 'destructive';
        default: return 'outline';
    }
};

const getStatusClass = (status: string) => {
     switch (status) {
        case 'paid': return 'bg-green-100 text-green-800 hover:bg-green-200 border-green-200';
        case 'pending': return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 border-yellow-200';
        case 'failed': return 'bg-red-100 text-red-800 hover:bg-red-200 border-red-200';
        default: return '';
    }
};

const promoteStudent = () => {
    if (confirm(`Are you sure you want to promote ${props.student.user.name} to the next level? This will create a new session record and generate the corresponding school fee invoice.`)) {
        router.post(route('admin.students.promote', props.student.id));
    }
};

const sessionForm = useForm({
    admitted_session_id: props.student.admitted_session_id,
});

const updateAdmissionSession = () => {
    sessionForm.put(route('admin.students.update_admission_session', props.student.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Close dialog? Dialog closing is handled by state or usually just closing it.
            // Since we use DialogTrigger, we might need a ref if we want to force close.
            // But usually just finishing is fine.
        }
    });
};

const deleteInvoice = (id: number) => {
    if (confirm('Are you sure you want to delete this invoice? This action cannot be undone.')) {
        router.delete(route('admin.invoices.destroy', id), {
            preserveScroll: true
        });
    }
};

// Student Sessions History State & Methods
const sessionModalOpen = ref(false);
const editingSession = ref<any>(null);

const studentSessionForm = useForm({
    session_id: '',
    level: '100',
    semester: 'First Semester',
    status: 'active',
});

const openAddSessionModal = () => {
    editingSession.value = null;
    studentSessionForm.session_id = props.sessions[0]?.id || '';
    studentSessionForm.level = '100';
    studentSessionForm.semester = 'First Semester';
    studentSessionForm.status = 'active';
    studentSessionForm.clearErrors();
    sessionModalOpen.value = true;
};

const openEditSessionModal = (sessionRecord: any) => {
    editingSession.value = sessionRecord;
    studentSessionForm.session_id = sessionRecord.session_id;
    studentSessionForm.level = sessionRecord.level.toString();
    studentSessionForm.semester = sessionRecord.semester;
    studentSessionForm.status = sessionRecord.status;
    studentSessionForm.clearErrors();
    sessionModalOpen.value = true;
};

const submitStudentSession = () => {
    if (editingSession.value) {
        studentSessionForm.put(route('admin.students.sessions.update', [props.student.id, editingSession.value.id]), {
            preserveScroll: true,
            onSuccess: () => {
                sessionModalOpen.value = false;
            },
        });
    } else {
        studentSessionForm.post(route('admin.students.sessions.store', props.student.id), {
            preserveScroll: true,
            onSuccess: () => {
                sessionModalOpen.value = false;
            },
        });
    }
};
</script>

<template>
    <Head :title="`${student.user.name} - Profile`" />

    <div class="print:hidden">
        <AdminLayout>
            <div class="py-10 px-6 space-y-8 w-full max-w-[1600px] mx-auto">
                
                <!-- Back Link -->
                <Button variant="ghost" size="sm" as-child class="-ml-2 text-muted-foreground hover:text-foreground">
                    <Link :href="route('admin.students.index')">
                        <ArrowLeft class="w-4 h-4 mr-2" /> Back to Students
                    </Link>
                </Button>

                <!-- Profile Header Card -->
                <Card>
                    <div class="h-32 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-t-lg opacity-90"></div>
                    <CardContent class="relative pt-0 pb-6 px-6">
                         <div class="flex flex-col md:flex-row items-start md:items-end -mt-12 gap-6">
                            <Avatar class="w-32 h-32 border-4 border-background shadow-lg">
                                <AvatarImage :src="student?.passport_photo_path ? `/storage/${student.passport_photo_path}` : ''" class="object-cover" />
                                <AvatarFallback class="text-3xl bg-muted">{{ student.user.name.charAt(0) }}</AvatarFallback>
                            </Avatar>
                            
                            <div class="flex-1 space-y-1 mt-2 md:mt-0 pb-2">
                                <h1 class="text-3xl font-bold text-foreground">{{ student.user.name }}</h1>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <Building2 class="w-4 h-4" /> {{ student.academic_department?.name || 'No Dept' }}
                                    </span>
                                    <span>&bull;</span>
                                    <span class="flex items-center gap-1">
                                        <GraduationCap class="w-4 h-4" /> {{ student.current_level }} Level
                                    </span>
                                    <span>&bull;</span>
                                    <span class="font-mono">{{ student.matriculation_number }}</span>
                                </div>
                            </div>

                            <div class="flex gap-2 self-start md:self-end mb-2">
                                <Dialog v-model:open="printOpen">
                                    <DialogTrigger as-child>
                                        <Button variant="outline" size="sm">
                                            <Printer class="w-4 h-4 mr-2" /> Print Request
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent class="sm:max-w-[425px]">
                                        <DialogHeader>
                                            <DialogTitle>Print Student Profile</DialogTitle>
                                            <DialogDescription>
                                                Select the sections you want to include in the printed report.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div class="grid gap-4 py-4">
                                            <div class="flex items-center space-x-2">
                                                <Checkbox id="print-personal" :checked="printOptions.personal" @update:checked="(v: boolean) => printOptions.personal = v" />
                                                <Label htmlFor="print-personal">Personal Information</Label>
                                            </div>
                                            <div v-if="permissions.can_view_academics" class="flex items-center space-x-2">
                                                <Checkbox id="print-academic" :checked="printOptions.academic" @update:checked="(v: boolean) => printOptions.academic = v" />
                                                <Label htmlFor="print-academic">Academic History</Label>
                                            </div>
                                            <div v-if="permissions.can_view_finance" class="flex items-center space-x-2">
                                                <Checkbox id="print-financial" :checked="printOptions.financial" @update:checked="(v: boolean) => printOptions.financial = v" />
                                                <Label htmlFor="print-financial">Financial History</Label>
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <Button type="button" @click="handlePrint">
                                                <Printer class="w-4 h-4 mr-2" /> Print
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <Button variant="default" size="sm" as-child>
                                    <Link :href="route('admin.students.edit', student.id)">
                                        <Edit class="w-4 h-4 mr-2" /> Edit Profile
                                    </Link>
                                </Button>

                                <Button v-if="permissions.manage_student_registrations" variant="secondary" size="sm" as-child>
                                    <Link :href="route('admin.course_registration.manage', student.id)">
                                        <GraduationCap class="w-4 h-4 mr-2" /> Manage Registration
                                    </Link>
                                </Button>

                                <Button v-if="permissions.manage_student_registrations" variant="outline" size="sm" as-child>
                                    <a :href="route('admin.course_registration.form', student.id)" target="_blank">
                                        <FileText class="w-4 h-4 mr-2" /> Preview Form
                                    </a>
                                </Button>

                                 <Button v-if="permissions.can_reset_password" variant="outline" size="sm" class="border-amber-200 text-amber-700 hover:bg-amber-50" @click="handleResetPassword">
                                    <Lock class="w-4 h-4 mr-2" /> Reset Password
                                </Button>

                                <Button v-if="student.status !== 'graduated'" variant="outline" size="sm" class="border-blue-200 text-blue-600 hover:bg-blue-50" @click="promoteStudent">
                                    <TrendingUp class="w-4 h-4 mr-2" /> Promote Student
                                </Button>
                            </div>
                         </div>
                    </CardContent>
                </Card>

                <Tabs default-value="overview" class="w-full">
                    <TabsList class="grid w-full grid-cols-1 lg:grid-cols-4 lg:w-[520px]">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger v-if="permissions.can_view_academics" value="academic">Academics</TabsTrigger>
                        <TabsTrigger v-if="permissions.can_view_finance" value="finance">Financials</TabsTrigger>
                        <TabsTrigger v-if="permissions.can_edit_students" value="sessions">Sessions</TabsTrigger>
                    </TabsList>
                    
                    <!-- Overview Tab -->
                    <TabsContent value="overview" class="space-y-6 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Personal Info -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <User class="w-5 h-5 text-muted-foreground" /> Personal Information
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="grid gap-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                         <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Email Address</p>
                                            <div class="flex items-center gap-2 text-sm">
                                                <Mail class="w-3.5 h-3.5 text-muted-foreground" /> {{ student.user.email }}
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Phone Number</p>
                                            <div class="flex items-center gap-2 text-sm">
                                                <Phone class="w-3.5 h-3.5 text-muted-foreground" /> {{ student.phone_number || 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="space-y-1 sm:col-span-2">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Address</p>
                                            <div class="flex items-start gap-2 text-sm">
                                                <MapPin class="w-3.5 h-3.5 text-muted-foreground mt-0.5" /> {{ student.address || 'N/A' }}
                                            </div>
                                        </div>
                                         <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Date of Birth</p>
                                            <div class="flex items-center gap-2 text-sm">
                                                <Calendar class="w-3.5 h-3.5 text-muted-foreground" /> {{ formatDate(student.dob) }}
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Origin</p>
                                            <p class="text-sm">
                                                {{ student.lga?.name }}, {{ student.state?.name }}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Academic Info -->
                             <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <GraduationCap class="w-5 h-5 text-muted-foreground" /> Academic Details
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="grid gap-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                         <div class="space-y-1 sm:col-span-2">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Program</p>
                                            <p class="text-sm font-medium">{{ student.program?.name || student.program || 'N/A' }}</p>
                                        </div>
                                         <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Faculty</p>
                                            <p class="text-sm">{{ student.academic_department?.faculty?.name || 'N/A' }}</p>
                                        </div>
                                         <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Department</p>
                                            <p class="text-sm">{{ student.academic_department?.name || 'N/A' }}</p>
                                        </div>
                                         <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Admitted Session</p>
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm">{{ student.admitted_session?.name || 'N/A' }}</p>
                                                <Dialog v-if="permissions.can_edit_admission">
                                                    <DialogTrigger as-child>
                                                        <Button variant="ghost" size="icon" class="h-6 w-6 text-primary hover:text-primary hover:bg-primary/10">
                                                            <Edit class="w-3.5 h-3.5" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent class="sm:max-w-[425px]">
                                                        <DialogHeader>
                                                            <DialogTitle>Change Admission Session</DialogTitle>
                                                            <DialogDescription>
                                                                Update the session when this student was officially admitted.
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <div class="grid gap-4 py-4">
                                                            <div class="space-y-2">
                                                                <Label htmlFor="session">Select Session</Label>
                                                                <Select v-model="sessionForm.admitted_session_id">
                                                                    <SelectTrigger id="session">
                                                                        <SelectValue placeholder="Select a session" />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        <SelectItem v-for="s in sessions" :key="s.id" :value="s.id">
                                                                            {{ s.name }}
                                                                        </SelectItem>
                                                                    </SelectContent>
                                                                </Select>
                                                            </div>
                                                        </div>
                                                        <DialogFooter>
                                                            <Button type="button" @click="updateAdmissionSession" :disabled="sessionForm.processing">
                                                                <Check class="w-4 h-4 mr-2" /> Save Changes
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Entry Mode</p>
                                            <Badge variant="secondary" class="uppercase">{{ student.entry_mode || 'UTME' }}</Badge>
                                        </div>
                                        <div class="space-y-1 sm:col-span-2">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Scholarship Status</p>
                                            <Badge v-if="student.scholarship" variant="default" class="bg-primary/20 text-primary hover:bg-primary/30 border-transparent">
                                                {{ student.scholarship.name }} ({{ Number(student.scholarship.percentage) }}% Discount)
                                            </Badge>
                                            <p v-else class="text-sm text-muted-foreground">None</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                             <!-- Next of Kin -->
                             <Card class="md:col-span-2">
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <Users class="w-5 h-5 text-muted-foreground" /> Next of Kin
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                     <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                        <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Full Name</p>
                                            <p class="text-sm">{{ student.next_of_kin_name || 'N/A' }}</p>
                                        </div>
                                         <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Relationship</p>
                                            <p class="text-sm">{{ student.next_of_kin_relationship || 'N/A' }}</p>
                                        </div>
                                         <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Phone</p>
                                            <p class="text-sm">{{ student.next_of_kin_phone || 'N/A' }}</p>
                                        </div>
                                     </div>
                                </CardContent>
                            </Card>

                            <!-- Uploaded Documents -->
                            <Card class="md:col-span-2">
                                 <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <FileText class="w-5 h-5 text-muted-foreground" /> Documents Uploaded
                                    </CardTitle>
                                </CardHeader>
                                 <CardContent>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                         <!-- Passport -->
                                        <div v-if="student.passport_photo_path" class="flex items-center p-3 border rounded-lg hover:bg-muted/50 transition">
                                            <FileIcon class="w-8 h-8 text-blue-500 mr-3" />
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate">Passport Photo</p>
                                                <p class="text-xs text-muted-foreground">Image</p>
                                            </div>
                                             <a :href="`/storage/${student.passport_photo_path}`" target="_blank" class="p-2 text-muted-foreground hover:text-primary">
                                                <Download class="w-4 h-4" />
                                            </a>
                                        </div>

                                         <!-- Indigene Letter -->
                                         <div v-if="student.indigene_letter_path" class="flex items-center p-3 border rounded-lg hover:bg-muted/50 transition">
                                            <FileIcon class="w-8 h-8 text-orange-500 mr-3" />
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium truncate">Indigene Letter</p>
                                                <p class="text-xs text-muted-foreground">Document</p>
                                            </div>
                                             <a :href="`/storage/${student.indigene_letter_path}`" target="_blank" class="p-2 text-muted-foreground hover:text-primary">
                                                <Download class="w-4 h-4" />
                                            </a>
                                        </div>

                                        <!-- O-Level Scanned Copies -->
                                        <div v-for="(sitting, idx) in (student.o_level_results || student.oLevelResults || [])" :key="sitting.id" class="flex items-center p-3 border rounded-lg hover:bg-muted/50 transition">
                                             <FileIcon class="w-8 h-8 text-purple-500 mr-3" />
                                             <div class="flex-1 min-w-0">
                                                 <p class="text-sm font-medium truncate">O-Level Sitting {{ idx + 1 }} Result</p>
                                                 <p class="text-xs text-muted-foreground">{{ sitting.exam_type }} ({{ sitting.exam_year }})</p>
                                             </div>
                                              <a v-if="sitting.scanned_copy_path" :href="`/storage/${sitting.scanned_copy_path}`" target="_blank" class="p-2 text-muted-foreground hover:text-primary">
                                                 <Download class="w-4 h-4" />
                                             </a>
                                         </div>
                                        
                                        <div v-if="!student.passport_photo_path && !student.indigene_letter_path && (!student.o_level_results || (student.o_level_results || student.oLevelResults || []).length === 0)" class="col-span-full py-4 text-center text-muted-foreground text-sm">
                                            No documents uploaded.
                                        </div>
                                    </div>
                                 </CardContent>
                            </Card>

                            <!-- O-Level Examination Results Card -->
                            <Card v-if="(student.o_level_results || student.oLevelResults || []).length > 0" class="md:col-span-2">
                                 <CardHeader class="pb-3 border-b">
                                     <CardTitle class="text-lg flex items-center gap-2">
                                         <GraduationCap class="w-5 h-5 text-muted-foreground" /> O-Level Examination Results
                                     </CardTitle>
                                 </CardHeader>
                                 <CardContent class="p-6 space-y-6">
                                     <div v-for="(sitting, idx) in (student.o_level_results || student.oLevelResults || [])" :key="sitting.id" class="border rounded-lg p-4 space-y-4">
                                         <div class="flex flex-wrap items-center justify-between border-b pb-2 gap-4">
                                             <div>
                                                 <h3 class="font-bold text-sm text-foreground">Sitting {{ idx + 1 }}: {{ sitting.exam_type }}</h3>
                                                 <p class="text-xs text-muted-foreground">Year: {{ sitting.exam_year }} | Reg No: {{ sitting.exam_number }}</p>
                                             </div>
                                             <a v-if="sitting.scanned_copy_path" :href="`/storage/${sitting.scanned_copy_path}`" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline">
                                                 <Download class="w-3.5 h-3.5" /> View Scanned Document
                                             </a>
                                         </div>
                                         <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                             <div v-for="subj in sitting.subjects" :key="subj.subject" class="bg-muted/30 p-2.5 rounded-md flex justify-between items-center text-xs">
                                                 <span class="font-medium text-foreground truncate pr-2">{{ subj.subject }}</span>
                                                 <Badge variant="outline" class="bg-background shrink-0 font-mono font-bold">{{ subj.grade }}</Badge>
                                             </div>
                                         </div>
                                     </div>
                                 </CardContent>
                             </Card>
                        </div>
                    </TabsContent>

                    <!-- Academics Tab -->
                    <TabsContent v-if="permissions.can_view_academics" value="academic" class="space-y-6 mt-6">
                        <Card v-if="!academicHistory || Object.keys(academicHistory).length === 0">
                            <CardContent class="py-12 flex flex-col items-center justify-center text-muted-foreground">
                                <GraduationCap class="h-10 w-10 mb-4 opacity-50" />
                                <p>No academic records found for this student.</p>
                            </CardContent>
                        </Card>

                        <div v-else class="space-y-6">
                            <Accordion type="single" collapsible class="w-full space-y-4">
                                <AccordionItem v-for="(semesters, sessionName) in academicHistory" :key="sessionName" :value="String(sessionName)" class="border rounded-lg px-4">
                                    <AccordionTrigger class="hover:no-underline">
                                        <div class="flex flex-1 items-center justify-between mr-4">
                                            <span class="text-base font-medium">{{ sessionName }} Session</span>
                                                 <!-- Count total courses in this session -->
                                            <Badge variant="outline">
                                                {{ (Object.values(semesters) as any[]).flat().length }} Courses
                                            </Badge>
                                        </div>
                                    </AccordionTrigger>
                                    <AccordionContent class="pt-4 pb-4 space-y-6">
                                        <div v-for="(courses, semesterName) in semesters" :key="semesterName">
                                            <h4 class="text-sm font-semibold text-muted-foreground mb-3 px-2 border-l-4 border-primary/50">{{ semesterName }}</h4>
                                            <div class="border rounded-md overflow-hidden">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow class="bg-muted/30">
                                                            <TableHead>Course Code</TableHead>
                                                            <TableHead>Course Title</TableHead>
                                                            <TableHead class="text-center">Units</TableHead>
                                                            <TableHead class="text-center">Score</TableHead>
                                                            <TableHead class="text-center">Grade</TableHead>
                                                            <TableHead class="text-right">Pass/Fail</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        <TableRow v-for="reg in courses" :key="reg.id">
                                                            <TableCell class="font-medium font-mono">{{ reg.course?.code }}</TableCell>
                                                            <TableCell>{{ reg.course?.title }}</TableCell>
                                                            <TableCell class="text-center">{{ reg.course?.units }}</TableCell>
                                                            <TableCell class="text-center">{{ reg.score !== null ? reg.score : '-' }}</TableCell>
                                                            <TableCell class="text-center font-bold">{{ reg.grade || '-' }}</TableCell>
                                                            <TableCell class="text-right">
                                                                <Badge v-if="reg.grade" :variant="['A','B','C','D','E'].includes(reg.grade) ? 'default' : 'destructive'" class="text-xs px-2 py-0">
                                                                    {{ ['A','B','C','D','E'].includes(reg.grade) ? 'PASS' : 'FAIL' }}
                                                                </Badge>
                                                                <span v-else class="text-muted-foreground">-</span>
                                                            </TableCell>
                                                        </TableRow>
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </div>
                                    </AccordionContent>
                                </AccordionItem>
                            </Accordion>
                        </div>
                    </TabsContent>

                    <!-- Financials Tab -->
                    <TabsContent v-if="permissions.can_view_finance" value="finance" class="space-y-6 mt-6">
                         <div class="grid gap-6 md:grid-cols-2">
                            <!-- Invoices -->
                             <Card class="flex flex-col h-full">
                                <CardHeader class="flex flex-row items-center justify-between pb-2">
                                    <CardTitle class="text-lg font-medium">Invoices</CardTitle>
                                    <FileText class="w-4 h-4 text-muted-foreground" />
                                </CardHeader>
                                 <CardContent class="p-0 mb-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Reference</TableHead>
                                                <TableHead>Amount</TableHead>
                                                <TableHead class="text-right">Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody v-if="financialHistory">
                                            <TableRow v-for="invoice in (financialHistory.invoices as any[])" :key="invoice.id">
                                                <TableCell class="font-mono text-xs">{{ invoice.reference }}</TableCell>
                                                <TableCell class="font-medium">{{ formatCurrency(invoice.amount) }}</TableCell>
                                                <TableCell class="text-right flex items-center justify-end gap-2">
                                                    <Badge :class="getStatusClass(invoice.status)" variant="outline">{{ invoice.status }}</Badge>
                                                    <Button 
                                                        v-if="invoice.paid_amount == 0" 
                                                        variant="ghost" 
                                                        size="icon" 
                                                        class="h-7 w-7 text-red-400 hover:text-red-600 hover:bg-red-50"
                                                        @click="deleteInvoice(invoice.id)"
                                                        title="Delete unpaid invoice"
                                                    >
                                                        <Trash2 class="w-3.5 h-3.5" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                              <TableRow v-if="financialHistory?.invoices.length === 0">
                                                <TableCell colspan="3" class="text-center py-6 text-muted-foreground">No invoices generated.</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </CardContent>
                             </Card>

                            <!-- Payments -->
                            <Card class="flex flex-col h-full">
                                 <CardHeader class="flex flex-row items-center justify-between pb-2">
                                    <CardTitle class="text-lg font-medium">Payment History</CardTitle>
                                    <Banknote class="w-4 h-4 text-muted-foreground" />
                                </CardHeader>
                                 <CardContent class="p-0">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Date</TableHead>
                                                <TableHead>Amount</TableHead>
                                                <TableHead class="text-right">Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody v-if="financialHistory">
                                            <TableRow v-for="payment in (financialHistory.payments as any[])" :key="payment.id">
                                                <TableCell class="text-xs">{{ formatDate(payment.paid_at) }}</TableCell>
                                                <TableCell class="font-medium">{{ formatCurrency(payment.amount) }}</TableCell>
                                                <TableCell class="text-right">
                                                    <Badge :class="getStatusClass(payment.status)" variant="outline">{{ payment.status }}</Badge>
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-if="financialHistory?.payments.length === 0">
                                                <TableCell colspan="3" class="text-center py-6 text-muted-foreground">No payments recorded.</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                         </div>
                    </TabsContent>

                    <!-- Sessions Tab -->
                    <TabsContent v-slot="{ active }" v-if="permissions.can_edit_students" value="sessions" class="space-y-6 mt-6">
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0">
                                <div>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <Calendar class="w-5 h-5 text-muted-foreground" /> Enrollment History (Sessions)
                                    </CardTitle>
                                    <CardDescription>
                                        View and manage the academic sessions this student has been enrolled in.
                                    </CardDescription>
                                </div>
                                <Button size="sm" @click="openAddSessionModal">
                                    <Plus class="w-4 h-4 mr-2" /> Add Session Record
                                </Button>
                            </CardHeader>
                            <CardContent>
                                <div class="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Academic Session</TableHead>
                                                <TableHead>Level</TableHead>
                                                <TableHead>Semester</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead class="text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-if="!student.sessions || student.sessions.length === 0">
                                                <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                                    No enrollment history records found for this student.
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-for="sessionRecord in student.sessions" :key="sessionRecord.id">
                                                <TableCell class="font-medium">
                                                    {{ sessionRecord.session?.name || 'Unknown Session' }}
                                                </TableCell>
                                                <TableCell>{{ sessionRecord.level }} Level</TableCell>
                                                <TableCell>{{ sessionRecord.semester }}</TableCell>
                                                <TableCell>
                                                    <Badge :variant="sessionRecord.status === 'active' ? 'default' : 'outline'">
                                                        {{ sessionRecord.status }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell class="text-right">
                                                    <Button variant="outline" size="icon" class="h-8 w-8" @click="openEditSessionModal(sessionRecord)">
                                                        <Edit class="w-4 h-4 text-blue-600" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                     </TabsContent>
                </Tabs>
            </div>
        </AdminLayout>
    </div>

    <!-- Add / Edit Session Modal -->
    <Dialog v-model:open="sessionModalOpen">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>{{ editingSession ? 'Edit Session Record' : 'Add Session Record' }}</DialogTitle>
                <DialogDescription>
                    {{ editingSession ? 'Modify the selected session enrollment details.' : 'Create a new academic session enrollment record for this student.' }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitStudentSession" class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="modal_session_id">Academic Session</Label>
                    <Select v-model="studentSessionForm.session_id" id="modal_session_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select academic session" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="s in sessions" :key="s.id" :value="s.id">
                                {{ s.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span v-if="studentSessionForm.errors.session_id" class="text-sm text-red-500">{{ studentSessionForm.errors.session_id }}</span>
                </div>

                <div class="space-y-2">
                    <Label for="modal_level">Level</Label>
                    <Select v-model="studentSessionForm.level" id="modal_level">
                        <SelectTrigger>
                            <SelectValue placeholder="Select level" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="100">100 Level</SelectItem>
                            <SelectItem value="200">200 Level</SelectItem>
                            <SelectItem value="300">300 Level</SelectItem>
                            <SelectItem value="400">400 Level</SelectItem>
                            <SelectItem value="500">500 Level</SelectItem>
                        </SelectContent>
                    </Select>
                    <span v-if="studentSessionForm.errors.level" class="text-sm text-red-500">{{ studentSessionForm.errors.level }}</span>
                </div>

                <div class="space-y-2">
                    <Label for="modal_semester">Semester</Label>
                    <Select v-model="studentSessionForm.semester" id="modal_semester">
                        <SelectTrigger>
                            <SelectValue placeholder="Select semester" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="First Semester">First Semester</SelectItem>
                            <SelectItem value="Second Semester">Second Semester</SelectItem>
                        </SelectContent>
                    </Select>
                    <span v-if="studentSessionForm.errors.semester" class="text-sm text-red-500">{{ studentSessionForm.errors.semester }}</span>
                </div>

                <div class="space-y-2">
                    <Label for="modal_status">Status</Label>
                    <Select v-model="studentSessionForm.status" id="modal_status">
                        <SelectTrigger>
                            <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="suspended">Suspended</SelectItem>
                            <SelectItem value="graduated">Graduated</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                    <span v-if="studentSessionForm.errors.status" class="text-sm text-red-500">{{ studentSessionForm.errors.status }}</span>
                </div>

                <DialogFooter class="pt-4">
                    <Button type="button" variant="outline" @click="sessionModalOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="studentSessionForm.processing">
                        {{ studentSessionForm.processing ? 'Saving...' : 'Save Record' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Print Layout (Hidden on Screen, Visible on Print) -->
    <div class="hidden print:block p-8 max-w-4xl mx-auto space-y-6 text-black bg-white">
        <!-- Letterhead -->
        <div class="border-b-2 border-gray-800 pb-4 mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-bold uppercase tracking-wider text-gray-900">University Portal</h1>
                <p class="text-sm text-gray-600">Administrative Report</p>
            </div>
            <div class="text-right">
                <p class="text-xl font-semibold">{{ student.user.name }}</p>
                <p class="text-sm text-gray-600 font-mono">{{ student.matriculation_number }}</p>
            </div>
        </div>

        <div class="flex items-center justify-between text-sm text-gray-500 mb-6">
            <span>Generated on {{ new Date().toLocaleDateString() }}</span>
            <span class="uppercase font-bold">{{ student.program?.name }} / {{ student.current_level }} Level</span>
        </div>

        <!-- Personal Info Section -->
        <div v-if="printOptions.personal" class="space-y-4">
            <h2 class="text-xl font-bold border-b border-gray-300 pb-2 flex items-center gap-2">
                <User class="w-5 h-5" /> Personal Information
            </h2>
            <div class="grid grid-cols-2 gap-y-4 text-sm">
                <div>
                    <span class="block text-gray-500 uppercase text-xs">Full Name</span>
                    <span class="font-semibold">{{ student.user.name }}</span>
                </div>
                 <div>
                    <span class="block text-gray-500 uppercase text-xs">Email</span>
                    <span>{{ student.user.email }}</span>
                </div>
                 <div>
                    <span class="block text-gray-500 uppercase text-xs">Phone</span>
                    <span>{{ student.phone_number }}</span>
                </div>
                 <div>
                    <span class="block text-gray-500 uppercase text-xs">DOB</span>
                    <span>{{ formatDate(student.dob) }}</span>
                </div>
                 <div>
                    <span class="block text-gray-500 uppercase text-xs">State / LGA</span>
                    <span>{{ student.state?.name }} / {{ student.lga?.name }}</span>
                </div>
                 <div>
                    <span class="block text-gray-500 uppercase text-xs">Department</span>
                    <span>{{ student.academic_department?.name }}</span>
                </div>
            </div>
        </div>

        <!-- Academic History Section -->
        <div v-if="printOptions.academic" class="space-y-4 mt-8 break-inside-avoid">
             <h2 class="text-xl font-bold border-b border-gray-300 pb-2 flex items-center gap-2">
                <GraduationCap class="w-5 h-5" /> Academic History
            </h2>
            <div v-if="!academicHistory || Object.keys(academicHistory).length === 0" class="text-sm text-gray-500 italic">
                No academic records found.
            </div>
            <div v-else class="space-y-6">
                 <div v-for="(semesters, sessionName) in academicHistory" :key="sessionName" class="break-inside-avoid">
                    <h3 class="text-lg font-semibold bg-gray-100 p-2 rounded mb-2">{{ sessionName }} Session</h3>
                    <div v-for="(courses, semesterName) in semesters" :key="semesterName" class="mb-4 pl-2">
                        <h4 class="text-sm font-bold text-gray-600 mb-1 uppercase">{{ semesterName }}</h4>
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="text-left border-b border-gray-300 text-gray-500">
                                    <th class="py-1">Code</th>
                                    <th class="py-1">Title</th>
                                    <th class="py-1 text-center">Unit</th>
                                    <th class="py-1 text-center">Score</th>
                                    <th class="py-1 text-center">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="reg in courses" :key="reg.id" class="border-b border-gray-100">
                                    <td class="py-1 font-mono">{{ reg.course?.code }}</td>
                                    <td class="py-1">{{ reg.course?.title }}</td>
                                    <td class="py-1 text-center">{{ reg.course?.units }}</td>
                                    <td class="py-1 text-center font-mono">{{ reg.score || '-' }}</td>
                                    <td class="py-1 text-center font-bold">{{ reg.grade || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

         <!-- Financial History Section -->
        <div v-if="printOptions.financial && financialHistory" class="space-y-4 mt-8 break-inside-avoid">
             <h2 class="text-xl font-bold border-b border-gray-300 pb-2 flex items-center gap-2">
                <Banknote class="w-5 h-5" /> Financial Summary
            </h2>
            <div class="grid grid-cols-2 gap-8">
                <!-- Invoices -->
                <div>
                    <h3 class="text-sm font-bold text-gray-600 uppercase mb-2">Invoices</h3>
                    <table class="w-full text-sm">
                        <thead>
                           <tr class="text-left border-b border-gray-300 text-gray-500">
                                <th class="py-1">Ref</th>
                                <th class="py-1 text-right">Amount</th>
                                <th class="py-1 text-right">Status</th>
                           </tr>
                        </thead>
                        <tbody v-if="financialHistory">
                            <tr v-for="inv in (financialHistory.invoices as any[])" :key="inv.id" class="border-b border-gray-100">
                                <td class="py-1 font-mono text-xs">{{ inv.reference }}</td>
                                <td class="py-1 text-right">{{ formatCurrency(inv.amount) }}</td>
                                <td class="py-1 text-right uppercase text-xs">{{ inv.status }}</td>
                            </tr>
                            <tr v-if="financialHistory.invoices.length === 0">
                                <td colspan="3" class="text-center italic py-2">No invoices.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Payments -->
                 <div>
                    <h3 class="text-sm font-bold text-gray-600 uppercase mb-2">Payments</h3>
                    <table class="w-full text-sm">
                        <thead>
                           <tr class="text-left border-b border-gray-300 text-gray-500">
                                <th class="py-1">Date</th>
                                <th class="py-1 text-right">Amount</th>
                                <th class="py-1 text-right">Status</th>
                           </tr>
                        </thead>
                         <tbody>
                            <tr v-for="pmt in financialHistory.payments" :key="pmt.id" class="border-b border-gray-100">
                                <td class="py-1 text-xs">{{ formatDate(pmt.paid_at) }}</td>
                                <td class="py-1 text-right">{{ formatCurrency(pmt.amount) }}</td>
                                <td class="py-1 text-right uppercase text-xs">{{ pmt.status }}</td>
                            </tr>
                             <tr v-if="financialHistory.payments.length === 0">
                                <td colspan="3" class="text-center italic py-2">No payments.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="pt-8 text-center text-xs text-gray-400">
            <p>Printed by Administrator</p>
            <p>&copy; {{ new Date().getFullYear() }} University Portal. All rights reserved.</p>
        </div>
    </div>
</template>
