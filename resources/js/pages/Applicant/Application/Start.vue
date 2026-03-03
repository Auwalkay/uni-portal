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
                    <CardTitle class="text-2xl font-serif">Application Pathway</CardTitle>
                    <CardDescription class="text-base text-muted-foreground/80">Please ensure the information selected below precisely matches your supporting documents.</CardDescription>
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
                                <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id" class="cursor-pointer focus:bg-primary/5 focus:text-primary transition-colors">
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
                        <Button size="lg" class="w-full md:w-auto font-semibold px-8 py-6 text-base tracking-wide shadow-lg hover:shadow-primary/30 transition-all duration-300 group" @click="proceedToForm" :disabled="!form.programme_id">
                            Continue to Form
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 w-5 h-5 transition-transform group-hover:translate-x-1"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </Button>
                    </CardFooter>
                </Transition>
            </Card>
            
            <div class="text-center mt-8">
               <p class="text-sm text-muted-foreground flex items-center justify-center gap-2">
                   <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-primary"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                   Ensure you have your O'Level and JAMB results ready before proceeding.
               </p>
            </div>
        </div>
        </div>
    </ApplicantLayout>
</template>
