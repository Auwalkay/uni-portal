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
import { ArrowLeft, Save, UserPlus } from 'lucide-vue-next';

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

const props = defineProps<{
    faculties: Faculty[];
    sessions: Session[];
    states: State[];
    levels: string[];
}>();

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
    next_of_kin_relationship: '', // 'Relative' etc. field
    faculty_id: '',
    department_id: '',
    program: '',
    current_level: '',
    admitted_session_id: '',
    password: '', // Optional default
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
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Add New Student" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as="a" :href="route('admin.students.index')">
                    <ArrowLeft class="h-5 w-5" />
                </Button>
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Add New Student</h2>
                    <p class="text-muted-foreground">Manually onboard a student into the system.</p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="space-y-8">
                    
                    <!-- Personal Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Personal Information</CardTitle>
                            <CardDescription>Basic details about the student.</CardDescription>
                        </CardHeader>
                        <CardContent class="grid md:grid-cols-2 gap-6">
                            <div class="grid gap-2">
                                <Label for="first_name">First Name</Label>
                                <Input id="first_name" v-model="form.first_name" placeholder="John" required />
                                <p v-if="form.errors.first_name" class="text-sm text-red-500">{{ form.errors.first_name }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="last_name">Last Name</Label>
                                <Input id="last_name" v-model="form.last_name" placeholder="Doe" required />
                                <p v-if="form.errors.last_name" class="text-sm text-red-500">{{ form.errors.last_name }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="email">Email Address</Label>
                                <Input id="email" type="email" v-model="form.email" placeholder="john.doe@example.com" required />
                                <p v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="phone">Phone Number</Label>
                                <Input id="phone" v-model="form.phone_number" placeholder="08012345678" required />
                                <p v-if="form.errors.phone_number" class="text-sm text-red-500">{{ form.errors.phone_number }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="gender">Gender</Label>
                                <select id="gender" v-model="form.gender" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                                    <option value="" disabled>Select Gender...</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                <p v-if="form.errors.gender" class="text-sm text-red-500">{{ form.errors.gender }}</p>
                            </div>
                           <div class="grid gap-2">
                                <Label for="dob">Date of Birth</Label>
                                <Input id="dob" type="date" v-model="form.dob" required />
                                <p v-if="form.errors.dob" class="text-sm text-red-500">{{ form.errors.dob }}</p>
                            </div>
                            <div class="grid gap-2 md:col-span-2">
                                <Label for="address">Address</Label>
                                <Input id="address" v-model="form.address" placeholder="123 Street, City" required />
                                <p v-if="form.errors.address" class="text-sm text-red-500">{{ form.errors.address }}</p>
                            </div>
                            <!-- Origin -->
                            <div class="grid gap-2">
                                <Label for="state">State of Origin</Label>
                                <select id="state" v-model="form.state_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background" required>
                                    <option value="" disabled>Select State...</option>
                                    <option v-for="state in states" :key="state.id" :value="state.id">{{ state.name }}</option>
                                </select>
                                <p v-if="form.errors.state_id" class="text-sm text-red-500">{{ form.errors.state_id }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="lga">LGA</Label>
                                <select id="lga" v-model="form.lga_id" :disabled="!form.state_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" required>
                                    <option value="" disabled>Select LGA...</option>
                                    <option v-for="lga in filteredLgas" :key="lga.id" :value="lga.id">{{ lga.name }}</option>
                                </select>
                                <p v-if="form.errors.lga_id" class="text-sm text-red-500">{{ form.errors.lga_id }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Next of Kin Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Next of Kin Information</CardTitle>
                            <CardDescription>Emergency contact details.</CardDescription>
                        </CardHeader>
                        <CardContent class="grid md:grid-cols-2 gap-6">
                            <div class="grid gap-2">
                                <Label for="nok_name">Full Name</Label>
                                <Input id="nok_name" v-model="form.next_of_kin_name" placeholder="Jane Doe" required />
                                <p v-if="form.errors.next_of_kin_name" class="text-sm text-red-500">{{ form.errors.next_of_kin_name }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="nok_phone">Phone Number</Label>
                                <Input id="nok_phone" v-model="form.next_of_kin_phone" placeholder="080..." required />
                                <p v-if="form.errors.next_of_kin_phone" class="text-sm text-red-500">{{ form.errors.next_of_kin_phone }}</p>
                            </div>
                             <div class="grid gap-2">
                                <Label for="nok_rel">Relationship</Label>
                                <Input id="nok_rel" v-model="form.next_of_kin_relationship" placeholder="Mother" required />
                                <p v-if="form.errors.next_of_kin_relationship" class="text-sm text-red-500">{{ form.errors.next_of_kin_relationship }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Academic Information -->
                     <Card>
                        <CardHeader>
                            <CardTitle>Academic Details</CardTitle>
                            <CardDescription>Enrollment and program information.</CardDescription>
                        </CardHeader>
                        <CardContent class="grid md:grid-cols-2 gap-6">

                             <div class="grid gap-2">
                                <Label for="session">Admitted Session</Label>
                                <select id="session" v-model="form.admitted_session_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background" required>
                                    <option value="" disabled>Select Session...</option>
                                    <option v-for="sess in sessions" :key="sess.id" :value="sess.id">{{ sess.name }}</option>
                                </select>
                                <p v-if="form.errors.admitted_session_id" class="text-sm text-red-500">{{ form.errors.admitted_session_id }}</p>
                            </div>
                             <div class="grid gap-2">
                                <Label for="faculty">Faculty</Label>
                                <select id="faculty" v-model="form.faculty_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background" required>
                                    <option value="" disabled>Select Faculty...</option>
                                    <option v-for="fac in faculties" :key="fac.id" :value="fac.id">{{ fac.name }}</option>
                                </select>
                                <p v-if="form.errors.faculty_id" class="text-sm text-red-500">{{ form.errors.faculty_id }}</p>
                            </div>
                             <div class="grid gap-2">
                                <Label for="department">Department</Label>
                                <select id="department" v-model="form.department_id" :disabled="!form.faculty_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:opacity-50" required>
                                    <option value="" disabled>Select Department...</option>
                                    <option v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                </select>
                                <p v-if="form.errors.department_id" class="text-sm text-red-500">{{ form.errors.department_id }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="program">Program</Label>
                                <Input id="program" v-model="form.program" placeholder="e.g. B.Sc. Computer Science" required />
                                <p v-if="form.errors.program" class="text-sm text-red-500">{{ form.errors.program }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="level">Current Level</Label>
                                <select id="level" v-model="form.current_level" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background" required>
                                    <option value="" disabled>Select Level...</option>
                                    <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
                                </select>
                                <p v-if="form.errors.current_level" class="text-sm text-red-500">{{ form.errors.current_level }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Account Security -->
                     <Card>
                        <CardHeader>
                            <CardTitle>Account Security</CardTitle>
                            <CardDescription>Set the initial login credentials.</CardDescription>
                        </CardHeader>
                        <CardContent class="grid md:grid-cols-2 gap-6">
                            <div class="grid gap-2">
                                <Label for="password">Password (Optional)</Label>
                                <Input id="password" type="password" v-model="form.password" placeholder="Leave empty for default: 'password'" />
                                <p class="text-xs text-muted-foreground">Default password will be 'password' if left blank.</p>
                                <p v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex justify-end">
                        <Button type="submit" size="lg" :disabled="form.processing">
                            <UserPlus class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Creating...' : 'Create Student' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
