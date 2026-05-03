<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    ArrowLeft,
    Save,
    User,
    GraduationCap,
    FileText,
    ShieldCheck,
    Users,
    MapPin,
    Phone,
    Mail,
    Calendar,
    Briefcase,
    Pencil
} from 'lucide-vue-next';

interface Faculty {
    id: number;
    name: string;
    departments: { id: number; name: string; faculty_id: number }[];
}

interface Session {
    id: string;
    name: string;
}

interface Lga {
    id: number;
    name: string;
    state_id: number;
}

interface State {
    id: number;
    name: string;
    lgas: Lga[];
}

interface Programme {
    id: string;
    name: string;
    department_id: string;
}

interface Scholarship {
    id: string;
    name: string;
}

const props = defineProps<{
    student: any;
    faculties: Faculty[];
    sessions: Session[];
    states: State[];
    programmes: Programme[];
    levels: string[];
    entry_modes: string[];
    scholarships: Scholarship[];
}>();

const activeTab = ref('personal');

const form = useForm({
    first_name: props.student.first_name || '',
    last_name: props.student.last_name || '',
    email: props.student.user?.email || '',
    phone_number: props.student.phone_number || '',
    gender: props.student.gender || '',
    dob: props.student.dob || '',
    address: props.student.address || '',
    state_id: String(props.student.state_id) || '',
    lga_id: String(props.student.lga_id) || '',
    next_of_kin_name: props.student.next_of_kin_name || '',
    next_of_kin_phone: props.student.next_of_kin_phone || '',
    faculty_id: String(props.student.faculty_id) || '',
    department_id: String(props.student.department_id) || '',
    program_id: props.student.program_id || '',
    current_level: props.student.current_level || '',
    admitted_session_id: props.student.admitted_session_id || '',
    entry_mode: props.student.entry_mode || '',
    matriculation_number: props.student.matriculation_number || '',
    jamb_registration_number: props.student.jamb_registration_number || '',
    jamb_score: props.student.jamb_score || '',
    previous_institution: props.student.previous_institution || '',
    password: '',
    fee_policy: props.student.fee_policy || 'admission_session',
    scholarship_id: props.student.scholarship_id || '',
});

// Dependent Dropdown for Departments
const filteredDepartments = computed(() => {
    if (!form.faculty_id) return [];
    const faculty = props.faculties.find(f => String(f.id) === form.faculty_id);
    return faculty ? faculty.departments : [];
});

watch(() => form.faculty_id, (newVal, oldVal) => {
    if (oldVal !== undefined && oldVal !== '') {
        form.department_id = '';
        form.program_id = '';
    }
});

watch(() => form.department_id, (newVal, oldVal) => {
    if (oldVal !== undefined && oldVal !== '') {
        form.program_id = '';
    }
});

watch(() => form.state_id, (newVal, oldVal) => {
    if (oldVal !== undefined && oldVal !== '') {
        form.lga_id = '';
    }
});

// Dependent Dropdown for Programmes
const filteredProgrammes = computed(() => {
    console.log('Editing: Filtering programmes for dept:', form.department_id);
    console.log('Available programmes:', props.programmes.length);
    if (!form.department_id) return [];
    const filtered = props.programmes.filter(p => String(p.department_id) === String(form.department_id));
    console.log('Found matches:', filtered.length);
    return filtered;
});

// Dependent Dropdown for LGAs
const filteredLgas = computed(() => {
    if (!form.state_id) return [];
    const state = props.states.find(s => String(s.id) === form.state_id);
    return state ? state.lgas : [];
});

const submit = () => {
    form.put(route('admin.students.update', props.student.id), {
        onFinish: () => form.reset('password'),
    });
};

const breadcrumbs = [
    { title: 'Student Management', href: route('admin.students.index') },
    { title: 'Edit Student', href: '#' }
];
</script>

<template>
    <Head :title="`Edit ${student.user.name}`" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-[1200px] mx-auto">

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-foreground">Edit Student Profile</h1>
                    <p class="text-muted-foreground">Updating records for {{ student.user.name }} ({{ student.matriculation_number }})</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('admin.students.show', student.id)">View Profile</Link>
                    </Button>
                    <Button @click="submit" :disabled="form.processing" class="shadow-lg">
                        <Save class="w-4 h-4 mr-2" />
                        {{ form.processing ? 'Saving...' : 'Update Records' }}
                    </Button>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Tabs v-model="activeTab" class="w-full">
                    <TabsList class="grid w-full grid-cols-2 md:grid-cols-5 h-auto p-1 bg-muted/50 rounded-xl shadow-sm">
                        <TabsTrigger value="personal" class="py-3 rounded-lg flex items-center gap-2">
                            <User class="w-4 h-4" /> <span class="hidden md:inline">Personal</span>
                        </TabsTrigger>
                        <TabsTrigger value="academic" class="py-3 rounded-lg flex items-center gap-2">
                            <GraduationCap class="w-4 h-4" /> <span class="hidden md:inline">Academic</span>
                        </TabsTrigger>
                        <TabsTrigger value="background" class="py-3 rounded-lg flex items-center gap-2">
                            <FileText class="w-4 h-4" /> <span class="hidden md:inline">Background</span>
                        </TabsTrigger>
                        <TabsTrigger value="nok" class="py-3 rounded-lg flex items-center gap-2">
                            <Users class="w-4 h-4" /> <span class="hidden md:inline">Next of Kin</span>
                        </TabsTrigger>
                        <TabsTrigger value="account" class="py-3 rounded-lg flex items-center gap-2">
                            <ShieldCheck class="w-4 h-4" /> <span class="hidden md:inline">Security</span>
                        </TabsTrigger>
                    </TabsList>

                    <div class="mt-8">
                        <!-- Personal Info Tab -->
                        <TabsContent value="personal" class="space-y-6">
                            <Card class="border-slate-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <User class="w-5 h-5 text-primary" /> Personal Information
                                    </CardTitle>
                                    <CardDescription>Bio-data and contact details.</CardDescription>
                                </CardHeader>
                                <CardContent class="grid md:grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="space-y-2">
                                        <Label for="first_name">First Name</Label>
                                        <Input id="first_name" v-model="form.first_name" required />
                                        <p v-if="form.errors.first_name" class="text-xs text-destructive">{{ form.errors.first_name }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="last_name">Last Name</Label>
                                        <Input id="last_name" v-model="form.last_name" required />
                                        <p v-if="form.errors.last_name" class="text-xs text-destructive">{{ form.errors.last_name }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="email">Email Address</Label>
                                        <div class="relative">
                                            <Mail class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                            <Input id="email" type="email" v-model="form.email" class="pl-10" required />
                                        </div>
                                        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="phone">Phone Number</Label>
                                        <div class="relative">
                                            <Phone class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                            <Input id="phone" v-model="form.phone_number" class="pl-10" required />
                                        </div>
                                        <p v-if="form.errors.phone_number" class="text-xs text-destructive">{{ form.errors.phone_number }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="gender">Gender</Label>
                                        <Select v-model="form.gender">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Gender" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="male">Male</SelectItem>
                                                <SelectItem value="female">Female</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.gender" class="text-xs text-destructive">{{ form.errors.gender }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="dob">Date of Birth</Label>
                                        <div class="relative">
                                            <Calendar class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                            <Input id="dob" type="date" v-model="form.dob" class="pl-10" required />
                                        </div>
                                        <p v-if="form.errors.dob" class="text-xs text-destructive">{{ form.errors.dob }}</p>
                                    </div>
                                    <div class="space-y-2 md:col-span-2">
                                        <Label for="address">Residential Address</Label>
                                        <textarea
                                            id="address"
                                            v-model="form.address"
                                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            required
                                        ></textarea>
                                        <p v-if="form.errors.address" class="text-xs text-destructive">{{ form.errors.address }}</p>
                                    </div>
                                    <div class="grid md:grid-cols-2 gap-4 md:col-span-2">
                                        <div class="space-y-2">
                                            <Label for="state">State of Origin</Label>
                                            <Select v-model="form.state_id">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select State" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="state in states" :key="state.id" :value="String(state.id)">
                                                        {{ state.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p v-if="form.errors.state_id" class="text-xs text-destructive">{{ form.errors.state_id }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="lga">LGA of Origin</Label>
                                            <Select v-model="form.lga_id" :disabled="!form.state_id">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select LGA" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="lga in filteredLgas" :key="lga.id" :value="String(lga.id)">
                                                        {{ lga.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p v-if="form.errors.lga_id" class="text-xs text-destructive">{{ form.errors.lga_id }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Academic Details Tab -->
                        <TabsContent value="academic" class="space-y-6">
                            <Card class="border-slate-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <GraduationCap class="w-5 h-5 text-primary" /> Academic Enrollment
                                    </CardTitle>
                                    <CardDescription>Academic placement and matriculation details.</CardDescription>
                                </CardHeader>
                                <CardContent class="grid md:grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="space-y-2">
                                        <Label for="matric_no">Matriculation Number</Label>
                                        <div class="relative">
                                            <ShieldCheck class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                            <Input id="matric_no" v-model="form.matriculation_number" class="pl-10 font-mono font-bold" required />
                                        </div>
                                        <p v-if="form.errors.matriculation_number" class="text-xs text-destructive">{{ form.errors.matriculation_number }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="level">Current Level</Label>
                                        <Select v-model="form.current_level">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Level" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="lvl in levels" :key="lvl" :value="lvl">
                                                    {{ lvl }} Level
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.current_level" class="text-xs text-destructive">{{ form.errors.current_level }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="faculty">Faculty</Label>
                                        <Select v-model="form.faculty_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Faculty" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="fac in faculties" :key="fac.id" :value="String(fac.id)">
                                                    {{ fac.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.faculty_id" class="text-xs text-destructive">{{ form.errors.faculty_id }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="department">Department</Label>
                                        <Select v-model="form.department_id" :disabled="!form.faculty_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Department" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="dept in filteredDepartments" :key="dept.id" :value="String(dept.id)">
                                                    {{ dept.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.department_id" class="text-xs text-destructive">{{ form.errors.department_id }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="program">Degree Program</Label>
                                        <Select v-model="form.program_id" :disabled="!form.department_id" :key="form.department_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Program" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="prog in filteredProgrammes" :key="prog.id" :value="String(prog.id)">
                                                    {{ prog.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.program_id" class="text-xs text-destructive">{{ form.errors.program_id }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="entry_mode">Entry Mode</Label>
                                        <Select v-model="form.entry_mode">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Mode" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="mode in entry_modes" :key="mode" :value="mode">
                                                    {{ mode }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.entry_mode" class="text-xs text-destructive">{{ form.errors.entry_mode }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="session">Admitted Session</Label>
                                        <Select v-model="form.admitted_session_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Session" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="sess in sessions" :key="sess.id" :value="sess.id">
                                                    {{ sess.name }} Session
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.admitted_session_id" class="text-xs text-destructive">{{ form.errors.admitted_session_id }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="fee_policy">Fee Policy</Label>
                                        <Select v-model="form.fee_policy">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Policy" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="admission_session">Admission Session Fees (Locked)</SelectItem>
                                                <SelectItem value="current_session">Current Session Fees (Floating)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p class="text-[10px] text-muted-foreground italic">Determines whether the student pays their original admission fees or the current session's rate.</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="scholarship">Scholarship (Optional)</Label>
                                        <Select v-model="form.scholarship_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="No Scholarship" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">None (Full Fees)</SelectItem>
                                                <SelectItem v-for="sch in scholarships" :key="sch.id" :value="sch.id">
                                                    {{ sch.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.scholarship_id" class="text-xs text-destructive">{{ form.errors.scholarship_id }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Background Tab -->
                        <TabsContent value="background" class="space-y-6">
                            <Card class="border-slate-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <FileText class="w-5 h-5 text-primary" /> Educational Background
                                    </CardTitle>
                                    <CardDescription>JAMB details and previous studies.</CardDescription>
                                </CardHeader>
                                <CardContent class="grid md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label for="jamb_num">JAMB Registration Number</Label>
                                        <Input id="jamb_num" v-model="form.jamb_registration_number" class="uppercase" />
                                        <p v-if="form.errors.jamb_registration_number" class="text-xs text-destructive">{{ form.errors.jamb_registration_number }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="jamb_score">JAMB Score</Label>
                                        <Input id="jamb_score" type="number" v-model="form.jamb_score" />
                                        <p v-if="form.errors.jamb_score" class="text-xs text-destructive">{{ form.errors.jamb_score }}</p>
                                    </div>
                                    <div class="space-y-2 md:col-span-2">
                                        <Label for="prev_inst">Previous Institution (if any)</Label>
                                        <Input id="prev_inst" v-model="form.previous_institution" />
                                        <p v-if="form.errors.previous_institution" class="text-xs text-destructive">{{ form.errors.previous_institution }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Next of Kin Tab -->
                        <TabsContent value="nok" class="space-y-6">
                            <Card class="border-slate-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Users class="w-5 h-5 text-primary" /> Next of Kin
                                    </CardTitle>
                                    <CardDescription>Emergency contact information.</CardDescription>
                                </CardHeader>
                                <CardContent class="grid md:grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="space-y-2">
                                        <Label for="nok_name">Full Name</Label>
                                        <Input id="nok_name" v-model="form.next_of_kin_name" />
                                        <p v-if="form.errors.next_of_kin_name" class="text-xs text-destructive">{{ form.errors.next_of_kin_name }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="nok_phone">Phone Number</Label>
                                        <Input id="nok_phone" v-model="form.next_of_kin_phone" />
                                        <p v-if="form.errors.next_of_kin_phone" class="text-xs text-destructive">{{ form.errors.next_of_kin_phone }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Account Security Tab -->
                        <TabsContent value="account" class="space-y-6">
                            <Card class="border-slate-200 shadow-sm">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <ShieldCheck class="w-5 h-5 text-primary" /> Account Security
                                    </CardTitle>
                                    <CardDescription>Update portal access credentials.</CardDescription>
                                </CardHeader>
                                <CardContent class="max-w-md space-y-4">
                                    <div class="space-y-2">
                                        <Label for="password">Change Password</Label>
                                        <Input id="password" type="password" v-model="form.password" placeholder="Leave blank to keep current" />
                                        <p class="text-[11px] text-muted-foreground bg-amber-50 dark:bg-amber-900/10 p-3 rounded-lg border border-amber-100 dark:border-amber-900/30">
                                            Changing this will update the student's login password immediately. They will not be notified automatically.
                                        </p>
                                        <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </div>
                </Tabs>

                <div class="flex justify-end gap-4 bg-card border rounded-xl p-6 shadow-sm">
                    <Button type="button" variant="ghost" as-child>
                        <Link :href="route('admin.students.show', student.id)">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing" class="px-8 shadow-md">
                        <Save class="w-4 h-4 mr-2" />
                        {{ form.processing ? 'Saving...' : 'Update Student' }}
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
