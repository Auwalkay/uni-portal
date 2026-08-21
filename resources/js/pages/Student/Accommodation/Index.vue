<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    Home, 
    CheckCircle2, 
    XCircle, 
    ChevronRight, 
    BedDouble, 
    ArrowRight, 
    ArrowLeft,
    LayoutDashboard, 
    Building2, 
    Layers, 
    MapPin,
    BadgeCheck,
    AlertCircle,
    Receipt,
    History,
    Download,
    FileText,
    Clock,
    Lock,
    VolumeX,
    ShieldAlert,
    Sparkles,
    BookOpen
} from 'lucide-vue-next';

import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
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
    hasPaidFees: boolean;
    hasRegisteredCourses: boolean;
    hostels: any[];
    existingBooking: any | null;
    isBookingActive: boolean;
}>();

const canBook = computed(() => props.hasPaidFees && props.isBookingActive);

// Booking State
const selectedHostelId = ref<string | null>(null);
const selectedBlockId = ref<string | null>(null);
const selectedFloorId = ref<string | null>(null);
const selectedRoomId = ref<string | null>(null);
const isBooking = ref(false);

// Hostel Rules & Agreement Modal State
const rulesModalOpen = ref(false);
const rulesAgreed = ref(false);

const activeHostel = computed(() => {
    return props.hostels?.find((h: any) => h.id === selectedHostelId.value);
});

const activeBlock = computed(() => {
    return activeHostel.value?.blocks?.find((b: any) => b.id === selectedBlockId.value);
});

const activeFloor = computed(() => {
    return activeBlock.value?.floors?.find((f: any) => f.id === selectedFloorId.value);
});

const activeRoom = computed(() => {
    return activeFloor.value?.rooms?.find((r: any) => r.id === selectedRoomId.value);
});

const selectHostel = (hostel: any) => {
    selectedHostelId.value = hostel.id;
    selectedBlockId.value = null;
    selectedFloorId.value = null;
    selectedRoomId.value = null;

    if (hostel.blocks?.length === 1) {
        selectedBlockId.value = hostel.blocks[0].id;
        if (hostel.blocks[0].floors?.length === 1) {
            selectedFloorId.value = hostel.blocks[0].floors[0].id;
        }
    }
};

const selectRoomAndConfirm = (room: any) => {
    if (room.is_full) return;
    selectedRoomId.value = room.id;
    rulesAgreed.value = false;
    rulesModalOpen.value = true;
};

const bookRoom = () => {
    if (!selectedRoomId.value || !rulesAgreed.value) return;
    isBooking.value = true;
    router.post(route('student.accommodation.store'), {
        hostel_room_id: selectedRoomId.value,
    }, {
        onSuccess: () => {
            rulesModalOpen.value = false;
        },
        onFinish: () => { 
            isBooking.value = false; 
        }
    });
};

const getHostelAvailableBeds = (hostel: any) => {
    let count = 0;
    (hostel.blocks || []).forEach((b: any) => {
        (b.floors || []).forEach((f: any) => {
            (f.rooms || []).forEach((r: any) => {
                count += r.available_beds || 0;
            });
        });
    });
    return count;
};

const getHostelCardGradient = (gender: string) => {
    if (gender === 'male') return 'from-blue-600 via-indigo-700 to-slate-900';
    if (gender === 'female') return 'from-pink-600 via-rose-700 to-slate-900';
    return 'from-purple-600 via-indigo-800 to-slate-900';
};
</script>

<template>
    <StudentLayout :breadcrumbs="[{ title: 'Accommodation', href: '/student/accommodation' }]">
        <Head title="Accommodation Booking" />

        <div class="flex flex-col min-h-[calc(100vh-4rem)] bg-background/50">
            <!-- Hero Section -->
            <div class="relative overflow-hidden border-b bg-background px-6 py-12 md:px-12 lg:py-16">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(45%_45%_at_50%_50%,var(--primary-muted),transparent)] opacity-20"></div>
                <div class="absolute inset-0 -z-10 bg-[grid-line_1px_1px_rgba(0,0,0,0.05)] [mask-image:radial-gradient(ellipse_at_center,black,transparent)]"></div>

                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between max-w-[1600px] mx-auto">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" class="px-2.5 py-0.5 text-[10px] uppercase tracking-wider font-bold border-primary text-primary">Academic Session 2024/2025</Badge>
                        </div>
                        <h1 class="text-4xl font-extrabold tracking-tight lg:text-5xl">Room Reservation</h1>
                        <p class="text-lg text-muted-foreground max-w-2xl">
                            Find your perfect living space on campus. Browse available hostels, blocks, and reserve your bed in seconds.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div v-if="existingBooking" class="flex flex-col items-end text-right">
                            <Badge variant="default" class="bg-primary hover:bg-primary shadow-lg px-4 py-1.5 gap-2 text-sm">
                                <BadgeCheck class="h-4 w-4" />
                                Booking Active
                            </Badge>
                            <p class="text-xs text-muted-foreground mt-2 font-medium">Reference: {{ existingBooking.invoice?.reference }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Body -->
            <main class="flex-1 px-6 py-12 md:px-12 max-w-[1600px] mx-auto w-full">
                <!-- If student already has an active allocation -->
                <div v-if="existingBooking" class="max-w-4xl mx-auto space-y-8">
                    <Card class="rounded-3xl border shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-8 sm:p-10 space-y-6">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <Badge variant="outline" class="border-white/30 text-white bg-white/10 px-3 py-1 font-bold text-xs uppercase w-fit">
                                    Active Allocation
                                </Badge>
                                <span class="text-xs font-bold text-white/70">Session: {{ existingBooking.session?.name || 'Current' }}</span>
                            </div>
                            <div>
                                <h2 class="text-3xl font-black tracking-tight">{{ existingBooking.room?.floor?.block?.hostel?.name || 'Hostel Reserved' }}</h2>
                                <p class="text-sm text-white/80 mt-1 font-medium">
                                    Block: {{ existingBooking.room?.floor?.block?.name || 'N/A' }} • Floor: {{ existingBooking.room?.floor?.name || 'N/A' }} • Room: {{ existingBooking.room?.room_number || 'N/A' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-6 pt-4 border-t border-white/10 text-xs">
                                <div>
                                    <span class="text-white/60 block uppercase font-bold">Bed Space</span>
                                    <span class="font-extrabold text-base text-white">Bed #{{ existingBooking.bed_space_number || 1 }}</span>
                                </div>
                                <div>
                                    <span class="text-white/60 block uppercase font-bold">Status</span>
                                    <Badge :class="existingBooking.status === 'confirmed' ? 'bg-emerald-500' : 'bg-amber-500'" class="text-white font-bold uppercase mt-1">
                                        {{ existingBooking.status }}
                                    </Badge>
                                </div>
                                <div v-if="existingBooking.invoice">
                                    <span class="text-white/60 block uppercase font-bold">Invoice Ref</span>
                                    <span class="font-mono text-sm text-white font-bold">{{ existingBooking.invoice.reference }}</span>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <template v-else>
                    <!-- If booking is disabled or requirements not met -->
                    <div v-if="!canBook" class="max-w-2xl mx-auto space-y-6">
                        <Card class="rounded-3xl border shadow-sm p-8 text-center space-y-6">
                            <div class="h-16 w-16 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto border border-amber-200">
                                <AlertCircle class="h-8 w-8" />
                            </div>
                            <div class="space-y-2">
                                <h3 class="text-2xl font-bold">Hostel Booking Unavailable</h3>
                                <p class="text-sm text-muted-foreground max-w-md mx-auto">
                                    <span v-if="!isBookingActive">Hostel reservation is currently closed by the university administration.</span>
                                    <span v-else-if="!hasPaidFees">You must have an active paid school fee invoice for the current session to book accommodation.</span>
                                </p>
                            </div>
                        </Card>
                    </div>

                    <!-- Booking Flow -->
                    <div v-else class="space-y-12">
                        <!-- Hostel Grid -->
                        <div v-if="!selectedHostelId">
                            <div class="flex items-center justify-between mb-8">
                                <h2 class="text-2xl font-bold flex items-center gap-3">
                                    <MapPin class="h-6 w-6 text-primary" />
                                    Choose Your Residence
                                </h2>
                                <p class="text-sm text-muted-foreground font-medium italic">Showing hostels matching your student profile.</p>
                            </div>

                            <div v-if="hostels.length === 0" class="flex flex-col items-center justify-center p-20 rounded-[2rem] border-2 border-dashed border-border py-32">
                                <div class="p-6 rounded-full bg-muted/50 mb-6">
                                    <Building2 class="h-16 w-16 text-muted-foreground/30" />
                                </div>
                                <h3 class="text-2xl font-bold text-muted-foreground">No Available Hostels</h3>
                                <p class="text-muted-foreground/70 max-w-sm text-center mt-2 font-medium">There are currently no room allocations matching your criteria. Contact the Hall Warden for assistance.</p>
                            </div>

                            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                                <div 
                                    v-for="hostel in hostels" 
                                    :key="hostel.id" 
                                    class="group cursor-pointer rounded-3xl bg-card border shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden border-border/80 hover:border-primary/50 flex flex-col"
                                    @click="selectHostel(hostel)"
                                >
                                    <!-- Hostel Cover Banner -->
                                    <div :class="['relative h-48 bg-gradient-to-br p-6 text-white flex flex-col justify-between overflow-hidden', getHostelCardGradient(hostel.gender_type)]">
                                        <div class="absolute -right-6 -bottom-6 opacity-15 transform group-hover:scale-110 transition-transform duration-700">
                                            <Building2 class="h-44 w-44 text-white" />
                                        </div>

                                        <div class="flex items-center justify-between relative z-10">
                                            <Badge variant="outline" class="bg-white/20 backdrop-blur border-white/30 text-white font-black text-xs uppercase px-3 py-1 tracking-wider">
                                                {{ hostel.gender_type }} Residence
                                            </Badge>

                                            <span class="text-xs font-extrabold bg-black/40 backdrop-blur px-3 py-1 rounded-full text-white/90">
                                                {{ getHostelAvailableBeds(hostel) }} Beds Open
                                            </span>
                                        </div>

                                        <div class="relative z-10 mt-auto">
                                            <h3 class="text-2xl font-black text-white tracking-tight group-hover:translate-x-1 transition-transform">
                                                {{ hostel.name }}
                                            </h3>
                                        </div>
                                    </div>

                                    <!-- Hostel Body Info -->
                                    <div class="p-6 space-y-4 flex-1 flex flex-col justify-between">
                                        <p class="text-xs text-muted-foreground line-clamp-2 font-medium">
                                            {{ hostel.description || 'Modern campus living space equipped with essential amenities for students.' }}
                                        </p>

                                        <div class="pt-4 border-t flex items-center justify-between text-xs font-bold text-muted-foreground">
                                            <div class="flex items-center gap-1.5">
                                                <Layers class="h-4 w-4 text-primary" />
                                                <span>{{ hostel.blocks?.length || 0 }} Wing Blocks</span>
                                            </div>

                                            <div class="flex items-center gap-1 text-primary group-hover:translate-x-1 transition-transform">
                                                <span>Browse Rooms</span>
                                                <ArrowRight class="h-4 w-4" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Focused Hierarchy Tree -->
                        <div v-else class="max-w-6xl mx-auto space-y-10">
                            <!-- Clean Header Bar -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b">
                                <div class="flex items-center gap-4">
                                    <button 
                                        class="h-11 w-11 rounded-2xl bg-card border shadow-xs flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-muted transition-all shrink-0"
                                        @click="selectedHostelId = null"
                                        title="Back to Hostels"
                                    >
                                        <ArrowLeft class="h-5 w-5" />
                                    </button>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h2 class="text-3xl font-extrabold tracking-tight text-foreground">{{ activeHostel?.name }}</h2>
                                            <Badge variant="secondary" class="font-bold text-[10px] uppercase px-2.5 py-0.5 tracking-widest bg-primary/10 text-primary border-primary/20">
                                                {{ activeHostel?.gender_type }} Residence
                                            </Badge>
                                        </div>

                                        <!-- Matured Breadcrumb Navigation -->
                                        <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground mt-1">
                                            <span>{{ activeHostel?.name }}</span>
                                            <template v-if="selectedBlockId">
                                                <ChevronRight class="h-3.5 w-3.5 text-muted-foreground/40" />
                                                <span class="text-primary font-black">{{ activeBlock?.name }}</span>
                                            </template>
                                            <template v-if="selectedFloorId">
                                                <ChevronRight class="h-3.5 w-3.5 text-muted-foreground/40" />
                                                <span class="text-primary font-black">{{ activeFloor?.name }}</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-10 lg:grid-cols-12">
                                <!-- Navigation Sidebar -->
                                <div class="lg:col-span-4 space-y-8">
                                    <!-- 1. Wing Block Selection -->
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-[11px] font-black text-muted-foreground uppercase tracking-widest">1. Residential Wing</h4>
                                            <span class="text-[10px] font-bold text-muted-foreground bg-muted px-2 py-0.5 rounded-full">{{ activeHostel?.blocks?.length || 0 }} Wing(s)</span>
                                        </div>

                                        <div class="grid gap-2.5">
                                            <button 
                                                v-for="block in activeHostel?.blocks" 
                                                :key="block.id"
                                                @click="selectedBlockId = block.id; selectedFloorId = null; selectedRoomId = null"
                                                :class="[
                                                    'group relative flex items-center justify-between px-5 py-4 rounded-2xl text-left font-bold transition-all duration-300 border',
                                                    selectedBlockId === block.id 
                                                        ? 'bg-slate-900 text-white border-slate-900 shadow-xl shadow-slate-900/10' 
                                                        : 'bg-card border-border/80 text-foreground hover:bg-muted/40 hover:border-border'
                                                ]"
                                            >
                                                <div class="flex items-center gap-3.5">
                                                    <div :class="[
                                                        'p-2 rounded-xl border transition-colors',
                                                        selectedBlockId === block.id ? 'bg-white/10 border-white/20 text-white' : 'bg-muted/60 border-border text-muted-foreground group-hover:text-foreground'
                                                    ]">
                                                        <Building2 class="h-4 w-4" />
                                                    </div>
                                                    <div>
                                                        <span class="block text-sm leading-tight">{{ block.name }}</span>
                                                        <span :class="['text-[11px] font-normal', selectedBlockId === block.id ? 'text-white/70' : 'text-muted-foreground']">
                                                            {{ block.floors?.length || 0 }} Floor Level(s)
                                                        </span>
                                                    </div>
                                                </div>
                                                <ChevronRight :class="['h-4 w-4 transition-transform group-hover:translate-x-0.5', selectedBlockId === block.id ? 'text-white/80' : 'text-muted-foreground/40']" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 2. Floor Level Selection -->
                                    <div v-if="selectedBlockId" class="space-y-3 animate-in fade-in duration-300">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-[11px] font-black text-muted-foreground uppercase tracking-widest">2. Floor Level</h4>
                                            <span class="text-[10px] font-bold text-muted-foreground bg-muted px-2 py-0.5 rounded-full">{{ activeBlock?.floors?.length || 0 }} Level(s)</span>
                                        </div>

                                        <div class="grid gap-2.5">
                                            <button 
                                                v-for="floor in activeBlock?.floors" 
                                                :key="floor.id"
                                                @click="selectedFloorId = floor.id; selectedRoomId = null"
                                                :class="[
                                                    'group relative flex items-center justify-between px-5 py-4 rounded-2xl text-left font-bold transition-all duration-300 border',
                                                    selectedFloorId === floor.id 
                                                        ? 'bg-primary text-primary-foreground border-primary shadow-lg shadow-primary/20 scale-[1.01]' 
                                                        : 'bg-card border-border/80 text-foreground hover:bg-muted/40 hover:border-border'
                                                ]"
                                            >
                                                <div class="flex items-center gap-3.5">
                                                    <div :class="[
                                                        'p-2 rounded-xl border transition-colors',
                                                        selectedFloorId === floor.id ? 'bg-white/20 border-white/30 text-white' : 'bg-muted/60 border-border text-muted-foreground group-hover:text-foreground'
                                                    ]">
                                                        <Layers class="h-4 w-4" />
                                                    </div>
                                                    <div>
                                                        <span class="block text-sm leading-tight">{{ floor.name }}</span>
                                                        <span :class="['text-[11px] font-normal', selectedFloorId === floor.id ? 'text-white/80' : 'text-muted-foreground']">
                                                            {{ floor.rooms?.length || 0 }} Accommodation Unit(s)
                                                        </span>
                                                    </div>
                                                </div>
                                                <ChevronRight :class="['h-4 w-4 transition-transform group-hover:translate-x-0.5', selectedFloorId === floor.id ? 'text-white/80' : 'text-muted-foreground/40']" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Room Matrix -->
                                <div class="lg:col-span-8">
                                    <div v-if="!selectedFloorId" class="h-full flex flex-col items-center justify-center p-12 bg-muted/20 border-2 border-dashed border-border/60 rounded-[2.5rem] text-center space-y-4">
                                        <div class="p-5 bg-background rounded-2xl shadow-xs border">
                                            <Layers class="h-10 w-10 text-muted-foreground/40" />
                                        </div>
                                        <h3 class="text-xl font-extrabold text-foreground">Select a Level to View Bedspaces</h3>
                                        <p class="text-xs text-muted-foreground max-w-xs font-medium leading-relaxed">Choose a Wing Block and Floor Level from the sidebar to inspect available rooms.</p>
                                    </div>

                                    <div v-else class="space-y-6">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h3 class="text-xl font-extrabold text-foreground">Available Room Units</h3>
                                                <p class="text-xs text-muted-foreground font-medium">Click any open unit to instantly open the reservation agreement modal.</p>
                                            </div>
                                            <Badge variant="outline" class="font-bold border-muted-foreground/30 text-muted-foreground uppercase text-[10px] px-3 py-1">
                                                {{ activeFloor?.rooms?.length || 0 }} Units Available
                                            </Badge>
                                        </div>

                                        <!-- Sleek Room Grid -->
                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <button
                                                v-for="room in activeFloor?.rooms"
                                                :key="room.id"
                                                @click="selectRoomAndConfirm(room)"
                                                :disabled="room.is_full"
                                                :class="[
                                                    'group relative flex flex-col p-5 rounded-3xl border-2 transition-all duration-300 text-left bg-card',
                                                    room.is_full 
                                                        ? 'bg-muted/30 border-transparent opacity-50 cursor-not-allowed' 
                                                        : selectedRoomId === room.id 
                                                            ? 'border-primary bg-primary/[0.03] shadow-xl ring-2 ring-primary/20 scale-[1.02]' 
                                                            : 'border-border/80 hover:border-primary/40 hover:shadow-md'
                                                ]"
                                            >
                                                <div class="flex items-center justify-between mb-4">
                                                    <div :class="[
                                                        'p-2.5 rounded-2xl border shadow-xs transition-colors',
                                                        selectedRoomId === room.id ? 'bg-primary text-primary-foreground border-primary' : 'bg-muted/50 border-border text-foreground group-hover:bg-primary/10 group-hover:text-primary'
                                                    ]">
                                                        <BedDouble class="h-5 w-5" />
                                                    </div>

                                                    <Badge 
                                                        :variant="room.is_full ? 'destructive' : selectedRoomId === room.id ? 'default' : 'secondary'"
                                                        class="font-black text-[10px] px-2.5 py-0.5 uppercase tracking-wider rounded-lg"
                                                    >
                                                        {{ room.is_full ? 'FULL' : `${room.available_beds} Open` }}
                                                    </Badge>
                                                </div>

                                                <span class="font-black text-2xl tracking-tight text-foreground mb-1">Unit {{ room.room_number }}</span>
                                                <p class="text-[11px] font-bold text-muted-foreground mb-4">Capacity: {{ room.capacity }} Bedspaces</p>
                                                
                                                <!-- Visual Bed Capacity Indicators -->
                                                <div class="mt-auto pt-3 border-t flex items-center justify-between">
                                                    <div class="flex items-center gap-1.5">
                                                        <div 
                                                            v-for="idx in room.capacity" 
                                                            :key="idx" 
                                                            :class="[
                                                                'h-2 w-2 rounded-full transition-colors',
                                                                idx <= (room.capacity - room.available_beds) 
                                                                    ? 'bg-slate-400 opacity-50' 
                                                                    : 'bg-emerald-500 shadow-xs'
                                                            ]"
                                                            :title="idx <= (room.capacity - room.available_beds) ? 'Occupied' : 'Vacant Bed'"
                                                        ></div>
                                                    </div>
                                                    <span class="text-[10px] font-extrabold text-muted-foreground uppercase">
                                                        {{ room.is_full ? 'Occupied' : `${room.available_beds}/${room.capacity} Vacant` }}
                                                    </span>
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </main>

            <!-- Sticky Footer / Branding -->
            <footer class="border-t py-6 bg-card/50">
                <div class="px-6 md:px-12 max-w-[1600px] mx-auto flex items-center justify-between text-[10px] font-bold text-muted-foreground uppercase tracking-[0.2em]">
                    <div class="flex items-center gap-4 italic opacity-50">
                        <span>Powered by MIU Systems</span>
                        <div class="w-1 h-1 rounded-full bg-primary mb-0.5"></div>
                    </div>
                </div>
            </footer>
        </div>

        <!-- Hostel Rules & Code of Conduct Agreement Modal -->
        <Dialog v-model:open="rulesModalOpen">
            <DialogContent class="w-[94vw] sm:w-full max-w-xl max-h-[85vh] sm:max-h-[90vh] rounded-3xl p-4 sm:p-6 shadow-2xl flex flex-col overflow-hidden">
                <DialogHeader class="border-b pb-3.5 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-2xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0">
                            <ShieldAlert class="h-5 w-5" />
                        </div>
                        <div class="text-left">
                            <DialogTitle class="text-lg sm:text-xl font-black text-foreground">
                                Residential Rules & Code of Conduct
                            </DialogTitle>
                            <DialogDescription class="text-[11px] sm:text-xs mt-0.5">
                                Please review and accept the hostel terms before confirming your space reservation.
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="space-y-4 py-3 sm:py-4 flex-1 overflow-y-auto pr-1.5 sm:pr-2">
                    <!-- Summary Banner -->
                    <div class="bg-primary/5 border border-primary/20 rounded-2xl p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-left">
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-black uppercase tracking-wider text-primary">Selected Space Allocation</span>
                            <h4 class="text-sm sm:text-base font-black text-foreground">
                                Unit {{ activeRoom?.room_number }} • {{ activeFloor?.name }} ({{ activeBlock?.name }})
                            </h4>
                            <p class="text-xs text-muted-foreground font-medium">{{ activeHostel?.name }}</p>
                        </div>
                        <Badge variant="secondary" class="font-bold text-[10px] sm:text-xs shrink-0 w-fit">
                            {{ activeHostel?.gender_type?.toUpperCase() }} RESIDENCE
                        </Badge>
                    </div>

                    <!-- Rules List -->
                    <div class="space-y-2.5 sm:space-y-3 text-xs text-muted-foreground text-left">
                        <div class="p-3 sm:p-3.5 bg-card border rounded-2xl flex items-start gap-3">
                            <Clock class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                            <div>
                                <strong class="text-foreground font-bold block">1. 48-Hour Payment Window</strong>
                                Upon confirming your reservation, an accommodation invoice will be generated. Payment must be completed within <strong>48 hours (2 days)</strong> or the reservation will automatically expire and release the bedspace.
                            </div>
                        </div>

                        <div class="p-3 sm:p-3.5 bg-card border rounded-2xl flex items-start gap-3">
                            <Lock class="h-4 w-4 text-indigo-600 shrink-0 mt-0.5" />
                            <div>
                                <strong class="text-foreground font-bold block">2. Non-Transferable Allocation</strong>
                                Bed space allocation is strictly for the registered student. Subletting, selling, or swapping room spaces with other students is strictly prohibited and subject to disciplinary action.
                            </div>
                        </div>

                        <div class="p-3 sm:p-3.5 bg-card border rounded-2xl flex items-start gap-3">
                            <ShieldAlert class="h-4 w-4 text-red-600 shrink-0 mt-0.5" />
                            <div>
                                <strong class="text-foreground font-bold block">3. Curfew & Visitor Restrictions</strong>
                                Hall gates close at 10:00 PM daily. Visitors of opposite gender are restricted to common lounges and are strictly prohibited inside residential rooms.
                            </div>
                        </div>

                        <div class="p-3 sm:p-3.5 bg-card border rounded-2xl flex items-start gap-3">
                            <VolumeX class="h-4 w-4 text-purple-600 shrink-0 mt-0.5" />
                            <div>
                                <strong class="text-foreground font-bold block">4. Quiet Hours & Maintenance</strong>
                                Quiet hours start at 10:00 PM. Students must maintain room cleanliness and refrain from damaging university property. Vandalism results in immediate expulsion from residence.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer area with Agreement Checkbox and Action Buttons -->
                <div class="border-t pt-3.5 space-y-3 shrink-0">
                    <label class="flex items-start gap-3 p-3 bg-muted/40 border border-primary/20 rounded-2xl cursor-pointer hover:bg-muted/60 transition-colors text-left">
                        <input 
                            type="checkbox" 
                            v-model="rulesAgreed" 
                            class="h-4 w-4 sm:h-5 sm:w-5 rounded-md border-primary text-primary focus:ring-primary mt-0.5 cursor-pointer shrink-0"
                        />
                        <span class="text-[11px] sm:text-xs font-bold text-foreground leading-snug">
                            I have read, understood, and agree to abide by all University Hostel Rules & Code of Conduct. I accept that failure to pay within 48 hours forfeits my reservation.
                        </span>
                    </label>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-2">
                        <Button variant="outline" @click="rulesModalOpen = false" class="w-full sm:w-auto rounded-xl font-bold">
                            Cancel
                        </Button>
                        <Button 
                            @click="bookRoom" 
                            :disabled="!rulesAgreed || isBooking"
                            class="w-full sm:w-auto rounded-xl font-bold bg-primary text-primary-foreground shadow-lg px-6"
                        >
                            <CheckCircle2 class="mr-2 h-4 w-4" /> 
                            {{ isBooking ? 'Processing Booking...' : 'Confirm & Reserve Space' }}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </StudentLayout>
</template>

<style scoped>
.animate-in {
    animation-timing-function: cubic-bezier(0.2, 0.8, 0.2, 1);
}
</style>
