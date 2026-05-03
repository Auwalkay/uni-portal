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
} from '@/components/ui/select'
import { 
    User, Mail, Briefcase, GraduationCap, Shield, ShieldCheck, 
    Building2, MapPin, AtSign, KeyRound, Info, CreditCard
} from 'lucide-vue-next';
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
    role_id: props.current_role_id || '',
});

const selectedFacultyId = ref<string>('');

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
</script>

<template>
    <Head title="Edit Staff" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 max-w-4xl mx-auto space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Edit Staff Member</h1>
                    <p class="text-slate-500 mt-1">Update personal, professional, and access details for {{ staff.name }}</p>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="route('admin.staff.index')">Back to List</Link>
                </Button>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Section 1: Personal Info -->
                <Card class="overflow-hidden border-slate-200 dark:border-slate-800 shadow-sm">
                    <CardHeader class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                                <User class="w-5 h-5" />
                            </div>
                            <div>
                                <CardTitle class="text-lg">Personal Information</CardTitle>
                                <CardDescription>Basic account and contact details.</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-6 grid md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <Label for="name">Full Name</Label>
                            <Input id="name" v-model="form.name" required placeholder="e.g. Dr. Jane Smith" />
                            <p v-if="form.errors.name" class="text-sm text-red-500 font-medium">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email Address</Label>
                            <Input id="email" type="email" v-model="form.email" required placeholder="jane.smith@university.edu" />
                            <p v-if="form.errors.email" class="text-sm text-red-500 font-medium">{{ form.errors.email }}</p>
                        </div>

                       
                    </CardContent>
                </Card>

                <!-- Section 2: Employment Details -->
                <Card class="overflow-hidden border-slate-200 dark:border-slate-800 shadow-sm">
                    <CardHeader class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
                                <Briefcase class="w-5 h-5" />
                            </div>
                            <div>
                                <CardTitle class="text-lg">Employment Details</CardTitle>
                                <CardDescription>Departmental and professional designation.</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-6 space-y-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="staff_number">Staff Identification Number</Label>
                                <Input id="staff_number" v-model="form.staff_number" required placeholder="e.g. STF/2024/001" />
                                <p v-if="form.errors.staff_number" class="text-sm text-red-500 font-medium">{{ form.errors.staff_number }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label>Professional Designation</Label>
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
                                <p v-if="form.errors.designation" class="text-sm text-red-500 font-medium">{{ form.errors.designation }}</p>
                            </div>

                        <div class="flex items-center space-x-3 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                            <Switch id="is_academic" v-model:checked="form.is_academic" />
                            <div>
                                <Label for="is_academic" class="font-bold">Academic Staff Profile</Label>
                                <p class="text-xs text-slate-500">Enabling this allows course allocations and result entry permissions.</p>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6 pt-4">
                            <div v-if="form.is_academic" class="space-y-2">
                                <Label>Faculty</Label>
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
                                <Label>Department</Label>
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
                                <p v-if="form.errors.department_id" class="text-sm text-red-500 font-medium">{{ form.errors.department_id }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label>Unit (Optional)</Label>
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
                                <p v-if="form.errors.unit_id" class="text-sm text-red-500 font-medium">{{ form.errors.unit_id }}</p>
                            </div>
                        </div>

                    </CardContent>
                </Card>

                <!-- Section 3: System Access -->
                <Card class="overflow-hidden border-slate-200 dark:border-slate-800 shadow-sm">
                    <CardHeader class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg">
                                <Shield class="w-5 h-5" />
                            </div>
                            <div>
                                <CardTitle class="text-lg">System Access & Roles</CardTitle>
                                <CardDescription>Define administrative privileges.</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-6">
                        <div class="space-y-4">
                            <Label>Administrative Role</Label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="role in roles" :key="role.id" 
                                    class="relative flex items-center p-4 rounded-xl border-2 cursor-pointer transition-all"
                                    :class="form.role_id === role.id ? 'border-primary bg-primary/5 shadow-sm' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200'"
                                    @click="form.role_id = role.id"
                                >
                                    <div class="flex-1">
                                        <div class="font-bold text-sm uppercase tracking-wider">{{ role.name.replace('_', ' ') }}</div>
                                        <p class="text-[10px] text-slate-500 mt-0.5">Grants specific unit permissions</p>
                                    </div>
                                    <div v-if="form.role_id === role.id" class="bg-primary text-white rounded-full p-1">
                                        <ShieldCheck class="w-4 h-4" />
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.role_id" class="text-sm text-red-500 font-medium">{{ form.errors.role_id }}</p>
                        </div>
                    </CardContent>
                </Card>
                
                <div class="flex justify-end items-center gap-4 pt-4">
                    <Link :href="route('admin.staff.index')" class="text-slate-500 hover:text-slate-700 font-medium text-sm">Cancel Changes</Link>
                    <Button type="submit" size="lg" :disabled="form.processing" class="px-8 font-bold">
                        {{ form.processing ? 'Saving Changes...' : 'Update Staff Member' }}
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
