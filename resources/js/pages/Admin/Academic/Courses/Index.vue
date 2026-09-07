<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { debounce } from 'lodash'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AcademicHeaderNav from '@/components/Academic/AcademicHeaderNav.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Switch } from '@/components/ui/switch'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Search, Plus, Pencil, FileSpreadsheet, Upload, BookOpen, Download } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import axios from 'axios'
import { computed } from 'vue'

const props = defineProps<{
    courses: {
        data: Array<any>;
        links: Array<any>;
    };
    faculties: Array<any>;
    departments: Array<any>;
    programmes: Array<any>;
    stats: any;
    filters: {
        search?: string;
        faculty_id?: string;
        department_id?: string;
        level?: string;
        semester?: string;
    };
}>()

const search = ref(props.filters?.search || '')
const facultyId = ref(props.filters?.faculty_id || 'ALL')
const departmentId = ref(props.filters?.department_id || 'ALL')
const level = ref(props.filters?.level || 'ALL')
const semester = ref(props.filters?.semester || 'ALL')

const exportUrl = computed(() => {
    const params = new URLSearchParams()
    if (search.value) params.append('search', search.value)
    if (facultyId.value && facultyId.value !== 'ALL') params.append('faculty_id', facultyId.value)
    if (departmentId.value && departmentId.value !== 'ALL') params.append('department_id', departmentId.value)
    if (level.value && level.value !== 'ALL') params.append('level', level.value)
    if (semester.value && semester.value !== 'ALL') params.append('semester', semester.value)
    const queryString = params.toString()
    return `/admin/academics/courses/export${queryString ? '?' + queryString : ''}`
})

const applyFilters = debounce(() => {
    router.get(route('admin.academics.courses'), {
        search: search.value,
        faculty_id: facultyId.value === 'ALL' ? '' : facultyId.value,
        department_id: departmentId.value === 'ALL' ? '' : departmentId.value,
        level: level.value === 'ALL' ? '' : level.value,
        semester: semester.value === 'ALL' ? '' : semester.value,
    }, { preserveState: true, replace: true })
}, 300)

watch([search, facultyId, departmentId, level, semester], applyFilters)

// Modal State
const isModalOpen = ref(false)
const modalMode = ref<'create' | 'edit'>('create')

const form = useForm({
    type: 'course',
    id: '',
    code: '',
    title: '',
    units: 3,
    level: 100,
    semester: '1',
    department_id: '',
    programme_id: '',
})

const openCreateModal = () => {
    modalMode.value = 'create'
    form.reset()
    form.type = 'course'
    form.units = 3
    form.level = 100
    form.semester = '1'
    form.clearErrors()
    isModalOpen.value = true
}

const openEditModal = (c: any) => {
    modalMode.value = 'edit'
    form.type = 'course'
    form.id = c.id
    form.code = c.code
    form.title = c.title
    form.units = c.units
    form.level = c.level
    form.semester = String(c.semester)
    form.department_id = c.department_id || ''
    form.programme_id = c.programme_id || ''
    form.clearErrors()
    isModalOpen.value = true
}

const submitForm = () => {
    const routeName = modalMode.value === 'create' ? 'admin.academics.store' : 'admin.academics.update'
    form.post(route(routeName), {
        onSuccess: () => {
            isModalOpen.value = false
            Swal.fire('Saved!', `Course ${modalMode.value === 'create' ? 'created' : 'updated'} successfully.`, 'success')
        }
    })
}

const toggleStatus = (id: string, currentStatus: boolean) => {
    router.post(route('admin.academics.toggle'), {
        type: 'course',
        id,
        is_active: !currentStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Course status updated',
                showConfirmButton: false,
                timer: 2000
            })
        }
    })
}

// Excel Import
const isExcelModalOpen = ref(false)
const excelFile = ref<File | null>(null)
const isSubmitting = ref(false)

const handleExcelUpload = async () => {
    if (!excelFile.value) {
        Swal.fire('Warning', 'Please select an Excel file.', 'warning')
        return
    }

    const formData = new FormData()
    formData.append('file', excelFile.value)

    isSubmitting.value = true
    try {
        const res = await axios.post(route('admin.academics.courses.import_excel'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        isExcelModalOpen.value = false
        Swal.fire('Import Complete', res.data.message, 'success')
        router.reload()
    } catch (err: any) {
        Swal.fire('Import Failed', err.response?.data?.message || 'Failed to import courses.', 'error')
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <Head title="Course Catalog - Academic Structure" />

    <AdminLayout>
        <div class="space-y-6">
            <AcademicHeaderNav
                :stats="stats"
                active-tab="courses"
                @primary-action="openCreateModal"
            />

            <!-- Filter & Table Card -->
            <Card class="border-neutral-200/80 dark:border-neutral-800 shadow-xs">
                <CardHeader class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4">
                    <div>
                        <CardTitle class="text-lg font-bold">University Course Catalog</CardTitle>
                        <CardDescription>Browse, filter, and manage courses across all faculties, levels, and semesters.</CardDescription>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" as-child class="gap-1.5 text-xs font-semibold">
                            <a :href="exportUrl" download>
                                <Download class="w-3.5 h-3.5 text-emerald-600" />
                                Export Courses
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" @click="isExcelModalOpen = true" class="gap-1.5 text-xs font-semibold">
                            <FileSpreadsheet class="w-3.5 h-3.5 text-emerald-600" />
                            Excel Import
                        </Button>
                    </div>
                </CardHeader>

                <!-- Filter Controls -->
                <div class="px-6 pb-4 flex flex-wrap items-center gap-3">
                    <Select v-model="level">
                        <SelectTrigger class="w-36 bg-neutral-50/50 dark:bg-neutral-900 border-neutral-200 dark:border-neutral-800">
                            <SelectValue placeholder="All Levels" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ALL">All Levels</SelectItem>
                            <SelectItem value="100">100 Level</SelectItem>
                            <SelectItem value="200">200 Level</SelectItem>
                            <SelectItem value="300">300 Level</SelectItem>
                            <SelectItem value="400">400 Level</SelectItem>
                            <SelectItem value="500">500 Level</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="semester">
                        <SelectTrigger class="w-36 bg-neutral-50/50 dark:bg-neutral-900 border-neutral-200 dark:border-neutral-800">
                            <SelectValue placeholder="Semester" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ALL">All Semesters</SelectItem>
                            <SelectItem value="1">1st Semester</SelectItem>
                            <SelectItem value="2">2nd Semester</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="departmentId">
                        <SelectTrigger class="w-48 bg-neutral-50/50 dark:bg-neutral-900 border-neutral-200 dark:border-neutral-800">
                            <SelectValue placeholder="Department" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ALL">All Departments</SelectItem>
                            <SelectItem v-for="d in departments" :key="d.id" :value="d.id">
                                {{ d.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
                        <Input
                            v-model="search"
                            placeholder="Search course by code or title..."
                            class="pl-9 bg-neutral-50/50 dark:bg-neutral-900 border-neutral-200 dark:border-neutral-800"
                        />
                    </div>
                </div>

                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent">
                                    <TableHead class="font-bold">Course Code</TableHead>
                                    <TableHead class="font-bold">Course Title</TableHead>
                                    <TableHead class="font-bold">Credit Units</TableHead>
                                    <TableHead class="font-bold">Level & Semester</TableHead>
                                    <TableHead class="font-bold">Department</TableHead>
                                    <TableHead class="font-bold">Status</TableHead>
                                    <TableHead class="text-right font-bold">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="c in courses.data"
                                    :key="c.id"
                                    class="hover:bg-neutral-50/60 dark:hover:bg-neutral-800/50 transition-colors"
                                >
                                    <TableCell class="font-mono font-semibold text-rose-600 dark:text-rose-400">
                                        {{ c.code }}
                                    </TableCell>
                                    <TableCell class="font-medium text-neutral-900 dark:text-white">
                                        {{ c.title }}
                                    </TableCell>
                                    <TableCell class="font-semibold">
                                        {{ c.units }} Units
                                    </TableCell>
                                    <TableCell>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
                                            {{ c.level }}L (Sem {{ c.semester }})
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-neutral-700 dark:text-neutral-300">
                                        {{ c.department?.name || 'N/A' }}
                                    </TableCell>
                                    <TableCell>
                                        <Switch
                                            :checked="Boolean(c.is_active)"
                                            @update:checked="toggleStatus(c.id, c.is_active)"
                                        />
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openEditModal(c)"
                                            class="hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        >
                                            <Pencil class="w-4 h-4 mr-1.5" />
                                            Edit
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!courses.data || courses.data.length === 0">
                                    <TableCell colspan="7" class="text-center py-12 text-neutral-500">
                                        No courses found matching criteria.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="courses.links && courses.links.length > 3" class="flex justify-center mt-6 gap-1">
                        <Button
                            v-for="(link, i) in courses.links"
                            :key="i"
                            :variant="link.active ? 'default' : 'outline'"
                            :disabled="!link.url"
                            size="sm"
                            as-child
                        >
                            <a :href="link.url || '#'" v-html="link.label" />
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Create / Edit Modal -->
            <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{{ modalMode === 'create' ? 'Add New Course' : 'Edit Course' }}</DialogTitle>
                        <DialogDescription>Define course code, title, units, level, and semester.</DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-2">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <Label for="c_code">Course Code</Label>
                                <Input
                                    id="c_code"
                                    v-model="form.code"
                                    placeholder="CSC 101"
                                    :class="{ 'border-rose-500': form.errors.code }"
                                />
                                <p v-if="form.errors.code" class="text-xs text-rose-500">{{ form.errors.code }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="c_units">Credit Units</Label>
                                <Input
                                    id="c_units"
                                    type="number"
                                    min="1"
                                    max="10"
                                    v-model="form.units"
                                    :class="{ 'border-rose-500': form.errors.units }"
                                />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="c_title">Course Title</Label>
                            <Input
                                id="c_title"
                                v-model="form.title"
                                placeholder="Introduction to Computer Science"
                                :class="{ 'border-rose-500': form.errors.title }"
                            />
                            <p v-if="form.errors.title" class="text-xs text-rose-500">{{ form.errors.title }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <Label>Level</Label>
                                <Select v-model.number="form.level">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="100">100 Level</SelectItem>
                                        <SelectItem :value="200">200 Level</SelectItem>
                                        <SelectItem :value="300">300 Level</SelectItem>
                                        <SelectItem :value="400">400 Level</SelectItem>
                                        <SelectItem :value="500">500 Level</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Semester</Label>
                                <Select v-model="form.semester">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Semester" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">1st Semester</SelectItem>
                                        <SelectItem value="2">2nd Semester</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Department</Label>
                            <Select v-model="form.department_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Department" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="d in departments" :key="d.id" :value="d.id">
                                        {{ d.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.department_id" class="text-xs text-rose-500">{{ form.errors.department_id }}</p>
                        </div>

                        <DialogFooter class="mt-6">
                            <Button type="button" variant="outline" @click="isModalOpen = false">Cancel</Button>
                            <Button type="submit" :disabled="form.processing">
                                {{ modalMode === 'create' ? 'Create Course' : 'Save Changes' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Excel Import Modal -->
            <Dialog :open="isExcelModalOpen" @update:open="isExcelModalOpen = $event">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Import Global Courses via Excel</DialogTitle>
                        <DialogDescription>Upload an Excel file to bulk-import courses into the university catalog.</DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4 py-3">
                        <div class="space-y-1.5">
                            <Label>Excel File (.xlsx, .csv)</Label>
                            <Input
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                @change="(e) => excelFile = e.target.files[0]"
                            />
                        </div>

                        <div class="text-xs text-neutral-500 bg-neutral-50 dark:bg-neutral-900 p-3 rounded-xl border border-neutral-200 dark:border-neutral-800">
                            <a :href="route('admin.academics.courses.import_template')" class="text-rose-600 hover:underline font-semibold flex items-center gap-1">
                                <FileSpreadsheet class="w-3.5 h-3.5" />
                                Download Course Import Excel Sample
                            </a>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isExcelModalOpen = false">Cancel</Button>
                        <Button @click="handleExcelUpload" :disabled="isSubmitting">
                            Start Bulk Import
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
