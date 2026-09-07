<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    Building2,
    Plus,
    Upload,
    Download,
    Search,
    Filter,
    Edit2,
    Trash2,
    CheckCircle2,
    XCircle,
    AlertCircle,
    Users,
    SlidersHorizontal,
    FileSpreadsheet,
    Building,
    BookCheck
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface BuildingItem {
    id: string;
    name: string;
    code: string;
    building_type: string;
    capacity: number;
    usable_for_exams: boolean;
    status: 'active' | 'under_maintenance' | 'inactive';
    description?: string;
}

const props = defineProps<{
    buildings: {
        data: BuildingItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    stats: {
        total_buildings: number;
        total_capacity: number;
        exam_usable_count: number;
        active_count: number;
    };
    filters: {
        search?: string;
        status?: string;
        building_type?: string;
        usable_for_exams?: string;
    };
    building_types: Array<{ value: string; label: string }>;
    userPermissions: {
        canCreate: boolean;
        canEdit: boolean;
        canDisable: boolean;
        canDelete: boolean;
    };
}>();

// Filter States
const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedType = ref(props.filters.building_type || '');
const selectedExamUsage = ref(props.filters.usable_for_exams || '');

const applyFilters = () => {
    router.get(
        '/admin/buildings',
        {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
            building_type: selectedType.value || undefined,
            usable_for_exams: selectedExamUsage.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedType.value = '';
    selectedExamUsage.value = '';
    applyFilters();
};

// Add / Edit Modal
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const editingBuilding = ref<BuildingItem | null>(null);

const form = useForm({
    name: '',
    code: '',
    building_type: 'classroom',
    capacity: 100,
    usable_for_exams: true,
    status: 'active',
    description: '',
});

const openCreateModal = () => {
    form.reset();
    form.clearErrors();
    form.building_type = 'classroom';
    form.capacity = 100;
    form.usable_for_exams = true;
    form.status = 'active';
    isCreateOpen.value = true;
};

const openEditModal = (building: BuildingItem) => {
    editingBuilding.value = building;
    form.clearErrors();
    form.name = building.name;
    form.code = building.code;
    form.building_type = building.building_type;
    form.capacity = building.capacity;
    form.usable_for_exams = building.usable_for_exams;
    form.status = building.status;
    form.description = building.description || '';
    isEditOpen.value = true;
};

const submitCreate = () => {
    form.post('/admin/buildings', {
        onSuccess: () => {
            isCreateOpen.value = false;
            form.reset();
        },
    });
};

const submitEdit = () => {
    if (!editingBuilding.value) return;
    form.put(`/admin/buildings/${editingBuilding.value.id}`, {
        onSuccess: () => {
            isEditOpen.value = false;
            form.reset();
        },
    });
};

const toggleExamStatus = (building: BuildingItem) => {
    router.post(`/admin/buildings/${building.id}/toggle-exam-status`, {}, { preserveScroll: true });
};

const toggleStatus = (building: BuildingItem) => {
    router.post(`/admin/buildings/${building.id}/toggle-status`, {}, { preserveScroll: true });
};

const deleteBuilding = (building: BuildingItem) => {
    if (confirm(`Are you sure you want to delete building '${building.name}'?`)) {
        router.delete(`/admin/buildings/${building.id}`, { preserveScroll: true });
    }
};

// Bulk Import Modal
const isImportOpen = ref(false);
const importForm = useForm({
    file: null as File | null,
});

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        importForm.file = target.files[0];
    }
};

const submitImport = () => {
    if (!importForm.file) return;
    importForm.post('/admin/buildings/import', {
        onSuccess: () => {
            isImportOpen.value = false;
            importForm.reset();
        },
    });
};

const getBuildingTypeLabel = (type: string) => {
    const found = props.building_types.find((t) => t.value === type);
    return found ? found.label : type.replace('_', ' ').toUpperCase();
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active':
            return { class: 'bg-emerald-500/10 text-emerald-600 border-emerald-200 dark:border-emerald-800 dark:text-emerald-400', label: 'Active' };
        case 'under_maintenance':
            return { class: 'bg-amber-500/10 text-amber-600 border-amber-200 dark:border-amber-800 dark:text-amber-400', label: 'Maintenance' };
        case 'inactive':
            return { class: 'bg-rose-500/10 text-rose-600 border-rose-200 dark:border-rose-800 dark:text-rose-400', label: 'Inactive' };
        default:
            return { class: 'bg-slate-500/10 text-slate-600 border-slate-200', label: status };
    }
};
</script>

<template>
    <Head title="Campus Buildings & Halls" />

    <AdminLayout :breadcrumbs="[{ title: 'Academic Infrastructure', href: '#' }, { title: 'Campus Buildings', href: '/admin/buildings' }]">
        <div class="space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200/80 dark:border-zinc-800 shadow-sm">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <Building2 class="w-7 h-7 text-indigo-600 dark:text-indigo-400" />
                        Campus Buildings & Examination Halls
                    </h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                        Manage university buildings, lecture theatres, CBT centers, seating capacities, and exam eligibility flags.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a
                        href="/admin/buildings/template"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-zinc-700 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700 rounded-lg transition-colors"
                    >
                        <Download class="w-4 h-4" />
                        Download Template
                    </a>
                    <Button
                        v-if="userPermissions.canCreate"
                        variant="outline"
                        @click="isImportOpen = true"
                        class="flex items-center gap-2 border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/50"
                    >
                        <Upload class="w-4 h-4" />
                        Bulk CSV Import
                    </Button>
                    <Button
                        v-if="userPermissions.canCreate"
                        @click="openCreateModal"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white flex items-center gap-2 shadow-sm"
                    >
                        <Plus class="w-4 h-4" />
                        Add Building
                    </Button>
                </div>
            </div>

            <!-- KPI Metric Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200/80 dark:border-zinc-800 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Buildings</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ stats.total_buildings }}</p>
                    </div>
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-950/50 rounded-xl text-indigo-600 dark:text-indigo-400">
                        <Building2 class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200/80 dark:border-zinc-800 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Exam Ready Halls</p>
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.exam_usable_count }}</p>
                    </div>
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl text-emerald-600 dark:text-emerald-400">
                        <BookCheck class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200/80 dark:border-zinc-800 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Seating Capacity</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ stats.total_capacity.toLocaleString() }} seats</p>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-950/50 rounded-xl text-blue-600 dark:text-blue-400">
                        <Users class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-zinc-200/80 dark:border-zinc-800 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Active Facilities</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ stats.active_count }}</p>
                    </div>
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/50 rounded-xl text-amber-600 dark:text-amber-400">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200/80 dark:border-zinc-800 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="relative">
                        <Search class="w-4 h-4 absolute left-3 top-3 text-zinc-400" />
                        <Input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            placeholder="Search name or code..."
                            class="pl-9 bg-zinc-50 dark:bg-zinc-800/50 border-zinc-200 dark:border-zinc-700"
                        />
                    </div>

                    <div>
                        <select
                            v-model="selectedType"
                            @change="applyFilters"
                            class="w-full h-10 px-3 py-2 text-sm rounded-md border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">All Facility Types</option>
                            <option v-for="t in building_types" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>

                    <div>
                        <select
                            v-model="selectedExamUsage"
                            @change="applyFilters"
                            class="w-full h-10 px-3 py-2 text-sm rounded-md border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">All Exam Status</option>
                            <option value="true">Exam Ready Only</option>
                            <option value="false">Not For Exams</option>
                        </select>
                    </div>

                    <div>
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="w-full h-10 px-3 py-2 text-sm rounded-md border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">All Operating Statuses</option>
                            <option value="active">Active</option>
                            <option value="under_maintenance">Under Maintenance</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-1 border-t border-zinc-100 dark:border-zinc-800/50">
                    <Button variant="ghost" size="sm" @click="resetFilters" class="text-xs text-zinc-500 hover:text-zinc-700">
                        Reset Filters
                    </Button>
                    <Button size="sm" @click="applyFilters" class="bg-zinc-900 dark:bg-zinc-100 dark:text-zinc-900 text-xs">
                        <Filter class="w-3.5 h-3.5 mr-1" /> Filter
                    </Button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200/80 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-zinc-50/80 dark:bg-zinc-800/50 border-b border-zinc-200/80 dark:border-zinc-800 text-zinc-500 dark:text-zinc-400 font-semibold uppercase text-xs tracking-wider">
                                <th class="p-4">Building Name</th>
                                <th class="p-4">Code Slug</th>
                                <th class="p-4">Facility Type</th>
                                <th class="p-4">Capacity</th>
                                <th class="p-4">Exam Usage</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200/80 dark:divide-zinc-800">
                            <tr v-if="buildings.data.length === 0">
                                <td colspan="7" class="p-8 text-center text-zinc-500 dark:text-zinc-400">
                                    No campus buildings found matching your criteria.
                                </td>
                            </tr>
                            <tr
                                v-for="b in buildings.data"
                                :key="b.id"
                                class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors"
                            >
                                <td class="p-4">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ b.name }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-xs" v-if="b.description">
                                        {{ b.description }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <Badge variant="outline" class="font-mono text-xs uppercase bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 border-zinc-300 dark:border-zinc-700">
                                        {{ b.code }}
                                    </Badge>
                                </td>
                                <td class="p-4 text-zinc-600 dark:text-zinc-300">
                                    {{ getBuildingTypeLabel(b.building_type) }}
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1 font-semibold text-zinc-900 dark:text-zinc-100">
                                        <Users class="w-3.5 h-3.5 text-zinc-400" />
                                        {{ b.capacity }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <button
                                        v-if="userPermissions.canEdit"
                                        @click="toggleExamStatus(b)"
                                        class="cursor-pointer transition-transform hover:scale-105"
                                        title="Click to toggle exam eligibility"
                                    >
                                        <Badge
                                            v-if="b.usable_for_exams"
                                            class="bg-emerald-500/10 text-emerald-700 border-emerald-200 dark:border-emerald-800 dark:text-emerald-400 hover:bg-emerald-500/20"
                                        >
                                            <CheckCircle2 class="w-3 h-3 mr-1" /> Exam Ready
                                        </Badge>
                                        <Badge
                                            v-else
                                            class="bg-zinc-100 text-zinc-500 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700 hover:bg-zinc-200"
                                        >
                                            <XCircle class="w-3 h-3 mr-1" /> Not For Exams
                                        </Badge>
                                    </button>
                                    <template v-else>
                                        <Badge
                                            v-if="b.usable_for_exams"
                                            class="bg-emerald-500/10 text-emerald-700 border-emerald-200 dark:border-emerald-800 dark:text-emerald-400"
                                        >
                                            Exam Ready
                                        </Badge>
                                        <Badge v-else class="bg-zinc-100 text-zinc-500">
                                            Not For Exams
                                        </Badge>
                                    </template>
                                </td>
                                <td class="p-4">
                                    <Badge :class="getStatusBadge(b.status).class">
                                        {{ getStatusBadge(b.status).label }}
                                    </Badge>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            v-if="userPermissions.canEdit"
                                            variant="ghost"
                                            size="sm"
                                            @click="openEditModal(b)"
                                            class="h-8 w-8 p-0 text-zinc-600 hover:text-indigo-600"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </Button>
                                        <Button
                                            v-if="userPermissions.canDisable"
                                            variant="ghost"
                                            size="sm"
                                            @click="toggleStatus(b)"
                                            class="h-8 w-8 p-0 text-zinc-600 hover:text-amber-600"
                                            :title="b.status === 'active' ? 'Disable Building' : 'Activate Building'"
                                        >
                                            <SlidersHorizontal class="w-4 h-4" />
                                        </Button>
                                        <Button
                                            v-if="userPermissions.canDelete"
                                            variant="ghost"
                                            size="sm"
                                            @click="deleteBuilding(b)"
                                            class="h-8 w-8 p-0 text-zinc-600 hover:text-rose-600"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="p-4 border-t border-zinc-200/80 dark:border-zinc-800 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-900/50">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Showing page <span class="font-medium">{{ buildings.current_page }}</span> of <span class="font-medium">{{ buildings.last_page }}</span> ({{ buildings.total }} items)
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in buildings.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-2.5 py-1 text-xs rounded-md transition-colors"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white font-medium' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-800',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Building Modal -->
        <Dialog :open="isCreateOpen" @update:open="isCreateOpen = $event">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Plus class="w-5 h-5 text-indigo-600" /> Add New Campus Building
                    </DialogTitle>
                    <DialogDescription>
                        Create a university facility or examination venue record.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="space-y-4 py-2">
                    <div>
                        <Label for="create-name">Building / Hall Name *</Label>
                        <Input id="create-name" v-model="form.name" placeholder="e.g. Multipurpose Hall A" required class="mt-1" />
                        <span v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</span>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <Label for="create-code">Building Code (Optional)</Label>
                            <span class="text-xs text-zinc-400">Auto-slugified in UPPERCASE if left blank</span>
                        </div>
                        <Input id="create-code" v-model="form.code" placeholder="e.g. MPH-A" class="mt-1 font-mono uppercase" />
                        <span v-if="form.errors.code" class="text-xs text-rose-500 mt-1">{{ form.errors.code }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label for="create-type">Facility Type *</Label>
                            <select
                                id="create-type"
                                v-model="form.building_type"
                                class="w-full mt-1 h-10 px-3 py-2 text-sm rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
                                required
                            >
                                <option v-for="t in building_types" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                            <span v-if="form.errors.building_type" class="text-xs text-rose-500 mt-1">{{ form.errors.building_type }}</span>
                        </div>

                        <div>
                            <Label for="create-capacity">Seating Capacity *</Label>
                            <Input id="create-capacity" type="number" v-model.number="form.capacity" min="1" required class="mt-1" />
                            <span v-if="form.errors.capacity" class="text-xs text-rose-500 mt-1">{{ form.errors.capacity }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div>
                            <Label for="create-status">Operating Status *</Label>
                            <select
                                id="create-status"
                                v-model="form.status"
                                class="w-full mt-1 h-10 px-3 py-2 text-sm rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
                                required
                            >
                                <option value="active">Active</option>
                                <option value="under_maintenance">Under Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 pt-6">
                            <input
                                id="create-exam"
                                type="checkbox"
                                v-model="form.usable_for_exams"
                                class="w-4 h-4 text-indigo-600 rounded border-zinc-300 focus:ring-indigo-500"
                            />
                            <Label for="create-exam" class="cursor-pointer text-xs font-medium">Usable for Examinations</Label>
                        </div>
                    </div>

                    <div>
                        <Label for="create-desc">Description / Notes</Label>
                        <textarea
                            id="create-desc"
                            v-model="form.description"
                            rows="3"
                            placeholder="Additional details regarding equipment, location, or partition setup..."
                            class="w-full mt-1 px-3 py-2 text-sm rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <DialogFooter class="pt-3">
                        <Button type="button" variant="outline" @click="isCreateOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white">Save Building</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Building Modal -->
        <Dialog :open="isEditOpen" @update:open="isEditOpen = $event">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Edit2 class="w-5 h-5 text-indigo-600" /> Edit Building Record
                    </DialogTitle>
                    <DialogDescription>
                        Update facility information and seating capacity.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4 py-2">
                    <div>
                        <Label for="edit-name">Building / Hall Name *</Label>
                        <Input id="edit-name" v-model="form.name" required class="mt-1" />
                        <span v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</span>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <Label for="edit-code">Building Code</Label>
                            <span class="text-xs text-zinc-400">UPPERCASE Code</span>
                        </div>
                        <Input id="edit-code" v-model="form.code" class="mt-1 font-mono uppercase" />
                        <span v-if="form.errors.code" class="text-xs text-rose-500 mt-1">{{ form.errors.code }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label for="edit-type">Facility Type *</Label>
                            <select
                                id="edit-type"
                                v-model="form.building_type"
                                class="w-full mt-1 h-10 px-3 py-2 text-sm rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
                                required
                            >
                                <option v-for="t in building_types" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>

                        <div>
                            <Label for="edit-capacity">Seating Capacity *</Label>
                            <Input id="edit-capacity" type="number" v-model.number="form.capacity" min="1" required class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div>
                            <Label for="edit-status">Operating Status *</Label>
                            <select
                                id="edit-status"
                                v-model="form.status"
                                class="w-full mt-1 h-10 px-3 py-2 text-sm rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
                                required
                            >
                                <option value="active">Active</option>
                                <option value="under_maintenance">Under Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 pt-6">
                            <input
                                id="edit-exam"
                                type="checkbox"
                                v-model="form.usable_for_exams"
                                class="w-4 h-4 text-indigo-600 rounded border-zinc-300 focus:ring-indigo-500"
                            />
                            <Label for="edit-exam" class="cursor-pointer text-xs font-medium">Usable for Examinations</Label>
                        </div>
                    </div>

                    <div>
                        <Label for="edit-desc">Description / Notes</Label>
                        <textarea
                            id="edit-desc"
                            v-model="form.description"
                            rows="3"
                            class="w-full mt-1 px-3 py-2 text-sm rounded-md border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <DialogFooter class="pt-3">
                        <Button type="button" variant="outline" @click="isEditOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white">Update Building</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Bulk CSV Import Modal -->
        <Dialog :open="isImportOpen" @update:open="isImportOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <FileSpreadsheet class="w-5 h-5 text-indigo-600" /> Bulk Import Campus Buildings
                    </DialogTitle>
                    <DialogDescription>
                        Upload a CSV or Excel spreadsheet containing campus building records.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitImport" class="space-y-4 py-2">
                    <div class="p-4 bg-indigo-50/50 dark:bg-indigo-950/30 rounded-lg border border-indigo-100 dark:border-indigo-900/50 text-xs text-indigo-900 dark:text-indigo-300 space-y-1">
                        <p class="font-semibold flex items-center gap-1.5">
                            <AlertCircle class="w-4 h-4" /> CSV File Format Guidelines:
                        </p>
                        <ul class="list-disc pl-5 space-y-0.5 text-indigo-800 dark:text-indigo-400">
                            <li>Required columns: <code class="font-bold">name</code></li>
                            <li>Optional columns: <code class="font-bold">code</code>, <code class="font-bold">building_type</code>, <code class="font-bold">capacity</code>, <code class="font-bold">usable_for_exams</code>, <code class="font-bold">status</code>, <code class="font-bold">description</code></li>
                            <li>If <code class="font-bold">code</code> is left blank, an uppercase slug will be automatically created.</li>
                        </ul>
                        <div class="pt-2">
                            <a
                                href="/admin/buildings/template"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 font-semibold underline hover:text-indigo-800"
                            >
                                <Download class="w-3.5 h-3.5" /> Download Sample CSV Template
                            </a>
                        </div>
                    </div>

                    <div>
                        <Label for="csv-file">Select Spreadsheet File (.csv, .xlsx)</Label>
                        <Input
                            id="csv-file"
                            type="file"
                            accept=".csv,.xlsx,.xls,.txt"
                            @change="handleFileUpload"
                            required
                            class="mt-1"
                        />
                        <span v-if="importForm.errors.file" class="text-xs text-rose-500 mt-1">{{ importForm.errors.file }}</span>
                    </div>

                    <DialogFooter class="pt-3">
                        <Button type="button" variant="outline" @click="isImportOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="importForm.processing || !importForm.file" class="bg-indigo-600 text-white">
                            Upload & Import
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
