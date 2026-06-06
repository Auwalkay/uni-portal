<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Activity, Plus, X, Bed, BookOpen, Clock, Heart, Search, FileText
} from 'lucide-vue-next';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    beds: Array<{
        id: number;
        name: string;
        description: string | null;
        is_active: boolean;
    }>;
    activeVisits: Array<{
        id: number;
        status: string;
        check_in_at: string;
        symptoms: string;
        visit_type: string;
        bed_number: string | null;
        admitted_to_bed_at: string | null;
        patient: { 
            name: string; 
            student?: { matriculation_number: string } 
            staff?: { staff_number: string }
        };
    }>;
}>();

const showBedModal = ref(false);

const bedForm = useForm({
    name: '',
    description: '',
});

const submitBed = () => {
    bedForm.post('/admin/sickbay/beds', {
        onSuccess: () => {
            showBedModal.value = false;
            bedForm.reset();
        }
    });
};

const dischargeBed = (visitId: number) => {
    if (confirm('Are you sure you want to discharge this patient from the observation bed?')) {
        router.post(`/admin/sickbay/beds/${visitId}/discharge`);
    }
};

const getBedOccupant = (bedName: string) => {
    return props.activeVisits.find(v => v.bed_number === bedName && v.status === 'under_observation');
};

const activeQueueCount = ref(props.activeVisits.length);
</script>

<template>
    <Head title="Observation Beds Matrix" />

    <AdminLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4 border-b pb-5">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2.5">
                            <Bed class="h-8 w-8 text-indigo-650 dark:text-indigo-400" />
                            Observation Beds Matrix
                        </h1>
                        <p class="text-sm text-gray-550 dark:text-gray-400 mt-1">
                            Monitor and manage dynamic sickbay observation beds, placements, and recovery statuses.
                        </p>
                    </div>
                    <div>
                        <button
                            @click="showBedModal = true"
                            class="inline-flex items-center px-4 py-2.5 border border-transparent rounded-xl shadow-md text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition"
                        >
                            <Plus class="h-4.5 w-4.5 mr-2" />
                            Add Observation Bed
                        </button>
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
                            <span v-if="activeVisits.filter(v => v.status === 'waiting').length > 0" class="ml-2 bg-orange-100 dark:bg-orange-950 text-orange-850 dark:text-orange-300 py-0.5 px-2 rounded-full text-xs font-extrabold">
                                {{ activeVisits.filter(v => v.status === 'waiting').length }}
                            </span>
                        </Link>
                        <Link
                            href="/admin/sickbay/beds"
                            class="border-indigo-500 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2"
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

                <!-- Beds Grid -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="bed in beds" :key="bed.id" class="overflow-hidden border shadow-md relative bg-card flex flex-col justify-between">
                        <div class="h-1.5 w-full" :class="getBedOccupant(bed.name) ? 'bg-blue-500' : 'bg-green-500'"></div>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-lg flex items-center justify-between">
                                <span>{{ bed.name }}</span>
                                <Badge :variant="getBedOccupant(bed.name) ? 'destructive' : 'secondary'">
                                    {{ getBedOccupant(bed.name) ? 'OCCUPIED' : 'OPEN' }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-0 flex-1">
                            <div v-if="getBedOccupant(bed.name)" class="space-y-2 mt-2">
                                <div class="font-bold text-sm">{{ getBedOccupant(bed.name)?.patient.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ getBedOccupant(bed.name)?.patient.student?.matriculation_number ? 'Matric: ' + getBedOccupant(bed.name)?.patient.student.matriculation_number : (getBedOccupant(bed.name)?.patient.staff?.staff_number ? 'Staff ID: ' + getBedOccupant(bed.name)?.patient.staff.staff_number : 'Staff') }}
                                </div>
                                <div class="text-xs text-muted-foreground bg-muted p-2 rounded italic">
                                    "{{ getBedOccupant(bed.name)?.symptoms }}"
                                </div>
                                <div class="text-[10px] text-muted-foreground flex items-center gap-1 pt-2">
                                    <Clock class="h-3.5 w-3.5" /> Admitted at: {{ new Date(getBedOccupant(bed.name)?.admitted_to_bed_at!).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                </div>
                            </div>
                            <div v-else class="text-muted-foreground text-xs py-8 text-center flex flex-col items-center justify-center">
                                <Bed class="h-8 w-8 text-muted-foreground/30 mb-2" />
                                <span>Ready for placement</span>
                                <span v-if="bed.description" class="text-[10px] text-muted-foreground/60 mt-1 block">({{ bed.description }})</span>
                            </div>
                        </CardContent>
                        <CardFooter v-if="getBedOccupant(bed.name)" class="border-t pt-3 p-4 bg-muted/10">
                            <Button @click="dischargeBed(getBedOccupant(bed.name)!.id)" variant="outline" size="sm" class="w-full text-xs font-semibold">
                                <X class="h-3.5 w-3.5 mr-1" /> Discharge Patient
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
                
                <div v-if="beds.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-dashed text-gray-500">
                    <Bed class="h-10 w-10 mx-auto mb-2 text-gray-400" />
                    No observation beds registered yet.
                </div>
            </div>
        </div>

        <!-- Add Bed Modal -->
        <div v-if="showBedModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b dark:border-gray-750 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Add New Sickbay Bed</h3>
                    <button @click="showBedModal = false" class="text-gray-400 hover:text-gray-600"><X class="h-6 w-6" /></button>
                </div>
                <form @submit.prevent="submitBed" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Bed Name / Number</label>
                        <input v-model="bedForm.name" type="text" required placeholder="e.g. Bed 5, Recovery Bed A" class="w-full px-3 py-2 border rounded-lg bg-background text-sm" />
                        <InputError :message="bedForm.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-750 dark:text-gray-300 mb-1">Description (Optional)</label>
                        <textarea v-model="bedForm.description" rows="2" placeholder="e.g. Near window, extra soft mattress..." class="w-full px-3 py-2 border rounded-lg bg-background text-sm"></textarea>
                        <InputError :message="bedForm.errors.description" class="mt-1" />
                    </div>
                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t dark:border-gray-750">
                        <button type="button" @click="showBedModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="bedForm.processing" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save Bed</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
