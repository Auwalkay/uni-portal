<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { 
    Search, 
    X,
    Users,
    User,
    UserPlus,
    Shield,
    Mail,
    BadgeCheck,
    Pencil,
    Trash2,
    Lock,
    Unlock,
    MoreHorizontal,
    ShieldAlert,
    Ban,
    UserCheck,
    UserX,
    Key,
    RotateCcw,
    SlidersHorizontal,
    ArrowUpDown,
    CheckCircle2,
    AlertTriangle,
    Eye,
    EyeOff,
    Sparkles
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';
import axios from 'axios';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';

const props = defineProps<{
    users: {
        data: Array<any>;
        links: Array<any>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        role?: string;
        status?: string;
        sort?: string;
        per_page?: number;
    };
    availableRoles: Array<{ id: number; name: string }>;
    stats: {
        total: number;
        active: number;
        inactive: number;
        admins: number;
        staff: number;
        students: number;
    };
}>();

// Multi-Role Filter State
const initialRoles = () => {
    if (props.filters.roles) {
        return Array.isArray(props.filters.roles) 
            ? props.filters.roles 
            : String(props.filters.roles).split(',').filter(Boolean);
    }
    if (props.filters.role && props.filters.role !== 'ALL') {
        return [props.filters.role];
    }
    return [];
};

const search = ref(props.filters.search || '');
const selectedRolesFilter = ref<string[]>(initialRoles());
const selectedStatusFilter = ref(props.filters.status || 'ALL');
const selectedSort = ref(props.filters.sort || 'created_at_desc');
const perPage = ref(String(props.filters.per_page || 15));

const toggleRoleFilter = (roleName: string) => {
    if (selectedRolesFilter.value.includes(roleName)) {
        selectedRolesFilter.value = selectedRolesFilter.value.filter(r => r !== roleName);
    } else {
        selectedRolesFilter.value = [...selectedRolesFilter.value, roleName];
    }
};

const removeRoleFilter = (roleName: string) => {
    selectedRolesFilter.value = selectedRolesFilter.value.filter(r => r !== roleName);
};

const activeFiltersCount = computed(() => {
    let count = 0;
    if (search.value) count++;
    if (selectedRolesFilter.value.length > 0) count += selectedRolesFilter.value.length;
    if (selectedStatusFilter.value !== 'ALL') count++;
    if (selectedSort.value !== 'created_at_desc') count++;
    return count;
});

const updateFilters = debounce(() => {
    router.get(route('admin.users.index'), {
        search: search.value,
        roles: selectedRolesFilter.value.join(','),
        status: selectedStatusFilter.value === 'ALL' ? '' : selectedStatusFilter.value,
        sort: selectedSort.value,
        per_page: perPage.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, 300);

watch([search, selectedRolesFilter, selectedStatusFilter, selectedSort, perPage], () => {
    updateFilters();
}, { deep: true });

const clearFilters = () => {
    search.value = '';
    selectedRolesFilter.value = [];
    selectedStatusFilter.value = 'ALL';
    selectedSort.value = 'created_at_desc';
    perPage.value = '15';
};

// Create User Modal State
const showCreateModal = ref(false);
const showCreatePassword = ref(false);
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
    staff_number: '',
    matriculation_number: '',
});

const isStaffRoleSelected = computed(() => {
    return createForm.roles.some(r => r !== 'student' && r !== 'applicant');
});

const isStudentRoleSelected = computed(() => {
    return createForm.roles.includes('student');
});

const toggleCreateUserRole = (roleName: string) => {
    const idx = createForm.roles.indexOf(roleName);
    if (idx > -1) {
        createForm.roles.splice(idx, 1);
    } else {
        createForm.roles.push(roleName);
    }
};

const generateRandomPassword = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789#@!';
    let pass = '';
    for (let i = 0; i < 12; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    createForm.password = pass;
    createForm.password_confirmation = pass;
    showCreatePassword.value = true;
};

const submitCreate = () => {
    createForm.post(route('admin.users.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

// Edit User Modal State
const showEditModal = ref(false);
const editingUserForDetails = ref<any>(null);
const showEditPassword = ref(false);
const editForm = useForm({
    name: '',
    email: '',
    password: '',
    staff_number: '',
    matriculation_number: '',
});

const openEditModal = (user: any) => {
    editingUserForDetails.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.staff_number = user.staff ? user.staff.staff_number : '';
    editForm.matriculation_number = user.student ? user.student.matriculation_number : '';
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!editingUserForDetails.value) return;
    editForm.put(route('admin.users.update', editingUserForDetails.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editingUserForDetails.value = null;
            editForm.reset();
        }
    });
};

// Role Assignment Management Modal State
const showRoleModal = ref(false);
const editingUserForRoles = ref<any>(null);
const roleForm = useForm({
    roles: [] as string[]
});

const openRoleModal = (user: any) => {
    editingUserForRoles.value = user;
    roleForm.roles = user.roles ? user.roles.map((r: any) => r.name) : [];
    showRoleModal.value = true;
};

const toggleUserRole = (roleName: string) => {
    const index = roleForm.roles.indexOf(roleName);
    if (index > -1) {
        roleForm.roles.splice(index, 1);
    } else {
        roleForm.roles.push(roleName);
    }
};

const submitRoles = () => {
    if (!editingUserForRoles.value) return;
    roleForm.patch(route('admin.users.roles.update', editingUserForRoles.value.id), {
        onSuccess: () => {
            showRoleModal.value = false;
            editingUserForRoles.value = null;
        }
    });
};

// Reset Password Modal State
const showResetPasswordModal = ref(false);
const userForPasswordReset = ref<any>(null);
const showManualPasswordInput = ref(false);
const resetPasswordForm = useForm({
    password: '',
});

const openResetPasswordModal = (user: any) => {
    userForPasswordReset.value = user;
    resetPasswordForm.password = '';
    showManualPasswordInput.value = false;
    showResetPasswordModal.value = true;
};

const submitResetPassword = () => {
    if (!userForPasswordReset.value) return;
    resetPasswordForm.post(route('admin.users.reset-password', userForPasswordReset.value.id), {
        onSuccess: () => {
            showResetPasswordModal.value = false;
            userForPasswordReset.value = null;
            resetPasswordForm.reset();
        }
    });
};

// Status & Delete Actions
const toggleStatus = (user: any) => {
    const action = user.is_active ? 'suspend' : 'reactivate';
    if (confirm(`Are you sure you want to ${action} user account for ${user.name}?`)) {
        router.patch(route('admin.users.status.toggle', user.id));
    }
};

const restoreUser = (user: any) => {
    if (confirm(`Are you sure you want to restore user account for ${user.name}?`)) {
        router.post(route('admin.users.restore', user.id));
    }
};

// View User Profile & Audit Log State
const showViewModal = ref(false);
const viewingUser = ref<any>(null);
const viewingActivityLogs = ref<any[]>([]);
const viewingPermissions = ref<string[]>([]);
const isLoadingUserDetails = ref(false);
const activeViewTab = ref<'details' | 'logs' | 'permissions'>('details');

const openViewModal = async (user: any) => {
    showViewModal.value = true;
    isLoadingUserDetails.value = true;
    activeViewTab.value = 'details';
    viewingUser.value = { ...user };
    viewingActivityLogs.value = [];
    viewingPermissions.value = [];

    try {
        const response = await axios.get(route('admin.users.show', user.id));
        if (response.data && response.data.user) {
            viewingUser.value = response.data.user;
            viewingActivityLogs.value = response.data.activityLogs || [];
            viewingPermissions.value = response.data.allPermissions || [];
        }
    } catch (err) {
        console.error("Failed to load user details", err);
    } finally {
        isLoadingUserDetails.value = false;
    }
};

const userToDelete = ref<any>(null);
const showDeleteModal = ref(false);

const confirmDeleteUser = (user: any) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const submitDeleteUser = () => {
    if (!userToDelete.value) return;
    router.delete(route('admin.users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            userToDelete.value = null;
        }
    });
};

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const getRoleBadgeVariant = (roleName: string) => {
    switch (roleName.toLowerCase()) {
        case 'admin':
        case 'super_admin':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200';
        case 'staff':
        case 'lecturer':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200';
        case 'student':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200';
        case 'applicant':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200 border-slate-200';
    }
};

const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatDateTime = (dateString: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });
};

const breadcrumbs = [
    { title: 'System Settings', href: route('admin.settings.index') },
    { title: 'System Users', href: '#' }
];
</script>

<template>
    <Head title="System Users" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-8 px-6 space-y-8 w-full max-w-[1600px] mx-auto">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b pb-6">
                <div>
                     <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">System Users & Access</h1>
                     <p class="text-xs sm:text-sm text-muted-foreground mt-1 font-medium">Manage user accounts, assign system roles, and configure administrative access levels.</p>
                </div>
                <Button @click="showCreateModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg border-0 gap-2 px-5 h-11 rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                    <UserPlus class="w-4 h-4" />
                    Register New User
                </Button>
            </div>

            <!-- Smart Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border-none bg-gradient-to-br from-indigo-900 via-indigo-950 to-slate-900 text-white shadow-xl overflow-hidden relative">
                    <Users class="absolute -right-4 -bottom-4 w-28 h-28 text-white/10 rotate-12" />
                    <CardHeader class="pb-2">
                        <CardDescription class="text-indigo-200 font-bold uppercase tracking-widest text-[10px]">Total Population</CardDescription>
                        <CardTitle class="text-3xl font-black text-white">{{ stats.total }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-xs text-indigo-200/90 font-medium flex items-center gap-1.5">
                            <BadgeCheck class="w-3.5 h-3.5 text-indigo-400" /> System-wide user accounts
                        </div>
                    </CardContent>
                </Card>

                <Card class="border shadow-sm relative overflow-hidden bg-card">
                    <UserCheck class="absolute -right-4 -bottom-4 w-28 h-28 text-emerald-500/10 rotate-12" />
                    <CardHeader class="pb-2">
                        <CardDescription class="text-muted-foreground font-bold uppercase tracking-widest text-[10px]">Active Accounts</CardDescription>
                        <CardTitle class="text-3xl font-black text-emerald-600">{{ stats.active }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> 
                            {{ stats.total ? ((stats.active / stats.total) * 100).toFixed(1) : 0 }}% enabled
                        </div>
                    </CardContent>
                </Card>

                <Card class="border shadow-sm relative overflow-hidden bg-card">
                    <Shield class="absolute -right-4 -bottom-4 w-28 h-28 text-purple-500/10 rotate-12" />
                    <CardHeader class="pb-2">
                        <CardDescription class="text-muted-foreground font-bold uppercase tracking-widest text-[10px]">Administrators</CardDescription>
                        <CardTitle class="text-3xl font-black text-purple-600">{{ stats.admins }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
                            <ShieldAlert class="w-3.5 h-3.5 text-purple-500" /> Administrative privileges
                        </div>
                    </CardContent>
                </Card>

                <Card class="border shadow-sm relative overflow-hidden bg-card">
                    <UserX class="absolute -right-4 -bottom-4 w-28 h-28 text-red-500/10 rotate-12" />
                    <CardHeader class="pb-2">
                        <CardDescription class="text-muted-foreground font-bold uppercase tracking-widest text-[10px]">Deactivated</CardDescription>
                        <CardTitle class="text-3xl font-black text-red-600">{{ stats.inactive }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-xs text-muted-foreground font-medium flex items-center gap-1.5">
                            <Ban class="w-3.5 h-3.5 text-red-400" /> Accounts disabled
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Advanced Control Bar & Filters -->
            <Card class="border shadow-sm p-4 rounded-2xl space-y-3">
                <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                    <!-- Search input -->
                    <div class="relative flex-1 w-full">
                        <Search class="absolute left-3.5 top-3 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Search by full name, email, staff ID, or matric number..."
                            class="pl-10 pr-8 h-10 rounded-xl"
                            v-model="search"
                        />
                        <button v-if="search" @click="search = ''" class="absolute right-3 top-3 text-muted-foreground hover:text-foreground">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    
                    <!-- Filters Grid -->
                    <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                        <!-- Multi-Role Dropdown Filter -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="h-10 rounded-xl justify-between bg-background font-bold text-xs gap-2 min-w-[170px]">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <Shield class="w-3.5 h-3.5 text-indigo-600 shrink-0" />
                                        <span>{{ selectedRolesFilter.length === 0 ? 'Roles: All' : `Roles (${selectedRolesFilter.length})` }}</span>
                                    </div>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start" class="w-56 p-2 rounded-xl">
                                <DropdownMenuLabel class="text-[10px] font-bold text-muted-foreground uppercase">Multi-Select System Roles</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <div class="space-y-1 max-h-60 overflow-y-auto">
                                    <div 
                                        v-for="role in availableRoles" 
                                        :key="role.id" 
                                        @click="toggleRoleFilter(role.name)"
                                        class="flex items-center gap-2 p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer text-xs font-semibold"
                                    >
                                        <Checkbox :checked="selectedRolesFilter.includes(role.name)" @update:checked="() => {}" />
                                        <span>{{ formatRoleName(role.name) }}</span>
                                    </div>
                                </div>
                                <template v-if="selectedRolesFilter.length > 0">
                                    <DropdownMenuSeparator />
                                    <button 
                                        type="button" 
                                        @click="selectedRolesFilter = []" 
                                        class="w-full text-center text-xs font-bold text-red-600 py-1.5 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"
                                    >
                                        Clear Role Filters
                                    </button>
                                </template>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <!-- Status Filter -->
                        <div class="w-full sm:w-[150px]">
                            <Select v-model="selectedStatusFilter">
                                <SelectTrigger class="h-10 rounded-xl">
                                    <SelectValue placeholder="Status: All" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ALL">All Status</SelectItem>
                                    <SelectItem value="active">Active Only</SelectItem>
                                    <SelectItem value="inactive">Suspended / Inactive</SelectItem>
                                    <SelectItem value="trashed">Soft-Deleted / Trashed</SelectItem>
                                    <SelectItem value="verified">Verified Email</SelectItem>
                                    <SelectItem value="unverified">Unverified Email</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Sort Selector -->
                        <div class="w-full sm:w-[170px]">
                            <Select v-model="selectedSort">
                                <SelectTrigger class="h-10 rounded-xl">
                                    <SelectValue placeholder="Sort Order" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="created_at_desc">Newest First</SelectItem>
                                    <SelectItem value="created_at_asc">Oldest First</SelectItem>
                                    <SelectItem value="name_asc">Name (A-Z)</SelectItem>
                                    <SelectItem value="name_desc">Name (Z-A)</SelectItem>
                                    <SelectItem value="email_asc">Email (A-Z)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Per Page -->
                        <div class="w-24">
                            <Select v-model="perPage">
                                <SelectTrigger class="h-10 rounded-xl">
                                    <SelectValue placeholder="15 / page" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="10">10 / page</SelectItem>
                                    <SelectItem value="15">15 / page</SelectItem>
                                    <SelectItem value="25">25 / page</SelectItem>
                                    <SelectItem value="50">50 / page</SelectItem>
                                    <SelectItem value="100">100 / page</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Reset Button -->
                        <Button 
                            v-if="activeFiltersCount > 0" 
                            variant="ghost" 
                            @click="clearFilters" 
                            class="text-red-600 hover:bg-red-50 h-10 px-3 gap-1 rounded-xl"
                        >
                            <RotateCcw class="w-4 h-4" /> Reset ({{ activeFiltersCount }})
                        </Button>
                    </div>
                </div>

                <!-- Active Filter Tags Bar -->
                <div v-if="activeFiltersCount > 0" class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Active Filters:</span>
                    <Badge v-for="r in selectedRolesFilter" :key="r" variant="secondary" class="bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold gap-1 rounded-lg">
                        Role: {{ formatRoleName(r) }}
                        <X class="w-3 h-3 cursor-pointer hover:text-indigo-950" @click="removeRoleFilter(r)" />
                    </Badge>
                    <Badge v-if="selectedStatusFilter !== 'ALL'" variant="secondary" class="bg-slate-100 text-slate-700 border text-[10px] font-bold gap-1 rounded-lg">
                        Status: {{ selectedStatusFilter }}
                        <X class="w-3 h-3 cursor-pointer hover:text-slate-950" @click="selectedStatusFilter = 'ALL'" />
                    </Badge>
                    <Badge v-if="search" variant="secondary" class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold gap-1 rounded-lg">
                        Search: "{{ search }}"
                        <X class="w-3 h-3 cursor-pointer hover:text-amber-950" @click="search = ''" />
                    </Badge>
                </div>
            </Card>

            <!-- Users Table -->
            <Card class="border shadow-sm overflow-hidden rounded-2xl">
                <Table>
                    <TableHeader class="bg-slate-50 dark:bg-slate-900/50">
                        <TableRow>
                            <TableHead class="w-[300px]">User Account</TableHead>
                            <TableHead>Assigned Roles</TableHead>
                            <TableHead>Last Login</TableHead>
                            <TableHead>Registered</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id" class="group hover:bg-slate-50/50 transition-colors">
                             <TableCell>
                                <div class="flex items-center gap-3">
                                   <Avatar class="h-10 w-10 border shadow-xs">
                                        <AvatarFallback :class="user.deleted_at ? 'bg-slate-100 text-slate-400 font-bold' : user.is_active ? 'bg-indigo-50 text-indigo-700 font-black' : 'bg-amber-50 text-amber-600 font-bold'">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </AvatarFallback>
                                   </Avatar>
                                   <div class="flex flex-col min-w-0">
                                       <span :class="['font-bold text-foreground leading-snug truncate', (user.deleted_at || !user.is_active) && 'text-muted-foreground line-through']">
                                           {{ user.name }}
                                       </span>
                                       <span class="text-xs text-muted-foreground flex items-center gap-1 mt-0.5 truncate">
                                           <Mail class="w-3 h-3 text-muted-foreground/60" /> {{ user.email }}
                                       </span>
                                       <span v-if="user.staff" class="text-[10px] font-bold tracking-wide text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-1.5 py-0.5 rounded border border-indigo-200 dark:border-indigo-800 w-fit mt-0.5">
                                           Staff ID: {{ user.staff.staff_number }}
                                       </span>
                                       <span v-else-if="user.student" class="text-[10px] font-bold tracking-wide text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-800 w-fit mt-0.5">
                                           Reg No: {{ user.student.matriculation_number }}
                                       </span>
                                   </div>
                                </div>
                             </TableCell>

                             <TableCell>
                                 <div class="flex flex-wrap gap-1.5">
                                     <Badge 
                                         v-for="role in user.roles" 
                                         :key="role.id" 
                                         variant="outline"
                                         :class="['font-bold text-[10px] uppercase px-2 py-0.5 rounded-lg border', getRoleBadgeVariant(role.name)]"
                                     >
                                         {{ formatRoleName(role.name) }}
                                     </Badge>
                                     <span v-if="!user.roles || user.roles.length === 0" class="text-xs text-muted-foreground italic">No Roles Assigned</span>
                                 </div>
                             </TableCell>

                             <TableCell>
                                 <div v-if="user.last_login_at" class="flex flex-col">
                                     <span class="font-bold text-foreground text-[11px]">{{ formatDateTime(user.last_login_at) }}</span>
                                     <span v-if="user.last_login_ip" class="text-[10px] text-muted-foreground font-mono">IP: {{ user.last_login_ip }}</span>
                                 </div>
                                 <div v-else class="text-[11px] text-slate-400 italic">
                                     Never Logged In
                                 </div>
                             </TableCell>

                             <TableCell class="text-xs text-muted-foreground font-medium">
                                 {{ formatDate(user.created_at) }}
                             </TableCell>

                             <TableCell>
                                 <div v-if="user.deleted_at" class="flex items-center gap-2">
                                     <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                                     <span class="text-[11px] uppercase font-black text-slate-500">Soft-Deleted</span>
                                 </div>
                                 <div v-else-if="user.is_active" class="flex items-center gap-2">
                                     <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                                     <span class="text-[11px] uppercase font-black text-emerald-700">Active</span>
                                 </div>
                                 <div v-else class="flex items-center gap-2">
                                     <div class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]"></div>
                                     <span class="text-[11px] uppercase font-black text-amber-600">Suspended</span>
                                 </div>
                             </TableCell>

                             <TableCell class="text-right">
                                 <DropdownMenu>
                                     <DropdownMenuTrigger as-child>
                                         <Button variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground rounded-lg">
                                             <MoreHorizontal class="w-4 h-4" />
                                         </Button>
                                     </DropdownMenuTrigger>
                                     <DropdownMenuContent align="end" class="w-56 rounded-xl">
                                         <DropdownMenuLabel class="text-xs font-bold text-muted-foreground uppercase">User Actions</DropdownMenuLabel>
                                         <DropdownMenuSeparator />
                                         <DropdownMenuItem @click="openViewModal(user)" class="cursor-pointer font-semibold">
                                             <Eye class="w-4 h-4 mr-2 text-indigo-600" /> View Details & Activity Logs
                                         </DropdownMenuItem>
                                         <DropdownMenuSeparator />
                                         <template v-if="user.deleted_at">
                                             <DropdownMenuItem @click="restoreUser(user)" class="text-emerald-600 font-bold cursor-pointer">
                                                 <RotateCcw class="w-4 h-4 mr-2" /> Restore Account
                                             </DropdownMenuItem>
                                         </template>
                                         <template v-else>
                                             <DropdownMenuItem @click="openEditModal(user)" class="cursor-pointer">
                                                 <Pencil class="w-4 h-4 mr-2 text-blue-600" /> Edit Details & IDs
                                             </DropdownMenuItem>
                                             <DropdownMenuItem @click="openRoleModal(user)" class="cursor-pointer">
                                                 <ShieldAlert class="w-4 h-4 mr-2 text-purple-600" /> Manage Roles
                                             </DropdownMenuItem>
                                             <DropdownMenuItem @click="openResetPasswordModal(user)" class="cursor-pointer">
                                                 <Key class="w-4 h-4 mr-2 text-amber-600" /> Reset Password
                                             </DropdownMenuItem>
                                             <DropdownMenuSeparator />
                                             <DropdownMenuItem @click="toggleStatus(user)" class="cursor-pointer" :class="user.is_active ? 'text-amber-600' : 'text-emerald-600'">
                                                 <component :is="user.is_active ? Ban : Unlock" class="w-4 h-4 mr-2" />
                                                 {{ user.is_active ? 'Suspend Account' : 'Reactivate Account' }}
                                             </DropdownMenuItem>
                                             <DropdownMenuSeparator />
                                             <DropdownMenuItem @click="confirmDeleteUser(user)" class="text-red-600 cursor-pointer">
                                                 <Trash2 class="w-4 h-4 mr-2" /> Soft-Delete Account
                                             </DropdownMenuItem>
                                         </template>
                                     </DropdownMenuContent>
                                 </DropdownMenu>
                             </TableCell>
                        </TableRow>

                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="5" class="h-64 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="p-4 bg-slate-50 rounded-full border">
                                        <Users class="w-8 h-8 text-muted-foreground" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-bold text-base">No matching users found</p>
                                        <p class="text-xs text-muted-foreground">Try clearing your search query or expanding filter parameters.</p>
                                    </div>
                                    <Button v-if="activeFiltersCount > 0" variant="outline" size="sm" @click="clearFilters" class="mt-2 rounded-xl">
                                        Clear All Filters
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination Footer -->
                <CardFooter class="flex items-center justify-between border-t p-4 px-6 bg-slate-50/50">
                    <div class="text-xs text-muted-foreground font-medium">
                        Showing <span class="font-bold text-foreground">{{ users.from || 0 }}</span> to <span class="font-bold text-foreground">{{ users.to || 0 }}</span> of <span class="font-bold text-foreground">{{ users.total }}</span> user accounts
                    </div>
                    <div class="flex gap-1">
                         <Button 
                            v-for="(link, i) in users.links" 
                            :key="i"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            as-child
                            class="h-8 min-w-[32px] px-2 text-xs font-bold rounded-lg"
                         >
                            <Link v-if="link.url" :href="link.url" v-html="link.label" />
                            <span v-else v-html="link.label"></span>
                         </Button>
                    </div>
                </CardFooter>
            </Card>

            <!-- Register New User Modal -->
            <Dialog v-model:open="showCreateModal">
                <DialogContent class="sm:max-w-[620px] p-0 overflow-hidden border-0 shadow-2xl rounded-3xl max-h-[90vh] flex flex-col">
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-indigo-900 via-indigo-950 to-slate-950 p-6 sm:p-8 text-white relative shrink-0">
                        <div class="absolute right-6 top-6 h-12 w-12 rounded-2xl bg-white/10 flex items-center justify-center backdrop-blur-md">
                            <UserPlus class="w-6 h-6 text-indigo-300" />
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-200 border border-indigo-500/30 text-[10px] uppercase font-bold tracking-widest mb-2">
                            <Shield class="w-3 h-3" /> Account Provisioning
                        </span>
                        <DialogTitle class="text-xl sm:text-2xl font-black tracking-tight">Register System User</DialogTitle>
                        <DialogDescription class="text-indigo-200/80 text-xs sm:text-sm mt-1 font-medium">
                            Configure user identity credentials and assign preliminary system access roles.
                        </DialogDescription>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitCreate" class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
                        <!-- Identity Info -->
                        <div class="space-y-4">
                            <div class="text-[11px] font-black uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                                <User class="w-3.5 h-3.5 text-indigo-600" /> Identity & Contact Credentials
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="create-name" class="text-xs font-bold text-foreground">Full Name</Label>
                                    <div class="relative">
                                        <User class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground" />
                                        <Input id="create-name" v-model="createForm.name" required placeholder="e.g. Dr. Alex Morgan" class="pl-10 h-11 rounded-xl" />
                                    </div>
                                    <p v-if="createForm.errors.name" class="text-xs text-red-500 font-semibold">{{ createForm.errors.name }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="create-email" class="text-xs font-bold text-foreground">Email Address</Label>
                                    <div class="relative">
                                        <Mail class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground" />
                                        <Input id="create-email" v-model="createForm.email" type="email" required placeholder="alex@university.edu" class="pl-10 h-11 rounded-xl" />
                                    </div>
                                    <p v-if="createForm.errors.email" class="text-xs text-red-500 font-semibold">{{ createForm.errors.email }}</p>
                                </div>
                            </div>

                            <!-- Optional Custom Numbers for Staff / Student -->
                            <div v-if="isStaffRoleSelected || isStudentRoleSelected" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div v-if="isStaffRoleSelected" class="space-y-2">
                                    <Label for="create-staff-num" class="text-xs font-bold text-foreground">Staff ID Number (Optional)</Label>
                                    <div class="relative">
                                        <BadgeCheck class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground" />
                                        <Input id="create-staff-num" v-model="createForm.staff_number" placeholder="e.g. STF/2026/001 (Blank = Auto)" class="pl-10 h-11 rounded-xl" />
                                    </div>
                                    <p v-if="createForm.errors.staff_number" class="text-xs text-red-500 font-semibold">{{ createForm.errors.staff_number }}</p>
                                </div>

                                <div v-if="isStudentRoleSelected" class="space-y-2">
                                    <Label for="create-matric-num" class="text-xs font-bold text-foreground">Matriculation / Reg No. (Optional)</Label>
                                    <div class="relative">
                                        <BadgeCheck class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground" />
                                        <Input id="create-matric-num" v-model="createForm.matriculation_number" placeholder="e.g. UNI/2024/0001 (Blank = Auto)" class="pl-10 h-11 rounded-xl" />
                                    </div>
                                    <p v-if="createForm.errors.matriculation_number" class="text-xs text-red-500 font-semibold">{{ createForm.errors.matriculation_number }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Security & Password -->
                        <div class="space-y-4 pt-4 border-t">
                            <div class="flex items-center justify-between">
                                <div class="text-[11px] font-black uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                                    <Lock class="w-3.5 h-3.5 text-indigo-600" /> Authentication Security
                                </div>
                                <Button type="button" variant="outline" size="sm" @click="generateRandomPassword" class="h-7 text-[11px] font-bold gap-1.5 rounded-lg border-indigo-200 text-indigo-700 bg-indigo-50/50 hover:bg-indigo-100 cursor-pointer">
                                    <Sparkles class="w-3 h-3" /> Auto-Generate Password
                                </Button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="create-password" class="text-xs font-bold text-foreground">Password</Label>
                                    <div class="relative">
                                        <Lock class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground" />
                                        <Input id="create-password" v-model="createForm.password" :type="showCreatePassword ? 'text' : 'password'" required placeholder="••••••••" class="pl-10 pr-10 h-11 rounded-xl" />
                                        <button type="button" @click="showCreatePassword = !showCreatePassword" class="absolute right-3 top-3.5 text-muted-foreground hover:text-foreground">
                                            <component :is="showCreatePassword ? EyeOff : Eye" class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <p v-if="createForm.errors.password" class="text-xs text-red-500 font-semibold">{{ createForm.errors.password }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="create-password-confirm" class="text-xs font-bold text-foreground">Confirm Password</Label>
                                    <div class="relative">
                                        <Lock class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground" />
                                        <Input id="create-password-confirm" v-model="createForm.password_confirmation" :type="showCreatePassword ? 'text' : 'password'" required placeholder="••••••••" class="pl-10 h-11 rounded-xl" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Role Selection -->
                        <div class="space-y-3 pt-4 border-t">
                            <div class="flex items-center justify-between">
                                <div class="text-[11px] font-black uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                                    <ShieldAlert class="w-3.5 h-3.5 text-indigo-600" /> Assign System Roles
                                </div>
                                <span class="text-[11px] text-muted-foreground font-medium">Select at least 1 role</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-48 overflow-y-auto p-1">
                                <div 
                                    v-for="role in availableRoles" 
                                    :key="role.id" 
                                    :class="[
                                        'flex items-center justify-between p-3 rounded-xl border transition-all cursor-pointer select-none',
                                        createForm.roles.includes(role.name) 
                                            ? 'bg-indigo-50/80 border-indigo-400 dark:bg-indigo-950/60 dark:border-indigo-600 ring-2 ring-indigo-500/20' 
                                            : 'bg-card hover:bg-slate-50 border-slate-200 dark:hover:bg-slate-900'
                                    ]"
                                    @click="toggleCreateUserRole(role.name)"
                                >
                                    <div class="flex items-center gap-3">
                                        <div :class="['w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs', createForm.roles.includes(role.name) ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600']">
                                            <Shield class="w-3.5 h-3.5" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-foreground capitalize">{{ formatRoleName(role.name) }}</span>
                                            <span class="text-[10px] text-muted-foreground">Access Role</span>
                                        </div>
                                    </div>
                                    <Checkbox 
                                        :checked="createForm.roles.includes(role.name)"
                                        class="rounded-md h-4 w-4 border-indigo-400"
                                    />
                                </div>
                            </div>
                            <p v-if="createForm.errors.roles" class="text-xs text-red-500 font-semibold">{{ createForm.errors.roles }}</p>
                        </div>

                        <DialogFooter class="pt-4 border-t shrink-0 flex gap-3">
                            <Button type="button" variant="outline" @click="showCreateModal = false" class="sm:flex-1 h-11 rounded-xl font-bold">
                                Cancel
                            </Button>
                            <Button 
                                type="submit"
                                :disabled="createForm.processing" 
                                class="sm:flex-[2] h-11 bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-200 dark:shadow-none rounded-xl font-bold tracking-wide cursor-pointer"
                            >
                                <span v-if="createForm.processing">Creating Account...</span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    <UserPlus class="w-4 h-4" /> Provision System User
                                </span>
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Edit User Details Modal -->
            <Dialog v-model:open="showEditModal">
                <DialogContent class="sm:max-w-[500px] p-0 overflow-hidden border-0 shadow-2xl rounded-3xl">
                    <div class="bg-slate-900 p-6 text-white relative">
                        <Pencil class="absolute -right-4 -top-4 w-28 h-28 text-white/10 rotate-12" />
                        <DialogTitle class="text-xl font-bold">Edit User Details</DialogTitle>
                        <DialogDescription class="text-slate-300 text-xs mt-1">
                            Update profile credentials for <strong>{{ editingUserForDetails?.name }}</strong>.
                        </DialogDescription>
                    </div>

                    <form @submit.prevent="submitEdit" class="p-6 space-y-5">
                        <div class="space-y-2">
                            <Label for="edit-name" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Full Name</Label>
                            <Input id="edit-name" v-model="editForm.name" required class="h-11 rounded-xl" />
                            <p v-if="editForm.errors.name" class="text-xs text-red-500 font-semibold">{{ editForm.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-email" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Email Address</Label>
                            <Input id="edit-email" v-model="editForm.email" type="email" required class="h-11 rounded-xl" />
                            <p v-if="editForm.errors.email" class="text-xs text-red-500 font-semibold">{{ editForm.errors.email }}</p>
                        </div>

                        <!-- Staff Number Editing if Staff -->
                        <div v-if="editingUserForDetails?.staff || editForm.staff_number" class="space-y-2">
                            <Label for="edit-staff-number" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Staff ID Number</Label>
                            <div class="relative">
                                <BadgeCheck class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground" />
                                <Input id="edit-staff-number" v-model="editForm.staff_number" placeholder="e.g. STF/2026/001" class="pl-10 h-11 rounded-xl" />
                            </div>
                            <p v-if="editForm.errors.staff_number" class="text-xs text-red-500 font-semibold">{{ editForm.errors.staff_number }}</p>
                        </div>

                        <!-- Registration / Matriculation Number Editing if Student -->
                        <div v-if="editingUserForDetails?.student || editForm.matriculation_number" class="space-y-2">
                            <Label for="edit-matric-number" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Matriculation / Reg Number</Label>
                            <div class="relative">
                                <BadgeCheck class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground" />
                                <Input id="edit-matric-number" v-model="editForm.matriculation_number" placeholder="e.g. UNI/2024/0001" class="pl-10 h-11 rounded-xl" />
                            </div>
                            <p v-if="editForm.errors.matriculation_number" class="text-xs text-red-500 font-semibold">{{ editForm.errors.matriculation_number }}</p>
                        </div>

                        <div class="space-y-2 pt-2 border-t">
                            <Label for="edit-password" class="text-xs font-bold uppercase tracking-wider text-muted-foreground">New Password (Optional)</Label>
                            <div class="relative">
                                <Input id="edit-password" v-model="editForm.password" :type="showEditPassword ? 'text' : 'password'" placeholder="Leave blank to keep unchanged" class="h-11 rounded-xl pr-10" />
                                <button type="button" @click="showEditPassword = !showEditPassword" class="absolute right-3 top-3 text-muted-foreground hover:text-foreground">
                                    <component :is="showEditPassword ? EyeOff : Eye" class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="editForm.errors.password" class="text-xs text-red-500 font-semibold">{{ editForm.errors.password }}</p>
                        </div>

                        <DialogFooter class="pt-4 border-t">
                            <Button type="button" variant="ghost" @click="showEditModal = false" class="rounded-xl font-bold">Cancel</Button>
                            <Button 
                                type="submit" 
                                :disabled="editForm.processing" 
                                class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold px-6"
                            >
                                Save Changes
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Role Assignment Modal -->
            <Dialog v-model:open="showRoleModal">
                <DialogContent class="sm:max-w-[480px] p-0 overflow-hidden border-0 shadow-2xl rounded-3xl">
                    <div class="bg-indigo-900 p-6 text-white relative">
                        <Shield class="absolute -right-6 -top-6 w-32 h-32 text-white/10 rotate-12" />
                        <DialogTitle class="text-xl font-bold">Manage Roles & Privileges</DialogTitle>
                        <DialogDescription class="text-indigo-200 text-xs mt-1">
                            Configure system roles for <strong>{{ editingUserForRoles?.name }}</strong>.
                        </DialogDescription>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <div class="space-y-3">
                            <Label class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Select System Roles</Label>
                            <div class="grid grid-cols-2 gap-3">
                                <div v-for="role in availableRoles" :key="role.id" 
                                    class="flex items-center gap-2.5 p-3 rounded-xl border hover:border-indigo-300 transition-all cursor-pointer bg-slate-50/50"
                                    @click="toggleUserRole(role.name)"
                                >
                                    <Checkbox 
                                        :checked="roleForm.roles.includes(role.name)"
                                        class="rounded-md"
                                    />
                                    <Label class="text-xs font-bold leading-none cursor-pointer capitalize">
                                        {{ formatRoleName(role.name) }}
                                    </Label>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl border border-indigo-100 flex gap-3 items-start">
                            <ShieldAlert class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" />
                            <p class="text-xs text-indigo-900 dark:text-indigo-200 leading-relaxed font-medium">
                                Granting or revoking roles immediately adjusts permission boundaries.
                            </p>
                        </div>
                    </div>

                    <DialogFooter class="p-6 pt-0 border-t flex justify-between">
                        <Button type="button" variant="ghost" @click="showRoleModal = false" class="rounded-xl font-bold">Cancel</Button>
                        <Button type="button" @click="submitRoles" :disabled="roleForm.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold px-6">
                            Save Roles
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Reset Password Modal -->
            <Dialog v-model:open="showResetPasswordModal">
                <DialogContent class="sm:max-w-[450px] p-0 overflow-hidden border-0 shadow-2xl rounded-3xl">
                    <div class="bg-amber-600 p-6 text-white relative">
                        <Key class="absolute -right-4 -top-4 w-28 h-28 text-white/10 rotate-12" />
                        <DialogTitle class="text-xl font-bold">Reset Password</DialogTitle>
                        <DialogDescription class="text-amber-100 text-xs mt-1">
                            Reset login password for <strong>{{ userForPasswordReset?.name }}</strong>.
                        </DialogDescription>
                    </div>

                    <form @submit.prevent="submitResetPassword" class="p-6 space-y-5">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <Label class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Reset Method</Label>
                                <Button type="button" variant="link" size="sm" @click="showManualPasswordInput = !showManualPasswordInput" class="h-auto p-0 text-xs text-amber-600">
                                    {{ showManualPasswordInput ? 'Auto-Generate Password' : 'Set Specific Password' }}
                                </Button>
                            </div>

                            <div v-if="showManualPasswordInput" class="space-y-2">
                                <Input 
                                    v-model="resetPasswordForm.password" 
                                    type="text" 
                                    placeholder="Enter new password (min 8 chars)" 
                                    class="h-11 rounded-xl"
                                />
                            </div>
                            <div v-else class="p-4 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 font-medium">
                                A random secure 10-character password will be generated and displayed upon confirmation.
                            </div>
                        </div>

                        <DialogFooter class="pt-4 border-t">
                            <Button type="button" variant="ghost" @click="showResetPasswordModal = false" class="rounded-xl font-bold">Cancel</Button>
                            <Button 
                                type="submit" 
                                :disabled="resetPasswordForm.processing" 
                                class="bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold px-6"
                            >
                                Confirm Password Reset
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Delete User Confirmation Modal -->
            <Dialog v-model:open="showDeleteModal">
                <DialogContent class="sm:max-w-[420px] p-6 rounded-3xl">
                    <DialogHeader class="space-y-3">
                        <div class="h-12 w-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center border border-red-200">
                            <AlertTriangle class="h-6 w-6" />
                        </div>
                        <DialogTitle class="text-xl font-bold">Soft-Delete User Account</DialogTitle>
                        <DialogDescription class="text-xs leading-relaxed">
                            Are you sure you want to soft-delete user account <strong>{{ userToDelete?.name }}</strong> ({{ userToDelete?.email }})? The account will be deactivated and hidden, but can be restored from the Trashed filter at any time.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter class="pt-4 flex gap-2">
                        <Button type="button" variant="ghost" @click="showDeleteModal = false" class="flex-1 rounded-xl font-bold">Cancel</Button>
                        <Button type="button" @click="submitDeleteUser" class="flex-1 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold">
                            Soft-Delete Account
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- View User Profile & Activity Logs Modal -->
            <Dialog v-model:open="showViewModal">
                <DialogContent class="sm:max-w-[720px] p-0 overflow-hidden border-0 shadow-2xl rounded-3xl max-h-[90vh] flex flex-col">
                    <!-- Modal Dark Gradient Header -->
                    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 text-white relative shrink-0">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <Avatar class="h-14 w-14 border-2 border-indigo-400/30 shadow-lg">
                                    <AvatarFallback class="bg-indigo-600 text-white font-black text-xl">
                                        {{ viewingUser?.name?.charAt(0)?.toUpperCase() }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-xl font-black text-white tracking-tight">{{ viewingUser?.name }}</h3>
                                        <Badge v-if="viewingUser?.deleted_at" class="bg-slate-700 text-slate-200 border-0 text-[10px]">Soft-Deleted</Badge>
                                        <Badge v-else-if="viewingUser?.is_active == 1 || viewingUser?.is_active === true" class="bg-emerald-500/20 text-emerald-300 border-emerald-500/30 text-[10px]">Active</Badge>
                                        <Badge v-else class="bg-amber-500/20 text-amber-300 border-amber-500/30 text-[10px]">Suspended</Badge>
                                    </div>
                                    <p class="text-xs text-indigo-200/80 flex items-center gap-1.5 mt-0.5 font-medium">
                                        <Mail class="w-3.5 h-3.5 text-indigo-400" /> {{ viewingUser?.email }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span v-if="viewingUser?.staff" class="text-[10px] font-bold tracking-wide text-indigo-200 bg-indigo-900/80 px-2 py-0.5 rounded-md border border-indigo-700">
                                            Staff ID: {{ viewingUser?.staff?.staff_number }}
                                        </span>
                                        <span v-if="viewingUser?.student" class="text-[10px] font-bold tracking-wide text-emerald-200 bg-emerald-900/80 px-2 py-0.5 rounded-md border border-emerald-700">
                                            Reg No: {{ viewingUser?.student?.matriculation_number }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Tabs Header -->
                        <div class="flex gap-2 mt-6 border-b border-indigo-900/60 pb-0.5">
                            <button 
                                type="button" 
                                @click="activeViewTab = 'details'"
                                :class="['px-4 py-2 text-xs font-bold rounded-t-xl transition-all cursor-pointer flex items-center gap-2', activeViewTab === 'details' ? 'bg-background text-foreground shadow-sm' : 'text-indigo-200/70 hover:text-white hover:bg-indigo-900/40']"
                            >
                                <User class="w-3.5 h-3.5" /> Identity & Profile
                            </button>
                            <button 
                                type="button" 
                                @click="activeViewTab = 'logs'"
                                :class="['px-4 py-2 text-xs font-bold rounded-t-xl transition-all cursor-pointer flex items-center gap-2', activeViewTab === 'logs' ? 'bg-background text-foreground shadow-sm' : 'text-indigo-200/70 hover:text-white hover:bg-indigo-900/40']"
                            >
                                <Sparkles class="w-3.5 h-3.5 text-indigo-400" /> Activity Logs
                                <span class="bg-indigo-500/20 text-indigo-200 px-1.5 py-0.2 rounded-full text-[10px] font-black">{{ viewingActivityLogs.length }}</span>
                            </button>
                            <button 
                                type="button" 
                                @click="activeViewTab = 'permissions'"
                                :class="['px-4 py-2 text-xs font-bold rounded-t-xl transition-all cursor-pointer flex items-center gap-2', activeViewTab === 'permissions' ? 'bg-background text-foreground shadow-sm' : 'text-indigo-200/70 hover:text-white hover:bg-indigo-900/40']"
                            >
                                <Shield class="w-3.5 h-3.5 text-purple-400" /> Permissions
                                <span class="bg-purple-500/20 text-purple-200 px-1.5 py-0.2 rounded-full text-[10px] font-black">{{ viewingPermissions.length }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body Container -->
                    <div class="p-6 overflow-y-auto flex-1 space-y-6">
                        <!-- Loading State -->
                        <div v-if="isLoadingUserDetails" class="py-12 flex flex-col items-center justify-center space-y-3">
                            <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                            <p class="text-xs text-muted-foreground font-semibold">Loading detailed user profile & audit logs...</p>
                        </div>

                        <template v-else>
                            <!-- Tab 1: Profile & Identity Details -->
                            <div v-if="activeViewTab === 'details'" class="space-y-6">
                                <!-- Core Metadata Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900 border space-y-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Account Status</span>
                                        <p class="text-xs font-bold flex items-center gap-1.5">
                                            <span :class="['w-2 h-2 rounded-full', viewingUser?.is_active ? 'bg-emerald-500' : 'bg-amber-500']"></span>
                                            {{ viewingUser?.is_active ? 'Active & Enabled' : 'Suspended / Disabled' }}
                                        </p>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900 border space-y-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Email Verified</span>
                                        <p class="text-xs font-bold text-foreground">
                                            {{ viewingUser?.email_verified_at ? formatDateTime(viewingUser.email_verified_at) : 'Not Verified' }}
                                        </p>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900 border space-y-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Account Created</span>
                                        <p class="text-xs font-bold text-foreground">{{ formatDateTime(viewingUser?.created_at) }}</p>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900 border space-y-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Last Login</span>
                                        <p class="text-xs font-bold text-foreground">
                                            {{ viewingUser?.last_login_at ? formatDateTime(viewingUser.last_login_at) : 'Never Logged In' }}
                                        </p>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900 border space-y-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Last Login IP</span>
                                        <p class="text-xs font-bold font-mono text-foreground">{{ viewingUser?.last_login_ip || 'N/A' }}</p>
                                    </div>

                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900 border space-y-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Assigned Roles</span>
                                        <div class="flex flex-wrap gap-1 mt-0.5">
                                            <Badge v-for="r in viewingUser?.roles" :key="r.id" class="text-[9px] uppercase bg-indigo-50 text-indigo-700 border-indigo-200">
                                                {{ formatRoleName(r.name) }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>

                                <!-- Linked Profile Details (Staff or Student or Applicant) -->
                                <div v-if="viewingUser?.staff" class="p-5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900 space-y-3">
                                    <div class="flex items-center justify-between border-b border-indigo-200/50 dark:border-indigo-800 pb-2">
                                        <h4 class="text-xs font-black uppercase tracking-wider text-indigo-950 dark:text-indigo-200 flex items-center gap-2">
                                            <UserCheck class="w-4 h-4 text-indigo-600" /> Linked Staff Profile
                                        </h4>
                                        <Badge variant="outline" class="bg-indigo-100 text-indigo-800 border-indigo-300 font-bold text-[10px]">
                                            {{ viewingUser?.staff?.is_academic ? 'Academic Staff' : 'Non-Academic Staff' }}
                                        </Badge>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                                        <div>
                                            <span class="text-[10px] text-muted-foreground font-bold uppercase block">Staff ID</span>
                                            <span class="font-black text-indigo-700 dark:text-indigo-300 font-mono">{{ viewingUser?.staff?.staff_number }}</span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-muted-foreground font-bold uppercase block">Department</span>
                                            <span class="font-bold text-foreground">{{ viewingUser?.staff?.department?.name || 'Unassigned' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-muted-foreground font-bold uppercase block">Designation</span>
                                            <span class="font-bold text-foreground">{{ viewingUser?.staff?.designation?.name || 'Standard Staff' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="viewingUser?.student" class="p-5 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900 space-y-3">
                                    <div class="flex items-center justify-between border-b border-emerald-200/50 dark:border-emerald-800 pb-2">
                                        <h4 class="text-xs font-black uppercase tracking-wider text-emerald-950 dark:text-emerald-200 flex items-center gap-2">
                                            <BadgeCheck class="w-4 h-4 text-emerald-600" /> Linked Student Record
                                        </h4>
                                        <Badge variant="outline" class="bg-emerald-100 text-emerald-800 border-emerald-300 font-bold text-[10px] uppercase">
                                            {{ viewingUser?.student?.status || 'Active' }}
                                        </Badge>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                                        <div>
                                            <span class="text-[10px] text-muted-foreground font-bold uppercase block">Matriculation No.</span>
                                            <span class="font-black text-emerald-700 dark:text-emerald-300 font-mono">{{ viewingUser?.student?.matriculation_number }}</span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-muted-foreground font-bold uppercase block">Academic Level</span>
                                            <span class="font-bold text-foreground">{{ viewingUser?.student?.level || '100' }} Level</span>
                                        </div>
                                        <div>
                                            <span class="text-[10px] text-muted-foreground font-bold uppercase block">Programme</span>
                                            <span class="font-bold text-foreground">{{ viewingUser?.student?.programme?.name || 'General Studies' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 2: Activity Audit Logs Timeline -->
                            <div v-else-if="activeViewTab === 'logs'" class="space-y-4">
                                <div v-if="viewingActivityLogs.length === 0" class="py-12 text-center space-y-2">
                                    <p class="text-sm font-bold text-muted-foreground">No activity logs recorded for this user yet.</p>
                                    <p class="text-xs text-muted-foreground">Actions performed by or on this user will automatically appear here.</p>
                                </div>

                                <div v-else class="relative border-l-2 border-slate-200 dark:border-slate-800 ml-4 space-y-6 py-2">
                                    <div v-for="log in viewingActivityLogs" :key="log.id" class="relative pl-6 group">
                                        <!-- Timeline Node Dot -->
                                        <div class="absolute -left-[9px] top-1 h-4 w-4 rounded-full bg-indigo-600 border-2 border-background shadow-xs"></div>
                                        
                                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900 border space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-black text-foreground">{{ log.description }}</span>
                                                <span class="text-[10px] text-muted-foreground font-medium">{{ formatDateTime(log.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[11px] text-muted-foreground">
                                                <span class="font-semibold text-indigo-600">Caused by:</span> 
                                                <span>{{ log.causer?.name || 'System Auto' }} ({{ log.causer?.email || 'N/A' }})</span>
                                            </div>
                                            <div v-if="log.properties && Object.keys(log.properties).length > 0" class="mt-2 p-2 bg-background rounded-xl border text-[10px] font-mono overflow-x-auto text-slate-600 dark:text-slate-400">
                                                {{ JSON.stringify(log.properties) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 3: Permissions -->
                            <div v-else-if="activeViewTab === 'permissions'" class="space-y-4">
                                <div v-if="viewingPermissions.length === 0" class="py-12 text-center">
                                    <p class="text-sm font-bold text-muted-foreground">No explicit permissions assigned.</p>
                                </div>
                                <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-72 overflow-y-auto pr-1">
                                    <div v-for="perm in viewingPermissions" :key="perm" class="flex items-center gap-2 p-2.5 rounded-xl bg-purple-50/50 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900 text-xs font-bold text-purple-900 dark:text-purple-200">
                                        <Shield class="w-3.5 h-3.5 text-purple-600 shrink-0" />
                                        <span class="truncate font-mono text-[11px]">{{ perm }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <DialogFooter class="p-4 border-t shrink-0 bg-slate-50 dark:bg-slate-900">
                        <Button type="button" variant="outline" @click="showViewModal = false" class="w-full sm:w-auto h-10 rounded-xl font-bold">
                            Close Inspection
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AdminLayout>
</template>
