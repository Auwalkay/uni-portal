<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { 
    QrCode, ScanLine, Search, CheckCircle2, XCircle, Calendar, Clock, Building, 
    ShieldCheck, UserCheck, AlertTriangle, Sparkles, Camera, CameraOff, RefreshCw, 
    ArrowLeft, Users, Check, Volume2, VolumeX, History, Keyboard, Barcode
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';

interface Props {
    schedules: any[];
    activeScheduleId?: string;
    sessions?: any[];
    semesters?: any[];
    selectedSessionId?: string;
    selectedSemesterId?: string;
    canManageExams?: boolean;
}

const props = defineProps<Props>();
const page = usePage();

const currentUser = computed(() => (page.props as any).auth?.user);
const verifiedCandidate = computed(() => (page.props as any).flash?.verified_candidate || null);

// Session & Semester filters
const filterSessionId = ref<string>(props.selectedSessionId || '');
const filterSemesterId = ref<string>(props.selectedSemesterId || '');

// Read query parameter if navigated with ?schedule_id=XYZ
const getInitialScheduleId = () => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const qId = params.get('schedule_id');
        if (qId && props.schedules.some(s => s.id === qId)) {
            return qId;
        }
    }
    return props.activeScheduleId || '';
};

const selectedScheduleId = ref<string>(getInitialScheduleId());
const tokenInput = ref('');
const isVerifying = ref(false);
const inputMode = ref<'manual' | 'camera'>('manual'); // Default to manual/barcode for instant reliability

const inputRef = ref<HTMLInputElement | null>(null);

// Audio feedback state
const soundEnabled = ref(true);

const playTone = (type: 'success' | 'error') => {
    if (!soundEnabled.value) return;
    try {
        const AudioCtx = window.AudioContext || (window as any).webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();

        if (type === 'success') {
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(880, ctx.currentTime);
            gain1.gain.setValueAtTime(0.2, ctx.currentTime);
            gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start();
            osc1.stop(ctx.currentTime + 0.2);

            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(1174.66, ctx.currentTime + 0.12);
            gain2.gain.setValueAtTime(0.25, ctx.currentTime + 0.12);
            gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(ctx.currentTime + 0.12);
            osc2.stop(ctx.currentTime + 0.35);
        } else {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, ctx.currentTime);
            osc.frequency.linearRampToValueAtTime(140, ctx.currentTime + 0.3);
            gain.gain.setValueAtTime(0.25, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        }
    } catch (e) {
        // audio context ignored
    }
};

// Camera Handler
const isCameraActive = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);
let mediaStream: MediaStream | null = null;
let scanInterval: any = null;
const cameraError = ref<string | null>(null);

const startCamera = async () => {
    cameraError.value = null;
    isCameraActive.value = true;
    await nextTick();

    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' }
        });
        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
            await videoRef.value.play();
        }

        if ('BarcodeDetector' in window) {
            const detector = new (window as any).BarcodeDetector({ formats: ['qr_code', 'code_128'] });
            scanInterval = setInterval(async () => {
                if (videoRef.value && isCameraActive.value && videoRef.value.readyState === 4 && !isVerifying.value) {
                    try {
                        const barcodes = await detector.detect(videoRef.value);
                        if (barcodes.length > 0 && barcodes[0].rawValue) {
                            const scannedValue = barcodes[0].rawValue.trim();
                            if (scannedValue && scannedValue !== tokenInput.value) {
                                tokenInput.value = scannedValue;
                                handleVerifyPass(scannedValue);
                            }
                        }
                    } catch (e) {
                        // frame error ignored
                    }
                }
            }, 350);
        }
    } catch (err: any) {
        console.error('Camera access error:', err);
        cameraError.value = 'Camera not available on this device/browser. Please use manual typing or USB barcode reader.';
        isCameraActive.value = false;
    }
};

const stopCamera = () => {
    isCameraActive.value = false;
    if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
    }
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
        mediaStream = null;
    }
};

watch(inputMode, (newMode) => {
    if (newMode === 'camera') {
        startCamera();
    } else {
        stopCamera();
        focusInput();
    }
});

const focusInput = () => {
    nextTick(() => {
        setTimeout(() => {
            if (inputRef.value) {
                const el = (inputRef.value as any).$el || inputRef.value;
                if (el && typeof el.focus === 'function') {
                    el.focus();
                }
            }
        }, 100);
    });
};

onMounted(() => {
    focusInput();
});

onUnmounted(() => {
    stopCamera();
});

// Invigilator Check Helper
const isUserInvigilatorForExam = (exam: any) => {
    if (!currentUser.value?.id || !exam?.invigilators || !Array.isArray(exam.invigilators)) return false;
    return exam.invigilators.some((inv: any) => 
        inv.staff?.user_id === currentUser.value.id || 
        inv.staff?.user?.id === currentUser.value.id
    );
};

const invigilatedSchedules = computed(() => {
    return (props.schedules || []).filter(isUserInvigilatorForExam);
});

const activeExam = computed(() => {
    if (!selectedScheduleId.value) return null;
    return props.schedules.find(e => e.id === selectedScheduleId.value) || null;
});

const isCandidateRegisteredForActiveExam = computed(() => {
    if (!verifiedCandidate.value) return null;
    if (!activeExam.value) return true; // In general verification mode, any registered student is eligible
    const regCourseIds = verifiedCandidate.value.registered_course_ids || [];
    return regCourseIds.includes(activeExam.value.course_id);
});

const candidateAttendanceForActiveExam = computed(() => {
    if (!verifiedCandidate.value || !activeExam.value) return null;
    const sched = (verifiedCandidate.value.schedules || []).find((s: any) => s.id === activeExam.value?.id);
    return sched?.attendances?.[0] || null;
});

const isCandidateAlreadyMarked = computed(() => {
    return !!candidateAttendanceForActiveExam.value;
});

// Dropdown options
const scheduleOptions = computed(() => {
    return [
        { value: '', label: '🔍 General Verification Mode (No Course Selected)' },
        ...(props.schedules || []).map(exam => ({
            value: exam.id,
            label: `${isUserInvigilatorForExam(exam) ? '⭐ ' : ''}${exam.course?.code || ''} - ${exam.course?.title || ''} (${exam.venue || 'Hall'}${exam.exam_date ? ' • ' + format(new Date(exam.exam_date), 'MMM dd') : ''})`
        }))
    ];
});

// Session & Semester filter options
const sessionFilterOptions = computed(() => {
    return (props.sessions || []).map(s => ({
        value: s.id,
        label: `${s.name}${s.is_current ? ' (Current)' : ''}${s.academic_stream ? ' • ' + s.academic_stream : ''}`,
    }));
});

const semesterFilterOptions = computed(() => {
    let list = props.semesters || [];
    if (filterSessionId.value) {
        list = list.filter(s => s.session_id === filterSessionId.value);
    }
    return [
        { value: '', label: 'All Semesters' },
        ...list.map(sem => ({
            value: sem.id,
            label: `${sem.name}${sem.is_current ? ' (Active)' : ''}`,
        }))
    ];
});

const handleFilterChange = () => {
    router.get(route('admin.exams.scanner'), {
        session_id: filterSessionId.value,
        semester_id: filterSemesterId.value,
        schedule_id: selectedScheduleId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(filterSessionId, (newId, oldId) => {
    if (newId !== oldId && oldId !== undefined) {
        filterSemesterId.value = '';
        selectedScheduleId.value = '';
        handleFilterChange();
    }
});

watch(filterSemesterId, (newId, oldId) => {
    if (newId !== oldId && oldId !== undefined) {
        selectedScheduleId.value = '';
        handleFilterChange();
    }
});

const getScheduleForCourse = (courseId: string) => {
    if (!verifiedCandidate.value?.schedules) return null;
    return verifiedCandidate.value.schedules.find((s: any) => s.course_id === courseId) || null;
};

// Recent Verification Feed Log
const recentScansLog = ref<any[]>([]);

const handleVerifyPass = (codeOverride?: string) => {
    let codeToVerify = typeof codeOverride === 'string' ? codeOverride.trim() : tokenInput.value.trim();
    if (!codeToVerify) return;

    // Handle scanned full URL (e.g. from Docket QR code: https://.../admin/exams/verify-pass/EXM-...)
    if (codeToVerify.includes('/verify-pass/')) {
        const parts = codeToVerify.split('/verify-pass/');
        if (parts[1]) {
            codeToVerify = decodeURIComponent(parts[1].split('?')[0]);
        }
    } else {
        // Handle scanned JSON payload: {"token": "EXM-...", "matric": "..."}
        try {
            if (codeToVerify.startsWith('{') && codeToVerify.endsWith('}')) {
                const parsed = JSON.parse(codeToVerify);
                if (parsed.token) codeToVerify = parsed.token;
                else if (parsed.matric) codeToVerify = parsed.matric;
            }
        } catch (e) {
            // Raw string
        }
    }

    tokenInput.value = codeToVerify;
    isVerifying.value = true;

    router.get(route('admin.exams.verify_pass', codeToVerify), {
        session_id: filterSessionId.value || undefined,
        semester_id: filterSemesterId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            if (verifiedCandidate.value) {
                const isCleared = verifiedCandidate.value.is_cleared;
                const isEligible = activeExam.value ? (isCleared && isCandidateRegisteredForActiveExam.value) : isCleared;

                if (isEligible) {
                    playTone('success');
                } else {
                    playTone('error');
                }

                recentScansLog.value.unshift({
                    id: Date.now(),
                    timestamp: format(new Date(), 'HH:mm:ss'),
                    student: verifiedCandidate.value.student,
                    is_cleared: verifiedCandidate.value.is_cleared,
                    is_registered: isCandidateRegisteredForActiveExam.value,
                    exam_code: activeExam.value?.course?.code || 'General',
                    attendance_status: candidateAttendanceForActiveExam.value?.status || null,
                });
            }
        },
        onError: () => {
            playTone('error');
        },
        onFinish: () => {
            isVerifying.value = false;
            if (inputMode.value === 'manual') {
                tokenInput.value = '';
                focusInput();
            }
        }
    });
};

const isMarkingAttendance = ref(false);

const markCandidateAttendance = (status: 'present' | 'late' = 'present') => {
    if (!activeExam.value || !verifiedCandidate.value) return;

    isMarkingAttendance.value = true;
    router.post(route('admin.exams.mark_attendance'), {
        exam_schedule_id: activeExam.value.id,
        student_id: verifiedCandidate.value.student.id,
        status: status,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2200,
                icon: 'success',
                title: `Attendance Marked ${status.toUpperCase()}!`,
            });

            // Update latest entry in recent scans log
            const latest = recentScansLog.value.find(s => s.student?.id === verifiedCandidate.value?.student?.id);
            if (latest) {
                latest.attendance_status = status;
            }
        },
        onFinish: () => {
            isMarkingAttendance.value = false;
        }
    });
};

const clearAndReset = () => {
    tokenInput.value = '';
    focusInput();
};

const breadcrumbs = [
    { title: 'Academic', href: '#' },
    { title: 'Examinations', href: route('admin.exams.index') },
    { title: 'Attendance Verification', href: '#' },
];
</script>

<template>
    <Head title="Exam Attendance Verification & Scanner" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-6 p-4 sm:p-6 pb-24">
            
            <!-- Top Action Header -->
            <!-- Header & Session/Semester Filter Bar -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <div class="flex items-center gap-3">
                    <Button 
                        variant="outline" 
                        size="sm" 
                        class="h-9 px-3 rounded-xl text-xs font-semibold gap-1.5 text-slate-700 dark:text-slate-200 border-slate-300"
                        @click="router.visit(route('admin.exams.index'))"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        <span>Back to Exams</span>
                    </Button>

                    <div>
                        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100 leading-tight">
                            Exam Attendance Verification
                        </h1>
                        <p class="text-xs text-slate-500 font-medium">Verify student pass tokens, matriculation numbers, or scan clearance QR codes</p>
                    </div>
                </div>

                <!-- Session & Semester Filters + Audio Chime Toggle -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Session Dropdown -->
                    <div class="w-44 sm:w-52">
                        <SearchableSelect
                            v-model="filterSessionId"
                            :items="sessionFilterOptions"
                            placeholder="Select Session"
                            search-placeholder="Search session..."
                            trigger-class="h-9 text-xs font-semibold bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                        />
                    </div>

                    <!-- Semester Dropdown -->
                    <div class="w-36 sm:w-44">
                        <SearchableSelect
                            v-model="filterSemesterId"
                            :items="semesterFilterOptions"
                            placeholder="Select Semester"
                            search-placeholder="Search semester..."
                            trigger-class="h-9 text-xs font-semibold bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                        />
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        :class="[
                            'h-9 px-3 rounded-xl text-xs font-semibold gap-1.5 border transition-colors',
                            soundEnabled ? 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300' : 'bg-slate-100 text-slate-500 border-slate-200'
                        ]"
                        @click="soundEnabled = !soundEnabled"
                        title="Toggle Beep Sound"
                    >
                        <Volume2 v-if="soundEnabled" class="w-4 h-4 text-purple-600" />
                        <VolumeX v-else class="w-4 h-4" />
                        <span class="hidden sm:inline">{{ soundEnabled ? 'Audio Chime On' : 'Audio Muted' }}</span>
                    </Button>
                </div>
            </div>

            <!-- Active Exam Paper Selection Card -->
            <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-2xl shadow-xs overflow-hidden">
                <CardContent class="p-5 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b pb-3">
                        <div class="flex items-center gap-2">
                            <Label class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <Building class="w-4 h-4 text-purple-600" />
                                <span>Course / Exam Hall Selection (Optional)</span>
                            </Label>
                            <Badge v-if="!selectedScheduleId" variant="outline" class="text-[10px] font-bold text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-950/40">
                                General Mode
                            </Badge>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                v-if="selectedScheduleId"
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-7 px-2.5 text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-semibold gap-1"
                                @click="selectedScheduleId = ''"
                            >
                                <XCircle class="w-3.5 h-3.5" />
                                <span>Clear Course (Verify Only)</span>
                            </Button>

                            <Badge v-if="invigilatedSchedules.length > 0" class="bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950 dark:text-amber-300 text-xs font-bold px-2.5 py-0.5 gap-1">
                                <Sparkles class="w-3.5 h-3.5 text-amber-600" />
                                <span>{{ invigilatedSchedules.length }} Assigned Invigilation{{ invigilatedSchedules.length > 1 ? 's' : '' }}</span>
                            </Badge>
                        </div>
                    </div>

                    <!-- Quick Tap Paper Selection Pills -->
                    <div class="space-y-1.5">
                        <p class="text-xs font-semibold text-slate-500">Quick Switch Mode / Paper:</p>
                        <div class="flex flex-wrap gap-2">
                            <!-- General Verification Mode Pill -->
                            <button
                                type="button"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 border transition-all cursor-pointer shadow-xs',
                                    selectedScheduleId === ''
                                        ? 'bg-purple-600 text-white border-purple-700 shadow-sm ring-2 ring-purple-500/30'
                                        : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-100'
                                ]"
                                @click="selectedScheduleId = ''"
                            >
                                <ShieldCheck class="w-3.5 h-3.5" />
                                <span>General Verification (No Course)</span>
                            </button>

                            <!-- Invigilated Schedules Pills -->
                            <button
                                v-for="exam in invigilatedSchedules"
                                :key="exam.id"
                                type="button"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 border transition-all cursor-pointer shadow-xs',
                                    selectedScheduleId === exam.id
                                        ? 'bg-purple-600 text-white border-purple-700 shadow-sm ring-2 ring-purple-500/30'
                                        : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-100'
                                ]"
                                @click="selectedScheduleId = exam.id"
                            >
                                <Sparkles class="w-3.5 h-3.5 text-amber-400" />
                                <span>{{ exam.course?.code }}</span>
                                <span class="text-[11px] opacity-80 font-normal">({{ exam.venue || 'Hall' }})</span>
                            </button>
                        </div>
                    </div>

                    <!-- Searchable Dropdown Selector -->
                    <div>
                        <SearchableSelect
                            v-model="selectedScheduleId"
                            :items="scheduleOptions"
                            placeholder="Select Exam Schedule (or keep empty for General Verification)"
                            search-placeholder="Search course code, title, venue..."
                            trigger-class="h-11 text-xs font-medium bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                        />
                    </div>

                    <!-- Active Exam Paper Info Summary Banner -->
                    <div v-if="activeExam" class="bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-900/60 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-lg font-bold text-purple-900 dark:text-purple-200">{{ activeExam.course?.code }}</span>
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">&bull; {{ activeExam.course?.title }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600 dark:text-slate-400 font-medium">
                                <span class="flex items-center gap-1"><Building class="w-3.5 h-3.5 text-purple-600" /> Venue: <strong>{{ activeExam.venue || 'N/A' }}</strong></span>
                                <span class="flex items-center gap-1"><Clock class="w-3.5 h-3.5 text-slate-500" /> Time: {{ activeExam.start_time }} - {{ activeExam.end_time }}</span>
                                <span class="flex items-center gap-1"><Calendar class="w-3.5 h-3.5 text-slate-500" /> Date: {{ activeExam.exam_date ? format(new Date(activeExam.exam_date), 'MMM dd, yyyy') : 'N/A' }}</span>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-3 bg-white dark:bg-slate-900 px-4 py-2 rounded-xl border border-purple-200 dark:border-purple-800 shadow-2xs">
                            <div class="text-center">
                                <p class="text-[10px] uppercase font-bold text-slate-500">Verified Present</p>
                                <p class="text-base font-black text-emerald-600 dark:text-emerald-400">{{ activeExam.attendances_count || 0 }}</p>
                            </div>
                            <div class="w-px h-7 bg-slate-200 dark:bg-slate-800"></div>
                            <div class="text-center">
                                <p class="text-[10px] uppercase font-bold text-slate-500">Hall Seats</p>
                                <p class="text-base font-black text-slate-800 dark:text-slate-200">{{ activeExam.max_capacity || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- General Verification Banner (when no specific course selected) -->
                    <div v-else class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-950/60 flex items-center justify-center text-purple-600 dark:text-purple-300 shrink-0">
                                <ShieldCheck class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900 dark:text-slate-100">General Clearance & Student Verification Mode</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">No specific course selected. Scan candidate exam cards or matric numbers to verify photo, fee clearance, and registered courses for this semester.</p>
                            </div>
                        </div>
                        <Badge variant="outline" class="text-xs font-bold px-3 py-1 bg-white dark:bg-slate-900 shrink-0 text-slate-600 dark:text-slate-300">
                            Verify Only
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- Verification Mode Switcher (Manual / Barcode vs Camera) -->
            <Card class="border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-2xl shadow-xs p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-3">
                    <Label class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                        <ScanLine class="w-4 h-4 text-emerald-600" />
                        <span>Verification Mode</span>
                    </Label>

                    <!-- Toggle Tabs -->
                    <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 text-xs font-bold rounded-lg flex items-center gap-1.5 transition-all cursor-pointer',
                                inputMode === 'manual' ? 'bg-white dark:bg-slate-900 text-purple-700 dark:text-purple-300 shadow-xs' : 'text-slate-600 dark:text-slate-400'
                            ]"
                            @click="inputMode = 'manual'"
                        >
                            <Keyboard class="w-3.5 h-3.5" />
                            <span>Type / USB Barcode Scanner</span>
                        </button>

                        <button
                            type="button"
                            :class="[
                                'px-3 py-1.5 text-xs font-bold rounded-lg flex items-center gap-1.5 transition-all cursor-pointer',
                                inputMode === 'camera' ? 'bg-white dark:bg-slate-900 text-purple-700 dark:text-purple-300 shadow-xs' : 'text-slate-600 dark:text-slate-400'
                            ]"
                            @click="inputMode = 'camera'"
                        >
                            <Camera class="w-3.5 h-3.5" />
                            <span>Live Camera Scanner</span>
                        </button>
                    </div>
                </div>

                <!-- Camera Viewfinder (only when Camera mode selected) -->
                <div v-if="inputMode === 'camera'" class="space-y-3">
                    <div v-if="isCameraActive" class="relative bg-black rounded-2xl overflow-hidden border-2 border-emerald-500 shadow-md flex flex-col items-center justify-center min-h-[220px]">
                        <video ref="videoRef" class="w-full h-60 object-cover" autoplay playsinline muted></video>
                        <div class="absolute inset-0 border-2 border-emerald-400/40 rounded-xl pointer-events-none flex items-center justify-center">
                            <div class="w-48 h-48 border-2 border-dashed border-emerald-400 animate-pulse rounded-xl flex items-center justify-center bg-black/30">
                                <span class="bg-black/80 text-emerald-300 text-[11px] px-2.5 py-1 rounded font-mono">Align Candidate QR Code</span>
                            </div>
                        </div>
                    </div>

                    <p v-if="cameraError" class="text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 p-3 rounded-xl border border-amber-200 dark:border-amber-800">
                        {{ cameraError }}
                    </p>
                </div>

                <!-- Verification Form Input -->
                <form @submit.prevent="handleVerifyPass()" class="space-y-3">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <div class="relative flex-1">
                            <Input 
                                ref="inputRef"
                                v-model="tokenInput" 
                                placeholder="Scan QR code, or type matric number / verification pass (e.g. MIUSTD2024180)..." 
                                class="pl-11 pr-10 text-sm h-12 font-mono font-bold focus-visible:ring-2 focus-visible:ring-emerald-500 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 rounded-xl shadow-2xs"
                                required
                                @keydown.enter.prevent="handleVerifyPass()"
                            />
                            <ScanLine class="absolute left-3.5 top-3.5 w-5 h-5 text-emerald-600" />
                            <button
                                v-if="tokenInput"
                                type="button"
                                class="absolute right-3.5 top-3.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                title="Clear"
                                @click="clearAndReset"
                            >
                                <XCircle class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button 
                                type="button" 
                                variant="outline"
                                class="h-12 px-3.5 text-xs font-bold border-slate-300 text-slate-700 hover:bg-slate-100 rounded-xl shrink-0 gap-1.5"
                                @click="clearAndReset"
                            >
                                <RefreshCw class="w-4 h-4 text-slate-500" />
                                <span>Clear</span>
                            </Button>

                            <Button 
                                type="submit" 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white h-12 px-6 font-bold text-sm shadow-sm rounded-xl gap-2 shrink-0" 
                                :disabled="isVerifying"
                            >
                                <Search class="w-4 h-4" />
                                <span>{{ isVerifying ? 'Verifying...' : 'Verify Candidate' }}</span>
                            </Button>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 font-medium">
                        💡 <strong>Invigilator Tip:</strong> You can type matriculation numbers directly, use a USB handheld barcode scanner gun, or switch to Live Camera Scanner above.
                    </p>
                </form>
            </Card>

            <!-- Verification Result Display Card -->
            <Card v-if="verifiedCandidate" class="p-5 rounded-2xl border-2 shadow-sm transition-all" :class="[activeExam ? (verifiedCandidate.is_cleared && isCandidateRegisteredForActiveExam ? 'bg-emerald-50/70 dark:bg-emerald-950/40 border-emerald-300' : 'bg-rose-50/70 dark:bg-rose-950/40 border-rose-300') : (verifiedCandidate.is_cleared ? 'bg-emerald-50/70 dark:bg-emerald-950/40 border-emerald-300' : 'bg-rose-50/70 dark:bg-rose-950/40 border-rose-300')]">
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-purple-100 dark:bg-purple-950 flex items-center justify-center text-purple-700 font-bold text-lg border-2 border-purple-300 overflow-hidden shrink-0 shadow-2xs">
                                <img v-if="verifiedCandidate.student.passport_photo" :src="verifiedCandidate.student.passport_photo" alt="Candidate Photo" class="w-full h-full object-cover" />
                                <span v-else>{{ verifiedCandidate.student.name.substring(0, 2).toUpperCase() }}</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-slate-100 text-lg leading-snug">{{ verifiedCandidate.student.name }}</h3>
                                <p class="text-sm font-mono text-purple-700 dark:text-purple-300 font-bold">{{ verifiedCandidate.student.matric_number }}</p>
                                <p class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-0.5">{{ verifiedCandidate.student.department }} &bull; {{ verifiedCandidate.student.level }} Level</p>
                            </div>
                        </div>

                        <!-- Status Clearance Pill -->
                        <div>
                            <Badge v-if="verifiedCandidate.is_cleared" class="bg-emerald-600 text-white px-3.5 py-1.5 text-xs gap-1.5 font-bold shadow-2xs">
                                <CheckCircle2 class="w-4 h-4" />
                                <span>FEE CLEARED</span>
                            </Badge>
                            <Badge v-else variant="destructive" class="px-3.5 py-1.5 text-xs gap-1.5 font-bold shadow-2xs">
                                <XCircle class="w-4 h-4" />
                                <span>UNCLEARED ({{ verifiedCandidate.pending_invoices }} Unpaid Invoice)</span>
                            </Badge>
                        </div>
                    </div>

                    <!-- Course Registration Banner: Specific Course Mode -->
                    <div v-if="activeExam">
                        <div v-if="isCandidateRegisteredForActiveExam" class="p-4 bg-white dark:bg-slate-900 border border-emerald-400 dark:border-emerald-800 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs">
                            <div class="flex items-center gap-3">
                                <CheckCircle2 class="w-6 h-6 text-emerald-600 shrink-0" />
                                <div class="text-xs">
                                    <p class="font-bold text-emerald-950 dark:text-emerald-300 text-sm">CONFIRMED: Candidate Registered for {{ activeExam.course?.code }}</p>
                                    <p class="text-slate-600 dark:text-slate-400 text-xs font-medium">{{ activeExam.course?.title }} ({{ activeExam.venue }})</p>
                                </div>
                            </div>

                            <!-- If already marked present -->
                            <div v-if="isCandidateAlreadyMarked" class="flex items-center gap-2">
                                <Badge class="bg-emerald-600 text-white font-bold text-xs px-3.5 py-1.5 gap-1.5 shadow-2xs">
                                    <UserCheck class="w-4 h-4" /> Marked {{ candidateAttendanceForActiveExam?.status?.toUpperCase() || 'PRESENT' }}
                                </Badge>
                            </div>

                            <!-- If not yet marked, show explicit action buttons (Option 2) -->
                            <div v-else-if="verifiedCandidate.is_cleared" class="flex items-center gap-2">
                                <Button 
                                    type="button"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2 rounded-xl gap-1.5 shadow-sm"
                                    :disabled="isMarkingAttendance"
                                    @click="markCandidateAttendance('present')"
                                >
                                    <UserCheck class="w-4 h-4" />
                                    <span>{{ isMarkingAttendance ? 'Marking...' : 'Mark as Present' }}</span>
                                </Button>
                                <Button 
                                    type="button"
                                    variant="outline"
                                    class="text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-800 hover:bg-amber-50 dark:hover:bg-amber-950/30 text-xs px-3 py-2 rounded-xl"
                                    :disabled="isMarkingAttendance"
                                    @click="markCandidateAttendance('late')"
                                >
                                    <span>Mark Late</span>
                                </Button>
                            </div>
                        </div>

                        <div v-else class="p-3.5 bg-white dark:bg-slate-900 border border-rose-400 dark:border-rose-800 rounded-xl flex items-center gap-3 text-rose-900 dark:text-rose-200 shadow-2xs">
                            <AlertTriangle class="w-7 h-7 text-rose-600 shrink-0" />
                            <div class="text-xs space-y-0.5">
                                <p class="font-bold text-rose-600 text-sm">NOT REGISTERED FOR {{ activeExam.course?.code }}!</p>
                                <p class="text-slate-700 dark:text-slate-300">Candidate <strong>{{ verifiedCandidate.student.name }}</strong> is NOT registered for {{ activeExam.course?.code }}. Candidate is ineligible to sit for this paper.</p>
                            </div>
                        </div>
                    </div>

                    <!-- General Verification Roster: No Specific Course Selected -->
                    <div v-else class="space-y-3">
                        <div class="p-3.5 bg-white dark:bg-slate-900 border rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs" :class="[verifiedCandidate.is_cleared ? 'border-emerald-300 dark:border-emerald-800' : 'border-rose-300 dark:border-rose-800']">
                            <div class="flex items-center gap-3">
                                <CheckCircle2 v-if="verifiedCandidate.is_cleared" class="w-6 h-6 text-emerald-600 shrink-0" />
                                <AlertTriangle v-else class="w-6 h-6 text-rose-600 shrink-0" />
                                <div class="text-xs">
                                    <p class="font-bold text-sm" :class="[verifiedCandidate.is_cleared ? 'text-emerald-950 dark:text-emerald-300' : 'text-rose-950 dark:text-rose-300']">
                                        {{ verifiedCandidate.is_cleared ? 'CANDIDATE VERIFIED & CLEARED' : 'CANDIDATE UNCLEARED' }}
                                    </p>
                                    <p class="text-slate-600 dark:text-slate-400 text-xs font-medium">
                                        {{ (verifiedCandidate.registered_courses || []).length }} registered courses on docket pass for this semester.
                                    </p>
                                </div>
                            </div>

                            <Badge :class="[verifiedCandidate.is_cleared ? 'bg-emerald-600' : 'bg-slate-600', 'text-white font-bold text-xs px-3 py-1']">
                                {{ (verifiedCandidate.registered_courses || []).length }} Enrolled Courses
                            </Badge>
                        </div>

                        <!-- Enrolled Courses Roster Table -->
                        <div v-if="(verifiedCandidate.registered_courses || []).length > 0" class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden bg-white dark:bg-slate-900 shadow-2xs">
                            <div class="bg-slate-50 dark:bg-slate-800/60 px-4 py-2 border-b text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 flex items-center justify-between">
                                <span>Enrolled Course</span>
                                <span>Exam Schedule</span>
                            </div>
                            <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-56 overflow-y-auto">
                                <div 
                                    v-for="course in verifiedCandidate.registered_courses" 
                                    :key="course.id"
                                    class="p-3 px-4 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-slate-50/50 dark:hover:bg-slate-800/40"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <span class="font-mono font-bold text-[#000a29] dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-[11px]">{{ course.code }}</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ course.title }}</span>
                                        <span class="text-slate-400 font-normal">({{ course.units }} Units)</span>
                                    </div>

                                    <div class="shrink-0">
                                        <template v-if="getScheduleForCourse(course.id)">
                                            <span class="text-[11px] font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                                <Calendar class="w-3.5 h-3.5 text-purple-600" />
                                                <strong>{{ format(new Date(getScheduleForCourse(course.id).exam_date), 'MMM dd') }}</strong>
                                                &bull; {{ getScheduleForCourse(course.id).venue }}
                                                &bull; {{ getScheduleForCourse(course.id).start_time }}
                                            </span>
                                        </template>
                                        <template v-else>
                                            <span class="text-[11px] text-slate-400 italic">No schedule posted</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-4 text-center text-slate-400 text-xs italic bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-dashed">
                            No registered courses found for candidate in this academic session / semester.
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Verification Roster Feed Log -->
            <Card class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b pb-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                        <History class="w-4 h-4 text-purple-600" />
                        <span>Session Attendance Verification Log</span>
                    </h3>
                    <Badge variant="outline" class="text-xs font-mono font-bold">{{ recentScansLog.length }} Candidates Logged</Badge>
                </div>

                <div v-if="recentScansLog.length > 0" class="space-y-2 max-h-56 overflow-y-auto pr-1">
                    <div 
                        v-for="item in recentScansLog" 
                        :key="item.id" 
                        class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border flex items-center justify-between gap-3 text-xs"
                    >
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-[11px] text-slate-400 font-bold">{{ item.timestamp }}</span>
                            <div>
                                <span class="font-bold text-slate-900 dark:text-slate-100">{{ item.student.name }}</span>
                                <span class="font-mono text-purple-600 dark:text-purple-400 ml-2 font-bold">{{ item.student.matric_number }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Badge v-if="item.attendance_status" class="bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 text-[10px] font-bold uppercase">
                                {{ item.attendance_status }}
                            </Badge>
                            <Badge v-else-if="item.is_cleared && (item.is_registered ?? true)" class="bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-950 dark:text-blue-300 text-[10px] font-bold">
                                CLEARED
                            </Badge>
                            <Badge v-else variant="destructive" class="text-[10px] font-bold">
                                {{ item.is_cleared ? 'UNREGISTERED' : 'UNCLEARED' }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <div v-else class="p-6 text-center text-slate-400 text-xs italic bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-dashed">
                    No student pass verified yet in this session.
                </div>
            </Card>
        </div>
    </AdminLayout>
</template>
