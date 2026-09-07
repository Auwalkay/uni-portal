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
import { Search, Plus, Pencil, Layers, Download } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import { computed } from 'vue'

const props = defineProps<{
    units: {
        data: Array<any>;
        links: Array<any>;
    };
    departments: Array<any>;
    stats: any;
    filters: {
        search?: string;
        department_id?: string;
    };
}>()

const search = ref(props.filters?.search || '')
const departmentId = ref(props.filters?.department_id || 'ALL')

const exportUrl = computed(() => {
    const params = new URLSearchParams()
    if (search.value) params.append('search', search.value)
    if (departmentId.value && departmentId.value !== 'ALL') params.append('department_id', departmentId.value)
    const queryString = params.toString()
    return `/admin/academics/units/export${queryString ? '?' + queryString : ''}`
})

const applyFilters = debounce(() => {
    router.get(route('admin.academics.units'), {
        search: search.value,
        department_id: departmentId.value === 'ALL' ? '' : departmentId.value,
    }, { preserveState: true, replace: true })
}, 300)

watch([search, departmentId], applyFilters)

// Modal State
const isModalOpen = ref(false)
const modalMode = ref<'create' | 'edit'>('create')

const form = useForm({
    type: 'unit',
    id: '',
    name: '',
    code: '',
    department_id: '',
})

const openCreateModal = () => {
    modalMode.value = 'create'
    form.reset()
    form.type = 'unit'
    form.clearErrors()
    isModalOpen.value = true
}

const openEditModal = (u: any) => {
    modalMode.value = 'edit'
    form.type = 'unit'
    form.id = u.id
    form.name = u.name
    form.code = u.code
    form.department_id = u.department_id || ''
    form.clearErrors()
    isModalOpen.value = true
}

const submitForm = () => {
    const routeName = modalMode.value === 'create' ? 'admin.academics.store' : 'admin.academics.update'
    form.post(route(routeName), {
        onSuccess: () => {
            isModalOpen.value = false
            Swal.fire('Saved!', `Unit ${modalMode.value === 'create' ? 'created' : 'updated'} successfully.`, 'success')
        }
    })
}

const toggleStatus = (id: string, currentStatus: boolean) => {
    router.post(route('admin.academics.toggle'), {
        type: 'unit',
        id,
        is_active: !currentStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Unit status updated',
                showConfirmButton: false,
                timer: 2000
            })
        }
    })
}
</script>

<template>
    <Head title="Sub-Units - Academic Structure" />

    <AdminLayout>
        <div class="space-y-6">
            <AcademicHeaderNav
                :stats="stats"
                active-tab="units"
                @primary-action="openCreateModal"
            />

            <!-- Filter & Table Card -->
            <Card class="border-neutral-200/80 dark:border-neutral-800 shadow-xs">
                <CardHeader class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4">
                    <div>
                        <CardTitle class="text-lg font-bold">Departmental Units & Sub-Divisions</CardTitle>
                        <CardDescription>Manage sub-units under university academic and non-academic departments.</CardDescription>
                    </div>
                    <div class="flex items-center gap-3">
                        <Button variant="outline" size="sm" as-child class="gap-1.5 text-xs font-semibold">
                            <a :href="exportUrl" download>
                                <Download class="w-3.5 h-3.5 text-emerald-600" />
                                Export Units
                            </a>
                        </Button>

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
                                placeholder="Search unit..."
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
                                    <TableHead class="font-bold">Unit Code</TableHead>
                                    <TableHead class="font-bold">Unit Name</TableHead>
                                    <TableHead class="font-bold">Parent Department</TableHead>
                                    <TableHead class="font-bold">Status</TableHead>
                                    <TableHead class="text-right font-bold">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="u in units.data"
                                    :key="u.id"
                                    class="hover:bg-neutral-50/60 dark:hover:bg-neutral-800/50 transition-colors"
                                >
                                    <TableCell class="font-mono font-semibold text-rose-600 dark:text-rose-400">
                                        {{ u.code }}
                                    </TableCell>
                                    <TableCell class="font-medium text-neutral-900 dark:text-white">
                                        {{ u.name }}
                                    </TableCell>
                                    <TableCell class="text-neutral-700 dark:text-neutral-300">
                                        {{ u.department?.name || 'N/A' }}
                                    </TableCell>
                                    <TableCell>
                                        <Switch
                                            :checked="Boolean(u.is_active)"
                                            @update:checked="toggleStatus(u.id, u.is_active)"
                                        />
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openEditModal(u)"
                                            class="hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        >
                                            <Pencil class="w-4 h-4 mr-1.5" />
                                            Edit
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!units.data || units.data.length === 0">
                                    <TableCell colspan="5" class="text-center py-12 text-neutral-500">
                                        No units found matching criteria.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="units.links && units.links.length > 3" class="flex justify-center mt-6 gap-1">
                        <Button
                            v-for="(link, i) in units.links"
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
                        <DialogTitle>{{ modalMode === 'create' ? 'Add New Unit' : 'Edit Unit' }}</DialogTitle>
                        <DialogDescription>Define unit code, title, and parent department.</DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="u_code">Unit Code</Label>
                            <Input
                                id="u_code"
                                v-model="form.code"
                                placeholder="e.g. IT-SEC"
                                :class="{ 'border-rose-500': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="text-xs text-rose-500">{{ form.errors.code }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="u_name">Unit Name</Label>
                            <Input
                                id="u_name"
                                v-model="form.name"
                                placeholder="IT Security Unit"
                                :class="{ 'border-rose-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Parent Department</Label>
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
                                {{ modalMode === 'create' ? 'Create Unit' : 'Save Changes' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
