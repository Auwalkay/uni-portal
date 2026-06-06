<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Activity, Plus, Search, Trash2, Edit, Filter, 
    Check, X, AlertCircle, Info, Calendar, User, 
    Heart, Bed, BookOpen, Clock, PhoneCall, Link2, FileText, Printer
} from 'lucide-vue-next';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    activeVisits: Array<{
        id: number;
        status: string;
        check_in_at: string;
        symptoms: string;
        visit_type: string;
        bed_number: string | null;
        admitted_to_bed_at: string | null;
        patient: { 
            id: string; 
            name: string; 
            email: string;
            student?: { 
                matriculation_number: string;
                next_of_kin_name: string;
                next_of_kin_phone: string;
                next_of_kin_relationship: string;
            }
            staff?: {
                staff_number: string;
                next_of_kin_name: string;
                next_of_kin_phone: string;
                next_of_kin_relationship: string;
            }
        };
        attendant: { name: string } | null;
    }>;
    stats: {
        waiting_count: number;
        observation_count: number;
        total_today: number;
        low_stock_count: number;
    };
    beds: Array<{
        id: number;
        name: string;
        description: string | null;
        is_active: boolean;
    }>;
    allSupplies: Array<{
        id: number;
        name: string;
        category: string;
        stock_quantity: number;
        alert_threshold: number;
        expiry_date: string | null;
    }>;
}>();

const showCheckInModal = ref(false);
const showTreatmentModal = ref(false);
const showAssignBedModal = ref(false);
const selectedVisit = ref<any>(null);

const studentQuery = ref('');
const studentSearchResults = ref<any[]>([]);

const checkInForm = useForm({
    user_id: '',
    symptoms: '',
    visit_type: 'walk_in',
    bed_number: '',
});

const treatmentForm = useForm({
    blood_pressure: '',
    temperature: '' as any,
    weight: '' as any,
    findings: '',
    treatment_given: '',
    medicines_dispensed: [] as Array<{ id: number; name: string; quantity: number }>,
    parent_contacted: false,
    parent_contact_notes: '',
    referral_hospital: '',
    referral_notes: '',
    recommended_tests: [] as string[],
    external_prescriptions: [] as Array<{ name: string; dosage: string }>,
    discharge_patient: false,
});

const newTestInput = ref('');
const newPrescName = ref('');
const newPrescDosage = ref('');

const addTestRecommendation = () => {
    if (newTestInput.value.trim()) {
        treatmentForm.recommended_tests.push(newTestInput.value.trim());
        newTestInput.value = '';
    }
};

const removeTestRecommendation = (idx: number) => {
    treatmentForm.recommended_tests.splice(idx, 1);
};

const addExternalPrescription = () => {
    if (newPrescName.value.trim() && newPrescDosage.value.trim()) {
        treatmentForm.external_prescriptions.push({
            name: newPrescName.value.trim(),
            dosage: newPrescDosage.value.trim()
        });
        newPrescName.value = '';
        newPrescDosage.value = '';
    }
};

const removeExternalPrescription = (idx: number) => {
    treatmentForm.external_prescriptions.splice(idx, 1);
};

const patientHistory = ref<any[]>([]);

const assignBedForm = useForm({
    bed_number: '',
});

const searchStudents = async () => {
    if (studentQuery.value.length < 2) {
        studentSearchResults.value = [];
        return;
    }
    const response = await fetch(`/admin/sickbay/students/search?query=${studentQuery.value}`);
    studentSearchResults.value = await response.json();
};

const selectStudent = (student: any) => {
    checkInForm.user_id = student.id;
    studentQuery.value = `${student.name} (${student.type}: ${student.matriculation_number})`;
    studentSearchResults.value = [];
};

const submitCheckIn = () => {
    checkInForm.post('/admin/sickbay/check-in', {
        onSuccess: () => {
            showCheckInModal.value = false;
            checkInForm.reset();
            studentQuery.value = '';
        }
    });
};

const openTreatmentModal = async (visit: any) => {
    selectedVisit.value = visit;
    treatmentForm.reset();
    newTestInput.value = '';
    newPrescName.value = '';
    newPrescDosage.value = '';
    treatmentForm.discharge_patient = false;
    
    patientHistory.value = [];
    try {
        const response = await fetch(`/admin/sickbay/patients/${visit.patient.id}/history`);
        patientHistory.value = await response.json();
    } catch (err) {
        console.error("Failed to load patient history:", err);
    }
    
    if (visit.medical_log) {
        treatmentForm.blood_pressure = visit.medical_log.blood_pressure || '';
        treatmentForm.temperature = visit.medical_log.temperature || '';
        treatmentForm.weight = visit.medical_log.weight || '';
        treatmentForm.findings = visit.medical_log.findings || '';
        treatmentForm.treatment_given = visit.medical_log.treatment_given || '';
        treatmentForm.medicines_dispensed = visit.medical_log.medicines_dispensed || [];
        treatmentForm.parent_contacted = visit.medical_log.parent_contacted || false;
        treatmentForm.parent_contact_notes = visit.medical_log.parent_contact_notes || '';
        treatmentForm.referral_hospital = visit.medical_log.referral_hospital || '';
        treatmentForm.referral_notes = visit.medical_log.referral_notes || '';
        treatmentForm.recommended_tests = visit.medical_log.recommended_tests || [];
        treatmentForm.external_prescriptions = visit.medical_log.external_prescriptions || [];
    }
    showTreatmentModal.value = true;
};

const addDispensedMedicine = (item: any) => {
    const existing = treatmentForm.medicines_dispensed.find(m => m.id === item.id);
    if (existing) {
        existing.quantity += 1;
    } else {
        treatmentForm.medicines_dispensed.push({
            id: item.id,
            name: item.name,
            quantity: 1,
        });
    }
};

const removeDispensedMedicine = (index: number) => {
    treatmentForm.medicines_dispensed.splice(index, 1);
};

const submitTreatment = () => {
    treatmentForm.post(`/admin/sickbay/treatment/${selectedVisit.value.id}`, {
        onSuccess: () => {
            showTreatmentModal.value = false;
            selectedVisit.value = null;
            treatmentForm.reset();
        }
    });
};

const openAssignBedModal = (visit: any) => {
    selectedVisit.value = visit;
    assignBedForm.bed_number = '';
    showAssignBedModal.value = true;
};

const submitAssignBed = () => {
    assignBedForm.post(`/admin/sickbay/beds/${selectedVisit.value.id}/assign`, {
        onSuccess: () => {
            showAssignBedModal.value = false;
            selectedVisit.value = null;
        }
    });
};

const getBedOccupant = (bedName: string) => {
    return props.activeVisits.find(v => v.bed_number === bedName && v.status === 'under_observation');
};
</script>

<template>
    <Head title="Sickbay Active Queue" />

    <AdminLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4 border-b pb-5">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2.5">
                            <Activity class="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                            School Sickbay
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Manage student walk-ins, log medical treatments, bed rest, and generate referrals.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="showCheckInModal = true"
                            class="inline-flex items-center px-4 py-2.5 border border-transparent rounded-xl shadow-md text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <Plus class="h-4.5 w-4.5 mr-2" />
                            Check In Patient
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Waiting Patients</p>
                            <p class="text-3xl font-extrabold text-orange-655 mt-1">{{ stats.waiting_count }}</p>
                        </div>
                        <Clock class="h-8 w-8 text-orange-500" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Bed Observations</p>
                            <p class="text-3xl font-extrabold text-blue-600 mt-1">{{ stats.observation_count }}</p>
                        </div>
                        <Bed class="h-8 w-8 text-blue-600" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Total Visits Today</p>
                            <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ stats.total_today }}</p>
                        </div>
                        <Heart class="h-8 w-8 text-red-500" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Low Stock Supplies</p>
                            <p class="text-3xl font-extrabold text-red-600 mt-1">{{ stats.low_stock_count }}</p>
                        </div>
                        <AlertCircle class="h-8 w-8 text-red-650" />
                    </div>
                </div>

                <!-- Page Navigation Sub-menu -->
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <Link
                            href="/admin/sickbay"
                            class="border-indigo-500 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2"
                        >
                            <Activity class="h-4.5 w-4.5" />
                            Active Queue
                            <span v-if="activeVisits.length > 0" class="ml-2 bg-orange-100 dark:bg-orange-950 text-orange-850 dark:text-orange-300 py-0.5 px-2 rounded-full text-xs font-extrabold">
                                {{ activeVisits.length }}
                            </span>
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
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <FileText class="h-4.5 w-4.5" />
                            Reports & Stats
                        </Link>
                    </nav>
                </div>

                <!-- Active Queue View -->
                <div class="space-y-4">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Symptoms / Complaint</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check In Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status / Bed</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="visit in activeVisits" :key="visit.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">{{ visit.patient?.name }}</div>
                                        <div class="text-xs text-gray-550">
                                            {{ visit.patient?.student?.matriculation_number ? 'Matric: ' + visit.patient.student.matriculation_number : (visit.patient?.staff?.staff_number ? 'Staff ID: ' + visit.patient.staff.staff_number : 'Staff') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ visit.symptoms }}
                                        <span v-if="visit.visit_type === 'emergency'" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-400 animate-pulse">
                                            EMERGENCY
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ new Date(visit.check_in_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="visit.status === 'under_observation'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                            <Bed class="h-3.5 w-3.5 mr-1" /> {{ visit.bed_number }}
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-850 dark:bg-orange-950 dark:text-orange-400">
                                            Waiting
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button v-if="visit.status === 'waiting'" @click="openAssignBedModal(visit)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3" title="Assign Bed">
                                            <Bed class="h-5 w-5 inline" />
                                        </button>
                                        <button @click="openTreatmentModal(visit)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-bold inline-flex items-center gap-1">
                                            <Heart class="h-4.5 w-4.5" /> Attend
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="activeVisits.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400 font-medium">
                                        No patients currently in active queue.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Check-In Walk-In Modal -->
        <div v-if="showCheckInModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b dark:border-gray-750 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Check In Walk-In Patient</h3>
                    <button @click="showCheckInModal = false" class="text-gray-400 hover:text-gray-600"><X class="h-6 w-6" /></button>
                </div>
                <form @submit.prevent="submitCheckIn" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-750 dark:text-gray-300 mb-1">Search Patient (Student or Staff)</label>
                        <input 
                            v-model="studentQuery" 
                            type="text" 
                            placeholder="Type name, matric, or staff number..." 
                            class="w-full px-3 py-2 border rounded-lg bg-background text-sm focus:ring-2 focus:ring-indigo-500"
                            @input="searchStudents"
                        />
                        <InputError :message="checkInForm.errors.user_id" class="mt-1" />
                        <div v-if="studentSearchResults.length > 0" class="mt-2 border rounded-lg max-h-40 overflow-y-auto bg-white dark:bg-gray-900">
                            <div 
                                v-for="student in studentSearchResults" 
                                :key="student.id"
                                @click="selectStudent(student)"
                                class="p-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer text-sm"
                            >
                                {{ student.name }} ({{ student.type }}: {{ student.matriculation_number }})
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-750 dark:text-gray-300 mb-1">Complaint / Symptoms</label>
                        <textarea v-model="checkInForm.symptoms" required rows="3" placeholder="Describe symptoms e.g. headache, fever, sprained ankle..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-650 rounded-lg dark:bg-gray-900 dark:text-white"></textarea>
                        <InputError :message="checkInForm.errors.symptoms" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-750 dark:text-gray-300 mb-1">Visit Severity</label>
                            <select v-model="checkInForm.visit_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-650 rounded-lg dark:bg-gray-900 dark:text-white text-sm">
                                <option value="walk_in">Walk-in Checkup</option>
                                <option value="appointment">Appointment</option>
                                <option value="emergency">Emergency / Acute</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-750 dark:text-gray-300 mb-1">Assign Bed (Optional)</label>
                            <select v-model="checkInForm.bed_number" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-650 rounded-lg dark:bg-gray-900 dark:text-white text-sm">
                                <option value="">No Bed Placement</option>
                                <option v-for="bed in props.beds" :key="bed.id" :value="bed.name" :disabled="!!getBedOccupant(bed.name)">{{ bed.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t dark:border-gray-750">
                        <button type="button" @click="showCheckInModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="checkInForm.processing || !checkInForm.user_id" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50">Check In Patient</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance / Treatment Log Modal -->
        <div v-if="showTreatmentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b dark:border-gray-750 flex items-center justify-between bg-muted/10">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Record Treatment Details</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Patient: {{ selectedVisit?.patient.name }}</p>
                    </div>
                    <button @click="showTreatmentModal = false" class="text-gray-400 hover:text-gray-600"><X class="h-6 w-6" /></button>
                </div>
                <form @submit.prevent="submitTreatment" class="p-6 space-y-4">
                    <!-- Next of Kin Emergency Details widget -->
                    <div class="p-4 bg-orange-50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/30 rounded-xl">
                        <h4 class="text-xs font-bold text-orange-850 dark:text-orange-300 flex items-center gap-1 uppercase tracking-wider mb-2">
                            <PhoneCall class="h-4 w-4" /> Next of Kin Contacts
                        </h4>
                        <div v-if="selectedVisit?.patient.student" class="grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <p class="text-muted-foreground font-semibold">Contact Name:</p>
                                <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">
                                    {{ selectedVisit.patient.student.next_of_kin_name }} ({{ selectedVisit.patient.student.next_of_kin_relationship }})
                                </p>
                            </div>
                            <div>
                                <p class="text-muted-foreground font-semibold">Phone Number:</p>
                                <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">
                                    {{ selectedVisit.patient.student.next_of_kin_phone }}
                                </p>
                            </div>
                        </div>
                        <div v-else-if="selectedVisit?.patient.staff" class="grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <p class="text-muted-foreground font-semibold">Contact Name:</p>
                                <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">
                                    {{ selectedVisit.patient.staff.next_of_kin_name }} ({{ selectedVisit.patient.staff.next_of_kin_relationship || 'Next of Kin' }})
                                </p>
                            </div>
                            <div>
                                <p class="text-muted-foreground font-semibold">Phone Number:</p>
                                <p class="font-bold text-gray-800 dark:text-gray-200 mt-0.5">
                                    {{ selectedVisit.patient.staff.next_of_kin_phone }}
                                </p>
                            </div>
                        </div>
                        <div v-else class="text-xs text-muted-foreground italic">No next of kin records found.</div>
                    </div>

                    <!-- Previous Treatment History Widget -->
                    <div v-if="patientHistory.length > 0" class="p-4 bg-indigo-50/50 dark:bg-indigo-950/10 border border-indigo-150 dark:border-indigo-900/30 rounded-xl space-y-3">
                        <h4 class="text-xs font-bold text-indigo-850 dark:text-indigo-300 flex items-center gap-1.5 uppercase tracking-wider">
                            <Activity class="h-4 w-4 text-indigo-500" /> Previous Treatment History ({{ patientHistory.length }})
                        </h4>
                        <div class="max-h-32 overflow-y-auto space-y-3 pr-1.5 divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="prev in patientHistory" :key="prev.id" class="pt-2.5 first:pt-0">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ new Date(prev.check_in_at).toLocaleDateString() }}</span>
                                    <span class="text-[9px] bg-muted px-1.5 py-0.2 rounded uppercase font-semibold text-gray-500">{{ prev.visit_type }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs mt-1.5 text-gray-600 dark:text-gray-400">
                                    <div>
                                        <span class="font-semibold block text-[9px] text-gray-400">Symptoms:</span>
                                        <span>{{ prev.symptoms }}</span>
                                    </div>
                                    <div v-if="prev.medical_log">
                                        <span class="font-semibold block text-[9px] text-gray-400">Findings:</span>
                                        <span class="font-medium text-indigo-650 dark:text-indigo-400">{{ prev.medical_log.findings }}</span>
                                    </div>
                                </div>
                                <div v-if="prev.medical_log?.treatment_given" class="text-xs text-gray-600 dark:text-gray-400 mt-1.5">
                                    <span class="font-semibold block text-[9px] text-gray-400">Treatment:</span>
                                    <span>{{ prev.medical_log.treatment_given }}</span>
                                </div>
                                <div v-if="prev.medical_log?.medicines_dispensed && prev.medical_log.medicines_dispensed.length > 0" class="mt-1.5 flex flex-wrap gap-1">
                                    <span v-for="med in prev.medical_log.medicines_dispensed" :key="med.name" class="text-[9px] bg-white dark:bg-gray-900 border px-1.5 py-0.2 rounded">
                                        {{ med.name }} (x{{ med.quantity }})
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-755 dark:text-gray-300 mb-1">Blood Pressure</label>
                            <input v-model="treatmentForm.blood_pressure" type="text" placeholder="e.g. 120/80" class="w-full px-3 py-2 border rounded-lg bg-background text-sm" />
                            <InputError :message="treatmentForm.errors.blood_pressure" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-755 dark:text-gray-300 mb-1">Temperature (°C)</label>
                            <input v-model.number="treatmentForm.temperature" type="number" step="0.1" placeholder="e.g. 37.2" class="w-full px-3 py-2 border rounded-lg bg-background text-sm" />
                            <InputError :message="treatmentForm.errors.temperature" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-755 dark:text-gray-300 mb-1">Weight (kg)</label>
                            <input v-model.number="treatmentForm.weight" type="number" step="0.1" placeholder="e.g. 62.5" class="w-full px-3 py-2 border rounded-lg bg-background text-sm" />
                            <InputError :message="treatmentForm.errors.weight" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-755 dark:text-gray-300 mb-1">Clinical Findings & Assessment</label>
                        <textarea v-model="treatmentForm.findings" required rows="3" placeholder="Describe diagnosis or observations..." class="w-full px-3 py-2 border rounded-lg bg-background text-sm"></textarea>
                        <InputError :message="treatmentForm.errors.findings" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-755 dark:text-gray-300 mb-1">First-Aid Treatment Given</label>
                        <textarea v-model="treatmentForm.treatment_given" required rows="2" placeholder="Describe first aid, wound cleaning, or rest instructions..." class="w-full px-3 py-2 border rounded-lg bg-background text-sm"></textarea>
                        <InputError :message="treatmentForm.errors.treatment_given" class="mt-1" />
                    </div>

                    <!-- Dispense first aid supplies selector -->
                    <div class="border rounded-xl p-4 bg-muted/10 space-y-3">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">1. Dispense On-Site Supplies</h4>
                        <div class="flex gap-2">
                            <select @change="addDispensedMedicine(props.allSupplies.find(s => s.id === parseInt(($event.target as HTMLSelectElement).value))!)" class="flex-1 px-3 py-2 border rounded-lg bg-background text-sm">
                                <option value="">Select supply item to dispense...</option>
                                <option v-for="item in props.allSupplies" :key="item.id" :value="item.id" :disabled="item.stock_quantity <= 0">
                                    {{ item.name }} (Stock: {{ item.stock_quantity }})
                                </option>
                            </select>
                        </div>
                        <div v-if="treatmentForm.medicines_dispensed.length > 0" class="space-y-1 mt-2">
                            <div v-for="(med, idx) in treatmentForm.medicines_dispensed" :key="med.id" class="flex items-center justify-between p-2 bg-muted rounded-lg text-xs">
                                <span>{{ med.name }}</span>
                                <div class="flex items-center gap-3">
                                    <input v-model.number="med.quantity" type="number" min="1" class="w-12 text-center border rounded bg-background p-0.5" />
                                    <button type="button" @click="removeDispensedMedicine(idx)" class="text-red-500"><X class="h-4 w-4" /></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommended Tests Widget -->
                    <div class="border border-blue-150 dark:border-blue-900/30 rounded-xl p-4 bg-blue-50/5 dark:bg-blue-950/5 space-y-3">
                        <h4 class="text-xs font-bold text-blue-600 uppercase tracking-wider flex items-center gap-1">
                            <FileText class="h-4 w-4" /> 2. Recommend External Medical Tests (Slip printable)
                        </h4>
                        <div class="flex gap-2">
                            <input 
                                v-model="newTestInput" 
                                type="text" 
                                placeholder="e.g. Full Blood Count, Chest X-Ray, Typhoid Test" 
                                class="flex-1 px-3 py-2 border rounded-lg bg-background text-xs" 
                                @keypress.enter.prevent="addTestRecommendation"
                            />
                            <button type="button" @click="addTestRecommendation" class="px-3 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                Add Test
                            </button>
                        </div>
                        <div v-if="treatmentForm.recommended_tests.length > 0" class="flex flex-wrap gap-1.5 mt-2">
                            <Badge v-for="(test, idx) in treatmentForm.recommended_tests" :key="idx" class="px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 text-xs font-semibold">
                                {{ test }}
                                <button type="button" @click="removeTestRecommendation(idx)" class="ml-1.5 text-blue-500 hover:text-blue-800"><X class="h-3 w-3 inline" /></button>
                            </Badge>
                        </div>
                    </div>

                    <!-- Outside Prescriptions Widget -->
                    <div class="border border-green-150 dark:border-green-900/30 rounded-xl p-4 bg-green-50/5 dark:bg-green-950/5 space-y-3">
                        <h4 class="text-xs font-bold text-green-600 uppercase tracking-wider flex items-center gap-1">
                            <Link2 class="h-4 w-4" /> 3. Recommend Drugs to Purchase Outside (Slip printable)
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input 
                                v-model="newPrescName" 
                                type="text" 
                                placeholder="Drug Name e.g. Amoxicillin 500mg" 
                                class="px-3 py-2 border rounded-lg bg-background text-xs" 
                            />
                            <div class="flex gap-2">
                                <input 
                                    v-model="newPrescDosage" 
                                    type="text" 
                                    placeholder="Dosage Instruction e.g. 1 tab three times daily for 5 days" 
                                    class="flex-1 px-3 py-2 border rounded-lg bg-background text-xs" 
                                    @keypress.enter.prevent="addExternalPrescription"
                                />
                                <button type="button" @click="addExternalPrescription" class="px-3 py-2 text-xs font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">
                                    Add
                                </button>
                            </div>
                        </div>
                        <div v-if="treatmentForm.external_prescriptions.length > 0" class="space-y-1.5 mt-2">
                            <div v-for="(presc, idx) in treatmentForm.external_prescriptions" :key="idx" class="flex justify-between items-center bg-green-500/5 p-2 rounded-lg text-xs border border-green-500/10">
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ presc.name }}</span>
                                    <span class="text-muted-foreground ml-2">({{ presc.dosage }})</span>
                                </div>
                                <button type="button" @click="removeExternalPrescription(idx)" class="text-red-500"><X class="h-4 w-4" /></button>
                            </div>
                        </div>
                    </div>

                    <!-- Next of Kin Contact Log -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/30 border rounded-xl space-y-3">
                        <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <input type="checkbox" v-model="treatmentForm.parent_contacted" class="mr-2 h-4 w-4 text-indigo-650" />
                            Next of Kin has been Contacted
                        </label>
                        <div v-if="treatmentForm.parent_contacted">
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">Next of Kin Communication Details</label>
                            <textarea v-model="treatmentForm.parent_contact_notes" rows="2" placeholder="Describe next of kin contact logs..." class="w-full px-3 py-2 border rounded-lg bg-background text-xs"></textarea>
                            <InputError :message="treatmentForm.errors.parent_contact_notes" class="mt-1" />
                        </div>
                    </div>

                    <!-- Discharge Bed Option (Only for under_observation patients) -->
                    <div v-if="selectedVisit?.status === 'under_observation'" class="p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30 rounded-xl space-y-2">
                        <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                            <input type="checkbox" v-model="treatmentForm.discharge_patient" class="mr-2 h-4 w-4 text-indigo-650" />
                            Discharge patient from Bed and check out
                        </label>
                        <p class="text-xs text-muted-foreground">Checking this will release Bed {{ selectedVisit.bed_number }} and mark this visit as completed / discharged.</p>
                        <InputError :message="treatmentForm.errors.discharge_patient" class="mt-1" />
                    </div>

                    <!-- Hospital Referral details -->
                    <div class="p-4 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 rounded-xl space-y-3">
                        <h4 class="text-xs font-bold text-red-850 dark:text-red-300 flex items-center gap-1 uppercase tracking-wider">
                            <Link2 class="h-4 w-4" /> Hospital Referral (If required)
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Referral Destination Hospital</label>
                                <input v-model="treatmentForm.referral_hospital" type="text" placeholder="e.g. City General Hospital" class="w-full px-3 py-2 border rounded-lg bg-background text-xs" />
                                <InputError :message="treatmentForm.errors.referral_hospital" class="mt-1" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Referral Instructions / Notes</label>
                                <input v-model="treatmentForm.referral_notes" type="text" placeholder="e.g. Suspected fracture, requires X-Ray diagnostics..." class="w-full px-3 py-2 border rounded-lg bg-background text-xs" />
                                <InputError :message="treatmentForm.errors.referral_notes" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t dark:border-gray-750">
                        <button type="button" @click="showTreatmentModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="treatmentForm.processing" class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50">Save Treatment Sheet</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Assign Bed Modal -->
        <div v-if="showAssignBedModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b dark:border-gray-750 flex items-center justify-between bg-muted/10">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Assign Patient to Bed</h3>
                    <button @click="showAssignBedModal = false" class="text-gray-400 hover:text-gray-600"><X class="h-6 w-6" /></button>
                </div>
                <form @submit.prevent="submitAssignBed" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Select Bed</label>
                        <select v-model="assignBedForm.bed_number" required class="w-full px-3 py-2 border rounded-lg bg-background text-sm">
                            <option value="">Choose available bed...</option>
                            <option v-for="bed in props.beds" :key="bed.id" :value="bed.name" :disabled="!!getBedOccupant(bed.name)">{{ bed.name }}</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showAssignBedModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="assignBedForm.processing || !assignBedForm.bed_number" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Assign Bed</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
