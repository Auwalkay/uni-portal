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
    Shield
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
        }>;
    }>;
    designations: Array<string>;
    roles: Array<{ id: string; name: string }>;
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
    is_academic: true,
    role_id: '',
});

const selectedFacultyId = ref<string>('');

const availableDepartments = computed(() => {
    if (!selectedFacultyId.value) return [];
    const faculty = props.faculties.find(f => f.id === selectedFacultyId.value);
    return faculty ? faculty.departments : [];
});

watch(selectedFacultyId, () => {
    form.department_id = '';
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
        <div class="p-4 md:p-8 max-w-5xl mx-auto space-y-8 pb-20">
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

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Primary Information -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Personal & Account Details -->
                    <Card class="border shadow-sm overflow-hidden">
                        <CardHeader class="bg-slate-50 border-b py-4">
                            <CardTitle class="text-base flex items-center gap-2">
                                <User class="w-4 h-4 text-primary" />
                                Account Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label for="name" class="text-sm font-semibold">Full Name</Label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground group-focus-within:text-primary transition-colors">
                                            <User class="w-4 h-4" />
                                        </div>
                                        <Input id="name" v-model="form.name" class="pl-10" placeholder="e.g. Dr. John Doe" required />
                                    </div>
                                    <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="email" class="text-sm font-semibold">Email Address</Label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground group-focus-within:text-primary transition-colors">
                                            <Mail class="w-4 h-4" />
                                        </div>
                                        <Input id="email" type="email" v-model="form.email" class="pl-10" placeholder="john.doe@university.edu" required />
                                    </div>
                                    <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="staff_number" class="text-sm font-semibold">Staff ID Number</Label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground group-focus-within:text-primary transition-colors">
                                            <BadgeCheck class="w-4 h-4" />
                                        </div>
                                        <Input id="staff_number" v-model="form.staff_number" class="pl-10" placeholder="STF/2024/001" required />
                                    </div>
                                    <p v-if="form.errors.staff_number" class="text-xs text-destructive">{{ form.errors.staff_number }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="password" class="text-sm font-semibold">Temporary Password</Label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground group-focus-within:text-primary transition-colors">
                                            <Lock class="w-4 h-4" />
                                        </div>
                                        <Input id="password" type="password" v-model="form.password" class="pl-10" required />
                                    </div>
                                    <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Academic & Placement -->
                    <Card class="border shadow-sm overflow-hidden">
                        <CardHeader class="bg-slate-50 border-b py-4">
                            <CardTitle class="text-base flex items-center gap-2">
                                <Building2 class="w-4 h-4 text-primary" />
                                Academic Placement
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
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
                                    <Select v-model="form.department_id" :disabled="!selectedFacultyId">
                                        <SelectTrigger class="bg-white">
                                            <SelectValue placeholder="Select Department" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="dept in availableDepartments" :key="dept.id" :value="dept.id">
                                                {{ dept.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.department_id" class="text-xs text-destructive">{{ form.errors.department_id }}</p>
                                </div>

                                <div class="space-y-4 md:col-span-2 p-4 bg-primary/5 rounded-lg border border-primary/10">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <Label for="is_academic" class="text-base font-semibold">Academic Position</Label>
                                            <p class="text-xs text-muted-foreground italic">Toggle on if this staff member is involved in teaching/research.</p>
                                        </div>
                                        <Switch 
                                            id="is_academic" 
                                            :checked="form.is_academic" 
                                            @update:checked="(val) => form.is_academic = val"
                                            class="data-[state=checked]:bg-primary"
                                        />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
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

