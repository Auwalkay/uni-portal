<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import SearchableSelect from '@/components/SearchableSelect.vue'
import {
    ArrowLeft,
    Plus,
    Trash2,
    Upload,
    FileSpreadsheet,
    BookOpen,
    GraduationCap,
    School,
    Building2,
    Layers,
    Copy,
    CheckCircle2,
    ChevronRight,
    Download
} from 'lucide-vue-next'
import Swal from 'sweetalert2'
import axios from 'axios'

const props = defineProps<{
    programme: any;
    allCourses: Array<any>;
    allProgrammes: Array<any>;
    stats: any;
}>()

const activeLevelFilter = ref<string>('ALL') // 'ALL' | '100' | '200' | '300' | '400' | '500'

const isAddModalOpen = ref(false)
const selectedCourseIds = ref<string[]>([])
const isCompulsory = ref(true)
const isSubmitting = ref(false)

const isCopyModalOpen = ref(false)
const sourceProgrammeId = ref('')

const isExcelModalOpen = ref(false)
const excelFile = ref<File | null>(null)

const formatLevelLabel = (lvl: any) => {
    const num = Number(lvl)
    if (isNaN(num)) return `${lvl} Level`
    if (num < 10) return `${num * 100} Level`
    return `${num} Level`
}

const getNormalizedLevelString = (lvl: any) => {
    const num = Number(lvl)
    if (isNaN(num)) return String(lvl)
    return num < 10 ? String(num * 100) : String(num)
}

const courseOptions = computed(() => {
    return props.allCourses.map(c => {
        const lvlStr = getNormalizedLevelString(c.level)
        return {
            value: c.id,
            label: `${c.code} - ${c.title} (${c.units} Units | ${lvlStr}L Sem ${c.semester})`
        }
    })
})

// Calculate metric aggregations
const totalCourses = computed(() => props.programme.courses?.length || 0)
const totalCreditUnits = computed(() => {
    if (!props.programme.courses) return 0
    return props.programme.courses.reduce((sum: number, c: any) => sum + (Number(c.units) || 0), 0)
})

const compulsoryUnits = computed(() => {
    if (!props.programme.courses) return 0
    return props.programme.courses
        .filter((c: any) => Boolean(c.pivot?.is_compulsory))
        .reduce((sum: number, c: any) => sum + (Number(c.units) || 0), 0)
})

const electiveUnits = computed(() => {
    if (!props.programme.courses) return 0
    return props.programme.courses
        .filter((c: any) => !Boolean(c.pivot?.is_compulsory))
        .reduce((sum: number, c: any) => sum + (Number(c.units) || 0), 0)
})

// Group courses by Level (100L, 200L, 300L, 400L, 500L) and Semester with level filtering
const coursesByLevelAndSemester = computed(() => {
    if (!props.programme.courses) return {}
    const grouped: Record<string, any[]> = {}
    
    props.programme.courses.forEach((c: any) => {
        const normLvl = getNormalizedLevelString(c.level)
        if (activeLevelFilter.value !== 'ALL' && normLvl !== activeLevelFilter.value) {
            return
        }

        const key = `${formatLevelLabel(c.level)} - Semester ${c.semester}`
        if (!grouped[key]) {
            grouped[key] = []
        }
        grouped[key].push(c)
    })

    return grouped
})

const addCoursesToProgramme = async () => {
    if (selectedCourseIds.value.length === 0) {
        Swal.fire('Warning', 'Please select at least one course.', 'warning')
        return
    }

    isSubmitting.value = true
    try {
        await axios.post(route('admin.academics.programmes.courses.store', props.programme.id), {
            course_ids: selectedCourseIds.value,
            is_compulsory: isCompulsory.value,
        })
        isAddModalOpen.value = false
        selectedCourseIds.value = []
        Swal.fire('Success', 'Courses added to curriculum successfully.', 'success')
        router.reload()
    } catch (err: any) {
        Swal.fire('Error', err.response?.data?.message || 'Failed to add courses.', 'error')
    } finally {
        isSubmitting.value = false
    }
}

const copyCurriculum = async () => {
    if (!sourceProgrammeId.value) {
        Swal.fire('Warning', 'Please select a source programme to copy from.', 'warning')
        return
    }

    isSubmitting.value = true
    try {
        const res = await axios.post(route('admin.academics.programmes.courses.import', props.programme.id), {
            source_programme_id: sourceProgrammeId.value
        })
        isCopyModalOpen.value = false
        Swal.fire('Curriculum Cloned', res.data.message, 'success')
        router.reload()
    } catch (err: any) {
        Swal.fire('Cloning Failed', err.response?.data?.message || 'Failed to copy curriculum.', 'error')
    } finally {
        isSubmitting.value = false
    }
}

const removeCourse = async (courseId: string) => {
    const confirm = await Swal.fire({
        title: 'Remove Course?',
        text: 'This course will be removed from this programme curriculum.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        confirmButtonColor: '#ef4444'
    })

    if (confirm.isConfirmed) {
        try {
            await axios.delete(route('admin.academics.programmes.courses.destroy', [props.programme.id, courseId]))
            Swal.fire('Removed', 'Course removed from curriculum.', 'success')
            router.reload()
        } catch (err: any) {
            Swal.fire('Error', 'Failed to remove course.', 'error')
        }
    }
}

const handleExcelUpload = async () => {
    if (!excelFile.value) {
        Swal.fire('Warning', 'Please choose an Excel file.', 'warning')
        return
    }

    const formData = new FormData()
    formData.append('file', excelFile.value)

    isSubmitting.value = true
    try {
        const res = await axios.post(route('admin.academics.programmes.courses.import_excel', props.programme.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        isExcelModalOpen.value = false
        Swal.fire('Import Complete', res.data.message, 'success')
        router.reload()
    } catch (err: any) {
        Swal.fire('Import Failed', err.response?.data?.message || 'Failed to import curriculum.', 'error')
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <Head :title="`${programme.name} - Curriculum Handbook`" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Breadcrumbs Nav Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <nav class="flex items-center gap-2 text-sm font-medium text-neutral-500 dark:text-neutral-400">
                    <Link href="/admin/academics/faculties" class="hover:text-neutral-900 dark:hover:text-white transition-colors">
                        Academic Structure
                    </Link>
                    <ChevronRight class="w-4 h-4 text-neutral-400" />
                    <Link href="/admin/academics/programmes" class="hover:text-neutral-900 dark:hover:text-white transition-colors">
                        Programmes
                    </Link>
                    <ChevronRight class="w-4 h-4 text-neutral-400" />
                    <span class="text-neutral-900 dark:text-white font-semibold truncate max-w-xs">
                        {{ programme.name }}
                    </span>
                </nav>

                <div class="flex flex-wrap items-center gap-2">
                    <Button variant="outline" as-child size="sm" class="gap-1.5 text-xs font-semibold">
                        <Link href="/admin/academics/programmes">
                            <ArrowLeft class="w-3.5 h-3.5" />
                            Back to Programmes
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" as-child class="gap-1.5 text-xs font-semibold">
                        <a :href="`/admin/academics/programmes/${programme.id}/courses/export`" download>
                            <Download class="w-3.5 h-3.5 text-emerald-600" />
                            Export Curriculum
                        </a>
                    </Button>
                    <Button variant="outline" size="sm" @click="isCopyModalOpen = true" class="gap-1.5 text-xs font-semibold">
                        <Copy class="w-3.5 h-3.5 text-sky-600" />
                        Clone Curriculum
                    </Button>
                    <Button variant="outline" size="sm" @click="isExcelModalOpen = true" class="gap-1.5 text-xs font-semibold">
                        <FileSpreadsheet class="w-3.5 h-3.5 text-emerald-600" />
                        Excel Import
                    </Button>
                    <Button size="sm" @click="isAddModalOpen = true" class="bg-rose-600 hover:bg-rose-700 text-white gap-1.5 text-xs font-semibold">
                        <Plus class="w-3.5 h-3.5" />
                        Add Courses
                    </Button>
                </div>
            </div>

            <!-- Programme Executive Banner -->
            <div class="bg-white dark:bg-neutral-900 p-6 sm:p-8 rounded-2xl border border-neutral-200/80 dark:border-neutral-800 shadow-xs relative overflow-hidden">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                    <div class="space-y-3 max-w-2xl">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold tracking-wide uppercase shadow-xs"
                                :class="{
                                    'bg-sky-100 text-sky-800 dark:bg-sky-950/80 dark:text-sky-300': programme.type === 'UG',
                                    'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300': programme.type === 'PG',
                                    'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300': programme.type === 'PHD',
                                }"
                            >
                                {{ programme.type }} Degree Programme
                            </span>
                            <Badge variant="outline" class="text-xs font-semibold border-neutral-300 dark:border-neutral-700">
                                <School class="w-3.5 h-3.5 mr-1 text-neutral-500" />
                                {{ programme.department?.name || 'Department' }}
                            </Badge>
                            <Badge variant="outline" class="text-xs font-semibold border-neutral-300 dark:border-neutral-700">
                                <Building2 class="w-3.5 h-3.5 mr-1 text-neutral-500" />
                                {{ programme.department?.faculty?.name || 'Faculty' }}
                            </Badge>
                        </div>

                        <div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 dark:text-white tracking-tight">
                                {{ programme.name }}
                            </h1>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                                Official degree curriculum handbook and course credit distribution.
                            </p>
                        </div>
                    </div>

                    <!-- Metric Cards Summary -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="bg-neutral-50 dark:bg-neutral-800/80 p-4 rounded-xl border border-neutral-200/60 dark:border-neutral-700/60 text-center">
                            <span class="block text-2xl font-extrabold text-rose-600 dark:text-rose-400">
                                {{ totalCourses }}
                            </span>
                            <span class="text-xs text-neutral-500 dark:text-neutral-400 font-semibold uppercase tracking-wider">
                                Total Courses
                            </span>
                        </div>

                        <div class="bg-neutral-50 dark:bg-neutral-800/80 p-4 rounded-xl border border-neutral-200/60 dark:border-neutral-700/60 text-center">
                            <span class="block text-2xl font-extrabold text-neutral-900 dark:text-white">
                                {{ totalCreditUnits }}
                            </span>
                            <span class="text-xs text-neutral-500 dark:text-neutral-400 font-semibold uppercase tracking-wider">
                                Total Units
                            </span>
                        </div>

                        <div class="bg-neutral-50 dark:bg-neutral-800/80 p-4 rounded-xl border border-neutral-200/60 dark:border-neutral-700/60 text-center">
                            <span class="block text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">
                                {{ compulsoryUnits }}
                            </span>
                            <span class="text-xs text-neutral-500 dark:text-neutral-400 font-semibold uppercase tracking-wider">
                                Compulsory
                            </span>
                        </div>

                        <div class="bg-neutral-50 dark:bg-neutral-800/80 p-4 rounded-xl border border-neutral-200/60 dark:border-neutral-700/60 text-center">
                            <span class="block text-2xl font-extrabold text-sky-600 dark:text-sky-400">
                                {{ electiveUnits }}
                            </span>
                            <span class="text-xs text-neutral-500 dark:text-neutral-400 font-semibold uppercase tracking-wider">
                                Electives
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Level Filter Bar -->
            <div class="flex items-center justify-between gap-4 bg-white dark:bg-neutral-900 p-2.5 rounded-2xl border border-neutral-200/80 dark:border-neutral-800 shadow-xs overflow-x-auto">
                <div class="flex items-center gap-1.5">
                    <button
                        v-for="lvl in ['ALL', '100', '200', '300', '400', '500']"
                        :key="lvl"
                        @click="activeLevelFilter = lvl"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all duration-150 cursor-pointer whitespace-nowrap"
                        :class="[
                            activeLevelFilter === lvl
                                ? 'bg-rose-600 text-white shadow-xs'
                                : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'
                        ]"
                    >
                        {{ lvl === 'ALL' ? 'All Levels' : `${lvl} Level` }}
                    </button>
                </div>
            </div>

            <!-- Empty State Options when no courses exist -->
            <div
                v-if="Object.keys(coursesByLevelAndSemester).length === 0"
                class="bg-white dark:bg-neutral-900 p-12 text-center rounded-2xl border border-neutral-200 dark:border-neutral-800 shadow-xs space-y-6"
            >
                <div class="w-16 h-16 bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-2xl flex items-center justify-center mx-auto">
                    <BookOpen class="w-8 h-8" />
                </div>
                <div>
                    <h3 class="text-xl font-bold text-neutral-900 dark:text-white">No Curriculum Courses Added Yet</h3>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 max-w-md mx-auto mt-1">
                        Build the degree curriculum handbook for {{ programme.name }} using any of the 3 setup options below:
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-3xl mx-auto pt-2">
                    <button
                        @click="isAddModalOpen = true"
                        class="p-5 text-left bg-neutral-50 hover:bg-rose-50/60 dark:bg-neutral-800/60 dark:hover:bg-neutral-800 border border-neutral-200/80 dark:border-neutral-700 rounded-xl transition-all duration-200 group cursor-pointer"
                    >
                        <div class="w-10 h-10 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <Plus class="w-5 h-5" />
                        </div>
                        <h4 class="font-bold text-neutral-900 dark:text-white text-sm">Add Courses Manually</h4>
                        <p class="text-xs text-neutral-500 mt-1">Search and select individual courses from the university catalog.</p>
                    </button>

                    <button
                        @click="isExcelModalOpen = true"
                        class="p-5 text-left bg-neutral-50 hover:bg-emerald-50/60 dark:bg-neutral-800/60 dark:hover:bg-neutral-800 border border-neutral-200/80 dark:border-neutral-700 rounded-xl transition-all duration-200 group cursor-pointer"
                    >
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <FileSpreadsheet class="w-5 h-5" />
                        </div>
                        <h4 class="font-bold text-neutral-900 dark:text-white text-sm">Excel Bulk Import</h4>
                        <p class="text-xs text-neutral-500 mt-1">Upload a curriculum spreadsheet formatted by level and semester.</p>
                    </button>

                    <button
                        @click="isCopyModalOpen = true"
                        class="p-5 text-left bg-neutral-50 hover:bg-sky-50/60 dark:bg-neutral-800/60 dark:hover:bg-neutral-800 border border-neutral-200/80 dark:border-neutral-700 rounded-xl transition-all duration-200 group cursor-pointer"
                    >
                        <div class="w-10 h-10 rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                            <Copy class="w-5 h-5" />
                        </div>
                        <h4 class="font-bold text-neutral-900 dark:text-white text-sm">Clone from Programme</h4>
                        <p class="text-xs text-neutral-500 mt-1">Copy standard general courses from an existing department degree.</p>
                    </button>
                </div>
            </div>

            <!-- Curriculum Table Cards grouped by Level & Semester -->
            <div v-else class="space-y-6">
                <Card
                    v-for="(courses, groupTitle) in coursesByLevelAndSemester"
                    :key="groupTitle"
                    class="border-neutral-200/80 dark:border-neutral-800 shadow-xs"
                >
                    <CardHeader class="pb-3 bg-neutral-50/60 dark:bg-neutral-900/50 border-b border-neutral-200/60 dark:border-neutral-800">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-base font-bold text-neutral-900 dark:text-white flex items-center gap-2">
                                <BookOpen class="w-4 h-4 text-rose-500" />
                                {{ groupTitle }}
                            </CardTitle>
                            <Badge variant="secondary" class="font-mono text-xs font-bold">
                                {{ courses.reduce((s, c) => s + (Number(c.units) || 0), 0) }} Total Credit Units
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent">
                                    <TableHead class="font-bold">Course Code</TableHead>
                                    <TableHead class="font-bold">Course Title</TableHead>
                                    <TableHead class="font-bold">Credit Units</TableHead>
                                    <TableHead class="font-bold">Requirement</TableHead>
                                    <TableHead class="text-right font-bold">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="c in courses" :key="c.id" class="hover:bg-neutral-50/60 dark:hover:bg-neutral-800/40">
                                    <TableCell class="font-mono font-bold text-rose-600 dark:text-rose-400">
                                        {{ c.code }}
                                    </TableCell>
                                    <TableCell class="font-medium text-neutral-900 dark:text-white">
                                        {{ c.title }}
                                    </TableCell>
                                    <TableCell class="font-semibold">
                                        {{ c.units }} Units
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold"
                                            :class="c.pivot?.is_compulsory ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300'"
                                        >
                                            {{ c.pivot?.is_compulsory ? 'Compulsory' : 'Elective' }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="removeCourse(c.id)"
                                            class="text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/50"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <!-- Add Courses Modal -->
            <Dialog :open="isAddModalOpen" @update:open="isAddModalOpen = $event">
                <DialogContent class="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Add Courses to Curriculum</DialogTitle>
                        <DialogDescription>Select courses from the university course catalog to link to {{ programme.name }}.</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4 py-3">
                        <div class="space-y-1.5">
                            <Label>Select Courses</Label>
                            <SearchableSelect
                                v-model="selectedCourseIds"
                                :options="courseOptions"
                                placeholder="Search & select courses..."
                                multiple
                            />
                        </div>

                        <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800">
                            <div>
                                <Label class="font-medium">Compulsory Requirement</Label>
                                <p class="text-xs text-neutral-500">Mark as mandatory course for degree completion</p>
                            </div>
                            <Switch v-model:checked="isCompulsory" />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isAddModalOpen = false">Cancel</Button>
                        <Button @click="addCoursesToProgramme" :disabled="isSubmitting" class="bg-rose-600 hover:bg-rose-700 text-white">
                            Add Courses
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Clone / Copy Curriculum Modal -->
            <Dialog :open="isCopyModalOpen" @update:open="isCopyModalOpen = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Clone Curriculum Structure</DialogTitle>
                        <DialogDescription>Copy all course links from an existing programme into {{ programme.name }}.</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4 py-3">
                        <div class="space-y-1.5">
                            <Label>Source Programme</Label>
                            <Select v-model="sourceProgrammeId">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Source Programme" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="p in allProgrammes" :key="p.id" :value="p.id">
                                        {{ p.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isCopyModalOpen = false">Cancel</Button>
                        <Button @click="copyCurriculum" :disabled="isSubmitting" class="bg-rose-600 hover:bg-rose-700 text-white">
                            Copy Courses
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Excel Import Modal -->
            <Dialog :open="isExcelModalOpen" @update:open="isExcelModalOpen = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Import Curriculum via Excel</DialogTitle>
                        <DialogDescription>Upload an Excel spreadsheet containing course codes, titles, units, levels, and compulsory status.</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4 py-3">
                        <div class="space-y-1.5">
                            <Label>Excel Spreadsheet (.xlsx, .csv)</Label>
                            <Input
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                @change="(e) => excelFile = e.target.files[0]"
                            />
                        </div>

                        <div class="text-xs text-neutral-500 bg-neutral-50 dark:bg-neutral-900 p-3 rounded-xl border border-neutral-200 dark:border-neutral-800">
                            <a :href="route().has('admin.academics.programmes.courses.import_template') ? route('admin.academics.programmes.courses.import_template') : '/admin/academics/programmes/courses/import-template'" class="text-rose-600 hover:underline font-semibold flex items-center gap-1">
                                <FileSpreadsheet class="w-3.5 h-3.5" />
                                Download Excel Sample Template
                            </a>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isExcelModalOpen = false">Cancel</Button>
                        <Button @click="handleExcelUpload" :disabled="isSubmitting" class="bg-rose-600 hover:bg-rose-700 text-white">
                            Start Import
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
