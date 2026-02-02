<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import { 
    Search, 
    Filter, 
    X,
    Users,
    UserPlus,
    GraduationCap,
    Award,
    Sparkles,
    Briefcase,
    Shield,
    Mail,
    Phone,
    Building2,
    Calendar,
    BadgeCheck,
    MoreVertical,
    Pencil,
    Trash2,
    UserCheck
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

const props = defineProps<{
    staff: {
        data: Array<any>;
        links: Array<any>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        role_id?: string;
        faculty_id?: string;
        department_id?: string;
    };
    faculties: Array<{ 
        id: string; 
        name: string; 
        departments: Array<{ id: string; name: string; faculty_id: string }> 
    }>;
    roles: Array<{ id: string; name: string }>;
    stats: {
        total: number;
        academic: number;
        non_academic: number;
        roles_count: Array<{ name: string; count: number }>;
    };
}>();

const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role_id || '');
const selectedFaculty = ref(props.filters.faculty_id || '');
const selectedDepartment = ref(props.filters.department_id || '');

// Computed departments based on selected faculty
const filteredDepartments = computed(() => {
    if (!selectedFaculty.value || selectedFaculty.value === 'ALL_FACULTIES') return [];
    const faculty = props.faculties.find(f => String(f.id) === String(selectedFaculty.value));
    return faculty ? faculty.departments : [];
});

// Watchers
const updateFilters = debounce(() => {
    router.get(route('admin.staff.index'), {
        search: search.value,
        role_id: selectedRole.value,
        faculty_id: selectedFaculty.value,
        department_id: selectedDepartment.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, 300);

watch([search, selectedRole, selectedFaculty, selectedDepartment], () => {
    if (selectedFaculty.value === 'ALL_FACULTIES') {
        selectedDepartment.value = '';
    }
    updateFilters();
});

const clearFilters = () => {
    search.value = '';
    selectedRole.value = '';
    selectedFaculty.value = '';
    selectedDepartment.value = '';
};

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const deleteStaff = (id: string) => {
    if (confirm('Are you sure you want to delete this staff member? This action cannot be undone.')) {
        router.delete(route('admin.staff.destroy', id), {
            preserveScroll: true
        });
    }
};

const breadcrumbs = [
    { title: 'Staff Management', href: '/admin/staff' }
];
</script>

<template>
    <Head title="Staff Directory" />

    <AdminLayout>
        <div class="py-10 px-6 space-y-8 w-full max-w-[1600px] mx-auto">

            <!-- Header & Stats -->
            <div class="flex flex-col gap-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-foreground">Staff Management</h1>
                        <p class="text-muted-foreground mt-1">Directory and profiles of all university staff members.</p>
                    </div>

                    <Button as-child shadow="md">
                        <Link :href="route('admin.staff.create')">
                            <UserPlus class="w-4 h-4 mr-2" /> Add Staff Member
                        </Link>
                    </Button>
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    <Card class="bg-primary/5 border-primary/20 shadow-none">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-primary">Total Staff</CardTitle>
                            <Users class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-primary">{{ stats.total }}</div>
                            <p class="text-xs text-muted-foreground">Active personnel</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Academic Staff</CardTitle>
                            <GraduationCap class="h-4 w-4 text-orange-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.academic }}</div>
                            <div class="flex items-center gap-1 text-xs text-muted-foreground">
                                <BadgeCheck class="w-3 h-3" /> Teaching & Research
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Non-Academic</CardTitle>
                            <Briefcase class="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.non_academic }}</div>
                            <p class="text-xs text-muted-foreground">Administrative & Support</p>
                        </CardContent>
                    </Card>
                    <Card class="bg-indigo-50/50 dark:bg-indigo-900/10 border-indigo-100 dark:border-indigo-900/50">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-indigo-700 dark:text-indigo-400">System Roles</CardTitle>
                            <Shield class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                        </CardHeader>
                        <CardContent>
                            <div class="flex -space-x-2 overflow-hidden py-1">
                                <div v-for="(role, i) in stats.roles_count.slice(0, 5)" :key="i" 
                                    class="h-6 px-2 flex items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900 text-[10px] font-bold text-indigo-700 dark:text-indigo-400 border-2 border-background">
                                    {{ role.count }}
                                </div>
                            </div>
                            <p class="text-[10px] text-muted-foreground mt-1">Cross-departmental roles</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Filters -->
             <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col lg:flex-row gap-4 items-end lg:items-center justify-between">
                <div class="flex flex-col gap-3 w-full lg:w-auto flex-1">
                    <div class="flex flex-col sm:flex-row gap-3">
                         <div class="relative w-full sm:w-[350px]">
                            <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                              type="search"
                              placeholder="Search by name, email, or staff ID..."
                              class="pl-10 h-10"
                              v-model="search"
                            />
                          </div>

                        <!-- Role -->
                        <Select v-model="selectedRole">
                            <SelectTrigger class="w-full sm:w-[200px] h-10">
                                <SelectValue placeholder="System Role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ALL_ROLES">Any System Role</SelectItem>
                                <SelectItem v-for="role in roles" :key="role.id" :value="String(role.id)">
                                    {{ formatRoleName(role.name) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                         <!-- Faculty -->
                        <Select v-model="selectedFaculty">
                            <SelectTrigger class="w-full sm:w-[250px] h-10">
                                <SelectValue placeholder="All Faculties" />
                            </SelectTrigger>
                             <SelectContent>
                                <SelectItem value="ALL_FACULTIES">All Faculties</SelectItem>
                                <SelectItem v-for="f in faculties" :key="f.id" :value="String(f.id)">{{ f.name }}</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Department -->
                        <Select v-model="selectedDepartment" :disabled="!selectedFaculty || selectedFaculty === 'ALL_FACULTIES'">
                            <SelectTrigger class="w-full sm:w-[250px] h-10">
                                <SelectValue placeholder="All Departments" />
                            </SelectTrigger>
                             <SelectContent>
                                <SelectItem value="ALL_DEPARTMENTS">All Departments</SelectItem>
                                <SelectItem v-for="d in filteredDepartments" :key="d.id" :value="String(d.id)">{{ d.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button 
                        v-if="search || selectedRole || (selectedFaculty && selectedFaculty !== 'ALL_FACULTIES') || selectedDepartment" 
                        variant="ghost" 
                        @click="clearFilters"
                        class="text-destructive hover:text-destructive hover:bg-destructive/10 h-10"
                    >
                        <X class="w-4 h-4 mr-2" />
                        Reset
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <Card class="border shadow-sm overflow-hidden">
                <Table>
                    <TableHeader class="bg-slate-50 dark:bg-slate-900">
                        <TableRow>
                            <TableHead class="w-[300px]">Staff Member</TableHead>
                            <TableHead>System Permissions</TableHead>
                            <TableHead>Department / Faculty</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Staff ID</TableHead>
                            <TableHead class="text-right">Manage</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in staff.data" :key="user.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-900/20">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                   <Avatar class="h-10 w-10 border-2 border-background shadow-sm">
                                        <AvatarFallback class="bg-primary/10 text-primary font-bold">
                                            {{ user.name.charAt(0) }}
                                        </AvatarFallback>
                                   </Avatar>
                                   <div class="flex flex-col">
                                       <span class="font-semibold text-foreground leading-none">{{ user.name }} <span class="text-[10px] text-muted-foreground font-normal ml-1" v-if="user.staff?.designation">({{ user.staff.designation }})</span></span>
                                       <span class="text-xs text-muted-foreground mt-1">{{ user.email }}</span>
                                   </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <template v-for="role in user.roles" :key="role.id">
                                        <Badge 
                                            v-if="role.name !== 'staff'" 
                                            variant="secondary" 
                                            class="bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/50 text-[10px] px-1.5 py-0 font-bold uppercase tracking-wider"
                                        >
                                            <Shield class="w-2 h-2 mr-1" /> {{ formatRoleName(role.name) }}
                                        </Badge>
                                    </template>
                                    <Badge variant="outline" v-if="user.roles.length <= 1" class="text-[10px] uppercase font-bold text-muted-foreground border-slate-200">
                                        General Access
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-foreground">{{ user.staff?.department?.name || 'Unassigned' }}</span>
                                    <span class="text-[10px] text-muted-foreground uppercase tracking-widest">{{ user.staff?.department?.faculty?.name || 'N/A' }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="user.staff?.is_academic ? 'default' : 'secondary'" class="text-[10px] font-bold uppercase">
                                    {{ user.staff?.is_academic ? 'Academic' : 'Non-Academic' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <span class="font-mono text-xs font-bold bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-slate-700 dark:text-slate-300">
                                    {{ user.staff?.staff_number || '---' }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground">
                                            <MoreVertical class="w-4 h-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-48">
                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem as-child>
                                            <Link :href="route('admin.staff.show', user.id)">
                                                <UserCheck class="w-4 h-4 mr-2" /> View Profile
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem as-child>
                                            <Link :href="route('admin.staff.edit', user.id)">
                                                <Pencil class="w-4 h-4 mr-2" /> Edit Member
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem class="text-destructive focus:text-destructive" @click="deleteStaff(user.id)">
                                            <Trash2 class="w-4 h-4 mr-2" /> Delete Member
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                         <TableRow v-if="staff.data.length === 0">
                            <TableCell colspan="6" class="h-48 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="p-3 bg-muted rounded-full">
                                        <Users class="w-8 h-8 text-muted-foreground" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-semibold">No records found</p>
                                        <p class="text-sm text-muted-foreground">Adjust your filters or add a new staff member to see them here.</p>
                                    </div>
                                    <Button variant="outline" size="sm" @click="clearFilters">Clear All Filters</Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                 <!-- Pagination -->
                <CardFooter class="flex items-center justify-between border-t p-4" v-if="staff.total > 0">
                    <div class="text-xs text-muted-foreground">
                        Showing <strong>{{ staff.from }}</strong>-<strong>{{ staff.to }}</strong> of <strong>{{ staff.total }}</strong> members
                    </div>
                    <div class="flex gap-1">
                         <Button 
                            v-for="(link, i) in staff.links" 
                            :key="i"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            as-child
                            class="h-8 min-w-[32px] px-2"
                         >
                            <Link v-if="link.url" :href="link.url" v-html="link.label" />
                            <span v-else v-html="link.label"></span>
                         </Button>
                    </div>
                </CardFooter>
            </Card>
        </div>
    </AdminLayout>
</template>
