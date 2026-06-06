<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Activity, Plus, Bed, BookOpen, Clock, Heart, Search, FileText, Check, Printer, Filter, Calendar, BarChart3, TrendingUp, AlertTriangle
} from 'lucide-vue-next';

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
    };
    reportData: {
        total_checkins: number;
        referrals_count: number;
        bed_admissions_count: number;
        patient_breakdown: {
            students: number;
            staff: number;
        };
        visit_types: {
            walk_in: number;
            appointment: number;
            emergency: number;
        };
        top_dispensed: Array<{
            name: string;
            quantity: number;
        }>;
    };
}>();

const form = reactive({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilter = () => {
    router.get('/admin/sickbay/reports', {
        start_date: form.start_date,
        end_date: form.end_date,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    form.start_date = thirtyDaysAgo.toISOString().split('T')[0];
    form.end_date = today.toISOString().split('T')[0];
    applyFilter();
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Sickbay Reports & Stats" />

    <AdminLayout>
        <div class="py-6 print:py-0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 print:px-0">
                
                <!-- Header (Hidden on print unless custom styled) -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4 border-b pb-5 no-print">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2.5">
                            <FileText class="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                            Reports & Statistics
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Generate date-filtered clinic performance sheets, patient breakdowns, and pharmacy stock rates.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="printReport"
                            class="inline-flex items-center px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm text-sm font-semibold text-gray-700 dark:text-gray-250 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <Printer class="h-4.5 w-4.5 mr-2" />
                            Print Report
                        </button>
                    </div>
                </div>

                <!-- Page Navigation Sub-menu (Hidden on print) -->
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6 no-print">
                    <nav class="-mb-px flex space-x-8 overflow-x-auto">
                        <Link
                            href="/admin/sickbay"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <Activity class="h-4.5 w-4.5" />
                            Active Queue
                        </Link>
                        <Link
                            href="/admin/sickbay/beds"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <Bed class="h-4.5 w-4.5" />
                            Beds Matrix
                        </Link>
                        <Link
                            href="/admin/sickbay/logs"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <BookOpen class="h-4.5 w-4.5" />
                            Treatment Logs
                        </Link>
                        <Link
                            href="/admin/sickbay/supplies"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <Plus class="h-4.5 w-4.5" />
                            Supplies Ledger
                        </Link>
                        <Link
                            href="/admin/sickbay/patients"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <Search class="h-4.5 w-4.5" />
                            Patient Search
                        </Link>
                        <Link
                            href="/admin/sickbay/reports"
                            class="border-indigo-500 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2"
                        >
                            <FileText class="h-4.5 w-4.5" />
                            Reports & Stats
                        </Link>
                    </nav>
                </div>

                <!-- Printable Document Header (Only visible on print) -->
                <div class="hidden print:block mb-8 text-center border-b-2 border-gray-900 pb-5">
                    <h2 class="text-2xl font-black uppercase tracking-wide text-gray-900">University Medical Center</h2>
                    <h3 class="text-lg font-bold text-gray-800 mt-1">School Sickbay Performance & Stats Report</h3>
                    <p class="text-sm text-gray-600 mt-2">
                        Reporting Period: <span class="font-semibold">{{ filters.start_date }}</span> to <span class="font-semibold">{{ filters.end_date }}</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Generated on: {{ new Date().toLocaleString() }}</p>
                </div>

                <!-- Date Filter Card (Hidden on print) -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 mb-6 no-print">
                    <form @submit.prevent="applyFilter" class="flex flex-col md:flex-row md:items-end gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Start Date</label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Calendar class="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    type="date"
                                    v-model="form.start_date"
                                    class="block w-full pl-10 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    required
                                />
                            </div>
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">End Date</label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Calendar class="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    type="date"
                                    v-model="form.end_date"
                                    class="block w-full pl-10 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    required
                                />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2.5 border border-transparent rounded-xl shadow-md text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <Filter class="h-4.5 w-4.5 mr-2" />
                                Filter
                            </button>
                            <button
                                type="button"
                                @click="resetFilter"
                                class="inline-flex items-center px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition focus:outline-none"
                            >
                                Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6 print:grid-cols-3 print:gap-4">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between print:border-gray-300 print:shadow-none print:p-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Total Patients Attended</p>
                            <p class="text-4xl font-black text-indigo-650 dark:text-indigo-400 mt-1.5">{{ reportData.total_checkins }}</p>
                        </div>
                        <Activity class="h-10 w-10 text-indigo-650 dark:text-indigo-400 no-print" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between print:border-gray-300 print:shadow-none print:p-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Observation Bed Admissions</p>
                            <p class="text-4xl font-black text-blue-600 dark:text-blue-400 mt-1.5">{{ reportData.bed_admissions_count }}</p>
                        </div>
                        <Bed class="h-10 w-10 text-blue-600 dark:text-blue-400 no-print" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between print:border-gray-300 print:shadow-none print:p-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Referrals to External Hospitals</p>
                            <p class="text-4xl font-black text-red-600 dark:text-red-400 mt-1.5">{{ reportData.referrals_count }}</p>
                        </div>
                        <AlertTriangle class="h-10 w-10 text-red-600 dark:text-red-400 no-print" />
                    </div>
                </div>

                <!-- Secondary Reports Blocks -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 print:grid-cols-2 print:gap-4 mb-6">
                    
                    <!-- Patient Breakdown Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 print:border-gray-300 print:shadow-none print:p-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-b pb-2">
                            <TrendingUp class="h-5 w-5 text-indigo-500 no-print" />
                            Patient Demographics
                        </h3>
                        
                        <div class="space-y-4">
                            <!-- Students -->
                            <div>
                                <div class="flex justify-between text-sm font-semibold mb-1">
                                    <span class="text-gray-655 dark:text-gray-300">Students</span>
                                    <span class="text-gray-900 dark:text-white font-bold">
                                        {{ reportData.patient_breakdown.students }} 
                                        ({{ reportData.total_checkins > 0 ? Math.round((reportData.patient_breakdown.students / reportData.total_checkins) * 100) : 0 }}%)
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-900 rounded-full h-2.5 overflow-hidden">
                                    <div 
                                        class="bg-indigo-650 h-2.5 rounded-full" 
                                        :style="`width: ${reportData.total_checkins > 0 ? (reportData.patient_breakdown.students / reportData.total_checkins) * 100 : 0}%`"
                                    ></div>
                                </div>
                            </div>

                            <!-- Staff -->
                            <div>
                                <div class="flex justify-between text-sm font-semibold mb-1">
                                    <span class="text-gray-655 dark:text-gray-300">Staff Members</span>
                                    <span class="text-gray-900 dark:text-white font-bold">
                                        {{ reportData.patient_breakdown.staff }} 
                                        ({{ reportData.total_checkins > 0 ? Math.round((reportData.patient_breakdown.staff / reportData.total_checkins) * 100) : 0 }}%)
                                    </span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-900 rounded-full h-2.5 overflow-hidden">
                                    <div 
                                        class="bg-emerald-600 h-2.5 rounded-full" 
                                        :style="`width: ${reportData.total_checkins > 0 ? (reportData.patient_breakdown.staff / reportData.total_checkins) * 100 : 0}%`"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visit Severity / Type Breakdown -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 print:border-gray-300 print:shadow-none print:p-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-b pb-2">
                            <BarChart3 class="h-5 w-5 text-indigo-500 no-print" />
                            Visit Types Breakdown
                        </h3>

                        <div class="space-y-4">
                            <!-- Walk-in -->
                            <div>
                                <div class="flex justify-between text-sm font-semibold mb-1">
                                    <span class="text-gray-655 dark:text-gray-300 font-medium">Walk-ins / Consultations</span>
                                    <span class="text-gray-900 dark:text-white font-bold">{{ reportData.visit_types.walk_in }}</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-900 rounded-full h-2 overflow-hidden">
                                    <div 
                                        class="bg-blue-500 h-2 rounded-full" 
                                        :style="`width: ${reportData.total_checkins > 0 ? (reportData.visit_types.walk_in / reportData.total_checkins) * 100 : 0}%`"
                                    ></div>
                                </div>
                            </div>

                            <!-- Appointments -->
                            <div>
                                <div class="flex justify-between text-sm font-semibold mb-1">
                                    <span class="text-gray-655 dark:text-gray-300 font-medium">Scheduled Appointments</span>
                                    <span class="text-gray-900 dark:text-white font-bold">{{ reportData.visit_types.appointment }}</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-900 rounded-full h-2 overflow-hidden">
                                    <div 
                                        class="bg-indigo-500 h-2 rounded-full" 
                                        :style="`width: ${reportData.total_checkins > 0 ? (reportData.visit_types.appointment / reportData.total_checkins) * 100 : 0}%`"
                                    ></div>
                                </div>
                            </div>

                            <!-- Emergencies -->
                            <div>
                                <div class="flex justify-between text-sm font-semibold mb-1">
                                    <span class="text-gray-655 dark:text-gray-300 font-medium text-red-600 dark:text-red-400">Emergencies / Triage</span>
                                    <span class="text-gray-900 dark:text-white font-bold text-red-655">{{ reportData.visit_types.emergency }}</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-900 rounded-full h-2 overflow-hidden">
                                    <div 
                                        class="bg-red-500 h-2 rounded-full" 
                                        :style="`width: ${reportData.total_checkins > 0 ? (reportData.visit_types.emergency / reportData.total_checkins) * 100 : 0}%`"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Dispensed Pharmacy Stock Ledger -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 print:border-gray-300 print:shadow-none print:p-4 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-b pb-2">
                        <Plus class="h-5 w-5 text-indigo-500 no-print" />
                        Top Dispensed Pharmacy Medications & Supplies
                    </h3>

                    <div v-if="reportData.top_dispensed.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-250 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantity Dispensed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="(item, idx) in reportData.top_dispensed" :key="idx" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">{{ item.name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 text-center font-bold">{{ item.quantity }} units</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center font-medium">
                        No pharmacy medications or supplies recorded as dispensed in this date range.
                    </p>
                </div>

                <!-- Printable Report Footer Sign-offs -->
                <div class="hidden print:grid grid-cols-2 gap-8 mt-16 text-center">
                    <div class="border-t border-gray-400 pt-3">
                        <p class="text-sm font-bold text-gray-800">Prepared By</p>
                        <p class="text-xs text-gray-600 mt-4">Clinic Duty Officer / Nurse In-Charge</p>
                        <p class="text-xs text-gray-500 mt-1">Signature & Date</p>
                    </div>
                    <div class="border-t border-gray-400 pt-3">
                        <p class="text-sm font-bold text-gray-800">Approved By</p>
                        <p class="text-xs text-gray-600 mt-4">Chief Medical Director</p>
                        <p class="text-xs text-gray-500 mt-1">Signature & Date</p>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>

<style>
@media print {
    /* Hide layout sidebar, header, navigation, filters, buttons, breadcrumbs etc */
    .no-print,
    nav,
    aside,
    header,
    button,
    .breadcrumbs,
    .sidebar-trigger,
    .user-menu {
        display: none !important;
    }
    
    /* Reset background colors for page sheets */
    body, html, main {
        background-color: #fff !important;
        color: #000 !important;
        font-size: 12pt;
    }

    .print\:py-0 {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    .print\:px-0 {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    /* Keep grid details correctly side-by-side during print */
    .print\:grid-cols-3 {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }

    .print\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    .print\:gap-4 {
        gap: 1rem !important;
    }

    .print\:border-gray-300 {
        border: 1px solid #d1d5db !important;
    }

    .print\:shadow-none {
        box-shadow: none !important;
    }

    .print\:p-4 {
        padding: 1rem !important;
    }
}
</style>
