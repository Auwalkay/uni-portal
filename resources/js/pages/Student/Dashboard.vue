<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { GraduationCap, BookOpen, CreditCard, Activity, CalendarDays, Clock, AlertCircle, IdCard, Calendar, CalendarClock, MapPin, FileText, Home, Megaphone, Download, Pin, Lock, ShieldAlert } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

const props = defineProps<{
    student?: any;
    user?: any;
    isProfileComplete?: boolean;
    schoolFeeStatus?: string; // 'paid', 'partial', 'pending', 'cancelled', etc.
    showRegistrationNotification?: boolean;
    registrationMessage?: string;
    isRegistrationActive?: boolean;
    showHostelNotification?: boolean;
    hostelNotificationMessage?: string;
    stats?: {
        cgpa: string;
        totalUnits: number;
        level: string;
        status: string;
        session: string;
        semester: string;
    };
    timetable?: Array<any>;
    pendingSession?: string;
    announcements?: Array<any>;
    activeSession?: {
        id: string;
        name: string;
        school_fee_payment_enabled: boolean;
        late_payment_deadline: string | null;
        late_fee_amount: number;
    };
}>();

const pinnedAnnouncements = computed(() => {
    return props.announcements?.filter(bulletin => bulletin.is_pinned) || [];
});

const hasPaidEnough = computed(() => {
    return props.schoolFeeStatus === 'paid' || props.schoolFeeStatus === 'partial';
});

const isSecondSemester = computed(() => {
    return props.stats?.semester && (props.stats.semester.toLowerCase().includes('second') || props.stats.semester === '2');
});

const paymentStatusText = computed(() => {
    switch (props.schoolFeeStatus) {
        case 'paid': return 'Fully Paid';
        case 'partial': return 'Partially Paid';
        case 'pending': return 'Unpaid (Pending)';
        case 'cancelled': return 'Cancelled';
        default: return 'Unpaid';
    }
});

const isLateFeeOverdue = computed(() => {
    if (!props.activeSession?.late_payment_deadline) return false;
    return new Date(props.activeSession.late_payment_deadline) < new Date();
});

const formattedLateDeadline = computed(() => {
    if (!props.activeSession?.late_payment_deadline) return '';
    try {
        return new Date(props.activeSession.late_payment_deadline).toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return '';
    }
});

const formatTime = (time: string) => {
    return time.substring(0, 5);
};

const getClassesForDay = (day: string) => {
    if (!props.timetable) return [];
    return props.timetable.filter((t: any) => t.day === day);
};

const getTodaysClasses = () => {
    const today = new Date().toLocaleDateString('en-US', { weekday: 'long' });
    return getClassesForDay(today);
};

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
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-700 p-6 sm:p-8 shadow-lg">
                <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-6 text-white text-center sm:text-left">
                    <img 
                        :src="student?.passport_photo_path ? `/storage/${student.passport_photo_path}` : `https://ui-avatars.com/api/?name=${user?.name}&background=random`" 
                        alt="Profile Photo" 
                        class="h-24 w-24 sm:h-20 sm:w-20 rounded-full border-4 border-white/30 object-cover shadow-md flex-shrink-0"
                    />
                    <div class="flex-1 min-w-0 w-full">
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white leading-tight break-words">{{ greeting() }}, {{ user?.name.split(' ')[0] }}!</h1>
                        <p class="mt-2 text-sm sm:text-base text-blue-100 break-words">
                            {{ student?.matriculation_number || 'Matriculation Pending' }} &bull; {{ student?.program?.name || 'Program N/A' }}
                        </p>
                        <div class="mt-4 flex flex-wrap justify-center sm:justify-start gap-2">
                            <div class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs sm:text-sm backdrop-blur-sm">
                                <CalendarDays class="mr-2 h-4 w-4" />
                                {{ stats?.session }} Session &bull; {{ stats?.semester }}
                            </div>
                            <div v-if="pendingSession" class="inline-flex items-center rounded-full bg-amber-500/30 border border-amber-400/40 text-amber-100 px-3 py-1 text-xs sm:text-sm backdrop-blur-sm font-medium">
                                <Clock class="mr-2 h-4 w-4 text-amber-300" />
                                Promotion Pending to {{ pendingSession }}
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Decorative Circle -->
                <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
            </div>

            <!-- Pending Promotion Notification -->
            <div v-if="pendingSession" class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm flex items-start gap-4 text-amber-900">
                <div class="rounded-full bg-amber-100 p-2">
                    <AlertCircle class="h-6 w-6 text-amber-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-amber-800">Academic Promotion Pending</h3>
                    <p class="mt-1 text-sm text-amber-700">
                        Your academic promotion to the **{{ pendingSession }}** session is currently pending. Please resolve any outstanding school fees for the previous session ({{ stats?.session }}) to automatically complete your promotion and register courses.
                    </p>
                </div>
            </div>

            <!-- School Fee Payments Closed Notification -->
            <div v-if="activeSession && !activeSession.school_fee_payment_enabled && schoolFeeStatus !== 'paid'" class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm flex items-start gap-4 text-red-900">
                <div class="rounded-full bg-red-100 p-2">
                    <AlertCircle class="h-6 w-6 text-red-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-800">School Fee Payments Suspended</h3>
                    <p class="mt-1 text-sm text-red-700">
                        School fee payment for the <strong>{{ activeSession.name }}</strong> session is currently disabled. Please contact the Bursary department for assistance.
                    </p>
                </div>
            </div>

            <!-- Unpaid School Fees Notification -->
            <div v-if="activeSession && activeSession.school_fee_payment_enabled && schoolFeeStatus !== 'paid' && schoolFeeStatus !== 'partial'" class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm flex items-start gap-4 text-red-900">
                <div class="rounded-full bg-red-100 p-2">
                    <CreditCard class="h-6 w-6 text-red-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-800">Outstanding School Fees</h3>
                    <p class="mt-1 text-sm text-red-700">
                        You have not paid your school fees for the <strong>{{ activeSession.name }}</strong> session. Please generate your invoice and make a payment to secure your student registration.
                    </p>
                    <Link :href="route('student.payments.index')" class="mt-3 inline-flex items-center text-sm font-medium text-red-800 hover:text-red-900 underline underline-offset-4">
                        Pay Fees Now &rarr;
                    </Link>
                </div>
            </div>

            <!-- Late Payment Fine Notification -->
            <div v-if="activeSession?.late_payment_deadline && schoolFeeStatus !== 'paid' && schoolFeeStatus !== 'partial'" 
                :class="[
                    'rounded-xl border p-4 shadow-sm flex items-start gap-4',
                    isLateFeeOverdue 
                        ? 'border-red-200 bg-red-50 text-red-900' 
                        : 'border-orange-200 bg-orange-50 text-orange-900'
                ]"
            >
                <div :class="['rounded-full p-2', isLateFeeOverdue ? 'bg-red-100' : 'bg-orange-100']">
                    <CalendarClock :class="['h-6 w-6', isLateFeeOverdue ? 'text-red-600' : 'text-orange-600']" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold">{{ isLateFeeOverdue ? 'Late Registration Fine Applied' : 'Late Payment Deadline Warning' }}</h3>
                    <p :class="['mt-1 text-sm', isLateFeeOverdue ? 'text-red-700' : 'text-orange-700']">
                        <span v-if="isLateFeeOverdue">
                            The late registration deadline of <strong>{{ formattedLateDeadline }}</strong> has passed. A late fine of <strong>₦{{ new Intl.NumberFormat().format(activeSession.late_fee_amount) }}</strong> has been added to your unpaid school fee invoice.
                        </span>
                        <span v-else>
                            Please note that the deadline to pay school fees without penalty is <strong>{{ formattedLateDeadline }}</strong>. A late registration fine of <strong>₦{{ new Intl.NumberFormat().format(activeSession.late_fee_amount) }}</strong> will be applied after this date.
                        </span>
                    </p>
                </div>
            </div>

            <!-- Outstanding Second Semester School Fees (Partial Payment) -->
            <div v-if="activeSession && isSecondSemester && schoolFeeStatus === 'partial'" class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 shadow-sm flex items-start gap-4 text-amber-950 mb-4">
                <div class="rounded-full bg-amber-100 p-2">
                    <ShieldAlert class="h-6 w-6 text-amber-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-amber-900">Second Semester Registration Locked</h3>
                    <p class="mt-1 text-sm text-amber-800">
                        You have only made a partial payment of your school fees for the current session. Course registration/editing and exam card downloads for the Second Semester are locked until your payment is fully cleared.
                    </p>
                    <Link :href="route('student.payments.index')" class="mt-3 inline-flex items-center text-sm font-medium text-amber-850 hover:text-amber-900 underline underline-offset-4">
                        Clear Outstanding Balance Now &rarr;
                    </Link>
                </div>
            </div>

            <!-- Registration Notification -->
            <div v-if="showRegistrationNotification" 
                :class="[
                    'rounded-xl border p-4 shadow-sm flex items-start gap-4',
                    isRegistrationActive 
                        ? 'border-blue-200 bg-blue-50 text-blue-900' 
                        : 'border-amber-200 bg-amber-50 text-amber-900'
                ]"
            >
                <div :class="['rounded-full p-2', isRegistrationActive ? 'bg-blue-100' : 'bg-amber-100']">
                    <AlertCircle :class="['h-6 w-6', isRegistrationActive ? 'text-blue-600' : 'text-amber-600']" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold">{{ isRegistrationActive ? 'Course Registration Open' : 'Course Registration Notice' }}</h3>
                    <p :class="['mt-1 text-sm', isRegistrationActive ? 'text-blue-700' : 'text-amber-700']">
                        {{ registrationMessage }}
                    </p>
                    <Link v-if="isRegistrationActive && !(isSecondSemester && schoolFeeStatus === 'partial')" :href="route('student.courses.create')" class="mt-3 inline-flex items-center text-sm font-medium text-blue-800 hover:text-blue-900 underline underline-offset-4">
                        Register Courses Now &rarr;
                    </Link>
                </div>
            </div>

            <!-- Hostel Booking Notification -->
            <div v-if="showHostelNotification" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm flex items-start gap-4 text-emerald-900">
                <div class="rounded-full bg-emerald-100 p-2">
                    <Home class="h-6 w-6 text-emerald-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold">Hostel Booking Open</h3>
                    <p class="mt-1 text-sm text-emerald-700">
                        {{ hostelNotificationMessage }}
                    </p>
                    <Link :href="route('student.accommodation.index')" class="mt-3 inline-flex items-center text-sm font-medium text-emerald-800 hover:text-emerald-900 underline underline-offset-4">
                        Book Your Room Now &rarr;
                    </Link>
                </div>
            </div>

            <!-- Pinned Announcements Banner -->
            <div v-if="pinnedAnnouncements.length > 0" class="space-y-3">
                <div 
                    v-for="bulletin in pinnedAnnouncements" 
                    :key="bulletin.id" 
                    class="rounded-xl border border-amber-200 bg-amber-50/70 p-5 shadow-sm flex items-start gap-4 text-amber-950 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100"
                >
                    <div class="rounded-full bg-amber-100 dark:bg-amber-950 p-2">
                        <Pin class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <h3 class="font-bold text-base flex items-center gap-2">
                                {{ bulletin.title }}
                                <Badge class="bg-amber-600 hover:bg-amber-700 text-white border-0 text-[10px] py-0.5 px-2">Important Notice</Badge>
                            </h3>
                            <span class="text-xs opacity-75 whitespace-nowrap">{{ new Date(bulletin.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</span>
                        </div>
                        <div v-if="bulletin.content" class="mt-2 text-sm opacity-90 leading-relaxed" v-html="bulletin.content"></div>
                        <div v-if="bulletin.document_path" class="mt-3">
                            <a 
                                :href="`/storage/${bulletin.document_path}`" 
                                target="_blank" 
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-900 hover:text-amber-950 dark:text-amber-300 dark:hover:text-amber-200 underline underline-offset-4"
                            >
                                <Download class="h-4 w-4" /> View Scanned Document Attachment
                            </a>
                        </div>
                    </div>
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
                    <div class="text-2xl font-bold">{{ paymentStatusText }}</div>
                     <p v-if="!hasPaidEnough" class="text-xs text-red-500 font-medium">Action Required</p>
                     <p v-else-if="schoolFeeStatus === 'partial'" class="text-xs text-blue-600 font-medium">Split Payment Active</p>
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
                                <p v-if="hasPaidEnough && student?.passport_photo_path" class="text-sm text-muted-foreground mt-1">
                                    View and print your Student ID Card.
                                </p>
                                <p v-else-if="!hasPaidEnough" class="text-sm text-red-500 mt-1">
                                    Pay School Fees to access ID Card.
                                </p>
                                <p v-else class="text-sm text-yellow-600 mt-1">
                                    Upload Passport Photo to access ID Card.
                                </p>
                            </div>
                            
                            <div v-if="hasPaidEnough && student?.passport_photo_path">
                                <a :href="route('student.id_card.show')" target="_blank" class="mt-4 block text-sm font-medium text-primary underline-offset-4 hover:underline">
                                    View ID Card &rarr;
                                </a>
                            </div>
                             <div v-else-if="!hasPaidEnough">
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
                <!-- Recent Activity / Notifications -->
                <div class="col-span-3 space-y-6">
                     <!-- Today's Schedule (Mini) -->
                     <!-- Today's Schedule (Mini) -->
                     <Card class="bg-indigo-900 text-white border-0 shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-800/50 rounded-full blur-3xl -mr-10 -mt-10"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-purple-600/30 rounded-full blur-2xl -ml-5 -mb-5"></div>
                        
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center gap-2 text-indigo-100 relative z-10">
                                    <CalendarClock class="w-5 h-5" /> Today's Schedule
                                </CardTitle>
                                <!-- View Full Button will go here -->
                            </div>
                        </CardHeader>
                        <CardContent class="relative z-10 space-y-3">
                             <div v-if="getTodaysClasses().length === 0" class="text-center py-6 text-indigo-200/60">
                                <div class="bg-indigo-800/50 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <Calendar class="w-6 h-6" />
                                </div>
                                <p class="text-sm">No classes scheduled for today.</p>
                            </div>

                            <div v-else class="space-y-3">
                                <div v-for="(cls, index) in getTodaysClasses()" :key="cls.id" 
                                    class="group flex items-start gap-3 bg-white/10 p-3 rounded-lg border border-indigo-400/20 hover:bg-white/20 transition-colors"
                                >
                                    <div class="flex flex-col items-center bg-indigo-950/50 rounded p-2 min-w-[3.5rem] border border-indigo-400/10">
                                        <span class="text-xs font-bold">{{ formatTime(cls.start_time) }}</span>
                                        <div class="w-px h-2 bg-indigo-400/30 my-0.5"></div>
                                        <span class="text-[10px] opacity-70">{{ formatTime(cls.end_time) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start">
                                            <h4 class="font-bold text-white truncate pr-2">{{ cls.course.title }}</h4>
                                            <Badge variant="outline" class="text-[10px] h-5 border-indigo-300/30 text-indigo-100 bg-indigo-500/20">
                                                {{ cls.course.code }}
                                            </Badge>
                                        </div>
                                        <div class="flex items-center gap-3 mt-1.5 text-xs text-indigo-200">
                                            <span class="flex items-center gap-1"><MapPin class="w-3 h-3 opacity-70" /> {{ cls.venue }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Latest Announcements Card -->
                    <Card class="border border-border bg-card shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between pb-3 border-b">
                            <CardTitle class="text-md font-semibold flex items-center gap-2 text-foreground">
                                <Megaphone class="w-4 h-4 text-primary" /> Latest Announcements
                            </CardTitle>
                            <Link href="/student/announcements" class="text-xs text-primary hover:underline font-medium">
                                View All
                            </Link>
                        </CardHeader>
                        <CardContent class="pt-4 space-y-4">
                            <div v-if="!announcements || announcements.length === 0" class="text-center py-6 text-muted-foreground text-sm">
                                No announcements published.
                            </div>
                            <div v-else class="space-y-3.5">
                                <div v-for="bulletin in announcements" :key="bulletin.id" class="relative pl-4 border-l-2 border-primary/20 pb-3 last:pb-0 last:border-0">
                                    <!-- Pin Indicator -->
                                    <div v-if="bulletin.is_pinned" class="absolute -left-[5px] top-1.5 h-2 w-2 rounded-full bg-amber-500 ring-4 ring-background"></div>
                                    
                                    <h4 class="font-semibold text-sm text-foreground line-clamp-1 flex items-center gap-1.5">
                                        {{ bulletin.title }}
                                        <Badge v-if="bulletin.is_pinned" variant="outline" class="h-4 px-1 text-[9px] border-amber-500 text-amber-600 bg-amber-500/5">
                                            Pinned
                                        </Badge>
                                        <Badge v-if="bulletin.document_path" variant="outline" class="h-4 px-1 text-[9px] border-blue-500 text-blue-600 bg-blue-500/5">
                                            File
                                        </Badge>
                                    </h4>
                                    <p class="text-xs text-muted-foreground mt-0.5 line-clamp-2">
                                        {{ bulletin.content || 'Scanned document attached.' }}
                                    </p>
                                    <div class="flex items-center justify-between mt-1.5">
                                        <span class="text-[10px] text-muted-foreground">
                                            {{ new Date(bulletin.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                                        </span>
                                        <a 
                                            v-if="bulletin.document_path"
                                            :href="`/storage/${bulletin.document_path}`" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 text-[10px] font-semibold text-primary hover:underline"
                                        >
                                            <Download class="h-3 w-3" /> Scanned File
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="rounded-xl border bg-card shadow-sm">
                    <div class="p-6 border-b">
                        <h3 class="font-semibold text-lg">Notifications</h3>
                    </div>
                    <div class="p-6 space-y-4">
                         <div v-if="!hasPaidEnough || schoolFeeStatus === 'partial'" class="flex items-start gap-3 rounded-lg border" :class="schoolFeeStatus === 'partial' ? 'border-blue-100 bg-blue-50' : 'border-red-100 bg-red-50'">
                            <CreditCard class="h-4 w-4 mt-0.5" :class="schoolFeeStatus === 'partial' ? 'text-blue-600' : 'text-red-600'" />
                            <div class="text-sm">
                                <p class="font-medium" :class="schoolFeeStatus === 'partial' ? 'text-blue-900' : 'text-red-900'">
                                    {{ schoolFeeStatus === 'partial' ? 'Fee Balance Outstanding' : 'School Fees Unpaid' }}
                                </p>
                                <p :class="schoolFeeStatus === 'partial' ? 'text-blue-700' : 'text-red-700'">
                                    {{ schoolFeeStatus === 'partial' ? 'You have a balance remaining on your split payment.' : 'You have not paid your school fees for this session.' }}
                                </p>
                                <Link :href="route('student.payments.index')" class="mt-2 text-xs font-semibold underline" :class="schoolFeeStatus === 'partial' ? 'text-blue-800 hover:text-blue-900' : 'text-red-800 hover:text-red-900'">
                                    {{ schoolFeeStatus === 'partial' ? 'Pay Balance' : 'Pay Now' }}
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
        </div>
    </StudentLayout>
</template>
