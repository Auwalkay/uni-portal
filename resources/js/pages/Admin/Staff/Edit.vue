<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import { 
    User, Mail, Briefcase, GraduationCap, Shield, ShieldCheck, 
    Building2, MapPin, KeyRound, Info, CreditCard, Search,
    Calendar, Phone, BookOpen, ClipboardList, Heart, ArrowLeft, Loader2, CheckCircle2,
    Eye, Building, Hash, RefreshCw, AlertTriangle
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
    current_role_ids: Array<string>;
    states: Array<{ id: number; name: string; lgas: Array<{ id: number; name: string }> }>;
    canAssignRoles?: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Staff Management', href: '/admin/staff' },
    { title: 'Edit Staff Member', href: '#' },
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
    role_ids: props.current_role_ids ? props.current_role_ids.map(String) : [] as string[],
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

const availableLgas = computed(() => {
    if (!form.state_id) return [];
    const state = props.states.find(s => String(s.id) === String(form.state_id));
    return state ? state.lgas : [];
});

watch(() => form.state_id, () => {
    form.lga_id = '';
});

onMounted(() => {
    if (props.staff.staff?.department_id) {
        if (props.staff.staff.is_academic) {
            for (const faculty of props.faculties) {
                const hasDept = faculty.departments.some(d => String(d.id) === String(props.staff.staff?.department_id));
                if (hasDept) {
                    selectedFacultyId.value = String(faculty.id);
                    break;
                }
            }
        }
    }
});

const availableDepartments = computed(() => {
    if (form.is_academic) {
        if (!selectedFacultyId.value) return [];
        const faculty = props.faculties.find(f => String(f.id) === String(selectedFacultyId.value));
        return faculty ? faculty.departments : [];
    } else {
        return props.nonAcademicDepartments;
    }
});

const availableUnits = computed(() => {
    if (!form.department_id) return [];
    let dept: any;
    if (form.is_academic) {
        const faculty = props.faculties.find(f => String(f.id) === String(selectedFacultyId.value));
        dept = faculty?.departments.find(d => String(d.id) === String(form.department_id));
    } else {
        dept = props.nonAcademicDepartments.find(d => String(d.id) === String(form.department_id));
    }
    return dept ? dept.units : [];
});

watch(selectedFacultyId, (newVal, oldVal) => {
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

import Swal from 'sweetalert2';

const submit = () => {
    form.put(route('admin.staff.update', props.staff.id), {
        onSuccess: () => {
            Swal.fire({
                title: 'Updated!',
                text: 'Staff profile updated successfully.',
                icon: 'success',
                confirmButtonColor: '#4f46e5',
            });
        },
        onError: (errors) => {
            const errorList = Object.values(errors).map(err => `• ${err}`).join('<br>');
            Swal.fire({
                title: 'Update Failed',
                html: `<div class="text-left text-sm text-red-600 font-medium space-y-1">${errorList}</div>`,
                icon: 'error',
                confirmButtonColor: '#ef4444',
            });
        }
    });
};

const roleSearch = ref('');
const filteredRoles = computed(() => {
    if (!roleSearch.value) return props.roles;
    const q = roleSearch.value.toLowerCase();
    return props.roles.filter(role => role.name.toLowerCase().includes(q));
});

const formatRoleName = (name: string) => {
    return name.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
};

const resetPassword = () => {
    if (confirm('Are you sure you want to reset this staff member\'s password? A new random password will be emailed to them immediately.')) {
        router.post(route('admin.staff.reset_password', props.staff.id));
    }
};

const confirmDelete = (e: Event) => {
    if (!confirm('Are you absolutely sure you want to terminate this staff account? This action cannot be undone and will revoke all access immediately.')) {
        e.preventDefault();
    }
};
</script>

<template>
    <Head :title="`Edit ${staff.name} - Staff`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-8 px-6 space-y-8 w-full max-w-[1600px] mx-auto pb-20">
            
            <!-- Hero Header Banner -->
            <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl overflow-hidden shadow-xl text-white border border-slate-800">
                <div class="absolute inset-0 bg-grid-white/[0.05] bg-[length:24px_24px]"></div>
                
                <div class="relative p-6 md:p-8 flex flex-col md:flex-row items-center md:items-end justify-between gap-6">
                    <div class="flex flex-col md:flex-row items-center md:items-end gap-6 text-center md:text-left">
                        <Avatar class="h-24 w-24 border-4 border-white/20 shadow-2xl rounded-2xl ring-4 ring-black/20">
                            <AvatarFallback class="bg-indigo-600 text-white text-3xl font-extrabold rounded-2xl">
                                {{ staff.name.charAt(0) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                                <Badge variant="secondary" class="bg-indigo-500/20 text-indigo-200 border-indigo-500/30 font-bold px-2.5 py-0.5 text-xs">
                                    Edit Staff Profile
                                </Badge>
                                <Badge variant="outline" class="border-emerald-500/40 text-emerald-300 bg-emerald-500/10 px-2.5 py-0.5 text-xs">
                                    <CheckCircle2 class="w-3 h-3 mr-1" /> Active Record
                                </Badge>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">{{ staff.name }}</h1>
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 text-slate-300 text-xs font-medium">
                                <span class="flex items-center gap-1"><Briefcase class="w-3.5 h-3.5 text-indigo-400" /> {{ staff.staff?.designation || 'Staff Member' }}</span>
                                <span class="hidden md:inline text-slate-600">•</span>
                                <span class="flex items-center gap-1"><Mail class="w-3.5 h-3.5 text-indigo-400" /> {{ staff.email }}</span>
                                <span class="hidden md:inline text-slate-600">•</span>
                                <span class="flex items-center gap-1"><Hash class="w-3.5 h-3.5 text-indigo-400" /> {{ staff.staff?.staff_number || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <Button variant="outline" size="sm" as-child class="bg-white/10 hover:bg-white/20 text-white border-white/20 font-bold backdrop-blur-sm h-10 px-4">
                            <Link :href="route('admin.staff.index')">
                                <ArrowLeft class="w-4 h-4 mr-2" /> Directory
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" as-child class="bg-white/10 hover:bg-white/20 text-white border-white/20 font-bold backdrop-blur-sm h-10 px-4">
                            <Link :href="route('admin.staff.show', staff.id)">
                                <Eye class="w-4 h-4 mr-2" /> View Profile
                            </Link>
                        </Button>
                        <Button @click="submit" :disabled="form.processing" size="sm" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold h-10 px-5 shadow-lg shadow-indigo-600/30">
                            <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                            <CheckCircle2 v-else class="w-4 h-4 mr-2" />
                            Save Changes
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Validation Error Alert Banner -->
            <div v-if="form.hasErrors" class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 text-red-700 dark:text-red-300 p-4 rounded-2xl flex items-start gap-3 shadow-sm">
                <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" />
                <div class="space-y-1">
                    <h4 class="font-bold text-sm">Update Cannot Be Saved</h4>
                    <p class="text-xs text-red-600/90 dark:text-red-300/90">Please review and resolve the following errors:</p>
                    <ul class="list-disc list-inside text-xs space-y-0.5 font-medium pt-1">
                        <li v-for="(error, field) in form.errors" :key="field">
                            <strong class="capitalize">{{ String(field).replace('_', ' ') }}:</strong> {{ error }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Form Content Grid -->
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Main Form Tabs (2/3) -->
                <div class="lg:col-span-2">
                    <Tabs default-value="account" class="w-full space-y-6">
                        <TabsList class="w-full justify-start border-b rounded-none h-12 bg-transparent p-0 mb-6 gap-6">
                            <TabsTrigger value="account" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-12 px-1 text-sm flex items-center gap-2">
                                <User class="w-4 h-4" /> Identity & Account
                            </TabsTrigger>
                            <TabsTrigger value="personal" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-12 px-1 text-sm flex items-center gap-2">
                                <Heart class="w-4 h-4" /> Personal Details
                            </TabsTrigger>
                            <TabsTrigger value="placement" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-12 px-1 text-sm flex items-center gap-2">
                                <Building2 class="w-4 h-4" /> Placement & Workload
                            </TabsTrigger>
                        </TabsList>

                        <!-- Identity & Account Tab -->
                        <TabsContent value="account" class="space-y-6">
                            <Card class="border shadow-sm">
                                <CardHeader class="pb-3 border-b bg-muted/20">
                                    <CardTitle class="text-sm font-bold flex items-center gap-2">
                                        <User class="w-4 h-4 text-indigo-600" />
                                        Employment & Identity Credentials
                                    </CardTitle>
                                    <CardDescription>Basic account profile and academic qualifications.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <Label for="name" class="text-xs font-bold uppercase tracking-wider text-slate-600">Full Name</Label>
                                            <Input id="name" v-model="form.name" required class="h-10 font-semibold" />
                                            <p v-if="form.errors.name" class="text-xs text-destructive font-medium">{{ form.errors.name }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="email" class="text-xs font-bold uppercase tracking-wider text-slate-600">Email Address</Label>
                                            <Input id="email" type="email" v-model="form.email" required class="h-10 font-semibold" />
                                            <p v-if="form.errors.email" class="text-xs text-destructive font-medium">{{ form.errors.email }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="staff_number" class="text-xs font-bold uppercase tracking-wider text-slate-600">Staff ID Number</Label>
                                            <Input id="staff_number" v-model="form.staff_number" required class="h-10 font-semibold font-mono" />
                                            <p v-if="form.errors.staff_number" class="text-xs text-destructive font-medium">{{ form.errors.staff_number }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Designation</Label>
                                            <Select v-model="form.designation">
                                                <SelectTrigger class="h-10 font-semibold">
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
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Date Joined</Label>
                                            <div class="relative group">
                                                <Calendar class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input type="date" v-model="form.date_joined" class="pl-10 h-10 font-semibold" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Highest Qualification</Label>
                                            <div class="relative group">
                                                <GraduationCap class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input v-model="form.highest_qualification" class="pl-10 h-10 font-semibold" placeholder="e.g. PhD, M.Sc, B.Sc" />
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Personal Profile Tab -->
                        <TabsContent value="personal" class="space-y-6">
                            <Card class="border shadow-sm">
                                <CardHeader class="pb-3 border-b bg-muted/20">
                                    <CardTitle class="text-sm font-bold flex items-center gap-2">
                                        <Heart class="w-4 h-4 text-indigo-600" />
                                        Personal & Contact Information
                                    </CardTitle>
                                    <CardDescription>Demographic data, contact details, and origin.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Gender</Label>
                                            <Select v-model="form.gender">
                                                <SelectTrigger class="h-10 font-semibold">
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
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Marital Status</Label>
                                            <Select v-model="form.marital_status">
                                                <SelectTrigger class="h-10 font-semibold">
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
                                            <Label for="date_of_birth" class="text-xs font-bold uppercase tracking-wider text-slate-600">Date of Birth</Label>
                                            <Input id="date_of_birth" type="date" v-model="form.date_of_birth" class="h-10 font-semibold" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="phone_number" class="text-xs font-bold uppercase tracking-wider text-slate-600">Phone Number</Label>
                                            <div class="relative group">
                                                <Phone class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                                <Input id="phone_number" v-model="form.phone_number" class="pl-10 h-10 font-semibold" placeholder="+234..." />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Nationality</Label>
                                            <Input v-model="form.nationality" class="h-10 font-semibold" placeholder="Nigerian" />
                                        </div>

                                        <div class="space-y-2">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-2">
                                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">State of Origin</Label>
                                                    <Select v-model="form.state_id">
                                                        <SelectTrigger class="h-10 font-semibold">
                                                            <SelectValue placeholder="State" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="state in states" :key="state.id" :value="String(state.id)">
                                                                {{ state.name }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div class="space-y-2">
                                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">LGA</Label>
                                                    <Select v-model="form.lga_id" :disabled="!form.state_id">
                                                        <SelectTrigger class="h-10 font-semibold">
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

                                        <div class="space-y-2 md:col-span-2">
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Residential Address</Label>
                                            <Textarea v-model="form.address" class="min-h-[90px] font-medium" placeholder="Full residential street address..." />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Placement & Academic Tab -->
                        <TabsContent value="placement" class="space-y-6">
                            <Card class="border shadow-sm">
                                <CardHeader class="pb-3 border-b bg-muted/20">
                                    <CardTitle class="text-sm font-bold flex items-center gap-2">
                                        <Building2 class="w-4 h-4 text-indigo-600" />
                                        Institutional Placement & Department
                                    </CardTitle>
                                    <CardDescription>Faculty, department, unit placement and research specialization.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-6 space-y-6">
                                    <div class="flex items-center justify-between p-4 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-xl border border-indigo-100 dark:border-indigo-900">
                                        <div class="space-y-0.5">
                                            <Label for="is_academic" class="text-sm font-bold text-slate-900 dark:text-white">Academic Faculty Position</Label>
                                            <p class="text-xs text-muted-foreground">Enable for teaching, lecturing, and research staff.</p>
                                        </div>
                                        <Switch id="is_academic" v-model:checked="form.is_academic" />
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div v-if="form.is_academic" class="space-y-2">
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Faculty</Label>
                                            <Select v-model="selectedFacultyId">
                                                <SelectTrigger class="h-10 font-semibold">
                                                    <SelectValue placeholder="Select Faculty" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="String(faculty.id)">
                                                        {{ faculty.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Department</Label>
                                            <Select v-model="form.department_id" :disabled="form.is_academic && !selectedFacultyId">
                                                <SelectTrigger class="h-10 font-semibold">
                                                    <SelectValue placeholder="Select Department" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="dept in availableDepartments" :key="dept.id" :value="String(dept.id)">
                                                        {{ dept.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Unit (Optional)</Label>
                                            <Select v-model="form.unit_id" :disabled="!form.department_id">
                                                <SelectTrigger class="h-10 font-semibold">
                                                    <SelectValue placeholder="Select Unit" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="unit in availableUnits" :key="unit.id" :value="String(unit.id)">
                                                        {{ unit.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <!-- Academic Specific Fields -->
                                    <div v-if="form.is_academic" class="space-y-6 pt-6 border-t">
                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                                                <BookOpen class="w-4 h-4 text-indigo-600" /> Area of Specialization
                                            </Label>
                                            <Input v-model="form.specialization" placeholder="e.g. Artificial Intelligence & Data Science" class="h-10 font-semibold" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                                                <ClipboardList class="w-4 h-4 text-indigo-600" /> Research Interests
                                            </Label>
                                            <Textarea v-model="form.research_interests" placeholder="Key research domains..." class="min-h-[90px] font-medium" />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>

                <!-- Right Sidebar: Access & Security (1/3) -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Access Assignment Card -->
                    <Card class="border shadow-sm bg-card">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-sm font-bold flex items-center gap-2">
                                    <Shield class="w-4 h-4 text-indigo-600" />
                                    System Permissions
                                </CardTitle>
                                <Badge variant="outline" class="font-bold text-[10px]">Multi-Role</Badge>
                            </div>
                            <CardDescription>Assign administrative and portal access roles.</CardDescription>
                        </CardHeader>

                        <CardContent class="p-4 space-y-4">
                            <div v-if="canAssignRoles !== false" class="space-y-3">
                                <!-- Search Roles -->
                                <div class="relative">
                                    <Search class="absolute left-3 top-2.5 w-3.5 h-3.5 text-muted-foreground" />
                                    <Input v-model="roleSearch" placeholder="Search roles..." class="pl-8 h-9 text-xs" />
                                </div>

                                <div class="space-y-2 max-h-[260px] overflow-y-auto pr-1">
                                    <div 
                                        v-for="role in filteredRoles" 
                                        :key="role.id" 
                                        class="flex items-center space-x-3 bg-slate-50 dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-100 transition-colors"
                                    >
                                        <Checkbox 
                                            :id="'role-' + role.id"
                                            :checked="form.role_ids.includes(String(role.id))"
                                            @update:checked="(checked) => {
                                                if (checked) {
                                                    form.role_ids.push(String(role.id));
                                                } else {
                                                    form.role_ids = form.role_ids.filter(id => id !== String(role.id));
                                                }
                                            }"
                                        />
                                        <label :for="'role-' + role.id" class="text-xs font-bold cursor-pointer text-slate-800 dark:text-slate-200 flex-1 select-none">
                                            {{ formatRoleName(role.name) }}
                                        </label>
                                    </div>
                                </div>
                                <p v-if="form.errors.role_ids" class="text-xs text-destructive font-medium">{{ form.errors.role_ids }}</p>
                            </div>
                            
                            <div v-else class="text-xs text-muted-foreground p-3 bg-muted/20 rounded-lg border">
                                You do not have permission to modify staff role assignments.
                            </div>
                        </CardContent>

                        <CardFooter class="bg-muted/10 border-t p-4">
                            <Button @click="submit" :disabled="form.processing" class="w-full font-bold h-10 gap-2 shadow-md bg-indigo-600 hover:bg-indigo-500 text-white">
                                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                                <CheckCircle2 v-else class="w-4 h-4" />
                                Update Permissions
                            </Button>
                        </CardFooter>
                    </Card>

                    <!-- Security Actions Card -->
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-xs uppercase tracking-wider font-bold text-muted-foreground">Security Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="p-4 space-y-2">
                            <Button variant="outline" type="button" @click="resetPassword" class="w-full justify-start gap-3 h-10 text-xs font-bold bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100">
                                <RefreshCw class="w-3.5 h-3.5 text-amber-600" />
                                Reset Account Password
                            </Button>
                            
                            <Button variant="ghost" type="button" @click="confirmDelete" class="w-full justify-start gap-3 h-10 text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50">
                                <AlertTriangle class="w-3.5 h-3.5" />
                                Terminate Account
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- System Info Callout -->
                    <div class="bg-indigo-50/70 dark:bg-indigo-950/30 p-4 rounded-xl border border-indigo-100 dark:border-indigo-900 flex gap-3">
                        <div class="bg-indigo-100 dark:bg-indigo-900 p-2 rounded-lg h-fit flex-shrink-0">
                            <Info class="w-4 h-4 text-indigo-700 dark:text-indigo-300" />
                        </div>
                        <p class="text-xs text-indigo-900 dark:text-indigo-200 leading-relaxed font-medium">
                            Role updates take effect immediately. Changing departments will adjust course allocation privileges automatically.
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
