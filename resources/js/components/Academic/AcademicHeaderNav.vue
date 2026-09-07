<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
    Building2,
    School,
    GraduationCap,
    BookOpen,
    Layers,
    Plus,
    LayoutDashboard
} from 'lucide-vue-next'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    activeTab: {
        type: String,
        required: true // 'faculties' | 'departments' | 'programmes' | 'courses' | 'units'
    }
})

const emit = defineEmits(['primaryAction'])

const page = usePage()

const navItems = [
    {
        id: 'faculties',
        name: 'Faculties',
        href: '/admin/academics/faculties',
        icon: Building2,
        count: computed(() => props.stats?.faculties ?? 0)
    },
    {
        id: 'departments',
        name: 'Departments',
        href: '/admin/academics/departments',
        icon: School,
        count: computed(() => props.stats?.departments ?? 0)
    },
    {
        id: 'programmes',
        name: 'Programmes',
        href: '/admin/academics/programmes',
        icon: GraduationCap,
        count: computed(() => props.stats?.programmes ?? 0)
    },
    {
        id: 'courses',
        name: 'Courses',
        href: '/admin/academics/courses',
        icon: BookOpen,
        count: computed(() => props.stats?.courses ?? 0)
    },
    {
        id: 'units',
        name: 'Units',
        href: '/admin/academics/units',
        icon: Layers,
        count: computed(() => props.stats?.units ?? 0)
    }
]
</script>

<template>
    <div class="space-y-6 mb-8">
        <!-- Top Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-neutral-900 p-6 rounded-2xl border border-neutral-200/80 dark:border-neutral-800 shadow-xs">
            <div>
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-xl">
                        <LayoutDashboard class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">
                            Academic Structure
                        </h1>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">
                            Manage Faculties, Departments, Programmes, Courses & Units.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Action Button -->
            <div class="flex items-center gap-3">
                <slot name="actions">
                    <button
                        @click="emit('primaryAction')"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 rounded-xl shadow-xs transition-all duration-150 cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Add {{ activeTab ? activeTab.slice(0, -1).replace(/^./, str => str.toUpperCase()) : 'Item' }}</span>
                    </button>
                </slot>
            </div>
        </div>

        <!-- Sub-Header Navigation Tabs -->
        <div class="flex items-center overflow-x-auto gap-2 p-1.5 bg-neutral-100 dark:bg-neutral-900/90 rounded-2xl border border-neutral-200/60 dark:border-neutral-800 no-scrollbar">
            <Link
                v-for="item in navItems"
                :key="item.id"
                :href="item.href"
                class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 whitespace-nowrap cursor-pointer"
                :class="[
                    activeTab === item.id
                        ? 'bg-white dark:bg-neutral-800 text-rose-600 dark:text-rose-400 shadow-xs border border-neutral-200/50 dark:border-neutral-700'
                        : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-neutral-800/50'
                ]"
            >
                <component :is="item.icon" class="w-4 h-4" />
                <span>{{ item.name }}</span>
                <span
                    class="px-2 py-0.5 text-xs font-bold rounded-full transition-colors"
                    :class="[
                        activeTab === item.id
                            ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300'
                            : 'bg-neutral-200/70 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'
                    ]"
                >
                    {{ item.count }}
                </span>
            </Link>
        </div>
    </div>
</template>
