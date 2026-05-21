<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { 
    Search, 
    Download, 
    Hotel, 
    Calendar,
    ChevronRight,
    BadgeCheck,
    Clock,
    XCircle
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    bookings: any[];
    sessions: any[];
    filters: {
        session_id: string;
    };
}>();

const searchTerm = ref('');
const selectedSessionId = ref(props.filters.session_id);

const handleSessionChange = (val: string) => {
    selectedSessionId.value = val;
    router.get(route('admin.hostels.bookings.index'), { session_id: val }, {
        preserveState: true,
        replace: true
    });
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
                </div>
            </div>

            <!-- Filters Area -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 bg-card border rounded-xl p-5 shadow-sm">
                <div class="lg:col-span-2 relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input 
                        v-model="searchTerm"
                        placeholder="Search student, matric no, or hostel..." 
                        class="pl-10 h-11 bg-muted/30 focus-visible:ring-primary/30"
                    />
                </div>
                
                <div class="space-y-1">
                    <Select v-model="selectedSessionId" @update:modelValue="handleSessionChange">
                        <SelectTrigger class="h-11 bg-muted/30 w-full text-left">
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

                <div class="flex items-center gap-2 px-3 py-2 bg-primary/5 rounded-lg border border-primary/10">
                    <div class="h-2 w-2 rounded-full bg-primary animate-pulse"></div>
                    <span class="text-xs font-bold text-primary uppercase tracking-wider">
                        {{ filteredBookings.length }} Total Bookings
                    </span>
                </div>
            </div>

            <!-- Table Card -->
            <div class="border rounded-xl shadow-sm bg-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 border-b text-muted-foreground font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-6 py-4">Student</th>
                                <th class="px-6 py-4">Placement</th>
                                <th class="px-6 py-4">Payment Info</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Date</th>
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
                                    <p class="text-xs text-muted-foreground font-medium">{{ new Date(booking.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Button variant="ghost" size="icon" class="h-8 w-8 rounded-full hover:bg-primary/10 hover:text-primary" @click="$inertia.visit(route('admin.students.show', booking.student.id))">
                                        <ChevronRight class="h-4 w-4" />
                                    </Button>
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
    </AdminLayout>
</template>
