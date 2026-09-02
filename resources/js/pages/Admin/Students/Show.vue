<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
    Plus,
    Home,
    Bed,
    Building,
    Eye,
    CreditCard,
    ChevronRight
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

const activeAccommodationBooking = computed(() => {
    const bookings = props.student.hostel_bookings || props.student.hostelBookings || [];
    return bookings.find((b: any) => b.status === 'confirmed') || bookings[0] || null;
});

const isBookingPaymentConfirmed = (booking: any) => {
    if (!booking || !booking.invoice) return false;
    return booking.status === 'confirmed' && ['paid', 'partial'].includes(booking.invoice.status);
};

const selectedInvoice = ref<any>(null);
const invoiceModalOpen = ref(false);

const openInvoiceDetails = (invoice: any) => {
    selectedInvoice.value = invoice;
    invoiceModalOpen.value = true;
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
                <Card class="rounded-3xl border shadow-md overflow-hidden bg-card">
                    <!-- Top Gradient Cover -->
                    <div class="h-36 bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-700 relative">
                        <div class="absolute inset-0 bg-black/10"></div>
                    </div>
                    
                    <CardContent class="relative pt-0 pb-8 px-6 sm:px-8">
                         <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            <!-- Avatar and Student Info -->
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 -mt-16">
                                <Avatar class="w-32 h-32 sm:w-36 sm:h-36 border-4 border-background shadow-xl ring-2 ring-black/5 bg-background shrink-0">
                                    <AvatarImage :src="student?.passport_photo_path ? `/storage/${student.passport_photo_path}` : ''" class="object-cover" />
                                    <AvatarFallback class="text-4xl font-extrabold bg-indigo-50 text-indigo-700">{{ student.user.name.charAt(0) }}</AvatarFallback>
                                </Avatar>
                                
                                <div class="space-y-1.5 sm:mt-14">
                                    <div class="flex items-center gap-2.5">
                                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-foreground">{{ student.user.name }}</h1>
                                        <Badge :variant="student.status === 'active' ? 'default' : 'secondary'" class="capitalize font-bold text-xs">
                                            {{ student.status || 'Active' }}
                                        </Badge>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground font-medium">
                                        <span class="flex items-center gap-1.5 text-foreground/80 font-semibold">
                                            <Building2 class="w-4 h-4 text-primary" /> {{ student.academic_department?.name || 'No Dept' }}
                                        </span>
                                        <span>&bull;</span>
                                        <span class="flex items-center gap-1">
                                            <GraduationCap class="w-4 h-4 text-primary" /> {{ student.current_level }} Level
                                        </span>
                                        <span>&bull;</span>
                                        <span class="font-mono bg-muted px-2 py-0.5 rounded-md text-xs font-bold text-foreground">{{ student.matriculation_number }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons Bar -->
                            <div class="flex flex-wrap items-center gap-2 self-start lg:self-center mt-2 lg:mt-12">
                                <Dialog v-model:open="printOpen">
                                    <DialogTrigger as-child>
                                        <Button variant="outline" size="sm" class="rounded-xl font-bold gap-1.5">
                                            <Printer class="w-4 h-4" /> Print Request
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
                                            <Button type="button" @click="handlePrint" class="rounded-xl font-bold">
                                                <Printer class="w-4 h-4 mr-2" /> Print
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>

                                <Button v-if="permissions.can_edit_students" variant="outline" size="sm" class="rounded-xl font-bold gap-1.5" as-child>
                                    <Link :href="route('admin.students.edit', student.id)">
                                        <Edit class="w-4 h-4" /> Edit Profile
                                    </Link>
                                </Button>

                                <Button v-if="permissions.manage_student_registrations" size="sm" class="rounded-xl font-bold gap-1.5 bg-slate-900 text-white hover:bg-slate-800 shadow-xs" as-child>
                                    <Link :href="route('admin.course_registration.manage', student.id)">
                                        <GraduationCap class="w-4 h-4" /> Manage Registration
                                    </Link>
                                </Button>

                                <Button v-if="permissions.manage_student_registrations" variant="outline" size="sm" class="rounded-xl font-bold gap-1.5" as-child>
                                    <a :href="route('admin.course_registration.form', student.id)" target="_blank">
                                        <FileText class="w-4 h-4" /> Preview Form
                                    </a>
                                </Button>

                                <Button v-if="permissions.can_reset_password" variant="outline" size="sm" class="rounded-xl font-bold gap-1.5 border-amber-200 text-amber-700 hover:bg-amber-50" @click="handleResetPassword">
                                    <Lock class="w-4 h-4" /> Reset Password
                                </Button>

                                <Button v-if="permissions.can_edit_students && student.status !== 'graduated'" variant="outline" size="sm" class="rounded-xl font-bold gap-1.5 border-blue-200 text-blue-600 hover:bg-blue-50" @click="promoteStudent">
                                    <TrendingUp class="w-4 h-4" /> Promote Student
                                </Button>
                            </div>
                         </div>
                    </CardContent>
                </Card>

                <Tabs default-value="overview" class="w-full">
                    <TabsList class="grid w-full grid-cols-2 lg:grid-cols-5 lg:w-[680px]">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger v-if="permissions.can_view_academics" value="academic">Academics</TabsTrigger>
                        <TabsTrigger v-if="permissions.can_view_finance" value="finance">Financials</TabsTrigger>
                        <TabsTrigger value="accommodation">Accommodation</TabsTrigger>
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

                             <!-- Hostel Accommodation Summary Card -->
                             <Card v-if="(student.hostel_bookings || student.hostelBookings || []).length > 0" class="md:col-span-2">
                                 <CardHeader class="pb-3 border-b">
                                     <CardTitle class="text-lg flex items-center justify-between">
                                         <span class="flex items-center gap-2">
                                             <Home class="w-5 h-5 text-muted-foreground" /> Hostel Accommodation
                                         </span>
                                         <Badge variant="outline" class="bg-emerald-100 text-emerald-800 border-emerald-300 font-bold text-xs uppercase">
                                             {{ (student.hostel_bookings || student.hostelBookings || []).length }} Booking Record(s)
                                         </Badge>
                                     </CardTitle>
                                 </CardHeader>
                                 <CardContent class="p-6">
                                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                         <div v-for="booking in (student.hostel_bookings || student.hostelBookings || [])" :key="booking.id" class="bg-muted/30 p-4 rounded-2xl border space-y-3">
                                             <div class="flex items-start justify-between">
                                                 <div>
                                                     <h4 class="font-extrabold text-base text-foreground">
                                                         {{ booking.room?.floor?.block?.hostel?.name || 'Campus Hostel' }}
                                                     </h4>
                                                     <p class="text-xs text-muted-foreground">
                                                         {{ booking.room?.floor?.block?.name }} • {{ booking.room?.floor?.name }}
                                                     </p>
                                                 </div>
                                                 <Badge variant="outline" class="font-mono font-bold text-xs">
                                                     Unit {{ booking.room?.room_number || 'N/A' }}
                                                 </Badge>
                                             </div>

                                             <div class="flex flex-wrap items-center justify-between text-xs pt-2 border-t font-medium gap-2">
                                                 <span>Session: <strong>{{ booking.session?.name }}</strong></span>
                                                 <Badge 
                                                     :variant="booking.status === 'confirmed' ? 'default' : 'secondary'"
                                                     :class="booking.status === 'confirmed' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ''"
                                                 >
                                                     {{ booking.status }}
                                                 </Badge>
                                             </div>

                                             <div v-if="booking.status === 'confirmed' && booking.invoice && (booking.invoice.status === 'paid' || booking.invoice.status === 'partial')" class="pt-1">
                                                 <a 
                                                     :href="route('admin.hostels.bookings.download-slip', booking.id)" 
                                                     target="_blank"
                                                     class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:underline"
                                                 >
                                                     <Download class="w-3.5 h-3.5" /> Download Accommodation Slip
                                                 </a>
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
                    </TabsContent>                    <!-- Financials Tab -->
                    <TabsContent v-if="permissions.can_view_finance" value="finance" class="space-y-6 mt-6">
                        <!-- Student Financial Invoices Card -->
                        <Card class="rounded-3xl shadow-sm border overflow-hidden">
                            <CardHeader class="flex flex-row items-center justify-between border-b p-6">
                                <div>
                                    <CardTitle class="text-xl font-black flex items-center gap-2">
                                        <FileText class="w-5 h-5 text-primary" /> Invoices & Transaction History
                                    </CardTitle>
                                    <CardDescription class="text-xs mt-1">
                                        Click on any invoice row to inspect payment attempts, gateway transaction references, and statuses.
                                    </CardDescription>
                                </div>
                            </CardHeader>

                            <CardContent class="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow class="bg-muted/40 text-xs uppercase font-bold text-muted-foreground">
                                            <TableHead class="py-4 px-6">Invoice Reference</TableHead>
                                            <TableHead class="py-4 px-6">Total Amount</TableHead>
                                            <TableHead class="py-4 px-6">Amount Paid</TableHead>
                                            <TableHead class="py-4 px-6">Invoice Status</TableHead>
                                            <TableHead class="py-4 px-6 text-right font-semibold">Payment Attempts</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody v-if="financialHistory" class="divide-y">
                                        <TableRow 
                                            v-for="invoice in (financialHistory.invoices as any[])" 
                                            :key="invoice.id"
                                            @click="openInvoiceDetails(invoice)"
                                            class="cursor-pointer hover:bg-muted/30 transition-colors group"
                                            title="Click to view payment attempts"
                                        >
                                            <TableCell class="py-4 px-6 font-mono text-sm font-black text-foreground">
                                                <div class="flex items-center gap-2">
                                                    <Eye class="w-4 h-4 text-primary opacity-0 group-hover:opacity-100 transition-opacity" />
                                                    <span>{{ invoice.reference }}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell class="py-4 px-6 font-extrabold text-foreground text-sm">
                                                {{ formatCurrency(invoice.amount) }}
                                            </TableCell>
                                            <TableCell class="py-4 px-6 font-bold text-emerald-600 text-sm">
                                                {{ formatCurrency(invoice.paid_amount || 0) }}
                                            </TableCell>
                                            <TableCell class="py-4 px-6">
                                                <Badge :class="getStatusClass(invoice.status)" variant="outline" class="font-bold text-xs uppercase">
                                                    {{ invoice.status }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell class="py-4 px-6 text-right">
                                                <div class="inline-flex items-center gap-2">
                                                    <span class="text-xs font-bold text-primary bg-primary/10 px-3 py-1 rounded-xl group-hover:bg-primary group-hover:text-white transition-colors">
                                                        View {{ (invoice.payments || []).length }} Attempt(s)
                                                    </span>
                                                    <Button 
                                                        v-if="invoice.paid_amount == 0" 
                                                        variant="ghost" 
                                                        size="icon" 
                                                        class="h-7 w-7 text-red-400 hover:text-red-600 hover:bg-red-50"
                                                        @click.stop="deleteInvoice(invoice.id)"
                                                        title="Delete unpaid invoice"
                                                    >
                                                        <Trash2 class="w-3.5 h-3.5" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="financialHistory?.invoices.length === 0">
                                            <TableCell colspan="5" class="text-center py-12 text-muted-foreground">
                                                <Banknote class="h-10 w-10 mx-auto mb-2 opacity-30" />
                                                <p class="font-bold text-foreground">No Invoices Generated</p>
                                                <p class="text-xs text-muted-foreground">No financial invoices found for this student profile.</p>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                     <!-- Accommodation Tab -->
                     <TabsContent value="accommodation" class="space-y-6 mt-6">
                         <!-- Active Accommodation Showcase Banner (Hero Card) -->
                         <div v-if="activeAccommodationBooking" class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden border border-slate-800">
                             <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                                 <div class="space-y-3">
                                     <div class="flex items-center space-x-2">
                                         <Badge class="bg-emerald-500 text-white font-extrabold text-[10px] uppercase tracking-wider px-3 py-1 border-none shadow-sm">
                                             ACTIVE ALLOCATION
                                         </Badge>
                                         <Badge variant="outline" class="text-white/80 border-white/20 font-bold text-xs">
                                             Session: {{ activeAccommodationBooking.session?.name || 'Current Session' }}
                                         </Badge>
                                     </div>

                                     <div class="flex items-center space-x-3">
                                         <div class="p-3 bg-white/10 rounded-2xl backdrop-blur-md">
                                             <Home class="h-8 w-8 text-emerald-400" />
                                         </div>
                                         <div>
                                             <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                                                 {{ activeAccommodationBooking.room?.floor?.block?.hostel?.name || 'Campus Residential Hall' }}
                                             </h2>
                                             <p class="text-sm text-slate-300 font-medium">
                                                 {{ activeAccommodationBooking.room?.floor?.block?.name || 'Block' }} • {{ activeAccommodationBooking.room?.floor?.name || 'Floor' }}
                                             </p>
                                         </div>
                                     </div>

                                     <!-- Room & Bedspace Pill Badges -->
                                     <div class="flex flex-wrap items-center gap-3 pt-1">
                                         <div class="bg-white/10 backdrop-blur-sm border border-white/15 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
                                             <DoorOpen class="h-4 w-4 text-emerald-400" />
                                             <span>Unit Number: <strong>{{ activeAccommodationBooking.room?.room_number || 'N/A' }}</strong></span>
                                         </div>

                                         <div class="bg-white/10 backdrop-blur-sm border border-white/15 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2">
                                             <Bed class="h-4 w-4 text-indigo-300" />
                                             <span>Bed Space: <strong>Bed {{ activeAccommodationBooking.bed_space_number || '1' }}</strong></span>
                                         </div>
                                     </div>
                                 </div>

                                 <!-- Action Button in Hero Banner -->
                                 <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                                     <a 
                                         v-if="isBookingPaymentConfirmed(activeAccommodationBooking)"
                                         :href="route('admin.hostels.bookings.download-slip', activeAccommodationBooking.id)" 
                                         target="_blank"
                                         class="inline-flex items-center justify-center px-6 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold text-sm rounded-2xl shadow-lg transition-all gap-2"
                                     >
                                         <Download class="h-4 w-4" /> Download Official Allocation Slip
                                     </a>

                                     <Button 
                                         v-else
                                         variant="outline"
                                         class="bg-amber-500/20 text-amber-300 border-amber-500/40 hover:bg-amber-500/30 rounded-2xl font-bold"
                                         disabled
                                     >
                                         Payment Unconfirmed (Slip Disabled)
                                     </Button>

                                     <Button variant="outline" class="bg-white/10 border-white/20 text-white hover:bg-white/20 rounded-2xl font-bold" as-child>
                                         <Link :href="route('admin.hostels.bookings.index')">
                                             Manage Allocations
                                         </Link>
                                     </Button>
                                 </div>
                             </div>
                         </div>

                         <!-- Full History Table Card -->
                         <Card class="rounded-3xl shadow-sm border overflow-hidden">
                             <CardHeader class="flex flex-row items-center justify-between border-b p-6">
                                 <div>
                                     <CardTitle class="text-xl font-black flex items-center gap-2">
                                         <Home class="w-5 h-5 text-primary" /> All Accommodation Bookings & History
                                     </CardTitle>
                                     <CardDescription class="text-xs mt-1">
                                         Comprehensive history of residential hall bookings, payment statuses, and downloadable slips.
                                     </CardDescription>
                                 </div>
                                 <Button size="sm" class="rounded-xl font-bold" as-child>
                                     <Link :href="route('admin.hostels.bookings.index')">
                                         <Plus class="w-4 h-4 mr-2" /> Allocate Accommodation
                                     </Link>
                                 </Button>
                             </CardHeader>

                             <CardContent class="p-0">
                                 <div v-if="(student.hostel_bookings || student.hostelBookings || []).length > 0" class="overflow-x-auto">
                                     <Table>
                                         <TableHeader>
                                             <TableRow class="bg-muted/40 text-xs uppercase font-bold text-muted-foreground">
                                                 <TableHead class="py-4 px-6">Academic Session</TableHead>
                                                 <TableHead class="py-4 px-6">Hostel & Location</TableHead>
                                                 <TableHead class="py-4 px-6">Room Unit</TableHead>
                                                 <TableHead class="py-4 px-6">Booking Status</TableHead>
                                                 <TableHead class="py-4 px-6">Payment Invoice</TableHead>
                                                 <TableHead class="py-4 px-6 text-right">Allocation Slip</TableHead>
                                             </TableRow>
                                         </TableHeader>
                                         <TableBody class="divide-y">
                                             <TableRow v-for="booking in (student.hostel_bookings || student.hostelBookings || [])" :key="booking.id" class="hover:bg-muted/20 transition-colors">
                                                 <TableCell class="py-4 px-6 font-bold text-foreground">
                                                     <Badge variant="outline" class="font-bold text-xs">
                                                         {{ booking.session?.name || 'N/A' }}
                                                     </Badge>
                                                 </TableCell>
                                                 <TableCell class="py-4 px-6">
                                                     <div class="space-y-0.5">
                                                         <p class="font-extrabold text-foreground text-sm">
                                                             {{ booking.room?.floor?.block?.hostel?.name || 'Hostel N/A' }}
                                                         </p>
                                                         <p class="text-xs text-muted-foreground">
                                                             {{ booking.room?.floor?.block?.name || 'Block' }} • {{ booking.room?.floor?.name || 'Floor' }}
                                                         </p>
                                                     </div>
                                                 </TableCell>
                                                 <TableCell class="py-4 px-6">
                                                     <Badge variant="secondary" class="font-bold text-xs font-mono">
                                                         Unit {{ booking.room?.room_number || 'N/A' }}
                                                     </Badge>
                                                 </TableCell>
                                                 <TableCell class="py-4 px-6">
                                                     <span :class="[
                                                         'text-xs font-black px-2.5 py-1 rounded-full uppercase border tracking-wider',
                                                         booking.status === 'confirmed' 
                                                             ? 'bg-emerald-100 text-emerald-800 border-emerald-300' 
                                                             : booking.status === 'cancelled'
                                                                 ? 'bg-slate-100 text-slate-700 border-slate-300'
                                                                 : 'bg-amber-100 text-amber-800 border-amber-300'
                                                     ]">
                                                         {{ booking.status }}
                                                     </span>
                                                 </TableCell>
                                                 <TableCell class="py-4 px-6">
                                                     <div v-if="booking.invoice" class="space-y-0.5">
                                                         <p class="text-xs font-mono font-bold text-foreground">{{ booking.invoice.reference }}</p>
                                                         <span :class="[
                                                             'text-[10px] font-bold uppercase px-2 py-0.5 rounded border',
                                                             booking.invoice.status === 'paid' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-amber-100 text-amber-800 border-amber-200'
                                                         ]">
                                                             {{ booking.invoice.status }}
                                                         </span>
                                                     </div>
                                                     <span v-else class="text-xs text-muted-foreground">No Invoice</span>
                                                 </TableCell>
                                                 <TableCell class="py-4 px-6 text-right">
                                                     <Button 
                                                         v-if="isBookingPaymentConfirmed(booking)"
                                                         variant="outline" 
                                                         size="sm" 
                                                         as-child
                                                         class="text-xs font-bold gap-1.5 rounded-xl border-primary/30 text-primary hover:bg-primary/10"
                                                     >
                                                         <a :href="route('admin.hostels.bookings.download-slip', booking.id)" target="_blank">
                                                             <Download class="w-3.5 h-3.5" /> Download Slip
                                                         </a>
                                                     </Button>
                                                     <span v-else class="text-xs text-amber-600 font-bold bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg">
                                                         Payment Unconfirmed
                                                     </span>
                                                 </TableCell>
                                             </TableRow>
                                         </TableBody>
                                     </Table>
                                 </div>

                                 <div v-else class="py-16 flex flex-col items-center justify-center text-center text-muted-foreground">
                                     <div class="h-16 w-16 bg-muted rounded-full flex items-center justify-center mb-4 ring-8 ring-muted/20">
                                         <Home class="h-8 w-8 text-muted-foreground/50" />
                                     </div>
                                     <p class="font-extrabold text-foreground text-lg">No Accommodation Record Found</p>
                                     <p class="text-xs text-muted-foreground mt-1 max-w-sm">This student has not booked or been allocated any hostel room accommodation.</p>
                                     <Button size="sm" class="mt-6 rounded-full font-bold px-6 shadow-sm" as-child>
                                         <Link :href="route('admin.hostels.bookings.index')">
                                             <Plus class="w-4 h-4 mr-2" /> Allocate Hostel Room
                                         </Link>
                                     </Button>
                                 </div>
                             </CardContent>
                         </Card>
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

    <!-- Invoice Breakdown & Payment Attempts Modal -->
    <Dialog v-model:open="invoiceModalOpen">
        <DialogContent class="sm:max-w-[650px] p-6 rounded-3xl">
            <DialogHeader class="border-b pb-4">
                <DialogTitle class="text-xl font-black flex items-center gap-2">
                    <CreditCard class="w-5 h-5 text-primary" /> Invoice Details & Payment Attempts
                </DialogTitle>
                <DialogDescription class="text-xs">
                    Payment attempts and transactions for Invoice Ref: <span class="font-mono font-bold text-foreground">{{ selectedInvoice?.reference }}</span>
                </DialogDescription>
            </DialogHeader>

            <div v-if="selectedInvoice" class="space-y-6 py-2">
                <!-- Invoice Summary Stats Bar -->
                <div class="grid grid-cols-3 gap-3 bg-muted/30 p-4 rounded-2xl border">
                    <div>
                        <p class="text-[10px] font-bold text-muted-foreground uppercase">Total Amount</p>
                        <p class="text-base font-black text-foreground">{{ formatCurrency(selectedInvoice.amount || 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-muted-foreground uppercase">Amount Paid</p>
                        <p class="text-base font-black text-emerald-600">{{ formatCurrency(selectedInvoice.paid_amount || 0) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-muted-foreground uppercase">Invoice Status</p>
                        <Badge :class="getStatusClass(selectedInvoice.status)" variant="outline" class="font-bold text-xs uppercase mt-0.5">
                            {{ selectedInvoice.status }}
                        </Badge>
                    </div>
                </div>

                <!-- Payment Attempts List Section -->
                <div class="space-y-3">
                    <h4 class="text-xs font-black uppercase tracking-wider text-muted-foreground flex items-center justify-between">
                        <span>Payment Attempts / Transactions</span>
                        <Badge variant="outline" class="font-mono text-xs font-bold">
                            {{ (selectedInvoice.payments || []).length }} Attempt(s)
                        </Badge>
                    </h4>

                    <div v-if="(selectedInvoice.payments || []).length > 0" class="border rounded-2xl overflow-hidden">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50 text-[11px] uppercase font-bold text-muted-foreground">
                                    <TableHead class="py-3 px-4">Date / Time</TableHead>
                                    <TableHead class="py-3 px-4">Gateway Reference</TableHead>
                                    <TableHead class="py-3 px-4">Amount</TableHead>
                                    <TableHead class="py-3 px-4 text-right">Attempt Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody class="divide-y">
                                <TableRow v-for="attempt in selectedInvoice.payments" :key="attempt.id" class="text-xs">
                                    <TableCell class="py-3 px-4 font-medium text-muted-foreground">
                                        {{ formatDate(attempt.paid_at || attempt.created_at) }}
                                    </TableCell>
                                    <TableCell class="py-3 px-4 font-mono font-bold text-foreground">
                                        {{ attempt.gateway_reference || attempt.reference || attempt.id }}
                                    </TableCell>
                                    <TableCell class="py-3 px-4 font-bold text-foreground">
                                        {{ formatCurrency(attempt.amount) }}
                                    </TableCell>
                                    <TableCell class="py-3 px-4 text-right">
                                        <span :class="[
                                            'text-[10px] font-black px-2 py-0.5 rounded-full uppercase border',
                                            attempt.status === 'success' || attempt.status === 'paid'
                                                ? 'bg-emerald-100 text-emerald-800 border-emerald-300'
                                                : attempt.status === 'failed'
                                                    ? 'bg-red-100 text-red-800 border-red-300'
                                                    : 'bg-amber-100 text-amber-800 border-amber-300'
                                        ]">
                                            {{ attempt.status }}
                                        </span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div v-else class="py-8 text-center text-muted-foreground bg-muted/10 border rounded-2xl space-y-1">
                        <Banknote class="w-8 h-8 mx-auto opacity-30" />
                        <p class="text-sm font-bold text-foreground">No Payment Attempts Recorded</p>
                        <p class="text-xs text-muted-foreground">There are no payment attempts or transactions recorded for this invoice yet.</p>
                    </div>
                </div>
            </div>

            <DialogFooter class="border-t pt-4">
                <Button variant="outline" class="rounded-xl font-bold" @click="invoiceModalOpen = false">
                    Close
                </Button>
            </DialogFooter>
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
