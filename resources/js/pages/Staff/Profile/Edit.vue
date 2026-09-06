<script setup lang="ts">
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import { 
    User, Mail, Phone, MapPin, BookOpen, ClipboardList, 
    Heart, Shield, KeyRound, Camera, ArrowLeft, Loader2,
    CheckCircle2, Eye, Info, BadgeCheck, Clock, CalendarDays,
    Calendar, Briefcase, Hash, Check
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    staff: any;
    status?: string;
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

const page = usePage();
const authUser = computed(() => (page.props.auth as any).user);

const breadcrumbs = [
    { title: 'My Profile', href: route('staff.profile.edit') },
];

const photoInput = ref<HTMLInputElement | null>(null);
const photoPreview = ref<string | null>(null);

const form = useForm({
    _method: 'POST',
    phone_number: props.staff.phone_number || '',
    address: props.staff.address || '',
    specialization: props.staff.specialization || '',
    research_interests: props.staff.research_interests || '',
    next_of_kin_name: props.staff.next_of_kin_name || '',
    next_of_kin_phone: props.staff.next_of_kin_phone || '',
    next_of_kin_address: props.staff.next_of_kin_address || '',
    next_of_kin_relationship: props.staff.next_of_kin_relationship || '',
    profile_photo: null as File | null,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

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
    router.get(route('staff.profile.edit'), {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
};

const selectNewPhoto = () => {
    photoInput.value?.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value?.files?.[0];
    if (!photo) return;

    form.profile_photo = photo;

    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(photo);
};

const updateProfile = () => {
    form.post(route('staff.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.profile_photo = null;
            if (photoInput.value) {
                photoInput.value.value = '';
            }
        }
    });
};

const updatePassword = () => {
    passwordForm.put(route('staff.profile.password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

const formatTime = (time: string | null) => {
    if (!time) return '---';
    return time.substring(0, 5);
};

const getMonthName = (month: number) => {
    return new Date(2000, month - 1).toLocaleString('default', { month: 'long' });
};

const getAttendanceBadge = (status: string) => {
    switch (status) {
        case 'present': return { label: 'Present', color: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300' };
        case 'late': return { label: 'Late', color: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-300' };
        case 'absent': return { label: 'Absent', color: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950 dark:text-rose-300' };
        case 'on_leave': return { label: 'On Leave', color: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950 dark:text-blue-300' };
        default: return { label: status, color: 'bg-slate-50 text-slate-700 border-slate-200' };
    }
};
</script>

<template>
    <Head title="My Profile Settings" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-8 px-6 space-y-8 w-full max-w-[1600px] mx-auto pb-20">
            
            <!-- Hero Banner Header -->
            <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl overflow-hidden shadow-xl text-white border border-slate-800">
                <div class="absolute inset-0 bg-grid-white/[0.05] bg-[length:24px_24px]"></div>
                
                <div class="relative p-6 md:p-8 flex flex-col md:flex-row items-center md:items-end justify-between gap-6">
                    <div class="flex flex-col md:flex-row items-center md:items-end gap-6 text-center md:text-left">
                        <!-- Photo Upload Avatar -->
                        <div class="relative group">
                            <Avatar class="h-28 w-28 border-4 border-white/20 shadow-2xl rounded-2xl ring-4 ring-black/20 overflow-hidden">
                                <img v-if="photoPreview || authUser.profile_photo_path" 
                                     :src="photoPreview || `/storage/${authUser.profile_photo_path}`" 
                                     class="w-full h-full object-cover" />
                                <AvatarFallback v-else class="bg-indigo-600 text-white text-3xl font-extrabold rounded-2xl">
                                    {{ authUser.name.charAt(0) }}
                                </AvatarFallback>
                            </Avatar>
                            <button @click="selectNewPhoto" type="button" class="absolute -bottom-1 -right-1 p-2 bg-indigo-600 text-white rounded-full border-2 border-white shadow-lg hover:scale-110 transition-transform">
                                <Camera class="w-4 h-4" />
                            </button>
                            <input type="file" ref="photoInput" class="hidden" @change="updatePhotoPreview" accept="image/*" />
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                                <Badge variant="secondary" class="bg-indigo-500/20 text-indigo-200 border-indigo-500/30 font-bold px-2.5 py-0.5 text-xs">
                                    Staff Account Profile
                                </Badge>
                                <Badge variant="outline" class="border-emerald-500/40 text-emerald-300 bg-emerald-500/10 px-2.5 py-0.5 text-xs">
                                    <CheckCircle2 class="w-3 h-3 mr-1" /> Active
                                </Badge>
                            </div>
                            <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">{{ authUser.name }}</h1>
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 text-slate-300 text-xs font-medium">
                                <span class="flex items-center gap-1"><Briefcase class="w-3.5 h-3.5 text-indigo-400" /> {{ staff.designation || 'Staff Member' }}</span>
                                <span class="hidden md:inline text-slate-600">•</span>
                                <span class="flex items-center gap-1"><Mail class="w-3.5 h-3.5 text-indigo-400" /> {{ authUser.email }}</span>
                                <span class="hidden md:inline text-slate-600">•</span>
                                <span class="flex items-center gap-1"><Hash class="w-3.5 h-3.5 text-indigo-400" /> {{ staff.staff_number || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <Button variant="outline" size="sm" as-child class="bg-white/10 hover:bg-white/20 text-white border-white/20 font-bold backdrop-blur-sm h-10 px-4">
                            <Link :href="route('staff.profile.show')">
                                <Eye class="w-4 h-4 mr-2" /> Preview Public Profile
                            </Link>
                        </Button>
                        <Button @click="updateProfile" :disabled="form.processing" size="sm" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold h-10 px-5 shadow-lg shadow-indigo-600/30">
                            <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                            <CheckCircle2 v-else class="w-4 h-4 mr-2" />
                            Save Changes
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Profile Tabs -->
            <Tabs default-value="personal" class="w-full space-y-6">
                <TabsList class="w-full justify-start border-b rounded-none h-14 bg-transparent p-0 mb-6 gap-8">
                    <TabsTrigger value="personal" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-base flex items-center gap-2">
                        <User class="w-4 h-4" /> Personal
                    </TabsTrigger>
                    <TabsTrigger value="attendance" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-base flex items-center gap-2">
                        <Clock class="w-4 h-4" /> My Attendance
                    </TabsTrigger>
                    <TabsTrigger value="academic" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-base flex items-center gap-2">
                        <BookOpen class="w-4 h-4" /> Academic Focus
                    </TabsTrigger>
                    <TabsTrigger value="nok" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-base flex items-center gap-2">
                        <Heart class="w-4 h-4" /> Next of Kin
                    </TabsTrigger>
                    <TabsTrigger value="security" class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary font-bold rounded-none h-14 px-1 text-base flex items-center gap-2">
                        <Shield class="w-4 h-4" /> Security
                    </TabsTrigger>
                </TabsList>

                <!-- Personal Information Tab -->
                <TabsContent value="personal" class="space-y-6">
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-sm font-bold flex items-center gap-2">
                                <Info class="w-4 h-4 text-indigo-600" /> Contact & Residence
                            </CardTitle>
                            <CardDescription>Update your personal contact details and home address.</CardDescription>
                        </CardHeader>
                        <CardContent class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2 md:col-span-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Phone Number</Label>
                                    <div class="relative group">
                                        <Phone class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                        <Input v-model="form.phone_number" class="pl-10 h-10 font-semibold" placeholder="+234..." />
                                    </div>
                                    <p v-if="form.errors.phone_number" class="text-xs text-destructive font-medium">{{ form.errors.phone_number }}</p>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Residential Address</Label>
                                    <div class="relative group">
                                        <MapPin class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                        <Textarea v-model="form.address" class="pl-10 min-h-[100px] font-medium" placeholder="Your current home address..." />
                                    </div>
                                    <p v-if="form.errors.address" class="text-xs text-destructive font-medium">{{ form.errors.address }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Attendance Tab (Weekly Grouping + Month/Year Filters) -->
                <TabsContent value="attendance" class="space-y-6">
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-4 border-b">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div>
                                    <CardTitle class="text-lg font-bold flex items-center gap-2">
                                        <CalendarDays class="w-5 h-5 text-indigo-600" />
                                        My Attendance History
                                    </CardTitle>
                                    <CardDescription>View your attendance records grouped by week for selected month and year.</CardDescription>
                                </div>

                                <!-- Month & Year Filter Controls -->
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
                            <!-- Attendance Metrics Summary Cards -->
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
                                    <div class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase">Rate</div>
                                    <div class="text-2xl font-black text-indigo-700 dark:text-indigo-300 mt-1">{{ attendanceData?.stats?.rate || 0 }}%</div>
                                </div>
                            </div>

                            <!-- Weekly Grouped Attendance Table -->
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

                            <!-- Empty State -->
                            <div v-else class="flex flex-col items-center justify-center py-12 text-center text-muted-foreground border border-dashed rounded-xl">
                                <Clock class="w-12 h-12 mb-3 opacity-20" />
                                <h3 class="font-bold text-base text-slate-800 dark:text-slate-200">No Attendance Records</h3>
                                <p class="text-xs max-w-sm mt-1 text-slate-500">You have no logged attendance entries for {{ getMonthName(Number(selectedMonth)) }} {{ selectedYear }}.</p>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Academic Focus Tab -->
                <TabsContent value="academic" class="space-y-6">
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-sm font-bold flex items-center gap-2">
                                <BookOpen class="w-4 h-4 text-indigo-600" /> Research & Specialization
                            </CardTitle>
                            <CardDescription>Manage your research focus and academic specialization.</CardDescription>
                        </CardHeader>
                        <CardContent class="p-6 space-y-6">
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Area of Specialization</Label>
                                    <div class="relative group">
                                        <BookOpen class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                        <Input v-model="form.specialization" class="pl-10 h-10 font-semibold" placeholder="e.g. Artificial Intelligence, Data Science..." />
                                    </div>
                                    <p v-if="form.errors.specialization" class="text-xs text-destructive font-medium">{{ form.errors.specialization }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Research Interests</Label>
                                    <div class="relative group">
                                        <ClipboardList class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                        <Textarea v-model="form.research_interests" class="pl-10 min-h-[140px] font-medium" placeholder="Describe your active research projects and publications..." />
                                    </div>
                                    <p v-if="form.errors.research_interests" class="text-xs text-destructive font-medium">{{ form.errors.research_interests }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Next of Kin Tab -->
                <TabsContent value="nok" class="space-y-6">
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-sm font-bold flex items-center gap-2">
                                <Heart class="w-4 h-4 text-rose-500" /> Next of Kin Details
                            </CardTitle>
                            <CardDescription>Emergency contact information.</CardDescription>
                        </CardHeader>
                        <CardContent class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Next of Kin Name</Label>
                                    <Input v-model="form.next_of_kin_name" class="h-10 font-semibold" placeholder="Full name" />
                                    <p v-if="form.errors.next_of_kin_name" class="text-xs text-destructive font-medium">{{ form.errors.next_of_kin_name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Relationship</Label>
                                    <Input v-model="form.next_of_kin_relationship" class="h-10 font-semibold" placeholder="e.g. Spouse, Parent, Sibling" />
                                    <p v-if="form.errors.next_of_kin_relationship" class="text-xs text-destructive font-medium">{{ form.errors.next_of_kin_relationship }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Contact Phone</Label>
                                    <Input v-model="form.next_of_kin_phone" class="h-10 font-semibold" placeholder="+234..." />
                                    <p v-if="form.errors.next_of_kin_phone" class="text-xs text-destructive font-medium">{{ form.errors.next_of_kin_phone }}</p>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-600">Residential Address</Label>
                                    <Textarea v-model="form.next_of_kin_address" class="min-h-[90px] font-medium" placeholder="Full address of next of kin" />
                                    <p v-if="form.errors.next_of_kin_address" class="text-xs text-destructive font-medium">{{ form.errors.next_of_kin_address }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Security Tab -->
                <TabsContent value="security" class="space-y-6">
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-3 border-b bg-muted/20">
                            <CardTitle class="text-sm font-bold flex items-center gap-2">
                                <KeyRound class="w-4 h-4 text-amber-500" /> Change Account Password
                            </CardTitle>
                            <CardDescription>Ensure your account is using a long, random password to stay secure.</CardDescription>
                        </CardHeader>
                        <CardContent class="p-6 space-y-6">
                            <form @submit.prevent="updatePassword" class="grid grid-cols-1 gap-6 max-w-md">
                                <div class="space-y-2">
                                    <Label for="current_password" class="text-xs font-bold uppercase tracking-wider text-slate-600">Current Password</Label>
                                    <Input id="current_password" type="password" v-model="passwordForm.current_password" class="h-10" />
                                    <p v-if="passwordForm.errors.current_password" class="text-xs text-destructive font-medium">{{ passwordForm.errors.current_password }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="password" class="text-xs font-bold uppercase tracking-wider text-slate-600">New Password</Label>
                                    <Input id="password" type="password" v-model="passwordForm.password" class="h-10" />
                                    <p v-if="passwordForm.errors.password" class="text-xs text-destructive font-medium">{{ passwordForm.errors.password }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="password_confirmation" class="text-xs font-bold uppercase tracking-wider text-slate-600">Confirm Password</Label>
                                    <Input id="password_confirmation" type="password" v-model="passwordForm.password_confirmation" class="h-10" />
                                </div>
                                <div class="flex items-center gap-4">
                                    <Button :disabled="passwordForm.processing" class="h-10 px-5 font-bold shadow-md bg-indigo-600 hover:bg-indigo-500 text-white">
                                        <Loader2 v-if="passwordForm.processing" class="w-4 h-4 animate-spin mr-2" />
                                        Update Password
                                    </Button>
                                    <p v-if="passwordForm.recentlySuccessful" class="text-xs text-emerald-600 font-bold">Password updated successfully.</p>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AdminLayout>
</template>
