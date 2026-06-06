<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Activity, Plus, Bed, BookOpen, Clock, Heart, Search, FileText, Calendar, User, PhoneCall, Link2, X
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';

const searchQuery = ref('');
const searchHistoryResults = ref<any[]>([]);
const selectedSearchUser = ref<any>(null);
const searchUserHistory = ref<any[]>([]);
const isSearchingHistory = ref(false);

const searchHistoryUsers = async () => {
    if (searchQuery.value.length < 2) {
        searchHistoryResults.value = [];
        return;
    }
    try {
        const response = await fetch(`/admin/sickbay/students/search?query=${searchQuery.value}`);
        searchHistoryResults.value = await response.json();
    } catch (err) {
        console.error("Failed to search users:", err);
    }
};

const selectSearchUser = async (user: any) => {
    selectedSearchUser.value = user;
    searchHistoryResults.value = [];
    isSearchingHistory.value = true;
    try {
        const response = await fetch(`/admin/sickbay/patients/${user.id}/history`);
        searchUserHistory.value = await response.json();
    } catch (err) {
        console.error("Failed to load user medical history:", err);
    } finally {
        isSearchingHistory.value = false;
    }
};
</script>

<template>
    <Head title="Patient History Search" />

    <AdminLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4 border-b pb-5">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2.5">
                            <Search class="h-8 w-8 text-indigo-650 dark:text-indigo-400" />
                            Patient History Lookup
                        </h1>
                        <p class="text-sm text-gray-555 dark:text-gray-400 mt-1">
                            Query student or staff records in real-time to load their complete clinical profile, vitals trends, and past treatments.
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
                            class="border-indigo-500 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2"
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

                <!-- Patient History Search Body -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="relative max-w-lg mb-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Search Patient (Name, Matric, or Staff ID)</label>
                        <div class="relative">
                            <input 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Type name or identification number..." 
                                class="w-full pl-10 pr-4 py-2.5 border rounded-lg bg-background text-sm focus:ring-2 focus:ring-indigo-500"
                                @input="searchHistoryUsers"
                            />
                            <Search class="absolute left-3 top-3 h-4.5 w-4.5 text-gray-400" />
                        </div>
                        
                        <!-- Search Dropdown Results -->
                        <div v-if="searchHistoryResults.length > 0" class="absolute left-0 right-0 mt-1 border rounded-lg max-h-56 overflow-y-auto bg-white dark:bg-gray-900 z-10 shadow-lg divide-y divide-gray-100 dark:divide-gray-800">
                            <div 
                                v-for="user in searchHistoryResults" 
                                :key="user.id"
                                @click="selectSearchUser(user)"
                                class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer text-sm transition-colors flex justify-between items-center"
                            >
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ user.name }}</div>
                                    <div class="text-xs text-gray-500">{{ user.email }}</div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-850 dark:bg-gray-800 dark:text-gray-300">
                                        {{ user.type }}: {{ user.matriculation_number }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Patient Profile and History Feed -->
                    <div v-if="selectedSearchUser" class="space-y-6 border-t pt-6">
                        <!-- Demographics & Next of Kin Emergency Details -->
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-muted/20 p-4 rounded-xl border border-dashed">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ selectedSearchUser.name }}</h3>
                                <p class="text-xs text-muted-foreground mt-0.5 font-medium">
                                    Email: {{ selectedSearchUser.email }} | ID: {{ selectedSearchUser.matriculation_number }} ({{ selectedSearchUser.type }})
                                </p>
                            </div>
                            <div v-if="selectedSearchUser.parent_name" class="text-xs text-gray-700 dark:text-gray-300 border-l border-gray-200 dark:border-gray-700 pl-4">
                                <span class="font-bold text-orange-850 dark:text-orange-300 block uppercase tracking-wider text-[9px] mb-1">Emergency Next of Kin</span>
                                <p class="font-bold text-sm">{{ selectedSearchUser.parent_name }}</p>
                                <p class="text-muted-foreground mt-0.5 font-semibold">{{ selectedSearchUser.parent_phone }}</p>
                                <p class="text-[10px] text-muted-foreground mt-0.5">{{ selectedSearchUser.parent_address }}</p>
                            </div>
                        </div>

                        <!-- History Timeline Feed -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-4 uppercase tracking-wider flex items-center gap-1.5">
                                <Activity class="h-4.5 w-4.5 text-red-500 animate-pulse" />
                                Treatment History Timeline
                            </h4>
                            
                            <div v-if="isSearchingHistory" class="py-10 text-center text-sm text-gray-500">
                                <Clock class="h-6 w-6 animate-spin mx-auto mb-2 text-indigo-500" />
                                Retrieving medical logs database...
                            </div>
                            
                            <div v-else-if="searchUserHistory.length > 0" class="space-y-4">
                                <div v-for="visit in searchUserHistory" :key="visit.id" class="border rounded-xl p-4 bg-background shadow-sm hover:shadow-md transition relative">
                                    <div :class="['absolute left-0 top-0 bottom-0 w-1 rounded-l-xl', visit.status === 'referred' ? 'bg-red-500' : 'bg-green-500']"></div>
                                    
                                    <div class="flex justify-between items-start gap-4 border-b border-dashed pb-3 mb-3">
                                        <div>
                                            <Badge variant="outline" class="text-[10px] font-bold uppercase tracking-wider">
                                                {{ visit.visit_type }}
                                            </Badge>
                                            <Badge :variant="visit.status === 'referred' ? 'destructive' : 'default'" class="ml-1.5 text-[10px] font-bold uppercase tracking-wider">
                                                {{ visit.status }}
                                            </Badge>
                                            <span v-if="visit.bed_number" class="ml-1.5 text-[10px] bg-blue-50 text-blue-650 dark:bg-blue-950/20 px-1.5 py-0.5 rounded border border-blue-200 font-semibold">
                                                Bed: {{ visit.bed_number }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-muted-foreground font-bold flex items-center gap-1">
                                            <Calendar class="h-3.5 w-3.5" /> Checked-in: {{ new Date(visit.check_in_at).toLocaleString() }}
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs text-gray-700 dark:text-gray-300">
                                        <div>
                                            <span class="block uppercase text-[9px] font-bold text-muted-foreground tracking-wider">Chief Symptoms</span>
                                            <p class="mt-1.5 font-bold text-sm text-foreground bg-muted/20 p-2.5 rounded-lg border border-dashed">{{ visit.symptoms }}</p>
                                            
                                            <!-- Vitals statistics widget -->
                                            <div v-if="visit.medical_log && (visit.medical_log.temperature || visit.medical_log.blood_pressure)" class="mt-3 grid grid-cols-2 gap-1 text-[10px] font-bold">
                                                <span v-if="visit.medical_log.temperature" class="bg-muted px-1.5 py-0.5 rounded">Temp: <strong>{{ visit.medical_log.temperature }}°C</strong></span>
                                                <span v-if="visit.medical_log.blood_pressure" class="bg-muted px-1.5 py-0.5 rounded">BP: <strong>{{ visit.medical_log.blood_pressure }}</strong></span>
                                            </div>
                                        </div>
                                        
                                        <div class="md:col-span-2 space-y-3.5">
                                            <div v-if="visit.medical_log">
                                                <div>
                                                    <span class="block uppercase text-[9px] font-bold text-muted-foreground tracking-wider">Assessment / Findings</span>
                                                    <p class="mt-1 font-semibold text-gray-800 dark:text-gray-200 leading-relaxed">{{ visit.medical_log.findings }}</p>
                                                </div>
                                                <div class="mt-2.5">
                                                    <span class="block uppercase text-[9px] font-bold text-muted-foreground tracking-wider">Treatment Administered</span>
                                                    <p class="mt-1 font-semibold text-gray-805 dark:text-gray-205 leading-relaxed">{{ visit.medical_log.treatment_given }}</p>
                                                </div>
                                                
                                                <!-- Supplies administered internally -->
                                                <div v-if="visit.medical_log.medicines_dispensed && visit.medical_log.medicines_dispensed.length > 0" class="mt-3">
                                                    <span class="block uppercase text-[9px] font-bold text-muted-foreground tracking-wider mb-1">Medicines Dispensed (On-Site)</span>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <Badge v-for="med in visit.medical_log.medicines_dispensed" :key="med.name" variant="outline" class="px-1.5 py-0.2 text-[10px] font-bold">
                                                            {{ med.name }} (x{{ med.quantity }})
                                                        </Badge>
                                                    </div>
                                                </div>

                                                <!-- Recommended Laboratory Tests -->
                                                <div v-if="visit.medical_log.recommended_tests && visit.medical_log.recommended_tests.length > 0" class="mt-3 bg-blue-500/5 p-2 rounded-lg border border-blue-500/10">
                                                    <span class="block uppercase text-[9px] font-bold text-blue-600 tracking-wider mb-1 flex items-center gap-1">
                                                        <FileText class="h-3 w-3" /> Recommended Tests (To do outside)
                                                    </span>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <span v-for="test in visit.medical_log.recommended_tests" :key="test" class="bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 text-[10px] px-2 py-0.5 rounded font-bold border border-blue-200">
                                                            {{ test }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Recommended External Drugs -->
                                                <div v-if="visit.medical_log.external_prescriptions && visit.medical_log.external_prescriptions.length > 0" class="mt-3 bg-green-500/5 p-2 rounded-lg border border-green-500/10">
                                                    <span class="block uppercase text-[9px] font-bold text-green-600 tracking-wider mb-1 flex items-center gap-1">
                                                        <Link2 class="h-3 w-3" /> External Prescriptions (To buy outside)
                                                    </span>
                                                    <div class="space-y-1">
                                                        <div v-for="presc in visit.medical_log.external_prescriptions" :key="presc.name" class="text-[10px] text-gray-700 dark:text-gray-300">
                                                            💊 <strong>{{ presc.name }}</strong> — <span class="italic font-medium text-muted-foreground">{{ presc.dosage }}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div v-if="visit.medical_log.parent_contacted" class="mt-2.5 p-2.5 bg-green-55/5 border border-green-500/10 text-green-600 rounded text-[10px] font-medium leading-relaxed">
                                                    <strong>Next of Kin Contacted:</strong> {{ visit.medical_log.parent_contact_notes || 'Routine update.' }}
                                                </div>

                                                <div v-if="visit.status === 'referred' && visit.medical_log.referral_hospital" class="mt-2.5 p-2.5 bg-red-500/5 border border-red-500/10 text-red-650 rounded text-[10px] font-semibold leading-relaxed">
                                                    <strong>Hospital Referral:</strong> {{ visit.medical_log.referral_hospital }} - {{ visit.medical_log.referral_notes }}
                                                </div>
                                            </div>
                                            <div v-else class="text-orange-500 italic text-[11px] font-semibold">
                                                Visit registered, but no medical treatment logs were recorded.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 pt-3 border-t border-dashed flex justify-end">
                                        <a
                                            :href="`/admin/sickbay/visits/${visit.id}/prescription`"
                                            target="_blank"
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition"
                                        >
                                            <Printer class="h-3.5 w-3.5 mr-1" /> Print Recommendation Slip
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="text-center py-12 border-2 border-dashed rounded-xl text-gray-500 italic text-sm font-semibold">
                                No completed treatment logs found in the archives for this patient.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>
