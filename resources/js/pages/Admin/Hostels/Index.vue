<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Edit, Trash2, Home, Eye, EyeOff } from 'lucide-vue-next';
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
        is_visible: boolean;
        floors_count: number;
        fees_count: number;
        created_at: string;
    }>;
    sessions: Array<{
        id: string;
        name: string;
    }>;
}>();

const page = usePage();
const hasPermission = (permission: string) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    
    // Admins have all permissions
    if (user.roles?.includes('admin')) return true;
    
    return user.permissions?.includes(permission);
};

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

const toggleHostelVisibility = (id: string) => {
    router.post(route('admin.hostels.toggle-visibility', id), {}, {
        preserveScroll: true,
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
                    <p class="text-sm text-muted-foreground">Manage campus hostels, floors, and rooms.</p>
                </div>
                <div class="flex space-x-2">
                    <Button v-if="hasPermission('create_hostels')" @click="openCreateModal">
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
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span :class="['text-xs px-2 py-0.5 rounded-full border', getGenderBadgeClass(hostel.gender_type)]">
                                        {{ hostel.gender_type.charAt(0).toUpperCase() + hostel.gender_type.slice(1) }}
                                    </span>
                                    <span v-if="!hostel.is_visible" class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">
                                        Hidden
                                    </span>
                                </div>
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
                        <Button v-if="hasPermission('toggle_hostels')" variant="ghost" size="sm" @click="toggleHostelVisibility(hostel.id)" :title="hostel.is_visible ? 'Hide Hostel' : 'Show Hostel'">
                            <component :is="hostel.is_visible ? EyeOff : Eye" class="h-4 w-4 mr-1" />
                            {{ hostel.is_visible ? 'Hide' : 'Show' }}
                        </Button>
                        <Button v-if="hasPermission('create_hostels')" variant="ghost" size="sm" @click="openEditModal(hostel)">
                            <Edit class="h-4 w-4 mr-1" /> Edit
                        </Button>
                        <Button v-if="hasPermission('create_hostels')" variant="ghost" size="sm" class="text-destructive hover:bg-destructive/10 hover:text-destructive" @click="deleteHostel(hostel.id)">
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
    </AdminLayout>
</template>
