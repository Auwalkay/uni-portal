<script setup lang="ts">
import { onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Printer, ArrowLeft, Heart, Calendar, User, Activity, FileText, CheckSquare, PlusCircle
} from 'lucide-vue-next';

const props = defineProps<{
    visit: {
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
            student?: { 
                matriculation_number: string;
                next_of_kin_name: string;
                next_of_kin_phone: string;
                next_of_kin_relationship: string;
                next_of_kin_address: string;
            }
            staff?: {
                staff_number: string;
                next_of_kin_name: string;
                next_of_kin_phone: string;
                next_of_kin_relationship: string;
                next_of_kin_address: string;
            }
        };
        attendant: { name: string } | null;
        medical_log: {
            blood_pressure: string | null;
            temperature: number | null;
            weight: number | null;
            findings: string;
            treatment_given: string;
            medicines_dispensed: Array<{ name: string; quantity: number }> | null;
            recommended_tests: Array<string> | null;
            external_prescriptions: Array<{ name: string; dosage: string }> | null;
            parent_contacted: boolean;
            parent_contact_notes: string | null;
            referral_hospital: string | null;
            referral_notes: string | null;
        } | null;
    };
}>();

const printSlip = () => {
    window.print();
};

onMounted(() => {
    // Automatically trigger print dialog on page load
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head :title="`Prescription / Referral Slip - ${visit.patient.name}`" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 no-print-bg">
        <!-- Action bar at the top (Hidden on print) -->
        <div class="max-w-3xl mx-auto mb-6 px-4 no-print flex items-center justify-between">
            <Link 
                href="/admin/sickbay/logs" 
                class="inline-flex items-center text-sm font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
            >
                <ArrowLeft class="h-4 w-4 mr-2" />
                Back to Treatment Logs
            </Link>
            
            <button 
                @click="printSlip"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-xl text-white bg-indigo-650 hover:bg-indigo-700 shadow-md focus:outline-none transition"
            >
                <Printer class="h-4 w-4 mr-2" />
                Print Slip
            </button>
        </div>

        <!-- A4 Page Container -->
        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 shadow-lg rounded-2xl border border-gray-150 dark:border-gray-700 p-8 md:p-12 print:shadow-none print:border-none print:p-0 print:bg-white print:text-black">
            
            <!-- School Medical Center Letterhead -->
            <div class="text-center border-b-2 border-gray-850 dark:border-gray-600 pb-6 mb-8 flex flex-col items-center">
                <div class="flex items-center gap-2 mb-2">
                    <Heart class="h-8 w-8 text-red-600 dark:text-red-500 fill-current" />
                    <span class="text-2xl font-black uppercase tracking-wider text-gray-900 dark:text-white print:text-black">University Health Services</span>
                </div>
                <h2 class="text-lg font-bold text-gray-700 dark:text-gray-300 print:text-gray-700">School Sickbay Medical Recommendation & Referral</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Campus Medical Complex • Tel: +234 800-CLINIC • E-mail: medical@university.edu.ng</p>
            </div>

            <!-- Meta Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 text-sm print:grid-cols-2">
                <!-- Patient Info -->
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 print:bg-gray-50 print:border-gray-200">
                    <h3 class="font-bold text-xs uppercase text-indigo-650 dark:text-indigo-400 mb-3 flex items-center gap-1.5 print:text-indigo-700">
                        <User class="h-4 w-4" /> Patient Demographics
                    </h3>
                    <div class="space-y-1.5 text-gray-800 dark:text-gray-250 print:text-black">
                        <div><span class="font-semibold text-gray-500 print:text-gray-600">Full Name:</span> {{ visit.patient.name }}</div>
                        <div>
                            <span class="font-semibold text-gray-500 print:text-gray-600">ID/Matric Number:</span> 
                            {{ visit.patient.student?.matriculation_number || visit.patient.staff?.staff_number || 'N/A' }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500 print:text-gray-600">Patient Type:</span> 
                            {{ visit.patient.student ? 'Student' : (visit.patient.staff ? 'Staff Member' : 'General') }}
                        </div>
                        <div><span class="font-semibold text-gray-500 print:text-gray-600">Email:</span> {{ visit.patient.email }}</div>
                    </div>
                </div>

                <!-- Emergency Contact (Next of Kin) -->
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 print:bg-gray-50 print:border-gray-200">
                    <h3 class="font-bold text-xs uppercase text-indigo-650 dark:text-indigo-400 mb-3 flex items-center gap-1.5 print:text-indigo-700">
                        <Activity class="h-4 w-4" /> Emergency Contact / Next of Kin
                    </h3>
                    <div class="space-y-1.5 text-gray-800 dark:text-gray-250 print:text-black">
                        <div>
                            <span class="font-semibold text-gray-500 print:text-gray-600">Name:</span> 
                            {{ visit.patient.student?.next_of_kin_name || visit.patient.staff?.next_of_kin_name || 'Not Registered' }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500 print:text-gray-600">Phone:</span> 
                            {{ visit.patient.student?.next_of_kin_phone || visit.patient.staff?.next_of_kin_phone || 'N/A' }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500 print:text-gray-600">Relationship:</span> 
                            {{ visit.patient.student?.next_of_kin_relationship || visit.patient.staff?.next_of_kin_relationship || 'N/A' }}
                        </div>
                        <div class="truncate">
                            <span class="font-semibold text-gray-500 print:text-gray-600">Address:</span> 
                            {{ visit.patient.student?.next_of_kin_address || visit.patient.staff?.next_of_kin_address || 'N/A' }}
                        </div>
                    </div>
                </div>

                <!-- Visit Meta -->
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 print:bg-gray-50 print:border-gray-200">
                    <h3 class="font-bold text-xs uppercase text-indigo-650 dark:text-indigo-400 mb-3 flex items-center gap-1.5 print:text-indigo-700">
                        <Calendar class="h-4 w-4" /> Visit & Vitals Info
                    </h3>
                    <div class="space-y-1.5 text-gray-800 dark:text-gray-250 print:text-black">
                        <div><span class="font-semibold text-gray-500 print:text-gray-600">Check-in Date:</span> {{ new Date(visit.check_in_at).toLocaleDateString() }}</div>
                        <div><span class="font-semibold text-gray-500 print:text-gray-600">Time:</span> {{ new Date(visit.check_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</div>
                        <div>
                            <span class="font-semibold text-gray-500 print:text-gray-600">Temperature:</span> 
                            {{ visit.medical_log?.temperature ? `${visit.medical_log.temperature} °C` : 'N/A' }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-500 print:text-gray-600">BP:</span> 
                            {{ visit.medical_log?.blood_pressure || 'N/A' }}
                        </div>
                    </div>
                </div>

                <!-- Attending Clinician -->
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 print:bg-gray-50 print:border-gray-200">
                    <h3 class="font-bold text-xs uppercase text-indigo-650 dark:text-indigo-400 mb-3 flex items-center gap-1.5 print:text-indigo-700">
                        <FileText class="h-4 w-4" /> Prescribing Provider
                    </h3>
                    <div class="space-y-1.5 text-gray-800 dark:text-gray-250 print:text-black">
                        <div><span class="font-semibold text-gray-500 print:text-gray-600">Clinician Name:</span> {{ visit.attendant?.name || 'On Duty Staff' }}</div>
                        <div><span class="font-semibold text-gray-500 print:text-gray-600">Admitting Bed:</span> {{ visit.bed_number || 'None (Outpatient)' }}</div>
                        <div><span class="font-semibold text-gray-500 print:text-gray-600">Severity/Triage:</span> <span class="capitalize">{{ visit.visit_type }}</span></div>
                    </div>
                </div>
            </div>

            <!-- Findings and Treatment Given -->
            <div class="mb-8 border-t dark:border-gray-700 pt-6">
                <h3 class="font-extrabold text-sm uppercase text-gray-800 dark:text-gray-200 mb-3 print:text-black">Clinical Summary</h3>
                <div class="grid grid-cols-1 gap-4 text-sm">
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 print:bg-gray-50 print:border-gray-200">
                        <span class="font-bold text-gray-500 print:text-gray-600 block mb-1">Assessment / Findings:</span>
                        <p class="text-gray-800 dark:text-gray-250 leading-relaxed font-medium print:text-black">{{ visit.medical_log?.findings || 'No assessment findings logged.' }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700 print:bg-gray-50 print:border-gray-200">
                        <span class="font-bold text-gray-500 print:text-gray-600 block mb-1">Clinic Treatment Given:</span>
                        <p class="text-gray-800 dark:text-gray-250 leading-relaxed font-medium print:text-black">{{ visit.medical_log?.treatment_given || 'None' }}</p>
                    </div>
                </div>
            </div>

            <!-- Referral Recommendations (Visible only if referral details exist) -->
            <div v-if="visit.medical_log?.referral_hospital" class="mb-8 border-l-4 border-red-500 pl-4 py-1">
                <h3 class="font-black text-sm uppercase text-red-650 mb-1 print:text-red-700">Official Referral Notice</h3>
                <p class="text-sm text-gray-800 dark:text-gray-200 font-semibold print:text-black">
                    Patient has been referred to <span class="font-bold text-indigo-650 dark:text-indigo-400 print:text-indigo-800">{{ visit.medical_log.referral_hospital }}</span>.
                </p>
                <p v-if="visit.medical_log.referral_notes" class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed print:text-gray-600">
                    Notes: {{ visit.medical_log.referral_notes }}
                </p>
            </div>

            <!-- External Recommended Laboratory Tests -->
            <div class="mb-8 border-t dark:border-gray-700 pt-6" v-if="visit.medical_log?.recommended_tests && visit.medical_log.recommended_tests.length > 0">
                <h3 class="font-extrabold text-sm uppercase text-indigo-650 dark:text-indigo-400 mb-3 flex items-center gap-2 print:text-indigo-700">
                    <CheckSquare class="h-5 w-5" />
                    Recommended Diagnostic & Lab Tests (Please conduct externally)
                </h3>
                <div class="bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-950 p-4 rounded-xl print:bg-white print:border-gray-300">
                    <ul class="space-y-3">
                        <li v-for="(test, index) in visit.medical_log.recommended_tests" :key="index" class="flex items-start text-sm font-semibold text-gray-800 dark:text-gray-250 print:text-black">
                            <span class="inline-block h-5 w-5 mr-3 border border-gray-400 rounded-md print:border-black flex-shrink-0"></span>
                            {{ test }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- External Prescription Slip Details -->
            <div class="mb-8 border-t dark:border-gray-700 pt-6" v-if="visit.medical_log?.external_prescriptions && visit.medical_log.external_prescriptions.length > 0">
                <h3 class="font-extrabold text-sm uppercase text-indigo-650 dark:text-indigo-400 mb-3 flex items-center gap-2 print:text-indigo-700">
                    <PlusCircle class="h-5 w-5" />
                    Outside Pharmacy Prescription Slip (Get drug from external pharmacy)
                </h3>
                <div class="bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-950 p-4 rounded-xl print:bg-white print:border-gray-300">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-indigo-100 dark:divide-indigo-950 print:divide-gray-300">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider print:text-black">Rx Medication Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider print:text-black">Dosage Instructions / Refills</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-indigo-100 dark:divide-indigo-950 print:divide-gray-300">
                                <tr v-for="(item, index) in visit.medical_log.external_prescriptions" :key="index">
                                    <td class="px-3 py-3 text-sm font-bold text-gray-900 dark:text-white print:text-black">{{ item.name }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-750 dark:text-gray-250 font-medium print:text-black">{{ item.dosage }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Signatures and stamps -->
            <div class="grid grid-cols-2 gap-8 border-t dark:border-gray-700 pt-10 mt-12 text-center">
                <div class="flex flex-col items-center">
                    <div class="w-48 border-b border-gray-400 dark:border-gray-650 h-8"></div>
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-350 mt-2 print:text-black">Attending Clinician (Signature)</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">{{ visit.attendant?.name || 'Medical Officer' }}</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-48 border-b border-gray-400 dark:border-gray-650 h-8 flex items-center justify-center">
                        <span class="text-[9px] text-gray-350 font-bold tracking-widest uppercase border border-dashed border-gray-300 dark:border-gray-700 px-2 py-0.5 print:border-gray-400 print:text-gray-400">Clinic Seal & Date</span>
                    </div>
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-350 mt-2 print:text-black">Official Stamp / Date</span>
                </div>
            </div>

        </div>
    </div>
</template>

<style>
/* CSS styles to assist screen design while maintaining high quality printing */
@media screen {
    .no-print-bg {
        background-color: #f3f4f6;
    }
}

@media print {
    /* Hide non-printable panels */
    .no-print {
        display: none !important;
    }

    body, html {
        background: white !important;
        color: black !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .no-print-bg {
        background-color: transparent !important;
        padding: 0 !important;
    }

    /* Standardize page size */
    @page {
        size: A4;
        margin: 20mm;
    }
}
</style>
