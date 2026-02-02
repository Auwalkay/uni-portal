<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
    ArrowLeft, 
    Save, 
    UserPlus, 
    User, 
    GraduationCap, 
    FileText, 
    ShieldCheck, 
    Users,
    MapPin,
    Phone,
    Mail,
    Calendar,
    Briefcase
} from 'lucide-vue-next';
import FileUploader from '@/components/FileUploader.vue';

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
}

const props = defineProps<{
    faculties: Faculty[];
    sessions: Session[];
    states: State[];
    programmes: Programme[];
    levels: string[];
    entry_modes: string[];
}>();

const activeTab = ref('personal');

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone_number: '',
    gender: '',
    dob: '',
    address: '',
    state_id: '',
    lga_id: '',
    next_of_kin_name: '',
    next_of_kin_phone: '',
    next_of_kin_relationship: '',
    faculty_id: '',
    department_id: '',
    program_id: '',
    current_level: '',
    admitted_session_id: '',
    entry_mode: '',
    jamb_registration_number: '',
    jamb_score: '',
    previous_institution: '',
    password: '',
    passport_photo: null as File | null,
    waec_result: null as File | null,
});

// Dependent Dropdown for Departments
const filteredDepartments = computed(() => {
    if (!form.faculty_id) return [];
    const faculty = props.faculties.find(f => f.id === Number(form.faculty_id));
    return faculty ? faculty.departments : [];
});

// Dependent Dropdown for LGAs
const filteredLgas = computed(() => {
    if (!form.state_id) return [];
    const state = props.states.find(s => s.id === Number(form.state_id));
    return state ? state.lgas : [];
});

const submit = () => {
    form.post(route('admin.students.store'), {
        forceFormData: true,
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Add New Student" />
    <AdminLayout>
        <div class="py-10 px-6 space-y-8 w-full max-w-[1200px] mx-auto">
            
            <!-- Back Link -->
            <Button variant="ghost" size="sm" as-child class="-ml-2 text-muted-foreground hover:text-foreground">
                <Link :href="route('admin.students.index')">
                    <ArrowLeft class="w-4 h-4 mr-2" /> Back to Students
                </Link>
            </Button>

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-foreground">Add New Student</h1>
                    <p class="text-muted-foreground">Comprehensive onboarding for manual student entry.</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('admin.students.index')">Cancel</Link>
                    </Button>
                    <Button @click="submit" :disabled="form.processing" class="shadow-lg">
                        <UserPlus class="w-4 h-4 mr-2" />
                        {{ form.processing ? 'Creating...' : 'Create Student' }}
                    </Button>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <Tabs v-model="activeTab" class="w-full">
                    <TabsList class="grid w-full grid-cols-2 md:grid-cols-5 h-auto p-1 bg-muted/50 rounded-xl">
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
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <User class="w-5 h-5 text-primary" /> Personal Information
                                    </CardTitle>
                                    <CardDescription>Basic bio-data and contact details of the student.</CardDescription>
                                </CardHeader>
                                <CardContent class="grid md:grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="space-y-2">
                                        <Label for="first_name">First Name</Label>
                                        <Input id="first_name" v-model="form.first_name" placeholder="Enter first name" required />
                                        <p v-if="form.errors.first_name" class="text-xs text-destructive">{{ form.errors.first_name }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="last_name">Last Name</Label>
                                        <Input id="last_name" v-model="form.last_name" placeholder="Enter last name" required />
                                        <p v-if="form.errors.last_name" class="text-xs text-destructive">{{ form.errors.last_name }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="email">Email Address</Label>
                                        <div class="relative">
                                            <Mail class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                            <Input id="email" type="email" v-model="form.email" class="pl-10" placeholder="student@example.com" required />
                                        </div>
                                        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="phone">Phone Number</Label>
                                        <div class="relative">
                                            <Phone class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                            <Input id="phone" v-model="form.phone_number" class="pl-10" placeholder="+234..." required />
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
                                        <div class="relative">
                                            <MapPin class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                            <textarea 
                                                id="address" 
                                                v-model="form.address" 
                                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background pl-10 pr-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                placeholder="Enter full physical address"
                                                required
                                            ></textarea>
                                        </div>
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
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <GraduationCap class="w-5 h-5 text-primary" /> Academic Details
                                    </CardTitle>
                                    <CardDescription>Enrollment details and academic placement.</CardDescription>
                                </CardHeader>
                                <CardContent class="grid md:grid-cols-2 gap-x-8 gap-y-6">
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
                                        <Select v-model="form.program_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Program" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="prog in programmes" :key="prog.id" :value="prog.id">
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
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Background & Documents Tab -->
                        <TabsContent value="background" class="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <FileText class="w-5 h-5 text-primary" /> Educational Background & Documents
                                    </CardTitle>
                                    <CardDescription>JAMB details, previous studies, and document uploads (Optional).</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-8">
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <Label for="jamb_num">JAMB Registration Number</Label>
                                            <Input id="jamb_num" v-model="form.jamb_registration_number" placeholder="e.g. 2024123456AB" class="uppercase" />
                                            <p v-if="form.errors.jamb_registration_number" class="text-xs text-destructive">{{ form.errors.jamb_registration_number }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="jamb_score">JAMB Score</Label>
                                            <Input id="jamb_score" type="number" v-model="form.jamb_score" placeholder="e.g. 280" />
                                            <p v-if="form.errors.jamb_score" class="text-xs text-destructive">{{ form.errors.jamb_score }}</p>
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <Label for="prev_inst">Previous Institution (if any)</Label>
                                            <div class="relative">
                                                <Briefcase class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                                <Input id="prev_inst" v-model="form.previous_institution" class="pl-10" placeholder="For Direct Entry / Transfer students" />
                                            </div>
                                            <p v-if="form.errors.previous_institution" class="text-xs text-destructive">{{ form.errors.previous_institution }}</p>
                                        </div>
                                    </div>

                                    <Separator />

                                    <div class="grid md:grid-cols-2 gap-8 mt-4">
                                        <FileUploader 
                                            label="Passport Photograph" 
                                            accept="image/*"
                                            @update:file="(file) => form.passport_photo = file"
                                        />
                                        <FileUploader 
                                            label="O'Level Result (WAEC/NECO/NABTEB)" 
                                            accept=".pdf,image/*"
                                            @update:file="(file) => form.waec_result = file"
                                        />
                                    </div>
                                    <div class="grid md:grid-cols-2 gap-8 text-xs text-muted-foreground -mt-4 px-1">
                                         <p v-if="form.errors.passport_photo" class="text-destructive">{{ form.errors.passport_photo }}</p>
                                         <p v-if="form.errors.waec_result" class="text-destructive">{{ form.errors.waec_result }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Next of Kin Tab -->
                        <TabsContent value="nok" class="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Users class="w-5 h-5 text-primary" /> Next of Kin (Emergency Contact)
                                    </CardTitle>
                                    <CardDescription>Primary contact person for the student (Optional).</CardDescription>
                                </CardHeader>
                                <CardContent class="grid md:grid-cols-2 gap-x-8 gap-y-6">
                                    <div class="space-y-2">
                                        <Label for="nok_name">Full Name</Label>
                                        <Input id="nok_name" v-model="form.next_of_kin_name" placeholder="John Doe Sr." />
                                        <p v-if="form.errors.next_of_kin_name" class="text-xs text-destructive">{{ form.errors.next_of_kin_name }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="nok_phone">Phone Number</Label>
                                        <Input id="nok_phone" v-model="form.next_of_kin_phone" placeholder="+234..." />
                                        <p v-if="form.errors.next_of_kin_phone" class="text-xs text-destructive">{{ form.errors.next_of_kin_phone }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="nok_rel">Relationship</Label>
                                        <Select v-model="form.next_of_kin_relationship">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Relationship" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="Father">Father</SelectItem>
                                                <SelectItem value="Mother">Mother</SelectItem>
                                                <SelectItem value="Sibling">Sibling</SelectItem>
                                                <SelectItem value="Spouse">Spouse</SelectItem>
                                                <SelectItem value="Guardian">Guardian</SelectItem>
                                                <SelectItem value="Other">Other</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.next_of_kin_relationship" class="text-xs text-destructive">{{ form.errors.next_of_kin_relationship }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Account Security Tab -->
                        <TabsContent value="account" class="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <ShieldCheck class="w-5 h-5 text-primary" /> Account Security
                                    </CardTitle>
                                    <CardDescription>Configure initial login credentials for the student portal.</CardDescription>
                                </CardHeader>
                                <CardContent class="max-w-md space-y-4">
                                    <div class="space-y-2">
                                        <Label for="password">Initial Password (Optional)</Label>
                                        <Input id="password" type="password" v-model="form.password" placeholder="Leave blank for default" />
                                        <p class="text-[11px] text-muted-foreground bg-muted/50 p-2 rounded border border-dashed">
                                            If left blank, the student's default password will be <strong>'password'</strong>. They will be prompted to change it upon first login.
                                        </p>
                                        <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </div>
                </Tabs>

                <div class="flex justify-between items-center bg-card border rounded-xl p-6 mt-10 shadow-sm border-t-2 border-t-primary">
                    <div class="hidden md:block">
                        <p class="text-sm font-medium">Verify all information before creating.</p>
                        <p class="text-xs text-muted-foreground">The student will receive an onboarding email upon successful creation.</p>
                    </div>
                    <div class="flex gap-4 w-full md:w-auto">
                        <Button type="button" variant="outline" @click="activeTab = 'personal'" class="flex-1 md:flex-none" v-if="activeTab !== 'personal'">
                            Back to Start
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="flex-1 md:flex-none px-8 shadow-lg">
                            <Save class="w-4 h-4 mr-2" />
                            {{ form.processing ? 'Processing...' : 'Save & Create Student' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Smooth transition for tabs */
.tabs-content-enter-active,
.tabs-content-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.tabs-content-enter-from,
.tabs-content-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
