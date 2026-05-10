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
    LayoutDashboard, 
    Building2, 
    Layers, 
    MapPin,
    BadgeCheck,
    AlertCircle,
    Receipt,
    History,
    Download,
    FileText
} from 'lucide-vue-next';

import StudentLayout from '@/layouts/StudentLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { route } from 'ziggy-js';

const props = defineProps<{
    hasPaidFees: boolean;
    hasRegisteredCourses: boolean;
    hostels: any[];
    existingBooking: any | null;
}>();

const canBook = computed(() => props.hasPaidFees);

// Booking State
const selectedHostelId = ref<string | null>(null);
const selectedBlockId = ref<string | null>(null);
const selectedFloorId = ref<string | null>(null);
const selectedRoomId = ref<string | null>(null);
const isBooking = ref(false);

const activeHostel = computed(() => {
    return props.hostels?.find((h: any) => h.id === selectedHostelId.value);
});

const activeBlock = computed(() => {
    return activeHostel.value?.blocks.find((b: any) => b.id === selectedBlockId.value);
});

const activeFloor = computed(() => {
    return activeBlock.value?.floors.find((f: any) => f.id === selectedFloorId.value);
});

const activeRoom = computed(() => {
    return activeFloor.value?.rooms.find((r: any) => r.id === selectedRoomId.value);
});

const selectHostel = (hostel: any) => {
    selectedHostelId.value = hostel.id;
    selectedBlockId.value = null;
    selectedFloorId.value = null;
    selectedRoomId.value = null;

    // Auto-select if logic permits
    if (hostel.blocks?.length === 1) {
        selectedBlockId.value = hostel.blocks[0].id;
        if (hostel.blocks[0].floors?.length === 1) {
            selectedFloorId.value = hostel.blocks[0].floors[0].id;
        }
    }
};

const bookRoom = () => {
    if (!selectedRoomId.value) return;
    isBooking.value = true;
    router.post(route('student.accommodation.store'), {
        hostel_room_id: selectedRoomId.value,
    }, {
        onFinish: () => { isBooking.value = false; }
    });
};
</script>

<template>
    <StudentLayout :breadcrumbs="[{ title: 'Accommodation', href: '/student/accommodation' }]">
        <Head title="Accommodation Booking" />

        <div class="flex flex-col min-h-[calc(100vh-4rem)] bg-background/50">
            <!-- Hero Section -->
            <div class="relative overflow-hidden border-b bg-background px-6 py-12 md:px-12 lg:py-16">
                <!-- Background Pattern -->
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(45%_45%_at_50%_50%,var(--primary-muted),transparent)] opacity-20"></div>
                <div class="absolute inset-0 -z-10 bg-[grid-line_1px_1px_rgba(0,0,0,0.05)] [mask-image:radial-gradient(ellipse_at_center,black,transparent)]"></div>

                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between max-w-[1600px] mx-auto">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold border-primary text-primary">Academic Session 2024/2025</Badge>
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

            <main class="flex-1 w-full p-6 md:p-12 max-w-[1600px] mx-auto">
                <!-- Existing Booking Detail View -->
                <div v-if="existingBooking" class="animate-in fade-in slide-in-from-bottom-4 duration-700">
                    <div class="grid gap-6 lg:grid-cols-3">
                        <Card class="lg:col-span-2 overflow-hidden border-0 shadow-2xl ring-1 ring-border bg-gradient-to-br from-card to-background">
                            <div class="h-2 bg-primary w-full"></div>
                            <CardHeader class="pb-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-3 rounded-full bg-primary/10">
                                            <Home class="h-6 w-6 text-primary" />
                                        </div>
                                        <div>
                                            <CardTitle class="text-2xl">Assigned Accommodation</CardTitle>
                                            <CardDescription>Details of your current room placement</CardDescription>
                                        </div>
                                    </div>
                                    <Badge :variant="existingBooking.status === 'confirmed' ? 'default' : 'secondary'" class="text-sm px-4">
                                        {{ existingBooking.status.toUpperCase() }}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent class="pt-6">
                                <div class="grid gap-12 sm:grid-cols-2">
                                    <div class="space-y-6">
                                        <div class="flex items-start gap-4 p-4 rounded-xl bg-muted/30 border border-border/50">
                                            <Building2 class="h-5 w-5 mt-1 text-primary" />
                                            <div>
                                                <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-0.5">Hostel & Block</p>
                                                <p class="text-lg font-bold">{{ existingBooking.room?.floor?.block?.hostel?.name }}</p>
                                                <p class="text-sm text-primary font-medium">{{ existingBooking.room?.floor?.block?.name }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-4 p-4 rounded-xl bg-muted/30 border border-border/50">
                                            <Layers class="h-5 w-5 mt-1 text-primary" />
                                            <div>
                                                <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-0.5">Floor Level</p>
                                                <p class="text-lg font-bold">{{ existingBooking.room?.floor?.name }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        <div class="flex items-start gap-4 p-4 rounded-xl ring-2 ring-primary bg-primary/[0.03]">
                                            <BedDouble class="h-5 w-5 mt-1 text-primary" />
                                            <div>
                                                <p class="text-xs font-bold text-primary uppercase tracking-wider mb-0.5">Room Number</p>
                                                <p class="text-3xl font-black text-primary">Room {{ existingBooking.room?.room_number }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-4 p-4 rounded-xl bg-muted/30 border border-border/50">
                                            <Receipt class="h-5 w-5 mt-1 text-primary" />
                                            <div>
                                                <p class="text-xs font-bold text-muted-foreground uppercase tracking-wider mb-0.5">Payment Reference</p>
                                                <p class="font-mono font-bold">{{ existingBooking.invoice?.reference }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                            <CardFooter v-if="existingBooking.status === 'pending'" class="bg-muted/10 border-t p-6">
                                <div class="flex flex-col sm:flex-row items-center justify-between w-full gap-4">
                                    <div class="flex items-center gap-3 text-sm text-muted-foreground font-medium italic">
                                        <AlertCircle class="h-4 w-4 text-orange-500" />
                                        Please complete payment to finalize your room reservation.
                                    </div>
                                    <Button size="lg" class="w-full sm:w-auto shadow-lg" @click="router.visit(route('student.payments.index'))">
                                        Pay Hostel Fee &nbsp; <History class="h-4 w-4" />
                                    </Button>
                                </div>
                            </CardFooter>
                            <CardFooter v-else class="bg-muted/10 border-t p-6">
                                <div class="flex flex-col sm:flex-row items-center justify-end w-full gap-4">
                                    <a 
                                        :href="route('student.accommodation.download-slip')" 
                                        target="_blank"
                                        class="w-full sm:w-auto"
                                    >
                                        <Button 
                                            variant="outline" 
                                            size="lg" 
                                            class="w-full gap-2 font-bold border-primary/20 text-primary hover:bg-primary/5"
                                        >
                                            <Download class="h-4 w-4" />
                                            Allocation Slip
                                        </Button>
                                    </a>
                                    <a 
                                        :href="route('student.accommodation.download-payment')" 
                                        target="_blank"
                                        class="w-full sm:w-auto"
                                    >
                                        <Button 
                                            size="lg" 
                                            class="w-full gap-2 shadow-lg"
                                        >
                                            <FileText class="h-4 w-4" />
                                            Payment Receipt
                                        </Button>
                                    </a>
                                </div>
                            </CardFooter>
                        </Card>

                        <div class="space-y-6">
                            <Card class="border-0 shadow-xl ring-1 ring-border">
                                <CardHeader>
                                    <CardTitle class="text-lg">Location Map</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="aspect-square rounded-xl bg-muted flex items-center justify-center relative overflow-hidden group">
                                        <div class="absolute inset-0 bg-gradient-to-tr from-primary/20 to-transparent"></div>
                                        <MapPin class="h-12 w-12 text-primary animate-bounce" />
                                        <div class="absolute bottom-4 inset-x-4">
                                            <div class="bg-card/90 backdrop-blur p-3 rounded-lg border shadow-sm">
                                                <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-tighter">Current Designation</p>
                                                <p class="text-xs font-bold leading-tight line-clamp-1 italic">{{ existingBooking.room?.floor?.block?.hostel?.name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>

                <template v-else>
                    <!-- Enhanced Prerequisite Gate -->
                    <div v-if="!canBook" class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
                        <div class="max-w-4xl mx-auto space-y-8">
                            <div class="text-center space-y-4">
                                <div class="inline-flex items-center justify-center p-3 rounded-2xl bg-destructive/10 text-destructive mb-2">
                                    <LayoutDashboard class="h-8 w-8" />
                                </div>
                                <h2 class="text-3xl font-bold">Registration Lock</h2>
                                <p class="text-muted-foreground">Complete the following steps to unlock the room reservation system.</p>
                            </div>

                            <div class="max-w-md mx-auto">
                                <Card class="group relative overflow-hidden transition-all hover:shadow-2xl hover:-translate-y-1" :class="hasPaidFees ? 'border-primary' : 'border-border opacity-75'">
                                    <div v-if="hasPaidFees" class="absolute top-0 right-0 w-16 h-16 pointer-events-none">
                                        <div class="absolute top-0 right-0 p-2 text-primary">
                                            <CheckCircle2 class="h-6 w-6" />
                                        </div>
                                    </div>
                                    <CardHeader>
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-2" :class="hasPaidFees ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'">
                                            <Receipt class="h-6 w-6" />
                                        </div>
                                        <CardTitle class="text-xl">Step 1: School Fees</CardTitle>
                                        <CardDescription>Official university fees must be settled for the 2024/2025 session.</CardDescription>
                                    </CardHeader>
                                    <CardFooter>
                                        <Button v-if="!hasPaidFees" variant="outline" class="w-full font-bold group-hover:bg-primary group-hover:text-primary-foreground" @click="router.visit(route('student.payments.index'))">
                                            Begin Payment Flow
                                        </Button>
                                        <Badge v-else variant="outline" class="border-primary text-primary px-4">Requirement Met</Badge>
                                    </CardFooter>
                                </Card>

                            </div>
                        </div>
                    </div>

                    <!-- Modern Booking Fluid Flow -->
                    <div v-else class="space-y-12 animate-in fade-in zoom-in-95 duration-1000">
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

                            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                <div 
                                    v-for="hostel in hostels" 
                                    :key="hostel.id" 
                                    class="group cursor-pointer"
                                    @click="selectHostel(hostel)"
                                >
                                    <div class="relative aspect-[4/3] rounded-3xl overflow-hidden mb-4 shadow-xl ring-1 ring-border group-hover:ring-primary group-hover:scale-[1.02] transition-all duration-500">
                                        <!-- Decorative Image/Gradient -->
                                        <div class="absolute inset-0 bg-gradient-to-br from-primary/10 via-background to-muted/50 group-hover:from-primary/20 transition-all"></div>
                                        <div class="absolute inset-0 flex items-center justify-center opacity-20 group-hover:opacity-30 transition-all">
                                            <Building2 class="h-24 w-24 text-primary" />
                                        </div>
                                        
                                        <!-- Overlays -->
                                        <div class="absolute top-4 left-4">
                                            <Badge variant="secondary" class="bg-background/90 backdrop-blur border shadow-sm px-3 py-1 font-bold capitalize">{{ hostel.gender_type }}</Badge>
                                        </div>
                                        <div class="absolute bottom-4 right-4 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                                            <div class="bg-primary text-primary-foreground p-3 rounded-2xl shadow-xl">
                                                <ArrowRight class="h-6 w-6" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-2">
                                        <h3 class="text-xl font-black mb-1 group-hover:text-primary transition-colors">{{ hostel.name }}</h3>
                                        <p class="text-sm text-muted-foreground line-clamp-1 font-medium">{{ hostel.description || 'Modern campus living experience' }}</p>
                                        <div class="flex items-center gap-4 mt-3">
                                            <div class="flex items-center gap-1 text-[10px] font-bold text-primary uppercase tracking-tighter">
                                                <Layers class="h-3 w-3" />
                                                {{ hostel.blocks?.length || 0 }} Wing Blocks
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Focused Hierarchy Tree -->
                        <div v-else class="max-w-6xl mx-auto space-y-10">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b">
                                <div class="flex items-center gap-4">
                                    <button 
                                        class="hover:bg-primary/10 p-2 rounded-xl transition-colors text-primary"
                                        @click="selectedHostelId = null"
                                    >
                                        <ChevronRight class="h-8 w-8 rotate-180" />
                                    </button>
                                    <div>
                                        <h2 class="text-3xl font-black tracking-tight">{{ activeHostel?.name }}</h2>
                                        <Breadcrumbs class="p-0">
                                            <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground uppercase tracking-widest mt-1">
                                                <span>{{ activeHostel?.name }}</span>
                                                <template v-if="selectedBlockId">
                                                    <ChevronRight class="h-3 w-3" />
                                                    <span class="text-primary">{{ activeBlock?.name }}</span>
                                                </template>
                                                <template v-if="selectedFloorId">
                                                    <ChevronRight class="h-3 w-3" />
                                                    <span class="text-primary">{{ activeFloor?.name }}</span>
                                                </template>
                                            </div>
                                        </Breadcrumbs>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline" class="rounded-full px-4 border-primary/20 text-xs font-bold">{{ activeHostel?.gender_type.toUpperCase() }} RESIDENCE</Badge>
                                </div>
                            </div>

                            <div class="grid gap-12 lg:grid-cols-12">
                                <!-- Navigation Sidebar -->
                                <div class="lg:col-span-4 space-y-10">
                                    <div class="space-y-6">
                                        <div>
                                            <h4 class="text-xs font-black text-muted-foreground uppercase tracking-widest mb-4">1. Residential Wing</h4>
                                            <div class="grid gap-2">
                                                <button 
                                                    v-for="block in activeHostel?.blocks" 
                                                    :key="block.id"
                                                    @click="selectedBlockId = block.id; selectedFloorId = null; selectedRoomId = null"
                                                    :class="[
                                                        'flex items-center justify-between px-6 py-4 rounded-2xl text-left font-bold transition-all',
                                                        selectedBlockId === block.id 
                                                            ? 'bg-primary text-primary-foreground shadow-xl shadow-primary/20' 
                                                            : 'bg-card border border-border/50 hover:border-primary/50'
                                                    ]"
                                                >
                                                    <div class="flex items-center gap-4">
                                                        <Building2 class="h-5 w-5 opacity-70" />
                                                        <span>{{ block.name }}</span>
                                                    </div>
                                                    <ChevronRight class="h-4 w-4 opacity-50" />
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="selectedBlockId" class="animate-in slide-in-from-top-2 fade-in duration-300">
                                            <h4 class="text-xs font-black text-muted-foreground uppercase tracking-widest mb-4">2. Floor Level</h4>
                                            <div class="grid gap-2">
                                                <button 
                                                    v-for="floor in activeBlock?.floors" 
                                                    :key="floor.id"
                                                    @click="selectedFloorId = floor.id; selectedRoomId = null"
                                                    :class="[
                                                        'flex items-center justify-between px-6 py-4 rounded-2xl text-left font-bold transition-all',
                                                        selectedFloorId === floor.id 
                                                            ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20 scale-[1.02]' 
                                                            : 'bg-card border border-border/50 hover:border-primary/30'
                                                    ]"
                                                >
                                                    <div class="flex items-center gap-4">
                                                        <Layers class="h-5 w-5 opacity-70" />
                                                        <span>{{ floor.name }}</span>
                                                    </div>
                                                    <ChevronRight class="h-4 w-4 opacity-50" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Room Matrix -->
                                <div class="lg:col-span-8">
                                    <div v-if="!selectedFloorId" class="h-full flex flex-col items-center justify-center p-12 bg-muted/20 border-2 border-dashed rounded-[3rem] text-center space-y-4">
                                        <div class="p-5 bg-background rounded-full shadow-sm border">
                                            <Layers class="h-10 w-10 text-muted-foreground/30" />
                                        </div>
                                        <h3 class="text-xl font-bold text-muted-foreground">Level Not Selected</h3>
                                        <p class="text-sm text-muted-foreground/60 max-w-xs font-medium italic">Please select a Wing and a Floor from the sidebar to view available bedspaces.</p>
                                    </div>

                                    <div v-else class="space-y-8 animate-in zoom-in-95.duration-500">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-xl font-black">Availability Matrix</h3>
                                            <Badge variant="outline" class="font-bold border-primary/50 text-primary uppercase text-[10px]">{{ activeFloor?.rooms?.length || 0 }} UNITS TOTAL</Badge>
                                        </div>

                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            <button
                                                v-for="room in activeFloor?.rooms"
                                                :key="room.id"
                                                @click="!room.is_full ? selectedRoomId = room.id : null"
                                                :disabled="room.is_full"
                                                :class="[
                                                    'group relative flex flex-col p-6 rounded-3xl border-2 transition-all transition-all duration-300 text-left',
                                                    room.is_full 
                                                        ? 'bg-muted/30 border-transparent opacity-60 cursor-not-allowed' 
                                                        : selectedRoomId === room.id 
                                                            ? 'border-primary bg-primary/[0.04] shadow-xl shadow-primary/5 ring-4 ring-primary/5 scale-[1.03]' 
                                                            : 'border-border/60 bg-card hover:border-primary/40 hover:shadow-lg'
                                                ]"
                                            >
                                                <div class="flex items-center justify-between mb-6">
                                                    <div class="p-2 rounded-xl bg-background border shadow-sm group-hover:bg-primary/5 transition-colors">
                                                        <BedDouble :class="[
                                                            'h-6 w-6',
                                                            room.is_full ? 'text-muted-foreground' : 'text-primary'
                                                        ]" />
                                                    </div>
                                                    <div v-if="selectedRoomId === room.id" class="rounded-full bg-primary text-primary-foreground p-1 animate-in zoom-in">
                                                        <CheckCircle2 class="h-4 w-4" />
                                                    </div>
                                                </div>

                                                <span class="font-black text-2xl tracking-tighter mb-1 leading-none">Unit {{ room.room_number }}</span>
                                                <p class="text-[10px] font-bold text-muted-foreground uppercase opacity-70 mb-4 tracking-wider">Accommodation Space</p>
                                                
                                                <div class="mt-auto">
                                                    <Badge 
                                                        :variant="room.is_full ? 'destructive' : 'secondary'"
                                                        class="w-full justify-center py-1 font-bold rounded-lg shadow-sm"
                                                    >
                                                        {{ room.is_full ? 'UNAVAILABLE' : `${room.available_beds} BEDS OPEN` }}
                                                    </Badge>
                                                </div>
                                            </button>
                                        </div>

                                        <!-- Summary & Booking Button -->
                                        <Transition
                                            enter-active-class="transform transition ease-out duration-500"
                                            enter-from-class="translate-y-10 opacity-0"
                                            enter-to-class="translate-y-0 opacity-100"
                                            leave-active-class="transform transition ease-in duration-300"
                                            leave-from-class="translate-y-0 opacity-100"
                                            leave-to-class="translate-y-10 opacity-0"
                                        >
                                            <div v-if="selectedRoomId" class="sticky bottom-8 z-20">
                                                <div class="rounded-[2.5rem] bg-card/80 backdrop-blur-2xl border-2 border-primary/20 p-8 shadow-[0_30px_60px_-15px_rgba(0,0,0,0.1)] flex flex-col md:flex-row items-center justify-between gap-8 ring-1 ring-primary/10">
                                                    <div class="flex items-center gap-6 text-left">
                                                        <div class="hidden sm:flex p-4 rounded-3xl bg-primary text-primary-foreground shadow-lg">
                                                            <BadgeCheck class="h-8 w-8" />
                                                        </div>
                                                        <div>
                                                            <h4 class="text-2xl font-black italic tracking-tighter text-primary">Confirm Placement?</h4>
                                                            <p class="text-sm text-muted-foreground font-bold leading-tight">
                                                                Room {{ activeRoom?.room_number }} on {{ activeFloor?.name }} Level
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-4 w-full md:w-auto">
                                                        <Button 
                                                            size="lg" 
                                                            class="w-full md:w-auto px-12 py-8 text-lg font-black rounded-2xl shadow-xl shadow-primary/20 transition-all hover:scale-105 active:scale-95" 
                                                            @click="bookRoom" 
                                                            :disabled="isBooking"
                                                        >
                                                            {{ isBooking ? 'Processing Booking...' : 'RESERVE SPACE NOW' }}
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        </Transition>
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
    </StudentLayout>
</template>

<style scoped>
.animate-in {
    animation-timing-function: cubic-bezier(0.2, 0.8, 0.2, 1);
}
</style>
