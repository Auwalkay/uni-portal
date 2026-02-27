<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { PlusCircle, Building2, ExternalLink, Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Pagination from '@/components/Pagination.vue';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';

const props = defineProps<{
    tenants: any;
    filters: {
        search?: string;
        status?: string;
    };
    flash?: { success?: string, error?: string };
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

watch([search, status], ([newSearch, newStatus]) => {
    router.get(route('central.tenants.index'), {
        search: newSearch,
        status: newStatus === 'all' ? null : newStatus
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, { debounce: 300 } as any);

const clearFilters = () => {
    search.value = '';
    status.value = 'all';
};

const getSubscriptionStatus = (tenant: any) => {
    if (!tenant.subscriptions || tenant.subscriptions.length === 0) return 'NONE';
    const activeSub = tenant.subscriptions.find((s: any) => s.status === 'active') || tenant.subscriptions[0];
    return activeSub.status.toUpperCase();
};

const toSentenceCase = (str: string) => {
    if (!str) return '';
    return str.toLowerCase().replace(/(^\s*|\.\s*)([a-z])/g, function(m, p1, p2) {
        return p1 + p2.toUpperCase();
    }).split(' ').map(word => {
        // Keep acronyms like NBTE uppercase if needed, but for now simple sentence case
        return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    }).join(' ');
};
</script>

<template>
    <Head title="Manage Polytechnics" />

    <CentralLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                Manage Polytechnics (Tenants)
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Header & Actions -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                     <div>
                        <h2 class="text-2xl font-bold tracking-tight">Polytechnics</h2>
                        <p class="text-slate-500">Manage all registered SaaS tenants and their domains.</p>
                    </div>

                    <Link :href="route('central.tenants.create')">
                        <Button class="bg-primary hover:bg-primary/90 text-white font-bold">
                            <PlusCircle class="mr-2 h-4 w-4" />
                            Onboard Polytechnics
                        </Button>
                    </Link>
                </div>

                <!-- Filters -->
                <div class="bg-white p-4 rounded-xl border shadow-sm flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <Label for="search" class="mb-2 block">Search by School Name</Label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <Input 
                                id="search" 
                                v-model="search" 
                                placeholder="E.g. Lagos State Polytechnic..." 
                                class="pl-10 focus-visible:ring-primary"
                            />
                        </div>
                    </div>

                    <div class="w-full md:w-48">
                        <Label for="status" class="mb-2 block">Filter by Status</Label>
                        <Select v-model="status">
                            <SelectTrigger>
                                <SelectValue placeholder="Select Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Status</SelectItem>
                                <SelectItem value="active">Active Only</SelectItem>
                                <SelectItem value="suspended">Suspended Only</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <Button v-if="search || status !== 'all'" variant="ghost" @click="clearFilters" class="text-slate-500">
                        <X class="h-4 w-4 mr-2" />
                        Clear
                    </Button>
                </div>

                <!-- Flash Message -->
                <div v-if="flash?.success" class="p-4 rounded-lg bg-green-50 border border-green-200 text-green-700">
                    {{ flash.success }}
                </div>

                <!-- Tenants List -->
                <div v-if="tenants.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link :href="route('central.tenants.show', tenant.id)" v-for="tenant in tenants.data" :key="tenant.id" class="rounded-xl border bg-white shadow-sm overflow-hidden flex flex-col hover:border-primary/30 hover:shadow-md transition group">
                        <div class="h-24 bg-gradient-to-r from-primary to-primary/80"></div>
                        <div class="px-6 pb-6 pt-0 flex-1 flex flex-col relative">
                            <div class="h-16 w-16 bg-white rounded-xl shadow-md border flex items-center justify-center -mt-8 mb-4 overflow-hidden group-hover:border-primary/20 transition-colors">
                                <img v-if="tenant.logo_path" :src="'/storage/' + tenant.logo_path" class="h-full w-full object-contain p-1" />
                                <Building2 v-else class="h-8 w-8 text-primary" />
                            </div>
                            
                            <div class="flex justify-between items-start gap-2">
                                <h3 class="text-xl font-bold text-slate-900 group-hover:text-primary transition-colors leading-snug">
                                    {{ toSentenceCase(tenant.school_name || tenant.id) }}
                                </h3>
                                <Badge :variant="tenant.is_active ? 'default' : 'destructive'" :class="[tenant.is_active ? 'bg-primary hover:bg-primary/90' : '', 'shrink-0 text-[10px] px-1.5 py-0 h-5']">
                                    {{ tenant.is_active ? 'Active' : 'Suspended' }}
                                </Badge>
                            </div>
                            <div class="flex justify-between items-center mt-auto mb-4">
                                <p class="text-slate-500 text-sm">
                                    DB: <code class="bg-slate-100 px-1 rounded">{{ tenant.tenancy_db_name || 'tenant_' + tenant.id }}</code>
                                </p>
                                <Badge :variant="getSubscriptionStatus(tenant) === 'ACTIVE' ? 'secondary' : 'outline'" class="text-[9px] uppercase font-bold tracking-tight px-1.5 py-0">
                                    Sub: {{ getSubscriptionStatus(tenant) }}
                                </Badge>
                            </div>

                            <div class="space-y-2 mt-auto">
                                <div>
                                    <Label class="text-xs text-slate-500 uppercase tracking-wider">Assigned Domains</Label>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        <Badge variant="secondary" v-for="domain in tenant.domains" :key="domain.id" class="flex items-center gap-1 bg-slate-100 text-slate-700 border-none">
                                            {{ domain.domain }}
                                            <a :href="'http://' + domain.domain + ':8000'" target="_blank" class="hover:text-primary transition-colors">
                                                <ExternalLink class="h-3 w-3" />
                                            </a>
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="py-16 text-center border-2 border-dashed rounded-xl border-slate-200 bg-slate-50">
                    <Building2 class="h-12 w-12 text-slate-300 mx-auto mb-3" />
                    <h3 class="text-lg font-medium text-slate-900">No Polytechnics Found</h3>
                    <p class="text-slate-500 max-w-sm mx-auto mt-1">
                        We couldn't find any polytechnics matching your search or filter criteria.
                    </p>
                    <Button variant="link" @click="clearFilters" class="mt-4">Clear all filters</Button>
                </div>

                <!-- Pagination -->
                <Pagination :links="tenants.links" />

            </div>
        </div>
    </CentralLayout>
</template>
