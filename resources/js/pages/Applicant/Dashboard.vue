<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ApplicantLayout from '@/layouts/ApplicantLayout.vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { FileText, Calendar, CheckCircle, AlertCircle } from 'lucide-vue-next';
import { route } from 'ziggy-js';

defineProps<{
    applicant?: {
        status: string;
        jamb_registration_number: string;
        application_mode: string;
    } | null;
    invoice?: {
        id: number;
        amount: number;
    } | null;
}>();
</script>

<template>
    <Head title="Applicant Dashboard" />

    <ApplicantLayout>
        <div class="p-6 space-y-8">
            <!-- Welcome Section with Classic Typography -->
            <div class="space-y-2">
                <h2 class="text-3xl font-serif font-bold text-gray-900 tracking-tight">Welcome, Applicant</h2>
                <p class="text-gray-500">Manage your admission progress and tasks.</p>
            </div>

            <!-- Status Overview -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card class="border-l-4 border-l-primary">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Application Status</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold capitalize">
                            {{ applicant?.status || 'Not Started' }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Current stage of your admission
                        </p>
                    </CardContent>
                </Card>

                 <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Admission Mode</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ applicant?.application_mode || 'UTME' }}
                        </div>
                    </CardContent>
                </Card>
            </div>


            <!-- Main Action Area -->
            <Card class="bg-slate-50 border-dashed">
                <CardHeader>
                    <CardTitle class="font-serif">Next Steps</CardTitle>
                    <CardDescription>Complete these tasks to move your application forward.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- ENROLLED STATE -->
                    <div v-if="applicant?.status === 'enrolled'" class="flex items-center justify-between bg-green-50 p-4 rounded-lg border border-green-200">
                         <div class="flex items-center gap-4">
                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <CheckCircle class="h-5 w-5" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-green-900">Enrollment Complete!</h4>
                                <p class="text-sm text-green-700">You are now a student. Proceed to your student dashboard.</p>
                            </div>
                        </div>
                        <a href="/student/dashboard" class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            Go to Student Dashboard
                        </a>
                    </div>

                    <!-- ADMITTED STATE -->
                    <div v-else-if="applicant?.status === 'admitted'" class="space-y-4">
                        <div class="flex items-center justify-between bg-blue-50 p-4 rounded-lg border border-blue-200">
                             <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <CheckCircle class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-blue-900">Congratulations! You have been admitted.</h4>
                                    <p class="text-sm text-blue-700">Please accept your admission to proceed.</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="invoice" class="flex items-center justify-between bg-white p-4 rounded-lg border shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
                                    <FileText class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Pay Acceptance Fee</h4>
                                    <p class="text-sm text-gray-500">Amount: ₦{{ invoice.amount.toLocaleString() }}</p>
                                </div>
                            </div>
                            <Link :href="route('student.payments.pay', invoice.id)" method="post" as="button">
                                <Button size="lg" class="font-serif">Pay Now</Button>
                            </Link>
                        </div>
                        
                        <div v-else class="flex items-center justify-between bg-white p-4 rounded-lg border shadow-sm">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <CheckCircle class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Accept Admission</h4>
                                    <p class="text-sm text-gray-500">Confirm your intent to join the university.</p>
                                </div>
                            </div>
                            <Link :href="route('applicant.accept.offer')" method="post" as="button">
                                <Button size="lg" class="font-serif">Accept Offer</Button>
                            </Link>
                        </div>
                    </div>

                    <!-- NOT ADMITTED / SUBMITTED -->
                    <div v-else-if="applicant" class="flex items-center justify-between bg-white p-4 rounded-lg border shadow-sm">
                        <div class="flex items-center gap-4">
                             <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600">
                                <AlertCircle class="h-5 w-5" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Application Submitted</h4>
                                <p class="text-sm text-gray-500">Your application is currently under review.</p>
                            </div>
                        </div>
                         <div class="flex gap-2">
                             <Link :href="route('applicant.apply.show')">
                                <Button variant="outline">View Application</Button>
                             </Link>
                             <Button variant="ghost" disabled class="text-yellow-600 bg-yellow-50">Under Review</Button>
                         </div>
                    </div>

                    <!-- START NEW -->
                    <div v-else class="flex items-center justify-between bg-white p-4 rounded-lg border shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                1
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Start New Application</h4>
                                <p class="text-sm text-gray-500">Begin your journey to join the university.</p>
                            </div>
                        </div>
                        <Link href="/applicant/apply/start">
                            <Button size="lg" class="font-serif">Start Application</Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </ApplicantLayout>
</template>
