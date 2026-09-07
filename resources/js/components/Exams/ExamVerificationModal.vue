<script setup lang="ts">
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { 
    QrCode, ScanLine, Search, CheckCircle2, XCircle, Calendar, Clock, Building, ShieldCheck, UserCheck, AlertTriangle, Sparkles, Camera, CameraOff, RefreshCw, X, Check
} from 'lucide-vue-next';
import { format } from 'date-fns';

import SearchableSelect from '@/components/SearchableSelect.vue';

interface Props {
    open: boolean;
    selectedScheduleId?: string;
    schedules: any[];
    verifiedCandidate: any;
    isVerifying: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:open', 'verify', 'markAttendance']);

const page = usePage();
const currentUser = computed(() => (page.props as any).auth?.user);

const inputRef = ref<HTMLInputElement | null>(null);

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

const filterOnlyInvigilated = ref(false);

const internalTokenInput = ref('');
const internalSelectedScheduleId = ref(props.selectedScheduleId || '');

// Camera State
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

        // Auto detect QR via BarcodeDetector if browser supports it
        if ('BarcodeDetector' in window) {
            const detector = new (window as any).BarcodeDetector({ formats: ['qr_code', 'code_128'] });
            scanInterval = setInterval(async () => {
                if (videoRef.value && isCameraActive.value && videoRef.value.readyState === 4) {
                    try {
                        const barcodes = await detector.detect(videoRef.value);
                        if (barcodes.length > 0 && barcodes[0].rawValue) {
                            const scannedValue = barcodes[0].rawValue.trim();
                            internalTokenInput.value = scannedValue;
                            stopCamera();
                            submitVerification();
                        }
                    } catch (e) {
                        // ignore frame detection error
                    }
                }
            }, 300);
        }
    } catch (err: any) {
        console.error('Camera access error:', err);
        cameraError.value = 'Could not access camera device. Please use manual typing or USB barcode gun.';
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

const toggleCamera = () => {
    if (isCameraActive.value) {
        stopCamera();
    } else {
        startCamera();
    }
};

onUnmounted(() => {
    stopCamera();
});

watch(() => props.selectedScheduleId, (newVal) => {
    internalSelectedScheduleId.value = newVal || '';
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
        }, 150);
    });
};

watch(() => props.open, (isOpenVal) => {
    if (isOpenVal) {
        focusInput();
        if (!props.selectedScheduleId && invigilatedSchedules.value.length > 0) {
            internalSelectedScheduleId.value = invigilatedSchedules.value[0].id;
        }
    } else {
        stopCamera();
    }
});

const clearAndFocusNext = () => {
    internalTokenInput.value = '';
    focusInput();
};

const scheduleOptions = computed(() => {
    const list = props.schedules || [];
    const filteredList = filterOnlyInvigilated.value 
        ? list.filter(isUserInvigilatorForExam)
        : list;

    const invigilated = filteredList.filter(isUserInvigilatorForExam);
    const nonInvigilated = filteredList.filter(e => !isUserInvigilatorForExam(e));

    const options: { value: string; label: string }[] = [
        { 
            value: '', 
            label: invigilated.length > 0 
                ? `⭐ Auto-Detect Registered Courses (${invigilated.length} Invigilated Paper${invigilated.length > 1 ? 's' : ''})` 
                : 'All Scheduled Exams (Auto-Detect Registered Courses)' 
        }
    ];

    if (invigilated.length > 0) {
        invigilated.forEach(exam => {
            options.push({
                value: exam.id,
                label: `⭐ [MY INVIGILATION] ${exam.course?.code || ''} - ${exam.course?.title || ''} (${exam.venue || ''}${exam.exam_date ? ' • ' + format(new Date(exam.exam_date), 'MMM dd') : ''}${exam.start_time ? ' ' + exam.start_time : ''})`
            });
        });
    }

    if (!filterOnlyInvigilated.value) {
        nonInvigilated.forEach(exam => {
            options.push({
                value: exam.id,
                label: `${exam.course?.code || ''} - ${exam.course?.title || ''} (${exam.venue || ''}${exam.exam_date ? ' • ' + format(new Date(exam.exam_date), 'MMM dd') : ''}${exam.start_time ? ' ' + exam.start_time : ''})`
            });
        });
    }

    return options;
});

const isOpen = computed({
    get: () => props.open,
    set: (val: boolean) => emit('update:open', val)
});

const activeSelectedExam = computed(() => {
    if (!internalSelectedScheduleId.value) return null;
    return props.schedules.find(e => e.id === internalSelectedScheduleId.value) || null;
});

const isCandidateRegisteredForActiveExam = computed(() => {
    if (!props.verifiedCandidate || !activeSelectedExam.value) return null;
    const regCourseIds = props.verifiedCandidate.registered_course_ids || [];
    return regCourseIds.includes(activeSelectedExam.value.course_id);
});

const submitVerification = () => {
    if (!internalTokenInput.value.trim()) return;
    emit('verify', internalTokenInput.value.trim());
};

const handleMarkAttendance = (scheduleId: string, studentId: string) => {
    emit('markAttendance', scheduleId, studentId);
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[760px] md:max-w-[800px] max-h-[92vh] overflow-y-auto w-[96vw] p-6 rounded-2xl">
            <DialogHeader class="border-b pb-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                            <QrCode class="w-7 h-7" />
                        </div>
                        <div>
                            <DialogTitle class="text-xl font-bold text-slate-900 dark:text-slate-100">Invigilator QR Verification & Attendance</DialogTitle>
                            <DialogDescription class="text-xs text-slate-500">
                                Scan candidate QR code, type verification token / matric number, or use your camera device scanner.
                            </DialogDescription>
                        </div>
                    </div>
                </div>
            </DialogHeader>

            <div class="space-y-5 py-2">
                <!-- Invigilating Exam Paper Selection & Quick Chips -->
                <div class="space-y-3 p-4 bg-gradient-to-br from-purple-50/80 to-indigo-50/50 dark:from-purple-950/30 dark:to-indigo-950/20 rounded-2xl border border-purple-200/80 dark:border-purple-900/60 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <Label class="text-xs font-black uppercase tracking-wider text-purple-800 dark:text-purple-300 flex items-center gap-2">
                            <span>Select Exam Paper (Invigilation Hall)</span>
                            <Badge v-if="invigilatedSchedules.length > 0" class="bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950 dark:text-amber-300 text-[10px] gap-1 px-2 py-0.5 font-bold">
                                <Sparkles class="w-3 h-3 text-amber-600" />
                                <span>{{ invigilatedSchedules.length }} Assigned Invigilation{{ invigilatedSchedules.length > 1 ? 's' : '' }}</span>
                            </Badge>
                        </Label>

                        <button 
                            v-if="invigilatedSchedules.length > 0" 
                            type="button" 
                            class="text-xs font-bold text-purple-700 hover:text-purple-900 dark:text-purple-300 underline transition-colors cursor-pointer"
                            @click="filterOnlyInvigilated = !filterOnlyInvigilated"
                        >
                            {{ filterOnlyInvigilated ? 'Show All Exams' : 'Filter My Invigilations Only' }}
                        </button>
                    </div>

                    <!-- Quick Tap Pills for My Invigilated Exams -->
                    <div v-if="invigilatedSchedules.length > 0" class="space-y-1">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Quick Tap Paper Select:</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="exam in invigilatedSchedules"
                                :key="exam.id"
                                type="button"
                                :class="[
                                    'px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-2 border transition-all cursor-pointer shadow-xs',
                                    internalSelectedScheduleId === exam.id
                                        ? 'bg-purple-600 text-white border-purple-700 shadow-md ring-2 ring-purple-500/30'
                                        : 'bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 border-purple-200 dark:border-purple-900 hover:bg-purple-100/60'
                                ]"
                                @click="internalSelectedScheduleId = exam.id"
                            >
                                <Sparkles class="w-3.5 h-3.5 text-amber-400" />
                                <span>{{ exam.course?.code }}</span>
                                <span class="text-[10px] opacity-80 font-normal">({{ exam.venue || 'Hall' }})</span>
                            </button>

                            <button
                                type="button"
                                :class="[
                                    'px-3 py-1.5 rounded-xl text-xs font-medium flex items-center gap-1 border transition-all cursor-pointer',
                                    internalSelectedScheduleId === ''
                                        ? 'bg-purple-600 text-white border-purple-700 shadow-xs'
                                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 hover:bg-slate-100'
                                ]"
                                @click="internalSelectedScheduleId = ''"
                            >
                                <span>Auto-Detect All Papers</span>
                            </button>
                        </div>
                    </div>

                    <!-- Full Dropdown Selector -->
                    <SearchableSelect
                        v-model="internalSelectedScheduleId"
                        :items="scheduleOptions"
                        placeholder="Select Exam Paper"
                        search-placeholder="Search by course code, title, venue..."
                        trigger-class="h-10 text-xs font-medium bg-white dark:bg-slate-900 border-purple-200 dark:border-purple-900"
                    />

                    <div v-if="activeSelectedExam && isUserInvigilatorForExam(activeSelectedExam)" class="flex items-center gap-1.5 text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 px-3 py-1.5 rounded-lg border border-amber-200 dark:border-amber-800">
                        <Sparkles class="w-4 h-4 text-amber-600 shrink-0" />
                        <span>ACTIVE INVIGILATION HALL: You are assigned to invigilate {{ activeSelectedExam.course?.code }} ({{ activeSelectedExam.venue }}).</span>
                    </div>
                </div>

                <!-- Input & Scanner Toolbar -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <Label class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <ScanLine class="w-4 h-4 text-emerald-600" />
                            <span>Scan Candidate QR Code or Enter Matric Number</span>
                        </Label>

                        <div class="flex items-center gap-2">
                            <Button 
                                type="button"
                                variant="outline"
                                size="sm"
                                :class="[
                                    'h-8 text-xs font-bold gap-1.5 border transition-all',
                                    isCameraActive 
                                        ? 'bg-rose-50 text-rose-700 border-rose-300 dark:bg-rose-950 dark:text-rose-300' 
                                        : 'bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-300'
                                ]"
                                @click="toggleCamera"
                            >
                                <CameraOff v-if="isCameraActive" class="w-3.5 h-3.5" />
                                <Camera v-else class="w-3.5 h-3.5" />
                                <span>{{ isCameraActive ? 'Close Camera' : 'Camera Scanner' }}</span>
                            </Button>
                        </div>
                    </div>

                    <!-- Live Camera Viewfinder (if active) -->
                    <div v-if="isCameraActive" class="relative bg-black rounded-2xl overflow-hidden border-2 border-emerald-500 shadow-inner flex flex-col items-center justify-center min-h-[220px]">
                        <video ref="videoRef" class="w-full h-56 object-cover" autoplay playsinline muted></video>
                        <div class="absolute inset-0 border-2 border-emerald-400/60 rounded-xl pointer-events-none flex items-center justify-center">
                            <div class="w-48 h-48 border-2 border-dashed border-emerald-400 animate-pulse rounded-lg flex items-center justify-center">
                                <span class="bg-black/60 text-white text-[10px] px-2 py-1 rounded font-mono">Align QR Code Inside Box</span>
                            </div>
                        </div>
                        <Button 
                            type="button" 
                            variant="secondary" 
                            size="sm" 
                            class="absolute top-2 right-2 h-7 text-[11px] bg-black/70 text-white hover:bg-black" 
                            @click="stopCamera"
                        >
                            <X class="w-3 h-3 mr-1" /> Close
                        </Button>
                    </div>

                    <p v-if="cameraError" class="text-xs text-rose-600 font-semibold italic">{{ cameraError }}</p>

                    <!-- Scan / Input Form -->
                    <form @submit.prevent="submitVerification" class="flex flex-col sm:flex-row gap-2">
                        <div class="relative flex-1">
                            <Input 
                                ref="inputRef"
                                v-model="internalTokenInput" 
                                placeholder="Scan QR token, type verification code or matric number (e.g. MIUSTD2024180)..." 
                                class="pl-10 pr-10 text-sm h-12 font-mono font-semibold focus-visible:ring-2 focus-visible:ring-emerald-500 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 shadow-xs"
                                required
                                @keydown.enter.prevent="submitVerification"
                            />
                            <ScanLine class="absolute left-3.5 top-3.5 w-5 h-5 text-emerald-600" />
                            <button
                                v-if="internalTokenInput"
                                type="button"
                                class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                title="Clear input"
                                @click="clearAndFocusNext"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button 
                                type="button" 
                                variant="outline"
                                class="h-12 px-3 text-xs font-bold border-slate-300 text-slate-700 hover:bg-slate-100 shrink-0 gap-1.5"
                                title="Clear and focus for next scan"
                                @click="clearAndFocusNext"
                            >
                                <RefreshCw class="w-4 h-4 text-slate-500" />
                                <span>Reset / Next</span>
                            </Button>

                            <Button 
                                type="submit" 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white h-12 px-6 font-bold text-sm shadow-md gap-2 shrink-0" 
                                :disabled="isVerifying"
                            >
                                <Search class="w-4 h-4" />
                                <span>{{ isVerifying ? 'Verifying...' : 'Verify Candidate' }}</span>
                            </Button>
                        </div>
                    </form>
                </div>

                <!-- Verification Result Card -->
                <div v-if="verifiedCandidate" class="space-y-4 bg-slate-50 dark:bg-slate-900/80 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <!-- Candidate Header -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-full bg-purple-100 dark:bg-purple-950 flex items-center justify-center text-purple-700 font-bold text-xl border-3 border-purple-300 overflow-hidden shrink-0 shadow-sm">
                                <img v-if="verifiedCandidate.student.passport_photo" :src="verifiedCandidate.student.passport_photo" alt="Candidate Photo" class="w-full h-full object-cover" />
                                <span v-else>{{ verifiedCandidate.student.name.substring(0, 2).toUpperCase() }}</span>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-slate-100 text-lg leading-snug">{{ verifiedCandidate.student.name }}</h3>
                                <p class="text-sm font-mono text-purple-600 dark:text-purple-400 font-black">{{ verifiedCandidate.student.matric_number }}</p>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">{{ verifiedCandidate.student.department }} &bull; {{ verifiedCandidate.student.level }} Level</p>
                            </div>
                        </div>

                        <!-- Clearance Badge -->
                        <div class="shrink-0">
                            <Badge v-if="verifiedCandidate.is_cleared" class="bg-emerald-100 text-emerald-900 border-emerald-300 dark:bg-emerald-950/80 dark:text-emerald-300 px-3.5 py-1.5 text-xs gap-1.5 font-bold shadow-xs">
                                <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                                <span>ELIGIBLE / FEE CLEARED</span>
                            </Badge>
                            <Badge v-else variant="destructive" class="px-3.5 py-1.5 text-xs gap-1.5 font-bold shadow-xs">
                                <XCircle class="w-4 h-4" />
                                <span>UNCLEARED ({{ verifiedCandidate.pending_invoices }} Unpaid Invoice)</span>
                            </Badge>
                        </div>
                    </div>

                    <!-- Specific Exam Course Registration Status Banner -->
                    <div v-if="activeSelectedExam">
                        <div v-if="isCandidateRegisteredForActiveExam" class="p-4 bg-emerald-50 dark:bg-emerald-950/50 border-2 border-emerald-400 dark:border-emerald-800 rounded-xl flex items-center justify-between shadow-xs">
                            <div class="flex items-center gap-3">
                                <CheckCircle2 class="w-6 h-6 text-emerald-600 shrink-0" />
                                <div class="text-xs">
                                    <p class="font-black text-emerald-950 dark:text-emerald-100 text-sm">CONFIRMED: Candidate Registered for {{ activeSelectedExam.course?.code }}</p>
                                    <p class="text-emerald-800 dark:text-emerald-300 text-xs mt-0.5">{{ activeSelectedExam.course?.title }} ({{ activeSelectedExam.venue }})</p>
                                </div>
                            </div>

                            <Button 
                                size="sm" 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs gap-1.5 shrink-0 shadow-sm"
                                @click="handleMarkAttendance(activeSelectedExam.id, verifiedCandidate.student.id)"
                            >
                                <UserCheck class="w-4 h-4" />
                                <span>Mark Present Now</span>
                            </Button>
                        </div>

                        <div v-else class="p-4 bg-rose-50 dark:bg-rose-950/60 border-2 border-rose-400 rounded-xl flex items-center gap-3 text-rose-900 dark:text-rose-200 shadow-xs">
                            <AlertTriangle class="w-7 h-7 text-rose-600 shrink-0" />
                            <div class="text-xs space-y-0.5">
                                <p class="font-black text-rose-950 dark:text-rose-100 text-base">NOT REGISTERED FOR THIS COURSE!</p>
                                <p class="text-rose-800 dark:text-rose-300">Candidate <strong>{{ verifiedCandidate.student.name }}</strong> is NOT registered for {{ activeSelectedExam.course?.code }} ({{ activeSelectedExam.course?.title }}). Student is ineligible to sit for this paper.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Registered Courses & Attendance Log -->
                    <div class="space-y-2 pt-1">
                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-500">Registered Courses & Attendance Log</h4>
                        
                        <div v-if="verifiedCandidate.schedules && verifiedCandidate.schedules.length > 0" class="space-y-2">
                            <div 
                                v-for="sched in verifiedCandidate.schedules" 
                                :key="sched.id"
                                :class="[
                                    'p-3.5 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-all',
                                    sched.id === internalSelectedScheduleId ? 'bg-purple-50/90 dark:bg-purple-950/50 border-purple-400 dark:border-purple-800 ring-2 ring-purple-500/30' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'
                                ]"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 dark:text-slate-100 text-sm">{{ sched.course?.code }}</span>
                                        <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">&bull; {{ sched.course?.title }}</span>
                                        <Badge v-if="sched.id === internalSelectedScheduleId" class="bg-purple-600 text-white text-[10px] px-2 py-0.5 font-bold">SELECTED HALL EXAM</Badge>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-500 mt-1 font-medium">
                                        <span class="flex items-center gap-1"><Calendar class="w-3.5 h-3.5 text-purple-500" /> {{ sched.exam_date ? format(new Date(sched.exam_date), 'MMM dd, yyyy') : 'N/A' }}</span>
                                        <span class="flex items-center gap-1"><Clock class="w-3.5 h-3.5 text-slate-400" /> {{ sched.start_time }} - {{ sched.end_time }}</span>
                                        <span class="flex items-center gap-1"><Building class="w-3.5 h-3.5 text-slate-400" /> {{ sched.venue }}</span>
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    <div v-if="sched.attendances && sched.attendances.length > 0" class="flex items-center gap-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100/80 dark:bg-emerald-950/60 px-3.5 py-1.5 rounded-xl border border-emerald-300 dark:border-emerald-800 shadow-xs">
                                        <ShieldCheck class="w-4 h-4 text-emerald-600" />
                                        <span>PRESENT / VERIFIED</span>
                                    </div>
                                    <Button 
                                        v-else 
                                        size="sm" 
                                        class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold gap-1.5 shadow-sm"
                                        @click="handleMarkAttendance(sched.id, verifiedCandidate.student.id)"
                                    >
                                        <UserCheck class="w-3.5 h-3.5" />
                                        <span>Mark Present</span>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-4 bg-white dark:bg-slate-900 rounded-xl border text-center text-xs text-slate-500 italic">
                            Candidate has no registered courses scheduled for examination.
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="border-t pt-3 flex items-center justify-between sm:justify-between">
                <Button type="button" variant="outline" size="sm" class="text-xs font-bold" @click="clearAndFocusNext">
                    <RefreshCw class="w-3.5 h-3.5 mr-1 text-slate-500" /> Reset Input
                </Button>
                <Button type="button" variant="secondary" size="sm" class="text-xs font-bold" @click="isOpen = false">Close Modal</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
