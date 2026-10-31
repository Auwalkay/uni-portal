<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { format, formatDistanceToNow } from 'date-fns';
import { 
    User, Mail, Phone, Building2, Briefcase, GraduationCap, Shield, ArrowLeft, 
    Calendar, Pencil, ShieldCheck, UserCircle, Building, Hash, BookOpen, 
    BarChart3, Clock, AlertCircle, CalendarClock, MapPin, Download, RefreshCw,
    CheckCircle2, XCircle, Clock3, CalendarDays, Filter, Sparkles, TrendingUp,
    Banknote, Check, HeartHandshake, Award, FileText, ChevronRight, ChevronDown,
    Activity as ActivityIcon, UserPlus, Eye, Lock, Globe, Home, UserCheck, Layers, 
    Search, CreditCard, KeyRound, AlertTriangle, ShieldAlert, Cpu
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Progress } from '@/components/ui/progress';
import { Input } from '@/components/ui/input';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { route } from 'ziggy-js';

interface ActivityLogItem {
    id: number;
    log_name: string;
    description: string;
    event: string;
    subject_type: string;
    subject_id: string;
    causer: {
        id: string;
        name: string;
        email: string;
    } | null;
    properties: any;
    created_at: string;
    created_at_human: string;
    created_at_formatted: string;
}

const props = defineProps<{
    staff: {
        id: string;
        name: string;
        email: string;
        is_active?: boolean;
        last_login_at?: string | null;
        last_login_ip?: string | null;
        email_verified_at?: string | null;
        created_at?: string;
        roles: Array<{ id: string; name: string }>;
        staff: {
            id: string;
            staff_number: string;
            designation?: string | null;
            is_academic: boolean;
            phone_number?: string | null;
            gender?: string | null;
            date_of_birth?: string | null;
            marital_status?: string | null;
            address?: string | null;
            nationality?: string | null;
            highest_qualification?: string | null;
            date_joined?: string | null;
            specialization?: string | null;
            research_interests?: string | null;
            next_of_kin_name?: string | null;
            next_of_kin_phone?: string | null;
            next_of_kin_address?: string | null;
            next_of_kin_relationship?: string | null;
            basic_salary?: number | string | null;
            allowances?: number | string | null;
            deductions?: number | string | null;
            bonuses?: number | string | null;
            bank_name?: string | null;
            account_number?: string | null;
            account_name?: string | null;
            created_at?: string;
            updated_at?: string;
            creator?: {
                id: string;
                name: string;
                email: string;
            } | null;
            updater?: {
                id: string;
                name: string;
                email: string;
            } | null;
            department?: {
                id: string;
                name: string;
                faculty: {
                    id: string;
                    name: string;
                } | null;
            } | null;
            unit?: {
                id: string;
                name: string;
            } | null;
            state?: {
                id: string;
                name: string;
            } | null;
            lga?: {
                id: string;
                name: string;
            } | null;
            allocations?: Array<{
                id: string;
                course: {
                    code: string;
                    title: string;
                    unit: number;
                };
                session: {
                    name: string;
                };
            }>;
        } | null;
    };
    timetable?: Array<any>;
    payslips?: Array<any>;
    canViewSalary?: boolean;
    activityLogs?: Array<ActivityLogItem>;
    attendanceData?: {
        weekly: Array<{
            week: string;
            start_date: string;
            records: Array<{
                id: string;
                date: string;
                day_name: string;
                formatted_date: string;
                clock_in: string | null;
                clock_out: string | null;
                status: string;
                notes: string | null;
            }>;
            present_count: number;
            total_count: number;
        }>;
        stats: {
            present: number;
            late: number;
            absent: number;
            on_leave: number;
            total: number;
            rate: number;
        };
        filters: {
            month: number;
            year: number;
        };
    };
}>();

// --- Attendance Filter State ---
const selectedMonth = ref(String(props.attendanceData?.filters?.month || new Date().getMonth() + 1));
const selectedYear = ref(String(props.attendanceData?.filters?.year || new Date().getFullYear()));

const months = [
    { value: '1', label: 'January' },
    { value: '2', label: 'February' },
    { value: '3', label: 'March' },
    { value: '4', label: 'April' },
    { value: '5', label: 'May' },
    { value: '6', label: 'June' },
    { value: '7', label: 'July' },
    { value: '8', label: 'August' },
    { value: '9', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

const years = computed(() => {
    const currentYr = new Date().getFullYear();
    return [currentYr - 2, currentYr - 1, currentYr, currentYr + 1].map(y => String(y));
});

const filterAttendance = () => {
    router.get(route('admin.staff.show', props.staff.id), {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
};

// --- Activity Logs Filter State ---
const activitySearch = ref('');
const activityFilterType = ref<'all' | 'profile' | 'account' | 'causer'>('all');
const expandedLogs = ref<Record<number, boolean>>({});

const toggleLogDiff = (id: number) => {
    expandedLogs.value[id] = !expandedLogs.value[id];
};

const filteredActivityLogs = computed(() => {
    let logs = props.activityLogs || [];
    
    if (activityFilterType.value === 'profile') {
        logs = logs.filter(l => l.subject_type === 'Staff');
    } else if (activityFilterType.value === 'account') {
        logs = logs.filter(l => l.subject_type === 'User');
    } else if (activityFilterType.value === 'causer') {
        logs = logs.filter(l => l.causer?.id === props.staff.id);
    }

    if (activitySearch.value.trim()) {
        const term = activitySearch.value.toLowerCase();
        logs = logs.filter(l => 
            (l.description && l.description.toLowerCase().includes(term)) ||
            (l.causer?.name && l.causer.name.toLowerCase().includes(term)) ||
            (l.event && l.event.toLowerCase().includes(term)) ||
            (l.subject_type && l.subject_type.toLowerCase().includes(term))
        );
    }

    return logs;
});

const getLogEventBadge = (event: string) => {
    const e = (event || '').toLowerCase();
    if (e.includes('create') || e.includes('registered')) {
        return { label: 'Created', color: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300' };
    }
    if (e.includes('update') || e.includes('edit')) {
        return { label: 'Updated', color: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-300' };
    }
    if (e.includes('delete') || e.includes('destroy') || e.includes('remove')) {
        return { label: 'Deleted', color: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950 dark:text-rose-300' };
    }
    if (e.includes('login') || e.includes('password') || e.includes('security') || e.includes('role')) {
        return { label: 'Security', color: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-300' };
    }
    return { label: event.toUpperCase() || 'ACTIVITY', color: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300' };
};

const getChangedFields = (properties: any) => {
    if (!properties) return [];
    const attributes = properties.attributes || {};
    const old = properties.old || {};
    const keys = Array.from(new Set([...Object.keys(attributes), ...Object.keys(old)]))
        .filter(k => !['updated_at', 'created_at', 'password', 'remember_token'].includes(k));

    return keys.map(k => ({
        key: k.replace(/_/g, ' '),
        old: old[k] !== undefined ? String(old[k]) : '—',
        new: attributes[k] !== undefined ? String(attributes[k]) : '—',
    }));
};

// --- Formatters & Helpers ---
const formatCurrency = (amount: number | string | null | undefined) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
    }).format(Number(amount) || 0);
};

const formatDateSafe = (dateString?: string | null, pattern: string = 'dd MMMM yyyy') => {
    if (!dateString) return 'N/A';
    try {
        const d = new Date(dateString);
        return isNaN(d.getTime()) ? 'N/A' : format(d, pattern);
    } catch {
        return 'N/A';
    }
};

const calculateAge = (dobString?: string | null) => {
    if (!dobString) return null;
    try {
        const dob = new Date(dobString);
        if (isNaN(dob.getTime())) return null;
        const diff = Date.now() - dob.getTime();
        const ageDate = new Date(diff);
        return Math.abs(ageDate.getUTCFullYear() - 1970);
    } catch {
        return null;
    }
};

const calculateTenure = (joinedDateString?: string | null) => {
    if (!joinedDateString) return null;
    try {
        const joined = new Date(joinedDateString);
        if (isNaN(joined.getTime())) return null;
        return formatDistanceToNow(joined, { addSuffix: true });
    } catch {
        return null;
    }
};

const formatTime = (time: string | null) => {
    if (!time) return '---';
    const parts = time.split(':');
    let h = parseInt(parts[0], 10);
    if (isNaN(h)) return time;
    const m = parts[1] || '00';
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    h = h ? h : 12;
    return `${h.toString().padStart(2, '0')}:${m} ${ampm}`;
};

const getMonthName = (month: number) => {
    return new Date(2000, month - 1).toLocaleString('default', { month: 'long' });
};

const getClassesForDay = (day: string) => {
    return props.timetable?.filter((t: any) => t.day === day) || [];
};

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const totalUnits = computed(() => {
    if (!props.staff.staff?.allocations) return 0;
    return props.staff.staff.allocations.reduce((acc, curr) => acc + (curr.course?.unit || 0), 0);
});

const getAttendanceBadge = (status: string) => {
    switch (status) {
        case 'present': return { label: 'Present', color: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300' };
        case 'late': return { label: 'Late', color: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-300' };
        case 'absent': return { label: 'Absent', color: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950 dark:text-rose-300' };
        case 'on_leave': return { label: 'On Leave', color: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-300' };
        default: return { label: status, color: 'bg-slate-50 text-slate-700 border-slate-200' };
    }
};

const breadcrumbs = [
    { title: 'Staff Directory', href: '/admin/staff' },
    { title: props.staff.name, href: '#' }
];

const resetPassword = () => {
    if (confirm('Are you sure you want to reset this staff member\'s password? A new random password will be generated and emailed to them immediately.')) {
        router.post(route('admin.staff.reset_password', props.staff.id));
    }
};
</script>

<template>
    <Head :title="`${staff.name} - Staff Profile`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 px-4 md:px-8 space-y-8 w-full max-w-[1600px] mx-auto">
            
            <!-- Hero Header Section -->
            <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl overflow-hidden shadow-2xl text-white border border-slate-800">
                <div class="absolute inset-0 bg-grid-white/[0.04] bg-[length:24px_24px]"></div>
                <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative p-6 md:p-10 flex flex-col md:flex-row items-center md:items-end justify-between gap-6">
                    <div class="flex flex-col md:flex-row items-center md:items-end gap-6 text-center md:text-left">
                        <Avatar class="h-28 w-28 md:h-32 md:w-32 border-4 border-white/20 shadow-2xl rounded-2xl ring-4 ring-black/30">
                            <AvatarFallback class="bg-indigo-600 text-white text-4xl font-extrabold rounded-2xl">
                                {{ staff.name.charAt(0) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="space-y-2.5">
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                                <Badge variant="secondary" class="bg-indigo-500/20 text-indigo-200 border-indigo-500/30 px-3 py-1 font-bold text-xs">
                                    {{ staff.staff?.is_academic ? 'Academic Faculty' : 'Administrative Staff' }}
                                </Badge>
                                <Badge variant="outline" class="border-emerald-500/40 text-emerald-300 bg-emerald-500/10 px-3 py-1 font-semibold text-xs">
                                    <CheckCircle2 class="w-3.5 h-3.5 mr-1 text-emerald-400" />
                                    {{ staff.is_active !== false ? 'Active Status' : 'Inactive / Suspended' }}
                                </Badge>
                                <!-- Creator Badge in Header -->
                                <Badge v-if="staff.staff?.creator" variant="outline" class="border-white/10 text-slate-300 bg-white/5 px-2.5 py-1 text-xs">
                                    <UserPlus class="w-3 h-3 mr-1 text-indigo-300" />
                                    Created by {{ staff.staff.creator.name }}
                                </Badge>
                            </div>

                            <h1 class="text-3xl md:text-4xl font-black tracking-tight text-white">{{ staff.name }}</h1>

                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 md:gap-4 text-slate-300 text-xs md:text-sm">
                                <span class="flex items-center gap-1.5 font-medium">
                                    <Briefcase class="w-4 h-4 text-indigo-400" />
                                    {{ staff.staff?.designation || 'Staff Member' }}
                                </span>
                                <span class="hidden md:inline text-slate-600">•</span>
                                <span class="flex items-center gap-1.5 font-medium">
                                    <Mail class="w-4 h-4 text-indigo-400" />
                                    {{ staff.email }}
                                </span>
                                <span v-if="staff.staff?.phone_number" class="hidden md:inline text-slate-600">•</span>
                                <span v-if="staff.staff?.phone_number" class="flex items-center gap-1.5 font-medium">
                                    <Phone class="w-4 h-4 text-indigo-400" />
                                    {{ staff.staff.phone_number }}
                                </span>
                                <span class="hidden md:inline text-slate-600">•</span>
                                <span class="flex items-center gap-1.5 font-mono font-bold text-indigo-300">
                                    <Hash class="w-4 h-4 text-indigo-400" />
                                    {{ staff.staff?.staff_number || 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <Button 
                            variant="outline" 
                            type="button" 
                            @click="resetPassword" 
                            class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-200 border-amber-500/30 font-bold backdrop-blur-sm h-10 px-4"
                        >
                            <RefreshCw class="w-4 h-4 mr-2" /> Reset Password
                        </Button>
                        <Button 
                            variant="default" 
                            as-child 
                            class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-600/30 h-10 px-5"
                        >
                            <Link :href="route('admin.staff.edit', staff.id)">
                                <Pencil class="w-4 h-4 mr-2" /> Edit Profile
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Content Grid Layout (1/4 Sidebar + 3/4 Main Content) -->
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
                
                <!-- Left Sidebar Column (1/4) -->
                <div class="space-y-6">
                    <!-- Departmental & Placement Info -->
                    <Card class="border shadow-sm bg-card">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-xs uppercase tracking-wider font-bold text-muted-foreground flex items-center gap-2">
                                <Building2 class="w-4 h-4 text-indigo-600" />
                                Departmental Info
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-4">
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">Faculty</div>
                                <div class="font-bold flex items-center gap-2 text-slate-800 dark:text-slate-200 text-sm">
                                    <Building class="w-4 h-4 text-indigo-600 shrink-0" />
                                    <span>{{ staff.staff?.department?.faculty?.name || 'N/A' }}</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">Department</div>
                                <div class="font-bold flex items-center gap-2 text-slate-800 dark:text-slate-200 text-sm">
                                    <Building2 class="w-4 h-4 text-orange-600 shrink-0" />
                                    <span>{{ staff.staff?.department?.name || 'Unassigned' }}</span>
                                </div>
                            </div>
                            <div v-if="staff.staff?.unit">
                                <div class="text-xs text-muted-foreground mb-1">Unit / Section</div>
                                <div class="font-bold flex items-center gap-2 text-slate-800 dark:text-slate-200 text-sm">
                                    <Layers class="w-4 h-4 text-teal-600 shrink-0" />
                                    <span>{{ staff.staff.unit.name }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Profile Provenance & Record Audit Card -->
                    <Card class="border shadow-sm bg-card">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-xs uppercase tracking-wider font-bold text-muted-foreground flex items-center gap-2">
                                <ShieldCheck class="w-4 h-4 text-emerald-600" />
                                Record Provenance & Audit
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3.5 pt-4 text-xs">
                            <!-- Created By -->
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <UserPlus class="w-3.5 h-3.5 text-emerald-600" />
                                    Created By
                                </div>
                                <p class="font-extrabold text-slate-900 dark:text-slate-100 text-sm">
                                    {{ staff.staff?.creator?.name || 'System / Super Admin' }}
                                </p>
                                <p v-if="staff.staff?.creator?.email" class="text-muted-foreground text-[11px]">
                                    {{ staff.staff.creator.email }}
                                </p>
                                <p class="text-slate-500 text-[11px] mt-1 font-mono">
                                    {{ formatDateSafe(staff.staff?.created_at || staff.created_at, 'dd MMM yyyy, hh:mm a') }}
                                </p>
                            </div>

                            <!-- Last Updated By -->
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <RefreshCw class="w-3.5 h-3.5 text-blue-600" />
                                    Last Updated By
                                </div>
                                <p class="font-extrabold text-slate-900 dark:text-slate-100 text-sm">
                                    {{ staff.staff?.updater?.name || staff.staff?.creator?.name || 'System / Auto' }}
                                </p>
                                <p v-if="staff.staff?.updater?.email" class="text-muted-foreground text-[11px]">
                                    {{ staff.staff.updater.email }}
                                </p>
                                <p class="text-slate-500 text-[11px] mt-1 font-mono">
                                    {{ formatDateSafe(staff.staff?.updated_at, 'dd MMM yyyy, hh:mm a') }}
                                </p>
                            </div>

                            <!-- Login & Session Security -->
                            <div class="space-y-1.5 pt-2 border-t text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground">Last Login:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ staff.last_login_at ? formatDateSafe(staff.last_login_at, 'dd MMM yyyy, hh:mm a') : 'Never' }}
                                    </span>
                                </div>
                                <div v-if="staff.last_login_ip" class="flex items-center justify-between">
                                    <span class="text-muted-foreground">Last IP:</span>
                                    <span class="font-mono text-slate-700 dark:text-slate-300">{{ staff.last_login_ip }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-muted-foreground">Registered:</span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ formatDateSafe(staff.created_at, 'dd MMM yyyy') }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- System Access & Roles -->
                    <Card class="border shadow-sm bg-card">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-xs uppercase tracking-wider font-bold text-muted-foreground flex items-center gap-2">
                                <Shield class="w-4 h-4 text-primary" />
                                System Access & Roles
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4">
                            <div class="flex flex-wrap gap-2">
                                <Badge v-for="role in staff.roles" :key="role.id" variant="secondary" class="font-semibold py-1 px-2.5 text-xs">
                                    <Shield class="w-3 h-3 mr-1 text-primary" />
                                    {{ formatRoleName(role.name) }}
                                </Badge>
                                <span v-if="!staff.roles?.length" class="text-xs text-muted-foreground italic">No roles assigned</span>
                            </div>
                        </CardContent>
                        <CardFooter class="bg-muted/10 border-t p-3.5">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground font-medium">
                                <ShieldCheck class="w-4 h-4 text-emerald-600 shrink-0" />
                                Account Secured & Verified
                            </div>
                        </CardFooter>
                    </Card>

                    <!-- Next of Kin Card (Quick Glance) -->
                    <Card v-if="staff.staff?.next_of_kin_name" class="border shadow-sm bg-card">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-xs uppercase tracking-wider font-bold text-muted-foreground flex items-center gap-2">
                                <HeartHandshake class="w-4 h-4 text-rose-600" />
                                Emergency Contact
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-3.5 space-y-2 text-xs">
                            <div>
                                <span class="text-muted-foreground">Name:</span>
                                <p class="font-bold text-slate-900 dark:text-slate-100">{{ staff.staff.next_of_kin_name }}</p>
                            </div>
                            <div v-if="staff.staff.next_of_kin_relationship">
                                <span class="text-muted-foreground">Relationship:</span>
                                <p class="font-semibold text-slate-800 dark:text-slate-200">{{ staff.staff.next_of_kin_relationship }}</p>
                            </div>
                            <div v-if="staff.staff.next_of_kin_phone">
                                <span class="text-muted-foreground">Phone:</span>
                                <p class="font-mono font-bold text-indigo-600">{{ staff.staff.next_of_kin_phone }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Main Content Tabs (3/4) -->
                <div class="xl:col-span-3">
                    <Tabs default-value="overview" class="w-full">
                        <TabsList class="w-full justify-start border-b rounded-none h-14 bg-transparent p-0 mb-6 gap-6 md:gap-8 overflow-x-auto">
                            <TabsTrigger value="overview" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-sm md:text-base flex items-center gap-2">
                                <User class="w-4 h-4" /> Overview
                            </TabsTrigger>
                            <TabsTrigger value="attendance" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-sm md:text-base flex items-center gap-2">
                                <Clock class="w-4 h-4" /> Attendance Logs
                            </TabsTrigger>
                            <TabsTrigger value="academic" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-sm md:text-base flex items-center gap-2" v-if="staff.staff?.is_academic">
                                <BookOpen class="w-4 h-4" /> Teaching & Research
                            </TabsTrigger>
                            <TabsTrigger value="payslips" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-sm md:text-base flex items-center gap-2" v-if="canViewSalary">
                                <Banknote class="w-4 h-4" /> Payslips
                            </TabsTrigger>
                            <TabsTrigger value="activity" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-sm md:text-base flex items-center gap-2">
                                <ActivityIcon class="w-4 h-4" /> Activity Log
                                <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-[10px] font-mono font-bold">
                                    {{ activityLogs?.length || 0 }}
                                </Badge>
                            </TabsTrigger>
                        </TabsList>

                        <!-- ========================================== -->
                        <!-- TAB 1: OVERVIEW TAB (Comprehensive Info)  -->
                        <!-- ========================================== -->
                        <TabsContent value="overview" class="space-y-6">
                            <!-- Top 4 KPI Metrics Strip -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <Card class="shadow-sm border-slate-200 dark:border-slate-800">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center justify-between">
                                            <span>Employment Status</span>
                                            <Briefcase class="w-3.5 h-3.5 text-emerald-600" />
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-2xl font-black text-emerald-600">
                                            {{ staff.is_active !== false ? 'Active' : 'Inactive' }}
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            {{ staff.staff?.date_joined ? `Tenure: ${calculateTenure(staff.staff.date_joined)}` : 'Full-time Employee' }}
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card class="shadow-sm border-slate-200 dark:border-slate-800">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center justify-between">
                                            <span>Classification</span>
                                            <GraduationCap class="w-3.5 h-3.5 text-indigo-600" />
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-2xl font-black text-slate-900 dark:text-white">
                                            {{ staff.staff?.is_academic ? 'Academic' : 'Administrative' }}
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1 line-clamp-1">
                                            {{ staff.staff?.designation || 'Staff Member' }}
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card class="shadow-sm border-slate-200 dark:border-slate-800">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center justify-between">
                                            <span>Attendance Rate</span>
                                            <Clock class="w-3.5 h-3.5 text-indigo-600" />
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-2xl font-black text-indigo-600">
                                            {{ attendanceData?.stats?.rate || 0 }}%
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            {{ attendanceData?.stats?.present || 0 }} Present / {{ attendanceData?.stats?.total || 0 }} Logged
                                        </p>
                                    </CardContent>
                                </Card>

                                <Card class="shadow-sm border-slate-200 dark:border-slate-800">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center justify-between">
                                            <span>Record Provenance</span>
                                            <UserCheck class="w-3.5 h-3.5 text-primary" />
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-lg font-black text-slate-900 dark:text-white truncate">
                                            {{ staff.staff?.creator?.name || 'Administrator' }}
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            Onboarded {{ formatDateSafe(staff.staff?.created_at || staff.created_at, 'dd MMM yyyy') }}
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Personal & Demographics Card -->
                            <Card class="shadow-sm">
                                <CardHeader class="border-b bg-muted/10 pb-4">
                                    <CardTitle class="text-base font-bold flex items-center gap-2">
                                        <UserCircle class="w-5 h-5 text-indigo-600" />
                                        Personal & Contact Information
                                    </CardTitle>
                                    <CardDescription>Demographic attributes, citizenship, and residential address details.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Full Name</span>
                                            <p class="text-sm font-extrabold text-slate-900 dark:text-slate-100 mt-0.5">{{ staff.name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Gender</span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ staff.staff?.gender || 'Not Specified' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Date of Birth</span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                                {{ formatDateSafe(staff.staff?.date_of_birth) }}
                                                <span v-if="calculateAge(staff.staff?.date_of_birth)" class="text-xs font-mono text-muted-foreground ml-1">
                                                    ({{ calculateAge(staff.staff?.date_of_birth) }} years old)
                                                </span>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Marital Status</span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ staff.staff?.marital_status || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Nationality</span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ staff.staff?.nationality || 'Nigerian' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">State & LGA of Origin</span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                                {{ staff.staff?.state?.name || 'N/A' }}
                                                <span v-if="staff.staff?.lga?.name">({{ staff.staff.lga.name }})</span>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Phone Number</span>
                                            <p class="text-sm font-mono font-bold text-slate-900 dark:text-slate-100 mt-0.5">{{ staff.staff?.phone_number || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Email Address</span>
                                            <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 mt-0.5">{{ staff.email }}</p>
                                        </div>
                                        <div class="sm:col-span-2 lg:col-span-3">
                                            <span class="text-xs text-muted-foreground font-medium">Residential Address</span>
                                            <p class="text-sm text-slate-800 dark:text-slate-200 mt-0.5">{{ staff.staff?.address || 'No residential address on record' }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Academic & Professional Information Card -->
                            <Card class="shadow-sm">
                                <CardHeader class="border-b bg-muted/10 pb-4">
                                    <CardTitle class="text-base font-bold flex items-center gap-2">
                                        <Award class="w-5 h-5 text-indigo-600" />
                                        Academic & Professional Credentials
                                    </CardTitle>
                                    <CardDescription>Organizational hierarchy, qualifications, and research specializations.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Staff ID / Number</span>
                                            <p class="text-sm font-mono font-extrabold text-indigo-600 dark:text-indigo-400 mt-0.5">{{ staff.staff?.staff_number || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Designation / Title</span>
                                            <p class="text-sm font-bold text-slate-900 dark:text-slate-100 mt-0.5">{{ staff.staff?.designation || 'Staff Member' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Highest Qualification</span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ staff.staff?.highest_qualification || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Date Joined</span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                                                {{ formatDateSafe(staff.staff?.date_joined) }}
                                                <span v-if="calculateTenure(staff.staff?.date_joined)" class="text-xs text-muted-foreground ml-1">
                                                    ({{ calculateTenure(staff.staff?.date_joined) }})
                                                </span>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Faculty Placement</span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ staff.staff?.department?.faculty?.name || 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs text-muted-foreground font-medium">Department</span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ staff.staff?.department?.name || 'Unassigned' }}</p>
                                        </div>
                                        <div v-if="staff.staff?.unit">
                                            <span class="text-xs text-muted-foreground font-medium">Sub-Unit</span>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ staff.staff.unit.name }}</p>
                                        </div>
                                        <div class="sm:col-span-2 lg:col-span-3" v-if="staff.staff?.specialization">
                                            <span class="text-xs text-muted-foreground font-medium">Specialization</span>
                                            <p class="text-sm text-slate-800 dark:text-slate-200 mt-0.5">{{ staff.staff.specialization }}</p>
                                        </div>
                                        <div class="sm:col-span-2 lg:col-span-3" v-if="staff.staff?.research_interests">
                                            <span class="text-xs text-muted-foreground font-medium">Research Interests</span>
                                            <p class="text-sm text-slate-800 dark:text-slate-200 mt-0.5">{{ staff.staff.research_interests }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Compensation & Payroll Details Card (Only if allowed) -->
                            <Card class="shadow-sm" v-if="canViewSalary && staff.staff">
                                <CardHeader class="border-b bg-muted/10 pb-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <CardTitle class="text-base font-bold flex items-center gap-2">
                                                <CreditCard class="w-5 h-5 text-emerald-600" />
                                                Compensation & Financial Profile
                                            </CardTitle>
                                            <CardDescription>Salary configuration, monthly allowances, and disbursement banking details.</CardDescription>
                                        </div>
                                        <Badge variant="outline" class="border-emerald-300 text-emerald-700 bg-emerald-50 dark:bg-emerald-950 dark:text-emerald-300 font-bold text-xs">
                                            Confidential
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900 border">
                                            <span class="text-xs font-semibold text-muted-foreground uppercase">Basic Salary</span>
                                            <p class="text-xl font-black text-slate-900 dark:text-white mt-1">
                                                {{ formatCurrency(staff.staff.basic_salary) }}
                                            </p>
                                        </div>
                                        <div class="p-3.5 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900">
                                            <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase">Allowances</span>
                                            <p class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                                                + {{ formatCurrency(staff.staff.allowances) }}
                                            </p>
                                        </div>
                                        <div class="p-3.5 rounded-xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900">
                                            <span class="text-xs font-semibold text-rose-700 dark:text-rose-400 uppercase">Deductions</span>
                                            <p class="text-xl font-black text-rose-600 dark:text-rose-400 mt-1">
                                                - {{ formatCurrency(staff.staff.deductions) }}
                                            </p>
                                        </div>
                                        <div class="p-3.5 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900">
                                            <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 uppercase">Disbursement Bank</span>
                                            <p class="text-sm font-extrabold text-slate-900 dark:text-white mt-1">
                                                {{ staff.staff.bank_name || 'N/A' }}
                                            </p>
                                            <p class="text-xs font-mono text-muted-foreground">
                                                {{ staff.staff.account_number || 'No Account #' }}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- ========================================== -->
                        <!-- TAB 2: ATTENDANCE TAB (Preserved)          -->
                        <!-- ========================================== -->
                        <TabsContent value="attendance" class="space-y-6">
                            <Card class="border shadow-sm">
                                <CardHeader class="pb-4 border-b">
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                        <div>
                                            <CardTitle class="text-lg font-bold flex items-center gap-2">
                                                <CalendarDays class="w-5 h-5 text-indigo-600" />
                                                Attendance History
                                            </CardTitle>
                                            <CardDescription>Filtered attendance records grouped by week.</CardDescription>
                                        </div>

                                        <div class="flex items-center gap-3 w-full md:w-auto">
                                            <div class="w-36">
                                                <Select v-model="selectedMonth" @update:modelValue="filterAttendance">
                                                    <SelectTrigger class="h-9 font-semibold">
                                                        <SelectValue placeholder="Month" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="w-28">
                                                <Select v-model="selectedYear" @update:modelValue="filterAttendance">
                                                    <SelectTrigger class="h-9 font-semibold">
                                                        <SelectValue placeholder="Year" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="y in years" :key="y" :value="y">{{ y }}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                    </div>
                                </CardHeader>

                                <CardContent class="p-6">
                                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 dark:bg-slate-900 dark:border-slate-800">
                                            <div class="text-xs font-bold text-slate-500 uppercase">Total Days</div>
                                            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ attendanceData?.stats?.total || 0 }}</div>
                                        </div>
                                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900">
                                            <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase">Present</div>
                                            <div class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">{{ attendanceData?.stats?.present || 0 }}</div>
                                        </div>
                                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 dark:bg-amber-950/40 dark:border-amber-900">
                                            <div class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase">Late</div>
                                            <div class="text-2xl font-black text-amber-700 dark:text-amber-300 mt-1">{{ attendanceData?.stats?.late || 0 }}</div>
                                        </div>
                                        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 dark:bg-rose-950/40 dark:border-rose-900">
                                            <div class="text-xs font-bold text-rose-700 dark:text-rose-400 uppercase">Absent</div>
                                            <div class="text-2xl font-black text-rose-700 dark:text-rose-300 mt-1">{{ attendanceData?.stats?.absent || 0 }}</div>
                                        </div>
                                        <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-200 dark:bg-indigo-950/40 dark:border-indigo-900 col-span-2 md:col-span-1">
                                            <div class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase">Attendance Rate</div>
                                            <div class="text-2xl font-black text-indigo-700 dark:text-indigo-300 mt-1">{{ attendanceData?.stats?.rate || 0 }}%</div>
                                        </div>
                                    </div>

                                    <div v-if="attendanceData?.weekly && attendanceData.weekly.length > 0" class="space-y-6">
                                        <div v-for="weekGroup in attendanceData.weekly" :key="weekGroup.week" class="border rounded-xl overflow-hidden bg-card shadow-sm">
                                            <div class="bg-muted/30 p-4 border-b flex flex-wrap items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <Calendar class="w-4 h-4 text-indigo-600" />
                                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ weekGroup.week }}</span>
                                                </div>
                                                <Badge variant="outline" class="font-semibold bg-background">
                                                    {{ weekGroup.present_count }} / {{ weekGroup.total_count }} Days Present
                                                </Badge>
                                            </div>

                                            <Table>
                                                <TableHeader>
                                                    <TableRow class="bg-muted/10 text-xs">
                                                        <TableHead class="font-bold">Date</TableHead>
                                                        <TableHead class="font-bold">Day</TableHead>
                                                        <TableHead class="font-bold">Clock In</TableHead>
                                                        <TableHead class="font-bold">Clock Out</TableHead>
                                                        <TableHead class="font-bold">Status</TableHead>
                                                        <TableHead class="font-bold">Notes</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    <TableRow v-for="rec in weekGroup.records" :key="rec.id" class="hover:bg-muted/30">
                                                        <TableCell class="font-medium text-xs">{{ rec.formatted_date }}</TableCell>
                                                        <TableCell class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ rec.day_name }}</TableCell>
                                                        <TableCell class="text-xs font-mono">{{ formatTime(rec.clock_in) }}</TableCell>
                                                        <TableCell class="text-xs font-mono">{{ formatTime(rec.clock_out) }}</TableCell>
                                                        <TableCell>
                                                            <Badge variant="outline" :class="`font-bold text-[11px] px-2 py-0.5 ${getAttendanceBadge(rec.status).color}`">
                                                                {{ getAttendanceBadge(rec.status).label }}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell class="text-xs text-muted-foreground">{{ rec.notes || '---' }}</TableCell>
                                                    </TableRow>
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </div>
                                    <div v-else class="flex flex-col items-center justify-center py-12 text-center text-muted-foreground border rounded-lg border-dashed">
                                        <Clock class="w-10 h-10 mb-2 opacity-20" />
                                        <p class="font-medium text-sm">No attendance records found</p>
                                        <p class="text-xs text-muted-foreground mt-1">Try selecting a different month or year to view attendance logs.</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- ========================================== -->
                        <!-- TAB 3: TEACHING & RESEARCH (Preserved)    -->
                        <!-- ========================================== -->
                        <TabsContent value="academic" class="space-y-6" v-if="staff.staff?.is_academic">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <Card class="shadow-sm">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-xs font-bold text-muted-foreground uppercase">Teaching Load</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="text-2xl font-black text-indigo-600">{{ totalUnits }} Units</div>
                                        <Progress :value="Math.min((totalUnits / 15) * 100, 100)" class="h-2 mt-3" />
                                    </CardContent>
                                </Card>

                                <Card class="md:col-span-2 shadow-sm">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-xs font-bold text-muted-foreground uppercase">Course Allocations</CardTitle>
                                    </CardHeader>
                                    <CardContent class="p-0">
                                        <Table>
                                            <TableHeader>
                                                <TableRow class="text-xs">
                                                    <TableHead>Code</TableHead>
                                                    <TableHead>Title</TableHead>
                                                    <TableHead>Session</TableHead>
                                                    <TableHead class="text-right">Units</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow v-for="allocation in staff.staff?.allocations" :key="allocation.id">
                                                    <TableCell class="font-semibold text-xs">{{ allocation.course?.code }}</TableCell>
                                                    <TableCell class="text-xs">{{ allocation.course?.title }}</TableCell>
                                                    <TableCell><Badge variant="outline" class="font-semibold">{{ allocation.session?.name }}</Badge></TableCell>
                                                    <TableCell class="text-right font-bold text-xs">{{ allocation.course?.unit }}</TableCell>
                                                </TableRow>
                                                <TableRow v-if="!staff.staff?.allocations?.length">
                                                    <TableCell colspan="4" class="h-24 text-center text-muted-foreground text-xs">
                                                        No courses assigned yet.
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </CardContent>
                                </Card>
                            </div>

                            <Card class="shadow-sm">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2 text-base font-bold">
                                        <CalendarClock class="w-5 h-5 text-indigo-600" />
                                        Weekly Timetable Schedule
                                    </CardTitle>
                                    <CardDescription>Scheduled classes based on allocated courses.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div v-if="!timetable || timetable.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground border rounded-lg border-dashed">
                                        <CalendarClock class="w-10 h-10 mb-3 opacity-20" />
                                        <p class="font-medium text-sm">No classes scheduled</p>
                                        <p class="text-xs text-muted-foreground mt-1">Allocated courses have not been added to the timetable for the current session.</p>
                                    </div>
                                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                                        <div v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']" :key="day" 
                                            class="bg-slate-50 dark:bg-slate-900 rounded-xl border overflow-hidden flex flex-col min-h-[200px]"
                                        >
                                            <div class="bg-indigo-50/60 dark:bg-indigo-950/20 p-3 border-b border-indigo-100 dark:border-indigo-900 flex items-center justify-between">
                                                <span class="font-bold text-indigo-900 dark:text-indigo-400 uppercase text-xs tracking-wider">{{ day }}</span>
                                                <span class="text-[10px] font-semibold text-indigo-700 dark:text-indigo-300 bg-white dark:bg-slate-800 px-2 py-0.5 rounded-full shadow-sm">
                                                    {{ getClassesForDay(day).length }}
                                                </span>
                                            </div>
                                            
                                            <div class="p-2 space-y-2 flex-1">
                                                <div v-if="getClassesForDay(day).length === 0" class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 opacity-60">
                                                    <span class="text-xs">No classes</span>
                                                </div>
                                                
                                                <div v-for="cls in getClassesForDay(day)" :key="cls.id" 
                                                    class="bg-white dark:bg-slate-950 rounded-lg p-3 shadow-sm border-l-4 border-indigo-500 hover:shadow-md transition-all group"
                                                >
                                                    <div class="flex justify-between items-start mb-1">
                                                        <Badge variant="secondary" class="font-mono text-[10px] bg-indigo-50 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-100">
                                                            {{ formatTime(cls.start_time) }} - {{ formatTime(cls.end_time) }}
                                                        </Badge>
                                                    </div>
                                                    
                                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 text-xs mb-0.5 group-hover:text-indigo-600 transition-colors">
                                                        {{ cls.course.code }}
                                                    </h4>
                                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1 mb-1">{{ cls.course.title }}</p>
                                                    
                                                    <div class="space-y-1 pt-1 border-t border-slate-50 dark:border-slate-900">
                                                        <div class="flex items-center gap-1.5 text-[10px] text-slate-600 dark:text-slate-400">
                                                            <MapPin class="w-2.5 h-2.5 text-slate-400" />
                                                            <span class="font-medium">{{ cls.venue || 'TBA' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- ========================================== -->
                        <!-- TAB 4: PAYSLIPS TAB (Preserved)            -->
                        <!-- ========================================== -->
                        <TabsContent value="payslips" class="space-y-6" v-if="canViewSalary">
                            <Card class="shadow-sm">
                                <CardHeader class="border-b">
                                    <CardTitle class="text-base font-bold">Payslip History</CardTitle>
                                    <CardDescription>View and download staff monthly payment records.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-0">
                                    <Table>
                                        <TableHeader>
                                            <TableRow class="bg-muted/10 text-xs">
                                                <TableHead class="font-bold">Period</TableHead>
                                                <TableHead class="font-bold">Basic Salary</TableHead>
                                                <TableHead class="font-bold">Allowances</TableHead>
                                                <TableHead class="font-bold">Deductions</TableHead>
                                                <TableHead class="font-bold">Net Salary</TableHead>
                                                <TableHead class="font-bold">Status</TableHead>
                                                <TableHead class="text-right font-bold pr-6">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="payslip in payslips" :key="payslip.id" class="hover:bg-muted/30">
                                                <TableCell class="font-bold text-xs">
                                                    {{ getMonthName(payslip.payroll.month) }} {{ payslip.payroll.year }}
                                                </TableCell>
                                                <TableCell class="text-xs">{{ formatCurrency(payslip.basic_salary) }}</TableCell>
                                                <TableCell class="text-xs text-emerald-600 font-semibold">+ {{ formatCurrency(payslip.total_allowances) }}</TableCell>
                                                <TableCell class="text-xs text-rose-600 font-semibold">- {{ formatCurrency(payslip.total_deductions) }}</TableCell>
                                                <TableCell class="text-xs font-black text-slate-900 dark:text-white">{{ formatCurrency(payslip.net_salary) }}</TableCell>
                                                <TableCell>
                                                    <Badge :variant="payslip.payroll.paid_at ? 'default' : 'secondary'" class="font-bold text-[10px]">
                                                        {{ payslip.payroll.paid_at ? 'Paid' : 'Pending' }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell class="text-right pr-6">
                                                    <a :href="route('admin.finance.payroll.payslip.download', { payroll: payslip.payroll.id, payrollItem: payslip.id })" target="_blank">
                                                        <Button variant="ghost" size="sm" class="h-8 font-bold">
                                                            <Download class="w-3.5 h-3.5 mr-1" />
                                                            Download
                                                        </Button>
                                                    </a>
                                                </TableCell>
                                            </TableRow>
                                            <TableRow v-if="!payslips?.length">
                                                <TableCell colspan="7" class="h-24 text-center text-muted-foreground text-xs">
                                                    No payslips found for this staff member.
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- ========================================== -->
                        <!-- TAB 5: ACTIVITY LOG TAB (Fully Upgraded)   -->
                        <!-- ========================================== -->
                        <TabsContent value="activity" class="space-y-6">
                            <Card class="shadow-sm">
                                <CardHeader class="border-b pb-4">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div>
                                            <CardTitle class="text-base font-bold flex items-center gap-2">
                                                <ActivityIcon class="w-5 h-5 text-indigo-600" />
                                                Staff Activity & Audit Trail
                                            </CardTitle>
                                            <CardDescription>
                                                Historical chronology of profile modifications, account logins, and administrative actions.
                                            </CardDescription>
                                        </div>
                                        <Badge variant="outline" class="font-mono text-xs w-fit">
                                            Total Logs: {{ filteredActivityLogs.length }}
                                        </Badge>
                                    </div>

                                    <!-- Filter Toolbar for Logs -->
                                    <div class="flex flex-col sm:flex-row items-center gap-3 pt-4 border-t mt-4">
                                        <div class="relative w-full sm:w-72">
                                            <Search class="w-4 h-4 text-muted-foreground absolute left-3 top-2.5" />
                                            <Input 
                                                v-model="activitySearch" 
                                                placeholder="Search activity description or admin..." 
                                                class="pl-9 h-9 text-xs"
                                            />
                                        </div>

                                        <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                                            <Button 
                                                size="sm" 
                                                :variant="activityFilterType === 'all' ? 'default' : 'outline'"
                                                @click="activityFilterType = 'all'"
                                                class="h-8 text-xs font-semibold px-3"
                                            >
                                                All ({{ activityLogs?.length || 0 }})
                                            </Button>
                                            <Button 
                                                size="sm" 
                                                :variant="activityFilterType === 'profile' ? 'default' : 'outline'"
                                                @click="activityFilterType = 'profile'"
                                                class="h-8 text-xs font-semibold px-3"
                                            >
                                                Profile Edits
                                            </Button>
                                            <Button 
                                                size="sm" 
                                                :variant="activityFilterType === 'account' ? 'default' : 'outline'"
                                                @click="activityFilterType = 'account'"
                                                class="h-8 text-xs font-semibold px-3"
                                            >
                                                User Account
                                            </Button>
                                            <Button 
                                                size="sm" 
                                                :variant="activityFilterType === 'causer' ? 'default' : 'outline'"
                                                @click="activityFilterType = 'causer'"
                                                class="h-8 text-xs font-semibold px-3"
                                            >
                                                Staff Actions
                                            </Button>
                                        </div>
                                    </div>
                                </CardHeader>

                                <CardContent class="p-6">
                                    <!-- Activity Timeline -->
                                    <div v-if="filteredActivityLogs && filteredActivityLogs.length > 0" class="relative pl-6 space-y-8 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
                                        <div 
                                            v-for="log in filteredActivityLogs" 
                                            :key="log.id" 
                                            class="relative group"
                                        >
                                            <!-- Timeline Circle Dot -->
                                            <div class="absolute -left-[27px] top-1.5 w-6 h-6 rounded-full bg-white dark:bg-slate-950 border-2 border-indigo-600 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                                                <div class="w-2 h-2 rounded-full bg-indigo-600"></div>
                                            </div>

                                            <!-- Activity Card Box -->
                                            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-card p-4 shadow-sm hover:border-indigo-300 dark:hover:border-indigo-900 transition-colors">
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                    <div class="flex items-center gap-2">
                                                        <Badge variant="outline" :class="`font-bold text-[10px] px-2 py-0.5 ${getLogEventBadge(log.event).color}`">
                                                            {{ getLogEventBadge(log.event).label }}
                                                        </Badge>
                                                        <h4 class="font-extrabold text-sm text-slate-900 dark:text-slate-100">
                                                            {{ log.description || 'System Activity' }}
                                                        </h4>
                                                    </div>
                                                    <span class="text-xs text-muted-foreground font-mono" :title="log.created_at_formatted">
                                                        {{ log.created_at_human }}
                                                    </span>
                                                </div>

                                                <!-- Metadata Row -->
                                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-2.5 pt-2.5 border-t border-slate-100 dark:border-slate-800/80">
                                                    <span class="flex items-center gap-1 font-medium text-slate-700 dark:text-slate-300">
                                                        <UserCheck class="w-3.5 h-3.5 text-indigo-600" />
                                                        By: <strong>{{ log.causer?.name || 'System Automated' }}</strong>
                                                        <span v-if="log.causer?.email" class="text-slate-400 font-normal">({{ log.causer.email }})</span>
                                                    </span>
                                                    <span class="text-slate-300 dark:text-slate-700">•</span>
                                                    <span class="flex items-center gap-1 font-mono text-[11px]">
                                                        <Clock class="w-3.5 h-3.5 text-slate-400" />
                                                        {{ log.created_at_formatted }}
                                                    </span>
                                                    <span class="text-slate-300 dark:text-slate-700">•</span>
                                                    <span class="text-[11px] font-mono text-muted-foreground">
                                                        Target: {{ log.subject_type }}
                                                    </span>
                                                </div>

                                                <!-- Expandable Diff Viewer -->
                                                <div v-if="getChangedFields(log.properties).length > 0" class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                                                    <Button 
                                                        variant="ghost" 
                                                        size="sm" 
                                                        class="h-7 text-xs font-bold text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 px-2 -ml-2"
                                                        @click="toggleLogDiff(log.id)"
                                                    >
                                                        <ChevronDown v-if="expandedLogs[log.id]" class="w-3.5 h-3.5 mr-1" />
                                                        <ChevronRight v-else class="w-3.5 h-3.5 mr-1" />
                                                        <span>{{ expandedLogs[log.id] ? 'Hide' : 'View' }} Attribute Changes ({{ getChangedFields(log.properties).length }})</span>
                                                    </Button>

                                                    <div v-if="expandedLogs[log.id]" class="mt-2.5 overflow-x-auto rounded-lg border bg-slate-50 dark:bg-slate-900 p-2.5">
                                                        <table class="w-full text-xs">
                                                            <thead>
                                                                <tr class="border-b text-slate-500 font-semibold text-left">
                                                                    <th class="py-1 px-2">Attribute</th>
                                                                    <th class="py-1 px-2">Previous Value</th>
                                                                    <th class="py-1 px-2">New Value</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr v-for="item in getChangedFields(log.properties)" :key="item.key" class="border-b last:border-0 border-slate-200/50">
                                                                    <td class="py-1.5 px-2 font-mono font-bold capitalize text-slate-800 dark:text-slate-200">{{ item.key }}</td>
                                                                    <td class="py-1.5 px-2 text-rose-600 line-through font-mono">{{ item.old }}</td>
                                                                    <td class="py-1.5 px-2 text-emerald-600 font-mono font-bold">{{ item.new }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Empty State for Activity Logs -->
                                    <div v-else class="flex flex-col items-center justify-center py-16 text-center text-muted-foreground border rounded-xl border-dashed bg-card">
                                        <ActivityIcon class="w-12 h-12 mb-3 opacity-20 text-indigo-600" />
                                        <h3 class="font-bold text-base text-slate-800 dark:text-slate-200">No Activity Logs Found</h3>
                                        <p class="text-xs max-w-sm mt-1 text-slate-500">
                                            {{ activitySearch ? 'No logs match your current search query. Try clearing the filter.' : 'All administrative edits, profile changes, and security events for this staff member will be logged here automatically.' }}
                                        </p>
                                        <Button v-if="activitySearch" variant="outline" size="sm" class="mt-3 text-xs" @click="activitySearch = ''; activityFilterType = 'all'">
                                            Clear Filters
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
