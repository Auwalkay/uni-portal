<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { 
    Activity, Heart, PhoneCall, Calendar, Info, 
    Clock, ShieldAlert, BadgeAlert, Plus, HelpCircle, 
    User, CheckCircle2, ChevronRight, ActivitySquare, AlertCircle
} from 'lucide-vue-next';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import Pagination from '@/components/Pagination.vue';

const props = defineProps<{
    visits: {
        data: Array<{
            id: number;
            status: string;
            check_in_at: string;
            check_out_at: string | null;
            symptoms: string;
            visit_type: string;
            bed_number: string | null;
            admitted_to_bed_at: string | null;
            attendant: { name: string } | null;
            medical_log: {
                blood_pressure: string | null;
                temperature: number | null;
                weight: number | null;
                findings: string;
                treatment_given: string;
                medicines_dispensed: Array<{ name: string; quantity: number }> | null;
                parent_contacted: boolean;
                parent_contacted_at: string | null;
                parent_contact_notes: string | null;
                referral_hospital: string | null;
                referral_notes: string | null;
            } | null;
        }>;
        links: any;
        total: number;
    };
    student: {
        id: string;
        matriculation_number: string;
        phone_number: string;
        address: string;
        next_of_kin_name: string;
        next_of_kin_phone: string;
        next_of_kin_relationship: string;
        next_of_kin_address: string | null;
        academic_department?: {
            name: string;
            faculty?: { name: string }
        }
    } | null;
}>();

const currentTab = ref('timeline');
</script>

<template>
    <StudentLayout :breadcrumbs="[{ title: 'Sickbay Logs', href: '/student/sickbay' }]">
        <Head title="Sickbay Portal" />

        <div class="flex flex-col min-h-[calc(100vh-4rem)] bg-background/50">
            <!-- Hero Header Section -->
            <div class="relative overflow-hidden border-b bg-background px-6 py-12 md:px-12 lg:py-16">
                <!-- Background Pattern -->
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(45%_45%_at_50%_50%,var(--primary-muted),transparent)] opacity-20"></div>
                <div class="absolute inset-0 -z-10 bg-[grid-line_1px_1px_rgba(0,0,0,0.05)] [mask-image:radial-gradient(ellipse_at_center,black,transparent)]"></div>

                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between max-w-[1600px] mx-auto">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold border-red-500 text-red-500 bg-red-50 dark:bg-red-950/20">
                                <Heart class="h-3 w-3 mr-1 fill-red-500 text-red-500" /> Clinic & Sickbay
                            </Badge>
                        </div>
                        <h1 class="text-4xl font-extrabold tracking-tight lg:text-5xl">Student Sickbay Hub</h1>
                        <p class="text-lg text-muted-foreground max-w-2xl">
                            Track walk-in medical logs, treatment sheets, and manage emergency contact information.
                        </p>
                    </div>

                    <!-- Tabs Trigger -->
                    <div class="flex items-center gap-3">
                        <div class="flex bg-muted p-1 rounded-lg">
                            <button 
                                @click="currentTab = 'timeline'"
                                :class="[
                                    'px-4 py-1.5 text-xs font-bold uppercase rounded-md transition-all flex items-center gap-1.5',
                                    currentTab === 'timeline' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:text-foreground'
                                ]"
                            >
                                <Activity class="h-3.5 w-3.5" /> Visits & Treatments
                                <span v-if="visits.data.length > 0" class="ml-1 bg-red-500/10 text-red-550 px-1.5 py-0.2 rounded text-[10px]">
                                    {{ visits.total }}
                                </span>
                            </button>
                            <button 
                                @click="currentTab = 'emergency'"
                                :class="[
                                    'px-4 py-1.5 text-xs font-bold uppercase rounded-md transition-all flex items-center gap-1.5',
                                    currentTab === 'emergency' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:text-foreground'
                                ]"
                            >
                                <PhoneCall class="h-3.5 w-3.5" /> Next of Kin
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <main class="flex-1 w-full p-6 md:p-12 max-w-[1600px] mx-auto">
                
                <!-- Timeline & Visits Tab Content -->
                <div v-if="currentTab === 'timeline'" class="space-y-6">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-2xl font-bold flex items-center gap-3">
                            <ActivitySquare class="h-6 w-6 text-red-500" />
                            Medical Logs & Visits
                        </h2>
                        <span class="text-xs text-muted-foreground font-semibold italic">Listing walk-in treatments, bed rest, and pharmacy dispensaries</span>
                    </div>

                    <div v-if="visits.data.length > 0" class="space-y-6">
                        <Card v-for="visit in visits.data" :key="visit.id" class="border-0 shadow-lg ring-1 ring-border bg-card p-6 overflow-hidden relative">
                            <div :class="['absolute left-0 top-0 bottom-0 w-1.5', visit.status === 'waiting' ? 'bg-orange-500' : (visit.status === 'under_observation' ? 'bg-blue-500' : (visit.status === 'referred' ? 'bg-red-600' : 'bg-green-500'))]"></div>

                            <div class="pl-2">
                                <!-- Top Row: Category, Date, Status badges -->
                                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-dashed pb-4 mb-4">
                                    <div class="flex items-center gap-2.5">
                                        <Badge :variant="visit.visit_type === 'emergency' ? 'destructive' : 'secondary'" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                                            {{ visit.visit_type }}
                                        </Badge>
                                        <Badge :variant="visit.status === 'waiting' ? 'outline' : (visit.status === 'under_observation' ? 'secondary' : (visit.status === 'referred' ? 'destructive' : 'default'))" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                                            {{ visit.status }}
                                        </Badge>
                                        <span v-if="visit.bed_number" class="inline-flex items-center text-xs font-semibold text-blue-600 bg-blue-50 dark:bg-blue-950/20 px-2 py-0.5 rounded border border-blue-200">
                                            Bed: {{ visit.bed_number }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-muted-foreground flex items-center gap-3">
                                        <span class="flex items-center gap-1"><Clock class="h-3.5 w-3.5" /> Checked in: {{ new Date(visit.check_in_at).toLocaleString() }}</span>
                                        <span v-if="visit.check_out_at" class="flex items-center gap-1"><CheckCircle2 class="h-3.5 w-3.5 text-green-500" /> Discharged: {{ new Date(visit.check_out_at).toLocaleString() }}</span>
                                    </div>
                                </div>

                                <!-- Visit Details Body -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    
                                    <!-- Left Section: Symptoms and Vitals -->
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">Chief Complaint / Symptoms</p>
                                            <p class="text-sm font-semibold text-foreground mt-1 bg-muted/40 p-3 rounded-lg border">
                                                {{ visit.symptoms }}
                                            </p>
                                        </div>

                                        <div v-if="visit.medical_log && (visit.medical_log.temperature || visit.medical_log.blood_pressure || visit.medical_log.weight)">
                                            <p class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider mb-2">Recorded Vitals</p>
                                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                                <div v-if="visit.medical_log.temperature" class="p-2 bg-muted rounded-lg border">
                                                    <span class="text-muted-foreground block text-[9px] uppercase font-bold">Temp</span>
                                                    <span class="font-bold text-foreground text-sm">{{ visit.medical_log.temperature }}°C</span>
                                                </div>
                                                <div v-if="visit.medical_log.blood_pressure" class="p-2 bg-muted rounded-lg border">
                                                    <span class="text-muted-foreground block text-[9px] uppercase font-bold">BP</span>
                                                    <span class="font-bold text-foreground text-sm">{{ visit.medical_log.blood_pressure }}</span>
                                                </div>
                                                <div v-if="visit.medical_log.weight" class="p-2 bg-muted rounded-lg border">
                                                    <span class="text-muted-foreground block text-[9px] uppercase font-bold">Weight</span>
                                                    <span class="font-bold text-foreground text-sm">{{ visit.medical_log.weight }}kg</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Middle Section: Findings and Treatment -->
                                    <div class="space-y-4 md:col-span-2">
                                        <div v-if="visit.medical_log">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">Clinical Assessment & Diagnosis</p>
                                                    <p class="text-sm text-foreground mt-1.5 font-medium leading-relaxed">
                                                        {{ visit.medical_log.findings || 'No assessment recorded.' }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">Treatment Administered</p>
                                                    <p class="text-sm text-foreground mt-1.5 font-medium leading-relaxed">
                                                        {{ visit.medical_log.treatment_given || 'No treatment recorded.' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Medicines / First aid items Dispensed -->
                                            <div v-if="visit.medical_log.medicines_dispensed && visit.medical_log.medicines_dispensed.length > 0" class="mt-4 pt-3 border-t border-dashed">
                                                <p class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider mb-2">Dispensed Supplies</p>
                                                <div class="flex flex-wrap gap-2">
                                                    <Badge v-for="med in visit.medical_log.medicines_dispensed" :key="med.name" variant="outline" class="px-2 py-0.5 bg-red-500/5 text-foreground border border-red-500/20 text-xs font-semibold">
                                                        {{ med.name }} <span class="ml-1 text-red-500 font-bold">x{{ med.quantity }}</span>
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="flex items-center gap-2 p-3 bg-orange-500/5 border border-orange-500/25 rounded-xl text-orange-600">
                                            <Clock class="h-5 w-5 animate-pulse" />
                                            <span class="text-xs font-semibold">Awaiting consultation and treatment log by sickbay staff.</span>
                                        </div>
                                    </div>

                                </div>

                                <!-- Bottom Row: Nurse attending, Referral details, Parent contacted notifications -->
                                <div v-if="visit.medical_log" class="mt-6 pt-4 border-t border-dashed grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <User class="h-4 w-4 text-primary" />
                                        <span>Attended by: <strong class="text-foreground font-semibold">{{ visit.attendant?.name || 'Duty Nurse' }}</strong></span>
                                    </div>

                                    <!-- Next of Kin notification card status -->
                                    <div class="flex flex-col justify-center">
                                        <div v-if="visit.medical_log.parent_contacted" class="flex items-start gap-1.5 text-green-600 bg-green-500/5 border border-green-500/10 p-2.5 rounded-lg">
                                            <CheckCircle2 class="h-4 w-4 text-green-600 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <span class="font-bold block">Next of Kin Contacted</span>
                                                <span v-if="visit.medical_log.parent_contact_notes" class="text-[10px] text-muted-foreground block mt-0.5 italic">
                                                    "{{ visit.medical_log.parent_contact_notes }}"
                                                </span>
                                            </div>
                                        </div>
                                        <div v-else class="text-muted-foreground p-2.5 bg-muted/40 rounded-lg border border-dashed flex items-center gap-1.5">
                                            <Info class="h-4 w-4 text-muted-foreground" />
                                            <span>Next of Kin not contacted (Routine Case)</span>
                                        </div>
                                    </div>

                                    <!-- Referral hospital status -->
                                    <div v-if="visit.status === 'referred' && visit.medical_log.referral_hospital" class="flex flex-col justify-center">
                                        <div class="flex items-start gap-1.5 text-red-600 bg-red-500/5 border border-red-500/10 p-2.5 rounded-lg">
                                            <ShieldAlert class="h-4 w-4 text-red-600 mt-0.5 flex-shrink-0" />
                                            <div>
                                                <span class="font-bold block">Hospital Referral Issued</span>
                                                <span class="text-[11px] font-semibold text-foreground mt-0.5 block">Dest: {{ visit.medical_log.referral_hospital }}</span>
                                                <span v-if="visit.medical_log.referral_notes" class="text-[10px] text-muted-foreground block mt-0.5">
                                                    Notes: {{ visit.medical_log.referral_notes }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card>
                        <Pagination :links="visits.links" />
                    </div>

                    <div v-else class="flex flex-col items-center justify-center p-20 rounded-[2rem] border-2 border-dashed border-border py-32 bg-card">
                        <div class="p-5 bg-muted rounded-full mb-4">
                            <Activity class="h-10 w-10 text-muted-foreground/30" />
                        </div>
                        <h3 class="text-xl font-bold text-muted-foreground">No Sickbay Visits</h3>
                        <p class="text-sm text-muted-foreground/60 max-w-sm text-center mt-1">You have no recorded walk-in clinic visits or treatments logged.</p>
                    </div>
                </div>

                <!-- Next of Kin Card Tab Content -->
                <div v-if="currentTab === 'emergency'" class="max-w-2xl mx-auto space-y-6">
                    <Card class="border shadow-lg bg-card overflow-hidden">
                        <div class="h-2 w-full bg-red-500"></div>
                        <CardHeader>
                            <CardTitle class="text-xl flex items-center gap-2">
                                <PhoneCall class="h-5 w-5 text-red-500" />
                                Next of Kin Information
                            </CardTitle>
                            <CardDescription>
                                This data is used by sickbay nursing staff to contact your next of kin in cases of medical emergencies.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="space-y-1">
                                    <span class="text-muted-foreground text-xs font-semibold uppercase tracking-wider block">Full Name</span>
                                    <span class="font-bold text-foreground block text-base">{{ student?.next_of_kin_name || 'Not Provided' }}</span>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-muted-foreground text-xs font-semibold uppercase tracking-wider block">Phone Number</span>
                                    <span class="font-bold text-foreground block text-base">{{ student?.next_of_kin_phone || 'Not Provided' }}</span>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-muted-foreground text-xs font-semibold uppercase tracking-wider block">Relationship</span>
                                    <Badge variant="outline" class="font-bold text-xs">{{ student?.next_of_kin_relationship || 'Not Provided' }}</Badge>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-muted-foreground text-xs font-semibold uppercase tracking-wider block">Student Matric Number</span>
                                    <span class="font-mono font-bold text-foreground block text-sm">{{ student?.matriculation_number || 'N/A' }}</span>
                                </div>
                            </div>

                            <div v-if="student?.next_of_kin_address" class="space-y-1 border-t pt-4">
                                <span class="text-muted-foreground text-xs font-semibold uppercase tracking-wider block">Home Address</span>
                                <span class="text-foreground text-sm font-semibold">{{ student.next_of_kin_address }}</span>
                            </div>

                            <div v-if="student?.academic_department" class="space-y-1 border-t pt-4">
                                <span class="text-muted-foreground text-xs font-semibold uppercase tracking-wider block">Academic Department</span>
                                <span class="text-foreground text-sm font-semibold">
                                    {{ student.academic_department.name }}
                                    <span v-if="student.academic_department.faculty" class="text-muted-foreground">({{ student.academic_department.faculty.name }})</span>
                                </span>
                            </div>

                            <!-- Alert Box -->
                            <div class="p-4 bg-orange-50 dark:bg-orange-950/20 border border-orange-200 dark:border-orange-900/30 rounded-xl flex gap-3 text-sm">
                                <AlertCircle class="h-5 w-5 text-orange-600 mt-0.5 flex-shrink-0" />
                                <div>
                                    <span class="font-bold text-orange-850 dark:text-orange-355">Keep this profile up to date!</span>
                                    <p class="text-muted-foreground text-xs mt-1 leading-relaxed">
                                        If these contact details are incorrect or have changed, please update them immediately in your profile settings or visit the Academic Affairs registry.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

            </main>
        </div>
    </StudentLayout>
</template>
