<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardFooter, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Label } from '@/components/ui/label';
import { format } from 'date-fns';
import { FileText, Download } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    applicant: {
        id: string;
        jamb_registration_number: string;
        status: string;
        created_at: string;
        application_mode: string;
        application_number?: string;
        program_choice_1?: string;
        scholarship_id?: string;
        admitted_level?: string;
        admitted_programme_id?: string;
        
        // New Fields
        first_name?: string;
        last_name?: string;
        phone?: string;
        address?: string;
        dob?: string;
        gender?: string;
        jamb_score?: string;
        previous_institution?: string;
        previous_qualification?: string;
        previous_course_of_study?: string;
        previous_class_of_degree?: string;
        previous_cgpa?: number | string | null;
        graduation_year?: number | string | null;
        nysc_status?: string | null;
        research_proposal_title?: string | null;
        
        // Relationships
        user: { name: string; email: string; avatar_url?: string };
        state?: { id: number; name: string };
        lga?: { id: number; name: string };
        programme?: { 
            name: string; 
            academic_stream?: string;
            department?: { 
                faculty?: { name: string } 
            } 
        };
        
        // NOK
        next_of_kin_name?: string;
        next_of_kin_phone?: string;
        next_of_kin_relationship?: string;
        
        documents: Array<{
            id: string;
            type: string;
            original_name: string;
            path: string;
        }>;
        referees?: Array<{
            id: string;
            name: string;
            email: string;
            phone?: string | null;
            institution_or_organization: string;
            position_or_rank?: string | null;
            relationship?: string | null;
            status: string;
            token: string;
            academic_capacity_rating?: number | null;
            research_potential_rating?: number | null;
            character_integrity_rating?: number | null;
            communication_rating?: number | null;
            recommendation_strength?: string | null;
            comments?: string | null;
            submitted_at?: string | null;
        }>;
    };
    pg_eligibility?: {
        eligible: boolean;
        tier: string;
        default_level: number;
        message: string;
    } | null;
    payment_info?: {
        amount: number;
        status: string;
        reference: string;
    };
    programmes: Array<{ id: string; name: string }>;
    scholarships: Array<{ id: string; name: string }>;
    user_permissions?: {
        can_view_ug?: boolean;
        can_view_pg?: boolean;
        can_review?: boolean;
        can_admit?: boolean;
    };
}>();

const breadcrumbs = [
    { title: 'Applicants', href: '/admin/admissions' },
    { title: 'Undergraduate', href: route().has('admin.admissions.ug.index') ? route('admin.admissions.ug.index') : '/admin/admissions/ug' },
    { title: props.applicant.user.name, href: '#' },
];

const form = useForm({
    status: props.applicant.status,
    admitted_level: props.applicant.admitted_level ?? '',
    admitted_programme_id: props.applicant.admitted_programme_id ?? props.applicant.program_choice_1 ?? '',
    scholarship_id: props.applicant.scholarship_id ?? '',
});

const isAdmitting = computed(() => form.status === 'admitted');

const updateStatus = () => {
    const targetRoute = route().has('admin.admissions.ug.update')
        ? route('admin.admissions.ug.update', props.applicant.id)
        : route('admin.admissions.update', props.applicant.id);

    form.put(targetRoute, {
        onSuccess: () => {
            // Toast success
        }
    });
};

const passportUrl = computed(() => {
    const doc = props.applicant.documents.find(d => d.type === 'passport_photo');
    return doc ? `/storage/${doc.path}` : null;
});
</script>

<template>
    <Head :title="`Application: ${applicant.user.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Actions & Key Status -->
            <div class="space-y-6">
                <!-- User Profile Card -->
                <Card class="text-center overflow-hidden">
                    <div class="bg-primary/10 h-24"></div>
                    <div class="px-6 pb-6 -mt-12">
                        <div class="relative w-32 h-32 mx-auto rounded-full border-4 border-background bg-muted flex items-center justify-center overflow-hidden">
                             <img 
                                v-if="passportUrl" 
                                :src="passportUrl" 
                                class="object-cover w-full h-full"
                                alt="Applicant Photo" 
                            />
                            <span v-else-if="!applicant.user.avatar_url" class="text-4xl">👤</span>
                             <img v-else :src="applicant.user.avatar_url" class="object-cover w-full h-full" />
                        </div>
                        <h2 class="mt-4 text-xl font-bold">{{ applicant.user.name }}</h2>
                        <p class="text-muted-foreground text-sm">{{ applicant.user.email }}</p>
                        
                        <div class="mt-4 flex justify-center">
                            <Badge variant="outline" class="uppercase tracking-wider px-3 py-1" :class="{
                                'bg-yellow-100 text-yellow-800 border-yellow-200': applicant.status === 'draft',
                                'bg-blue-100 text-blue-800 border-blue-200': applicant.status === 'submitted',
                                'bg-purple-100 text-purple-800 border-purple-200': applicant.status === 'screening',
                                'bg-green-100 text-green-800 border-green-200': applicant.status === 'admitted',
                                'bg-red-100 text-red-800 border-red-200': applicant.status === 'rejected',
                            }">
                                {{ applicant.status }}
                            </Badge>
                        </div>
                    </div>
                </Card>

                <!-- Action Panel -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Actions</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-3">
                        <Select v-model="form.status" :disabled="applicant.status === 'admitted' || (user_permissions && !user_permissions.can_review)">
                            <SelectTrigger :disabled="applicant.status === 'admitted' || (user_permissions && !user_permissions.can_review)">
                                <SelectValue placeholder="Change Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft (Incomplete)</SelectItem>
                                <SelectItem value="submitted">Submitted</SelectItem>
                                <SelectItem value="screening">Under Screening</SelectItem>
                                <SelectItem value="admitted" :disabled="user_permissions && !user_permissions.can_admit">
                                    Admit Student {{ user_permissions && !user_permissions.can_admit ? '(Requires admit_students)' : '' }}
                                </SelectItem>
                                <SelectItem value="rejected">Reject Application</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Admission Details — shown only when admitting -->
                        <template v-if="isAdmitting && applicant.status !== 'admitted'">
                            <Separator />
                            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Admission Details</p>

                            <!-- Level -->
                            <div class="flex flex-col gap-1.5">
                                <Label>Level Admitted <span class="text-destructive">*</span></Label>
                                <Select v-model="form.admitted_level">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="100">100 Level</SelectItem>
                                        <SelectItem value="200">200 Level</SelectItem>
                                        <SelectItem value="300">300 Level</SelectItem>
                                        <SelectItem value="400">400 Level</SelectItem>
                                        <SelectItem value="500">500 Level</SelectItem>
                                        <SelectItem value="700">700 Level (PGD)</SelectItem>
                                        <SelectItem value="800">800 Level (Master's - MBA/MIM/M.Sc)</SelectItem>
                                        <SelectItem value="900">900 Level (Ph.D Doctorate)</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.admitted_level" class="text-xs text-destructive">{{ form.errors.admitted_level }}</p>
                            </div>

                            <!-- Programme -->
                            <div class="flex flex-col gap-1.5">
                                <Label>Programme Admitted <span class="text-destructive">*</span></Label>
                                <Select v-model="form.admitted_programme_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Programme" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="prog in programmes" :key="prog.id" :value="prog.id">
                                            {{ prog.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.admitted_programme_id" class="text-xs text-destructive">{{ form.errors.admitted_programme_id }}</p>
                            </div>

                            <!-- Scholarship (optional) -->
                            <div class="flex flex-col gap-1.5">
                                <Label>Scholarship <span class="text-muted-foreground text-xs">(optional)</span></Label>
                                <Select v-model="form.scholarship_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="None" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No Scholarship</SelectItem>
                                        <SelectItem v-for="s in scholarships" :key="s.id" :value="s.id">
                                            {{ s.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </template>

                        <!-- Show admitted details (read-only) if already admitted -->
                        <template v-if="applicant.status === 'admitted'">
                            <Separator />
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Level</span>
                                    <Badge variant="secondary">{{ applicant.admitted_level ?? 'N/A' }}</Badge>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-muted-foreground">Programme</span>
                                    <span class="text-right font-medium text-xs max-w-[140px]">
                                        {{ programmes.find(p => p.id === applicant.admitted_programme_id)?.name ?? applicant.programme?.name ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </template>
                        
                        <Button @click="updateStatus" :disabled="form.processing || applicant.status === 'admitted' || (user_permissions && !user_permissions.can_review)" class="w-full">
                            Update Status
                        </Button>

                         <a v-if="applicant.status === 'admitted'" :href="route('admin.admissions.letter', applicant.id)" target="_blank" class="w-full">
                            <Button variant="outline" class="w-full">
                                <Download class="h-4 w-4 mr-2" />
                                Download Letter
                            </Button>
                        </a>
                    </CardContent>
                </Card>


                <!-- Payment Info -->
                <Card v-if="payment_info">
                    <CardHeader><CardTitle class="text-lg">Application Fee</CardTitle></CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div class="flex justify-between items-center border-b pb-2">
                            <span class="text-muted-foreground">Amount</span>
                            <span class="font-bold">₦{{ Number(payment_info.amount).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between items-center border-b pb-2">
                            <span class="text-muted-foreground">Status</span>
                            <Badge :variant="payment_info.status === 'paid' ? 'default' : 'destructive'" class="capitalize">
                                {{ payment_info.status }}
                            </Badge>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-muted-foreground">Reference</span>
                            <span class="font-mono text-xs">{{ payment_info.reference }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Contact Info -->
                <Card>
                    <CardHeader><CardTitle class="text-lg">Contact Info</CardTitle></CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div>
                            <span class="block text-muted-foreground text-xs uppercase">Phone</span>
                            <span class="font-medium">{{ applicant.phone || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-muted-foreground text-xs uppercase">Address</span>
                            <span class="font-medium">{{ applicant.address || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-muted-foreground text-xs uppercase">Next of Kin</span>
                            <span class="font-medium">{{ applicant.next_of_kin_name }} ({{ applicant.next_of_kin_relationship }})</span>
                            <span class="block text-xs text-muted-foreground">{{ applicant.next_of_kin_phone }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Detailed Information -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Academic Profile -->
                <Card>
                    <CardHeader>
                        <CardTitle>Academic Profile</CardTitle>
                        <CardDescription>JAMB and Educational background</CardDescription>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-muted/30 p-4 rounded-lg border">
                            <h4 class="text-sm font-semibold uppercase text-muted-foreground mb-2">JAMB DETAILS</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>App. Number</span>
                                    <span class="font-mono font-bold text-blue-600">{{ applicant.application_number || 'N/A' }}</span>
                                </div>
                                <Separator />
                                <div class="flex justify-between">
                                    <span>Reg. Number</span>
                                    <span class="font-mono font-bold">{{ applicant.jamb_registration_number }}</span>
                                </div>
                                <Separator />
                                <div class="flex justify-between items-center text-lg">
                                    <span>Score</span>
                                    <Badge :variant="Number(applicant.jamb_score) >= 200 ? 'default' : 'destructive'">
                                        {{ applicant.jamb_score || 'N/A' }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="bg-muted/30 p-4 rounded-lg border">
                            <h4 class="text-sm font-semibold uppercase text-muted-foreground mb-2">Applied Programme</h4>
                            <div class="space-y-1">
                                <p class="text-lg font-bold text-primary">{{ applicant.programme?.name || 'N/A' }}</p>
                                <p class="text-sm text-muted-foreground">{{ applicant.programme?.department?.faculty?.name }}</p>
                                <Badge variant="secondary" class="mt-2">{{ applicant.application_mode }}</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Personal Information -->
                <Card>
                    <CardHeader><CardTitle>Personal Details</CardTitle></CardHeader>
                    <CardContent class="grid grid-cols-2 lg:grid-cols-3 gap-6">
                         <div>
                            <Label class="text-muted-foreground">Date of Birth</Label>
                            <p class="font-medium">{{ applicant.dob ? format(new Date(applicant.dob), 'PPP') : 'N/A' }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">Gender</Label>
                            <p class="font-medium">{{ applicant.gender || 'N/A' }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">State of Origin</Label>
                            <p class="font-medium">{{ applicant.state?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">LGA</Label>
                            <p class="font-medium">{{ applicant.lga?.name || 'N/A' }}</p>
                        </div>
                         <div>
                            <Label class="text-muted-foreground">Previous Institution</Label>
                            <p class="font-medium">{{ applicant.previous_institution || 'N/A' }}</p>
                        </div>
                        <div v-if="applicant.previous_qualification">
                            <Label class="text-muted-foreground">Previous Qualification</Label>
                            <p class="font-medium">{{ applicant.previous_qualification }} ({{ applicant.previous_class_of_degree || (applicant as any).class_of_degree || 'N/A' }})</p>
                        </div>
                        <div v-if="applicant.previous_cgpa">
                            <Label class="text-muted-foreground">Previous CGPA</Label>
                            <p class="font-medium">{{ applicant.previous_cgpa }} / 5.00</p>
                        </div>
                        <div v-if="(applicant as any).study_mode">
                            <Label class="text-muted-foreground">Mode of Study</Label>
                            <p class="font-medium uppercase">{{ String((applicant as any).study_mode).replace('_', ' ') }}</p>
                        </div>
                        <div v-if="(applicant as any).sponsorship_type">
                            <Label class="text-muted-foreground">Sponsorship</Label>
                            <p class="font-medium uppercase">{{ (applicant as any).sponsorship_type }}</p>
                        </div>
                        <div v-if="(applicant as any).official_transcript_status">
                            <Label class="text-muted-foreground">Official Transcript Status</Label>
                            <p class="font-medium capitalize">{{ String((applicant as any).official_transcript_status).replaceAll('_', ' ') }}</p>
                        </div>
                        <div v-if="applicant.research_proposal_title" class="col-span-2 lg:col-span-3">
                            <Label class="text-muted-foreground">Proposed Research / Capstone Topic</Label>
                            <p class="font-medium italic">{{ applicant.research_proposal_title }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Multi-Degree Qualifications Card (PG) -->
                <Card v-if="(applicant as any).qualifications && (applicant as any).qualifications.length > 0">
                    <CardHeader>
                        <CardTitle>Academic Qualifications History ({{ (applicant as any).qualifications.length }})</CardTitle>
                        <CardDescription>All degrees and diplomas submitted for Postgraduate admission</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="qual in (applicant as any).qualifications"
                            :key="qual.id"
                            class="p-3.5 rounded-xl border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-sm"
                        >
                            <div>
                                <p class="font-bold">{{ qual.qualification_type }} — {{ qual.course_of_study }}</p>
                                <p class="text-xs text-muted-foreground">{{ qual.institution_name }} ({{ qual.graduation_year }})</p>
                            </div>
                            <div class="text-right">
                                <Badge variant="outline">{{ qual.class_of_degree }}</Badge>
                                <p v-if="qual.cgpa" class="text-xs font-mono mt-1">CGPA: {{ qual.cgpa }} / {{ qual.scale_max || '5.00' }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Postgraduate Referees Card -->
                <Card v-if="applicant.referees && applicant.referees.length > 0">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Referee Recommendations ({{ applicant.referees.filter(r => r.status === 'submitted').length }} / {{ applicant.referees.length }})</CardTitle>
                                <CardDescription>Confidential evaluations for School of Postgraduate Studies screening</CardDescription>
                            </div>
                            <Badge v-if="pg_eligibility" :variant="pg_eligibility.eligible ? 'default' : 'destructive'">
                                {{ pg_eligibility.eligible ? `Eligible (${pg_eligibility.default_level}L)` : 'Not Eligible' }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-for="referee in applicant.referees"
                            :key="referee.id"
                            class="p-4 rounded-xl border bg-muted/20 space-y-2 text-sm"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <p class="font-bold">{{ referee.name }} <span class="text-xs font-normal text-muted-foreground">— {{ referee.position_or_rank || referee.relationship }} ({{ referee.institution_or_organization }})</span></p>
                                    <p class="text-xs text-muted-foreground">{{ referee.email }} <span v-if="referee.phone">• {{ referee.phone }}</span></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge :variant="referee.status === 'submitted' ? 'default' : 'secondary'" class="uppercase">
                                        {{ referee.status }}
                                    </Badge>
                                    <a
                                        v-if="referee.status !== 'submitted'"
                                        :href="route('referee.recommendation.show', referee.token)"
                                        target="_blank"
                                        class="text-xs text-primary underline"
                                    >
                                        Evaluation Link
                                    </a>
                                </div>
                            </div>

                            <div v-if="referee.status === 'submitted'" class="border-t pt-2 mt-2 space-y-1.5 text-xs">
                                <div class="flex flex-wrap gap-3">
                                    <span><strong>Verdict:</strong> {{ referee.recommendation_strength?.replaceAll('_', ' ').toUpperCase() }}</span>
                                    <span><strong>Academic:</strong> {{ referee.academic_capacity_rating }}/5</span>
                                    <span><strong>Research:</strong> {{ referee.research_potential_rating }}/5</span>
                                    <span><strong>Integrity:</strong> {{ referee.character_integrity_rating }}/5</span>
                                    <span><strong>Communication:</strong> {{ referee.communication_rating }}/5</span>
                                </div>
                                <p v-if="referee.comments" class="text-muted-foreground italic bg-background p-2.5 rounded border">
                                    "{{ referee.comments }}"
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Documents Gallery -->
                <Card>
                    <CardHeader>
                        <CardTitle>Submitted Documents</CardTitle>
                        <CardDescription>Review credentials and uploads</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="applicant.documents.length === 0" class="flex flex-col items-center justify-center py-12 text-muted-foreground border-2 border-dashed rounded-lg bg-muted/20">
                            <FileText class="w-10 h-10 mb-2 opacity-20" />
                            <p>No documents uploaded yet</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-for="doc in applicant.documents" :key="doc.id" class="group relative flex items-center gap-4 p-4 border rounded-xl hover:shadow-md transition bg-card">
                                <div class="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                    <FileText class="h-6 w-6 text-primary" />
                                </div>
                                <div class="overflow-hidden">
                                    <p class="font-semibold truncate">{{ doc.type.replace('_', ' ').toUpperCase() }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ doc.original_name }}</p>
                                </div>
                                <a :href="route('admin.documents.show', doc.id)" target="_blank" class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition rounded-xl">
                                    <Button variant="secondary" size="sm">
                                        <Download class="w-4 h-4 mr-2" /> View
                                    </Button>
                                </a>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
