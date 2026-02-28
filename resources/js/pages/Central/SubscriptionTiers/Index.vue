<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { 
    CreditCard, 
    PlusCircle,
    Pencil,
    Trash2,
    Users
} from 'lucide-vue-next';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { route } from 'ziggy-js';
import Swal from 'sweetalert2';
import { ref } from 'vue';

const props = defineProps<{
    tiers: {
        id: string;
        name: string;
        max_students: number | null;
        price: string;
        created_at: string;
    }[];
}>();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedTier = ref<any>(null);

const form = useForm({
    name: '',
    max_students: null as number | null,
    price: ''
});

const formatCurrency = (amount: string | number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(Number(amount));
};

const openCreateModal = () => {
    form.reset();
    showCreateModal.value = true;
};

const openEditModal = (tier: any) => {
    selectedTier.value = tier;
    form.name = tier.name;
    form.max_students = tier.max_students;
    form.price = tier.price;
    showEditModal.value = true;
};

const submitCreate = () => {
    form.post(route('central.subscription-tiers.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            Swal.fire({
                title: 'Created!',
                text: 'New subscription tier added.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const submitEdit = () => {
    if (!selectedTier.value) return;
    
    form.put(route('central.subscription-tiers.update', selectedTier.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            Swal.fire({
                title: 'Updated!',
                text: 'Subscription tier has been modified.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const destroyTier = (tier: any) => {
    Swal.fire({
        title: 'Delete Tier?',
        text: `Are you sure you want to remove the "${tier.name}" tier? Institutions already on this tier will keep their current setup.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('central.subscription-tiers.destroy', tier.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'The tier has been removed.',
                        icon: 'success',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Subscription Tiers" />

    <CentralLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-slate-900 leading-tight flex items-center gap-2">
                        <CreditCard class="w-6 h-6 text-indigo-600" />
                        Infrastructure Setup Pricing
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">Configure scalable pricing brackets based on exact institution size.</p>
                </div>
                <Button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all h-10 px-4">
                    <PlusCircle class="w-4 h-4 mr-2" />
                    New Price Bracket
                </Button>
            </div>
        </template>

        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 flex gap-3 shadow-sm">
                    <CreditCard class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                    <div>
                        <h4 class="font-semibold text-blue-900 text-sm">How Tiers Work during Registration</h4>
                        <p class="text-xs text-blue-800 leading-relaxed mt-1">
                            When new polytechnics sign up via the public `/register` link, they must select an Expected Student Enrollment. The tiers defined below dictate the drop-down options presented to them and calculate their final Paystack initialization charge autonomously. Do not delete tiers that are actively matching large groups of customers.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <Card v-for="tier in props.tiers" :key="tier.id" class="border-slate-200 shadow-sm hover:shadow-md transition-all relative overflow-hidden group hover:border-indigo-200">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-400 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <CardHeader class="pb-3 border-b border-slate-100 bg-slate-50/30">
                            <CardTitle class="text-lg font-bold text-slate-800 flex items-center justify-between">
                                {{ tier.name }}
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                                    <button @click="openEditModal(tier)" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors">
                                        <Pencil class="w-3.5 h-3.5" />
                                    </button>
                                    <button @click="destroyTier(tier)" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-md transition-colors">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <div class="flex items-end justify-between mb-4">
                                <div>
                                    <p class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">Setup Cost</p>
                                    <div class="text-2xl font-black text-indigo-700">{{ formatCurrency(tier.price) }}</div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 text-sm text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                <Users class="w-4 h-4 text-slate-400" />
                                <span class="font-medium flex-1">Max Capacity</span>
                                <span class="font-bold text-slate-900 border border-slate-200 bg-white px-2 py-0.5 rounded text-xs shadow-sm">
                                    {{ tier.max_students ? tier.max_students.toLocaleString() : 'Unlimited 🚀' }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Add New Card Placeholder -->
                    <button @click="openCreateModal" class="rounded-xl border-2 border-dashed border-slate-200 hover:border-indigo-300 hover:bg-slate-50 transition-all flex flex-col items-center justify-center min-h-[220px] text-slate-500 hover:text-indigo-600 group">
                        <div class="w-12 h-12 bg-slate-100 group-hover:bg-indigo-100 rounded-full flex items-center justify-center mb-3 transition-colors">
                            <PlusCircle class="w-6 h-6" />
                        </div>
                        <span class="font-medium">Define Custom Tier</span>
                    </button>
                </div>
                
                <div v-if="props.tiers.length === 0" class="text-center py-16 bg-white rounded-xl border border-slate-200 border-dashed">
                    <CreditCard class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                    <h3 class="text-lg font-bold text-slate-900">No Pricing Tiers Found</h3>
                    <p class="text-sm text-slate-500 mt-1 mb-4">You haven't defined any public registration prices yet.</p>
                </div>

            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog :open="showCreateModal || showEditModal" @update:open="(val) => { if(!val) { showCreateModal = false; showEditModal = false; } }">
            <DialogContent class="sm:max-w-[450px] overflow-hidden p-0 border-0 rounded-2xl">
                <div class="h-1.5 bg-indigo-600 w-full"></div>
                <form @submit.prevent="showEditModal ? submitEdit() : submitCreate()">
                    <div class="p-6">
                        <DialogHeader class="mb-5 text-left">
                            <DialogTitle class="text-xl font-bold text-slate-900">
                                {{ showEditModal ? 'Edit Pricing Bracket' : 'Create Pricing Bracket' }}
                            </DialogTitle>
                            <DialogDescription class="text-slate-500 text-sm mt-1">
                                Fill in the details for this infrastructure scale tier.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name" class="text-sm font-semibold text-slate-700">Display Label</Label>
                                <Input id="name" v-model="form.name" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 h-10" placeholder="e.g. Medium Sized Institution" required />
                                <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                            </div>

                            <div class="space-y-2">
                                <Label for="price" class="text-sm font-semibold text-slate-700">Setup Cost (₦)</Label>
                                <Input id="price" type="number" step="1000" v-model="form.price" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 h-10 font-medium" placeholder="250000" required />
                                <span v-if="form.errors.price" class="text-xs text-red-500">{{ form.errors.price }}</span>
                            </div>

                            <div class="space-y-2">
                                <Label for="max_students" class="text-sm font-semibold text-slate-700">Hard Capacity Limit</Label>
                                <Input id="max_students" type="number" :value="form.max_students ?? ''" @input="(e: Event) => form.max_students = (e.target as HTMLInputElement).value ? Number((e.target as HTMLInputElement).value) : null" class="border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 h-10" placeholder="Enter number or leave blank for unlimited" />
                                <p class="text-[11px] text-slate-500 leading-tight">Leave this blank if this tier represents an unlimited or enterprise-level capacity ceiling.</p>
                                <span v-if="form.errors.max_students" class="text-xs text-red-500">{{ form.errors.max_students }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <DialogFooter class="bg-slate-50 p-4 border-t border-slate-100 flex justify-end gap-2">
                        <Button type="button" variant="outline" @click="showCreateModal = false; showEditModal = false" class="border-slate-200" :disabled="form.processing">
                            Cancel
                        </Button>
                        <Button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : (showEditModal ? 'Save Modifications' : 'Publish Bracket') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </CentralLayout>
</template>
