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
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Staff Management',
        href: '/admin/staff',
    },
    {
        title: 'Add Staff',
        href: '/admin/staff/create',
    },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    staff_number: '',
    designation: '',
    department_id: '',
    is_academic: true,
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

const submit = () => {
    form.post(route('admin.staff.store')); 
};
</script>

<template>
    <Head title="Add Staff" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl mx-auto">
             <Card>
                <CardHeader>
                    <CardTitle>Add New Staff Member</CardTitle>
                    <CardDescription>Create a new staff account with system access.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="name">Full Name</Label>
                            <Input id="name" v-model="form.name" required />
                            <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email Address</Label>
                            <Input id="email" type="email" v-model="form.email" required />
                            <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Password</Label>
                            <Input id="password" type="password" v-model="form.password" required />
                            <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="staff_number">Staff Number</Label>
                            <Input id="staff_number" v-model="form.staff_number" required />
                            <p v-if="form.errors.staff_number" class="text-sm text-red-500">{{ form.errors.staff_number }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                             <div class="space-y-2">
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
                                <Select v-model="form.department_id" :disabled="!selectedFacultyId">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Department" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="dept in availableDepartments" :key="dept.id" :value="dept.id">
                                            {{ dept.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.department_id" class="text-sm text-red-500">{{ form.errors.department_id }}</p>
                            </div>

                            <div class="space-y-2 col-span-2">
                                <Label for="designation">Designation</Label>
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
                                <p v-if="form.errors.designation" class="text-sm text-red-500">{{ form.errors.designation }}</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Switch id="is_academic" :checked="form.is_academic" @update:checked="(val) => form.is_academic = val" />
                            <Label for="is_academic">Academic Staff</Label>
                        </div>
                        
                        <div class="flex justify-end gap-4">
                            <Button variant="outline" type="button" as-child>
                                <Link :href="route('admin.staff.index')">Cancel</Link>
                            </Button>
                            <Button type="submit" :disabled="form.processing">Create Staff</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
