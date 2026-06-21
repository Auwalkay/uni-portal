<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Search, GraduationCap, FileText, Settings2, UserSearch, ArrowRight, FilterX } from 'lucide-vue-next';
import debounce from 'lodash/debounce';
import { route } from 'ziggy-js';

const props = defineProps<{
    students: any;
    filters: any;
    faculties: any[];
    departments: any[];
}>();

const search = ref(props.filters.search || '');
const facultyId = ref(props.filters.faculty_id || 'all');
const departmentId = ref(props.filters.department_id || 'all');

const filteredDepartments = computed(() => {
    if (facultyId.value === 'all') return [];
    return props.departments.filter(d => String(d.faculty_id) === String(facultyId.value));
});

const facultyItems = computed(() => [
    { value: 'all', label: 'All Faculties' },
    ...props.faculties.map(f => ({ value: String(f.id), label: f.name }))
]);

const departmentItems = computed(() => [
    { value: 'all', label: 'All Departments' },
    ...filteredDepartments.value.map(d => ({ value: String(d.id), label: d.name }))
]);

const updateFilters = debounce(() => {
    router.get(route('admin.course_registration.index'), { 
        search: search.value,
        faculty_id: facultyId.value === 'all' ? null : facultyId.value,
        department_id: departmentId.value === 'all' ? null : departmentId.value,
    }, {
        preserveState: true,
        replace: true
    });
}, 500);

watch([search, facultyId, departmentId], () => {
    updateFilters();
});

const resetFilters = () => {
    search.value = '';
    facultyId.value = 'all';
    departmentId.value = 'all';
};

const breadcrumbs = [
    { title: 'Academics', href: '#' },
    { title: 'Course Registration', href: '#' }
];
</script>

<template>
    <Head title="Course Registration Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-7xl mx-auto">
            
            <!-- Header & Search -->
            <div class="space-y-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-white p-8 rounded-2xl border shadow-sm">
                    <div class="space-y-1">
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 text-transparent bg-clip-text bg-gradient-to-r from-gray-900 to-gray-600">Course Registration</h1>
                        <p class="text-muted-foreground">Manage and preview course registrations for all students.</p>
                    </div>
                    <div class="relative w-full md:w-96">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input 
                            v-model="search" 
                            placeholder="Search student by name or matric number..." 
                            class="pl-10 h-11 rounded-xl bg-muted/50 border-none focus-visible:ring-primary shadow-inner"
                        />
                    </div>
                </div>

                <!-- Filters -->
                <Card class="border-none shadow-sm bg-muted/30">
                    <CardContent class="p-4">
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="space-y-1.5 flex-1 min-w-[200px]">
                                <Label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Faculty</Label>
                                <SearchableSelect
                                    v-model="facultyId"
                                    :items="facultyItems"
                                    placeholder="All Faculties"
                                    search-placeholder="Search faculties..."
                                />
                            </div>

                            <div class="space-y-1.5 flex-1 min-w-[200px]">
                                <Label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Department</Label>
                                <SearchableSelect
                                    v-model="departmentId"
                                    :items="departmentItems"
                                    placeholder="All Departments"
                                    search-placeholder="Search departments..."
                                    :disabled="facultyId === 'all'"
                                />
                            </div>

                            <Button variant="ghost" @click="resetFilters" class="text-xs font-semibold text-muted-foreground hover:text-red-600">
                                <FilterX class="w-4 h-4 mr-2" /> Reset Filters
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Student List -->
            <Card class="border-none shadow-lg overflow-hidden rounded-2xl">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-muted/30">
                            <TableRow>
                                <TableHead class="pl-8">Student</TableHead>
                                <TableHead>Matric Number</TableHead>
                                <TableHead>Academic Info</TableHead>
                                <TableHead class="text-right pr-8">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="student in students.data" :key="student.id" class="group hover:bg-primary/5 transition-colors">
                                <TableCell class="pl-8 py-4">
                                    <div class="flex items-center gap-4">
                                        <Avatar class="h-12 w-12 border-2 border-white shadow-sm group-hover:scale-105 transition-transform">
                                            <AvatarImage :src="student.passport_photo_path ? `/storage/${student.passport_photo_path}` : ''" />
                                            <AvatarFallback class="bg-primary/10 text-primary font-bold">
                                                {{ student.user.name.charAt(0) }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <div class="font-bold text-gray-900 group-hover:text-primary transition-colors">{{ student.user.name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ student.user.email }}</div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary" class="font-mono px-2 py-0.5 bg-gray-100 text-gray-700">
                                        {{ student.matriculation_number }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium">{{ student.academic_department?.name || 'N/A' }}</div>
                                        <div class="flex items-center gap-2">
                                            <Badge variant="outline" class="text-[10px] font-bold">{{ student.current_level }} Level</Badge>
                                            <span class="text-[10px] text-muted-foreground">•</span>
                                            <span class="text-[10px] font-medium text-muted-foreground uppercase tracking-tight">{{ student.program?.name }}</span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right pr-8">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="sm" as-child class="h-9 rounded-lg hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all">
                                            <a :href="route('admin.course_registration.form', student.id)" target="_blank">
                                                <FileText class="w-4 h-4 mr-2" /> Preview
                                            </a>
                                        </Button>
                                        <Button variant="secondary" size="sm" as-child class="h-9 rounded-lg hover:bg-primary hover:text-white transition-all">
                                            <Link :href="route('admin.course_registration.manage', student.id)">
                                                <Settings2 class="w-4 h-4 mr-2" /> Manage
                                                <ArrowRight class="w-3.5 h-3.5 ml-1.5 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all" />
                                            </Link>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="students.data.length === 0">
                                <TableCell colspan="4" class="h-64 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="p-4 bg-muted/50 rounded-full">
                                            <UserSearch class="w-10 h-10 text-muted-foreground" />
                                        </div>
                                        <div>
                                            <p class="text-lg font-semibold text-gray-900">No students found</p>
                                            <p class="text-muted-foreground">Try searching with a different name or matriculation number.</p>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="students.links.length > 3" class="flex justify-center mt-8">
                <nav class="flex items-center space-x-2">
                    <Link 
                        v-for="(link, k) in students.links" 
                        :key="k" 
                        :href="link.url || '#'" 
                        v-html="link.label"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="[
                            link.active ? 'bg-primary text-white shadow-md' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>
