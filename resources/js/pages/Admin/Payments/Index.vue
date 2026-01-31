<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import { 
    Search, 
    Filter, 
    CreditCard, 
    X,
    ChevronDown,
    TrendingUp,
    CheckCircle,
    Clock,
    AlertCircle
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const props = defineProps<{
    payments: {
        data: Array<any>;
        links: Array<any>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        session_id?: string;
        faculty_id?: string;
        department_id?: string;
    };
    sessions: Array<{ id: string; name: string }>;
    faculties: Array<{ id: string; name: string }>;
    departments: Array<{ id: string; name: string; faculty_id: string }>;
    stats: {
        total_revenue: number;
        today_revenue: number;
        successful_count: number;
        pending_count: number;
        failed_count: number;
    };
}>();

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || '');
const selectedFaculty = ref(props.filters.faculty_id || '');
const selectedDepartment = ref(props.filters.department_id || '');

// Derived state for stats (Client-side approximation based on current page/data)
// Ideally this should be passed from backend if we want global totals, but using page data for now or props.
// Since existing code calculated from page data, we keep it consistent or assume backend might pass it later.
// For now, let's calculate from props.payments.data
const totalAmount = computed(() => {
    return props.payments.data.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0);
});

// Computed departments based on selected faculty
const filteredDepartments = computed(() => {
    if (!selectedFaculty.value) return props.departments;
    return props.departments.filter(dept => dept.faculty_id === selectedFaculty.value);
});

// Watchers for filters
const updateFilters = debounce(() => {
    router.get(route('admin.payments.index'), { 
        search: search.value,
        session_id: selectedSession.value,
        faculty_id: selectedFaculty.value,
        department_id: selectedDepartment.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, 300);

watch([search, selectedSession, selectedFaculty, selectedDepartment], () => {
    if (selectedFaculty.value && selectedDepartment.value) {
         const dept = props.departments.find(d => d.id === selectedDepartment.value);
         if (dept && dept.faculty_id !== selectedFaculty.value) {
             selectedDepartment.value = '';
         }
    }
    updateFilters();
});

const clearFilters = () => {
    search.value = '';
    selectedSession.value = '';
    selectedFaculty.value = '';
    selectedDepartment.value = '';
};

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(amount);
};

const getStatusVariant = (status: string) => {
    switch(status) {
        case 'paid': return 'default'; // Using deafult/green if configured, or we can use specific classes
        case 'pending': return 'secondary';
        case 'failed': return 'destructive';
        default: return 'outline';
    }
};

const getStatusClass = (status: string) => {
     switch(status) {
        case 'paid': return 'bg-green-100 text-green-800 hover:bg-green-200 border-green-200';
        case 'pending': return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 border-yellow-200';
        case 'failed': return 'bg-red-100 text-red-800 hover:bg-red-200 border-red-200';
        default: return 'bg-gray-100 text-gray-800';
    }
};

</script>

<template>
    <Head title="Payments Management" />

    <AdminLayout>
        <div class="py-8 px-6 space-y-6 w-full max-w-[1600px] mx-auto">
            
            <!-- Header & Stats -->
            <div class="flex flex-col gap-6">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Payments</h1>
                    <p class="text-muted-foreground mt-1">Manage, search, and track all student payment records.</p>
                </div>
                
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <Card class="bg-primary/5 border-primary/20 shadow-none">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                        <CreditCard class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats?.total_revenue || 0) }}</div>
                        <p class="text-xs text-muted-foreground">All time collected</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today's Revenue</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats?.today_revenue || 0) }}</div>
                        <p class="text-xs text-muted-foreground">Collected today</p>
                        </CardContent>
                    </Card>

                     <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Successful</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.successful_count || 0 }}</div>
                        <p class="text-xs text-muted-foreground">Paid transactions</p>
                        </CardContent>
                    </Card>

                     <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending</CardTitle>
                        <Clock class="h-4 w-4 text-yellow-500" />
                        </CardHeader>
                        <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.pending_count || 0 }}</div>
                        <p class="text-xs text-muted-foreground">Awaiting payment</p>
                        </CardContent>
                    </Card>

                     <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Failed</CardTitle>
                        <AlertCircle class="h-4 w-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.failed_count || 0 }}</div>
                        <p class="text-xs text-muted-foreground">Unsuccessful attempts</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col lg:flex-row gap-4 items-end lg:items-center justify-between">
                <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto flex-1">
                     <div class="relative w-full sm:w-[300px]">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                          type="search"
                          placeholder="Search reference, name..."
                          class="pl-8"
                          v-model="search"
                        />
                      </div>
                      
                      <!-- Session Filter -->
                       <Select v-model="selectedSession">
                        <SelectTrigger class="w-[180px]">
                          <SelectValue placeholder="Session" />
                        </SelectTrigger>
                        <SelectContent>
                           <SelectItem value="ALL_SESSIONS_RESET_VALUE">All Sessions</SelectItem>
                          <SelectItem v-for="session in sessions" :key="session.id" :value="session.id">
                            {{ session.name }}
                          </SelectItem>
                        </SelectContent>
                      </Select>

                      <!-- Faculty Filter -->
                       <Select v-model="selectedFaculty">
                        <SelectTrigger class="w-[180px]">
                          <SelectValue placeholder="Faculty" />
                        </SelectTrigger>
                        <SelectContent>
                           <SelectItem value="ALL_FACULTIES_RESET_VALUE">All Faculties</SelectItem>
                          <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id">
                            {{ faculty.name }}
                          </SelectItem>
                        </SelectContent>
                      </Select>

                      <!-- Department Filter -->
                       <Select v-model="selectedDepartment" :disabled="!selectedFaculty">
                        <SelectTrigger class="w-[200px]">
                          <SelectValue placeholder="Department" />
                        </SelectTrigger>
                        <SelectContent>
                           <SelectItem value="ALL_DEPARTMENTS_RESET_VALUE">All Departments</SelectItem>
                          <SelectItem v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">
                            {{ dept.name }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                </div>
                
                <Button 
                    v-if="search || selectedSession || selectedFaculty || selectedDepartment" 
                    variant="ghost" 
                    @click="clearFilters"
                    class="text-destructive hover:text-destructive hover:bg-destructive/10"
                >
                    <X class="w-4 h-4 mr-2" />
                    Clear Filters
                </Button>
            </div>

            <!-- Data Table -->
            <Card>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Reference</TableHead>
                            <TableHead>Student</TableHead>
                            <TableHead>Type / Session</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="payment in payments.data" :key="payment.id">
                            <TableCell>
                                <div class="font-medium font-mono">{{ payment.reference }}</div>
                                <div class="text-xs text-muted-foreground capitalize">{{ payment.channel || 'Manual' }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                   <!-- Avatar fallback -->
                                   <div class="h-8 w-8 rounded-full bg-muted flex items-center justify-center text-xs font-bold text-muted-foreground overflow-hidden">
                                        <img :src="`https://ui-avatars.com/api/?name=${payment.user.name}&background=random`" alt="Avatar" />
                                   </div>
                                   <div>
                                       <div class="font-medium">{{ payment.user.name }}</div>
                                       <div class="text-xs text-muted-foreground flex flex-col">
                                           <span>{{ payment.user.student?.matriculation_number || payment.user.email }}</span>
                                           <span v-if="payment.user.student?.academic_department" class="text-[10px] opacity-75">
                                               {{ payment.user.student?.academic_department?.name }}
                                           </span>
                                       </div>
                                   </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="capitalize text-sm">{{ payment.invoice?.type?.replace('_', ' ') || 'Payment' }}</div>
                                <Badge variant="outline" class="mt-1 text-[10px]">
                                    {{ payment.invoice?.session?.name || 'N/A' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="font-bold">
                                {{ formatCurrency(payment.amount) }}
                            </TableCell>
                            <TableCell>
                                <Badge :class="getStatusClass(payment.status)" variant="outline">
                                    {{ payment.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-muted-foreground text-sm">
                                {{ formatDate(payment.paid_at) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="route('admin.payments.show', payment.id)">
                                        View
                                    </Link>
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="payments.data.length === 0">
                            <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                No payments found. Try adjusting your filters.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                
                 <!-- Pagination -->
                <CardFooter class="flex items-center justify-between border-t p-4" v-if="payments.total > 0">
                    <div class="text-xs text-muted-foreground">
                        Showing <strong>{{ payments.from }}</strong>-<strong>{{ payments.to }}</strong> of <strong>{{ payments.total }}</strong>
                    </div>
                    <div class="flex gap-1">
                         <Button 
                            v-for="(link, i) in payments.links" 
                            :key="i"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            as-child
                         >
                            <Link v-if="link.url" :href="link.url" v-html="link.label" />
                            <span v-else v-html="link.label"></span>
                         </Button>
                    </div>
                </CardFooter>
            </Card>
        </div>
    </AdminLayout>
</template>

