<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardFooter, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Label } from '@/components/ui/label';
import { format } from 'date-fns';
import { 
    FileText, 
    Download, 
    CheckCircle2, 
    XCircle, 
    GraduationCap, 
    Building2, 
    Briefcase, 
    Award,
    ShieldAlert,
    ArrowLeft
} from 'lucide-vue-next';
import { computed } from 'vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    applicant: {
        id: string;
        jamb_registration_number?: string;
        application_number?: string;
        status: string;
        created_at: string;
        application_mode: string;
        program_choice_1?: string;
        scholarship_id?: string;
        admitted_level?: string;
        admitted_programme_id?: string;
        
        first_name?: string;
        last_name?: string;
        phone?: string;
        address?: string;
        dob?: string;
        gender?: string;
        previous_institution?: string;
        previous_qualification?: string;
        previous_course_of_study?: string;
        previous_class_of_degree?: string;
        previous_cgpa?: number | string | null;
        graduation_year?: number | string | null;
        nysc_status?: string | null;
        research_proposal_title?: string | null;
        
        user: { name: string; email: string; avatar_url?: string };
        state?: { id: number; name: string };
        lga?: { id: number; name: string };
        programme?: { 
            name: string; 
            type?: string;
            academic_stream?: string;
            department?: { 
                faculty?: { name: string } 
            } 
        };
        
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
        qualifications?: Array<{
            id: string;
            institution_name: string;
            qualification_type: string;
            course_of_study: string;
            class_of_degree: string;
            cgpa?: string | number | null;
            scale_max?: string | number | null;
            graduation_year?: number | string | null;
        }>;
        employments?: Array<{
            id: string;
            employer_name: string;
            position_title: string;
            start_date?: string | null;
            end_date?: string | null;
            is_current?: boolean;
            job_description?: string | null;
        }>;
    };
    pg_eligibility?: {
        eligible: boolean;
        tier: string;
        default_level: number;
        message: string;
    } | null;
    pg_tier?: string | null;
    default_level?: number;
    payment_info?: {
        amount: number;
        status: string;
        reference: string;
    };
    programmes: Array<{ id: string; name: string; type?: string }>;
    scholarships: Array<{ id: string; name: string }>;
    user_permissions?: {
        can_view_ug?: boolean;
        can_view_pg?: boolean;
        can_review?: boolean;
        can_admit?: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Applicants', href: '/admin/admissions' },
    { title: 'Postgraduate (SPGS)', href: route().has('admin.admissions.pg.index') ? route('admin.admissions.pg.index') : '/admin/admissions/pg' },
    { title: props.applicant.user.name, href: '#' },
];

const form = useForm({
    status: props.applicant.status,
    admitted_level: props.applicant.admitted_level ?? String(props.default_level ?? 700),
    admitted_programme_id: props.applicant.admitted_programme_id ?? props.applicant.program_choice_1 ?? '',
    scholarship_id: props.applicant.scholarship_id ?? '',
});

const isAdmitting = computed(() => form.status === 'admitted');

const updateStatus = () => {
    const targetRoute = route().has('admin.admissions.pg.update')
        ? route('admin.admissions.pg.update', props.applicant.id)
        : `/admin/admissions/pg/${props.applicant.id}`;

    form.put(targetRoute, {
        preserveScroll: true,
    });
};

const passportUrl = computed(() => {
    const doc = props.applicant.documents.find(d => d.type === 'passport_photo');
    return doc ? `/storage/${doc.path}` : null;
});

const submittedRefereesCount = computed(() => {
    return (props.applicant.referees || []).filter(r => r.status === 'submitted').length;
});
</script>

<template>
    <Head :title="`SPGS Dossier: ${applicant.user.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Top Bar Nav -->
            <div class="flex items-center justify-between">
                <Link :href="route().has('admin.admissions.pg.index') ? route('admin.admissions.pg.index') : '/admin/admissions/pg'">
                    <Button variant="ghost" size="sm" class="gap-2 text-xs">
                        <ArrowLeft class="w-4 h-4" />
                        Back to PG Applicants
                    </Button>
                </Link>

                <div class="flex items-center gap-2">
                    <Badge variant="outline" class="bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800 text-xs font-semibold">
                        SPGS Candidate: {{ applicant.application_number || applicant.jamb_registration_number || 'PG' }}
                    </Badge>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Actions & Status -->
                <div class="space-y-6">
                    <!-- Profile Card -->
                    <Card class="text-center overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-600/20 via-indigo-600/20 to-primary/20 h-24"></div>
                        <div class="px-6 pb-6 -mt-12">
                            <div class="relative w-28 h-28 mx-auto rounded-full border-4 border-background bg-muted flex items-center justify-center overflow-hidden shadow-sm">
                                <img 
                                    v-if="passportUrl" 
                                    :src="passportUrl" 
                                    class="object-cover w-full h-full"
                                    :alt="applicant.user.name"
                                />
                                <GraduationCap v-else class="w-12 h-12 text-muted-foreground opacity-40" />
                            </div>

                            <h3 class="mt-3 text-lg font-bold tracking-tight text-foreground">{{ applicant.user.name }}</h3>
                            <p class="text-xs text-muted-foreground">{{ applicant.user.email }}</p>
                            <div class="mt-2 flex items-center justify-center gap-1.5 flex-wrap">
                                <Badge variant="secondary" class="text-xs uppercase">
                                    {{ applicant.programme?.type || 'POSTGRADUATE' }}
                                </Badge>
                                <Badge :class="applicant.status === 'admitted' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-purple-100 text-purple-700 border-purple-200'" class="capitalize text-xs">
                                    {{ applicant.status }}
                                </Badge>
                            </div>
                        </div>

                        <Separator />

                        <!-- Status Action Form -->
                        <CardContent class="p-6 text-left space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Screening & Admission Verdict</h4>

                            <div class="space-y-2">
                                <Label for="status" class="text-xs">Application Status</Label>
                                <Select v-model="form.status" :disabled="applicant.status === 'admitted' || !user_permissions?.can_review">
                                    <SelectTrigger id="status" class="w-full">
                                        <SelectValue placeholder="Select Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="submitted">Submitted (Under Review)</SelectItem>
                                        <SelectItem value="screening">In SPGS Screening</SelectItem>
                                        <SelectItem 
                                            value="admitted" 
                                            :disabled="!user_permissions?.can_admit"
                                        >
                                            Admitted {{ !user_permissions?.can_admit ? '(Requires admit_pg_students)' : '' }}
                                        </SelectItem>
                                        <SelectItem value="rejected">Rejected / Ineligible</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Admitted Extra Fields -->
                            <div v-if="isAdmitting" class="space-y-4 pt-2 border-t">
                                <div class="space-y-2">
                                    <Label for="admitted_level" class="text-xs">Admitted Level (PG Tier)</Label>
                                    <Select v-model="form.admitted_level" :disabled="applicant.status === 'admitted' || !user_permissions?.can_admit">
                                        <SelectTrigger id="admitted_level" class="w-full">
                                            <SelectValue placeholder="Select Level" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="700">700 Level (PGD)</SelectItem>
                                            <SelectItem value="800">800 Level (Masters / M.Phil)</SelectItem>
                                            <SelectItem value="900">900 Level (Doctorate - Ph.D)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2">
                                    <Label for="admitted_programme_id" class="text-xs">Admitted Programme</Label>
                                    <Select v-model="form.admitted_programme_id" :disabled="applicant.status === 'admitted' || !user_permissions?.can_admit">
                                        <SelectTrigger id="admitted_programme_id" class="w-full">
                                            <SelectValue placeholder="Select Programme" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="p in programmes" :key="p.id" :value="p.id">
                                                {{ p.name }} ({{ p.type || 'PG' }})
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <Button 
                                @click="updateStatus" 
                                :disabled="form.processing || applicant.status === 'admitted' || !user_permissions?.can_review" 
                                class="w-full mt-2"
                            >
                                <span v-if="form.processing">Updating...</span>
                                <span v-else>Update Verdict</span>
                            </Button>

                            <a 
                                v-if="applicant.status === 'admitted'" 
                                :href="route().has('admin.admissions.pg.letter') ? route('admin.admissions.pg.letter', applicant.id) : `/admin/admissions/pg/${applicant.id}/letter`" 
                                target="_blank" 
                                class="block w-full mt-2"
                            >
                                <Button variant="outline" class="w-full gap-2">
                                    <Download class="h-4 w-4" />
                                    Download SPGS Letter
                                </Button>
                            </a>
                        </CardContent>
                    </Card>

                    <!-- SPGS Pre-check / Automated Eligibility -->
                    <Card v-if="pg_eligibility" class="border-purple-200 dark:border-purple-800">
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-sm font-bold flex items-center gap-1.5">
                                    <Award class="w-4 h-4 text-purple-600" />
                                    SPGS Eligibility Assessment
                                </CardTitle>
                                <Badge :variant="pg_eligibility.eligible ? 'default' : 'destructive'" class="text-xs">
                                    {{ pg_eligibility.eligible ? 'Qualified' : 'Requires Waiver' }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="text-xs space-y-2 pt-2">
                            <p class="text-muted-foreground">{{ pg_eligibility.message }}</p>
                            <div v-if="pg_eligibility.default_level" class="text-foreground font-semibold flex items-center gap-1">
                                Recommended Placement: <span class="font-mono text-purple-600">{{ pg_eligibility.default_level }}L ({{ pg_eligibility.tier }})</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Application Fee -->
                    <Card v-if="payment_info">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-bold">Application Fee Payment</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-xs">
                            <div class="flex justify-between items-center border-b pb-2">
                                <span class="text-muted-foreground">Amount</span>
                                <span class="font-bold">₦{{ Number(payment_info.amount).toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b pb-2">
                                <span class="text-muted-foreground">Status</span>
                                <Badge :variant="payment_info.status === 'paid' ? 'default' : 'destructive'" class="capitalize text-xs">
                                    {{ payment_info.status }}
                                </Badge>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-muted-foreground">Reference</span>
                                <span class="font-mono text-[10px]">{{ payment_info.reference }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Candidate Dossier -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Applied Programme Card -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>Prospective Programme</CardTitle>
                                    <CardDescription>Applied degree track in School of Postgraduate Studies</CardDescription>
                                </div>
                                <Badge variant="outline" class="font-mono text-xs uppercase">
                                    {{ applicant.programme?.type || 'PG' }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div class="p-4 rounded-xl bg-purple-500/5 border border-purple-200/50 dark:border-purple-800/50">
                                <h4 class="text-base font-bold text-foreground">{{ applicant.programme?.name || 'N/A' }}</h4>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ applicant.programme?.department?.faculty?.name }}
                                </p>
                            </div>

                            <div v-if="applicant.research_proposal_title" class="p-3 bg-muted/30 rounded-lg border text-xs">
                                <span class="font-bold text-muted-foreground uppercase text-[10px] block mb-1">PROPOSED RESEARCH / DISSERTATION TOPIC</span>
                                <p class="font-medium text-foreground italic">"{{ applicant.research_proposal_title }}"</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Prior Academic Qualifications -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Academic Credentials & Degrees</CardTitle>
                            <CardDescription>Prior tertiary degrees evaluated for postgraduate entry</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div 
                                v-if="applicant.qualifications && applicant.qualifications.length > 0"
                                class="space-y-3"
                            >
                                <div 
                                    v-for="qual in applicant.qualifications" 
                                    :key="qual.id"
                                    class="p-4 rounded-xl border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-sm"
                                >
                                    <div>
                                        <p class="font-bold text-foreground">{{ qual.qualification_type }} — {{ qual.course_of_study }}</p>
                                        <p class="text-xs text-muted-foreground">{{ qual.institution_name }} ({{ qual.graduation_year || 'N/A' }})</p>
                                    </div>
                                    <div class="text-right">
                                        <Badge variant="outline" class="font-semibold text-xs">{{ qual.class_of_degree }}</Badge>
                                        <p v-if="qual.cgpa" class="text-xs font-mono font-bold mt-1 text-primary">
                                            CGPA: {{ qual.cgpa }} / {{ qual.scale_max || '5.00' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="applicant.previous_qualification" class="p-4 rounded-xl border bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-sm">
                                <div>
                                    <p class="font-bold text-foreground">{{ applicant.previous_qualification }} — {{ applicant.previous_course_of_study || 'N/A' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ applicant.previous_institution || 'N/A' }} ({{ applicant.graduation_year || 'N/A' }})</p>
                                </div>
                                <div class="text-right">
                                    <Badge variant="outline">{{ applicant.previous_class_of_degree || 'N/A' }}</Badge>
                                    <p v-if="applicant.previous_cgpa" class="text-xs font-mono font-bold mt-1 text-primary">
                                        CGPA: {{ applicant.previous_cgpa }}
                                    </p>
                                </div>
                            </div>
                            <div v-else class="text-center py-6 text-xs text-muted-foreground italic">
                                No prior qualifications entered by applicant.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Confidential Referee Recommendations -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>Academic & Professional Referees</CardTitle>
                                    <CardDescription>Confidential evaluations submitted for SPGS review</CardDescription>
                                </div>
                                <Badge :variant="submittedRefereesCount >= 3 ? 'default' : 'secondary'" class="text-xs font-mono">
                                    {{ submittedRefereesCount }} / {{ applicant.referees?.length || 0 }} Submitted
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="!applicant.referees || applicant.referees.length === 0" class="text-center py-6 text-xs text-muted-foreground italic">
                                No referees registered.
                            </div>
                            <div
                                v-for="referee in applicant.referees"
                                :key="referee.id"
                                class="p-4 rounded-xl border bg-muted/20 space-y-2 text-sm"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <p class="font-bold text-foreground">
                                            {{ referee.name }} 
                                            <span class="text-xs font-normal text-muted-foreground">— {{ referee.position_or_rank || referee.relationship }} ({{ referee.institution_or_organization }})</span>
                                        </p>
                                        <p class="text-xs text-muted-foreground">{{ referee.email }} <span v-if="referee.phone">• {{ referee.phone }}</span></p>
                                    </div>
                                    <Badge :variant="referee.status === 'submitted' ? 'default' : 'secondary'" class="uppercase text-xs font-semibold">
                                        {{ referee.status }}
                                    </Badge>
                                </div>

                                <div v-if="referee.status === 'submitted'" class="border-t pt-2 mt-2 space-y-2 text-xs">
                                    <div class="flex flex-wrap gap-3 font-medium">
                                        <span>Verdict: <strong class="text-foreground">{{ referee.recommendation_strength?.replaceAll('_', ' ').toUpperCase() }}</strong></span>
                                        <span>Academic: <strong class="text-foreground">{{ referee.academic_capacity_rating }}/5</strong></span>
                                        <span>Research: <strong class="text-foreground">{{ referee.research_potential_rating }}/5</strong></span>
                                        <span>Integrity: <strong class="text-foreground">{{ referee.character_integrity_rating }}/5</strong></span>
                                        <span>Communication: <strong class="text-foreground">{{ referee.communication_rating }}/5</strong></span>
                                    </div>
                                    <p v-if="referee.comments" class="text-muted-foreground italic bg-background p-3 rounded-lg border">
                                        "{{ referee.comments }}"
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Professional Experience -->
                    <Card v-if="applicant.employments && applicant.employments.length > 0">
                        <CardHeader>
                            <CardTitle>Work Experience</CardTitle>
                            <CardDescription>Professional background and relevant industry practice</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div 
                                v-for="emp in applicant.employments" 
                                :key="emp.id"
                                class="p-3 rounded-lg border bg-muted/20 text-xs space-y-1"
                            >
                                <div class="flex justify-between items-center">
                                    <p class="font-bold text-foreground">{{ emp.position_title }}</p>
                                    <Badge v-if="emp.is_current" variant="outline" class="text-[10px]">Current</Badge>
                                </div>
                                <p class="text-muted-foreground">{{ emp.employer_name }}</p>
                                <p v-if="emp.job_description" class="text-muted-foreground text-[11px] pt-1">
                                    {{ emp.job_description }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Document Uploads -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Submitted Documents & Dossier Uploads</CardTitle>
                            <CardDescription>Degree certificates, academic transcripts, and credentials</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="applicant.documents.length === 0" class="text-center py-6 text-xs text-muted-foreground italic">
                                No documents uploaded.
                            </div>
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="doc in applicant.documents" :key="doc.id" class="p-4 border rounded-xl flex items-center justify-between gap-3 bg-card hover:bg-muted/30 transition">
                                    <div class="overflow-hidden">
                                        <p class="font-semibold text-xs truncate">{{ doc.type.replace('_', ' ').toUpperCase() }}</p>
                                        <p class="text-[11px] text-muted-foreground truncate">{{ doc.original_name }}</p>
                                    </div>
                                    <a :href="`/admin/documents/${doc.id}`" target="_blank">
                                        <Button variant="outline" size="sm" class="h-8 gap-1.5 text-xs">
                                            <Download class="w-3.5 h-3.5" /> View
                                        </Button>
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

