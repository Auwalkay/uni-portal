<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { 
    Building, ArrowLeft, CreditCard, Key, Sparkles, CheckCircle2, 
    Users, UserCheck, Eye, EyeOff, ShieldCheck, HelpCircle, Home
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const showSquadcoSecret = ref(false);
const showPaystackSecret = ref(false);

const form = useForm({
    name: '',
    gender_type: 'mixed',
    description: '',
    payment_gateway: 'none',
    squadco_secret_key: '',
    squadco_public_key: '',
    paystack_secret_key: '',
    paystack_public_key: '',
});

const submit = () => {
    form.post(route('admin.hostels.store'));
};
</script>

<template>
    <Head title="Create Hostel" />

    <AdminLayout>
        <div class="space-y-8 p-6 lg:p-10 max-w-[1300px] mx-auto">
            <!-- Header Ribbon -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-gradient-to-r from-card via-card to-primary/5 border rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <Link :href="route('admin.hostels.index')" class="inline-flex items-center text-xs font-bold text-muted-foreground hover:text-primary transition-colors bg-muted/50 px-3 py-1.5 rounded-full border">
                            <ArrowLeft class="h-3.5 w-3.5 mr-1.5" /> Back to Hostels Directory
                        </Link>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl flex items-center gap-3">
                        <div class="h-12 w-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shadow-inner">
                            <Building class="h-7 w-7" />
                        </div>
                        Register New Hostel
                    </h1>
                    <p class="text-muted-foreground max-w-2xl text-base">
                        Register a new campus residential building. Set up gender allocation rules, descriptive facilities, and custom payment gateway API keys.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Main Form Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column: Form Controls (2 cols) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Section 1: Basic Information -->
                        <div class="bg-card border rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                            <div class="flex items-center gap-3 border-b pb-4">
                                <div class="p-2.5 bg-primary/10 text-primary rounded-xl">
                                    <Building class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-extrabold text-foreground">Basic Information</h2>
                                    <p class="text-xs text-muted-foreground">General details and gender restrictions for the hostel building.</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Hostel Name -->
                                <div class="space-y-2">
                                    <Label for="name" class="font-bold text-foreground flex items-center justify-between text-sm">
                                        <span>Hostel Name <span class="text-destructive">*</span></span>
                                        <span class="text-xs text-muted-foreground font-normal">Must be unique</span>
                                    </Label>
                                    <Input 
                                        id="name" 
                                        v-model="form.name" 
                                        placeholder="e.g. Mandela Hall, Block A, Queen Elizabeth Hostel" 
                                        class="h-12 rounded-2xl text-base bg-background transition-all focus:ring-2 focus:ring-primary/20" 
                                    />
                                    <p v-if="form.errors.name" class="text-xs text-destructive font-semibold flex items-center gap-1">
                                        <span>⚠️</span> {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- Gender Allocation Cards -->
                                <div class="space-y-3">
                                    <Label class="font-bold text-foreground text-sm">Gender Allocation <span class="text-destructive">*</span></Label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <!-- Mixed Card -->
                                        <div 
                                            @click="form.gender_type = 'mixed'"
                                            :class="[
                                                'cursor-pointer border-2 rounded-2xl p-4 transition-all duration-200 flex flex-col justify-between space-y-3',
                                                form.gender_type === 'mixed' 
                                                    ? 'border-purple-500 bg-purple-50/50 dark:bg-purple-950/20 shadow-md ring-2 ring-purple-500/20' 
                                                    : 'border-border bg-background hover:border-muted-foreground/30'
                                            ]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div class="p-2 rounded-xl bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300">
                                                    <Users class="h-5 w-5" />
                                                </div>
                                                <div :class="['h-4 w-4 rounded-full border-2 flex items-center justify-center', form.gender_type === 'mixed' ? 'border-purple-600 bg-purple-600' : 'border-muted-foreground/40']">
                                                    <div v-if="form.gender_type === 'mixed'" class="h-1.5 w-1.5 rounded-full bg-white"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-sm text-foreground">Mixed Residence</p>
                                                <p class="text-[11px] text-muted-foreground mt-0.5">Male & Female students allowed</p>
                                            </div>
                                        </div>

                                        <!-- Male Only Card -->
                                        <div 
                                            @click="form.gender_type = 'male'"
                                            :class="[
                                                'cursor-pointer border-2 rounded-2xl p-4 transition-all duration-200 flex flex-col justify-between space-y-3',
                                                form.gender_type === 'male' 
                                                    ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 shadow-md ring-2 ring-blue-500/20' 
                                                    : 'border-border bg-background hover:border-muted-foreground/30'
                                            ]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div class="p-2 rounded-xl bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                                                    <Building class="h-5 w-5" />
                                                </div>
                                                <div :class="['h-4 w-4 rounded-full border-2 flex items-center justify-center', form.gender_type === 'male' ? 'border-blue-600 bg-blue-600' : 'border-muted-foreground/40']">
                                                    <div v-if="form.gender_type === 'male'" class="h-1.5 w-1.5 rounded-full bg-white"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-sm text-foreground">Male Only</p>
                                                <p class="text-[11px] text-muted-foreground mt-0.5">Restricted to Male students</p>
                                            </div>
                                        </div>

                                        <!-- Female Only Card -->
                                        <div 
                                            @click="form.gender_type = 'female'"
                                            :class="[
                                                'cursor-pointer border-2 rounded-2xl p-4 transition-all duration-200 flex flex-col justify-between space-y-3',
                                                form.gender_type === 'female' 
                                                    ? 'border-pink-500 bg-pink-50/50 dark:bg-pink-950/20 shadow-md ring-2 ring-pink-500/20' 
                                                    : 'border-border bg-background hover:border-muted-foreground/30'
                                            ]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div class="p-2 rounded-xl bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300">
                                                    <Building class="h-5 w-5" />
                                                </div>
                                                <div :class="['h-4 w-4 rounded-full border-2 flex items-center justify-center', form.gender_type === 'female' ? 'border-pink-600 bg-pink-600' : 'border-muted-foreground/40']">
                                                    <div v-if="form.gender_type === 'female'" class="h-1.5 w-1.5 rounded-full bg-white"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-sm text-foreground">Female Only</p>
                                                <p class="text-[11px] text-muted-foreground mt-0.5">Restricted to Female students</p>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.gender_type" class="text-xs text-destructive font-semibold">⚠️ {{ form.errors.gender_type }}</p>
                                </div>

                                <!-- Description -->
                                <div class="space-y-2">
                                    <Label for="description" class="font-bold text-foreground text-sm">Description & Facilities (Optional)</Label>
                                    <Textarea 
                                        id="description" 
                                        v-model="form.description" 
                                        placeholder="Enter details about location, room amenities, study areas, internet access..." 
                                        rows="4" 
                                        class="rounded-2xl text-sm bg-background transition-all focus:ring-2 focus:ring-primary/20" 
                                    />
                                    <p v-if="form.errors.description" class="text-xs text-destructive font-semibold">⚠️ {{ form.errors.description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Payment Gateway Configuration -->
                        <div class="bg-card border rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                            <div class="flex items-center gap-3 border-b pb-4">
                                <div class="p-2.5 bg-amber-500/10 text-amber-600 rounded-xl">
                                    <CreditCard class="h-5 w-5" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-extrabold text-foreground">Payment Gateway & Custom Keys</h2>
                                    <p class="text-xs text-muted-foreground">Specify a dedicated payment gateway and custom API keys for this hostel.</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Gateway Preference Radio Cards -->
                                <div class="space-y-3">
                                    <Label class="font-bold text-foreground text-sm">Preferred Payment Gateway</Label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <!-- System Default -->
                                        <div 
                                            @click="form.payment_gateway = 'none'"
                                            :class="[
                                                'cursor-pointer border-2 rounded-2xl p-4 transition-all duration-200 flex flex-col justify-between space-y-3',
                                                form.payment_gateway === 'none' 
                                                    ? 'border-slate-800 dark:border-slate-300 bg-slate-100/60 dark:bg-slate-800/40 shadow-md ring-2 ring-slate-400/20' 
                                                    : 'border-border bg-background hover:border-muted-foreground/30'
                                            ]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <Badge variant="outline" class="font-bold text-[10px] uppercase">Default</Badge>
                                                <div :class="['h-4 w-4 rounded-full border-2 flex items-center justify-center', form.payment_gateway === 'none' ? 'border-foreground bg-foreground' : 'border-muted-foreground/40']">
                                                    <div v-if="form.payment_gateway === 'none'" class="h-1.5 w-1.5 rounded-full bg-background"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-sm text-foreground">System Global</p>
                                                <p class="text-[11px] text-muted-foreground mt-0.5">Use default system gateway setting</p>
                                            </div>
                                        </div>

                                        <!-- Squadco Gateway -->
                                        <div 
                                            @click="form.payment_gateway = 'squadco'"
                                            :class="[
                                                'cursor-pointer border-2 rounded-2xl p-4 transition-all duration-200 flex flex-col justify-between space-y-3',
                                                form.payment_gateway === 'squadco' 
                                                    ? 'border-purple-600 bg-purple-50/50 dark:bg-purple-950/20 shadow-md ring-2 ring-purple-600/20' 
                                                    : 'border-border bg-background hover:border-muted-foreground/30'
                                            ]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <Badge variant="outline" class="font-bold text-[10px] bg-purple-100 text-purple-800 border-purple-300 uppercase">Squadco</Badge>
                                                <div :class="['h-4 w-4 rounded-full border-2 flex items-center justify-center', form.payment_gateway === 'squadco' ? 'border-purple-600 bg-purple-600' : 'border-muted-foreground/40']">
                                                    <div v-if="form.payment_gateway === 'squadco'" class="h-1.5 w-1.5 rounded-full bg-white"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-sm text-foreground">Squadco Gateway</p>
                                                <p class="text-[11px] text-muted-foreground mt-0.5">Route checkout through Squadco</p>
                                            </div>
                                        </div>

                                        <!-- Paystack Gateway -->
                                        <div 
                                            @click="form.payment_gateway = 'paystack'"
                                            :class="[
                                                'cursor-pointer border-2 rounded-2xl p-4 transition-all duration-200 flex flex-col justify-between space-y-3',
                                                form.payment_gateway === 'paystack' 
                                                    ? 'border-emerald-600 bg-emerald-50/50 dark:bg-emerald-950/20 shadow-md ring-2 ring-emerald-600/20' 
                                                    : 'border-border bg-background hover:border-muted-foreground/30'
                                            ]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <Badge variant="outline" class="font-bold text-[10px] bg-emerald-100 text-emerald-800 border-emerald-300 uppercase">Paystack</Badge>
                                                <div :class="['h-4 w-4 rounded-full border-2 flex items-center justify-center', form.payment_gateway === 'paystack' ? 'border-emerald-600 bg-emerald-600' : 'border-muted-foreground/40']">
                                                    <div v-if="form.payment_gateway === 'paystack'" class="h-1.5 w-1.5 rounded-full bg-white"></div>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-extrabold text-sm text-foreground">Paystack Gateway</p>
                                                <p class="text-[11px] text-muted-foreground mt-0.5">Route checkout through Paystack</p>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.payment_gateway" class="text-xs text-destructive font-semibold">⚠️ {{ form.errors.payment_gateway }}</p>
                                </div>

                                <!-- Squadco API Credentials Section -->
                                <div class="border rounded-2xl p-5 space-y-4 bg-muted/20">
                                    <div class="flex items-center justify-between border-b pb-3">
                                        <div class="flex items-center gap-2">
                                            <div class="p-1.5 rounded-lg bg-purple-100 text-purple-700">
                                                <Key class="h-4 w-4" />
                                            </div>
                                            <h3 class="text-sm font-extrabold text-foreground">Squadco Custom API Keys</h3>
                                        </div>
                                        <span class="text-[11px] text-muted-foreground font-medium">Optional Override</span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Secret Key -->
                                        <div class="space-y-1.5">
                                            <Label for="squadco_secret_key" class="text-xs font-bold text-foreground">Secret Key</Label>
                                            <div class="relative">
                                                <Input 
                                                    id="squadco_secret_key" 
                                                    v-model="form.squadco_secret_key" 
                                                    :type="showSquadcoSecret ? 'text' : 'password'" 
                                                    placeholder="sandbox_sk_..." 
                                                    class="h-11 rounded-xl bg-background text-sm pr-10" 
                                                />
                                                <button 
                                                    type="button"
                                                    @click="showSquadcoSecret = !showSquadcoSecret"
                                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                                >
                                                    <component :is="showSquadcoSecret ? EyeOff : Eye" class="h-4 w-4" />
                                                </button>
                                            </div>
                                            <p v-if="form.errors.squadco_secret_key" class="text-xs text-destructive font-medium">{{ form.errors.squadco_secret_key }}</p>
                                        </div>

                                        <!-- Public Key -->
                                        <div class="space-y-1.5">
                                            <Label for="squadco_public_key" class="text-xs font-bold text-foreground">Public Key</Label>
                                            <Input 
                                                id="squadco_public_key" 
                                                v-model="form.squadco_public_key" 
                                                placeholder="sandbox_pk_..." 
                                                class="h-11 rounded-xl bg-background text-sm" 
                                            />
                                            <p v-if="form.errors.squadco_public_key" class="text-xs text-destructive font-medium">{{ form.errors.squadco_public_key }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Paystack API Credentials Section -->
                                <div class="border rounded-2xl p-5 space-y-4 bg-muted/20">
                                    <div class="flex items-center justify-between border-b pb-3">
                                        <div class="flex items-center gap-2">
                                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-700">
                                                <Key class="h-4 w-4" />
                                            </div>
                                            <h3 class="text-sm font-extrabold text-foreground">Paystack Custom API Keys</h3>
                                        </div>
                                        <span class="text-[11px] text-muted-foreground font-medium">Optional Override</span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Secret Key -->
                                        <div class="space-y-1.5">
                                            <Label for="paystack_secret_key" class="text-xs font-bold text-foreground">Secret Key</Label>
                                            <div class="relative">
                                                <Input 
                                                    id="paystack_secret_key" 
                                                    v-model="form.paystack_secret_key" 
                                                    :type="showPaystackSecret ? 'text' : 'password'" 
                                                    placeholder="sk_test_..." 
                                                    class="h-11 rounded-xl bg-background text-sm pr-10" 
                                                />
                                                <button 
                                                    type="button"
                                                    @click="showPaystackSecret = !showPaystackSecret"
                                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                                >
                                                    <component :is="showPaystackSecret ? EyeOff : Eye" class="h-4 w-4" />
                                                </button>
                                            </div>
                                            <p v-if="form.errors.paystack_secret_key" class="text-xs text-destructive font-medium">{{ form.errors.paystack_secret_key }}</p>
                                        </div>

                                        <!-- Public Key -->
                                        <div class="space-y-1.5">
                                            <Label for="paystack_public_key" class="text-xs font-bold text-foreground">Public Key</Label>
                                            <Input 
                                                id="paystack_public_key" 
                                                v-model="form.paystack_public_key" 
                                                placeholder="pk_test_..." 
                                                class="h-11 rounded-xl bg-background text-sm" 
                                            />
                                            <p v-if="form.errors.paystack_public_key" class="text-xs text-destructive font-medium">{{ form.errors.paystack_public_key }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Live Directory Preview & Actions -->
                    <div class="space-y-6">
                        <!-- Sticky Preview Container -->
                        <div class="bg-card border rounded-3xl p-6 shadow-sm space-y-6 sticky top-8">
                            <div class="flex items-center justify-between border-b pb-4">
                                <h3 class="font-extrabold text-base text-foreground flex items-center gap-2">
                                    <Sparkles class="h-5 w-5 text-primary" /> Live Directory Card Preview
                                </h3>
                                <Badge variant="secondary" class="text-[10px] font-bold">REALTIME</Badge>
                            </div>

                            <!-- Mock Card Preview -->
                            <div class="border rounded-2xl overflow-hidden shadow-md bg-card">
                                <div class="p-5 border-b bg-gradient-to-r from-card to-muted/40 flex items-start justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2.5 bg-primary/10 text-primary rounded-xl">
                                            <Home class="h-6 w-6" />
                                        </div>
                                        <div>
                                            <h4 class="font-extrabold text-base text-foreground">
                                                {{ form.name.trim() || 'New Hostel Name' }}
                                            </h4>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <Badge 
                                                    variant="outline" 
                                                    :class="[
                                                        'font-bold text-[10px] uppercase',
                                                        form.gender_type === 'male' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                                        form.gender_type === 'female' ? 'bg-pink-50 text-pink-700 border-pink-200' : 'bg-purple-50 text-purple-700 border-purple-200'
                                                    ]"
                                                >
                                                    {{ form.gender_type }}
                                                </Badge>
                                                <Badge v-if="form.payment_gateway !== 'none'" variant="outline" class="font-bold text-[10px] bg-amber-50 text-amber-700 border-amber-200 uppercase">
                                                    {{ form.payment_gateway }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-5 space-y-3 text-xs">
                                    <p class="text-muted-foreground line-clamp-2">
                                        {{ form.description.trim() || 'No description provided yet.' }}
                                    </p>

                                    <div class="pt-2 border-t space-y-1.5">
                                        <div class="flex items-center justify-between text-muted-foreground font-medium">
                                            <span>Squadco Custom Keys:</span>
                                            <span :class="['font-bold', form.squadco_secret_key ? 'text-emerald-600' : 'text-slate-400']">
                                                {{ form.squadco_secret_key ? '✓ Configured' : 'Global Default' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between text-muted-foreground font-medium">
                                            <span>Paystack Custom Keys:</span>
                                            <span :class="['font-bold', form.paystack_secret_key ? 'text-emerald-600' : 'text-slate-400']">
                                                {{ form.paystack_secret_key ? '✓ Configured' : 'Global Default' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="space-y-3 pt-2">
                                <Button 
                                    type="submit" 
                                    :disabled="form.processing || !form.name.trim()" 
                                    class="w-full h-12 rounded-2xl font-extrabold text-base shadow-lg shadow-primary/20 transition-all hover:scale-[1.01]"
                                >
                                    <CheckCircle2 class="mr-2 h-5 w-5" /> 
                                    {{ form.processing ? 'Saving Hostel...' : 'Create Hostel' }}
                                </Button>

                                <Button type="button" variant="outline" as-child class="w-full h-12 rounded-2xl font-bold text-sm">
                                    <Link :href="route('admin.hostels.index')">Cancel & Return</Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
