<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
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
    Check
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

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
    };
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

                                <Button variant="default" size="sm">
                                    <Edit class="w-4 h-4 mr-2" /> Edit Profile
                                </Button>
                            </div>
                         </div>
                    </CardContent>
                </Card>

                <Tabs default-value="overview" class="w-full">
                    <TabsList class="grid w-full grid-cols-1 lg:grid-cols-3 lg:w-[400px]">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger v-if="permissions.can_view_academics" value="academic">Academics</TabsTrigger>
                        <TabsTrigger v-if="permissions.can_view_finance" value="finance">Financials</TabsTrigger>
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
                                            <p class="text-sm">{{ student.admitted_session?.name || 'N/A' }}</p>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase">Entry Mode</p>
                                            <Badge variant="secondary" class="uppercase">{{ student.entry_mode || 'UTME' }}</Badge>
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
                                        
                                        <div v-if="!student.passport_photo_path && !student.indigene_letter_path" class="col-span-full py-4 text-center text-muted-foreground text-sm">
                                            No documents uploaded.
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
                                                <TableCell class="text-right">
                                                    <Badge :class="getStatusClass(invoice.status)" variant="outline">{{ invoice.status }}</Badge>
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
                </Tabs>
            </div>
        </AdminLayout>
    </div>

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
