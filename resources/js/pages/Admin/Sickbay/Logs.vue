<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Activity, Plus, Bed, BookOpen, Clock, Heart, Search, FileText, Check, Printer
} from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';

defineProps<{
    completedVisits: {
        data: Array<{
            id: number;
            status: string;
            check_in_at: string;
            check_out_at: string | null;
            symptoms: string;
            visit_type: string;
            bed_number: string | null;
            patient: { 
                id: string; 
                name: string; 
                email: string; 
                student?: { matriculation_number: string } 
                staff?: { staff_number: string }
            };
            attendant: { name: string } | null;
            medical_log: {
                blood_pressure: string | null;
                temperature: number | null;
                weight: number | null;
                findings: string;
                treatment_given: string;
                medicines_dispensed: Array<{ name: string; quantity: number }> | null;
                parent_contacted: boolean;
                parent_contact_notes: string | null;
                referral_hospital: string | null;
                referral_notes: string | null;
            } | null;
        }>;
        links: any;
        total: number;
    };
}>();
</script>

<template>
    <Head title="Treatment Logs" />

    <AdminLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4 border-b pb-5">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2.5">
                            <BookOpen class="h-8 w-8 text-indigo-650 dark:text-indigo-400" />
                            Treatment Logs Ledger
                        </h1>
                        <p class="text-sm text-gray-550 dark:text-gray-400 mt-1">
                            Browse completed clinic sheets, next of kin log records, referral plans, and print clinical slips.
                        </p>
                    </div>
                </div>

                <!-- Page Navigation Sub-menu -->
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="-mb-px flex space-x-8">
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
                            class="border-indigo-500 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2"
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
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <FileText class="h-4.5 w-4.5" />
                            Reports & Stats
                        </Link>
                    </nav>
                </div>

                <!-- Treatment Logs Table -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Patient Details</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Diagnosis / Treatment</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Visit Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Next of Kin contacted?</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="visit in completedVisits.data" :key="visit.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white text-sm">{{ visit.patient?.name }}</div>
                                    <div class="text-xs text-gray-550 mt-0.5">
                                        {{ visit.patient?.student?.matriculation_number ? 'Matric: ' + visit.patient.student.matriculation_number : (visit.patient?.staff?.staff_number ? 'Staff ID: ' + visit.patient.staff.staff_number : 'Staff') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    <div class="font-bold text-indigo-700 dark:text-indigo-400 text-xs uppercase tracking-wider">{{ visit.medical_log?.findings || 'No assessment logged' }}</div>
                                    <div class="text-xs text-gray-500 mt-1">Tx: {{ visit.medical_log?.treatment_given || 'None' }}</div>
                                    
                                    <!-- Dispensed pharmacy items -->
                                    <div v-if="visit.medical_log?.medicines_dispensed && visit.medical_log.medicines_dispensed.length > 0" class="text-[10px] text-muted-foreground mt-2 flex flex-wrap gap-1">
                                        <span v-for="med in visit.medical_log.medicines_dispensed" class="bg-gray-100 dark:bg-gray-900 px-1.5 py-0.5 rounded border">
                                            {{ med.name }} (x{{ med.quantity }})
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-medium">
                                    {{ new Date(visit.check_in_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'px-2.5 py-0.5 text-[10px] font-bold rounded uppercase tracking-wider',
                                        visit.status === 'referred' 
                                            ? 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-400' 
                                            : 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-400'
                                    ]">
                                        {{ visit.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span v-if="visit.medical_log?.parent_contacted" class="text-green-600 font-bold flex items-center gap-1">
                                        <Check class="h-4.5 w-4.5" /> Contacted
                                    </span>
                                    <span v-else class="text-gray-400 font-semibold">No</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-semibold">
                                    <a
                                        :href="`/admin/sickbay/visits/${visit.id}/prescription`"
                                        target="_blank"
                                        class="inline-flex items-center px-2.5 py-1.5 border border-indigo-200 rounded-lg text-indigo-650 hover:bg-indigo-50 dark:text-indigo-400 dark:border-indigo-900/50 dark:hover:bg-indigo-950/20"
                                    >
                                        <Printer class="h-3.5 w-3.5 mr-1" />
                                        Print Slip
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="completedVisits.data.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400 font-medium">
                                    No completed treatment logs found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <Pagination :links="completedVisits.links" />

            </div>
        </div>
    </AdminLayout>
</template>
