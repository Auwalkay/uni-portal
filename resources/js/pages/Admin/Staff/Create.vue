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
import { Textarea } from '@/components/ui/textarea';
import { 
    User, 
    Mail, 
    Lock, 
    BadgeCheck, 
    Building2, 
    Briefcase, 
    GraduationCap, 
    ArrowLeft,
    CheckCircle2,
    Loader2,
    Shield,
    Calendar,
    Phone,
    MapPin,
    Globe,
    BookOpen,
    ClipboardList,
    Heart
} from 'lucide-vue-next';
import { route } from 'ziggy-js'; 
import { ref, watch, computed } from 'vue';

const props = defineProps<{
    faculties: Array<{
        id: string;
        name: string;
        departments: Array<{
            id: string;
            name: string;
            units: Array<{
                id: string;
                name: string;
            }>;
        }>;
    }>;
    nonAcademicDepartments: Array<any>;
    designations: Array<string>;
    roles: Array<{ id: string; name: string }>;
    states: Array<{ id: number; name: string; lgas: Array<{ id: number; name: string }> }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Staff Management', href: '/admin/staff' },
    { title: 'Add Staff', href: '/admin/staff/create' },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    staff_number: '',
    designation: '',
    department_id: '',
    unit_id: '',
    is_academic: true,
    role_id: '',
    date_joined: '',
    highest_qualification: '',
    phone_number: '',
    gender: '',
    date_of_birth: '',
    marital_status: '',
    address: '',
    nationality: 'Nigerian',
    state_id: '',
    lga_id: '',
    specialization: '',
    research_interests: '',
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

watch(selectedFacultyId, () => {
    form.department_id = '';
});

watch(() => form.is_academic, () => {
    form.department_id = '';
    selectedFacultyId.value = '';
});

watch(() => form.department_id, () => {
    form.unit_id = '';
});

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const submit = () => {
    form.post(route('admin.staff.store')); 
};
</script>

<template>
    <Head title="Create Staff Account" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-8 space-y-8 pb-20">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border shadow-sm">
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 flex items-center gap-2">
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <GraduationCap class="w-6 h-6 text-primary" />
                        </div>
                        Staff Onboarding
                    </h1>
                    <p class="text-muted-foreground">Register a new staff member and assign system roles.</p>
                </div>
                
                <Button variant="outline" size="sm" as-child class="h-9 gap-2">
                    <Link :href="route('admin.staff.index')">
                        <ArrowLeft class="w-4 h-4" /> Back to Directory
                    </Link>
                </Button>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-20">
                <!-- Left Column: Tabbed Information -->
                <div class="lg:col-span-2">
                    <Tabs default-value="account" class="w-full space-y-6">
                        <TabsList class="grid w-full grid-cols-3 h-12 p-1 bg-slate-100 rounded-xl">
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
                                <CardHeader class="bg-slate-50 border-b py-4">
                                    <CardTitle class="text-base flex items-center gap-2">
                                        <User class="w-4 h-4 text-primary" />
                                        Identity & Credentials
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <Label for="name" class="text-sm font-semibold">Full Name</Label>
                                            <div class="relative group">
                                                <User class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                                <Input id="name" v-model="form.name" required class="pl-10" placeholder="e.g. Dr. Jane Smith" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="email" class="text-sm font-semibold">Email Address</Label>
                                            <div class="relative group">
                                                <Mail class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                                <Input id="email" type="email" v-model="form.email" required class="pl-10" placeholder="jane.smith@university.edu" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="password" class="text-sm font-semibold">Initial Password</Label>
                                            <div class="relative group">
                                                <Lock class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                                <Input id="password" type="password" v-model="form.password" required class="pl-10" />
                                            </div>
                                            <p class="text-[10px] text-muted-foreground italic">Staff can change this after first login.</p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="staff_number" class="text-sm font-semibold">Staff ID Number</Label>
                                            <div class="relative group">
                                                <BadgeCheck class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                                <Input id="staff_number" v-model="form.staff_number" required class="pl-10" placeholder="STF/2024/001" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold">Professional Designation</Label>
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
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Personal Details Tab -->
                        <TabsContent value="personal" class="space-y-6 animate-in fade-in-50 duration-300">
                            <Card class="border shadow-sm overflow-hidden">
                                <CardHeader class="bg-slate-50 border-b py-4">
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
                                            <div class="relative group">
                                                <Calendar class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                                <Input id="date_of_birth" type="date" v-model="form.date_of_birth" class="pl-10" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="phone_number" class="text-sm font-semibold">Phone Number</Label>
                                            <div class="relative group">
                                                <Phone class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                                <Input id="phone_number" v-model="form.phone_number" class="pl-10" placeholder="+234..." />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-sm font-semibold">Nationality</Label>
                                            <div class="relative group">
                                                <Globe class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                                <Input v-model="form.nationality" class="pl-10" placeholder="e.g. Nigerian" />
                                            </div>
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
                                            <div class="relative group">
                                                <MapPin class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                                <Textarea v-model="form.address" class="pl-10 min-h-[80px]" placeholder="Full residential address..." />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Placement & Academic Tab -->
                        <TabsContent value="placement" class="space-y-6 animate-in fade-in-50 duration-300">
                            <Card class="border shadow-sm overflow-hidden">
                                <CardHeader class="bg-slate-50 border-b py-4">
                                    <CardTitle class="text-base flex items-center gap-2">
                                        <Building2 class="w-4 h-4 text-primary" />
                                        Placement & Academic Track
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="p-6 space-y-8">
                                    <div class="space-y-2">
                                        <Label class="text-sm font-semibold">Highest Qualification</Label>
                                        <div class="relative group">
                                            <GraduationCap class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                            <Input v-model="form.highest_qualification" class="pl-10" placeholder="e.g. PhD in Computer Science" />
                                        </div>
                                    </div>

                                    <div class="space-y-4 p-4 bg-primary/5 rounded-lg border border-primary/10">
                                        <div class="flex items-center justify-between">
                                            <div class="space-y-0.5">
                                                <Label for="is_academic" class="text-base font-semibold">Academic Position</Label>
                                                <p class="text-xs text-muted-foreground italic">Enable for faculty/teaching staff.</p>
                                            </div>
                                            <Switch 
                                                id="is_academic" 
                                                v-model:checked="form.is_academic" 
                                                class="data-[state=checked]:bg-primary"
                                            />
                                        </div>
                                    </div>

                                    <!-- Academic Specific Fields -->
                                    <div v-if="form.is_academic" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-in slide-in-from-top-2 duration-300">
                                        <div class="space-y-2 md:col-span-2">
                                            <Label class="text-sm font-semibold text-primary">Area of Specialization</Label>
                                            <div class="relative group">
                                                <BookOpen class="absolute left-3 top-3 w-4 h-4 text-primary" />
                                                <Input v-model="form.specialization" class="pl-10 border-primary/20 focus:border-primary" placeholder="e.g. Machine Learning" />
                                            </div>
                                        </div>

                                        <div class="space-y-2 md:col-span-2">
                                            <Label class="text-sm font-semibold text-primary">Research Interests</Label>
                                            <div class="relative group">
                                                <ClipboardList class="absolute left-3 top-3 w-4 h-4 text-primary" />
                                                <Textarea v-model="form.research_interests" class="pl-10 min-h-[100px] border-primary/20 focus:border-primary" placeholder="Research focus areas..." />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                                        <div v-if="form.is_academic" class="space-y-2">
                                            <Label class="text-sm font-semibold">Faculty</Label>
                                            <Select v-model="selectedFacultyId">
                                                <SelectTrigger class="bg-white">
                                                    <SelectValue placeholder="Assign Faculty" />
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
                                                <SelectTrigger class="bg-white">
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
                                                <SelectTrigger class="bg-white">
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
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Right Column: Roles & Action -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Role Assignment Card -->
                    <Card class="border shadow-md border-primary/20 bg-primary/5">
                        <CardHeader>
                            <CardTitle class="text-base flex items-center gap-2">
                                <Shield class="w-4 h-4 text-primary" />
                                System Permissions
                            </CardTitle>
                            <CardDescription>Grant specific access rights to different modules.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div class="space-y-3">
                                <Label class="text-sm font-bold text-gray-700">Primary System Role</Label>
                                <Select v-model="form.role_id">
                                    <SelectTrigger class="bg-white border-primary/20 h-11">
                                        <SelectValue placeholder="Select access level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="role in roles" :key="role.id" :value="role.id" class="py-2">
                                            {{ formatRoleName(role.name) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.role_id" class="text-xs text-destructive font-medium">{{ form.errors.role_id }}</p>
                            </div>

                            <div class="space-y-3 pt-4 border-t border-primary/10">
                                <Label class="text-sm font-bold text-gray-700">Designation</Label>
                                <Select v-model="form.designation">
                                    <SelectTrigger class="bg-white h-11">
                                        <SelectValue placeholder="Official Title" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="designation in designations" :key="designation" :value="designation">
                                            {{ designation }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.designation" class="text-xs text-destructive">{{ form.errors.designation }}</p>
                            </div>

                            <div class="pt-6">
                                <Button 
                                    type="submit" 
                                    class="w-full h-12 text-lg font-bold gap-2 shadow-lg hover:shadow-xl transition-all"
                                    :disabled="form.processing"
                                >
                                    <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                                    <CheckCircle2 v-else class="w-5 h-5" />
                                    Finalize Onboarding
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Info Box -->
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 flex gap-3">
                        <div class="bg-blue-100 p-2 rounded-full h-fit flex-shrink-0">
                            <CheckCircle2 class="w-4 h-4 text-blue-600" />
                        </div>
                        <p class="text-xs text-blue-800 leading-relaxed">
                            Once created, an email notification will be sent (if configured) allowing the staff member to complete their profile setup.
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

