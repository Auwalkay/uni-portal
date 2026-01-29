<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ApplicantLayout from '@/layouts/ApplicantLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { CheckCircle2 } from 'lucide-vue-next';

interface Programme { id: string; name: string; }
interface Department { id: string; name: string; programmes: Programme[]; }
interface Faculty { id: string; name: string; departments: Department[]; }

const props = defineProps<{
    faculties: Faculty[]
}>();

const form = useForm({
    mode: 'UTME',
    faculty_id: '',
    department_id: '',
    programme_id: '',
});

// Cascading Logic
const selectedFaculty = computed(() => props.faculties.find(f => f.id === form.faculty_id));
const departments = computed(() => selectedFaculty.value?.departments || []);

const selectedDepartment = computed(() => departments.value.find(d => d.id === form.department_id));
const programmes = computed(() => selectedDepartment.value?.programmes || []);

const proceedToForm = () => {
    router.visit(route('applicant.apply.form'), {
        data: {
            mode: form.mode,
            programme_id: form.programme_id
        }
    });
};
</script>

<template>
    <Head title="Start Application" />

    <ApplicantLayout>
        <div class="max-w-3xl mx-auto p-6 space-y-8">
            <div class="space-y-2 text-center">
                <h1 class="text-3xl font-serif font-bold text-primary">Begin Your Journey</h1>
                <p class="text-gray-500">Select your application mode and desired programme to get started.</p>
            </div>

            <Card class="border-t-4 border-t-primary shadow-lg">
                <CardHeader>
                    <CardTitle>Application Details</CardTitle>
                    <CardDescription>Please ensure the information selected below matches your JAMB documents.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    
                    <!-- Mode Selection -->
                    <div class="space-y-3">
                        <Label class="text-base font-medium">Application Mode</Label>
                        <RadioGroup v-model="form.mode" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <RadioGroupItem id="utme" value="UTME" class="peer sr-only" />
                                <Label for="utme" class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary cursor-pointer transition-all">
                                    <span class="text-xl font-bold mb-1">UTME</span>
                                    <span class="text-xs text-center text-muted-foreground">Unified Tertiary Matriculation</span>
                                </Label>
                            </div>
                            <div>
                                <RadioGroupItem id="de" value="DE" class="peer sr-only" />
                                <Label for="de" class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary cursor-pointer transition-all">
                                    <span class="text-xl font-bold mb-1">Direct Entry</span>
                                    <span class="text-xs text-center text-muted-foreground">Diploma / A-Level</span>
                                </Label>
                            </div>
                            <div>
                                <RadioGroupItem id="pg" value="PG" class="peer sr-only" />
                                <Label for="pg" class="flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-accent hover:text-accent-foreground peer-data-[state=checked]:border-primary [&:has([data-state=checked])]:border-primary cursor-pointer transition-all">
                                    <span class="text-xl font-bold mb-1">Postgraduate</span>
                                    <span class="text-xs text-center text-muted-foreground">Masters / PhD</span>
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>

                    <!-- Faculty Selection -->
                    <div class="space-y-2">
                        <Label>Faculty</Label>
                        <Select v-model="form.faculty_id" @update:modelValue="form.department_id = ''; form.programme_id = ''">
                            <SelectTrigger>
                                <SelectValue placeholder="Select Faculty" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id">
                                    {{ faculty.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Department Selection -->
                    <div class="space-y-2" v-if="form.faculty_id">
                        <Label>Department</Label>
                        <Select v-model="form.department_id" @update:modelValue="form.programme_id = ''">
                            <SelectTrigger>
                                <SelectValue placeholder="Select Department" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                     <!-- Programme Selection -->
                    <div class="space-y-2" v-if="form.department_id">
                        <Label>Programme of Study</Label>
                        <Select v-model="form.programme_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select Programme" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="prog in programmes" :key="prog.id" :value="prog.id">
                                    {{ prog.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                </CardContent>
                <CardFooter class="flex justify-end bg-gray-50 border-t p-6">
                    <Button size="lg" class="w-full md:w-auto font-serif" @click="proceedToForm" :disabled="!form.programme_id">
                        Proceed to Form Wizard
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </ApplicantLayout>
</template>
