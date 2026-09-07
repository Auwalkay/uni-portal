<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
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
import { Search, Plus, Pencil, Building2, Download } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import { computed } from 'vue'

const props = defineProps<{
    faculties: {
        data: Array<any>;
        links: Array<any>;
    };
    stats: any;
    filters: {
        search?: string;
    };
}>()

const search = ref(props.filters?.search || '')

const exportUrl = computed(() => {
    const params = new URLSearchParams()
    if (search.value) params.append('search', search.value)
    const queryString = params.toString()
    return `/admin/academics/faculties/export${queryString ? '?' + queryString : ''}`
})

watch(search, debounce((val) => {
    router.get(route('admin.academics.faculties'), { search: val }, { preserveState: true, replace: true })
}, 300))

// Modal State
const isModalOpen = ref(false)
const modalMode = ref<'create' | 'edit'>('create')
const editingId = ref<string | null>(null)

const form = useForm({
    type: 'faculty',
    id: '',
    name: '',
    code: '',
})

const openCreateModal = () => {
    modalMode.value = 'create'
    editingId.value = null
    form.reset()
    form.type = 'faculty'
    form.clearErrors()
    isModalOpen.value = true
}

const openEditModal = (faculty: any) => {
    modalMode.value = 'edit'
    editingId.value = faculty.id
    form.type = 'faculty'
    form.id = faculty.id
    form.name = faculty.name
    form.code = faculty.code
    form.clearErrors()
    isModalOpen.value = true
}

const submitForm = () => {
    if (modalMode.value === 'create') {
        form.post(route('admin.academics.store'), {
            onSuccess: () => {
                isModalOpen.value = false
                Swal.fire('Created!', 'Faculty created successfully.', 'success')
            }
        })
    } else {
        form.post(route('admin.academics.update'), {
            onSuccess: () => {
                isModalOpen.value = false
                Swal.fire('Updated!', 'Faculty updated successfully.', 'success')
            }
        })
    }
}

const toggleStatus = (id: string, currentStatus: boolean) => {
    router.post(route('admin.academics.toggle'), {
        type: 'faculty',
        id,
        is_active: !currentStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Faculty status updated',
                showConfirmButton: false,
                timer: 2000
            })
        }
    })
}
</script>

<template>
    <Head title="Faculties - Academic Structure" />

    <AdminLayout>
        <div class="space-y-6">
            <AcademicHeaderNav
                :stats="stats"
                active-tab="faculties"
                @primary-action="openCreateModal"
            />

            <!-- Filter & Table Card -->
            <Card class="border-neutral-200/80 dark:border-neutral-800 shadow-xs">
                <CardHeader class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4">
                    <div>
                        <CardTitle class="text-lg font-bold">Faculties</CardTitle>
                        <CardDescription>Manage university faculties and associated departments.</CardDescription>
                    </div>
                    <div class="flex items-center gap-3">
                        <Button variant="outline" size="sm" as-child class="gap-1.5 text-xs font-semibold">
                            <a :href="exportUrl" download>
                                <Download class="w-3.5 h-3.5 text-emerald-600" />
                                Export Faculties
                            </a>
                        </Button>

                        <div class="relative w-full sm:w-72">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" />
                            <Input
                                v-model="search"
                                placeholder="Search by code or name..."
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
                                    <TableHead class="font-bold">Faculty Name</TableHead>
                                    <TableHead class="font-bold">Departments</TableHead>
                                    <TableHead class="font-bold">Status</TableHead>
                                    <TableHead class="text-right font-bold">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="faculty in faculties.data"
                                    :key="faculty.id"
                                    class="hover:bg-neutral-50/60 dark:hover:bg-neutral-800/50 transition-colors"
                                >
                                    <TableCell class="font-mono font-semibold text-rose-600 dark:text-rose-400">
                                        {{ faculty.code }}
                                    </TableCell>
                                    <TableCell class="font-medium text-neutral-900 dark:text-white">
                                        {{ faculty.name }}
                                    </TableCell>
                                    <TableCell>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
                                            {{ faculty.departments_count }} Departments
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <Switch
                                            :checked="Boolean(faculty.is_active)"
                                            @update:checked="toggleStatus(faculty.id, faculty.is_active)"
                                        />
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openEditModal(faculty)"
                                            class="hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        >
                                            <Pencil class="w-4 h-4 mr-1.5" />
                                            Edit
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!faculties.data || faculties.data.length === 0">
                                    <TableCell colspan="5" class="text-center py-12 text-neutral-500">
                                        No faculties found matching your criteria.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="faculties.links && faculties.links.length > 3" class="flex justify-center mt-6 gap-1">
                        <Button
                            v-for="(link, i) in faculties.links"
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
                        <DialogTitle>{{ modalMode === 'create' ? 'Add New Faculty' : 'Edit Faculty' }}</DialogTitle>
                        <DialogDescription>Enter faculty code and full official title.</DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="submitForm" class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="code">Faculty Code (e.g. ENG, SCI)</Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="e.g. ENG"
                                :class="{ 'border-rose-500': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="text-xs text-rose-500">{{ form.errors.code }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="name">Faculty Full Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Faculty of Engineering"
                                :class="{ 'border-rose-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <DialogFooter class="mt-6">
                            <Button type="button" variant="outline" @click="isModalOpen = false">Cancel</Button>
                            <Button type="submit" :disabled="form.processing">
                                {{ modalMode === 'create' ? 'Create Faculty' : 'Save Changes' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
