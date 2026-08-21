<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { 
    Search, 
    Download, 
    Hotel, 
    Calendar,
    ChevronRight,
    BadgeCheck,
    Clock,
    XCircle,
    Plus,
    X,
    Loader2,
    ArrowUpDown,
    FileText
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { debounce } from 'lodash';
import axios from 'axios';

const props = defineProps<{
    bookings: any[];
    sessions: any[];
    hostels: any[];
    filters: {
        session_id?: string;
        level?: string;
        hostel_id?: string;
        status?: string;
        date?: string;
        sort_by?: string;
        sort_direction?: string;
    };
}>();

const searchTerm = ref('');
const filterSessionId = ref(props.filters.session_id || '');
const filterLevel = ref(props.filters.level || 'all');
const filterHostelId = ref(props.filters.hostel_id || 'all');
const filterStatus = ref(props.filters.status || 'all');
const filterDate = ref(props.filters.date || '');

const filterSortBy = ref(props.filters.sort_by || 'created_at');
const filterSortDirection = ref(props.filters.sort_direction || 'desc');

// --- Admin Booking Modal State ---
const isBookModalOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);
const selectedStudent = ref<any>(null);
const showResults = ref(false);

const selectedHostelId = ref<string | null>(null);
const selectedBlockId = ref<string | null>(null);
const selectedFloorId = ref<string | null>(null);
const selectedRoomId = ref<string | null>(null);

const availableHostels = ref<any[]>([]);
const isLoadingRooms = ref(false);

const form = useForm({
    student_id: '',
    hostel_room_id: '',
    mark_as_paid: false,
});

// Cascading computed selectors
const activeHostel = computed(() => {
    return availableHostels.value.find(h => h.id === selectedHostelId.value);
});

const activeBlock = computed(() => {
    return activeHostel.value?.blocks.find(b => b.id === selectedBlockId.value);
});

const activeFloor = computed(() => {
    return activeBlock.value?.floors.find(f => f.id === selectedFloorId.value);
});

// Watch student query
const handleStudentSearch = debounce(async (query: string) => {
    if (!query || query.length < 2) {
        searchResults.value = [];
        return;
    }
    
    isSearching.value = true;
    try {
        const response = await axios.get(route('admin.hostels.search-students'), {
            params: { query }
        });
        searchResults.value = response.data;
        showResults.value = true;
    } catch (error) {
        console.error('Student search failed', error);
    } finally {
        isSearching.value = false;
    }
}, 300);

watch(searchQuery, (newVal) => {
    if (!selectedStudent.value) {
        handleStudentSearch(newVal);
    }
});

// Watch selected student to load their available rooms (based on gender)
watch(selectedStudent, async (newStudent) => {
    selectedHostelId.value = null;
    selectedBlockId.value = null;
    selectedFloorId.value = null;
    selectedRoomId.value = null;
    availableHostels.value = [];
    form.hostel_room_id = '';

    if (!newStudent || !newStudent.student) {
        return;
    }

    isLoadingRooms.value = true;
    try {
        const response = await axios.get(route('admin.hostels.rooms.available'), {
            params: { student_id: newStudent.student.id }
        });
        availableHostels.value = response.data;
    } catch (e) {
        console.error('Failed loading rooms', e);
    } finally {
        isLoadingRooms.value = false;
    }
});

const openBookModal = () => {
    form.reset();
    form.clearErrors();
    selectedStudent.value = null;
    searchQuery.value = '';
    searchResults.value = [];
    selectedHostelId.value = null;
    selectedBlockId.value = null;
    selectedFloorId.value = null;
    selectedRoomId.value = null;
    availableHostels.value = [];
    isBookModalOpen.value = true;
};

const selectStudent = (student: any) => {
    selectedStudent.value = student;
    form.student_id = student.student.id;
    searchQuery.value = student.name;
    showResults.value = false;
};

const clearStudentSelection = () => {
    selectedStudent.value = null;
    form.student_id = '';
    searchQuery.value = '';
    searchResults.value = [];
    availableHostels.value = [];
};

const handleRoomChange = (roomId: string) => {
    selectedRoomId.value = roomId;
    form.hostel_room_id = roomId;
};

const submitBooking = () => {
    form.post(route('admin.hostels.bookings.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isBookModalOpen.value = false;
        },
    });
};

const applyFilters = () => {
    router.get(route('admin.hostels.bookings.index'), {
        session_id: filterSessionId.value,
        level: filterLevel.value === 'all' ? '' : filterLevel.value,
        hostel_id: filterHostelId.value === 'all' ? '' : filterHostelId.value,
        status: filterStatus.value === 'all' ? '' : filterStatus.value,
        date: filterDate.value,
        sort_by: filterSortBy.value,
        sort_direction: filterSortDirection.value,
    }, {
        preserveState: true,
        replace: true
    });
};

const handleSessionChange = (val: string) => {
    filterSessionId.value = val;
    applyFilters();
};

const handleLevelChange = (val: string) => {
    filterLevel.value = val;
    applyFilters();
};

const handleHostelChange = (val: string) => {
    filterHostelId.value = val;
    applyFilters();
};

const handleStatusChange = (val: string) => {
    filterStatus.value = val;
    applyFilters();
};

const handleDateChange = (event: any) => {
    filterDate.value = event.target.value;
    applyFilters();
};

const toggleSort = (field: string) => {
    if (filterSortBy.value === field) {
        filterSortDirection.value = filterSortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        filterSortBy.value = field;
        filterSortDirection.value = 'asc';
    }
    applyFilters();
};

const getStatusBadgeClass = (status: string) => {
    switch (status.toLowerCase()) {
        case 'confirmed':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'cancelled':
            return 'bg-rose-100 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

const filteredBookings = ref(props.bookings);

watch([searchTerm, () => props.bookings], () => {
    if (!searchTerm.value) {
        filteredBookings.value = props.bookings;
        return;
    }
    
    const term = searchTerm.value.toLowerCase();
    filteredBookings.value = props.bookings.filter(b => 
        b.student?.user?.name?.toLowerCase().includes(term) ||
        b.student?.matriculation_number?.toLowerCase().includes(term) ||
        b.invoice?.reference?.toLowerCase().includes(term) ||
        b.room?.floor?.block?.hostel?.name?.toLowerCase().includes(term)
    );
}, { immediate: true });

const unbookStudent = (bookingId: string) => {
    if (confirm('Are you sure you want to unbook this student? The allocated room slot will be released.')) {
        router.post(route('admin.hostels.bookings.unbook', bookingId), {}, {
            preserveScroll: true
        });
    }
};

const reallocateStudent = (bookingId: string) => {
    if (confirm('Are you sure you want to re-allocate/reactivate this room allocation for the student?')) {
        router.post(route('admin.hostels.bookings.reallocate', bookingId), {}, {
            preserveScroll: true
        });
    }
};

const formatCurrency = (amount: any) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(amount);
};
</script>

<template>
    <Head title="Hostel Bookings Report" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-foreground">Hostel Bookings Report</h2>
                    <p class="text-muted-foreground mt-1">Monitor and manage student accommodation assignments across sessions.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="outline" class="gap-2 shadow-sm border-primary/20 hover:bg-primary/5 text-primary font-semibold">
                        <Download class="h-4 w-4" />
                        Export CSV
                    </Button>
                    <Button @click="openBookModal" class="gap-2 font-semibold">
                        <Plus class="h-4 w-4" />
                        Book for Student
                    </Button>
                </div>
            </div>

            <!-- Filters Area -->
            <div class="bg-card border rounded-xl p-5 shadow-sm space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Search Input -->
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input 
                            v-model="searchTerm"
                            placeholder="Quick search student, matric no, or room..." 
                            class="pl-10 h-10 bg-muted/30 focus-visible:ring-primary/30"
                        />
                    </div>

                    <!-- Session Selector -->
                    <div>
                        <Select v-model="filterSessionId" @update:modelValue="handleSessionChange">
                            <SelectTrigger class="h-10 bg-muted/30 w-full text-left">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    <SelectValue placeholder="Academic Session" />
                                </div>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="session in sessions" :key="session.id" :value="session.id">
                                    {{ session.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Hostel Selector -->
                    <div>
                        <Select v-model="filterHostelId" @update:modelValue="handleHostelChange">
                            <SelectTrigger class="h-10 bg-muted/30 w-full text-left">
                                <div class="flex items-center gap-2">
                                    <Hotel class="h-4 w-4 text-muted-foreground" />
                                    <SelectValue placeholder="All Hostels" />
                                </div>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">-- All Hostels --</SelectItem>
                                <SelectItem v-for="hostel in hostels" :key="hostel.id" :value="hostel.id">
                                    {{ hostel.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Level Selector -->
                    <div>
                        <Select v-model="filterLevel" @update:modelValue="handleLevelChange">
                            <SelectTrigger class="h-10 bg-muted/30 w-full text-left">
                                <SelectValue placeholder="All Levels" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">-- All Levels --</SelectItem>
                                <SelectItem value="100">100 Level</SelectItem>
                                <SelectItem value="200">200 Level</SelectItem>
                                <SelectItem value="300">300 Level</SelectItem>
                                <SelectItem value="400">400 Level</SelectItem>
                                <SelectItem value="500">500 Level</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Status Selector -->
                    <div>
                        <Select v-model="filterStatus" @update:modelValue="handleStatusChange">
                            <SelectTrigger class="h-10 bg-muted/30 w-full text-left">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">-- All Statuses --</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="confirmed">Confirmed</SelectItem>
                                <SelectItem value="cancelled">Cancelled</SelectItem>
                                <SelectItem value="expired">Expired</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date Selector -->
                    <div>
                        <input 
                            type="date" 
                            :value="filterDate" 
                            @input="handleDateChange"
                            class="flex h-10 w-full rounded-md border border-input bg-muted/30 px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 text-muted-foreground"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between border-t pt-3 mt-1">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-primary/5 rounded-lg border border-primary/10">
                        <div class="h-2 w-2 rounded-full bg-primary animate-pulse"></div>
                        <span class="text-xs font-bold text-primary uppercase tracking-wider">
                            {{ filteredBookings.length }} Bookings Found
                        </span>
                    </div>
                    <Button variant="ghost" size="sm" class="text-xs text-muted-foreground hover:text-foreground" @click="() => { filterLevel='all'; filterHostelId='all'; filterStatus='all'; filterDate=''; applyFilters(); }">
                        Reset Filters
                    </Button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="border rounded-xl shadow-sm bg-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 border-b text-muted-foreground font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-6 py-4 cursor-pointer select-none hover:text-foreground hover:bg-muted/30 transition-colors" @click="toggleSort('student_name')">
                                    <div class="flex items-center gap-1">
                                        Student
                                        <ArrowUpDown class="h-3 w-3" :class="{'text-primary': filterSortBy === 'student_name'}" />
                                    </div>
                                </th>
                                <th class="px-6 py-4 cursor-pointer select-none hover:text-foreground hover:bg-muted/30 transition-colors" @click="toggleSort('hostel_name')">
                                    <div class="flex items-center gap-1">
                                        Placement
                                        <ArrowUpDown class="h-3 w-3" :class="{'text-primary': filterSortBy === 'hostel_name'}" />
                                    </div>
                                </th>
                                <th class="px-6 py-4">Payment Info</th>
                                <th class="px-6 py-4 cursor-pointer select-none hover:text-foreground hover:bg-muted/30 transition-colors" @click="toggleSort('status')">
                                    <div class="flex items-center gap-1">
                                        Status
                                        <ArrowUpDown class="h-3 w-3" :class="{'text-primary': filterSortBy === 'status'}" />
                                    </div>
                                </th>
                                <th class="px-6 py-4 cursor-pointer select-none hover:text-foreground hover:bg-muted/30 transition-colors" @click="toggleSort('created_at')">
                                    <div class="flex items-center gap-1">
                                        Date
                                        <ArrowUpDown class="h-3 w-3" :class="{'text-primary': filterSortBy === 'created_at'}" />
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="booking in filteredBookings" :key="booking.id" class="group hover:bg-muted/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold border border-primary/20">
                                            {{ booking.student?.user?.name?.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-foreground leading-none">{{ booking.student?.user?.name }}</p>
                                            <p class="text-xs text-muted-foreground mt-1 font-mono uppercase">{{ booking.student?.matriculation_number }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5 text-foreground font-semibold">
                                            <Hotel class="h-3.5 w-3.5 text-primary" />
                                            {{ booking.room?.floor?.block?.hostel?.name }}
                                        </div>
                                        <p class="text-[11px] text-muted-foreground font-medium pl-5">
                                            {{ booking.room?.floor?.block?.name }} • {{ booking.room?.floor?.name }} • Room {{ booking.room?.room_number }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="booking.invoice" class="space-y-1">
                                        <p class="font-mono text-[11px] font-bold text-muted-foreground uppercase tracking-tighter">{{ booking.invoice.reference }}</p>
                                        <div class="flex items-center gap-2">
                                            <p class="font-bold text-foreground">{{ formatCurrency(booking.invoice.amount) }}</p>
                                            <BadgeCheck v-if="booking.invoice.status === 'paid'" class="h-4 w-4 text-emerald-500" />
                                            <Clock v-else class="h-4 w-4 text-amber-500" />
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground italic">No invoice linked</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-widest', getStatusBadgeClass(booking.status)]">
                                        {{ booking.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs text-muted-foreground font-semibold">{{ new Date(booking.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                                    <p v-if="booking.updater" class="text-[9px] text-muted-foreground mt-0.5" :title="`Created by: ${booking.creator?.name || 'System'}`">
                                        Updated by: <span class="font-semibold text-foreground">{{ booking.updater.name }}</span>
                                    </p>
                                    <p v-else-if="booking.creator" class="text-[9px] text-muted-foreground mt-0.5">
                                        Created by: <span class="font-semibold text-foreground">{{ booking.creator.name }}</span>
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button 
                                            v-if="booking.status !== 'cancelled'"
                                            variant="outline" 
                                            size="sm" 
                                            class="text-xs text-destructive border-destructive/20 hover:bg-destructive/10 hover:border-destructive h-8 rounded-lg font-semibold"
                                            @click="unbookStudent(booking.id)"
                                        >
                                            Unbook
                                        </Button>
                                        <Button 
                                            v-else
                                            variant="outline" 
                                            size="sm" 
                                            class="text-xs text-emerald-600 border-emerald-200 hover:bg-emerald-50 hover:border-emerald-500 h-8 rounded-lg font-semibold"
                                            @click="reallocateStudent(booking.id)"
                                        >
                                            Re-allocate
                                        </Button>
                                        <a v-if="booking.status === 'confirmed' && (booking.invoice?.status === 'paid' || booking.invoice?.status === 'partial')" :href="route('admin.hostels.bookings.download-slip', booking.id)" target="_blank">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-full hover:bg-primary/10 hover:text-primary text-muted-foreground" title="Download Booking Slip">
                                                <FileText class="h-4 w-4" />
                                            </Button>
                                        </a>
                                        <Button v-else variant="ghost" size="icon" class="h-8 w-8 rounded-full text-muted-foreground/30 cursor-not-allowed" disabled title="Accommodation payment not confirmed">
                                            <FileText class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-full hover:bg-primary/10 hover:text-primary" @click="$inertia.visit(route('admin.students.show', booking.student.id))" title="View Student Profile">
                                            <ChevronRight class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredBookings.length === 0">
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <XCircle class="h-12 w-12 text-muted-foreground/30 mb-4" />
                                        <h3 class="text-lg font-bold text-foreground">No bookings found</h3>
                                        <p class="text-sm text-muted-foreground mt-1 max-w-xs mx-auto">
                                            Try adjusting your search terms or selecting a different academic session.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Book for Student Dialog -->
        <Dialog :open="isBookModalOpen" @update:open="isBookModalOpen = $event">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Allocate Hostel Room</DialogTitle>
                    <DialogDescription>
                        Manually assign a hostel room to a student for the current active session.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitBooking" class="space-y-5 py-4">
                    <!-- Student Search -->
                    <div class="space-y-2 relative">
                        <Label>Search Student <span class="text-red-500">*</span></Label>
                        
                        <div v-if="selectedStudent" class="flex items-center justify-between p-3 border rounded-lg bg-slate-50 dark:bg-slate-900">
                            <div class="flex items-center gap-3">
                                <Avatar class="h-10 w-10">
                                    <AvatarImage :src="selectedStudent.profile_photo_url" />
                                    <AvatarFallback>{{ selectedStudent.name.charAt(0) }}</AvatarFallback>
                                </Avatar>
                                <div>
                                    <p class="font-semibold text-sm">{{ selectedStudent.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ selectedStudent.email }}</p>
                                    <p v-if="selectedStudent.student" class="text-xs font-mono text-primary font-bold uppercase">
                                        Matric: {{ selectedStudent.student.matriculation_number }} • {{ selectedStudent.student.gender }}
                                    </p>
                                </div>
                            </div>
                            <Button type="button" variant="ghost" size="icon" @click="clearStudentSelection" class="rounded-full h-8 w-8 hover:bg-destructive/10 hover:text-destructive">
                                <X class="w-4 h-4" />
                            </Button>
                        </div>

                        <div v-else class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input 
                                v-model="searchQuery" 
                                placeholder="Type student name, matric number, or email..." 
                                class="pl-9 h-10"
                                @focus="showResults = true"
                            />
                            <div v-if="isSearching" class="absolute right-3 top-3">
                                <Loader2 class="w-4 h-4 animate-spin text-muted-foreground" />
                            </div>

                            <!-- Search Results List -->
                            <div v-if="showResults && searchResults.length > 0" class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-950 border rounded-md shadow-lg max-h-56 overflow-y-auto">
                                <div 
                                    v-for="student in searchResults" 
                                    :key="student.id"
                                    class="p-3 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer flex items-center gap-3 transition-colors border-b last:border-0"
                                    @click="selectStudent(student)"
                                >
                                    <Avatar class="h-8 w-8">
                                        <AvatarImage :src="student.profile_photo_url" />
                                        <AvatarFallback>{{ student.name.charAt(0) }}</AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <p class="text-sm font-semibold">{{ student.name }}</p>
                                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                            <span>{{ student.email }}</span>
                                            <span v-if="student.student" class="text-primary font-mono uppercase">• {{ student.student.matriculation_number }} ({{ student.student.gender }})</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="showResults && searchQuery.length > 2 && !isSearching && searchResults.length === 0" class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-950 border rounded-md shadow-lg p-4 text-center text-sm text-muted-foreground">
                                No students found.
                            </div>
                        </div>
                        <p v-if="form.errors.student_id" class="text-xs text-destructive mt-1 font-semibold">{{ form.errors.student_id }}</p>
                    </div>

                    <!-- Room Selection -->
                    <div v-if="selectedStudent" class="space-y-4">
                        <div v-if="isLoadingRooms" class="flex items-center justify-center p-6">
                            <Loader2 class="h-6 w-6 animate-spin text-primary mr-2" />
                            <span class="text-sm text-muted-foreground font-medium">Fetching rooms matching student gender...</span>
                        </div>

                        <div v-else-if="availableHostels.length === 0" class="p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 rounded-lg text-center">
                            <p class="text-xs text-amber-800 dark:text-amber-300 font-medium">
                                No available hostels found matching student gender ({{ selectedStudent.student.gender }}).
                            </p>
                        </div>

                        <div v-else class="grid grid-cols-1 gap-4">
                            <!-- Select Hostel -->
                            <div class="space-y-1.5">
                                <Label>Hostel <span class="text-red-500">*</span></Label>
                                <Select v-model="selectedHostelId">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Select Hostel" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="hostel in availableHostels" :key="hostel.id" :value="hostel.id">
                                            {{ hostel.name }} ({{ hostel.gender_type }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Select Block -->
                            <div v-if="selectedHostelId" class="space-y-1.5">
                                <Label>Wing / Block <span class="text-red-500">*</span></Label>
                                <Select v-model="selectedBlockId">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Select Block" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="block in activeHostel?.blocks || []" :key="block.id" :value="block.id">
                                            {{ block.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Select Floor -->
                            <div v-if="selectedBlockId" class="space-y-1.5">
                                <Label>Floor <span class="text-red-500">*</span></Label>
                                <Select v-model="selectedFloorId">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Select Floor" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="floor in activeBlock?.floors || []" :key="floor.id" :value="floor.id">
                                            {{ floor.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Select Room -->
                            <div v-if="selectedFloorId" class="space-y-1.5">
                                <Label>Room <span class="text-red-500">*</span></Label>
                                <Select v-model="selectedRoomId" @update:modelValue="handleRoomChange">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Select Room" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="room in activeFloor?.rooms || []" :key="room.id" :value="room.id">
                                            Room {{ room.room_number }} ({{ room.available_beds }} beds available)
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.hostel_room_id" class="text-xs text-destructive mt-1 font-semibold">{{ form.errors.hostel_room_id }}</p>
                            </div>
                        </div>
                    </div>



                    <!-- Action buttons -->
                    <DialogFooter class="border-t pt-4">
                        <Button type="button" variant="outline" @click="isBookModalOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing || !form.student_id || !form.hostel_room_id">
                            <Loader2 v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                            Allocate Room
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
