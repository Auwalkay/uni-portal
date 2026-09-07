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
import { Search, Plus, Pencil, School, Download } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import { computed } from 'vue'

const props = defineProps<{
    departments: {
        data: Array<any>;
        links: Array<any>;
    };
    faculties: Array<any>;
    stats: any;
    filters: {
        search?: string;
        faculty_id?: string;
    };
}>()

const search = ref(props.filters?.search || '')
const facultyId = ref(props.filters?.faculty_id || 'ALL')

const exportUrl = computed(() => {
    const params = new URLSearchParams()
    if (search.value) params.append('search', search.value)
    if (facultyId.value && facultyId.value !== 'ALL') params.append('faculty_id', facultyId.value)
    const queryString = params.toString()
    return `/admin/academics/departments/export${queryString ? '?' + queryString : ''}`
})

const applyFilters = debounce(() => {
    router.get(route('admin.academics.departments'), {
        search: search.value,
        faculty_id: facultyId.value === 'ALL' ? '' : facultyId.value
    }, { preserveState: true, replace: true })
}, 300)

watch(search, applyFilters)
watch(facultyId, applyFilters)

// Modal State
const isModalOpen = ref(false)
const modalMode = ref<'create' | 'edit'>('create')

const form = useForm({
    type: 'department',
    id: '',
    name: '',
    code: '',
    faculty_id: '',
    is_academic: true,
})

const openCreateModal = () => {
    modalMode.value = 'create'
    form.reset()
    form.type = 'department'
    form.is_academic = true
    form.clearErrors()
    isModalOpen.value = true
}

const openEditModal = (department: any) => {
    modalMode.value = 'edit'
    form.type = 'department'
    form.id = department.id
    form.name = department.name
    form.code = department.code
    form.faculty_id = department.faculty_id || ''
    form.is_academic = Boolean(department.is_academic)
    form.clearErrors()
    isModalOpen.value = true
}

const submitForm = () => {
    const routeName = modalMode.value === 'create' ? 'admin.academics.store' : 'admin.academics.update'
    form.post(route(routeName), {
        onSuccess: () => {
            isModalOpen.value = false
            Swal.fire('Saved!', `Department ${modalMode.value === 'create' ? 'created' : 'updated'} successfully.`, 'success')
        }
    })
}

const toggleStatus = (id: string, currentStatus: boolean) => {
    router.post(route('admin.academics.toggle'), {
        type: 'department',
        id,
        is_active: !currentStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Department status updated',
                showConfirmButton: false,
                timer: 2000
            })
        }
    })
}
</script>

<template>
    <Head title="Departments - Academic Structure" />

    <AdminLayout>
        <div class="space-y-6">
            <AcademicHeaderNav
                :stats="stats"
                active-tab="departments"
                @primary-action="openCreateModal"
            />

            <!-- Filter & Table Card -->
            <Card class="border-neutral-200/80 dark:border-neutral-800 shadow-xs">
                <CardHeader class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4">
                    <div>
                        <CardTitle class="text-lg font-bold">Departments</CardTitle>
                        <CardDescription>Manage academic departments and non-academic administrative units.</CardDescription>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-3">
                        <Button variant="outline" size="sm" as-child class="gap-1.5 text-xs font-semibold">
                            <a :href="exportUrl" download>
                                <Download class="w-3.5 h-3.5 text-emerald-600" />
                                Export Departments
                            </a>
                        </Button>

                        <Select v-model="facultyId">
                            <SelectTrigger class="w-full sm:w-48 bg-neutral-50/50 dark:bg-neutral-900 border-neutral-200 dark:border-neutral-800">
                                <SelectValue placeholder="All Faculties" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL">All Faculties</SelectItem>
                                <SelectItem value="NON_ACADEMIC">Non-Academic Only</SelectItem>
                                <SelectItem v-for="f in faculties" :key="f.id" :value="f.id">
                                    {{ f.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div class="relative w-full sm:w-64">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
                            <Input
                                v-model="search"
                                placeholder="Search department..."
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
                                    <TableHead class="font-bold">Code</TableHead>
                                    <TableHead class="font-bold">Department Name</TableHead>
                                    <TableHead class="font-bold">Faculty</TableHead>
                                    <TableHead class="font-bold">Type</TableHead>
                                    <TableHead class="font-bold">Programmes</TableHead>
                                    <TableHead class="font-bold">Status</TableHead>
                                    <TableHead class="text-right font-bold">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="dept in departments.data"
                                    :key="dept.id"
                                    class="hover:bg-neutral-50/60 dark:hover:bg-neutral-800/50 transition-colors"
                                >
                                    <TableCell class="font-mono font-semibold text-rose-600 dark:text-rose-400">
                                        {{ dept.code }}
                                    </TableCell>
                                    <TableCell class="font-medium text-neutral-900 dark:text-white">
                                        {{ dept.name }}
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="dept.faculty" class="text-neutral-700 dark:text-neutral-300">
                                            {{ dept.faculty.name }}
                                        </span>
                                        <span v-else class="text-neutral-400 italic">Non-Academic</span>
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                            :class="dept.is_academic ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300'"
                                        >
                                            {{ dept.is_academic ? 'Academic' : 'Non-Academic' }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
                                            {{ dept.programmes_count }} Programmes
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <Switch
                                            :checked="Boolean(dept.is_active)"
                                            @update:checked="toggleStatus(dept.id, dept.is_active)"
                                        />
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openEditModal(dept)"
                                            class="hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        >
                                            <Pencil class="w-4 h-4 mr-1.5" />
                                            Edit
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!departments.data || departments.data.length === 0">
                                    <TableCell colspan="7" class="text-center py-12 text-neutral-500">
                                        No departments found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="departments.links && departments.links.length > 3" class="flex justify-center mt-6 gap-1">
                        <Button
                            v-for="(link, i) in departments.links"
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
                        <DialogTitle>{{ modalMode === 'create' ? 'Add New Department' : 'Edit Department' }}</DialogTitle>
                        <DialogDescription>Define department name, code, parent faculty, and type.</DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="dept_code">Department Code</Label>
                            <Input
                                id="dept_code"
                                v-model="form.code"
                                placeholder="e.g. CSH"
                                :class="{ 'border-rose-500': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="text-xs text-rose-500">{{ form.errors.code }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="dept_name">Department Full Name</Label>
                            <Input
                                id="dept_name"
                                v-model="form.name"
                                placeholder="Department of Computer Science"
                                :class="{ 'border-rose-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="dept_faculty">Parent Faculty (Optional for non-academic)</Label>
                            <Select v-model="form.faculty_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Parent Faculty" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">None (Non-Academic Unit)</SelectItem>
                                    <SelectItem v-for="f in faculties" :key="f.id" :value="f.id">
                                        {{ f.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-xl border border-neutral-200 dark:border-neutral-800">
                            <div>
                                <Label class="font-medium text-neutral-900 dark:text-white">Academic Department</Label>
                                <p class="text-xs text-neutral-500">Offers academic programmes and student degrees</p>
                            </div>
                            <Switch v-model:checked="form.is_academic" />
                        </div>

                        <DialogFooter class="mt-6">
                            <Button type="button" variant="outline" @click="isModalOpen = false">Cancel</Button>
                            <Button type="submit" :disabled="form.processing">
                                {{ modalMode === 'create' ? 'Create Department' : 'Save Changes' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
