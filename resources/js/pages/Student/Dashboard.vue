<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { GraduationCap, BookOpen, CreditCard, Activity, CalendarDays, Clock, AlertCircle, IdCard } from 'lucide-vue-next';

const props = defineProps<{
    student?: any;
    user?: any;
    isProfileComplete?: boolean;
    hasPaidSchoolFee?: boolean;
    showRegistrationNotification?: boolean;
    registrationMessage?: string;
    stats?: {
        cgpa: string;
        totalUnits: number;
        level: string;
        status: string;
        session: string;
        semester: string;
    };
}>();

console.log('Student', props.student?.program)

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/student/dashboard',
    },
];

const greeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good Morning';
    if (hour < 18) return 'Good Afternoon';
    return 'Good Evening';
};
</script>

<template>
    <Head title="Student Dashboard" />

    <StudentLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-6">
            <!-- Welcome Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 p-8 shadow-lg">
                <div class="relative z-10 flex items-center gap-6 text-white">
                    <img 
                        :src="student?.passport_photo_path ? `/storage/${student.passport_photo_path}` : `https://ui-avatars.com/api/?name=${user?.name}&background=random`" 
                        alt="Profile Photo" 
                        class="h-20 w-20 rounded-full border-4 border-white/30 object-cover shadow-md"
                    />
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ greeting() }}, {{ user?.name.split(' ')[0] }}!</h1>
                        <p class="mt-2 text-blue-100">
                            {{ student?.matriculation_number || 'Matriculation Pending' }} &bull; {{ student?.program?.name || 'Program N/A' }}
                        </p>
                        <div class="mt-4 inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-sm backdrop-blur-sm">
                            <CalendarDays class="mr-2 h-4 w-4" />
                            {{ stats?.session }} Session &bull; {{ stats?.semester }}
                        </div>
                    </div>
                </div>
                <!-- Decorative Circle -->
                <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
            </div>

            <!-- Registration Notification -->
            <div v-if="showRegistrationNotification" class="rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm flex items-start gap-4">
                <div class="rounded-full bg-blue-100 p-2">
                    <AlertCircle class="h-6 w-6 text-blue-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-blue-900">Course Registration Open</h3>
                    <p class="text-blue-700 mt-1 text-sm">
                        {{ registrationMessage || 'Course registration for the current semester is now open. Please register your courses before the deadline.' }}
                    </p>
                    <Link :href="route('student.courses.create')" class="mt-3 inline-flex items-center text-sm font-medium text-blue-800 hover:text-blue-900 underline underline-offset-4">
                        Register Courses Now &rarr;
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                    <div class="flex items-center justify-between space-y-0 pb-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Current CGPA</h3>
                        <GraduationCap class="h-4 w-4 text-primary" />
                    </div>
                    <div class="text-2xl font-bold">{{ stats?.cgpa }}</div>
                    <p class="text-xs text-muted-foreground">{{ stats?.status }}</p>
                </div>
                <div class="rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                    <div class="flex items-center justify-between space-y-0 pb-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Registered Units</h3>
                        <BookOpen class="h-4 w-4 text-blue-500" />
                    </div>
                    <div class="text-2xl font-bold">{{ stats?.totalUnits }}</div>
                    <p class="text-xs text-muted-foreground">Units this session</p>
                </div>
                <div class="rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                    <div class="flex items-center justify-between space-y-0 pb-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Current Level</h3>
                        <Activity class="h-4 w-4 text-green-500" />
                    </div>
                    <div class="text-2xl font-bold">{{ stats?.level }}</div>
                    <p class="text-xs text-muted-foreground">{{ stats?.session }}</p>
                </div>
                <div class="rounded-xl border bg-card p-6 shadow-sm transition-all hover:shadow-md">
                    <div class="flex items-center justify-between space-y-0 pb-2">
                        <h3 class="text-sm font-medium text-muted-foreground">Pending Fees</h3>
                        <CreditCard class="h-4 w-4 text-red-500" />
                    </div>
                    <div class="text-2xl font-bold">{{ hasPaidSchoolFee ? 'Paid' : 'Unpaid' }}</div>
                     <p v-if="!hasPaidSchoolFee" class="text-xs text-red-500 font-medium">Action Required</p>
                     <p v-else class="text-xs text-green-600 font-medium">Cleared</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
                <!-- Action Center -->
                <div class="col-span-4 rounded-xl border bg-card shadow-sm">
                    <div class="p-6">
                        <h3 class="font-semibold text-lg">Quick Actions</h3>
                        <p class="text-sm text-muted-foreground">Common tasks you might need to perform.</p>
                    </div>
                    <div class="grid gap-4 p-6 pt-0 sm:grid-cols-2">
                        <!-- Profile Action -->
                        <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                    <div :class="['h-2 w-2 rounded-full', isProfileComplete ? 'bg-green-500' : 'bg-yellow-500']"></div>
                                    Update Profile
                                </h4>
                                <p class="text-sm text-muted-foreground mt-1">
                                    {{ isProfileComplete ? 'Your profile is up to date.' : 'Complete your bio data to proceed.' }}
                                </p>
                            </div>
                            <Link :href="route('student.profile.edit')" class="mt-4 text-sm font-medium text-primary underline-offset-4 hover:underline">
                                {{ isProfileComplete ? 'View Profile' : 'Complete Profile' }} &rarr;
                            </Link>
                        </div>

                        <!-- Course Reg Action -->
                         <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                     <BookOpen class="h-4 w-4 text-muted-foreground" />
                                    Course Registration
                                </h4>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Register your courses for the current semester.
                                </p>
                            </div>
                            <Link :href="route('student.courses.index')" class="mt-4 text-sm font-medium text-primary underline-offset-4 hover:underline">
                                Register Courses &rarr;
                            </Link>
                        </div>

                         <!-- Payment Action -->
                         <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                    <CreditCard class="h-4 w-4 text-muted-foreground" />
                                    Make Payment
                                </h4>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Pay school fees and other outstanding invoices.
                                </p>
                            </div>
                            <Link :href="route('student.payments.index')" class="mt-4 text-sm font-medium text-primary underline-offset-4 hover:underline">
                                Go to Payments &rarr;
                            </Link>
                        </div>

                        <!-- Results Action -->
                         <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                    <GraduationCap class="h-4 w-4 text-muted-foreground" />
                                    Check Results
                                </h4>
                                <p class="text-sm text-muted-foreground mt-1">
                                    View your academic performance and transcript.
                                </p>
                            </div>
                            <Link :href="route('student.results.index')" class="mt-4 text-sm font-medium text-primary underline-offset-4 hover:underline">
                                View Results &rarr;
                            </Link>
                        </div>

                        <!-- ID Card Action -->
                        <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                    <IdCard class="h-4 w-4 text-muted-foreground" />
                                    Student ID Card
                                </h4>
                                <p v-if="hasPaidSchoolFee && student?.passport_photo_path" class="text-sm text-muted-foreground mt-1">
                                    View and print your Student ID Card.
                                </p>
                                <p v-else-if="!hasPaidSchoolFee" class="text-sm text-red-500 mt-1">
                                    Pay School Fees to access ID Card.
                                </p>
                                <p v-else class="text-sm text-yellow-600 mt-1">
                                    Upload Passport Photo to access ID Card.
                                </p>
                            </div>
                            
                            <div v-if="hasPaidSchoolFee && student?.passport_photo_path">
                                <a :href="route('student.id_card.show')" target="_blank" class="mt-4 block text-sm font-medium text-primary underline-offset-4 hover:underline">
                                    View ID Card &rarr;
                                </a>
                            </div>
                             <div v-else-if="!hasPaidSchoolFee">
                                <Link :href="route('student.payments.index')" class="mt-4 text-sm font-medium text-red-600 underline-offset-4 hover:underline">
                                    Pay Now &rarr;
                                </Link>
                            </div>
                             <div v-else>
                                <Link :href="route('student.profile.edit')" class="mt-4 text-sm font-medium text-yellow-600 underline-offset-4 hover:underline">
                                    Upload Photo &rarr;
                                </Link>
                            </div>
                        </div>
                         <!-- Admission Letter Action -->
                         <div class="group relative flex flex-col justify-between rounded-lg border p-4 hover:bg-accent/50 transition-colors">
                            <div>
                                <h4 class="font-medium flex items-center gap-2">
                                    <FileText class="h-4 w-4 text-muted-foreground" />
                                    Admission Letter
                                </h4>
                                <p class="text-sm text-muted-foreground mt-1">
                                    Download your official admission letter.
                                </p>
                            </div>
                            <a :href="route('student.admission_letter.download')" target="_blank" class="mt-4 text-sm font-medium text-primary underline-offset-4 hover:underline">
                                Download PDF &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity / Notifications -->
                <div class="col-span-3 rounded-xl border bg-card shadow-sm">
                    <div class="p-6 border-b">
                        <h3 class="font-semibold text-lg">Notifications</h3>
                    </div>
                    <div class="p-6 space-y-4">
                         <div v-if="!hasPaidSchoolFee" class="flex items-start gap-3 rounded-lg border border-red-100 bg-red-50 p-3 text-sm">
                            <CreditCard class="h-4 w-4 text-red-600 mt-0.5" />
                            <div>
                                <p class="font-medium text-red-900">School Fees Unpaid</p>
                                <p class="text-red-700">You have not paid your school fees for this session.</p>
                                <Link :href="route('student.payments.create_school_fee')" method="post" as="button" class="mt-2 text-xs font-semibold text-red-800 underline hover:text-red-900">
                                    Pay Now
                                </Link>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 rounded-lg border p-3 text-sm">
                            <Clock class="h-4 w-4 text-muted-foreground mt-0.5" />
                            <div>
                                <p class="font-medium">Course Registration Deadline</p>
                                <p class="text-muted-foreground">Registration closes on Feb 28th, 2026.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
