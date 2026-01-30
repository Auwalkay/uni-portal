<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import { 
    Search, 
    Filter, 
    Download, 
    CreditCard, 
    CheckCircle, 
    AlertCircle,
    X,
    ChevronDown,
    Building2,
    Calendar,
    GraduationCap
} from 'lucide-vue-next';

import { route } from 'ziggy-js'; // Fixing route import

const props = defineProps({
    payments: Object,
    filters: Object,
    sessions: Array,
    faculties: Array,
    departments: Array,
});

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || '');
const selectedFaculty = ref(props.filters.faculty_id || '');
const selectedDepartment = ref(props.filters.department_id || '');

// Derived state for stats
const totalAmount = computed(() => {
    return props.payments.data.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0);
});

const successCount = computed(() => {
    return props.payments.data.filter(p => p.status === 'paid').length;
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
    // When faculty changes, clear department if it doesn't belong to new faculty
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

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const getStatusClass = (status) => {
    switch(status) {
        case 'paid': return 'bg-green-100 text-green-800 border-green-200';
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'failed': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

</script>

<template>
    <Head title="Payments Management" />

    <AdminLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8 w-full mx-auto space-y-6">
            
            <!-- Header & Stats -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Payments</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage and track all student payments.</p>
                </div>
                
                <div class="flex gap-4">
                     <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex items-center space-x-4">
                        <div class="p-2 bg-green-50 rounded-full">
                            <CreditCard class="w-6 h-6 text-green-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Page Total</p>
                            <p class="text-lg font-bold text-gray-900">₦{{ totalAmount.toLocaleString() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Smart Filter Bar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <Filter class="w-4 h-4" /> Filter Records
                    </h3>
                    <button 
                        v-if="search || selectedSession || selectedFaculty || selectedDepartment"
                        @click="clearFilters" 
                        class="text-xs text-red-600 hover:text-red-800 flex items-center gap-1 font-medium transition"
                    >
                        <X class="w-3 h-3" /> Clear Filters
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-4 w-4 text-gray-400" />
                        </div>
                        <input 
                            v-model="search" 
                            type="text" 
                            class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors duration-200" 
                            placeholder="Search Ref, Name, Matric..." 
                        />
                    </div>

                    <!-- Session Select -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Calendar class="h-4 w-4 text-gray-400" />
                        </div>
                        <select 
                            v-model="selectedSession"
                            class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm appearance-none transition-colors duration-200"
                        >
                            <option value="">All Sessions</option>
                            <option v-for="session in sessions" :key="session.id" :value="session.id">
                                {{ session.name }}
                            </option>
                        </select>
                        <ChevronDown class="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>

                    <!-- Faculty Select -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Building2 class="h-4 w-4 text-gray-400" />
                        </div>
                        <select 
                            v-model="selectedFaculty"
                            class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm appearance-none transition-colors duration-200"
                        >
                            <option value="">All Faculties</option>
                            <option v-for="faculty in faculties" :key="faculty.id" :value="faculty.id">
                                {{ faculty.name }}
                            </option>
                        </select>
                        <ChevronDown class="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>

                    <!-- Department Select -->
                    <div class="relative">
                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <GraduationCap class="h-4 w-4 text-gray-400" />
                        </div>
                        <select 
                            v-model="selectedDepartment"
                            class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm appearance-none transition-colors duration-200"
                        >
                            <option value="">All Departments</option>
                            <option v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">
                                {{ dept.name }}
                            </option>
                        </select>
                         <ChevronDown class="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type / Session</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono font-medium text-gray-900">{{ payment.reference }}</div>
                                    <div class="text-xs text-gray-500">{{ payment.channel || 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                       <div class="flex-shrink-0 h-8 w-8">
                                            <img class="h-8 w-8 rounded-full" :src="`https://ui-avatars.com/api/?name=${payment.user.name}&background=random`" alt="" />
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ payment.user.name }}</div>
                                            <div class="text-xs text-gray-500">{{ payment.user.student?.matriculation_number || payment.user.email }}</div>
                                             <div class="text-xs text-gray-400 mt-0.5" v-if="payment.user.student?.academic_department">
                                                {{ payment.user.student?.academic_department?.name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 capitalize">{{ payment.invoice?.type?.replace('_', ' ') || 'Payment' }}</div>
                                    <div class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full inline-block mt-1">
                                        {{ payment.invoice?.session?.name || 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">₦{{ Number(payment.amount).toLocaleString() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full border" :class="getStatusClass(payment.status)">
                                        {{ payment.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(payment.paid_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('admin.payments.show', payment.id)" class="text-blue-600 hover:text-blue-900 font-semibold text-xs uppercase tracking-wide">
                                        View
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="payments.data.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <div class="bg-gray-100 p-3 rounded-full mb-3">
                                            <Search class="h-6 w-6 text-gray-400" />
                                        </div>
                                        <p class="text-base font-medium text-gray-900">No payments found</p>
                                        <p class="text-sm">Try adjusting your filters or search query.</p>
                                        <button @click="clearFilters" class="mt-3 text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Clear all filters
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer / Pagination -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Showing <span class="font-medium text-gray-900">{{ payments.from || 0 }}</span> to <span class="font-medium text-gray-900">{{ payments.to || 0 }}</span> of <span class="font-medium text-gray-900">{{ payments.total }}</span> results
                    </div>
                    <div class="flex space-x-1">
                         <Link 
                            v-for="(link, i) in payments.links" 
                            :key="i"
                            :href="link.url ?? '#'"
                            class="px-3 py-1 text-sm border rounded-md transition-colors duration-150"
                            :class="[
                                link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
