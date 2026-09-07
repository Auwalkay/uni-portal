<script setup lang="ts">
import { Head, router, useForm, Link } from '@inertiajs/vue3'
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
import { Search, Plus, Pencil, GraduationCap, BookOpen, Download } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import { computed } from 'vue'

const props = defineProps<{
    programmes: {
        data: Array<any>;
        links: Array<any>;
    };
    faculties: Array<any>;
    departments: Array<any>;
    stats: any;
    filters: {
        search?: string;
        faculty_id?: string;
        department_id?: string;
        program_type?: string;
    };
}>()

const search = ref(props.filters?.search || '')
const departmentId = ref(props.filters?.department_id || 'ALL')
const programType = ref(props.filters?.program_type || 'ALL')

const exportUrl = computed(() => {
    const params = new URLSearchParams()
    if (search.value) params.append('search', search.value)
    if (departmentId.value && departmentId.value !== 'ALL') params.append('department_id', departmentId.value)
    if (programType.value && programType.value !== 'ALL') params.append('program_type', programType.value)
    const queryString = params.toString()
    return `/admin/academics/programmes/export${queryString ? '?' + queryString : ''}`
})

const applyFilters = debounce(() => {
    router.get(route('admin.academics.programmes'), {
        search: search.value,
        department_id: departmentId.value === 'ALL' ? '' : departmentId.value,
        program_type: programType.value === 'ALL' ? '' : programType.value,
    }, { preserveState: true, replace: true })
}, 300)

watch([search, departmentId, programType], applyFilters)

// Modal State
const isModalOpen = ref(false)
const modalMode = ref<'create' | 'edit'>('create')

const form = useForm({
    type: 'programme',
    id: '',
    name: '',
    program_type: 'UG',
    department_id: '',
    scholarship_eligible: true,
})

const openCreateModal = () => {
    modalMode.value = 'create'
    form.reset()
    form.type = 'programme'
    form.program_type = 'UG'
    form.scholarship_eligible = true
    form.clearErrors()
    isModalOpen.value = true
}

const openEditModal = (prog: any) => {
    modalMode.value = 'edit'
    form.type = 'programme'
    form.id = prog.id
    form.name = prog.name
    form.program_type = prog.type || 'UG'
    form.department_id = prog.department_id || ''
    form.scholarship_eligible = Boolean(prog.scholarship_eligible)
    form.clearErrors()
    isModalOpen.value = true
}

const submitForm = () => {
    const routeName = modalMode.value === 'create' ? 'admin.academics.store' : 'admin.academics.update'
    form.post(route(routeName), {
        onSuccess: () => {
            isModalOpen.value = false
            Swal.fire('Saved!', `Programme ${modalMode.value === 'create' ? 'created' : 'updated'} successfully.`, 'success')
        }
    })
}

const toggleStatus = (id: string, currentStatus: boolean) => {
    router.post(route('admin.academics.toggle'), {
        type: 'programme',
        id,
        is_active: !currentStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Programme status updated',
                showConfirmButton: false,
                timer: 2000
            })
        }
    })
}
</script>

<template>
    <Head title="Programmes - Academic Structure" />

    <AdminLayout>
        <div class="space-y-6">
            <AcademicHeaderNav
                :stats="stats"
                active-tab="programmes"
                @primary-action="openCreateModal"
            />

            <!-- Filter & Table Card -->
            <Card class="border-neutral-200/80 dark:border-neutral-800 shadow-xs">
                <CardHeader class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4">
                    <div>
                        <CardTitle class="text-lg font-bold">Degree Programmes</CardTitle>
                        <CardDescription>Manage undergraduate (UG), postgraduate (PG), and doctoral (PhD) academic programmes.</CardDescription>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <Button variant="outline" size="sm" as-child class="gap-1.5 text-xs font-semibold">
                            <a :href="exportUrl" download>
                                <Download class="w-3.5 h-3.5 text-emerald-600" />
                                Export Programmes
                            </a>
                        </Button>

                        <Select v-model="programType">
                            <SelectTrigger class="w-36 bg-neutral-50/50 dark:bg-neutral-900 border-neutral-200 dark:border-neutral-800">
                                <SelectValue placeholder="Degree Level" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL">All Degree Levels</SelectItem>
                                <SelectItem value="UG">Undergraduate (UG)</SelectItem>
                                <SelectItem value="PG">Postgraduate (PG)</SelectItem>
                                <SelectItem value="PHD">Doctorate (PhD)</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="departmentId">
                            <SelectTrigger class="w-48 bg-neutral-50/50 dark:bg-neutral-900 border-neutral-200 dark:border-neutral-800">
                                <SelectValue placeholder="All Departments" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL">All Departments</SelectItem>
                                <SelectItem v-for="d in departments" :key="d.id" :value="d.id">
                                    {{ d.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div class="relative w-full sm:w-60">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
                            <Input
                                v-model="search"
                                placeholder="Search programme..."
                                class="pl-9 bg-neutral-50/50 dark:bg-neutral-900 border-neutral-200 dark:border-neutral-800"
                            />
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent">
                                    <TableHead class="font-bold">Programme Name</TableHead>
                                    <TableHead class="font-bold">Degree Type</TableHead>
                                    <TableHead class="font-bold">Department</TableHead>
                                    <TableHead class="font-bold">Faculty</TableHead>
                                    <TableHead class="font-bold">Courses</TableHead>
                                    <TableHead class="font-bold">Status</TableHead>
                                    <TableHead class="text-right font-bold">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="prog in programmes.data"
                                    :key="prog.id"
                                    class="hover:bg-neutral-50/60 dark:hover:bg-neutral-800/50 transition-colors"
                                >
                                    <TableCell class="font-medium text-neutral-900 dark:text-white">
                                        {{ prog.name }}
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                            :class="{
                                                'bg-sky-100 text-sky-800 dark:bg-sky-950/80 dark:text-sky-300': prog.type === 'UG',
                                                'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300': prog.type === 'PG',
                                                'bg-rose-100 text-rose-800 dark:bg-rose-950/80 dark:text-rose-300': prog.type === 'PHD',
                                            }"
                                        >
                                            {{ prog.type }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-neutral-700 dark:text-neutral-300">
                                        {{ prog.department?.name || 'N/A' }}
                                    </TableCell>
                                    <TableCell class="text-neutral-500">
                                        {{ prog.department?.faculty?.name || 'N/A' }}
                                    </TableCell>
                                    <TableCell>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
                                            {{ prog.courses_count }} Courses
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <Switch
                                            :checked="Boolean(prog.is_active)"
                                            @update:checked="toggleStatus(prog.id, prog.is_active)"
                                        />
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                as-child
                                                class="text-xs"
                                            >
                                                <Link :href="route().has('admin.academics.programmes.show') ? route('admin.academics.programmes.show', prog.id) : `/admin/academics/programmes/${prog.id}/show`">
                                                    <BookOpen class="w-3.5 h-3.5 mr-1" />
                                                    Curriculum
                                                </Link>
                                            </Button>

                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openEditModal(prog)"
                                            >
                                                <Pencil class="w-4 h-4 mr-1" />
                                                Edit
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!programmes.data || programmes.data.length === 0">
                                    <TableCell colspan="7" class="text-center py-12 text-neutral-500">
                                        No programmes found matching criteria.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="programmes.links && programmes.links.length > 3" class="flex justify-center mt-6 gap-1">
                        <Button
                            v-for="(link, i) in programmes.links"
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
                        <DialogTitle>{{ modalMode === 'create' ? 'Add New Programme' : 'Edit Programme' }}</DialogTitle>
                        <DialogDescription>Define programme name, degree type, and department.</DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="prog_name">Programme Name</Label>
                            <Input
                                id="prog_name"
                                v-model="form.name"
                                placeholder="B.Sc. Computer Science"
                                :class="{ 'border-rose-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="prog_type">Degree Type</Label>
                            <Select v-model="form.program_type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="UG">Undergraduate (UG)</SelectItem>
                                    <SelectItem value="PG">Postgraduate (PG)</SelectItem>
                                    <SelectItem value="PHD">Doctorate (PhD)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="prog_dept">Department</Label>
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

                        <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800">
                            <div>
                                <Label class="font-medium text-neutral-900 dark:text-white">Scholarship Eligible</Label>
                                <p class="text-xs text-neutral-500">Allow students in this programme to apply for university scholarships</p>
                            </div>
                            <Switch v-model:checked="form.scholarship_eligible" />
                        </div>

                        <DialogFooter class="mt-6">
                            <Button type="button" variant="outline" @click="isModalOpen = false">Cancel</Button>
                            <Button type="submit" :disabled="form.processing">
                                {{ modalMode === 'create' ? 'Create Programme' : 'Save Changes' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
