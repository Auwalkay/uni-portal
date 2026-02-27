<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    User, 
    GraduationCap, 
    BookOpen, 
    FileText, 
    CreditCard, 
    MapPin, 
    Calendar, 
    Phone, 
    Mail, 
    ArrowLeft,
    Clock,
    CheckCircle2,
    Building2
} from 'lucide-vue-next';

interface Props {
    tenant: any;
    student: any;
}

const props = defineProps<Props>();

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN'
    }).format(amount);
};
</script>

<template>
    <Head :title="`Student Profile - ${student.user?.name}`" />

    <CentralLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('central.tenants.show', tenant.id)">
                    <Button variant="outline" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                    Student Profile: {{ student.user?.name }}
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Student Header Card -->
                <div class="bg-white rounded-2xl shadow-sm border overflow-hidden p-6 md:p-8">
                    <div class="flex flex-col md:flex-row gap-8 items-center md:items-start text-center md:text-left">
                        <div class="h-32 w-32 rounded-2xl bg-slate-100 border-4 border-white shadow-xl flex items-center justify-center overflow-hidden shrink-0">
                            <img v-if="student.passport_photo_path" :src="'/storage/' + student.passport_photo_path" class="h-full w-full object-cover" />
                            <User v-else class="h-16 w-16 text-slate-300" />
                        </div>
                        
                        <div class="flex-1 space-y-4">
                            <div>
                                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ student.user?.name }}</h1>
                                <p class="text-slate-500 font-medium">{{ student.matriculation_number || 'Matriculation Pending' }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2 justify-center md:justify-start">
                                <Badge variant="outline" class="bg-indigo-50 border-indigo-200 text-indigo-700">
                                    <GraduationCap class="h-3 w-3 mr-1" />
                                    {{ student.program?.name || 'No Program' }}
                                </Badge>
                                <Badge variant="outline" class="bg-blue-50 border-blue-200 text-blue-700">
                                    <Building2 class="h-3 w-3 mr-1" />
                                    {{ student.department?.name || 'No Department' }}
                                </Badge>
                                <Badge :variant="student.status === 'active' ? 'default' : 'secondary'">
                                    {{ student.status.toUpperCase() }}
                                </Badge>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-3 gap-x-8 text-sm pt-2">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <Mail class="h-4 w-4 text-slate-400" />
                                    {{ student.user?.email }}
                                </div>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <Phone class="h-4 w-4 text-slate-400" />
                                    {{ student.phone_number || 'N/A' }}
                                </div>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <Clock class="h-4 w-4 text-slate-400" />
                                    Level {{ student.current_level || 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Tabs -->
                <Tabs defaultValue="academic" class="w-full">
                    <TabsList class="grid w-full grid-cols-2 md:grid-cols-5 h-auto lg:h-10">
                        <TabsTrigger value="academic">Academic Info</TabsTrigger>
                        <TabsTrigger value="personal">Bio & Contact</TabsTrigger>
                        <TabsTrigger value="registrations">Registrations</TabsTrigger>
                        <TabsTrigger value="results">O-Level Results</TabsTrigger>
                        <TabsTrigger value="finance">Finance</TabsTrigger>
                    </TabsList>

                    <!-- Academic Info Tab -->
                    <TabsContent value="academic" class="space-y-6 pt-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <GraduationCap class="h-5 w-5 text-indigo-600" />
                                        Program Details
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Faculty</p>
                                            <p class="font-medium">{{ student.department?.faculty?.name || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Department</p>
                                            <p class="font-medium">{{ student.department?.name || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Program</p>
                                            <p class="font-medium">{{ student.program?.name || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Entry Mode</p>
                                            <p class="font-medium">{{ (student.entry_mode || 'N/A').toUpperCase() }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Admitted Session</p>
                                            <p class="font-medium">{{ student.admitted_session?.name || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Program Duration</p>
                                            <p class="font-medium">{{ student.program_duration ? student.program_duration + ' Years' : 'N/A' }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <BookOpen class="h-5 w-5 text-blue-600" />
                                        Admission Data
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">JAMB Reg Number</p>
                                            <p class="font-medium">{{ student.jamb_registration_number || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">JAMB Score</p>
                                            <p class="font-medium">{{ student.jamb_score || 'N/A' }}</p>
                                        </div>
                                        <div class="col-span-2">
                                            <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Previous Institution</p>
                                            <p class="font-medium">{{ student.previous_institution || 'N/A' }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <!-- Personal Information Tab -->
                    <TabsContent value="personal" class="space-y-6 pt-4">
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <User class="h-5 w-5 text-slate-600" />
                                        Bio Info
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Gender</p>
                                        <p class="font-medium capitalized">{{ student.gender || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Date of Birth</p>
                                        <p class="font-medium">{{ formatDate(student.dob) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">State of Origin</p>
                                        <p class="font-medium">{{ student.state?.name || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">LGA</p>
                                        <p class="font-medium">{{ student.lga?.name || 'N/A' }}</p>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Address</p>
                                        <p class="font-medium">{{ student.address || 'N/A' }}</p>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <MapPin class="h-5 w-5 text-red-600" />
                                        Next of Kin
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2">
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Full Name</p>
                                        <p class="font-medium">{{ student.next_of_kin_name || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Relationship</p>
                                        <p class="font-medium">{{ student.next_of_kin_relationship || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Phone</p>
                                        <p class="font-medium">{{ student.next_of_kin_phone || 'N/A' }}</p>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Address</p>
                                        <p class="font-medium">{{ student.next_of_kin_address || 'N/A' }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <!-- Course Registrations Tab -->
                    <TabsContent value="registrations" class="pt-4">
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-lg flex items-center gap-2">
                                    <BookOpen class="h-5 w-5 text-indigo-600" />
                                    Course Registrations
                                </CardTitle>
                                <CardDescription>History of courses registered by the student.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="relative overflow-x-auto border rounded-xl">
                                    <table class="w-full text-sm text-left">
                                        <thead class="text-xs text-slate-700 uppercase bg-slate-50 border-b">
                                            <tr>
                                                <th class="px-6 py-3 font-bold">Code</th>
                                                <th class="px-6 py-3 font-bold">Course Title</th>
                                                <th class="px-6 py-3 font-bold">Units</th>
                                                <th class="px-6 py-3 font-bold">Session</th>
                                                <th class="px-6 py-3 font-bold">Semester</th>
                                                <th class="px-6 py-3 font-bold">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            <tr v-for="reg in student.registrations" :key="reg.id" class="bg-white hover:bg-slate-50">
                                                <td class="px-6 py-4 font-mono font-bold text-indigo-700">{{ reg.course?.course_code }}</td>
                                                <td class="px-6 py-4 font-medium">{{ reg.course?.title }}</td>
                                                <td class="px-6 py-4">{{ reg.course?.units }}</td>
                                                <td class="px-6 py-4">{{ reg.session?.name }}</td>
                                                <td class="px-6 py-4">{{ reg.semester?.name }}</td>
                                                <td class="px-6 py-4">
                                                    <Badge :variant="reg.status === 'confirmed' ? 'default' : 'secondary'">{{ reg.status }}</Badge>
                                                </td>
                                            </tr>
                                            <tr v-if="!student.registrations || student.registrations.length === 0">
                                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">No course registrations found.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- O-Level Results Tab -->
                    <TabsContent value="results" class="pt-4">
                        <div class="grid grid-cols-1 gap-6">
                            <Card v-for="result in student.o_level_results" :key="result.id">
                                <CardHeader class="border-b bg-slate-50/50">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <CardTitle class="text-lg">{{ result.exam_type?.toUpperCase() }} Result</CardTitle>
                                            <p class="text-sm text-slate-500">Exam Year: {{ result.exam_year }} | Exam No: {{ result.exam_number }}</p>
                                        </div>
                                        <div v-if="result.scanned_copy_path">
                                            <Button variant="outline" size="sm" as-child>
                                                <a :href="'/storage/' + result.scanned_copy_path" target="_blank">
                                                    <FileText class="h-4 w-4 mr-2" />
                                                    View Scanned Copy
                                                </a>
                                            </Button>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="pt-6">
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                        <div v-for="subject in result.subjects" :key="subject.subject" class="bg-slate-50 border rounded-lg p-3 flex justify-between items-center">
                                            <span class="font-medium text-slate-700">{{ subject.subject }}</span>
                                            <Badge variant="secondary" class="font-bold text-indigo-700">{{ subject.grade }}</Badge>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card v-if="!student.o_level_results || student.o_level_results.length === 0">
                                <CardContent class="py-12 text-center text-slate-500 italic">
                                    No O-Level results documented for this student.
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <!-- Finance Tab -->
                    <TabsContent value="finance" class="pt-4">
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-lg flex items-center gap-2">
                                    <CreditCard class="h-5 w-5 text-green-600" />
                                    Invoices & Payments
                                </CardTitle>
                                <CardDescription>Financial records for the student across all sessions.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-6">
                                    <div v-for="sess in student.sessions" :key="sess.id" class="border rounded-xl bg-slate-50/30 overflow-hidden">
                                        <div class="bg-slate-100/50 px-4 py-2 border-b flex justify-between items-center">
                                            <span class="font-bold text-slate-700">{{ sess.session?.name }} - Level {{ sess.level }}</span>
                                            <Badge :variant="sess.status === 'active' ? 'default' : 'secondary'">{{ sess.status }}</Badge>
                                        </div>
                                        <div class="p-4 space-y-3">
                                            <div v-for="invoice in sess.invoices" :key="invoice.id" class="bg-white border rounded-lg p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                                <div>
                                                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Invoice #{{ invoice.invoice_number }}</p>
                                                    <h4 class="font-bold text-lg text-slate-900">{{ formatCurrency(invoice.amount) }}</h4>
                                                    <p class="text-xs text-slate-500">Due: {{ formatDate(invoice.due_date) }}</p>
                                                </div>

                                                <div class="flex items-center gap-3">
                                                    <div class="text-right">
                                                        <p class="text-xs text-slate-500">Paid: {{ formatCurrency(invoice.paid_amount) }}</p>
                                                        <Badge v-if="invoice.status === 'paid'" variant="default" class="bg-green-600">FULLY PAID</Badge>
                                                        <Badge v-else-if="invoice.paid_amount > 0" variant="secondary" class="bg-yellow-100 text-yellow-800 border-yellow-200">PARTIALLY PAID</Badge>
                                                        <Badge v-else variant="destructive">UNPAID</Badge>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="!sess.invoices || sess.invoices.length === 0" class="text-center py-4 text-slate-400 text-sm">
                                                No invoices found for this session record.
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="!student.sessions || student.sessions.length === 0" class="text-center py-12 text-slate-500 border border-dashed rounded-xl">
                                        No student session records (and thus no invoices) found.
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>

            </div>
        </div>
    </CentralLayout>
</template>
