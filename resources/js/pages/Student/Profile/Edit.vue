<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
// If Textarea component doesn't exist, I'll fallback to HTML textarea or Input if suitable.
// Checking imports... usually Input is fine for text.

const props = defineProps<{
    student: any;
    user: any;
    status?: string;
}>();

const form = useForm({
    gender: props.student.gender || '',
    dob: props.student.dob || '',
    phone_number: props.student.phone_number || '',
    address: props.student.address || '',
    state_of_origin: props.student.state_of_origin || '',
    lga: props.student.lga || '',
    next_of_kin_name: props.student.next_of_kin_name || '',
    next_of_kin_phone: props.student.next_of_kin_phone || '',
    next_of_kin_address: props.student.next_of_kin_address || '',
});

const submit = () => {
    form.patch(route('student.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: (error) => {
            console.log(error);
        },
    });
};
</script>

<template>
    <Head title="My Profile" />

    <StudentLayout>
        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">My Profile</h2>
                    <p class="text-muted-foreground">Manage your personal information.</p>
                </div>
            </div>

             <div v-if="status" class="mb-4 rounded-md bg-green-50 p-4 text-sm font-medium text-green-600">
                {{ status }}
            </div>

            <div class="grid gap-6 rounded-xl border bg-card p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                         <div class="space-y-2">
                            <Label for="matric">Matriculation Number</Label>
                            <Input id="matric" :model-value="student.matriculation_number" disabled class="bg-muted" />
                        </div>
                        <div class="space-y-2">
                             <Label for="email">Email Address</Label>
                             <Input id="email" :model-value="user.email" disabled class="bg-muted" />
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="gender">Gender</Label>
                            <select id="gender" v-model="form.gender" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="" disabled>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            <span v-if="form.errors.gender" class="text-sm text-red-500">{{ form.errors.gender }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="dob">Date of Birth</Label>
                            <Input id="dob" type="date" v-model="form.dob" />
                            <span v-if="form.errors.dob" class="text-sm text-red-500">{{ form.errors.dob }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">Phone Number</Label>
                            <Input id="phone" v-model="form.phone_number" placeholder="+234..." />
                             <span v-if="form.errors.phone_number" class="text-sm text-red-500">{{ form.errors.phone_number }}</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="state">State of Origin</Label>
                            <Input id="state" v-model="form.state_of_origin" />
                             <span v-if="form.errors.state_of_origin" class="text-sm text-red-500">{{ form.errors.state_of_origin }}</span>
                        </div>
                         <div class="space-y-2">
                            <Label for="lga">LGA</Label>
                            <Input id="lga" v-model="form.lga" />
                             <span v-if="form.errors.lga" class="text-sm text-red-500">{{ form.errors.lga }}</span>
                        </div>
                    </div>
                    
                     <div class="space-y-2">
                        <Label for="address">Residential Address</Label>
                        <textarea id="address" v-model="form.address" class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"></textarea>
                         <span v-if="form.errors.address" class="text-sm text-red-500">{{ form.errors.address }}</span>
                    </div>

                    <div class="border-t pt-6">
                        <h3 class="mb-4 text-lg font-medium">Next of Kin Details</h3>
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="nok_name">Full Name</Label>
                                <Input id="nok_name" v-model="form.next_of_kin_name" />
                                 <span v-if="form.errors.next_of_kin_name" class="text-sm text-red-500">{{ form.errors.next_of_kin_name }}</span>
                            </div>
                            <div class="space-y-2">
                                <Label for="nok_phone">Phone Number</Label>
                                <Input id="nok_phone" v-model="form.next_of_kin_phone" />
                                 <span v-if="form.errors.next_of_kin_phone" class="text-sm text-red-500">{{ form.errors.next_of_kin_phone }}</span>
                            </div>
                        </div>
                         <div class="mt-4 space-y-2">
                            <Label for="nok_address">Address</Label>
                             <textarea id="nok_address" v-model="form.next_of_kin_address" class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="form.processing">
                            Save Changes
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </StudentLayout>
</template>
