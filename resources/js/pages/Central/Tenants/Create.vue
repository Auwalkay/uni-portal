<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArrowLeft, Building2, User, Globe } from 'lucide-vue-next';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';

const form = useForm({
    school_name: '',
    id: '', // URL friendly prefix
    domain: '', // Full domain
    email: '',
    address: '',
    logo: null as File | null,
    
    // Custom Admin setup
    admin_name: '',
    admin_email: '',
    admin_password: '',
});

const submit = () => {
    form.post(route('central.tenants.store'));
};

const formatDomain = () => {
    if (form.id) {
        form.domain = `${form.id}.localhost`; // Change locally, could be domain mapping in production
    }
};

const handleLogoUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.logo = target.files[0];
    }
};
</script>

<template>
    <Head title="Onboard New Polytechnic" />

    <CentralLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('central.tenants.index')" class="p-2 border rounded-md hover:bg-slate-50 transition">
                    <ArrowLeft class="w-4 h-4 text-slate-600" />
                </Link>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                    Onboard New Polytechnic
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Institution Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Building2 class="h-5 w-5 text-indigo-600" />
                                Institution Profile
                            </CardTitle>
                            <CardDescription>
                                Basic information about the polytechnic.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2 col-span-full">
                                <Label for="school_name">Institution Name</Label>
                                <Input id="school_name" v-model="form.school_name" placeholder="e.g. Lagos State Polytechnic" required />
                                <p class="text-xs text-red-500" v-if="form.errors.school_name">{{ form.errors.school_name }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="email">Official Contact Email</Label>
                                <Input id="email" type="email" v-model="form.email" placeholder="contact@institution.edu" required />
                                <p class="text-xs text-red-500" v-if="form.errors.email">{{ form.errors.email }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="address">Physical Address</Label>
                                <Input id="address" v-model="form.address" placeholder="Main Campus Address" required />
                                <p class="text-xs text-red-500" v-if="form.errors.address">{{ form.errors.address }}</p>
                            </div>

                            <div class="space-y-2 col-span-full">
                                <Label for="logo">Institution Logo</Label>
                                <Input id="logo" type="file" accept="image/*" @change="handleLogoUpload" />
                                <p class="text-xs text-slate-500">Provide a square transparent PNG for best results.</p>
                                <p class="text-xs text-red-500" v-if="form.errors.logo">{{ form.errors.logo }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Domain & Hosting -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Globe class="h-5 w-5 text-indigo-600" />
                                Domain & Database
                            </CardTitle>
                            <CardDescription>
                                System identifiers and web address configuration.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="id">Tenant Database ID</Label>
                                <Input 
                                    id="id" 
                                    v-model="form.id" 
                                    placeholder="e.g. laspotech" 
                                    @input="formatDomain"
                                    required 
                                />
                                <p class="text-xs text-slate-500">Only lowercase alphanumeric and dashes.</p>
                                <p class="text-xs text-red-500" v-if="form.errors.id">{{ form.errors.id }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="domain">Primary Subdomain / Domain</Label>
                                <Input id="domain" v-model="form.domain" placeholder="e.g. laspotech.localhost" required />
                                <p class="text-xs text-slate-500">The web address for this tenant.</p>
                                <p class="text-xs text-red-500" v-if="form.errors.domain">{{ form.errors.domain }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Admin Account Setup -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <User class="h-5 w-5 text-indigo-600" />
                                Super Admin Account
                            </CardTitle>
                            <CardDescription>
                                Create the initial administrator for this institution.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2 col-span-full">
                                <Label for="admin_name">Admin Full Name</Label>
                                <Input id="admin_name" v-model="form.admin_name" placeholder="e.g. John Doe" required />
                                <p class="text-xs text-red-500" v-if="form.errors.admin_name">{{ form.errors.admin_name }}</p>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="admin_email">Admin Login Email</Label>
                                <Input id="admin_email" type="email" v-model="form.admin_email" placeholder="admin@institution.edu" required />
                                <p class="text-xs text-red-500" v-if="form.errors.admin_email">{{ form.errors.admin_email }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="admin_password">Admin Login Password</Label>
                                <Input id="admin_password" type="password" v-model="form.admin_password" placeholder="••••••••" required />
                                <p class="text-xs text-slate-500">Minimum 8 characters.</p>
                                <p class="text-xs text-red-500" v-if="form.errors.admin_password">{{ form.errors.admin_password }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex justify-end gap-4">
                        <Link :href="route('central.tenants.index')">
                            <Button type="button" variant="outline">Cancel</Button>
                        </Link>
                        <Button type="submit" size="lg" :disabled="form.processing">
                            {{ form.processing ? 'Provisioning Infrastructure...' : 'Provision Polytechnic' }}
                        </Button>
                    </div>

                </form>

            </div>
        </div>
    </CentralLayout>
</template>
