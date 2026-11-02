<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CheckCircle2, AlertTriangle, ShieldCheck, GraduationCap } from 'lucide-vue-next';

const props = defineProps<{
    referee: {
        id: string;
        name: string;
        email: string;
        phone?: string | null;
        institution_or_organization: string;
        position_or_rank?: string | null;
        relationship?: string | null;
        status: string;
        is_submitted: boolean;
        is_expired: boolean;
        submitted_at?: string | null;
        token: string;
    };
    applicant: {
        full_name: string;
        programme_name: string;
        department_name?: string | null;
        faculty_name?: string | null;
        previous_qualification?: string | null;
        previous_institution?: string | null;
        research_proposal_title?: string | null;
    };
    recommendationStrengths: Record<string, string>;
}>();

const form = useForm({
    position_or_rank: props.referee.position_or_rank || '',
    relationship: props.referee.relationship || '',
    academic_capacity_rating: '4',
    research_potential_rating: '4',
    character_integrity_rating: '5',
    communication_rating: '4',
    recommendation_strength: 'strongly_recommend',
    comments: '',
});

const submitRecommendation = () => {
    form.post(route('referee.recommendation.store', props.referee.token), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Postgraduate Referee Recommendation" />

    <div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header Banner -->
            <div class="text-center space-y-2">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-purple-100 text-purple-800 text-xs font-semibold uppercase tracking-wider">
                    <GraduationCap class="w-4 h-4" />
                    School of Postgraduate Studies (SPGS)
                </div>
                <h1 class="text-3xl font-serif font-bold text-slate-900">Confidential Referee Assessment</h1>
                <p class="text-sm text-slate-600 max-w-xl mx-auto">
                    You have been nominated as an academic/professional referee for a Postgraduate admission candidate.
                </p>
            </div>

            <!-- Applicant Summary Card -->
            <Card class="border-t-4 border-t-purple-700 shadow-sm">
                <CardHeader class="pb-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <CardTitle class="text-xl">{{ applicant.full_name }}</CardTitle>
                            <CardDescription>Candidate for {{ applicant.programme_name }}</CardDescription>
                        </div>
                        <Badge variant="outline" class="self-start sm:self-auto bg-purple-50 text-purple-800 border-purple-200">
                            Confidential Reference
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm border-t pt-4 bg-slate-50/60">
                    <div>
                        <span class="text-xs uppercase text-muted-foreground font-semibold block">Target Programme</span>
                        <span class="font-medium">{{ applicant.programme_name }}</span>
                    </div>
                    <div v-if="applicant.department_name">
                        <span class="text-xs uppercase text-muted-foreground font-semibold block">Department / Faculty</span>
                        <span class="font-medium">{{ applicant.department_name }} ({{ applicant.faculty_name }})</span>
                    </div>
                    <div v-if="applicant.previous_qualification">
                        <span class="text-xs uppercase text-muted-foreground font-semibold block">Previous Qualification</span>
                        <span class="font-medium">{{ applicant.previous_qualification }} — {{ applicant.previous_institution }}</span>
                    </div>
                    <div v-if="applicant.research_proposal_title" class="sm:col-span-2">
                        <span class="text-xs uppercase text-muted-foreground font-semibold block">Proposed Research / Capstone Area</span>
                        <span class="font-medium italic">{{ applicant.research_proposal_title }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Already Submitted State -->
            <Card v-if="referee.is_submitted" class="border-green-200 bg-green-50/50 shadow-sm">
                <CardContent class="p-8 text-center space-y-3">
                    <CheckCircle2 class="w-12 h-12 text-green-600 mx-auto" />
                    <h2 class="text-2xl font-bold text-green-900">Recommendation Submitted</h2>
                    <p class="text-sm text-green-800 max-w-md mx-auto">
                        Thank you, <strong>{{ referee.name }}</strong>. Your confidential evaluation for <strong>{{ applicant.full_name }}</strong> has been recorded by the School of Postgraduate Studies.
                    </p>
                </CardContent>
            </Card>

            <!-- Expired State -->
            <Card v-else-if="referee.is_expired" class="border-amber-200 bg-amber-50/50 shadow-sm">
                <CardContent class="p-8 text-center space-y-3">
                    <AlertTriangle class="w-12 h-12 text-amber-600 mx-auto" />
                    <h2 class="text-2xl font-bold text-amber-900">Recommendation Link Expired</h2>
                    <p class="text-sm text-amber-800 max-w-md mx-auto">
                        This referee evaluation link has expired. Please contact the applicant or the School of Postgraduate Studies if an extension is required.
                    </p>
                </CardContent>
            </Card>

            <!-- Evaluation Form -->
            <Card v-else class="shadow-md">
                <form @submit.prevent="submitRecommendation">
                    <CardHeader>
                        <CardTitle class="text-lg flex items-center gap-2">
                            <ShieldCheck class="w-5 h-5 text-purple-700" />
                            Referee Evaluation Form
                        </CardTitle>
                        <CardDescription>
                            Completing as <strong>{{ referee.name }}</strong> ({{ referee.institution_or_organization }} — {{ referee.email }})
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label>Your Rank / Position</Label>
                                <Input v-model="form.position_or_rank" placeholder="e.g., Professor, Senior Lecturer, Director" />
                            </div>
                            <div class="space-y-2">
                                <Label>Relationship to Applicant</Label>
                                <Input v-model="form.relationship" placeholder="e.g., Undergraduate Project Supervisor, HOD, Employer" />
                            </div>
                        </div>

                        <div class="border-t pt-4 space-y-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-700">Candidate Ratings (1 = Poor, 5 = Exceptional)</h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label>Academic Capacity & Analytical Rigor *</Label>
                                    <Select v-model="form.academic_capacity_rating">
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="5">5 — Exceptional (Top 5%)</SelectItem>
                                            <SelectItem value="4">4 — Very Good (Top 15%)</SelectItem>
                                            <SelectItem value="3">3 — Good / Average</SelectItem>
                                            <SelectItem value="2">2 — Below Average</SelectItem>
                                            <SelectItem value="1">1 — Poor</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2">
                                    <Label>Independent Research / Project Potential *</Label>
                                    <Select v-model="form.research_potential_rating">
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="5">5 — Exceptional</SelectItem>
                                            <SelectItem value="4">4 — Very Good</SelectItem>
                                            <SelectItem value="3">3 — Satisfactory</SelectItem>
                                            <SelectItem value="2">2 — Needs Supervision</SelectItem>
                                            <SelectItem value="1">1 — Unprepared</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2">
                                    <Label>Character, Ethics & Integrity *</Label>
                                    <Select v-model="form.character_integrity_rating">
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="5">5 — Exemplary Integrity</SelectItem>
                                            <SelectItem value="4">4 — High Integrity</SelectItem>
                                            <SelectItem value="3">3 — Satisfactory</SelectItem>
                                            <SelectItem value="2">2 — Questionable</SelectItem>
                                            <SelectItem value="1">1 — Unsatisfactory</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2">
                                    <Label>Written & Oral Expression in English *</Label>
                                    <Select v-model="form.communication_rating">
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="5">5 — Articulate & Fluent</SelectItem>
                                            <SelectItem value="4">4 — Very Good</SelectItem>
                                            <SelectItem value="3">3 — Adequate</SelectItem>
                                            <SelectItem value="2">2 — Limited</SelectItem>
                                            <SelectItem value="1">1 — Poor</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Overall Recommendation Verdict *</Label>
                            <Select v-model="form.recommendation_strength">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="(label, key) in recommendationStrengths"
                                        :key="key"
                                        :value="key"
                                    >
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.recommendation_strength" class="text-xs text-destructive">{{ form.errors.recommendation_strength }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Confidential Assessment & Remarks *</Label>
                            <textarea
                                v-model="form.comments"
                                rows="5"
                                required
                                placeholder="Please provide your candid assessment of the applicant's intellectual readiness, character, and suitability for postgraduate study..."
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-600"
                            ></textarea>
                            <p v-if="form.errors.comments" class="text-xs text-destructive">{{ form.errors.comments }}</p>
                        </div>
                    </CardContent>

                    <CardFooter class="flex justify-end border-t bg-slate-50 px-6 py-4">
                        <Button type="submit" :disabled="form.processing" class="bg-purple-700 hover:bg-purple-800 text-white px-8">
                            {{ form.processing ? 'Submitting...' : 'Submit Confidential Recommendation' }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </div>
</template>

