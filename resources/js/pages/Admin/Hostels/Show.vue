<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { 
    Plus, Edit, Trash2, Home, Eye, EyeOff, Building, DoorOpen, Bed, 
    Layers, Users, UserCheck, ShieldCheck, Sparkles, Ban, ArrowLeft, 
    Filter, CheckCircle2, Grid, ChevronDown, ChevronUp, UserPlus, AlertCircle,
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { route } from 'ziggy-js';

const props = defineProps<{
    hostel: {
        id: string;
        name: string;
        gender_type: string;
        description: string;
        is_visible: boolean;
        blocks: Array<{
            id: string;
            name: string;
            floors: Array<{
                id: string;
                name: string;
                rooms: Array<{
                    id: string;
                    room_number: string;
                    capacity: number;
                    is_visible: boolean;
                    is_suspended: boolean;
                    bookings?: Array<{
                        id: string;
                        student_id: string;
                        bed_space_number: number;
                        status: string;
                        student?: {
                            id: string;
                            user?: {
                                name: string;
                                email: string;
                            };
                            matric_number?: string;
                            department?: {
                                name: string;
                            };
                            level?: string;
                        };
                    }>;
                }>;
            }>;
        }>;
    };
    sessions: Array<{
        id: string;
        name: string;
    }>;
    currentSession?: {
        id: string;
        name: string;
    };
}>();

const activeBlockId = ref<string | null>(props.hostel.blocks[0]?.id || null);
const roomFilter = ref<'all' | 'vacant' | 'occupied' | 'suspended'>('all');

watch(() => props.hostel.blocks, (newBlocks) => {
    if (newBlocks.length > 0 && (!activeBlockId.value || !newBlocks.some(b => b.id === activeBlockId.value))) {
        activeBlockId.value = newBlocks[0].id;
    }
}, { deep: true });

const currentBlock = computed(() => {
    return props.hostel.blocks.find(b => b.id === activeBlockId.value) || null;
});

// Overall Hostel Calculations
const totalBlocks = computed(() => props.hostel.blocks.length);

const totalFloors = computed(() => {
    return props.hostel.blocks.reduce((sum, block) => sum + block.floors.length, 0);
});

const totalRooms = computed(() => {
    return props.hostel.blocks.reduce((sum, block) => {
        return sum + block.floors.reduce((fSum, floor) => fSum + floor.rooms.length, 0);
    }, 0);
});

const totalCapacity = computed(() => {
    return props.hostel.blocks.reduce((sum, block) => {
        return sum + block.floors.reduce((fSum, floor) => {
            return fSum + floor.rooms.reduce((rSum, room) => rSum + (room.capacity || 0), 0);
        }, 0);
    }, 0);
});

const totalOccupied = computed(() => {
    return props.hostel.blocks.reduce((sum, block) => {
        return sum + block.floors.reduce((fSum, floor) => {
            return fSum + floor.rooms.reduce((rSum, room) => {
                const booked = room.bookings ? room.bookings.length : 0;
                return rSum + Math.min(room.capacity || 0, booked);
            }, 0);
        }, 0);
    }, 0);
});

const totalVacant = computed(() => {
    return Math.max(0, totalCapacity.value - totalOccupied.value);
});

const occupancyPercentage = computed(() => {
    if (totalCapacity.value === 0) return 0;
    return Math.round((totalOccupied.value / totalCapacity.value) * 100);
});

// Helper for specific room occupancy
const getRoomBookedCount = (room: any) => {
    return room.bookings ? room.bookings.length : 0;
};

const getRoomVacantBeds = (room: any) => {
    const booked = getRoomBookedCount(room);
    return Math.max(0, (room.capacity || 0) - booked);
};

const isRoomFull = (room: any) => {
    return getRoomBookedCount(room) >= (room.capacity || 0);
};

// Modals State
const isBlockModalOpen = ref(false);
const isFloorModalOpen = ref(false);
const isRoomModalOpen = ref(false);
const isEditRoomModalOpen = ref(false);
const isEditHostelModalOpen = ref(false);
const isRoomDetailsModalOpen = ref(false);

const activeFloorId = ref<string | null>(null);
const activeRoom = ref<any>(null);
const selectedRoomDetails = ref<any>(null);
const selectedRoomFloor = ref<any>(null);

const blockForm = useForm({
    name: '',
});

const floorForm = useForm({
    name: '',
});

const roomForm = useForm({
    room_number: '',
    capacity: 4,
});

const openBlockModal = () => {
    blockForm.reset();
    blockForm.clearErrors();
    isBlockModalOpen.value = true;
};

const openFloorModal = () => {
    floorForm.reset();
    floorForm.clearErrors();
    isFloorModalOpen.value = true;
};

const openRoomModal = (floorId: string) => {
    activeFloorId.value = floorId;
    roomForm.reset();
    roomForm.capacity = 4;
    roomForm.clearErrors();
    isRoomModalOpen.value = true;
};

const openEditRoomModal = (floorId: string, room: any) => {
    activeFloorId.value = floorId;
    activeRoom.value = room;
    roomForm.room_number = room.room_number;
    roomForm.capacity = room.capacity;
    roomForm.clearErrors();
    isEditRoomModalOpen.value = true;
};

const openRoomDetailsModal = (floor: any, room: any) => {
    selectedRoomFloor.value = floor;
    selectedRoomDetails.value = room;
    isRoomDetailsModalOpen.value = true;
};

const submitBlock = () => {
    blockForm.post(route('admin.hostels.blocks.store', props.hostel.id), {
        onSuccess: () => isBlockModalOpen.value = false,
    });
};

const submitFloor = () => {
    if(!activeBlockId.value) return;
    floorForm.post(route('admin.hostels.floors.store', [props.hostel.id, activeBlockId.value]), {
        onSuccess: () => isFloorModalOpen.value = false,
    });
};

const submitRoom = () => {
    if(!activeBlockId.value || !activeFloorId.value) return;
    roomForm.post(route('admin.hostels.rooms.store', [props.hostel.id, activeBlockId.value, activeFloorId.value]), {
        onSuccess: () => isRoomModalOpen.value = false,
    });
};

const submitEditRoom = () => {
    if(!activeBlockId.value || !activeFloorId.value || !activeRoom.value) return;
    roomForm.put(route('admin.hostels.rooms.update', [props.hostel.id, activeBlockId.value, activeFloorId.value, activeRoom.value.id]), {
        onSuccess: () => isEditRoomModalOpen.value = false,
    });
};

const deleteBlock = (blockId: string) => {
    if(confirm('Are you sure you want to delete this block?')) {
        router.delete(route('admin.hostels.blocks.destroy', [props.hostel.id, blockId]));
    }
};

const deleteFloor = (floorId: string) => {
    if(!activeBlockId.value) return;
    if(confirm('Are you sure you want to delete this floor?')) {
        router.delete(route('admin.hostels.floors.destroy', [props.hostel.id, activeBlockId.value, floorId]));
    }
};

const deleteRoom = (floorId: string, roomId: string) => {
    if(!activeBlockId.value) return;
    if(confirm('Are you sure you want to delete this room?')) {
        router.delete(route('admin.hostels.rooms.destroy', [props.hostel.id, activeBlockId.value, floorId, roomId]));
    }
};

const toggleRoomVisibility = (floorId: string, roomId: string) => {
    if(!activeBlockId.value) return;
    router.post(route('admin.hostels.rooms.toggle-visibility', [props.hostel.id, activeBlockId.value, floorId, roomId]));
};

const getFilteredRooms = (floorRooms: any[]) => {
    if (!floorRooms) return [];
    return floorRooms.filter(room => {
        if (roomFilter.value === 'vacant') return getRoomVacantBeds(room) > 0 && !room.is_suspended;
        if (roomFilter.value === 'occupied') return isRoomFull(room) && !room.is_suspended;
        if (roomFilter.value === 'suspended') return room.is_suspended;
        return true;
    });
};

const hostelForm = useForm({
    name: props.hostel.name,
    gender_type: props.hostel.gender_type,
    description: props.hostel.description || '',
});

const openEditHostelModal = () => {
    hostelForm.name = props.hostel.name;
    hostelForm.gender_type = props.hostel.gender_type;
    hostelForm.description = props.hostel.description || '';
    isEditHostelModalOpen.value = true;
};

const submitEditHostel = () => {
    hostelForm.put(route('admin.hostels.update', props.hostel.id), {
        onSuccess: () => isEditHostelModalOpen.value = false,
    });
};

const toggleHostelVisibility = () => {
    router.post(route('admin.hostels.toggle-visibility', props.hostel.id), {}, {
        preserveScroll: true
    });
};

const roomImportModalOpen = ref(false);
const roomImportForm = useForm({
    block_id: 'all',
    floor_id: 'all',
    file: null as File | null,
});

const availableBlocksForImport = computed(() => {
    return props.hostel.blocks || [];
});

const availableFloorsForImport = computed(() => {
    if (!roomImportForm.block_id || roomImportForm.block_id === 'all') return [];
    const selectedBlock = availableBlocksForImport.value.find(b => b.id === roomImportForm.block_id);
    return selectedBlock?.floors || [];
});

watch(() => roomImportForm.block_id, () => {
    roomImportForm.floor_id = 'all';
});

const openRoomImportModal = () => {
    roomImportForm.reset();
    roomImportForm.clearErrors();
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
    router.post(route('admin.hostels.specific-rooms.import', props.hostel.id), {
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

const getGenderBadgeClass = (gender: string) => {
    if (gender === 'male') return 'bg-blue-100 text-blue-800 border-blue-200';
    if (gender === 'female') return 'bg-pink-100 text-pink-800 border-pink-200';
    return 'bg-purple-100 text-purple-800 border-purple-200';
};
</script>

<template>
    <Head :title="`${hostel.name} - Room & Vacancy Management`" />

    <AdminLayout>
        <div class="space-y-8 p-6 lg:p-10 max-w-[1600px] mx-auto">
            <!-- Header Ribbon -->
            <div class="bg-card border rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center space-x-3">
                            <Link :href="route('admin.hostels.index')">
                                <Button variant="outline" size="icon" class="h-9 w-9 rounded-full">
                                    <ArrowLeft class="h-4 w-4" />
                                </Button>
                            </Link>
                            <span :class="['text-xs font-bold px-3 py-1 rounded-full border uppercase tracking-wider', getGenderBadgeClass(hostel.gender_type)]">
                                {{ hostel.gender_type }} Residence
                            </span>
                            <Badge v-if="currentSession" variant="secondary" class="font-bold text-xs">
                                Session: {{ currentSession.name }}
                            </Badge>
                        </div>
                        <h1 class="text-3xl font-black tracking-tight text-foreground sm:text-4xl flex items-center gap-3">
                            <Building class="h-8 w-8 text-primary" />
                            {{ hostel.name }}
                        </h1>
                        <p class="text-muted-foreground max-w-2xl text-base">
                            {{ hostel.description || 'Hostel rooms, floor allocations, and live vacancy status breakdown.' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button @click="openRoomImportModal" variant="outline" class="rounded-xl px-5 h-11 border-emerald-300 text-emerald-800 bg-emerald-50 hover:bg-emerald-100 font-bold">
                            <FileSpreadsheet class="mr-2 h-4 w-4 text-emerald-600" /> Import Rooms (Excel)
                        </Button>
                        <Button @click="toggleHostelVisibility" variant="outline" class="rounded-xl px-5 h-11 border-border font-bold">
                            <component :is="hostel.is_visible ? EyeOff : Eye" class="mr-2 h-4 w-4" /> 
                            {{ hostel.is_visible ? 'Hide Hostel' : 'Show Hostel' }}
                        </Button>
                        <Button @click="openEditHostelModal" variant="outline" class="rounded-xl px-5 h-11 border-border font-bold">
                            <Edit class="mr-2 h-4 w-4" /> Edit Details
                        </Button>
                        <Button @click="openBlockModal" class="rounded-xl shadow-md font-bold px-6 h-11">
                            <Plus class="mr-2 h-5 w-5" /> Add New Block
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Global Executive KPI Dashboard -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                        <Grid class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Structure</p>
                        <h3 class="text-2xl font-black text-foreground">{{ totalBlocks }} Blocks <span class="text-sm font-bold text-muted-foreground">• {{ totalFloors }} Floors</span></h3>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <DoorOpen class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Total Rooms</p>
                        <h3 class="text-2xl font-black text-foreground">{{ totalRooms }} Units</h3>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <Bed class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Total Capacity</p>
                        <h3 class="text-2xl font-black text-foreground">{{ totalCapacity }} <span class="text-xs font-bold text-muted-foreground">Beds Total</span></h3>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <Users class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Occupied Beds</p>
                        <h3 class="text-2xl font-black text-amber-700">{{ totalOccupied }} <span class="text-xs font-bold text-amber-600">({{ occupancyPercentage }}%)</span></h3>
                    </div>
                </div>

                <!-- Vacant Beds Highlight Card -->
                <div class="bg-emerald-500/10 border-2 border-emerald-500/30 rounded-2xl p-6 flex items-center space-x-4 shadow-sm">
                    <div class="h-14 w-14 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shadow-md">
                        <UserCheck class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Available Vacant</p>
                        <h3 class="text-2xl font-black text-emerald-700 dark:text-emerald-400">{{ totalVacant }} <span class="text-xs font-bold text-emerald-600">Open Beds</span></h3>
                    </div>
                </div>
            </div>

            <!-- Empty State when no blocks exist -->
            <div v-if="hostel.blocks.length === 0" class="flex flex-col items-center justify-center py-24 text-center bg-card shadow-sm border rounded-3xl">
                <div class="h-24 w-24 bg-muted/50 rounded-full flex items-center justify-center mb-6 ring-8 ring-muted/20">
                    <Grid class="h-10 w-10 text-muted-foreground/60" />
                </div>
                <h3 class="text-2xl font-bold text-foreground tracking-tight">Initialize First Block</h3>
                <p class="text-base text-muted-foreground mt-3 max-w-lg">
                    Add your first Block (e.g. Block A, Wing C) to start mapping out floors, rooms, and vacancy status.
                </p>
                <Button @click="openBlockModal" size="lg" class="mt-8 rounded-full shadow-md font-semibold px-8 h-12">
                    <Plus class="mr-2 h-5 w-5" /> Initialize First Block
                </Button>
            </div>

            <div v-else class="space-y-6">
                <!-- Modern Segmented Navigation Bar for Blocks & Filter -->
                <div class="bg-card border rounded-3xl p-4 sm:p-5 shadow-sm space-y-4">
                    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                        <!-- Block Segmented Pills -->
                        <div class="flex items-center space-x-2 overflow-x-auto scrollbar-none pb-1 lg:pb-0">
                            <button 
                                v-for="block in hostel.blocks" 
                                :key="block.id"
                                @click="activeBlockId = block.id"
                                :class="[
                                    'px-5 py-2.5 rounded-2xl font-extrabold text-sm transition-all whitespace-nowrap border flex items-center gap-2',
                                    activeBlockId === block.id 
                                        ? 'bg-primary text-primary-foreground border-primary shadow-md' 
                                        : 'bg-background text-muted-foreground border-border hover:bg-muted hover:text-foreground'
                                ]"
                            >
                                <Building class="h-4 w-4" />
                                <span>{{ block.name }}</span>
                                <Badge 
                                    :variant="activeBlockId === block.id ? 'secondary' : 'outline'" 
                                    class="ml-1 text-[10px] px-1.5 py-0"
                                >
                                    {{ block.floors.length }} Floors
                                </Badge>
                            </button>
                            
                            <button 
                                @click="openBlockModal" 
                                class="px-4 py-2.5 rounded-2xl font-bold text-sm transition-all whitespace-nowrap border border-dashed border-primary/40 text-primary hover:bg-primary/10 flex items-center gap-1.5"
                            >
                                <Plus class="h-4 w-4" />
                                <span>Add Block</span>
                            </button>
                        </div>

                        <!-- Filter Rooms Bar -->
                        <div class="flex items-center space-x-2 bg-muted/40 p-1.5 rounded-2xl border shrink-0">
                            <span class="text-xs font-bold text-muted-foreground uppercase px-2 flex items-center">
                                <Filter class="h-3.5 w-3.5 mr-1" /> View:
                            </span>
                            <button 
                                @click="roomFilter = 'all'"
                                :class="['px-3 py-1.5 text-xs font-bold rounded-xl transition-all', roomFilter === 'all' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground']"
                            >
                                All Rooms
                            </button>
                            <button 
                                @click="roomFilter = 'vacant'"
                                :class="['px-3 py-1.5 text-xs font-bold rounded-xl transition-all', roomFilter === 'vacant' ? 'bg-emerald-500 text-white shadow-sm' : 'text-muted-foreground hover:text-foreground']"
                            >
                                Vacant Only
                            </button>
                            <button 
                                @click="roomFilter = 'occupied'"
                                :class="['px-3 py-1.5 text-xs font-bold rounded-xl transition-all', roomFilter === 'occupied' ? 'bg-amber-500 text-white shadow-sm' : 'text-muted-foreground hover:text-foreground']"
                            >
                                Fully Occupied
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Block Control Bar -->
                <div v-if="currentBlock" class="bg-card border rounded-3xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <h2 class="text-2xl font-black text-foreground flex items-center gap-2">
                                {{ currentBlock.name }}
                            </h2>
                            <Badge variant="outline" class="font-bold text-xs">
                                {{ currentBlock.floors.length }} Floors Configured
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">Configure floors and inspect real-time unit occupancy.</p>
                    </div>

                    <div class="flex items-center space-x-3 shrink-0">
                        <Button @click="openFloorModal" class="rounded-xl font-bold px-5 h-10 shadow-sm">
                            <Plus class="h-4 w-4 mr-2" /> Add Floor Level
                        </Button>
                        <Button variant="outline" class="text-destructive hover:bg-destructive/10 border-destructive/30 rounded-xl h-10 font-bold" @click="deleteBlock(currentBlock.id)">
                            <Trash2 class="h-4 w-4 mr-2" /> Delete Block
                        </Button>
                    </div>
                </div>

                <!-- Empty Floor State -->
                <div v-if="currentBlock && currentBlock.floors.length === 0" class="py-20 text-center bg-card border rounded-3xl flex flex-col items-center justify-center">
                    <div class="h-16 w-16 bg-muted rounded-full flex items-center justify-center mb-4 ring-8 ring-muted/20">
                        <Layers class="h-8 w-8 text-muted-foreground/60" />
                    </div>
                    <h3 class="text-xl font-bold text-foreground">No Floors Configured</h3>
                    <p class="text-sm text-muted-foreground mt-2 max-w-sm">No floors have been created in {{ currentBlock.name }} yet.</p>
                    <Button @click="openFloorModal" class="mt-6 rounded-full font-bold px-6 shadow-sm">
                        <Plus class="mr-2 h-4 w-4" /> Add First Floor Level
                    </Button>
                </div>

                <!-- Floors & Rooms Matrix -->
                <div v-else-if="currentBlock" class="space-y-8">
                    <div v-for="(floor, index) in currentBlock.floors" :key="floor.id" class="bg-card border rounded-3xl shadow-sm overflow-hidden flex flex-col">
                        <!-- Floor Level Header Ribbon -->
                        <div class="p-6 border-b bg-gradient-to-r from-card to-muted/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center space-x-4">
                                <div class="h-12 w-12 rounded-2xl bg-primary/10 text-primary font-black text-xl flex items-center justify-center border border-primary/20">
                                    {{ index + 1 }}
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-xl text-foreground tracking-tight">{{ floor.name }}</h3>
                                    <div class="flex items-center gap-3 text-xs text-muted-foreground font-medium mt-0.5">
                                        <span>{{ floor.rooms.length }} Units Configured</span>
                                        <span>•</span>
                                        <span class="text-emerald-700 font-bold dark:text-emerald-400">
                                            {{ floor.rooms.reduce((sum, r) => sum + getRoomVacantBeds(r), 0) }} Vacant Beds Open
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <Button size="sm" @click="openRoomModal(floor.id)" class="rounded-xl font-bold gap-1.5 shadow-sm">
                                    <Plus class="h-4 w-4" /> Add Room Unit
                                </Button>
                                <Button variant="ghost" size="icon" class="h-9 w-9 rounded-xl text-destructive hover:bg-destructive/10" @click="deleteFloor(floor.id)" title="Delete Floor">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- Room Cards Grid -->
                        <div class="p-6 bg-muted/20">
                            <div v-if="getFilteredRooms(floor.rooms).length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-5">
                                <div 
                                    v-for="room in getFilteredRooms(floor.rooms)" 
                                    :key="room.id"
                                    :class="[
                                        'group bg-card border rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between space-y-4 cursor-pointer',
                                        room.is_suspended ? 'border-red-300 bg-red-500/5' : getRoomVacantBeds(room) > 0 ? 'hover:border-emerald-500/50' : 'hover:border-primary/40'
                                    ]"
                                    @click="openRoomDetailsModal(floor, room)"
                                >
                                    <!-- Room Card Top Header -->
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-center space-x-2.5">
                                            <div :class="['p-2 rounded-xl border', room.is_suspended ? 'bg-red-100 text-red-700 border-red-200' : 'bg-muted/50 text-foreground border-border']">
                                                <DoorOpen class="h-5 w-5" />
                                            </div>
                                            <div>
                                                <h4 class="font-black text-lg text-foreground group-hover:text-primary transition-colors">Unit {{ room.room_number }}</h4>
                                                <p class="text-[11px] text-muted-foreground font-medium">Floor {{ index + 1 }}</p>
                                            </div>
                                        </div>

                                        <Badge 
                                            :class="[
                                                'font-bold text-[10px] uppercase tracking-wider px-2 py-0.5 border',
                                                getRoomVacantBeds(room) > 0 
                                                    ? 'bg-emerald-100 text-emerald-800 border-emerald-300' 
                                                    : 'bg-amber-100 text-amber-800 border-amber-300'
                                            ]"
                                        >
                                            {{ getRoomVacantBeds(room) > 0 ? `${getRoomVacantBeds(room)} VACANT` : 'FULL' }}
                                        </Badge>
                                    </div>

                                    <!-- Visual Bed Space Layout Silhouettes -->
                                    <div class="bg-muted/40 p-3 rounded-xl border space-y-2">
                                        <div class="flex items-center justify-between text-xs font-bold">
                                            <span class="text-muted-foreground">Occupancy:</span>
                                            <span :class="getRoomVacantBeds(room) > 0 ? 'text-emerald-700 font-black' : 'text-foreground font-black'">
                                                {{ getRoomBookedCount(room) }} / {{ room.capacity }} Beds
                                            </span>
                                        </div>

                                        <!-- Bed Silhouettes Grid -->
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <div 
                                                v-for="bIndex in room.capacity" 
                                                :key="bIndex"
                                                :class="[
                                                    'h-6 flex-1 min-w-[20px] rounded-md flex items-center justify-center border text-[10px] font-bold transition-all',
                                                    bIndex <= getRoomBookedCount(room) 
                                                        ? 'bg-indigo-600 text-white border-indigo-700 shadow-xs' 
                                                        : 'bg-emerald-500/20 text-emerald-700 border-emerald-400/40'
                                                ]"
                                                :title="bIndex <= getRoomBookedCount(room) ? `Bed ${bIndex}: Occupied` : `Bed ${bIndex}: Vacant`"
                                            >
                                                <Bed class="h-3.5 w-3.5" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Pills -->
                                    <div v-if="room.is_suspended || !room.is_visible" class="flex items-center gap-1.5">
                                        <span v-if="room.is_suspended" class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-red-100 text-red-800 border border-red-200">
                                            Suspended
                                        </span>
                                        <span v-if="!room.is_visible" class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">
                                            Hidden
                                        </span>
                                    </div>

                                    <!-- Permanent Sleek Action Bar -->
                                    <div class="pt-2 border-t flex items-center justify-between gap-1" @click.stop>
                                        <Button variant="outline" size="sm" class="h-8 text-xs font-bold flex-1 rounded-xl" @click="openRoomDetailsModal(floor, room)">
                                            <Users class="h-3.5 w-3.5 mr-1" /> Occupants
                                        </Button>

                                        <div class="flex items-center space-x-1">
                                            <Button 
                                                variant="ghost" 
                                                size="icon" 
                                                class="h-8 w-8 rounded-lg"
                                                @click="toggleRoomVisibility(floor.id, room.id)"
                                                :title="room.is_visible ? 'Hide Room' : 'Show Room'"
                                            >
                                                <component :is="room.is_visible ? EyeOff : Eye" class="h-3.5 w-3.5" />
                                            </Button>

                                            <Button 
                                                variant="ghost" 
                                                size="icon" 
                                                class="h-8 w-8 rounded-lg"
                                                @click="openEditRoomModal(floor.id, room)"
                                                title="Edit Room"
                                            >
                                                <Edit class="h-3.5 w-3.5" />
                                            </Button>

                                            <Button 
                                                variant="ghost" 
                                                size="icon" 
                                                class="h-8 w-8 rounded-lg text-destructive hover:bg-destructive/10"
                                                @click="deleteRoom(floor.id, room.id)"
                                                title="Delete Room"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- No Rooms Match Filter -->
                            <div v-else class="py-12 text-center bg-card border-2 border-dashed rounded-2xl flex flex-col items-center justify-center">
                                <DoorOpen class="h-10 w-10 text-muted-foreground/40 mb-3" />
                                <p class="text-base font-bold text-foreground">No Rooms Match Filter</p>
                                <p class="text-xs text-muted-foreground mt-1 max-w-xs">No room units found for the selected filter option.</p>
                                <Button size="sm" variant="outline" @click="openRoomModal(floor.id)" class="mt-4 rounded-full font-bold">
                                    <Plus class="h-4 w-4 mr-1" /> Add Room Unit
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Details & Occupants Modal -->
        <Dialog :open="isRoomDetailsModalOpen" @update:open="isRoomDetailsModalOpen = $event">
            <DialogContent class="sm:max-w-[700px] rounded-3xl p-6 md:p-8">
                <DialogHeader v-if="selectedRoomDetails">
                    <div class="flex items-center justify-between border-b pb-4">
                        <div>
                            <Badge variant="outline" class="font-bold text-xs border-primary text-primary mb-1">
                                Unit {{ selectedRoomDetails.room_number }} Details
                            </Badge>
                            <DialogTitle class="text-2xl font-black">Room Occupants & Vacancy</DialogTitle>
                        </div>

                        <Badge 
                            :class="[
                                'font-bold text-xs uppercase tracking-wider px-3 py-1 border',
                                getRoomVacantBeds(selectedRoomDetails) > 0 
                                    ? 'bg-emerald-100 text-emerald-800 border-emerald-300' 
                                    : 'bg-amber-100 text-amber-800 border-amber-300'
                            ]"
                        >
                            {{ getRoomVacantBeds(selectedRoomDetails) > 0 ? `${getRoomVacantBeds(selectedRoomDetails)} Vacant Beds` : 'Fully Occupied' }}
                        </Badge>
                    </div>
                </DialogHeader>

                <div v-if="selectedRoomDetails" class="space-y-6 py-4">
                    <!-- Room Info Header Grid -->
                    <div class="grid grid-cols-3 gap-4 bg-muted/30 p-4 rounded-2xl border text-center">
                        <div>
                            <p class="text-xs font-bold text-muted-foreground uppercase">Total Capacity</p>
                            <p class="text-lg font-black text-foreground">{{ selectedRoomDetails.capacity }} Beds</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-muted-foreground uppercase">Occupied Beds</p>
                            <p class="text-lg font-black text-indigo-700">{{ getRoomBookedCount(selectedRoomDetails) }} Beds</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-muted-foreground uppercase">Available Vacant</p>
                            <p class="text-lg font-black text-emerald-700">{{ getRoomVacantBeds(selectedRoomDetails) }} Beds</p>
                        </div>
                    </div>

                    <!-- Occupants List -->
                    <div class="space-y-3">
                        <h4 class="font-bold text-sm text-foreground uppercase tracking-wider flex items-center gap-2">
                            <Users class="h-4 w-4 text-primary" />
                            Allocated Student Occupants ({{ getRoomBookedCount(selectedRoomDetails) }})
                        </h4>

                        <div v-if="selectedRoomDetails.bookings && selectedRoomDetails.bookings.length > 0" class="divide-y border rounded-2xl overflow-hidden bg-card">
                            <div 
                                v-for="booking in selectedRoomDetails.bookings" 
                                :key="booking.id" 
                                class="p-4 flex items-center justify-between hover:bg-muted/20 transition-colors"
                            >
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-full bg-primary/10 text-primary font-bold flex items-center justify-center text-sm">
                                        {{ booking.student?.user?.name ? booking.student.user.name.charAt(0).toUpperCase() : 'S' }}
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-foreground text-sm">
                                            {{ booking.student?.user?.name || 'Unknown Student' }}
                                        </p>
                                        <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                                            <span class="font-mono font-bold">{{ booking.student?.matric_number || 'N/A' }}</span>
                                            <span>•</span>
                                            <span>{{ booking.student?.department?.name || 'Department N/A' }}</span>
                                            <span v-if="booking.student?.level">• {{ booking.student.level }}L</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right space-y-1">
                                    <Badge variant="outline" class="font-bold text-xs">
                                        Bed {{ booking.bed_space_number }}
                                    </Badge>
                                    <p class="text-[10px] uppercase font-bold text-emerald-700">
                                        {{ booking.status }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-8 text-center bg-muted/20 border-2 border-dashed rounded-2xl">
                            <Bed class="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                            <p class="text-sm font-bold text-foreground">No Occupants Allocated Yet</p>
                            <p class="text-xs text-muted-foreground mt-1">This room is currently empty and has {{ selectedRoomDetails.capacity }} vacant beds ready for assignment.</p>
                        </div>
                    </div>
                </div>

                <DialogFooter class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <Button variant="outline" @click="isRoomDetailsModalOpen = false" class="rounded-full">Close</Button>
                    <Button 
                        v-if="getRoomVacantBeds(selectedRoomDetails) > 0"
                        @click="router.visit(route('admin.hostels.bookings.index'))" 
                        class="rounded-full font-bold shadow-md"
                    >
                        <UserPlus class="h-4 w-4 mr-2" /> Allocate Student to Bedspace
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Create Block Modal -->
        <Dialog :open="isBlockModalOpen" @update:open="isBlockModalOpen = $event">
            <DialogContent class="sm:max-w-[425px] rounded-3xl p-6">
                <DialogHeader>
                    <DialogTitle class="text-2xl font-black">Add Block</DialogTitle>
                    <DialogDescription>
                        Create a block division for {{ hostel.name }}.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitBlock">
                    <div class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="block_name" class="font-bold text-slate-700">Block Name / Wing</Label>
                            <Input id="block_name" v-model="blockForm.name" placeholder="e.g. Block A, West Wing" class="h-12 rounded-xl" />
                            <p v-if="blockForm.errors.name" class="text-sm text-destructive font-medium">{{ blockForm.errors.name }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isBlockModalOpen = false" class="rounded-full">Cancel</Button>
                        <Button type="submit" :disabled="blockForm.processing" class="rounded-full font-bold px-6 shadow-md">Create Block</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Create Floor Modal -->
        <Dialog :open="isFloorModalOpen" @update:open="isFloorModalOpen = $event">
            <DialogContent class="sm:max-w-[425px] rounded-3xl p-6">
                <DialogHeader>
                    <DialogTitle class="text-2xl font-black">Add Floor Level</DialogTitle>
                    <DialogDescription>
                        Add a floor level to {{ currentBlock?.name }}.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitFloor">
                    <div class="space-y-4 py-4">
                        <div class="space-y-2">
                            <Label for="floor_name" class="font-bold text-slate-700">Floor Level Name</Label>
                            <Input id="floor_name" v-model="floorForm.name" placeholder="e.g. Ground Floor, First Floor" class="h-12 rounded-xl" />
                            <p v-if="floorForm.errors.name" class="text-sm text-destructive font-medium">{{ floorForm.errors.name }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isFloorModalOpen = false" class="rounded-full">Cancel</Button>
                        <Button type="submit" :disabled="floorForm.processing" class="rounded-full font-bold px-6 shadow-md">Save Floor</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Create Room Modal -->
        <Dialog :open="isRoomModalOpen" @update:open="isRoomModalOpen = $event">
            <DialogContent class="sm:max-w-[425px] rounded-3xl p-6">
                <DialogHeader>
                    <DialogTitle class="text-2xl font-black">Add Room Unit</DialogTitle>
                    <DialogDescription>
                        Create a room unit and specify bed capacity.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitRoom">
                    <div class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <Label for="room_number" class="font-bold text-slate-700">Room Number / Identifier</Label>
                            <Input id="room_number" v-model="roomForm.room_number" placeholder="e.g. 101, RM 12" class="h-12 rounded-xl" />
                            <p v-if="roomForm.errors.room_number" class="text-sm text-destructive font-medium">{{ roomForm.errors.room_number }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="capacity" class="font-bold text-slate-700">Bed Capacity</Label>
                            <Input id="capacity" type="number" min="1" max="12" v-model="roomForm.capacity" class="h-12 rounded-xl" />
                            <p v-if="roomForm.errors.capacity" class="text-sm text-destructive font-medium">{{ roomForm.errors.capacity }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isRoomModalOpen = false" class="rounded-full">Cancel</Button>
                        <Button type="submit" :disabled="roomForm.processing" class="rounded-full font-bold px-6 shadow-md">Create Room</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Room Modal -->
        <Dialog :open="isEditRoomModalOpen" @update:open="isEditRoomModalOpen = $event">
            <DialogContent class="sm:max-w-[425px] rounded-3xl p-6">
                <DialogHeader>
                    <DialogTitle class="text-2xl font-black">Edit Room Unit</DialogTitle>
                    <DialogDescription>
                        Modify room details and bed capacity.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEditRoom">
                    <div class="grid gap-4 py-4">
                        <div class="space-y-2">
                            <Label for="edit_room_number" class="font-bold text-slate-700">Room Number</Label>
                            <Input id="edit_room_number" v-model="roomForm.room_number" class="h-12 rounded-xl" />
                            <p v-if="roomForm.errors.room_number" class="text-sm text-destructive font-medium">{{ roomForm.errors.room_number }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_capacity" class="font-bold text-slate-700">Bed Capacity</Label>
                            <Input id="edit_capacity" type="number" min="1" max="12" v-model="roomForm.capacity" class="h-12 rounded-xl" />
                            <p v-if="roomForm.errors.capacity" class="text-sm text-destructive font-medium">{{ roomForm.errors.capacity }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isEditRoomModalOpen = false" class="rounded-full">Cancel</Button>
                        <Button type="submit" :disabled="roomForm.processing" class="rounded-full font-bold px-6 shadow-md">Update Room</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Hostel Modal -->
        <Dialog :open="isEditHostelModalOpen" @update:open="isEditHostelModalOpen = $event">
            <DialogContent class="sm:max-w-[450px] rounded-3xl p-6">
                <DialogHeader>
                    <DialogTitle class="text-2xl font-black">Edit Hostel Details</DialogTitle>
                    <DialogDescription>
                        Modify hostel name and gender allocation.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEditHostel">
                    <div class="grid gap-5 py-5">
                        <div class="space-y-2">
                            <Label for="edit_hostel_name" class="font-bold text-slate-700">Hostel Name</Label>
                            <Input id="edit_hostel_name" v-model="hostelForm.name" class="h-12 rounded-xl" />
                            <p v-if="hostelForm.errors.name" class="text-sm text-destructive font-medium">{{ hostelForm.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_hostel_gender" class="font-bold text-slate-700">Gender Allocation</Label>
                            <Select v-model="hostelForm.gender_type">
                                <SelectTrigger class="h-12 rounded-xl">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="mixed">Mixed Residence</SelectItem>
                                    <SelectItem value="male">Male Only</SelectItem>
                                    <SelectItem value="female">Female Only</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="hostelForm.errors.gender_type" class="text-sm text-destructive font-medium">{{ hostelForm.errors.gender_type }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_hostel_description" class="font-bold text-slate-700">Description</Label>
                            <Textarea id="edit_hostel_description" v-model="hostelForm.description" rows="3" class="rounded-xl" />
                            <p v-if="hostelForm.errors.description" class="text-sm text-destructive font-medium">{{ hostelForm.errors.description }}</p>
                        </div>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="isEditHostelModalOpen = false" class="rounded-full">Cancel</Button>
                        <Button type="submit" :disabled="hostelForm.processing" class="rounded-full font-bold px-8 shadow-md">Save Changes</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Room Excel Import Modal -->
        <Dialog v-model:open="roomImportModalOpen">
            <DialogContent class="sm:max-w-[520px] rounded-3xl p-6 shadow-2xl">
                <DialogHeader class="border-b pb-4">
                    <DialogTitle class="text-xl font-black flex items-center gap-2 text-foreground">
                        <FileSpreadsheet class="h-6 w-6 text-emerald-600" /> Import Rooms via Excel / CSV
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Upload an Excel (.xlsx, .xls, .csv) file to automatically create blocks, floors, and hostel rooms for <strong>{{ hostel.name }}</strong>.
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
                            <p class="text-xs text-muted-foreground">Download pre-formatted template with headers and example room rows.</p>
                        </div>
                        <a 
                            :href="route('admin.hostels.rooms.import-template')" 
                            target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs font-bold px-4 py-2 bg-background border rounded-xl shadow-xs hover:bg-muted text-primary shrink-0 transition-colors"
                        >
                            <Download class="h-3.5 w-3.5" /> Download
                        </a>
                    </div>

                    <!-- Step 2: Structure Selection (Block & Floor) -->
                    <div class="space-y-4 bg-muted/20 p-4 rounded-2xl border">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Target Structure Selection (Optional)</h4>

                        <!-- Select Block -->
                        <div class="space-y-1.5">
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

                        <!-- Select Floor (only if Block selected) -->
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
                            Expected headers: <code>block_name</code>, <code>floor_name</code>, <code>room_number</code>, <code>capacity</code>, <code>is_visible</code>.
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
