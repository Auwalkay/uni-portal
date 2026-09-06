<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { 
    Plus, Edit, Trash2, Home, Eye, EyeOff, Search, SlidersHorizontal, ArrowUpDown, X, 
    Building, DoorOpen, Bed, Layers, Users, UserCheck, ShieldCheck, Sparkles, Receipt,
    LayoutList, LayoutGrid, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, ExternalLink,
    FileSpreadsheet, Upload, Download
} from 'lucide-vue-next';
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
        blocks?: Array<{
            id: string;
            name: string;
            floors: Array<{
                id: string;
                name: string;
                rooms: Array<{
                    id: string;
                    room_number: string;
                    capacity: number;
                    bookings?: Array<any>;
                }>;
            }>;
        }>;
    }>;
    sessions: Array<{
        id: string;
        name: string;
    }>;
    currentSession?: {
        id: string;
        name: string;
    };
}>();

const page = usePage();
const hasPermission = (permission: string) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    
    // Admins have all permissions
    if (user.roles?.includes('admin')) return true;
    
    return user.permissions?.includes(permission);
};

// View Mode & Pagination State
const viewMode = ref<'table' | 'grid'>('table');
const search = ref('');
const genderFilter = ref('all');
const visibilityFilter = ref('all');
const sortBy = ref('name-asc');

const currentPage = ref(1);
const perPage = ref('10');
const perPageNum = computed(() => Number(perPage.value) || 10);

// Modals State
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingHostel = ref<any>(null);

const form = useForm({
    name: '',
    gender_type: 'mixed',
    description: '',
});

// Capacity and Vacancy helper calculations
const getHostelTotalCapacity = (hostel: any) => {
    if (!hostel.blocks) return 0;
    return hostel.blocks.reduce((sum: number, block: any) => {
        return sum + block.floors.reduce((fSum: number, floor: any) => {
            return fSum + floor.rooms.reduce((rSum: number, room: any) => rSum + (room.capacity || 0), 0);
        }, 0);
    }, 0);
};

const getHostelTotalRooms = (hostel: any) => {
    if (!hostel.blocks) return 0;
    return hostel.blocks.reduce((sum: number, block: any) => {
        return sum + block.floors.reduce((fSum: number, floor: any) => fSum + floor.rooms.length, 0);
    }, 0);
};

const getHostelOccupiedBeds = (hostel: any) => {
    if (!hostel.blocks) return 0;
    return hostel.blocks.reduce((sum: number, block: any) => {
        return sum + block.floors.reduce((fSum: number, floor: any) => {
            return fSum + floor.rooms.reduce((rSum: number, room: any) => {
                const booked = room.bookings ? room.bookings.length : 0;
                return rSum + Math.min(room.capacity || 0, booked);
            }, 0);
        }, 0);
    }, 0);
};

const getHostelOccupiedRooms = (hostel: any) => {
    if (!hostel.blocks) return 0;
    return hostel.blocks.reduce((sum: number, block: any) => {
        return sum + block.floors.reduce((fSum: number, floor: any) => {
            return fSum + floor.rooms.reduce((rSum: number, room: any) => {
                const booked = room.bookings ? room.bookings.length : 0;
                return rSum + (booked > 0 ? 1 : 0);
            }, 0);
        }, 0);
    }, 0);
};

const getHostelVacantBeds = (hostel: any) => {
    const cap = getHostelTotalCapacity(hostel);
    const occ = getHostelOccupiedBeds(hostel);
    return Math.max(0, cap - occ);
};

// Global Metrics
const totalHostels = computed(() => props.hostels.length);
const totalRoomsAll = computed(() => props.hostels.reduce((sum, h) => sum + getHostelTotalRooms(h), 0));
const occupiedRoomsAll = computed(() => props.hostels.reduce((sum, h) => sum + getHostelOccupiedRooms(h), 0));
const totalCapacityAll = computed(() => props.hostels.reduce((sum, h) => sum + getHostelTotalCapacity(h), 0));
const totalVacantAll = computed(() => props.hostels.reduce((sum, h) => sum + getHostelVacantBeds(h), 0));
const maleHostelsCount = computed(() => props.hostels.filter(h => h.gender_type === 'male').length);
const femaleHostelsCount = computed(() => props.hostels.filter(h => h.gender_type === 'female').length);
const mixedHostelsCount = computed(() => props.hostels.filter(h => h.gender_type === 'mixed').length);

// Filtered & Sorted Hostels
const filteredHostels = computed(() => {
    let list = [...props.hostels];

    // Search Query
    if (search.value.trim()) {
        const q = search.value.toLowerCase().trim();
        list = list.filter(h => 
            h.name.toLowerCase().includes(q) || 
            (h.description && h.description.toLowerCase().includes(q))
        );
    }

    // Gender Filter
    if (genderFilter.value !== 'all') {
        list = list.filter(h => h.gender_type === genderFilter.value);
    }

    // Visibility Filter
    if (visibilityFilter.value !== 'all') {
        const isVis = visibilityFilter.value === 'visible';
        list = list.filter(h => h.is_visible === isVis);
    }

    // Sorting
    list.sort((a, b) => {
        if (sortBy.value === 'name-asc') return a.name.localeCompare(b.name);
        if (sortBy.value === 'name-desc') return b.name.localeCompare(a.name);
        if (sortBy.value === 'capacity-desc') return getHostelTotalCapacity(b) - getHostelTotalCapacity(a);
        if (sortBy.value === 'vacant-desc') return getHostelVacantBeds(b) - getHostelVacantBeds(a);
        if (sortBy.value === 'created-desc') return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        if (sortBy.value === 'created-asc') return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
        return 0;
    });

    return list;
});

// Pagination Calculations
const totalPages = computed(() => Math.ceil(filteredHostels.value.length / perPageNum.value) || 1);

const paginatedHostels = computed(() => {
    const start = (currentPage.value - 1) * perPageNum.value;
    return filteredHostels.value.slice(start, start + perPageNum.value);
});

const startItemIndex = computed(() => {
    if (filteredHostels.value.length === 0) return 0;
    return (currentPage.value - 1) * perPageNum.value + 1;
});

const endItemIndex = computed(() => {
    return Math.min(currentPage.value * perPageNum.value, filteredHostels.value.length);
});

const resetFilters = () => {
    search.value = '';
    genderFilter.value = 'all';
    visibilityFilter.value = 'all';
    sortBy.value = 'name-asc';
    currentPage.value = 1;
};

const handleSortChange = (newSort: string) => {
    sortBy.value = newSort;
    currentPage.value = 1;
};

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
            onSuccess: () => {},
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

const roomImportModalOpen = ref(false);
const roomImportForm = useForm({
    hostel_id: 'all',
    block_id: 'all',
    floor_id: 'all',
    file: null as File | null,
});

const availableBlocksForImport = computed(() => {
    if (!roomImportForm.hostel_id || roomImportForm.hostel_id === 'all') return [];
    const selectedHostel = props.hostels.find(h => h.id === roomImportForm.hostel_id);
    return selectedHostel?.blocks || [];
});

const availableFloorsForImport = computed(() => {
    if (!roomImportForm.block_id || roomImportForm.block_id === 'all') return [];
    const selectedBlock = availableBlocksForImport.value.find(b => b.id === roomImportForm.block_id);
    return selectedBlock?.floors || [];
});

watch(() => roomImportForm.hostel_id, () => {
    roomImportForm.block_id = 'all';
    roomImportForm.floor_id = 'all';
});

watch(() => roomImportForm.block_id, () => {
    roomImportForm.floor_id = 'all';
});

const openRoomImportModal = () => {
    roomImportForm.reset();
    roomImportForm.clearErrors();
    roomImportForm.hostel_id = 'all';
    roomImportForm.block_id = 'all';
    roomImportForm.floor_id = 'all';
    roomImportModalOpen.value = true;
};

const handleRoomImportFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        roomImportForm.file = target.files[0];
    }
};

const submitRoomImport = () => {
    if (!roomImportForm.file) return;
    router.post(route('admin.hostels.rooms.import'), {
        hostel_id: roomImportForm.hostel_id === 'all' ? '' : roomImportForm.hostel_id,
        block_id: roomImportForm.block_id === 'all' ? '' : roomImportForm.block_id,
        floor_id: roomImportForm.floor_id === 'all' ? '' : roomImportForm.floor_id,
        file: roomImportForm.file,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            roomImportModalOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="Hostels Management" />

    <AdminLayout>
        <div class="space-y-8 p-6 lg:p-10 max-w-[1600px] mx-auto">
            <!-- Header Ribbon -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-card border rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <Badge variant="outline" class="uppercase font-bold tracking-wider text-xs border-primary text-primary px-3 py-1">
                            Campus Infrastructure
                        </Badge>
                        <Badge v-if="currentSession" variant="secondary" class="font-bold text-xs">
                            Session: {{ currentSession.name }}
                        </Badge>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl flex items-center gap-3">
                        <Building class="h-8 w-8 text-primary" />
                        Hostels Directory
                    </h1>
                    <p class="text-muted-foreground max-w-2xl text-base">
                        Central directory for campus hostels. Monitor live bed capacities, occupancy, and room allocations.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Button v-if="hasPermission('create_hostels')" @click="openRoomImportModal" variant="outline" class="rounded-xl px-5 h-12 border-emerald-300 text-emerald-800 bg-emerald-50 hover:bg-emerald-100 font-bold">
                        <FileSpreadsheet class="mr-2 h-5 w-5 text-emerald-600" /> Import Rooms (Excel)
                    </Button>
                    <Button v-if="hasPermission('create_hostels')" @click="openCreateModal" class="rounded-xl shadow-md font-semibold px-6 h-12 text-base">
                        <Plus class="mr-2 h-5 w-5" /> Add New Hostel
                    </Button>
                </div>
            </div>

            <!-- Global Summary Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                        <Building class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Total Hostels</p>
                        <h3 class="text-2xl font-black text-foreground">{{ totalHostels }}</h3>
                        <p class="text-[11px] text-muted-foreground mt-0.5">
                            {{ maleHostelsCount }} Male • {{ femaleHostelsCount }} Female • {{ mixedHostelsCount }} Mixed
                        </p>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <DoorOpen class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Rooms & Occupied</p>
                        <h3 class="text-2xl font-black text-foreground">{{ occupiedRoomsAll }} <span class="text-xs font-bold text-muted-foreground">/ {{ totalRoomsAll }} Rooms</span></h3>
                        <p class="text-[11px] text-muted-foreground mt-0.5">{{ totalCapacityAll }} Total Beds Capacity</p>
                    </div>
                </div>

                <div class="bg-emerald-500/10 border-2 border-emerald-500/30 rounded-2xl p-6 flex items-center space-x-4 shadow-sm">
                    <div class="h-14 w-14 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shadow-md">
                        <UserCheck class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Vacant Available</p>
                        <h3 class="text-2xl font-black text-emerald-700 dark:text-emerald-400">{{ totalVacantAll }} <span class="text-xs font-bold text-emerald-600">Beds Open</span></h3>
                        <p class="text-[11px] text-emerald-700/80 font-medium mt-0.5">Ready for student booking</p>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <Receipt class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Accommodation Actions</p>
                        <Button variant="outline" size="sm" class="mt-1 font-bold text-xs rounded-lg" @click="router.visit(route('admin.hostels.bookings.index'))">
                            Manage Allocations →
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Control Bar: Search, Filters, Sort & View Mode Switcher -->
            <div class="bg-card border rounded-3xl p-5 shadow-sm space-y-4">
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input 
                            v-model="search" 
                            placeholder="Search hostel name or description..." 
                            class="pl-10 pr-9 h-11 rounded-xl bg-background"
                            @input="currentPage = 1"
                        />
                        <button 
                            v-if="search" 
                            @click="search = ''; currentPage = 1;" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Filters & Controls Grid -->
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Gender Filter -->
                        <div class="w-40">
                            <Select v-model="genderFilter" @update:model-value="currentPage = 1">
                                <SelectTrigger class="h-11 rounded-xl bg-background">
                                    <SelectValue placeholder="Gender" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Genders</SelectItem>
                                    <SelectItem value="male">Male Only</SelectItem>
                                    <SelectItem value="female">Female Only</SelectItem>
                                    <SelectItem value="mixed">Mixed Residence</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Visibility Filter -->
                        <div class="w-36">
                            <Select v-model="visibilityFilter" @update:model-value="currentPage = 1">
                                <SelectTrigger class="h-11 rounded-xl bg-background">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="visible">Visible Only</SelectItem>
                                    <SelectItem value="hidden">Hidden Only</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Sort By -->
                        <div class="w-48">
                            <Select v-model="sortBy" @update:model-value="currentPage = 1">
                                <SelectTrigger class="h-11 rounded-xl bg-background">
                                    <SelectValue placeholder="Sort By" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="name-asc">Name (A - Z)</SelectItem>
                                    <SelectItem value="name-desc">Name (Z - A)</SelectItem>
                                    <SelectItem value="capacity-desc">Capacity (Highest First)</SelectItem>
                                    <SelectItem value="vacant-desc">Vacant Beds (Most Open)</SelectItem>
                                    <SelectItem value="created-desc">Newest Created</SelectItem>
                                    <SelectItem value="created-asc">Oldest Created</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- View Mode Switcher -->
                        <div class="flex items-center border rounded-xl p-1 bg-muted/40 shrink-0">
                            <button 
                                @click="viewMode = 'table'"
                                :class="[
                                    'p-2 rounded-lg transition-all flex items-center gap-1.5 text-xs font-bold',
                                    viewMode === 'table' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'
                                ]"
                                title="Data Table View"
                            >
                                <LayoutList class="h-4 w-4" />
                                <span class="hidden sm:inline">Table</span>
                            </button>
                            <button 
                                @click="viewMode = 'grid'"
                                :class="[
                                    'p-2 rounded-lg transition-all flex items-center gap-1.5 text-xs font-bold',
                                    viewMode === 'grid' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'
                                ]"
                                title="Grid Cards View"
                            >
                                <LayoutGrid class="h-4 w-4" />
                                <span class="hidden sm:inline">Cards</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Info Bar -->
                <div v-if="search || genderFilter !== 'all' || visibilityFilter !== 'all' || sortBy !== 'name-asc'" class="flex items-center justify-between pt-3 border-t text-xs">
                    <div class="flex items-center space-x-2 text-muted-foreground font-medium">
                        <span>Showing <strong>{{ startItemIndex }} - {{ endItemIndex }}</strong> of <strong>{{ filteredHostels.length }}</strong> matching hostels</span>
                    </div>
                    <Button variant="ghost" size="sm" @click="resetFilters" class="h-7 text-xs text-destructive hover:bg-destructive/10">
                        <X class="h-3.5 w-3.5 mr-1" /> Clear All Filters
                    </Button>
                </div>
            </div>

            <!-- Scalable Data Table View (Primary view for 30+ hostels) -->
            <div v-if="viewMode === 'table' && paginatedHostels.length > 0" class="bg-card border rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-muted/40 border-b text-xs font-bold text-muted-foreground uppercase tracking-wider">
                            <tr>
                                <th class="py-4 px-6 cursor-pointer hover:text-foreground" @click="handleSortChange(sortBy === 'name-asc' ? 'name-desc' : 'name-asc')">
                                    <div class="flex items-center space-x-1">
                                        <span>Hostel Name</span>
                                        <ArrowUpDown class="h-3.5 w-3.5" />
                                    </div>
                                </th>
                                <th class="py-4 px-6">Gender</th>
                                <th class="py-4 px-6">Status</th>
                                <th class="py-4 px-6">Structure</th>
                                <th class="py-4 px-6 cursor-pointer hover:text-foreground" @click="handleSortChange(sortBy === 'capacity-desc' ? 'vacant-desc' : 'capacity-desc')">
                                    <div class="flex items-center space-x-1">
                                        <span>Capacity & Vacancy</span>
                                        <ArrowUpDown class="h-3.5 w-3.5" />
                                    </div>
                                </th>
                                <th class="py-4 px-6 text-center">Fee Configs</th>
                                <th class="py-4 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr 
                                v-for="hostel in paginatedHostels" 
                                :key="hostel.id"
                                class="hover:bg-muted/30 transition-colors group cursor-pointer"
                                @click="router.visit(route('admin.hostels.show', hostel.id))"
                            >
                                <!-- Name & Description -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2.5 bg-primary/10 text-primary rounded-xl shrink-0 group-hover:bg-primary group-hover:text-primary-foreground transition-all">
                                            <Home class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-foreground text-base group-hover:text-primary transition-colors">
                                                {{ hostel.name }}
                                            </p>
                                            <p class="text-xs text-muted-foreground line-clamp-1 max-w-sm">
                                                {{ hostel.description || 'No detailed description provided.' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Gender Badge -->
                                <td class="py-4 px-6">
                                    <span :class="['text-xs font-bold px-2.5 py-1 rounded-full border uppercase tracking-wider', getGenderBadgeClass(hostel.gender_type)]">
                                        {{ hostel.gender_type }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-6">
                                    <Badge :variant="hostel.is_visible ? 'default' : 'secondary'" class="font-bold text-xs">
                                        {{ hostel.is_visible ? 'VISIBLE' : 'HIDDEN' }}
                                    </Badge>
                                </td>

                                <!-- Structure -->
                                <td class="py-4 px-6">
                                    <div class="text-xs font-medium space-y-0.5">
                                        <p class="font-bold text-foreground">{{ hostel.floors_count }} Floors</p>
                                        <p class="text-muted-foreground">{{ getHostelTotalRooms(hostel) }} Rooms</p>
                                    </div>
                                </td>

                                <!-- Capacity & Vacancy Progress -->
                                <td class="py-4 px-6 min-w-[200px]">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between text-xs font-bold">
                                            <span class="text-emerald-700 dark:text-emerald-400 font-extrabold">
                                                {{ getHostelVacantBeds(hostel) }} Vacant Beds
                                            </span>
                                            <span class="text-muted-foreground">
                                                {{ getHostelOccupiedBeds(hostel) }} / {{ getHostelTotalCapacity(hostel) }} Beds
                                            </span>
                                        </div>

                                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border">
                                            <div 
                                                class="h-full bg-emerald-500 transition-all duration-500"
                                                :style="{ width: `${getHostelTotalCapacity(hostel) > 0 ? Math.min(100, (getHostelOccupiedBeds(hostel) / getHostelTotalCapacity(hostel)) * 100) : 0}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Fee Configs -->
                                <td class="py-4 px-6 text-center">
                                    <Badge variant="outline" class="font-mono font-bold">
                                        {{ hostel.fees_count }} Configs
                                    </Badge>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-6 text-right" @click.stop>
                                    <div class="flex items-center justify-end space-x-2">
                                        <Button 
                                            variant="outline" 
                                            size="sm" 
                                            class="font-bold text-xs gap-1 rounded-xl"
                                            @click="router.visit(route('admin.hostels.show', hostel.id))"
                                        >
                                            View Rooms <ExternalLink class="h-3.5 w-3.5" />
                                        </Button>

                                        <Button 
                                            v-if="hasPermission('toggle_hostels')" 
                                            variant="ghost" 
                                            size="icon" 
                                            class="h-8 w-8 rounded-lg"
                                            @click="toggleHostelVisibility(hostel.id)"
                                            :title="hostel.is_visible ? 'Hide Hostel' : 'Show Hostel'"
                                        >
                                            <component :is="hostel.is_visible ? EyeOff : Eye" class="h-4 w-4" />
                                        </Button>

                                        <Button 
                                            v-if="hasPermission('create_hostels')" 
                                            variant="ghost" 
                                            size="icon" 
                                            class="h-8 w-8 rounded-lg"
                                            @click="openEditModal(hostel)"
                                            title="Edit Hostel"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>

                                        <Button 
                                            v-if="hasPermission('create_hostels')" 
                                            variant="ghost" 
                                            size="icon" 
                                            class="h-8 w-8 rounded-lg text-destructive hover:bg-destructive/10"
                                            @click="deleteHostel(hostel.id)"
                                            title="Delete Hostel"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="p-4 border-t bg-muted/20 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <span class="text-xs text-muted-foreground font-medium">
                            Showing {{ startItemIndex }} to {{ endItemIndex }} of {{ filteredHostels.length }} Hostels
                        </span>
                        
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-muted-foreground">Per page:</span>
                            <Select v-model="perPage" @update:model-value="currentPage = 1">
                                <SelectTrigger class="h-8 w-20 text-xs rounded-lg">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="10">10</SelectItem>
                                    <SelectItem value="20">20</SelectItem>
                                    <SelectItem value="30">30</SelectItem>
                                    <SelectItem value="50">50</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Page Buttons -->
                    <div v-if="totalPages > 1" class="flex items-center space-x-1">
                        <Button 
                            variant="outline" 
                            size="icon" 
                            class="h-8 w-8 rounded-lg"
                            :disabled="currentPage === 1"
                            @click="currentPage = 1"
                            title="First Page"
                        >
                            <ChevronsLeft class="h-4 w-4" />
                        </Button>
                        <Button 
                            variant="outline" 
                            size="icon" 
                            class="h-8 w-8 rounded-lg"
                            :disabled="currentPage === 1"
                            @click="currentPage--"
                            title="Previous Page"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>

                        <span class="px-3 text-xs font-bold">
                            Page {{ currentPage }} of {{ totalPages }}
                        </span>

                        <Button 
                            variant="outline" 
                            size="icon" 
                            class="h-8 w-8 rounded-lg"
                            :disabled="currentPage === totalPages"
                            @click="currentPage++"
                            title="Next Page"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                        <Button 
                            variant="outline" 
                            size="icon" 
                            class="h-8 w-8 rounded-lg"
                            :disabled="currentPage === totalPages"
                            @click="currentPage = totalPages"
                            title="Last Page"
                        >
                            <ChevronsRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Grid Cards View -->
            <div v-else-if="viewMode === 'grid' && paginatedHostels.length > 0" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="hostel in paginatedHostels" 
                        :key="hostel.id" 
                        class="group bg-card border rounded-3xl shadow-sm hover:shadow-xl hover:border-primary/40 transition-all duration-300 overflow-hidden flex flex-col cursor-pointer"
                        @click="router.visit(route('admin.hostels.show', hostel.id))"
                    >
                        <!-- Card Top Banner -->
                        <div class="p-6 border-b bg-gradient-to-r from-card to-muted/30 flex items-start justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="p-3 bg-primary/10 text-primary rounded-2xl group-hover:bg-primary group-hover:text-primary-foreground transition-all duration-300 shadow-sm">
                                    <Home class="h-7 w-7" />
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-xl group-hover:text-primary transition-colors">{{ hostel.name }}</h3>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span :class="['text-xs font-bold px-2.5 py-0.5 rounded-full border uppercase tracking-wider', getGenderBadgeClass(hostel.gender_type)]">
                                            {{ hostel.gender_type }}
                                        </span>
                                        <span v-if="!hostel.is_visible" class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">
                                            Hidden
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="p-6 flex-1 space-y-5">
                            <p class="text-sm text-muted-foreground line-clamp-2 min-h-[40px] leading-relaxed">
                                {{ hostel.description || 'Modern university residential hall and bed allocation block.' }}
                            </p>

                            <!-- Key Metrics Grid -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-muted/40 p-3.5 rounded-2xl border text-center">
                                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-0.5">Structure</p>
                                    <p class="text-lg font-black text-foreground">{{ hostel.floors_count }} <span class="text-xs font-bold text-muted-foreground">Floors</span></p>
                                    <p class="text-[10px] text-muted-foreground">{{ getHostelTotalRooms(hostel) }} Rooms</p>
                                </div>

                                <div class="bg-emerald-500/10 p-3.5 rounded-2xl border border-emerald-500/20 text-center">
                                    <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider mb-0.5">Vacant Beds</p>
                                    <p class="text-lg font-black text-emerald-700 dark:text-emerald-400">{{ getHostelVacantBeds(hostel) }} <span class="text-xs font-bold">Open</span></p>
                                    <p class="text-[10px] text-emerald-600 font-bold">of {{ getHostelTotalCapacity(hostel) }} Total Capacity</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-muted-foreground font-medium pt-2 border-t">
                                <span>Fee Configurations: <strong>{{ hostel.fees_count }}</strong></span>
                                <span class="text-primary font-bold group-hover:underline">View Rooms & Vacancies →</span>
                            </div>
                        </div>

                        <!-- Card Footer Actions -->
                        <div class="p-4 bg-muted/30 border-t flex items-center justify-between" @click.stop>
                            <Button 
                                v-if="hasPermission('toggle_hostels')" 
                                variant="ghost" 
                                size="sm" 
                                @click="toggleHostelVisibility(hostel.id)" 
                                class="rounded-xl font-bold text-xs"
                                :title="hostel.is_visible ? 'Hide Hostel' : 'Show Hostel'"
                            >
                                <component :is="hostel.is_visible ? EyeOff : Eye" class="h-4 w-4 mr-1.5" />
                                {{ hostel.is_visible ? 'Hide' : 'Show' }}
                            </Button>

                            <div class="flex items-center space-x-1">
                                <Button 
                                    v-if="hasPermission('create_hostels')" 
                                    variant="ghost" 
                                    size="sm" 
                                    class="rounded-xl font-bold text-xs"
                                    @click="openEditModal(hostel)"
                                >
                                    <Edit class="h-4 w-4 mr-1.5" /> Edit
                                </Button>

                                <Button 
                                    v-if="hasPermission('create_hostels')" 
                                    variant="ghost" 
                                    size="sm" 
                                    class="rounded-xl font-bold text-xs text-destructive hover:bg-destructive/10 hover:text-destructive" 
                                    @click="deleteHostel(hostel.id)"
                                >
                                    <Trash2 class="h-4 w-4 mr-1.5" /> Delete
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid View Pagination Footer -->
                <div class="p-4 border bg-card rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs text-muted-foreground font-medium">
                        Showing {{ startItemIndex }} to {{ endItemIndex }} of {{ filteredHostels.length }} Hostels
                    </div>
                    <div v-if="totalPages > 1" class="flex items-center space-x-1">
                        <Button variant="outline" size="sm" :disabled="currentPage === 1" @click="currentPage--">Previous</Button>
                        <span class="px-3 text-xs font-bold">Page {{ currentPage }} of {{ totalPages }}</span>
                        <Button variant="outline" size="sm" :disabled="currentPage === totalPages" @click="currentPage++">Next</Button>
                    </div>
                </div>
            </div>

            <!-- No Search/Filter Results -->
            <div v-else class="flex flex-col items-center justify-center p-16 text-center bg-card border-2 border-dashed rounded-3xl">
                <div class="h-20 w-20 bg-muted rounded-full flex items-center justify-center mb-4 ring-8 ring-muted/20">
                    <Building class="h-10 w-10 text-muted-foreground/40" />
                </div>
                <h3 class="text-xl font-bold text-foreground">No Hostels Match Your Criteria</h3>
                <p class="text-sm text-muted-foreground mt-2 max-w-md">
                    No hostels found matching your search query or selected filter criteria. Try adjusting your parameters.
                </p>
                <Button variant="outline" @click="resetFilters" class="mt-6 rounded-full font-bold px-6">
                    Reset All Filters
                </Button>
            </div>
        </div>

        <!-- Create Hostel Modal -->
        <Dialog :open="isCreateModalOpen" @update:open="isCreateModalOpen = $event">
            <DialogContent class="sm:max-w-[450px] rounded-3xl p-6">
                <DialogHeader>
                    <DialogTitle class="text-2xl font-black">Add New Hostel</DialogTitle>
                    <DialogDescription>
                        Register a new campus residential building in the system.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitCreate">
                    <div class="grid gap-5 py-5">
                        <div class="space-y-2">
                            <Label for="name" class="font-bold text-slate-700">Hostel Name</Label>
                            <Input id="name" v-model="form.name" placeholder="e.g. Block A, Mandela Hall" class="h-12 rounded-xl" />
                            <p v-if="form.errors.name" class="text-sm text-destructive font-medium">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="gender_type" class="font-bold text-slate-700">Gender Allocation</Label>
                            <Select v-model="form.gender_type">
                                <SelectTrigger class="h-12 rounded-xl">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="mixed">Mixed Residence</SelectItem>
                                    <SelectItem value="male">Male Only</SelectItem>
                                    <SelectItem value="female">Female Only</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.gender_type" class="text-sm text-destructive font-medium">{{ form.errors.gender_type }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="description" class="font-bold text-slate-700">Description (Optional)</Label>
                            <Textarea id="description" v-model="form.description" placeholder="Short details about the hostel structure..." rows="3" class="rounded-xl" />
                            <p v-if="form.errors.description" class="text-sm text-destructive font-medium">{{ form.errors.description }}</p>
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="isCreateModalOpen = false" class="rounded-full">Cancel</Button>
                        <Button type="submit" :disabled="form.processing" class="rounded-full px-8 font-bold shadow-md">Save Hostel</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Hostel Modal -->
        <Dialog :open="isEditModalOpen" @update:open="isEditModalOpen = $event">
            <DialogContent class="sm:max-w-[450px] rounded-3xl p-6">
                <DialogHeader>
                    <DialogTitle class="text-2xl font-black">Edit Hostel</DialogTitle>
                    <DialogDescription>
                        Modify hostel details and gender allocation.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEdit">
                    <div class="grid gap-5 py-5">
                        <div class="space-y-2">
                            <Label for="edit_name" class="font-bold text-slate-700">Hostel Name</Label>
                            <Input id="edit_name" v-model="form.name" class="h-12 rounded-xl" />
                            <p v-if="form.errors.name" class="text-sm text-destructive font-medium">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_gender_type" class="font-bold text-slate-700">Gender Allocation</Label>
                            <Select v-model="form.gender_type">
                                <SelectTrigger class="h-12 rounded-xl">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="mixed">Mixed Residence</SelectItem>
                                    <SelectItem value="male">Male Only</SelectItem>
                                    <SelectItem value="female">Female Only</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.gender_type" class="text-sm text-destructive font-medium">{{ form.errors.gender_type }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_description" class="font-bold text-slate-700">Description</Label>
                            <Textarea id="edit_description" v-model="form.description" rows="3" class="rounded-xl" />
                            <p v-if="form.errors.description" class="text-sm text-destructive font-medium">{{ form.errors.description }}</p>
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="isEditModalOpen = false" class="rounded-full">Cancel</Button>
                        <Button type="submit" :disabled="form.processing" class="rounded-full px-8 font-bold shadow-md">Update Hostel</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Room Excel Import Modal -->
        <Dialog v-model:open="roomImportModalOpen">
            <DialogContent class="sm:max-w-[540px] rounded-3xl p-6 shadow-2xl">
                <DialogHeader class="border-b pb-4">
                    <DialogTitle class="text-xl font-black flex items-center gap-2 text-foreground">
                        <FileSpreadsheet class="h-6 w-6 text-emerald-600" /> Batch Import Hostel Rooms (Excel / CSV)
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Upload an Excel file to bulk create blocks, floors, and hostel rooms.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-5 py-4">
                    <!-- Mode Indicator Banner -->
                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-3.5 flex items-start gap-3">
                        <Sparkles class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                        <div class="text-xs text-emerald-900">
                            <span class="font-bold block text-emerald-950">Update & Create Mode Active</span>
                            If a room number already exists under the selected structure, its capacity and visibility will be updated. New rooms will be automatically created.
                        </div>
                    </div>

                    <!-- Step 1: Download Template -->
                    <div class="bg-muted/40 p-4 rounded-2xl border flex items-center justify-between gap-4">
                        <div>
                            <h4 class="text-sm font-bold text-foreground">Need the Excel template?</h4>
                            <p class="text-xs text-muted-foreground">Download pre-formatted template with sample room headers.</p>
                        </div>
                        <a 
                            :href="route('admin.hostels.rooms.import-template')" 
                            target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs font-bold px-4 py-2 bg-background border rounded-xl shadow-xs hover:bg-muted text-primary shrink-0 transition-colors"
                        >
                            <Download class="h-3.5 w-3.5" /> Download
                        </a>
                    </div>

                    <!-- Step 2: Target Structure Selection (Hostel, Block, Floor) -->
                    <div class="space-y-4 bg-muted/20 p-4 rounded-2xl border">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Target Structure Selection (Optional)</h4>

                        <!-- Select Hostel -->
                        <div class="space-y-1.5">
                            <Label class="font-bold text-xs">Target Hostel</Label>
                            <Select v-model="roomImportForm.hostel_id">
                                <SelectTrigger class="rounded-xl bg-background">
                                    <SelectValue placeholder="All Hostels (Specified in Excel)" />
                                </SelectTrigger>
                                <SelectContent class="rounded-xl">
                                    <SelectItem value="all">All Hostels (Use Excel 'hostel_name' column)</SelectItem>
                                    <SelectItem v-for="hostel in hostels" :key="hostel.id" :value="hostel.id">
                                        {{ hostel.name }} ({{ hostel.gender_type }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Select Block (only if hostel selected) -->
                        <div v-if="roomImportForm.hostel_id && roomImportForm.hostel_id !== 'all'" class="space-y-1.5">
                            <Label class="font-bold text-xs">Target Block</Label>
                            <Select v-model="roomImportForm.block_id">
                                <SelectTrigger class="rounded-xl bg-background">
                                    <SelectValue placeholder="All Blocks (Specified in Excel)" />
                                </SelectTrigger>
                                <SelectContent class="rounded-xl">
                                    <SelectItem value="all">All Blocks (Use Excel 'block_name' column)</SelectItem>
                                    <SelectItem v-for="block in availableBlocksForImport" :key="block.id" :value="block.id">
                                        {{ block.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Select Floor (only if block selected) -->
                        <div v-if="roomImportForm.block_id && roomImportForm.block_id !== 'all'" class="space-y-1.5">
                            <Label class="font-bold text-xs">Target Floor</Label>
                            <Select v-model="roomImportForm.floor_id">
                                <SelectTrigger class="rounded-xl bg-background">
                                    <SelectValue placeholder="All Floors (Specified in Excel)" />
                                </SelectTrigger>
                                <SelectContent class="rounded-xl">
                                    <SelectItem value="all">All Floors (Use Excel 'floor_name' column)</SelectItem>
                                    <SelectItem v-for="floor in availableFloorsForImport" :key="floor.id" :value="floor.id">
                                        {{ floor.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Step 3: Upload File -->
                    <div class="space-y-2">
                        <Label class="font-bold text-sm">Select Excel / CSV File</Label>
                        <Input 
                            type="file" 
                            accept=".xlsx,.xls,.csv" 
                            @change="handleRoomImportFileChange"
                            class="bg-background rounded-xl text-xs py-2 cursor-pointer border"
                        />
                        <p class="text-[11px] text-muted-foreground">
                            Expected headers: <code>hostel_name</code>, <code>block_name</code>, <code>floor_name</code>, <code>room_number</code>, <code>capacity</code>, <code>is_visible</code>.
                        </p>
                        <span v-if="roomImportForm.errors.file" class="text-xs font-bold text-red-500">
                            {{ roomImportForm.errors.file }}
                        </span>
                    </div>
                </div>

                <DialogFooter class="border-t pt-4">
                    <Button variant="outline" @click="roomImportModalOpen = false" class="rounded-xl font-bold">
                        Cancel
                    </Button>
                    <Button 
                        @click="submitRoomImport" 
                        :disabled="!roomImportForm.file || roomImportForm.processing"
                        class="rounded-xl font-bold bg-emerald-600 hover:bg-emerald-700 text-white"
                    >
                        <Upload class="mr-2 h-4 w-4" /> 
                        {{ roomImportForm.processing ? 'Importing Rooms...' : 'Upload & Process Rooms' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>

