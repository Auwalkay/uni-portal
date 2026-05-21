<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { 
    User, Mail, Briefcase, GraduationCap, Shield, ShieldCheck, 
    Building2, MapPin, AtSign, KeyRound, Info, CreditCard, Search,
    Calendar, Phone, Globe, BookOpen, ClipboardList, Heart, ArrowLeft, Loader2, CheckCircle2
} from 'lucide-vue-next';
import { Textarea } from '@/components/ui/textarea';
import { route } from 'ziggy-js'; 
import { ref, watch, computed, onMounted } from 'vue';

const props = defineProps<{
    staff: {
        id: string;
        name: string;
        email: string;
        staff: {
            staff_number: string;
            designation: string;
            department_id: string;
            unit_id: string;
            is_academic: boolean;
            date_joined: string | null;
            highest_qualification: string | null;
            phone_number: string | null;
            gender: string | null;
            date_of_birth: string | null;
            marital_status: string | null;
            address: string | null;
            nationality: string | null;
            state_id: number | null;
            lga_id: number | null;
            specialization: string | null;
            research_interests: string | null;
            department?: {
                id: string;
                faculty_id: string;
            };
            unit?: {
                id: string;
                name: string;
            };
        } | null;
    };
    faculties: Array<{
        id: string;
        name: string;
        departments: Array<{
            id: string;
            name: string;
            units: Array<{ id: string; name: string }>;
        }>;
    }>;
    nonAcademicDepartments: Array<any>;
    designations: Array<string>;
    roles: Array<{ id: string; name: string }>;
    current_role_id: string | null;
    states: Array<{ id: number; name: string; lgas: Array<{ id: number; name: string }> }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Staff Management',
        href: '/admin/staff',
    },
    {
        title: 'Edit Staff',
        href: `/admin/staff/${props.staff.id}/edit`,
    },
];

const form = useForm({
    name: props.staff.name,
    email: props.staff.email,
    password: '',
    staff_number: props.staff.staff?.staff_number || '',
    designation: props.staff.staff?.designation || '',
    department_id: props.staff.staff?.department_id || '',
    unit_id: props.staff.staff?.unit_id || '',
    is_academic: props.staff.staff?.is_academic ?? false,
    role_id: props.current_role_id ? String(props.current_role_id) : '',
    date_joined: props.staff.staff?.date_joined || '',
    highest_qualification: props.staff.staff?.highest_qualification || '',
    phone_number: props.staff.staff?.phone_number || '',
    gender: props.staff.staff?.gender || '',
    date_of_birth: props.staff.staff?.date_of_birth || '',
    marital_status: props.staff.staff?.marital_status || '',
    address: props.staff.staff?.address || '',
    nationality: props.staff.staff?.nationality || '',
    state_id: props.staff.staff?.state_id || '',
    lga_id: props.staff.staff?.lga_id || '',
    specialization: props.staff.staff?.specialization || '',
    research_interests: props.staff.staff?.research_interests || '',
});

const selectedFacultyId = ref<string>('');

// State & LGA Logic
const availableLgas = computed(() => {
    if (!form.state_id) return [];
    const state = props.states.find(s => s.id === form.state_id);
    return state ? state.lgas : [];
});

watch(() => form.state_id, () => {
    form.lga_id = '';
});

// Initialize faculty based on department
onMounted(() => {
    if (props.staff.staff?.department_id) {
        if (props.staff.staff.is_academic) {
            // Find faculty containing this department
            for (const faculty of props.faculties) {
                const hasDept = faculty.departments.some(d => d.id === props.staff.staff?.department_id);
                if (hasDept) {
                    selectedFacultyId.value = faculty.id;
                    break;
                }
            }
        }
    }
});

const availableDepartments = computed(() => {
    if (form.is_academic) {
        if (!selectedFacultyId.value) return [];
        const faculty = props.faculties.find(f => f.id === selectedFacultyId.value);
        return faculty ? faculty.departments : [];
    } else {
        return props.nonAcademicDepartments;
    }
});

const availableUnits = computed(() => {
    if (!form.department_id) return [];
    let dept: any;
    if (form.is_academic) {
        const faculty = props.faculties.find(f => f.id === selectedFacultyId.value);
        dept = faculty?.departments.find(d => d.id === form.department_id);
    } else {
        dept = props.nonAcademicDepartments.find(d => d.id === form.department_id);
    }
    return dept ? dept.units : [];
});

watch(selectedFacultyId, (newVal, oldVal) => {
    // Only clear department if interaction happened (not initial setup)
    if (oldVal !== '') {
        form.department_id = '';
    }
});

watch(() => form.is_academic, () => {
    form.department_id = '';
    selectedFacultyId.value = '';
});

watch(() => form.department_id, () => {
    form.unit_id = '';
});

const submit = () => {
    form.put(route('admin.staff.update', props.staff.id));
};

const searchQuery = ref('');

const filteredRoles = computed(() => {
    if (!searchQuery.value) return props.roles;
    return props.roles.filter(role => 
        role.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const getRoleMetadata = (name: string) => {
    const meta: Record<string, { icon: any, color: string, desc: string }> = {
        admin: { icon: Shield, color: 'text-red-600 bg-red-50', desc: 'Full system access & configuration.' },
        registrar: { icon: GraduationCap, color: 'text-indigo-600 bg-indigo-50', desc: 'Primary academic record authority.' },
        dean: { icon: Building2, color: 'text-blue-600 bg-blue-50', desc: 'Faculty level oversight & approvals.' },
        hod: { icon: Building2, color: 'text-sky-600 bg-sky-50', desc: 'Departmental leadership & results.' },
        lecturer: { icon: GraduationCap, color: 'text-emerald-600 bg-emerald-50', desc: 'Course management & grading.' },
        bursar: { icon: CreditCard, color: 'text-amber-600 bg-amber-50', desc: 'Chief financial controller.' },
        finance_officer: { icon: CreditCard, color: 'text-orange-600 bg-orange-50', desc: 'Payroll & fee verification.' },
        receptionist: { icon: User, color: 'text-teal-600 bg-teal-50', desc: 'Front desk & visitor management.' },
        staff: { icon: User, color: 'text-slate-600 bg-slate-50', desc: 'Standard internal staff access.' }
    };
    
    return meta[name.toLowerCase()] || { icon: Shield, color: 'text-slate-600 bg-slate-50', desc: 'Specialized unit permissions.' };
};

const confirmDelete = (e: Event) => {
    if (!confirm('Are you absolutely sure you want to terminate this staff account? This action cannot be undone and will revoke all access immediately.')) {
        e.preventDefault();
    }
};
</script>

<template>
    <Head title="Edit Staff" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-8 space-y-8 pb-20">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-xl border shadow-sm">
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <User class="w-6 h-6 text-primary" />
                        </div>
                        Edit Staff Member
                    </h1>
                    <p class="text-muted-foreground text-sm">Update profile and access permissions for {{ staff.name }}.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <Button variant="outline" size="sm" as-child class="h-9 gap-2">
                        <Link :href="route('admin.staff.index')">
                            <ArrowLeft class="w-4 h-4" /> Back to Directory
                        </Link>
                    </Button>
                    <Button @click="submit" :disabled="form.processing" size="sm" class="h-9">
                        <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                        <CheckCircle2 v-else class="w-4 h-4 mr-2" />
                        Save Changes
                    </Button>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Tabbed Information -->
                <div class="lg:col-span-2">
                    <Tabs default-value="account" class="w-full space-y-6">
                        <TabsList class="grid w-full grid-cols-3 h-12 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                            <TabsTrigger value="account" class="rounded-lg font-bold text-xs uppercase tracking-widest">
                                <User class="w-3.5 h-3.5 mr-2" /> Account
                            </TabsTrigger>
                            <TabsTrigger value="personal" class="rounded-lg font-bold text-xs uppercase tracking-widest">
                                <Heart class="w-3.5 h-3.5 mr-2" /> Personal
                            </TabsTrigger>
                            <TabsTrigger value="placement" class="rounded-lg font-bold text-xs uppercase tracking-widest">
                                <Building2 class="w-3.5 h-3.5 mr-2" /> Placement
                            </TabsTrigger>
                        </TabsList>

                        <!-- Account & Employment Tab -->
                        <TabsContent value="account" class="space-y-6 animate-in fade-in-50 duration-300">
                            <Card class="border shadow-sm overflow-hidden">
                                <CardHeader class="bg-slate-50 dark:bg-slate-900/50 border-b py-4">
                                    <CardTitle class="text-base flex items-center gap-2">
                                        <User class="w-4 h-4 text-primary" />
                                        Identity & Employment
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <Label for="name" class="text-sm font-semibold">Full Name</Label>
                                            <Input id="name" v-model="form.name" required />
                                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="email" class="text-sm font-semibold">Email Address</Label>
                                            <Input id="email" type="email" v-model="form.email" required />
                                            <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="staff_number" class="text-sm font-semibold">Staff ID Number</Label>
                                            <Input id="staff_number" v-model="form.staff_number" required />
                                            <p v-if="form.errors.staff_number" class="text-xs text-destructive">{{ form.errors.staff_number }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold">Designation</Label>
                                            <Select v-model="form.designation">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select Designation" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="designation in designations" :key="designation" :value="designation">
                                                        {{ designation }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold">Date Joined</Label>
                                            <div class="relative group">
                                                <Calendar class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                                <Input type="date" v-model="form.date_joined" class="pl-10" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold">Highest Qualification</Label>
                                            <div class="relative group">
                                                <GraduationCap class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                                <Input v-model="form.highest_qualification" class="pl-10" placeholder="e.g. PhD" />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Personal Profile Tab -->
                        <TabsContent value="personal" class="space-y-6 animate-in fade-in-50 duration-300">
                            <Card class="border shadow-sm overflow-hidden">
                                <CardHeader class="bg-slate-50 dark:bg-slate-900/50 border-b py-4">
                                    <CardTitle class="text-base flex items-center gap-2">
                                        <Heart class="w-4 h-4 text-primary" />
                                        Personal Profile
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold">Gender</Label>
                                            <Select v-model="form.gender">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select Gender" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="male">Male</SelectItem>
                                                    <SelectItem value="female">Female</SelectItem>
                                                    <SelectItem value="other">Other</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold">Marital Status</Label>
                                            <Select v-model="form.marital_status">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select Status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="single">Single</SelectItem>
                                                    <SelectItem value="married">Married</SelectItem>
                                                    <SelectItem value="divorced">Divorced</SelectItem>
                                                    <SelectItem value="widowed">Widowed</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="date_of_birth" class="text-sm font-semibold">Date of Birth</Label>
                                            <Input id="date_of_birth" type="date" v-model="form.date_of_birth" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="phone_number" class="text-sm font-semibold">Phone Number</Label>
                                            <div class="relative group">
                                                <Phone class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input id="phone_number" v-model="form.phone_number" class="pl-10" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold">Nationality</Label>
                                            <Input v-model="form.nationality" />
                                        </div>

                                        <div class="space-y-2">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-2">
                                                    <Label class="text-sm font-semibold">State of Origin</Label>
                                                    <Select v-model="form.state_id">
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select State" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="state in states" :key="state.id" :value="state.id">
                                                                {{ state.name }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div class="space-y-2">
                                                    <Label class="text-sm font-semibold">LGA</Label>
                                                    <Select v-model="form.lga_id" :disabled="!form.state_id">
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Select LGA" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="lga in availableLgas" :key="lga.id" :value="lga.id">
                                                                {{ lga.name }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-2 md:col-span-2">
                                            <Label class="text-sm font-semibold">Residential Address</Label>
                                            <Textarea v-model="form.address" class="min-h-[100px]" />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Placement & Academic Tab -->
                        <TabsContent value="placement" class="space-y-6 animate-in fade-in-50 duration-300">
                            <Card class="border shadow-sm overflow-hidden">
                                <CardHeader class="bg-slate-50 dark:bg-slate-900/50 border-b py-4">
                                    <CardTitle class="text-base flex items-center gap-2">
                                        <Building2 class="w-4 h-4 text-primary" />
                                        Institutional Placement
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="p-6 space-y-6">
                                    <div class="flex items-center justify-between p-4 bg-primary/5 rounded-lg border border-primary/10 mb-4">
                                        <div class="space-y-0.5">
                                            <Label for="is_academic" class="text-base font-semibold">Academic Position</Label>
                                            <p class="text-xs text-muted-foreground italic">Enable for teaching/research staff.</p>
                                        </div>
                                        <Switch id="is_academic" v-model:checked="form.is_academic" class="data-[state=checked]:bg-primary" />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div v-if="form.is_academic" class="space-y-2">
                                            <Label class="text-sm font-semibold">Faculty</Label>
                                            <Select v-model="selectedFacultyId">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select Faculty" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id">
                                                        {{ faculty.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold">Department</Label>
                                            <Select v-model="form.department_id" :disabled="form.is_academic && !selectedFacultyId">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select Department" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="dept in availableDepartments" :key="dept.id" :value="dept.id">
                                                        {{ dept.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold">Unit (Optional)</Label>
                                            <Select v-model="form.unit_id" :disabled="!form.department_id">
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select Unit" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="unit in availableUnits" :key="unit.id" :value="unit.id">
                                                        {{ unit.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <!-- Academic Specific Fields -->
                                    <div v-if="form.is_academic" class="space-y-6 pt-6 border-t animate-in slide-in-from-top-2 duration-300">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-bold text-primary flex items-center gap-2">
                                                <BookOpen class="w-4 h-4" /> Area of Specialization
                                            </Label>
                                            <Input v-model="form.specialization" placeholder="e.g. Theoretical Physics" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-sm font-bold text-primary flex items-center gap-2">
                                                <ClipboardList class="w-4 h-4" /> Research Interests
                                            </Label>
                                            <Textarea v-model="form.research_interests" placeholder="Primary research areas..." class="min-h-[100px]" />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Right Column: Access & Security -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Access Assignment Card -->
                    <Card class="border shadow-md border-primary/20 bg-primary/5">
                        <CardHeader>
                            <CardTitle class="text-base flex items-center gap-2">
                                <Shield class="w-4 h-4 text-primary" />
                                System Permissions
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="space-y-3">
                                <Label class="text-sm font-bold text-gray-700">Primary System Role</Label>
                                <Select v-model="form.role_id">
                                    <SelectTrigger class="bg-white border-primary/20 h-11">
                                        <SelectValue placeholder="Select access level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="role in roles" :key="role.id" :value="String(role.id)" class="py-2">
                                            {{ role.name.replace('_', ' ').toUpperCase() }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.role_id" class="text-xs text-destructive font-medium">{{ form.errors.role_id }}</p>
                            </div>

                            <div class="pt-4 border-t border-primary/10">
                                <Button @click="submit" :disabled="form.processing" class="w-full h-11 font-bold gap-2 shadow-lg">
                                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                                    <CheckCircle2 v-else class="w-4 h-4" />
                                    Update Permissions
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Security Management -->
                    <Card class="border shadow-sm">
                        <CardHeader class="py-4">
                            <CardTitle class="text-sm font-bold">Security Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2 p-4 pt-0">
                            <Button variant="outline" type="button" class="w-full justify-start gap-3 h-10 text-xs" as-child>
                                <Link :href="route('admin.staff.reset_password', staff.id)" method="post" as="button">
                                    <KeyRound class="w-3.5 h-3.5 text-amber-500" />
                                    Reset Password
                                </Link>
                            </Button>
                            
                            <Button variant="ghost" type="button" class="w-full justify-start gap-3 h-10 text-xs text-red-600 hover:text-red-700 hover:bg-red-50" @click="confirmDelete">
                                <User class="w-3.5 h-3.5" />
                                Terminate Account
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Info Box -->
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 flex gap-3">
                        <div class="bg-blue-100 p-2 rounded-full h-fit flex-shrink-0">
                            <Info class="w-4 h-4 text-blue-600" />
                        </div>
                        <p class="text-[11px] text-blue-800 leading-relaxed">
                            Changes to system roles take effect immediately. The staff member may need to re-login to see new permissions.
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
