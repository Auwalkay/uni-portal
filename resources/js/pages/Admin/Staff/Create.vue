<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
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
    Heart,
    Sparkles,
    Check,
    Banknote,
    Layers,
    UserPlus
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
    { title: 'Staff Directory', href: '/admin/staff' },
    { title: 'New Staff Onboarding', href: '/admin/staff/create' },
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
    role_ids: [] as string[],
    date_joined: new Date().toISOString().split('T')[0],
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
    basic_salary: '',
    allowances: '',
    deductions: '',
    bonuses: '',
    bank_name: '',
    account_number: '',
    account_name: '',
});

const activeTab = ref('account');
const page = usePage();

const hasPermission = (permission: string) => {
    const user = (page.props.auth?.user as any);
    if (!user || ((!user.permissions || !Array.isArray(user.permissions)) && !user.roles)) return false;
    if (user.roles?.includes('admin')) return true;
    return (user.permissions && Array.isArray(user.permissions) && user.permissions.includes(permission));
};

const selectedFacultyId = ref<string>('');

const availableLgas = computed(() => {
    if (!form.state_id) return [];
    const state = props.states.find(s => String(s.id) === String(form.state_id));
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

const formatCurrency = (value: number | string) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN'
    }).format(Number(value || 0));
};

const computedNetSalary = computed(() => {
    const basic = Number(form.basic_salary || 0);
    const allowances = Number(form.allowances || 0);
    const bonuses = Number(form.bonuses || 0);
    const deductions = Number(form.deductions || 0);
    return basic + allowances + bonuses - deductions;
});

const toggleRole = (roleId: string) => {
    if (form.role_ids.includes(roleId)) {
        form.role_ids = form.role_ids.filter(id => id !== roleId);
    } else {
        form.role_ids.push(roleId);
    }
};

const submit = () => {
    form.post(route('admin.staff.store')); 
};
</script>

<template>
    <Head title="Staff Onboarding" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-8 space-y-8 max-w-7xl mx-auto pb-24">
            
            <!-- Sleek Top Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-card border rounded-2xl p-6 shadow-xs relative overflow-hidden">
                <div class="flex items-center gap-4 relative z-10">
                    <div class="h-12 w-12 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shrink-0 shadow-xs">
                        <UserPlus class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight text-foreground">Staff Onboarding</h1>
                            <Badge variant="outline" class="bg-primary/5 text-primary border-primary/20 text-[10px] font-bold uppercase tracking-wider">
                                System Portal
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground mt-0.5">Register staff credentials, set department placement, and assign system access rights.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 relative z-10">
                    <Button variant="outline" size="sm" as-child class="h-10 px-4 gap-2 font-semibold">
                        <Link :href="route('admin.staff.index')">
                            <ArrowLeft class="w-4 h-4" /> Back to Directory
                        </Link>
                    </Button>
                    <Button 
                        @click="submit"
                        class="h-10 px-5 gap-2 font-bold shadow-md hover:shadow-lg transition-all"
                        :disabled="form.processing"
                    >
                        <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <CheckCircle2 v-else class="w-4 h-4" />
                        <span>Save & Complete Onboarding</span>
                    </Button>
                </div>
            </div>

            <!-- Form & Smart Layout -->
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Main Content Area (8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    
                    <!-- Smart Section Tabs Navigation -->
                    <Tabs v-model="activeTab" class="w-full">
                        <TabsList class="grid grid-cols-3 sm:grid-cols-4 w-full h-12 p-1.5 bg-muted/60 rounded-xl border">
                            <TabsTrigger value="account" class="rounded-lg font-bold text-xs gap-1.5 data-[state=active]:shadow-xs">
                                <User class="w-3.5 h-3.5" /> Identity & Track
                            </TabsTrigger>
                            <TabsTrigger value="personal" class="rounded-lg font-bold text-xs gap-1.5 data-[state=active]:shadow-xs">
                                <Heart class="w-3.5 h-3.5" /> Personal Profile
                            </TabsTrigger>
                            <TabsTrigger value="placement" class="rounded-lg font-bold text-xs gap-1.5 data-[state=active]:shadow-xs">
                                <Building2 class="w-3.5 h-3.5" /> Placement
                            </TabsTrigger>
                            <TabsTrigger 
                                v-if="hasPermission('view_salaries') || hasPermission('manage_salaries')" 
                                value="salary" 
                                class="rounded-lg font-bold text-xs gap-1.5 data-[state=active]:shadow-xs"
                            >
                                <Briefcase class="w-3.5 h-3.5" /> Payroll & Bank
                            </TabsTrigger>
                        </TabsList>

                        <!-- Tab 1: Identity & Credentials -->
                        <TabsContent value="account" class="mt-6 space-y-6">
                            <Card class="border shadow-xs rounded-2xl overflow-hidden">
                                <CardHeader class="border-b bg-muted/30 py-4 px-6">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <CardTitle class="text-base font-bold flex items-center gap-2">
                                                <User class="w-4 h-4 text-primary" /> Identity & Credentials
                                            </CardTitle>
                                            <CardDescription class="text-xs">Primary account identity and login credentials.</CardDescription>
                                        </div>
                                        <Badge variant="secondary" class="text-[10px] uppercase font-bold">Required Step</Badge>
                                    </div>
                                </CardHeader>
                                <CardContent class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        
                                        <!-- Full Name -->
                                        <div class="space-y-2">
                                            <Label for="name" class="text-xs font-bold uppercase text-muted-foreground">Full Name <span class="text-rose-500">*</span></Label>
                                            <div class="relative">
                                                <User class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input id="name" v-model="form.name" required class="pl-10 h-10 font-medium" placeholder="e.g. Dr. Sopoline Payne" />
                                            </div>
                                            <p v-if="form.errors.name" class="text-xs text-rose-500 font-medium">{{ form.errors.name }}</p>
                                        </div>

                                        <!-- Email Address -->
                                        <div class="space-y-2">
                                            <Label for="email" class="text-xs font-bold uppercase text-muted-foreground">Email Address <span class="text-rose-500">*</span></Label>
                                            <div class="relative">
                                                <Mail class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input id="email" type="email" v-model="form.email" required class="pl-10 h-10 font-medium" placeholder="sopoline.payne@university.edu" />
                                            </div>
                                            <p v-if="form.errors.email" class="text-xs text-rose-500 font-medium">{{ form.errors.email }}</p>
                                        </div>

                                        <!-- Password -->
                                        <div class="space-y-2">
                                            <Label for="password" class="text-xs font-bold uppercase text-muted-foreground">Initial Password <span class="text-rose-500">*</span></Label>
                                            <div class="relative">
                                                <Lock class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input id="password" type="password" v-model="form.password" required class="pl-10 h-10" placeholder="••••••••" />
                                            </div>
                                            <p class="text-[11px] text-muted-foreground">Staff can change this after first sign-in.</p>
                                            <p v-if="form.errors.password" class="text-xs text-rose-500 font-medium">{{ form.errors.password }}</p>
                                        </div>

                                        <!-- Staff ID -->
                                        <div class="space-y-2">
                                            <Label for="staff_number" class="text-xs font-bold uppercase text-muted-foreground">Staff ID Number</Label>
                                            <div class="relative">
                                                <BadgeCheck class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input id="staff_number" v-model="form.staff_number" class="pl-10 h-10 font-mono" placeholder="e.g. STF-2026001 (Optional)" />
                                            </div>
                                            <p class="text-[11px] text-muted-foreground">Leave empty to auto-generate (e.g. STF-20264821).</p>
                                        </div>

                                        <!-- Professional Designation -->
                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Professional Designation</Label>
                                            <Select v-model="form.designation">
                                                <SelectTrigger class="h-10">
                                                    <SelectValue placeholder="Select Designation Title" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="designation in designations" :key="designation" :value="designation">
                                                        {{ designation }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <!-- Date Joined -->
                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Date Joined</Label>
                                            <div class="relative">
                                                <Calendar class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input type="date" v-model="form.date_joined" class="pl-10 h-10" />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Tab 2: Personal Profile -->
                        <TabsContent value="personal" class="mt-6 space-y-6">
                            <Card class="border shadow-xs rounded-2xl overflow-hidden">
                                <CardHeader class="border-b bg-muted/30 py-4 px-6">
                                    <CardTitle class="text-base font-bold flex items-center gap-2">
                                        <Heart class="w-4 h-4 text-primary" /> Personal Profile & Background
                                    </CardTitle>
                                    <CardDescription class="text-xs">Demographic details and emergency contact records.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        
                                        <!-- Gender -->
                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Gender</Label>
                                            <Select v-model="form.gender">
                                                <SelectTrigger class="h-10">
                                                    <SelectValue placeholder="Select Gender" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="male">Male</SelectItem>
                                                    <SelectItem value="female">Female</SelectItem>
                                                    <SelectItem value="other">Other</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <!-- Marital Status -->
                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Marital Status</Label>
                                            <Select v-model="form.marital_status">
                                                <SelectTrigger class="h-10">
                                                    <SelectValue placeholder="Select Marital Status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="single">Single</SelectItem>
                                                    <SelectItem value="married">Married</SelectItem>
                                                    <SelectItem value="divorced">Divorced</SelectItem>
                                                    <SelectItem value="widowed">Widowed</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <!-- DOB -->
                                        <div class="space-y-2">
                                            <Label for="date_of_birth" class="text-xs font-bold uppercase text-muted-foreground">Date of Birth</Label>
                                            <div class="relative">
                                                <Calendar class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input id="date_of_birth" type="date" v-model="form.date_of_birth" class="pl-10 h-10" />
                                            </div>
                                        </div>

                                        <!-- Phone Number -->
                                        <div class="space-y-2">
                                            <Label for="phone_number" class="text-xs font-bold uppercase text-muted-foreground">Phone Number</Label>
                                            <div class="relative">
                                                <Phone class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input id="phone_number" v-model="form.phone_number" class="pl-10 h-10" placeholder="+234 800 000 0000" />
                                            </div>
                                        </div>

                                        <!-- Nationality -->
                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Nationality</Label>
                                            <div class="relative">
                                                <Globe class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input v-model="form.nationality" class="pl-10 h-10" placeholder="e.g. Nigerian" />
                                            </div>
                                        </div>

                                        <!-- State & LGA -->
                                        <div class="space-y-2">
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <Label class="text-xs font-bold uppercase text-muted-foreground">State of Origin</Label>
                                                    <Select v-model="form.state_id">
                                                        <SelectTrigger class="h-10 mt-1">
                                                            <SelectValue placeholder="State" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="state in states" :key="state.id" :value="String(state.id)">
                                                                {{ state.name }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div>
                                                    <Label class="text-xs font-bold uppercase text-muted-foreground">LGA</Label>
                                                    <Select v-model="form.lga_id" :disabled="!form.state_id">
                                                        <SelectTrigger class="h-10 mt-1">
                                                            <SelectValue placeholder="LGA" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="lga in availableLgas" :key="lga.id" :value="String(lga.id)">
                                                                {{ lga.name }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Address -->
                                        <div class="space-y-2 md:col-span-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Residential Address</Label>
                                            <div class="relative">
                                                <MapPin class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Textarea v-model="form.address" class="pl-10 min-h-[80px]" placeholder="Full residential address..." />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Tab 3: Placement & Academic Track -->
                        <TabsContent value="placement" class="mt-6 space-y-6">
                            <Card class="border shadow-xs rounded-2xl overflow-hidden">
                                <CardHeader class="border-b bg-muted/30 py-4 px-6">
                                    <CardTitle class="text-base font-bold flex items-center gap-2">
                                        <Building2 class="w-4 h-4 text-primary" /> Placement & Faculty Track
                                    </CardTitle>
                                    <CardDescription class="text-xs">Configure academic standing, faculty department, and specialization.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-6 space-y-6">
                                    
                                    <!-- Academic Switch -->
                                    <div class="p-4 rounded-xl bg-primary/5 border border-primary/20 flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <Label for="is_academic" class="text-sm font-bold text-foreground">Academic Teaching & Research Staff</Label>
                                            <p class="text-xs text-muted-foreground">Enable for faculty, professors, and teaching staff.</p>
                                        </div>
                                        <Switch 
                                            id="is_academic" 
                                            v-model:checked="form.is_academic" 
                                            class="data-[state=checked]:bg-primary"
                                        />
                                    </div>

                                    <!-- Highest Qualification -->
                                    <div class="space-y-2">
                                        <Label class="text-xs font-bold uppercase text-muted-foreground">Highest Qualification</Label>
                                        <div class="relative">
                                            <GraduationCap class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                            <Input v-model="form.highest_qualification" class="pl-10 h-10" placeholder="e.g. PhD in Computer Science" />
                                        </div>
                                    </div>

                                    <!-- Placement Selection -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                                        <div v-if="form.is_academic" class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Faculty <span class="text-rose-500">*</span></Label>
                                            <Select v-model="selectedFacultyId">
                                                <SelectTrigger class="h-10">
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
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Department <span class="text-rose-500">*</span></Label>
                                            <Select v-model="form.department_id" :disabled="form.is_academic && !selectedFacultyId">
                                                <SelectTrigger class="h-10">
                                                    <SelectValue placeholder="Select Department" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="dept in availableDepartments" :key="dept.id" :value="dept.id">
                                                        {{ dept.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2 md:col-span-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Unit / Sub-department (Optional)</Label>
                                            <Select v-model="form.unit_id" :disabled="!form.department_id">
                                                <SelectTrigger class="h-10">
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

                                    <!-- Specialization (Academic Only) -->
                                    <div v-if="form.is_academic" class="space-y-6 pt-4 border-t">
                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Area of Specialization</Label>
                                            <div class="relative">
                                                <BookOpen class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input v-model="form.specialization" class="pl-10 h-10" placeholder="e.g. Artificial Intelligence & Neural Networks" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Research Interests</Label>
                                            <div class="relative">
                                                <ClipboardList class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Textarea v-model="form.research_interests" class="pl-10 min-h-[90px]" placeholder="List key research areas..." />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Tab 4: Salary & Bank -->
                        <TabsContent v-if="hasPermission('view_salaries') || hasPermission('manage_salaries')" value="salary" class="mt-6 space-y-6">
                            <Card class="border shadow-xs rounded-2xl overflow-hidden">
                                <CardHeader class="border-b bg-muted/30 py-4 px-6">
                                    <CardTitle class="text-base font-bold flex items-center gap-2">
                                        <Briefcase class="w-4 h-4 text-primary" /> Salary Configuration
                                    </CardTitle>
                                    <CardDescription class="text-xs">Setup monthly compensation structure.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Basic Salary (NGN)</Label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs font-bold">₦</span>
                                                <Input type="number" step="0.01" v-model="form.basic_salary" placeholder="0.00" class="pl-8 h-10" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Allowances (NGN)</Label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs font-bold">₦</span>
                                                <Input type="number" step="0.01" v-model="form.allowances" placeholder="0.00" class="pl-8 h-10" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Deductions (NGN)</Label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs font-bold">₦</span>
                                                <Input type="number" step="0.01" v-model="form.deductions" placeholder="0.00" class="pl-8 h-10" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Bonuses (NGN)</Label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-xs font-bold">₦</span>
                                                <Input type="number" step="0.01" v-model="form.bonuses" placeholder="0.00" class="pl-8 h-10" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Net Salary Banner -->
                                    <div class="p-4 rounded-xl bg-slate-900 text-white flex justify-between items-center shadow-sm">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Estimated Monthly Take-Home</span>
                                            <span class="text-2xl font-black text-emerald-400 block">{{ formatCurrency(computedNetSalary) }}</span>
                                        </div>
                                        <div class="text-right text-[10px] text-slate-400 font-medium">
                                            Basic + Allowances + Bonuses - Deductions
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card class="border shadow-xs rounded-2xl overflow-hidden">
                                <CardHeader class="border-b bg-muted/30 py-4 px-6">
                                    <CardTitle class="text-base font-bold flex items-center gap-2">
                                        <Banknote class="w-4 h-4 text-primary" /> Bank Account Details
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Bank Name</Label>
                                            <Input v-model="form.bank_name" placeholder="e.g. Zenith Bank" class="h-10" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Account Number</Label>
                                            <Input v-model="form.account_number" placeholder="10-digit NUBAN number" class="h-10 font-mono" />
                                        </div>

                                        <div class="space-y-2 md:col-span-2">
                                            <Label class="text-xs font-bold uppercase text-muted-foreground">Account Name</Label>
                                            <Input v-model="form.account_name" placeholder="Name as registered on bank account" class="h-10" />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Right Sidebar: System Roles & Submission (4 Cols) -->
                <div class="lg:col-span-4 space-y-6">
                    
                    <!-- Roles & Permissions Card -->
                    <Card class="border shadow-xs rounded-2xl overflow-hidden sticky top-6">
                        <CardHeader class="border-b bg-primary/5 py-4 px-6">
                            <div class="flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <CardTitle class="text-base font-bold flex items-center gap-2 text-foreground">
                                        <Shield class="w-4 h-4 text-primary" /> System Access Roles
                                    </CardTitle>
                                    <CardDescription class="text-xs">Grant module permissions to staff.</CardDescription>
                                </div>
                                <Badge variant="outline" class="bg-primary/10 text-primary border-primary/20 text-[10px] font-bold">
                                    {{ form.role_ids.length }} Selected
                                </Badge>
                            </div>
                        </CardHeader>
                        
                        <CardContent class="p-6 space-y-6">
                            
                            <!-- Role Selection Badges / Cards -->
                            <div class="space-y-2.5 max-h-[320px] overflow-y-auto pr-1">
                                <div 
                                    v-for="role in roles" 
                                    :key="role.id" 
                                    @click="toggleRole(role.id)"
                                    :class="[
                                        'flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer select-none',
                                        form.role_ids.includes(role.id) 
                                            ? 'bg-primary/10 border-primary/40 text-primary shadow-xs font-bold' 
                                            : 'bg-card border-border hover:border-primary/20 hover:bg-muted/40 text-muted-foreground font-medium'
                                    ]"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <Shield class="w-4 h-4 shrink-0" :class="form.role_ids.includes(role.id) ? 'text-primary' : 'text-muted-foreground'" />
                                        <span class="text-xs">{{ formatRoleName(role.name) }}</span>
                                    </div>
                                    <div 
                                        :class="[
                                            'h-5 w-5 rounded-md flex items-center justify-center border transition-colors',
                                            form.role_ids.includes(role.id) ? 'bg-primary border-primary text-primary-foreground' : 'border-input bg-background'
                                        ]"
                                    >
                                        <Check v-if="form.role_ids.includes(role.id)" class="w-3.5 h-3.5 stroke-[3]" />
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.role_ids" class="text-xs text-rose-500 font-medium">{{ form.errors.role_ids }}</p>

                            <!-- Finalize Button -->
                            <div class="pt-4 border-t space-y-3">
                                <Button 
                                    type="submit" 
                                    class="w-full h-12 text-sm font-bold gap-2 shadow-md hover:shadow-lg transition-all rounded-xl"
                                    :disabled="form.processing"
                                >
                                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                                    <CheckCircle2 v-else class="w-4 h-4" />
                                    <span>Complete Staff Onboarding</span>
                                </Button>

                                <div class="p-3.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/60 flex items-start gap-2.5">
                                    <CheckCircle2 class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                                    <p class="text-[11px] text-blue-800 dark:text-blue-300 font-medium leading-relaxed">
                                        An automated email notification will be dispatched with sign-in instructions once finalized.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

