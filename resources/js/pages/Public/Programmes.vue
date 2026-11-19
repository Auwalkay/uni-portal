<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    Building2,
    ChevronDown,
    ChevronUp,
    Clock,
    GraduationCap,
    Search,
    Sparkles,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface FeeItem {
    id: string;
    name: string;
    amount: number;
    is_one_time: boolean;
}

interface SchedulePayload {
    total_fee: number;
    items: FeeItem[];
}

interface ProgrammeItem {
    id: string;
    name: string;
    type: string;
    academic_stream: 'UG' | 'PG';
    duration: number | null;
    department_id: string | null;
    department_name: string;
    faculty_id: string | null;
    faculty_name: string;
    session_name: string;
    total_fee: number;
    fee_items: FeeItem[];
    schedules_by_mode?: {
        UTME: SchedulePayload;
        DE: SchedulePayload;
        Transfer: SchedulePayload;
    } | null;
}

interface FacultyOption {
    id: string;
    name: string;
}

const props = defineProps<{
    programmes: ProgrammeItem[];
    faculties: FacultyOption[];
    ugSessionName: string | null;
    pgSessionName: string | null;
}>();

const search = ref('');
const selectedStream = ref<'ALL' | 'UG' | 'PG'>('ALL');
const selectedEntryMode = ref<'UTME' | 'DE' | 'Transfer'>('UTME');
const selectedFaculty = ref<string>('ALL');
const expandedIds = ref<Record<string, boolean>>({});

const getActiveSchedule = (prog: ProgrammeItem): { total_fee: number; items: FeeItem[] } => {
    if (prog.academic_stream === 'UG' && prog.schedules_by_mode) {
        return prog.schedules_by_mode[selectedEntryMode.value] ?? prog.schedules_by_mode.UTME;
    }
    return { total_fee: prog.total_fee, items: prog.fee_items };
};

const toggleBreakdown = (id: string) => {
    expandedIds.value[id] = !expandedIds.value[id];
};

const filteredProgrammes = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.programmes.filter((prog) => {
        if (selectedStream.value !== 'ALL' && prog.academic_stream !== selectedStream.value) {
            return false;
        }
        if (selectedFaculty.value !== 'ALL' && prog.faculty_id !== selectedFaculty.value) {
            return false;
        }
        if (q) {
            return (
                prog.name.toLowerCase().includes(q) ||
                prog.department_name.toLowerCase().includes(q) ||
                prog.faculty_name.toLowerCase().includes(q) ||
                prog.type.toLowerCase().includes(q)
            );
        }
        return true;
    });
});

const ugCount = computed(() => props.programmes.filter((p) => p.academic_stream === 'UG').length);
const pgCount = computed(() => props.programmes.filter((p) => p.academic_stream === 'PG').length);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);
};
</script>

<template>
    <Head title="Programmes & School Fees — MIU Nigeria" />

    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <!-- Top Navigation -->
        <nav class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 dark:border-slate-800 dark:bg-slate-950/95">
            <div class="mx-auto flex h-18 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm dark:border-slate-800">
                        <img src="/miu-logo.jpeg" alt="MIU Logo" class="h-full w-full object-contain" />
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-base font-black tracking-tight text-slate-900 uppercase dark:text-white">MIU Nigeria</span>
                        <span class="text-[10px] font-semibold tracking-widest text-slate-500 uppercase">Admissions & Tuition</span>
                    </div>
                </Link>

                <div class="flex items-center gap-3">
                    <template v-if="$page.props.auth?.user">
                        <Link
                            :href="route('dashboard')"
                            class="inline-flex h-9 items-center justify-center rounded-full bg-slate-900 px-5 text-xs font-bold tracking-wider text-white uppercase transition hover:bg-slate-800 dark:bg-white dark:text-slate-900"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="px-3 py-2 text-xs font-bold tracking-wider text-slate-600 uppercase transition hover:text-red-600 dark:text-slate-300"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="route('register')"
                            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-full bg-red-600 px-5 text-xs font-bold tracking-wider text-white uppercase shadow-sm transition hover:bg-red-700"
                        >
                            Apply Now
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero Header -->
        <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/60">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div class="max-w-2xl space-y-2">
                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                            Explore Our Programmes & School Fees
                        </h1>
                        <p class="text-sm text-slate-600 sm:text-base dark:text-slate-400">
                            Browse accredited Undergraduate and Postgraduate programmes at Mewar International University Nigeria, with transparent tuition and compulsory fee breakdowns.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 text-xs">
                        <div v-if="ugSessionName" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-800 dark:bg-slate-900">
                            <span class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase">UG Session</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ ugSessionName }}</span>
                        </div>
                        <div v-if="pgSessionName" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 dark:border-slate-800 dark:bg-slate-900">
                            <span class="block text-[10px] font-bold tracking-wider text-slate-500 uppercase">PG Session</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ pgSessionName }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Filters Bar -->
            <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between dark:border-slate-800 dark:bg-slate-900">
                <!-- Stream Tabs -->
                <div class="inline-flex flex-wrap gap-1.5 rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                    <button
                        type="button"
                        @click="selectedStream = 'ALL'"
                        :class="[
                            'rounded-lg px-3.5 py-2 text-xs font-bold transition',
                            selectedStream === 'ALL'
                                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-white'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
                        ]"
                    >
                        All Programmes ({{ programmes.length }})
                    </button>
                    <button
                        type="button"
                        @click="selectedStream = 'UG'"
                        :class="[
                            'rounded-lg px-3.5 py-2 text-xs font-bold transition',
                            selectedStream === 'UG'
                                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-white'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
                        ]"
                    >
                        Undergraduate ({{ ugCount }})
                    </button>
                    <button
                        type="button"
                        @click="selectedStream = 'PG'"
                        :class="[
                            'rounded-lg px-3.5 py-2 text-xs font-bold transition',
                            selectedStream === 'PG'
                                ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-white'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
                        ]"
                    >
                        Postgraduate ({{ pgCount }})
                    </button>
                </div>

                <!-- Search & Faculty -->
                <div class="flex flex-1 flex-col gap-3 sm:flex-row lg:max-w-xl">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search programme, department, or degree..."
                            class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pr-3 pl-10 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:bg-white dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                        />
                    </div>

                    <select
                        v-model="selectedFaculty"
                        class="h-10 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-900 outline-none transition focus:border-red-500 focus:bg-white sm:w-56 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                    >
                        <option value="ALL">All Faculties</option>
                        <option v-for="fac in faculties" :key="fac.id" :value="fac.id">
                            {{ fac.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- UG Entry Mode Selector (UTME / Direct Entry / Transfer) -->
            <div
                v-if="selectedStream !== 'PG'"
                class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <span class="text-xs font-bold tracking-wider text-slate-500 uppercase">
                    Undergraduate Entry Mode
                </span>
                <div class="inline-flex flex-wrap gap-1.5 rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                    <button
                        type="button"
                        @click="selectedEntryMode = 'UTME'"
                        :class="[
                            'rounded-lg px-3 py-1.5 text-xs font-bold transition',
                            selectedEntryMode === 'UTME'
                                ? 'bg-red-600 text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white',
                        ]"
                    >
                        UTME (100L)
                    </button>
                    <button
                        type="button"
                        @click="selectedEntryMode = 'DE'"
                        :class="[
                            'rounded-lg px-3 py-1.5 text-xs font-bold transition',
                            selectedEntryMode === 'DE'
                                ? 'bg-red-600 text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white',
                        ]"
                    >
                        Direct Entry (DE · 200L)
                    </button>
                    <button
                        type="button"
                        @click="selectedEntryMode = 'Transfer'"
                        :class="[
                            'rounded-lg px-3 py-1.5 text-xs font-bold transition',
                            selectedEntryMode === 'Transfer'
                                ? 'bg-red-600 text-white shadow-sm'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white',
                        ]"
                    >
                        Transfer
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="filteredProgrammes.length === 0"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900"
            >
                <GraduationCap class="mb-3 h-10 w-10 text-slate-400" />
                <h2 class="text-base font-bold text-slate-900 dark:text-white">No matching programmes found</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try clearing your search or switching faculty/stream filters.</p>
            </div>

            <!-- Programmes Grid -->
            <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="prog in filteredProgrammes"
                    :key="prog.id"
                    class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                >
                    <div>
                        <!-- Top Badges -->
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase',
                                    prog.academic_stream === 'PG'
                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                                        : 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
                                ]"
                            >
                                {{ prog.academic_stream === 'PG' ? 'Postgraduate' : 'Undergraduate' }} · {{ prog.type }}
                            </span>

                            <span v-if="prog.duration" class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                <Clock class="h-3.5 w-3.5" />
                                {{ prog.duration }} {{ prog.duration === 1 ? 'Year' : 'Years' }}
                            </span>
                        </div>

                        <!-- Title & Dept -->
                        <h2 class="text-lg font-bold leading-snug text-slate-900 dark:text-white">
                            {{ prog.name }}
                        </h2>
                        <div class="mt-2 space-y-1 text-xs text-slate-600 dark:text-slate-400">
                            <p class="flex items-center gap-1.5">
                                <BookOpen class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                <span>{{ prog.department_name }}</span>
                            </p>
                            <p class="flex items-center gap-1.5">
                                <Building2 class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                <span>{{ prog.faculty_name }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Fee Summary & Breakdown -->
                    <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <div class="flex items-baseline justify-between">
                            <div>
                                <span class="block text-[11px] font-semibold tracking-wider text-slate-500 uppercase">
                                    School Fees ({{ prog.session_name }}<template v-if="prog.academic_stream === 'UG'"> · {{ selectedEntryMode }}</template>)
                                </span>
                                <span v-if="getActiveSchedule(prog).items.length > 0" class="text-xl font-black text-slate-900 dark:text-white">
                                    {{ formatCurrency(getActiveSchedule(prog).total_fee) }}
                                </span>
                                <span v-else class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                    Fee schedule pending
                                </span>
                            </div>

                            <button
                                v-if="getActiveSchedule(prog).items.length > 0"
                                type="button"
                                @click="toggleBreakdown(prog.id)"
                                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40"
                            >
                                <span>{{ expandedIds[prog.id] ? 'Hide' : 'Breakdown' }}</span>
                                <ChevronUp v-if="expandedIds[prog.id]" class="h-3.5 w-3.5" />
                                <ChevronDown v-else class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <!-- Expandable Itemized Breakdown -->
                        <div
                            v-if="expandedIds[prog.id] && getActiveSchedule(prog).items.length > 0"
                            class="mt-3 space-y-1.5 rounded-xl bg-slate-50 p-3 text-xs dark:bg-slate-800/70"
                        >
                            <div
                                v-for="item in getActiveSchedule(prog).items"
                                :key="item.id"
                                class="flex items-center justify-between gap-2 text-slate-700 dark:text-slate-300"
                            >
                                <span class="truncate">
                                    {{ item.name }}
                                    <span v-if="item.is_one_time" class="text-[10px] text-slate-400">(One-time)</span>
                                </span>
                                <span class="shrink-0 font-semibold text-slate-900 dark:text-white">
                                    {{ formatCurrency(item.amount) }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <Link
                                :href="route('register')"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold tracking-wider text-white uppercase transition hover:bg-red-600 dark:bg-slate-800 dark:hover:bg-red-600"
                            >
                                Apply for this Programme
                                <ArrowRight class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </article>
            </div>
        </main>
    </div>
</template>
