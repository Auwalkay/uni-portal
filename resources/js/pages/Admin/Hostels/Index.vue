<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Edit, Trash2, Home, Settings } from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { route } from 'ziggy-js';

const props = defineProps<{
    hostels: Array<{
        id: string;
        name: string;
        gender_type: string;
        description: string;
        floors_count: number;
        fees_count: number;
        created_at: string;
    }>;
    sessions: Array<{
        id: string;
        name: string;
    }>;
}>();

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingHostel = ref<any>(null);

const form = useForm({
    name: '',
    gender_type: 'mixed',
    description: '',
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    isCreateModalOpen.value = true;
};

const openEditModal = (hostel: any) => {
    editingHostel.value = hostel;
    form.name = hostel.name;
    form.gender_type = hostel.gender_type;
    form.description = hostel.description;
    form.clearErrors();
    isEditModalOpen.value = true;
};

const submitCreate = () => {
    form.post(route('admin.hostels.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
        },
    });
};

const submitEdit = () => {
    form.put(route('admin.hostels.update', editingHostel.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
};

const deleteHostel = (id: string) => {
    if (confirm('Are you sure you want to delete this hostel? All floors and rooms will be removed.')) {
        useForm({}).delete(route('admin.hostels.destroy', id), {
            onSuccess: () => {
            },
        });
    }
};

// --- Fee Configuration Logic ---
const isFeeModalOpen = ref(false);
const feeForm = useForm({
    session_id: '',
    hostel_id: '', // Empty means global default for that session
    amount: '',
});

const openFeeModal = () => {
    feeForm.reset();
    feeForm.clearErrors();
    // Default to latest session if available
    if (props.sessions && props.sessions.length > 0) {
        feeForm.session_id = props.sessions[0].id;
    }
    isFeeModalOpen.value = true;
};

const submitFee = () => {
    feeForm.post(route('admin.hostels.fees.store'), {
        onSuccess: () => {
            isFeeModalOpen.value = false;
        },
    });
};

const getGenderBadgeClass = (gender: string) => {
    if (gender === 'male') return 'bg-blue-100 text-blue-800 border-blue-200';
    if (gender === 'female') return 'bg-pink-100 text-pink-800 border-pink-200';
    return 'bg-purple-100 text-purple-800 border-purple-200';
};
</script>

<template>
    <Head title="Hostels Management" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Hostels Management</h2>
                    <p class="text-sm text-muted-foreground">Manage campus hostels, floors, rooms, and accommodation fees.</p>
                </div>
                <div class="flex space-x-2">
                    <Button @click="openFeeModal" variant="outline">
                        <Settings class="mr-2 h-4 w-4" />
                        Configure Fees
                    </Button>
                    <Button @click="openCreateModal">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Hostel
                    </Button>
                </div>
            </div>

            <!-- Hostels Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="hostel in hostels" :key="hostel.id" class="bg-card border rounded-lg shadow-sm overflow-hidden flex flex-col">
                    <div class="p-5 border-b flex justify-between items-start">
                        <div class="flex items-center space-x-3 cursor-pointer group" @click="$inertia.visit(route('admin.hostels.show', hostel.id))">
                            <div class="p-2 bg-primary/10 rounded-lg group-hover:bg-primary/20 transition-colors">
                                <Home class="h-6 w-6 text-primary" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg hover:underline">{{ hostel.name }}</h3>
                                <span :class="['text-xs px-2 py-0.5 rounded-full border', getGenderBadgeClass(hostel.gender_type)]">
                                    {{ hostel.gender_type.charAt(0).toUpperCase() + hostel.gender_type.slice(1) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-5 flex-1 cursor-pointer" @click="$inertia.visit(route('admin.hostels.show', hostel.id))">
                        <p class="text-sm text-muted-foreground line-clamp-2">{{ hostel.description || 'No description provided.' }}</p>
                        
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div class="bg-muted p-3 rounded-md text-center">
                                <p class="text-2xl font-bold">{{ hostel.floors_count }}</p>
                                <p class="text-xs text-muted-foreground uppercase tracking-wider">Floors</p>
                            </div>
                            <div class="bg-muted p-3 rounded-md text-center">
                                <p class="text-2xl font-bold">{{ hostel.fees_count }}</p>
                                <p class="text-xs text-muted-foreground uppercase tracking-wider">Fee Configs</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-muted/50 border-t flex justify-end space-x-2">
                        <Button variant="ghost" size="sm" @click="openEditModal(hostel)">
                            <Edit class="h-4 w-4 mr-1" /> Edit
                        </Button>
                        <Button variant="ghost" size="sm" class="text-destructive hover:bg-destructive/10 hover:text-destructive" @click="deleteHostel(hostel.id)">
                            <Trash2 class="h-4 w-4 mr-1" /> Delete
                        </Button>
                    </div>
                </div>

                <div v-if="hostels.length === 0" class="col-span-full flex flex-col items-center justify-center p-12 text-center bg-muted/30 border border-dashed rounded-lg">
                    <Home class="h-12 w-12 text-muted-foreground/50 mb-4" />
                    <h3 class="text-lg font-semibold text-foreground">No hostels found</h3>
                    <p class="text-sm text-muted-foreground mt-1 max-w-sm">You haven't added any campus hostels yet. Click the button above to create one.</p>
                </div>
            </div>
        </div>

        <!-- Create Hostel Modal -->
        <Dialog :open="isCreateModalOpen" @update:open="isCreateModalOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Add New Hostel</DialogTitle>
                    <DialogDescription>
                        Create a new hostel building in the system.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitCreate">
                    <div class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <Label for="name">Hostel Name</Label>
                            <Input id="name" v-model="form.name" placeholder="e.g. Block A, Mandela Hall" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="gender_type">Gender Allocation</Label>
                            <Select v-model="form.gender_type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="mixed">Mixed</SelectItem>
                                    <SelectItem value="male">Male Only</SelectItem>
                                    <SelectItem value="female">Female Only</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.gender_type" class="text-sm text-destructive">{{ form.errors.gender_type }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="description">Description (Optional)</Label>
                            <Textarea id="description" v-model="form.description" placeholder="Short details about the hostel..." rows="3" />
                            <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isCreateModalOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">Save Hostel</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Hostel Modal -->
        <Dialog :open="isEditModalOpen" @update:open="isEditModalOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Edit Hostel</DialogTitle>
                    <DialogDescription>
                        Modify hostel details.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEdit">
                    <div class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <Label for="edit_name">Hostel Name</Label>
                            <Input id="edit_name" v-model="form.name" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_gender_type">Gender Allocation</Label>
                            <Select v-model="form.gender_type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="mixed">Mixed</SelectItem>
                                    <SelectItem value="male">Male Only</SelectItem>
                                    <SelectItem value="female">Female Only</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.gender_type" class="text-sm text-destructive">{{ form.errors.gender_type }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_description">Description</Label>
                            <Textarea id="edit_description" v-model="form.description" rows="3" />
                            <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isEditModalOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">Update</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Configure Fees Modal -->
        <Dialog :open="isFeeModalOpen" @update:open="isFeeModalOpen = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Configure Hostel Fees</DialogTitle>
                    <DialogDescription>
                        Set default fees for an academic session or custom fees per hostel.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitFee">
                    <div class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <Label for="fee_session">Academic Session</Label>
                            <Select v-model="feeForm.session_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Session" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="session in sessions" :key="session.id" :value="session.id.toString()">
                                        {{ session.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="feeForm.errors.session_id" class="text-sm text-destructive">{{ feeForm.errors.session_id }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="fee_hostel">Target Hostel (Optional)</Label>
                            <Select v-model="feeForm.hostel_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Default for All Hostels" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">-- Default for All Hostels --</SelectItem>
                                    <SelectItem v-for="hostel in hostels" :key="hostel.id" :value="hostel.id.toString()">
                                        {{ hostel.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground mt-1">If left empty, this becomes the default fee for the session.</p>
                            <p v-if="feeForm.errors.hostel_id" class="text-sm text-destructive">{{ feeForm.errors.hostel_id }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="fee_amount">Fee Amount (₦)</Label>
                            <Input id="fee_amount" type="number" step="0.01" min="0" v-model="feeForm.amount" placeholder="e.g. 150000" />
                            <p v-if="feeForm.errors.amount" class="text-sm text-destructive">{{ feeForm.errors.amount }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isFeeModalOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="feeForm.processing">Save Configuration</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
