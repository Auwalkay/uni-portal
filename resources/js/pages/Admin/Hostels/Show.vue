<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Edit, Trash2, ArrowLeft, Layers, DoorOpen, Bed, Building, MapPin, Grid } from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { route } from 'ziggy-js';

const props = defineProps<{
    hostel: {
        id: string;
        name: string;
        gender_type: string;
        description: string;
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
                }>;
            }>;
        }>;
    };
}>();

const isBlockModalOpen = ref(false);
const isFloorModalOpen = ref(false);
const isRoomModalOpen = ref(false);
const isEditRoomModalOpen = ref(false);

const activeBlockId = ref<string | null>(null);
const activeFloorId = ref<string | null>(null);
const activeRoom = ref<any>(null);

// Initialize active block if blocks exist
onMounted(() => {
    if (props.hostel.blocks.length > 0) {
        activeBlockId.value = props.hostel.blocks[0].id;
    }
});

// Watch for changes in blocks to ensure an active block is set
watch(() => props.hostel.blocks, (blocks) => {
    if (blocks.length > 0 && !blocks.find(b => b.id === activeBlockId.value)) {
        activeBlockId.value = blocks[0].id;
    } else if (blocks.length === 0) {
        activeBlockId.value = null;
    }
}, { deep: true });

const currentBlock = computed(() => {
    return props.hostel.blocks.find(b => b.id === activeBlockId.value);
});


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

const totalBlocks = computed(() => props.hostel.blocks.length);
const totalFloors = computed(() => props.hostel.blocks.reduce((sum, block) => sum + block.floors.length, 0));
const totalRooms = computed(() => props.hostel.blocks.reduce((sum, block) => {
    return sum + block.floors.reduce((fSum, floor) => fSum + floor.rooms.length, 0);
}, 0));
const totalCapacity = computed(() => props.hostel.blocks.reduce((sum, block) => {
    return sum + block.floors.reduce((fSum, floor) => {
        return fSum + floor.rooms.reduce((rSum, room) => rSum + room.capacity, 0);
    }, 0);
}, 0));

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

const submitBlock = () => {
    blockForm.post(route('admin.hostels.blocks.store', props.hostel.id), {
        onSuccess: () => {
            isBlockModalOpen.value = false;
        },
    });
};

const submitFloor = () => {
    if(!activeBlockId.value) return;
    floorForm.post(route('admin.hostels.floors.store', [props.hostel.id, activeBlockId.value]), {
        onSuccess: () => {
            isFloorModalOpen.value = false;
        },
    });
};

const submitRoom = () => {
    if(!activeBlockId.value || !activeFloorId.value) return;
    roomForm.post(route('admin.hostels.rooms.store', [props.hostel.id, activeBlockId.value, activeFloorId.value]), {
        onSuccess: () => {
            isRoomModalOpen.value = false;
        },
    });
};

const submitEditRoom = () => {
    if(!activeBlockId.value || !activeFloorId.value || !activeRoom.value) return;
    roomForm.put(route('admin.hostels.rooms.update', [props.hostel.id, activeBlockId.value, activeFloorId.value, activeRoom.value.id]), {
        onSuccess: () => {
            isEditRoomModalOpen.value = false;
        },
    });
};

const deleteBlock = (blockId: string) => {
    if (confirm('Are you sure you want to delete this block? All floors and rooms inside will be permanently lost.')) {
        useForm({}).delete(route('admin.hostels.blocks.destroy', [props.hostel.id, blockId]), {
            onSuccess: () => {},
        });
    }
};

const deleteFloor = (floorId: string) => {
    if (!activeBlockId.value) return;
    if (confirm('Are you sure you want to delete this floor? All rooms inside will be permanently lost.')) {
        useForm({}).delete(route('admin.hostels.floors.destroy', [props.hostel.id, activeBlockId.value, floorId]), {
            onSuccess: () => {},
        });
    }
};

const deleteRoom = (floorId: string, roomId: string) => {
    if (!activeBlockId.value) return;
    if (confirm('Are you sure you want to delete this room?')) {
        useForm({}).delete(route('admin.hostels.rooms.destroy', [props.hostel.id, activeBlockId.value, floorId, roomId]), {
            onSuccess: () => {},
        });
    }
};

const getGenderDisplay = (gender: string) => {
    if (gender === 'male') return { label: 'Male Hostel', class: 'bg-blue-50 text-blue-700 border-blue-200' };
    if (gender === 'female') return { label: 'Female Hostel', class: 'bg-pink-50 text-pink-700 border-pink-200' };
    return { label: 'Mixed Hostel', class: 'bg-purple-50 text-purple-700 border-purple-200' };
};
</script>

<template>
    <Head :title="`${hostel.name} | Hostel Management`" />

    <AdminLayout>
        <!-- Full-width sophisticated header -->
        <div class="-mt-4 -mx-4 sm:-mt-6 sm:-mx-8 mb-8">
            <div class="h-48 bg-slate-900 relative flex items-end w-full overflow-hidden">
                <!-- Abstract pattern overlay -->
                <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>
                
                <div class="absolute top-6 left-6 z-10">
                    <Button variant="outline" size="sm" @click="$inertia.visit(route('admin.hostels.index'))" class="bg-white/10 hover:bg-white/20 text-white border-white/20 backdrop-blur-sm shadow-sm transition-all">
                        <ArrowLeft class="mr-2 h-4 w-4" /> Back to Directory
                    </Button>
                </div>
            </div>

            <!-- Header Content -->
            <div class="px-6 sm:px-10 -mt-16 relative z-20">
                <div class="bg-card rounded-2xl shadow-sm border p-6 flex flex-col lg:flex-row justify-between lg:items-center gap-6">
                    <div class="flex items-start sm:items-center gap-5 flex-col sm:flex-row">
                        <div class="h-20 w-20 rounded-xl bg-primary/10 flex items-center justify-center shrink-0 border shadow-inner">
                            <Building class="h-10 w-10 text-primary" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-1">
                                <h1 class="text-3xl font-extrabold tracking-tight text-foreground">{{ hostel.name }}</h1>
                                <span :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full border', getGenderDisplay(hostel.gender_type).class]">
                                    {{ getGenderDisplay(hostel.gender_type).label }}
                                </span>
                            </div>
                            <div class="flex items-center text-muted-foreground text-sm">
                                <MapPin class="h-4 w-4 mr-1.5 opacity-70" />
                                <span>Campus Accommodations</span>
                            </div>
                            <p class="text-base text-muted-foreground mt-2 max-w-3xl leading-relaxed">
                                {{ hostel.description || 'No detailed description provided for this accommodation block.' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0">
                        <Button @click="openBlockModal" size="lg" class="shadow-md rounded-full px-6">
                            <Plus class="mr-2 h-5 w-5" /> Add New Block
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="space-y-8 w-full max-w-[1600px] mx-auto pb-12">
            
            <!-- Key Statistics Metrics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">
                        <Grid class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Blocks</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-foreground">{{ totalBlocks }}</h3>
                    </div>
                </div>
                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">
                        <Layers class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Floors</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-foreground">{{ totalFloors }}</h3>
                    </div>
                </div>
                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <DoorOpen class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Rooms</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-foreground">{{ totalRooms }}</h3>
                    </div>
                </div>
                <div class="bg-card rounded-2xl border shadow-sm p-6 flex items-center space-x-4">
                    <div class="h-14 w-14 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <Bed class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-muted-foreground uppercase tracking-wider">Total Capacity</p>
                        <div class="flex items-baseline space-x-1">
                            <h3 class="text-2xl sm:text-3xl font-bold text-foreground">{{ totalCapacity }}</h3>
                            <span class="text-sm font-medium text-muted-foreground">beds</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Blocks Tabs Section -->
            <div v-if="hostel.blocks.length === 0" class="flex flex-col items-center justify-center py-24 text-center bg-card shadow-sm border rounded-2xl">
                <div class="h-24 w-24 bg-muted/50 rounded-full flex items-center justify-center mb-6 ring-8 ring-muted/20">
                    <Grid class="h-10 w-10 text-muted-foreground/60" />
                </div>
                <h3 class="text-2xl font-bold text-foreground tracking-tight">Ready to build?</h3>
                <p class="text-base text-muted-foreground mt-3 max-w-lg">
                    This hostel is currently an empty shell. Add your first Block (e.g. Block A, Annex) to start mapping out floors and floors rooms.
                </p>
                <Button @click="openBlockModal" size="lg" class="mt-8 rounded-full shadow-md font-semibold px-8 h-12">
                    <Plus class="mr-2 h-5 w-5" /> Initialize First Block
                </Button>
            </div>

            <div v-else class="space-y-6">
                <!-- Tab Navigation for Blocks -->
                <div class="flex items-center space-x-2 overflow-x-auto pb-2 scrollbar-none">
                    <button 
                        v-for="block in hostel.blocks" 
                        :key="block.id"
                        @click="activeBlockId = block.id"
                        :class="[
                            'px-6 py-3 rounded-xl font-bold text-sm tracking-wide transition-all whitespace-nowrap border',
                            activeBlockId === block.id 
                                ? 'bg-primary text-primary-foreground border-primary shadow-md' 
                                : 'bg-card text-muted-foreground border-slate-200 hover:bg-slate-50 hover:text-slate-700'
                        ]"
                    >
                        {{ block.name }}
                    </button>
                    
                    <button @click="openBlockModal" class="px-5 py-3 rounded-xl font-bold text-sm tracking-wide transition-all whitespace-nowrap border border-dashed border-slate-300 text-slate-500 hover:bg-slate-50 flex items-center">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Block
                    </button>
                </div>

                <!-- Current Block Controls -->
                <div v-if="currentBlock" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-slate-50 border rounded-2xl p-5 mb-2 shadow-sm">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-foreground flex items-center">
                            Managing {{ currentBlock.name }}
                        </h2>
                        <p class="text-sm text-muted-foreground mt-0.5">Use the controls to configure floors mapped precisely to this particular block.</p>
                    </div>
                    <div class="flex items-center space-x-3 shrink-0">
                        <Button variant="default" @click="openFloorModal" class="rounded-full shadow-sm">
                            <Plus class="h-4 w-4 mr-2" /> Add Floor to {{ currentBlock.name }}
                        </Button>
                        <Button variant="outline" class="text-destructive hover:bg-destructive hover:text-destructive-foreground hover:border-destructive rounded-full" @click="deleteBlock(currentBlock.id)">
                            <Trash2 class="h-4 w-4 mr-2" /> Delete Block
                        </Button>
                    </div>
                </div>

                <!-- Floors Matrix for the active block -->
                <div v-if="currentBlock && currentBlock.floors.length === 0" class="py-16 text-center bg-white border border-slate-200 shadow-sm rounded-2xl flex flex-col items-center justify-center">
                    <div class="h-16 w-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 ring-4 ring-slate-100">
                        <Layers class="h-8 w-8 text-slate-400" />
                    </div>
                    <h3 class="text-xl font-bold text-slate-700">Empty Block</h3>
                    <p class="text-base text-slate-500 mt-2 max-w-sm">No floors have been created in this block yet. Start building your structure.</p>
                    <Button @click="openFloorModal" class="mt-6 rounded-full shadow-sm">
                        <Plus class="mr-2 h-4 w-4" /> Initialize First Floor
                    </Button>
                </div>

                <div v-else-if="currentBlock" class="grid grid-cols-1 gap-10">
                    <!-- Floor Structural Cards -->
                    <div v-for="(floor, index) in currentBlock.floors" :key="floor.id" class="relative mb-3 bg-white border border-slate-200 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden flex flex-col group transition-all hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)]">
                       
                       <!-- Decorative accent line -->
                       <div class="absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b from-primary/80 to-primary/30 z-10"></div>
    
                       <!-- Floor Header Ribbon -->
                       <div class="bg-gradient-to-r from-slate-50 to-white px-8 py-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-6 relative overflow-hidden">
                            <!-- Abstract watermark -->
                            <div class="absolute right-20 -top-8 text-slate-900/[0.02] pointer-events-none transform -rotate-12">
                                <Layers class="w-48 h-48" />
                            </div>
    
                            <div class="flex items-center space-x-5 relative z-10">
                                <div class="h-14 w-14 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center shrink-0 text-primary bg-gradient-to-br from-white to-slate-50">
                                    <span class="font-extrabold text-2xl">{{ index + 1 }}</span>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-2xl text-slate-800 tracking-tight">{{ floor.name }}</h3>
                                    <div class="flex items-center mt-1.5 space-x-3 text-sm text-slate-500 font-medium">
                                        <span class="flex items-center bg-slate-100 px-2.5 py-1 rounded-md text-slate-600">
                                            <DoorOpen class="h-4 w-4 mr-1.5 opacity-70" />
                                            {{ floor.rooms.length }} {{ floor.rooms.length === 1 ? 'room allocated' : 'rooms allocated' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4 shrink-0 relative z-10">
                                <Button variant="default" @click="openRoomModal(floor.id)" class="rounded-xl shadow-sm hover:shadow-md transition-all font-semibold px-5 h-10">
                                    <Plus class="h-4 w-4 mr-2" /> Allocate Room
                                </Button>
                                <div class="w-px h-10 bg-slate-200 mx-1 block hidden sm:block"></div>
                                <Button variant="ghost" size="icon" class="text-slate-400 hover:bg-red-50 hover:text-red-600 rounded-xl h-10 w-10 transition-colors" @click="deleteFloor(floor.id)" title="Decommission floor">
                                    <Trash2 class="h-5 w-5" />
                                </Button>
                            </div>
                       </div>
    
                       <!-- Rooms Matrix -->
                       <div class="p-8 bg-slate-50/50">
                            <div v-if="floor.rooms.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                                <div v-for="room in floor.rooms" :key="room.id" class="relative group/room flex flex-col items-center justify-center p-6 rounded-2xl border border-slate-200 bg-white shadow-sm hover:border-primary/30 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                                    
                                    <div class="flex flex-col items-center text-center space-y-3 w-full transition-opacity duration-300 group-hover/room:opacity-5">
                                        <div class="h-12 w-12 rounded-xl bg-slate-50 flex items-center justify-center mb-1 text-slate-400 ring-1 ring-slate-100">
                                            <DoorOpen class="h-6 w-6" :stroke-width="1.5" />
                                        </div>
                                        <span class="text-2xl font-extrabold tracking-tight text-slate-800">{{ room.room_number }}</span>
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 tracking-wide uppercase">
                                            {{ room.capacity }} Beds
                                        </span>
                                    </div>
                                    
                                    <!-- Hover Actions -->
                                    <div class="absolute inset-0 bg-white/95 backdrop-blur-sm rounded-2xl flex flex-col justify-center items-center space-y-4 opacity-0 scale-95 group-hover/room:opacity-100 group-hover/room:scale-100 transition-all duration-200 border border-primary/20">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Room {{ room.room_number }} Actions</p>
                                        <div class="flex space-x-3">
                                            <Button variant="secondary" size="icon" class="h-10 w-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 shadow-sm" @click="openEditRoomModal(floor.id, room)" title="Modify Capacity">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button variant="destructive" size="icon" class="h-10 w-10 rounded-xl shadow-sm" @click="deleteRoom(floor.id, room.id)" title="Decommission Room">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
    
                            <!-- Empty Floor State -->
                            <div v-else class="py-20 rounded-2xl border-2 border-dashed border-slate-200 bg-white/50 flex flex-col items-center justify-center">
                                 <div class="h-20 w-20 bg-slate-100 rounded-full flex items-center justify-center mb-5 ring-8 ring-slate-50">
                                    <DoorOpen class="h-10 w-10 text-slate-300" />
                                 </div>
                                 <p class="text-xl font-bold text-slate-700 tracking-tight">Level Unoccupied</p>
                                 <p class="text-base text-slate-500 mt-2 mb-8 text-center max-w-md">No rooms have been physically mapped to this floor structurally yet.</p>
                                 <Button variant="outline" size="lg" @click="openRoomModal(floor.id)" class="rounded-full mb-3 border-slate-300 hover:bg-slate-50 shadow-sm px-6">
                                    <Plus class="h-5 w-5 text-primary mr-2" /> Allocate First Room Here
                                 </Button>
                            </div>
                       </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Add Block Modal -->
        <Dialog :open="isBlockModalOpen" @update:open="isBlockModalOpen = $event">
            <DialogContent class="sm:max-w-[425px] rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-xl">Create New Block</DialogTitle>
                    <DialogDescription>Add a new block/wing to this hostel.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitBlock">
                    <div class="grid gap-5 py-6">
                        <div class="space-y-3">
                            <Label for="block_name" class="font-semibold text-slate-700">Block Name</Label>
                            <Input id="block_name" v-model="blockForm.name" placeholder="e.g. Block A, Wing C, Main Building" class="h-12" />
                            <p v-if="blockForm.errors.name" class="text-sm text-destructive font-medium">{{ blockForm.errors.name }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="isBlockModalOpen = false" class="rounded-full">Cancel</Button>
                        <Button type="submit" :disabled="blockForm.processing" class="rounded-full px-8 shadow-sm">Initialize Block</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Add Floor Modal -->
        <Dialog :open="isFloorModalOpen" @update:open="isFloorModalOpen = $event">
            <DialogContent class="sm:max-w-[425px] rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-xl">Create New Floor</DialogTitle>
                    <DialogDescription>Define a new structural level for this block.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitFloor">
                    <div class="grid gap-5 py-6">
                        <div class="space-y-3">
                            <Label for="floor_name" class="font-semibold text-slate-700">Floor Designation (Name)</Label>
                            <Input id="floor_name" v-model="floorForm.name" placeholder="e.g. Mezzanine, 1st Floor, Level 1" class="h-12" />
                            <p v-if="floorForm.errors.name" class="text-sm text-destructive font-medium">{{ floorForm.errors.name }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="isFloorModalOpen = false" class="rounded-full">Cancel</Button>
                        <Button type="submit" :disabled="floorForm.processing" class="rounded-full px-8 shadow-sm">Initialize Level</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Add/Edit Room Modal -->
        <Dialog :open="isRoomModalOpen || isEditRoomModalOpen" @update:open="(val) => { isRoomModalOpen = val; isEditRoomModalOpen = val; }">
            <DialogContent class="sm:max-w-[425px] rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="text-xl">{{ isEditRoomModalOpen ? 'Edit Room Specifications' : 'Allocate New Room' }}</DialogTitle>
                    <DialogDescription v-if="!isEditRoomModalOpen">Create a new occupiable space on the selected floor.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="isEditRoomModalOpen ? submitEditRoom() : submitRoom()">
                    <div class="grid gap-6 py-6">
                        <div class="space-y-3">
                            <Label for="room_number" class="font-semibold text-slate-700">Room Number / Identifier</Label>
                            <div class="relative">
                                <DoorOpen class="absolute left-3 top-3.5 h-5 w-5 text-muted-foreground" />
                                <Input id="room_number" v-model="roomForm.room_number" placeholder="e.g. 101, A12" class="pl-10 h-12 text-lg font-mono" />
                            </div>
                            <p v-if="roomForm.errors.room_number" class="text-sm text-destructive font-medium">{{ roomForm.errors.room_number }}</p>
                        </div>
                        <div class="space-y-3">
                            <Label for="capacity" class="font-semibold text-slate-700">Bed Capacity</Label>
                            <div class="relative w-1/2">
                                <Bed class="absolute left-3 top-3.5 h-5 w-5 text-muted-foreground" />
                                <Input id="capacity" type="number" min="1" max="20" v-model="roomForm.capacity" class="pl-10 h-12 text-lg" />
                            </div>
                            <p class="text-xs text-muted-foreground">Maximum occupants permitted.</p>
                            <p v-if="roomForm.errors.capacity" class="text-sm text-destructive font-medium">{{ roomForm.errors.capacity }}</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="isRoomModalOpen = false; isEditRoomModalOpen = false;" class="rounded-full">Discard</Button>
                        <Button type="submit" :disabled="roomForm.processing" class="rounded-full px-8 shadow-sm">{{ isEditRoomModalOpen ? 'Save Changes' : 'Confirm Allocation' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    </AdminLayout>
</template>
