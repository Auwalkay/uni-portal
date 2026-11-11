<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ApplicantLayout from '@/layouts/ApplicantLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { CheckCircle2 } from 'lucide-vue-next';

interface Programme {
    id: string;
    name: string;
    is_active?: boolean;
    academic_stream?: 'UG' | 'PG';
    type?: string;
}
interface Department {
    id: string;
    name: string;
    programmes: Programme[];
}
interface Faculty {
    id: string;
    name: string;
    departments: Department[];
}

const props = defineProps<{
    faculties: Faculty[];
    currentSession?: { id: string; name: string } | null;
}>();

const form = useForm({
    mode: 'UTME',
    faculty_id: '',
    department_id: '',
    programme_id: '',
});

const isPgProgramme = (p: Programme): boolean => {
    if (String(p.academic_stream || '').toUpperCase() === 'PG') return true;
    const type = String(p.type || '').toUpperCase();
    if (['PG', 'PGD', 'MBA', 'MIM', 'MSC', 'MPHIL', 'PHD', 'MASTERS', 'DOCTORATE'].includes(type)) return true;
    const name = String(p.name || '').trim();
    if (/^(M\.?Sc|Ph\.?D|PGD|MBA|MIM|M\.?Eng|M\.?Phil|Master|Doctor)\b/i.test(name)) return true;
    return false;
};

const isProgramMatchingMode = (p: Programme): boolean => {
    return form.mode === 'PG' ? isPgProgramme(p) : !isPgProgramme(p);
};

// Only show faculties that offer programmes for the selected application mode
const availableFaculties = computed(() => {
    return props.faculties.filter(faculty =>
        (faculty.departments || []).some(dept =>
            (dept.programmes || []).some(p => p.is_active !== false && isProgramMatchingMode(p))
        )
    );
});

// Cascading Logic: Selected Faculty
const selectedFaculty = computed(() => availableFaculties.value.find(f => f.id === form.faculty_id));

// Only show departments within the selected faculty that offer programmes for the selected mode
const departments = computed(() => {
    return (selectedFaculty.value?.departments || []).filter(dept =>
        (dept.programmes || []).some(p => p.is_active !== false && isProgramMatchingMode(p))
    );
});

// Cascading Logic: Selected Department
const selectedDepartment = computed(() => departments.value.find(d => d.id === form.department_id));

// STRICTLY return programmes matching the chosen stream - NO fallback to other streams
const programmes = computed(() => {
    const active = (selectedDepartment.value?.programmes || []).filter(p => p.is_active !== false);
    return active.filter(isProgramMatchingMode);
});

// Reset cascading selections when mode changes
watch(() => form.mode, () => {
    form.programme_id = '';
    if (!availableFaculties.value.some(f => f.id === form.faculty_id)) {
        form.faculty_id = '';
        form.department_id = '';
    } else if (!departments.value.some(d => d.id === form.department_id)) {
        form.department_id = '';
    }
});

const proceedToPayment = () => {
    form.post(route('applicant.apply.select_programme'));
};
</script>

<template>
    <Head title="Start Application" />

    <ApplicantLayout>
        <div class="relative min-h-[calc(100vh-200px)] flex flex-col items-center justify-center p-6 sm:p-10">
            <!-- Background Decoration -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none -z-10">
                <div class="absolute -top-1/2 -right-1/2 w-full h-full bg-gradient-to-b from-primary/5 to-transparent rounded-full blur-3xl opacity-50"></div>
                <div class="absolute -bottom-1/2 -left-1/2 w-full h-full bg-gradient-to-t from-secondary/5 to-transparent rounded-full blur-3xl opacity-50"></div>
            </div>

            <div class="w-full max-w-3xl space-y-10">
                <div class="space-y-3 text-center animate-fade-in-up">
                    <h1 class="text-4xl md:text-5xl font-serif font-bold text-primary tracking-tight">Begin Your Journey</h1>
                    <p class="text-lg text-muted-foreground max-w-xl mx-auto">Select your application mode and desired programme to initiate your admission process.</p>
                </div>

            <Card class="border-t-4 border-t-primary shadow-xl ring-1 ring-border/50 bg-card/95 backdrop-blur-sm animate-fade-in-up delay-100">
                <CardHeader class="pb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <CardTitle class="text-2xl font-serif">Application Pathway</CardTitle>
                            <CardDescription class="text-base text-muted-foreground/80 mt-1">Please ensure the information selected below precisely matches your supporting documents.</CardDescription>
                        </div>
                        <div v-if="currentSession" class="self-start sm:self-auto shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary/10 text-primary text-xs font-semibold">
                            <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                            <span>{{ currentSession.name }} Academic Session</span>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-8">
                    
                    <!-- Mode Selection -->
                    <div class="space-y-4">
                        <Label class="text-base font-semibold text-foreground">1. Application Mode</Label>
                        <RadioGroup v-model="form.mode" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <RadioGroupItem id="utme" value="UTME" class="peer sr-only" />
                                <Label for="utme" class="flex flex-col items-center justify-between rounded-xl border-2 border-border/50 bg-background p-5 hover:bg-muted/50 hover:border-primary/50 hover:text-foreground peer-data-[state=checked]:border-primary peer-data-[state=checked]:bg-primary/5 peer-data-[state=checked]:text-primary cursor-pointer transition-all duration-300">
                                    <span class="text-xl font-bold mb-2 tracking-tight">UTME</span>
                                    <span class="text-xs text-center font-medium opacity-80">Unified Tertiary Matriculation</span>
                                </Label>
                            </div>
                            <div>
                                <RadioGroupItem id="de" value="DE" class="peer sr-only" />
                                <Label for="de" class="flex flex-col items-center justify-between rounded-xl border-2 border-border/50 bg-background p-5 hover:bg-muted/50 hover:border-primary/50 hover:text-foreground peer-data-[state=checked]:border-primary peer-data-[state=checked]:bg-primary/5 peer-data-[state=checked]:text-primary cursor-pointer transition-all duration-300">
                                    <span class="text-xl font-bold mb-2 tracking-tight">Direct Entry</span>
                                    <span class="text-xs text-center font-medium opacity-80">Diploma / A-Level</span>
                                </Label>
                            </div>
                            <div>
                                <RadioGroupItem id="pg" value="PG" class="peer sr-only" />
                                <Label for="pg" class="flex flex-col items-center justify-between rounded-xl border-2 border-border/50 bg-background p-5 hover:bg-muted/50 hover:border-primary/50 hover:text-foreground peer-data-[state=checked]:border-primary peer-data-[state=checked]:bg-primary/5 peer-data-[state=checked]:text-primary cursor-pointer transition-all duration-300">
                                    <span class="text-xl font-bold mb-2 tracking-tight">Postgraduate</span>
                                    <span class="text-xs text-center font-medium opacity-80">Masters / PhD</span>
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>

                    <!-- Faculty Selection -->
                    <div class="space-y-3">
                        <Label class="text-base font-semibold text-foreground">2. Faculty</Label>
                        <Select v-model="form.faculty_id" @update:modelValue="form.department_id = ''; form.programme_id = ''">
                            <SelectTrigger class="h-12 bg-background border-border shadow-sm focus:ring-primary/20 transition-all font-medium">
                                <SelectValue placeholder="Select your target Faculty" />
                            </SelectTrigger>
                            <SelectContent class="border-border shadow-lg">
                                <SelectItem v-for="faculty in availableFaculties" :key="faculty.id" :value="faculty.id" class="cursor-pointer focus:bg-primary/5 focus:text-primary transition-colors">
                                    {{ faculty.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Department Selection -->
                    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-4">
                        <div class="space-y-3" v-if="form.faculty_id">
                            <Label class="text-base font-semibold text-foreground">3. Department</Label>
                            <Select v-model="form.department_id" @update:modelValue="form.programme_id = ''">
                                <SelectTrigger class="h-12 bg-background border-border shadow-sm focus:ring-primary/20 transition-all font-medium">
                                    <SelectValue placeholder="Select your target Department" />
                                </SelectTrigger>
                                <SelectContent class="border-border shadow-lg">
                                    <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id" class="cursor-pointer focus:bg-primary/5 focus:text-primary transition-colors">
                                        {{ dept.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </Transition>

                     <!-- Programme Selection -->
                    <Transition enter-active-class="transition-all duration-400 ease-out delay-75" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-4">
                        <div class="space-y-3" v-if="form.department_id">
                            <Label class="text-base font-semibold text-foreground">4. Programme of Study</Label>
                            <Select v-model="form.programme_id">
                                <SelectTrigger class="h-12 bg-background border-border shadow-sm focus:ring-primary/20 transition-all font-medium text-foreground">
                                    <SelectValue placeholder="Select your specific Programme" />
                                </SelectTrigger>
                                <SelectContent class="border-border shadow-lg">
                                    <SelectItem v-for="prog in programmes" :key="prog.id" :value="prog.id" class="cursor-pointer focus:bg-primary/5 focus:text-primary transition-colors">
                                        {{ prog.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="programmes.length === 0" class="text-xs text-amber-600 dark:text-amber-400 font-medium mt-1.5 flex items-center gap-1.5">
                                <span>⚠️</span> No {{ form.mode === 'PG' ? 'Postgraduate' : 'Undergraduate' }} programmes are currently available for this department.
                            </p>
                        </div>
                    </Transition>

                </CardContent>
                
                <Transition enter-active-class="transition-opacity duration-500 ease-in-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-300 ease-in-out" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <CardFooter v-if="form.programme_id" class="flex flex-col md:flex-row items-center justify-between bg-primary/5 border-t border-primary/20 p-6 md:p-8 rounded-b-xl gap-6">
                        <div class="flex items-center gap-4 text-left">
                            <div class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-full bg-primary/20 text-primary">
                                <CheckCircle2 class="w-6 h-6" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-foreground">Ready to proceed</p>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Applying for <strong>{{ programmes.find(p => p.id === form.programme_id)?.name }}</strong> via <strong>{{ form.mode }}</strong>.
                                </p>
                            </div>
                        </div>
                        <Button size="lg" class="w-full md:w-auto font-semibold px-8 py-6 text-base tracking-wide shadow-lg hover:shadow-primary/30 transition-all duration-300 group" @click="proceedToPayment" :disabled="!form.programme_id || form.processing">
                            Save & Proceed to Payment
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 w-5 h-5 transition-transform group-hover:translate-x-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </Button>
                    </CardFooter>
                </Transition>
            </Card>
            
            <div class="text-center mt-8">
               <p class="text-sm text-muted-foreground flex items-center justify-center gap-2">
                   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-primary"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                   Next: Complete application fee payment, then fill in your personal, academic, and document credentials.
               </p>
            </div>
        </div>
        </div>
    </ApplicantLayout>
</template>
