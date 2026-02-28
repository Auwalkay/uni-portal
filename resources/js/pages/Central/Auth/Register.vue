<script setup lang="ts">
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { route } from 'ziggy-js';
import ApplicationLogo from '@/components/ApplicationLogo.vue';
import { AlertCircle, Building, CheckCircle2, Navigation, Upload, User, UserCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    status?: string;
    errors?: any;
    pricingTiers?: { id: string, name: string, price: number }[];
}>();

const page = usePage();
const centralDomain = page.props.central_domain as string || 'localhost';

const form = useForm({
    school_name: '',
    id: '',
    domain: '',
    email: '',
    address: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    logo: null as File | null,
    capacity_tier_id: '',
});

const selectedPrice = computed(() => {
    if (!props.pricingTiers || !form.capacity_tier_id) return 0;
    const tier = props.pricingTiers.find(t => t.id === form.capacity_tier_id);
    return tier ? tier.price : 0;
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formSteps = ref(1);
const logoPreview = ref<string | null>(null);

const formatDomain = () => {
    if (form.id) {
        // Remove spaces, special chars, make lowercase for ID
        form.id = form.id.toLowerCase().replace(/[^a-z0-9-]/g, '');
        // Auto-fill domain based on ID
        if(centralDomain) {
           form.domain = `${form.id}.${centralDomain}`;
        }
    }
};

const handleLogoUpload = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        form.logo = input.files[0];
        logoPreview.value = URL.createObjectURL(input.files[0]);
    }
};

const triggerLogoUpload = () => {
    document.getElementById('logo-upload')?.click();
};

const submit = () => {
    form.post(route('central.register.store'), {
        preserveScroll: true,
        onError: () => {
             // If there's an error on step 1 fields, go back to step 1
             if (form.errors.school_name || form.errors.id || form.errors.email || form.errors.address || form.errors.logo) {
                 formSteps.value = 1;
             }
        }
    });
};
</script>

<template>
    <Head title="Register Polytechnic" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-[url('/img/grid.svg')] bg-center">
        <div class="max-w-3xl w-full space-y-8 bg-white p-8 md:p-10 rounded-2xl shadow-xl border border-slate-100 relative overflow-hidden">
            <!-- Decorative Background Element -->
             <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
             
             <div class="text-center relative z-10">
                <Link href="/" class="inline-block">
                    <ApplicationLogo class="h-16 w-16 mx-auto mb-2 text-primary" />
                </Link>
                <h2 class="mt-4 text-3xl font-extrabold text-slate-900 tracking-tight">Onboard your Institution</h2>
                <p class="mt-2 text-sm text-slate-600 max-w-xl mx-auto">
                    Join the central registry and spin up a dedicated portal for your Polytechnic in seconds. <br>
                    <span class="font-semibold text-primary mt-1 inline-block">Setup fee based on student capacity</span>
                </p>
            </div>

            <!-- Global Errors -->
            <div v-if="props.errors?.payment" class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md relative z-10">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <AlertCircle class="h-5 w-5 text-red-500" aria-hidden="true" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 font-medium">
                            {{ props.errors.payment }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="relative z-10 mt-8">
                
                <!-- STEP 1: Institution Details -->
                <div v-show="formSteps === 1" class="transition-all duration-300">
                    <div class="flex items-center gap-2 mb-6 text-primary border-b border-primary/10 pb-2">
                        <Building class="w-5 h-5" />
                        <h3 class="text-lg font-bold">1. Institutional Profile</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4 md:col-span-2">
                            <Label for="school_name">Institution Name <span class="text-red-500">*</span></Label>
                            <Input id="school_name" v-model="form.school_name" type="text" placeholder="e.g. Kaduna State Polytechnic" required class="h-11" />
                            <p class="text-xs text-red-500" v-if="form.errors.school_name">{{ form.errors.school_name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="id">Portal Shorthand (ID) <span class="text-red-500">*</span></Label>
                            <Input id="id" v-model="form.id" @input="formatDomain" type="text" placeholder="kadpoly" required class="h-11" />
                            <p class="text-xs text-muted-foreground mt-1">Used to generate your unique domain.</p>
                            <p class="text-xs text-red-500" v-if="form.errors.id">{{ form.errors.id }}</p>
                        </div>

                         <div class="space-y-2">
                            <Label for="domain">Dedicated Domain <span class="text-red-500">*</span></Label>
                            <div class="relative">
                                <Navigation class="absolute left-3 top-3 h-5 w-5 text-slate-400" />
                                <Input id="domain" v-model="form.domain" readonly class="pl-10 h-11 bg-slate-50 border-slate-200 text-slate-700" placeholder="kadpoly.nbte.gov.ng" />
                            </div>
                            <p class="text-xs text-red-500" v-if="form.errors.domain">{{ form.errors.domain }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Official Email <span class="text-red-500">*</span></Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="contact@polytechnic.edu.ng" required class="h-11" />
                            <p class="text-xs text-red-500" v-if="form.errors.email">{{ form.errors.email }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Institutional Logo</Label>
                            <div 
                                @click="triggerLogoUpload"
                                class="border-2 border-dashed border-slate-200 rounded-lg h-24 flex items-center justify-center cursor-pointer hover:border-primary/50 hover:bg-slate-50 transition-colors"
                            >
                                <input id="logo-upload" type="file" @change="handleLogoUpload" accept="image/*" class="hidden" />
                                <div v-if="!logoPreview" class="text-center">
                                    <Upload class="w-6 h-6 mx-auto text-slate-400 mb-1" />
                                    <span class="text-xs text-slate-500 font-medium">Click to upload logo</span>
                                </div>
                                <img v-else :src="logoPreview" class="h-16 object-contain" />
                            </div>
                            <p class="text-xs text-red-500" v-if="form.errors.logo">{{ form.errors.logo }}</p>
                        </div>

                        <div class="space-y-4 md:col-span-2">
                            <Label for="address">Full Address <span class="text-red-500">*</span></Label>
                            <Textarea id="address" v-model="form.address" placeholder="Main Campus, Km 5 Expressway..." required class="h-24 resize-none" />
                            <p class="text-xs text-red-500" v-if="form.errors.address">{{ form.errors.address }}</p>
                        </div>
                        
                        <div class="space-y-4 md:col-span-2 pt-2">
                            <Label for="capacity_tier_id">Expected Student Enrollment Capacity <span class="text-red-500">*</span></Label>
                            <div class="relative">
                                <select 
                                    id="capacity_tier_id" 
                                    v-model="form.capacity_tier_id" 
                                    class="flex h-11 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50 appearance-none shadow-sm"
                                    required
                                >
                                    <option value="" disabled>Select your expected capacity</option>
                                    <option v-for="tier in props.pricingTiers" :key="tier.id" :value="tier.id">
                                        {{ tier.name }} - {{ formatCurrency(tier.price) }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">This determines your scalable infrastructure limits and exact setup fee.</p>
                            <p class="text-xs text-red-500" v-if="form.errors.capacity_tier_id">{{ form.errors.capacity_tier_id }}</p>
                        </div>
                        
                        <div class="md:col-span-2 pt-4">
                            <Button type="button" @click="formSteps = 2" class="w-full h-12 text-md font-semibold gap-2">
                                Continue to Administrator Setup <UserCircle class="w-5 h-5"/>
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Admin Details & Payment -->
                <div v-show="formSteps === 2" class="transition-all duration-300">
                     <div class="flex items-center justify-between mb-6 border-b border-primary/10 pb-2">
                        <div class="flex items-center gap-2 text-primary">
                            <User class="w-5 h-5" />
                            <h3 class="text-lg font-bold">2. Primary Administrator</h3>
                        </div>
                        <button type="button" @click="formSteps = 1" class="text-sm font-medium text-slate-500 hover:text-slate-800">
                             &larr; Back to Profile
                        </button>
                    </div>

                    <div class="bg-blue-50/50 p-4 rounded-lg border border-blue-100 mb-6 flex gap-3">
                        <AlertCircle class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                        <p class="text-xs text-blue-800 leading-relaxed">
                            These credentials will be used to log into your newly generated polytechnic portal once payment is completed. Keep them safe.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="admin_name">Full Name <span class="text-red-500">*</span></Label>
                            <Input id="admin_name" v-model="form.admin_name" type="text" placeholder="Dr. John Doe" required class="h-11" />
                            <p class="text-xs text-red-500" v-if="form.errors.admin_name">{{ form.errors.admin_name }}</p>
                        </div>

                         <div class="space-y-2">
                            <Label for="admin_email">Admin Email <span class="text-red-500">*</span></Label>
                            <Input id="admin_email" v-model="form.admin_email" type="email" placeholder="admin@poly.edu.ng" required class="h-11" />
                            <p class="text-xs text-red-500" v-if="form.errors.admin_email">{{ form.errors.admin_email }}</p>
                        </div>

                         <div class="space-y-2">
                            <Label for="admin_password">Password <span class="text-red-500">*</span></Label>
                            <Input id="admin_password" v-model="form.admin_password" type="password" placeholder="••••••••" required class="h-11" />
                            <p class="text-xs text-red-500" v-if="form.errors.admin_password">{{ form.errors.admin_password }}</p>
                        </div>
                    </div>

                    <!-- Checkout Summary -->
                    <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 mt-8 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-slate-600 font-medium">Integration Fee (1 Year)</span>
                            <span class="text-xl font-bold text-slate-900">{{ formatCurrency(selectedPrice) }}</span>
                        </div>
                        <ul class="space-y-2 text-sm text-slate-600">
                            <li class="flex items-center gap-2"><CheckCircle2 class="w-4 h-4 text-emerald-500"/> Instant provision of isolated database</li>
                            <li class="flex items-center gap-2"><CheckCircle2 class="w-4 h-4 text-emerald-500"/> Custom subdomain routing mapping</li>
                            <li class="flex items-center gap-2"><CheckCircle2 class="w-4 h-4 text-emerald-500"/> Pre-configured admin roles mapped</li>
                        </ul>
                    </div>
                        
                    <div class="flex items-center justify-between pt-2">
                        <p class="text-xs text-slate-500">Secured via Paystack.</p>
                        <Button type="submit" :disabled="form.processing" class="h-12 px-8 text-md font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/20">
                            <span v-if="form.processing">Processing...</span>
                            <span v-else>Pay {{ formatCurrency(selectedPrice) }} & Register</span>
                        </Button>
                    </div>
                </div>
            </form>

            <div class="mt-8 text-center text-sm">
                <p class="text-slate-600">
                    Already have a portal? 
                    <Link :href="route('central.login')" class="font-bold text-primary hover:underline">
                         Access Super Admin panel
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>
