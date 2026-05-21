<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import {
  Search, Plus, Trash2, User, Upload, Download, FileSpreadsheet
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { route } from 'ziggy-js';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card, CardFooter
} from '@/components/ui/card';
import {
  Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue, SelectGroup, SelectLabel,
} from '@/components/ui/select';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';
const props = defineProps<{
  allocations: { data: Array<any>; links: Array<any>; from: number; to: number; total: number };
  sessions: Array<{ id: string; name: string }>;
  faculties: Array<{ id: string; name: string; departments: Array<any> }>;
  courses: Array<{ id: string; code: string; title: string; department_id: string }>;
  programmes: Array<{ id: string; name: string }>;
  lecturers: Array<{ id: string; name: string }>;
  filters: { session_id?: string; department_id?: string; search?: string; faculty_id?: string };
  currentSessionId?: string;
}>();

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || props.currentSessionId || '');
const selectedFaculty = ref(props.filters.faculty_id || '');
const selectedDepartment = ref(props.filters.department_id || '');
const isAssignOpen = ref(false);
const isImportOpen = ref(false);


// Actually, removing the whole block 61-79
const form = useForm({
  course_id: '',
  staff_id: '',
  session_id: props.currentSessionId || '',
  department_id: '',
  program_id: '',
});

const importForm = useForm({
  file: null as File | null,
});

const filteredDepartments = computed(() => {
  if (!selectedFaculty.value || selectedFaculty.value === 'ALL') return [];
  const faculty = props.faculties.find(f => String(f.id) === String(selectedFaculty.value));
  return faculty ? faculty.departments : [];
});

const updateFilters = debounce(() => {
  router.get(route('admin.course-allocations.index'), {
    search: search.value,
    session_id: selectedSession.value,
    faculty_id: selectedFaculty.value,
    department_id: selectedDepartment.value,
  }, { preserveState: true, replace: true });
}, 300);

watch([search, selectedSession, selectedFaculty, selectedDepartment], () => {
  if (selectedFaculty.value === 'ALL') selectedDepartment.value = '';
  updateFilters();
});

const submitAssign = () => {
  if (!form.session_id) form.session_id = selectedSession.value;

  form.post(route('admin.course-allocations.store'), {
    onSuccess: () => {
      isAssignOpen.value = false;
      form.reset('course_id', 'staff_id');
    },
  });
};

const submitImport = () => {
  importForm.post(route('admin.course-allocations.import'), {
    onSuccess: () => {
      isImportOpen.value = false;
      importForm.reset();
    },
  });
};

const deleteAllocation = (id: string) => {
  if (confirm('Remove this course assignment?')) {
    router.delete(route('admin.course-allocations.destroy', id));
  }
};

const getLecturerName = (staffId: string) => {
  return props.lecturers.find(l => String(l.id) === String(staffId))?.name || 'Unknown';
};
</script>

<template>
  <Head title="Course Allocations" />

  <AdminLayout>
    <div class="py-10 px-6 space-y-8 w-full max-w-[1600px] mx-auto">

      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-foreground">Course Allocation</h1>
          <p class="text-muted-foreground mt-1">Assign courses to lecturers for the academic session.</p>
        </div>

        <div class="flex gap-2">
          <!-- Import -->
          <Dialog v-model:open="isImportOpen">
            <DialogTrigger as-child>
              <Button variant="outline">
                <Upload class="w-4 h-4 mr-2" /> Import
              </Button>
            </DialogTrigger>

            <DialogContent class="sm:max-w-[425px]">
              <DialogHeader>
                <DialogTitle>Import Allocations</DialogTitle>
                <DialogDescription>
                  Bulk assign courses using a CSV file.
                </DialogDescription>
              </DialogHeader>

              <div class="grid gap-6 py-4">
                <div class="bg-slate-50 p-4 rounded-lg text-sm space-y-2 border">
                  <p class="font-medium flex items-center gap-2">
                    <FileSpreadsheet class="w-4 h-4 text-green-600" /> CSV Format Guide
                  </p>
                  <p class="text-muted-foreground text-xs">
                    Your file must have headers: <code>course_code</code>, <code>staff_email</code>.
                  </p>

                  <Button variant="secondary" size="sm" class="w-full mt-2" as-child>
                    <a :href="route('admin.course-allocations.template')">
                      <Download class="w-3 h-3 mr-2" /> Download Template
                    </a>
                  </Button>
                </div>

                <div class="grid w-full max-w-sm items-center gap-1.5">
                  <Label for="file">Upload CSV</Label>
                  <Input id="file" type="file" @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null" />
                  <p class="text-xs text-red-500" v-if="importForm.errors.file">{{ importForm.errors.file }}</p>
                </div>
              </div>

              <DialogFooter>
                <Button type="submit" @click="submitImport" :disabled="importForm.processing">
                  <Upload class="w-4 h-4 mr-2" /> Upload &amp; Process
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <!-- Assign -->
          <Dialog v-model:open="isAssignOpen">
            <DialogTrigger as-child>
              <Button>
                <Plus class="w-4 h-4 mr-2" /> Assign Course
              </Button>
            </DialogTrigger>

            <DialogContent class="sm:max-w-[425px]">
              <DialogHeader>
                <DialogTitle>Assign Course</DialogTitle>
                <DialogDescription>
                  Select a session, course, and lecturer to create an allocation.
                </DialogDescription>
              </DialogHeader>

              <div class="grid gap-4 py-4">

                <!-- Session -->
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label class="text-right">Session</Label>
                  <div class="col-span-3">
                    <Select v-model="form.session_id">
                      <SelectTrigger>
                        <SelectValue placeholder="Select Session" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">
                          {{ s.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <!-- Course (✅ searchable dropdown) -->
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label class="text-right">Course</Label>
                  <div class="col-span-3">
                    <Select v-model="form.course_id">
                      <SelectTrigger>
                        <SelectValue placeholder="Select Course" />
                      </SelectTrigger>
                      <SelectContent class="max-h-[200px]">
                        <SelectItem v-for="c in courses" :key="c.id" :value="String(c.id)">
                          {{ c.code }} - {{ c.title }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <!-- Lecturer -->
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label class="text-right">Lecturer</Label>
                  <div class="col-span-3">
                    <Select v-model="form.staff_id">
                      <SelectTrigger>
                        <SelectValue placeholder="Select Lecturer" />
                      </SelectTrigger>
                      <SelectContent class="max-h-[200px]">
                        <SelectItem v-for="l in lecturers" :key="l.id" :value="String(l.id)">
                          {{ l.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>

                <!-- Department -->
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label class="text-right">Department</Label>
                  <div class="col-span-3">
                    <Select v-model="form.department_id">
                      <SelectTrigger>
                        <SelectValue placeholder="Select Department (Optional)" />
                      </SelectTrigger>
                      <SelectContent class="max-h-[200px]">
                        <SelectGroup v-for="f in faculties" :key="f.id">
                          <SelectLabel>{{ f.name }}</SelectLabel>
                          <SelectItem
                            v-for="d in f.departments"
                            :key="d.id"
                            :value="String(d.id)"
                          >
                            {{ d.name }}
                          </SelectItem>
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    <p class="text-[0.8rem] text-muted-foreground mt-1">
                      Specify if this allocation is for a specific department.
                    </p>
                  </div>
                </div>

                <!-- Programme -->
                <div class="grid grid-cols-4 items-center gap-4">
                  <Label class="text-right">Programme</Label>
                  <div class="col-span-3">
                    <Select v-model="form.program_id">
                      <SelectTrigger>
                        <SelectValue placeholder="Select Programme (Optional)" />
                      </SelectTrigger>
                      <SelectContent class="max-h-[200px]">
                        <SelectItem v-for="p in programmes" :key="p.id" :value="String(p.id)">
                          {{ p.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <p class="text-[0.8rem] text-muted-foreground mt-1">
                      Specify if this allocation is for a specific programme.
                    </p>
                  </div>
                </div>

              </div>

              <DialogFooter>
                <Button type="submit" @click="submitAssign" :disabled="form.processing">
                  Save Allocation
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col lg:flex-row gap-4">
        <div class="flex flex-1 gap-3 flex-wrap">
          <div class="relative w-full sm:w-[300px]">
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input v-model="search" placeholder="Search course or lecturer..." class="pl-10" />
          </div>

          <Select v-model="selectedSession">
            <SelectTrigger class="w-[200px]">
              <SelectValue placeholder="Session" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="s in sessions" :key="s.id" :value="String(s.id)">
                {{ s.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="selectedFaculty">
            <SelectTrigger class="w-[200px]">
              <SelectValue placeholder="All Faculties" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="ALL">All Faculties</SelectItem>
              <SelectItem v-for="f in faculties" :key="f.id" :value="String(f.id)">
                {{ f.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="selectedDepartment" :disabled="!selectedFaculty || selectedFaculty === 'ALL'">
            <SelectTrigger class="w-[200px]">
              <SelectValue placeholder="Department" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="ALL">All Departments</SelectItem>
              <SelectItem v-for="d in filteredDepartments" :key="d.id" :value="String(d.id)">
                {{ d.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Table -->
      <Card>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Course</TableHead>
              <TableHead>Lecturer</TableHead>
              <TableHead>Session</TableHead>
              <TableHead>Department</TableHead>
              <TableHead class="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-for="alloc in allocations.data" :key="alloc.id">
              <TableCell>
                <div class="flex flex-col">
                  <span class="font-medium">{{ alloc.course?.code }}</span>
                  <span class="text-xs text-muted-foreground">{{ alloc.course?.title }}</span>
                </div>
              </TableCell>

              <TableCell>
                <div class="flex items-center gap-2">
                  <User class="w-4 h-4 text-muted-foreground" />
                  <span>{{ alloc.staff?.user?.name || 'Unknown' }}</span>
                </div>
              </TableCell>

              <TableCell>
                <Badge variant="outline">{{ alloc.session?.name }}</Badge>
              </TableCell>

              <TableCell>
                <span class="text-sm text-muted-foreground">{{ alloc.course?.department?.name || '-' }}</span>
              </TableCell>

              <TableCell class="text-right">
                <Button variant="ghost" size="icon" class="text-destructive" @click="deleteAllocation(alloc.id)">
                  <Trash2 class="w-4 h-4" />
                </Button>
              </TableCell>
            </TableRow>

            <TableRow v-if="allocations.data.length === 0">
              <TableCell colspan="5" class="text-center py-8 text-muted-foreground">
                No allocations found.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <CardFooter class="border-t p-4 flex justify-between" v-if="allocations.total > 0">
          <div class="text-xs text-muted-foreground">
            Showing {{ allocations.from }}-{{ allocations.to }} of {{ allocations.total }}
          </div>
          <!-- Pagination links would go here -->
        </CardFooter>
      </Card>

    </div>
  </AdminLayout>
</template>
