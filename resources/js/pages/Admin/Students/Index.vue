<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import { 
    Search, 
    Filter, 
    X,
    ChevronDown,
    User,
    Users,
    GraduationCap,
    BookOpen,
    Building2,
    Calendar,
    Award,
    UserPlus
} from 'lucide-vue-next';

const props = defineProps({
    students: Object,
    filters: Object,
    sessions: Array,
    faculties: Array,
    departments: Array,
    stats: Object,
});

import { route } from 'ziggy-js'; // Fixing route import

const search = ref(props.filters.search || '');
const selectedSession = ref(props.filters.session_id || '');
const selectedFaculty = ref(props.filters.faculty_id || '');
const selectedDepartment = ref(props.filters.department_id || '');
const selectedLevel = ref(props.filters.level || '');
const selectedProgram = ref(props.filters.program || '');

// Computed departments based on selected faculty
const filteredDepartments = computed(() => {
    if (!selectedFaculty.value) return props.departments;
    return props.departments.filter(dept => dept.faculty_id === selectedFaculty.value);
});

// Watchers
const updateFilters = debounce(() => {
    router.get(route('admin.students.index'), {
        search: search.value,
        session_id: selectedSession.value,
        faculty_id: selectedFaculty.value,
        department_id: selectedDepartment.value,
        level: selectedLevel.value,
        program: selectedProgram.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, 300);

watch([search, selectedSession, selectedFaculty, selectedDepartment, selectedLevel, selectedProgram], () => {
     // When faculty changes, clear department if it doesn't belong to new faculty
     if (selectedFaculty.value && selectedDepartment.value) {
         const dept = props.departments.find(d => d.id === selectedDepartment.value);
         if (dept && dept.faculty_id !== selectedFaculty.value) {
             selectedDepartment.value = '';
         }
    }
    updateFilters();
});

const clearFilters = () => {
    search.value = '';
    selectedSession.value = '';
    selectedFaculty.value = '';
    selectedDepartment.value = '';
    selectedLevel.value = '';
    selectedProgram.value = '';
};
</script>

<template>
    <Head title="Manage Students" />

    <AdminLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8 w-full mx-auto space-y-6">

            <!-- Header & Stats -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Student Management</h1>
                    <p class="text-sm text-gray-500 mt-1">Directory of all registered students.</p>
                </div>

                <div class="flex gap-4 items-center">
                    <Link :href="route('admin.students.create')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                        <UserPlus class="w-4 h-4" /> Add Student
                    </Link>

                     <!-- Total Students Card -->
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex items-center space-x-4">
                        <div class="p-2 bg-blue-50 rounded-full">
                            <Users class="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Total Students</p>
                            <p class="text-lg font-bold text-gray-900">{{ stats.total }}</p>
                        </div>
                    </div>
                     <!-- Active Students Card (Example) -->
                     <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex items-center space-x-4">
                        <div class="p-2 bg-green-50 rounded-full">
                            <User class="w-6 h-6 text-green-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Active</p>
                            <p class="text-lg font-bold text-gray-900">{{ stats.active }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Smart Filter Bar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <Filter class="w-4 h-4" /> Filter Students
                    </h3>
                    <button 
                        v-if="search || selectedSession || selectedFaculty || selectedDepartment || selectedLevel || selectedProgram"
                        @click="clearFilters" 
                        class="text-xs text-red-600 hover:text-red-800 flex items-center gap-1 font-medium transition"
                    >
                        <X class="w-3 h-3" /> Clear Filters
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <!-- Search -->
                    <div class="relative col-span-1 md:col-span-2 lg:col-span-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search class="h-4 w-4 text-gray-400" />
                        </div>
                        <input 
                            v-model="search" 
                            type="text" 
                            class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors duration-200" 
                            placeholder="Name, Matric, Email..." 
                        />
                    </div>

                    <!-- Session -->
                    <div class="relative">
                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Calendar class="h-4 w-4 text-gray-400" />
                        </div>
                        <select v-model="selectedSession" class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 sm:text-sm">
                            <option value="">Any Session</option>
                            <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                         <ChevronDown class="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>

                    <!-- Faculty -->
                    <div class="relative">
                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Building2 class="h-4 w-4 text-gray-400" />
                        </div>
                        <select v-model="selectedFaculty" class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 sm:text-sm">
                            <option value="">All Faculties</option>
                            <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
                        </select>
                         <ChevronDown class="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>

                    <!-- Department -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <BookOpen class="h-4 w-4 text-gray-400" />
                        </div>
                        <select v-model="selectedDepartment" class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 sm:text-sm">
                            <option value="">All Departments</option>
                            <option v-for="d in filteredDepartments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                         <ChevronDown class="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>

                    <!-- Level -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Award class="h-4 w-4 text-gray-400" />
                        </div>
                         <select v-model="selectedLevel" class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 sm:text-sm">
                            <option value="">All Levels</option>
                            <option value="100">100 Level</option>
                            <option value="200">200 Level</option>
                            <option value="300">300 Level</option>
                            <option value="400">400 Level</option>
                            <option value="500">500 Level</option>
                            <option value="600">600 Level</option>
                        </select>
                         <ChevronDown class="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                    </div>

                     <!-- Program -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <GraduationCap class="h-4 w-4 text-gray-400" />
                        </div>
                         <input 
                            v-model="selectedProgram" 
                            type="text"
                            class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white focus:ring-blue-500 sm:text-sm"
                            placeholder="Program..." 
                        />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name / Matric</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department / Faculty</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admitted Session</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="student in students.data" :key="student.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                    <img :src="student?.passport_photo_path ? `/storage/${student.passport_photo_path}` : `https://ui-avatars.com/api/?name=${user?.name}&background=random`" 
                        alt="Profile Photo" 
                        class="h-20 w-20 rounded-full border-4 border-white/30 object-cover shadow-md"
                    />                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ student.user.name }}</div>
                                            <div class="text-xs text-gray-500">{{ student.matriculation_number || 'No Matric No' }}</div>
                                             <div class="text-xs text-gray-400">{{ student.user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ student.academic_department?.name || 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ student.academic_department?.faculty?.name || 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                     <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ student.admitted_session?.name || 'N/A' }}
                                     </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    {{ student.current_level }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ student.program || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('admin.students.show', student.id)" class="text-blue-600 hover:text-blue-900 font-semibold text-xs uppercase tracking-wide">
                                        View
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="students.data.length === 0">
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                    <p class="text-base font-medium text-gray-900">No students found</p>
                                    <p class="text-sm">Try adjusting your filters.</p>
                                    <button @click="clearFilters" class="mt-2 text-blue-600 hover:text-blue-800 text-sm font-medium">Clear Filters</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                     <div class="text-sm text-gray-500">
                        Showing <span class="font-medium text-gray-900">{{ students.from || 0 }}</span> to <span class="font-medium text-gray-900">{{ students.to || 0 }}</span> of <span class="font-medium text-gray-900">{{ students.total }}</span> results
                    </div>
                    <div class="flex space-x-1">
                          <Link 
                            v-for="(link, i) in students.links" 
                            :key="i"
                            :href="link.url ?? '#'"
                            class="px-3 py-1 text-sm border rounded-md transition-colors duration-150"
                            :class="[
                                link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
