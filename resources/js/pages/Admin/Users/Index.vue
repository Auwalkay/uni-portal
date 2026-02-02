<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { 
    Search, 
    Filter, 
    X,
    Users,
    UserPlus,
    Shield,
    Mail,
    Phone,
    Calendar,
    BadgeCheck,
    MoreVertical,
    Pencil,
    Trash2,
    Lock,
    Unlock,
    Settings,
    MoreHorizontal,
    ShieldAlert,
    Ban,
    UserCheck,
    UserX,
    Server,
    LayoutDashboard,
    Key,
    UserCircle
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
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
    };
    availableRoles: Array<{ id: number; name: string }>;
    stats: {
        total: number;
        active: number;
        inactive: number;
        admins: number;
        staff: number;
    };
}>();

const search = ref(props.filters.search || '');
const selectedRoleFilter = ref(props.filters.role || 'ALL');

const updateFilters = debounce(() => {
    router.get(route('admin.users.index'), {
        search: search.value,
        role: selectedRoleFilter.value === 'ALL' ? '' : selectedRoleFilter.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, 300);

watch([search, selectedRoleFilter], () => {
    updateFilters();
});

const clearFilters = () => {
    search.value = '';
    selectedRoleFilter.value = 'ALL';
};

// Role Assignment Management
const showRoleModal = ref(false);
const editingUser = ref<any>(null);

const roleForm = useForm({
    roles: [] as string[]
});

const openRoleModal = (user: any) => {
    editingUser.value = user;
    roleForm.roles = user.roles.map((r: any) => r.name);
    showRoleModal.value = true;
};

const submitRoles = () => {
    roleForm.patch(route('admin.users.roles.update', editingUser.value.id), {
        onSuccess: () => {
            showRoleModal.value = false;
            editingUser.value = null;
        }
    });
};

const toggleUserRole = (roleName: string) => {
    const index = roleForm.roles.indexOf(roleName);
    if (index > -1) {
        roleForm.roles.splice(index, 1);
    } else {
        roleForm.roles.push(roleName);
    }
};

// Create User Management
const showCreateModal = ref(false);
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[]
});

const submitCreate = () => {
    createForm.post(route('admin.users.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

const toggleStatus = (user: any) => {
    if (confirm(`Are you sure you want to ${user.is_active ? 'deactivate' : 'activate'} ${user.name}?`)) {
        router.patch(route('admin.users.status.toggle', user.id));
    }
};

const deleteUser = (user: any) => {
    if (confirm(`CRITICAL: Are you sure you want to PERMANENTLY delete ${user.name}? This action cannot be undone.`)) {
        router.delete(route('admin.settings.index')); // Placeholder - we need to confirm delete route
        // Wait, I didn't add the delete route to settings.php yet. 
        // Let's implement toggleStatus first as it's safer.
    }
};

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const breadcrumbs = [
    { title: 'System Settings', href: route('admin.settings.index') },
    { title: 'User Management', href: '#' }
];
</script>

<template>
    <Head title="User Management" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-6xl mx-auto">

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                     <h1 class="text-3xl font-bold tracking-tight text-foreground">User Management</h1>
                     <p class="text-muted-foreground mt-1 text-sm">Monitor system users, manage access levels, and assign administrative roles.</p>
                </div>
                <Button @click="showCreateModal = true" class="bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-200 dark:shadow-none gap-2 px-6 h-12 rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <UserPlus class="w-5 h-5" />
                    Register New User
                </Button>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border-none bg-indigo-600 text-white shadow-xl shadow-indigo-100 dark:shadow-none overflow-hidden relative">
                    <Users class="absolute -right-4 -bottom-4 w-24 h-24 text-white/10 rotate-12" />
                    <CardHeader class="pb-2">
                        <CardDescription class="text-indigo-100/80 font-bold uppercase tracking-widest text-[10px]">Total Users</CardDescription>
                        <CardTitle class="text-3xl font-black">{{ stats.total }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-[10px] text-indigo-100 flex items-center gap-1.5">
                            <BadgeCheck class="w-3 h-3" /> System-wide accounts
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
                    <UserCheck class="absolute -right-4 -bottom-4 w-24 h-24 text-slate-100 dark:text-slate-900 rotate-12" />
                    <CardHeader class="pb-2">
                        <CardDescription class="text-muted-foreground font-bold uppercase tracking-widest text-[10px]">Active Now</CardDescription>
                        <CardTitle class="text-3xl font-black text-green-600">{{ stats.active }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-[10px] text-muted-foreground flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span> {{ ((stats.active / stats.total) * 100).toFixed(1) }}% of total population
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden">
                    <Shield class="absolute -right-4 -bottom-4 w-24 h-24 text-slate-100 dark:text-slate-900 rotate-12" />
                    <CardHeader class="pb-2">
                        <CardDescription class="text-muted-foreground font-bold uppercase tracking-widest text-[10px]">Administrators</CardDescription>
                        <CardTitle class="text-3xl font-black text-indigo-600">{{ stats.admins }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-[10px] text-muted-foreground flex items-center gap-1.5">
                            <ShieldAlert class="w-3 h-3 text-indigo-500" /> Elevated system access
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden text-destructive">
                    <UserX class="absolute -right-4 -bottom-4 w-24 h-24 text-red-50 dark:text-red-950/20 rotate-12" />
                    <CardHeader class="pb-2">
                        <CardDescription class="text-muted-foreground font-bold uppercase tracking-widest text-[10px]">Inactive</CardDescription>
                        <CardTitle class="text-3xl font-black">{{ stats.inactive }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-[10px] text-muted-foreground flex items-center gap-1.5">
                            <Ban class="w-3 h-3 text-red-400" /> Accounts disabled
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
             <div class="bg-white dark:bg-slate-950 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row gap-4 items-center">
                <div class="relative flex-1 w-full">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        type="search"
                        placeholder="Search users by name or email..."
                        class="pl-10 h-10 border-slate-200"
                        v-model="search"
                    />
                </div>
                
                <Select v-model="selectedRoleFilter">
                    <SelectTrigger class="w-full md:w-[200px] h-10 border-slate-200">
                        <SelectValue placeholder="Filter by Role" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="ALL">All Roles</SelectItem>
                        <SelectItem v-for="role in availableRoles" :key="role.id" :value="role.name">
                            {{ formatRoleName(role.name) }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Button v-if="search || selectedRoleFilter !== 'ALL'" variant="ghost" @click="clearFilters" class="text-destructive h-10">
                    <X class="w-4 h-4 mr-2" /> Reset
                </Button>
            </div>

            <!-- Users Table -->
            <Card class="border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader class="bg-slate-50 dark:bg-slate-900/50">
                        <TableRow>
                            <TableHead class="w-[350px]">Identity</TableHead>
                            <TableHead>Assigned Roles</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-900/20">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                   <Avatar class="h-10 w-10 border-2 border-background shadow-sm">
                                        <AvatarFallback :class="[user.is_active ? 'bg-primary/10 text-primary' : 'bg-red-50 text-red-500', 'font-bold']">
                                            {{ user.name.charAt(0) }}
                                        </AvatarFallback>
                                   </Avatar>
                                   <div class="flex flex-col">
                                       <span :class="['font-bold text-foreground leading-none', !user.is_active && 'text-muted-foreground line-through']">{{ user.name }}</span>
                                       <span class="text-[11px] text-muted-foreground mt-1">{{ user.email }}</span>
                                   </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1.5">
                                    <Badge 
                                        v-for="role in user.roles" 
                                        :key="role.id" 
                                        variant="secondary" 
                                        class="bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/50 text-[10px] uppercase font-bold px-1.5 py-0"
                                    >
                                        {{ formatRoleName(role.name) }}
                                    </Badge>
                                    <span v-if="user.roles.length === 0" class="text-xs text-muted-foreground italic">No Roles</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div v-if="user.is_active" class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.4)]"></div>
                                    <span class="text-[10px] uppercase font-black text-green-700 dark:text-green-400">Active</span>
                                </div>
                                <div v-else class="flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-red-400 shadow-[0_0_8px_rgba(239,68,68,0.4)]"></div>
                                    <span class="text-[10px] uppercase font-black text-red-600 dark:text-red-400">Deactivated</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-muted-foreground">
                                            <MoreHorizontal class="w-4 h-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-56">
                                        <DropdownMenuLabel>User Management</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem @click="openRoleModal(user)">
                                            <ShieldAlert class="w-4 h-4 mr-2 text-indigo-600" /> Manage Roles
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="toggleStatus(user)" :class="user.is_active ? 'text-red-600' : 'text-green-600'">
                                            <component :is="user.is_active ? Ban : Unlock" class="w-4 h-4 mr-2" />
                                            {{ user.is_active ? 'Deactivate Account' : 'Reactivate Account' }}
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem class="text-muted-foreground opacity-50 cursor-not-allowed">
                                            <Trash2 class="w-4 h-4 mr-2" /> Delete Account
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="4" class="h-64 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="p-4 bg-slate-50 rounded-full">
                                        <Users class="w-8 h-8 text-muted-foreground" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-bold">No users found</p>
                                        <p class="text-sm text-muted-foreground">Try adjusting your filters or search keywords.</p>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <CardFooter class="flex items-center justify-between border-t p-4 px-6 bg-slate-50/30">
                    <div class="text-[11px] text-muted-foreground font-medium uppercase tracking-widest">
                        Data Chunk: {{ users.from }}-{{ users.to }} of {{ users.total }} Records
                    </div>
                    <div class="flex gap-1">
                         <Button 
                            v-for="(link, i) in users.links" 
                            :key="i"
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                            as-child
                            class="h-8 min-w-[32px] px-2 text-[11px] font-bold"
                         >
                            <Link v-if="link.url" :href="link.url" v-html="link.label" />
                            <span v-else v-html="link.label"></span>
                         </Button>
                    </div>
                </CardFooter>
            </Card>

            <!-- Role Assignment Modal -->
            <Dialog v-model:open="showRoleModal">
                <DialogContent class="sm:max-w-[450px] p-0 overflow-hidden border-0 shadow-2xl">
                    <div class="bg-indigo-600 p-6 text-white relative">
                        <Shield class="absolute -right-6 -top-6 w-32 h-32 text-white/10 rotate-12" />
                        <DialogTitle class="text-xl font-bold">Assign System Roles</DialogTitle>
                        <DialogDescription class="text-indigo-100/80 text-sm mt-1">
                            Grant administrative or academic authorities to <strong>{{ editingUser?.name }}</strong>.
                        </DialogDescription>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <div class="space-y-3">
                            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">Available System Roles</Label>
                            <div class="grid grid-cols-2 gap-3">
                                <div v-for="role in availableRoles" :key="role.id" 
                                    class="flex items-start gap-2 p-3 rounded-xl border border-slate-100 hover:border-indigo-200 hover:bg-indigo-50/30 transition-all cursor-pointer group"
                                    @click="toggleUserRole(role.name)"
                                >
                                    <Checkbox 
                                        :id="`role-${role.id}`" 
                                        :checked="roleForm.roles.includes(role.name)"
                                        class="mt-0.5"
                                    />
                                    <Label class="text-xs font-bold leading-none cursor-pointer group-hover:text-indigo-700">
                                        {{ formatRoleName(role.name) }}
                                    </Label>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-900/50 flex gap-3">
                            <ShieldAlert class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0" />
                            <p class="text-[11px] text-indigo-700 dark:text-indigo-300 leading-relaxed font-medium">
                                Granting roles will immediately provide the user with the permissions associated with those roles. Revoking the 'staff' or 'student' role may affect portal login access.
                            </p>
                        </div>
                    </div>

                    <DialogFooter class="p-6 pt-0">
                        <Button variant="ghost" @click="showRoleModal = false">Cancel</Button>
                        <Button @click="submitRoles" :disabled="roleForm.processing" class="gap-2 bg-indigo-600 hover:bg-indigo-700">
                            Confirm Access Update
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Create User Modal -->
            <Dialog v-model:open="showCreateModal">
                <DialogContent class="sm:max-w-[550px] p-0 overflow-hidden border-0 shadow-2xl bg-white dark:bg-slate-950">
                    <div class="bg-indigo-600 p-8 text-white relative">
                        <UserPlus class="absolute -right-4 -top-4 w-32 h-32 text-white/10 rotate-12" />
                        <DialogTitle class="text-2xl font-black uppercase tracking-tighter">System Registration</DialogTitle>
                        <DialogDescription class="text-indigo-100/80 text-sm mt-1 font-medium">
                            Create a new administrative or staff account with specific portal access.
                        </DialogDescription>
                    </div>

                    <div class="p-8 space-y-6 max-h-[60vh] overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground px-1">Full Name</Label>
                                <Input v-model="createForm.name" placeholder="John Doe" class="h-11 rounded-xl" />
                                <p v-if="createForm.errors.name" class="text-xs text-destructive">{{ createForm.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground px-1">Email Address</Label>
                                <Input v-model="createForm.email" type="email" placeholder="john@university.edu" class="h-11 rounded-xl" />
                                <p v-if="createForm.errors.email" class="text-xs text-destructive">{{ createForm.errors.email }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground px-1">Password</Label>
                                <Input v-model="createForm.password" type="password" placeholder="••••••••" class="h-11 rounded-xl" />
                                <p v-if="createForm.errors.password" class="text-xs text-destructive">{{ createForm.errors.password }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground px-1">Confirm Password</Label>
                                <Input v-model="createForm.password_confirmation" type="password" placeholder="••••••••" class="h-11 rounded-xl" />
                            </div>
                        </div>

                        <div class="space-y-3 pt-4 border-t">
                            <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground px-1">Assign Preliminary Roles</Label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div v-for="role in availableRoles" :key="role.id" 
                                    class="flex flex-col p-3 rounded-xl border border-slate-100 hover:border-indigo-200 hover:bg-slate-50 transition-all cursor-pointer group relative"
                                    @click="toggleUserRoleInCreate(role.name)"
                                >
                                    <div class="flex justify-between items-start mb-2">
                                        <Badge variant="outline" class="text-[9px] uppercase font-black px-1 py-0 border-indigo-200 text-indigo-700 bg-indigo-50/50">
                                            Role
                                        </Badge>
                                        <Checkbox 
                                            :checked="createForm.roles.includes(role.name)"
                                            class="rounded-full h-4 w-4 border-slate-200"
                                        />
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-700 truncate capitalize">{{ formatRoleName(role.name) }}</span>
                                </div>
                            </div>
                            <p v-if="createForm.errors.roles" class="text-xs text-destructive">{{ createForm.errors.roles }}</p>
                        </div>
                    </div>

                    <div class="p-6 bg-slate-50 dark:bg-slate-900/50 flex flex-col sm:flex-row gap-3 border-t">
                        <Button variant="ghost" @click="showCreateModal = false" class="sm:flex-1 h-12 rounded-xl font-bold">Cancel</Button>
                        <Button 
                            @click="submitCreate" 
                            :disabled="createForm.processing" 
                            class="sm:flex-[2] h-12 bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-100 dark:shadow-none rounded-xl font-black uppercase tracking-widest"
                        >
                            <span v-if="createForm.processing">Creating Account...</span>
                            <span v-else>Register System User</span>
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>

<script lang="ts">
// Helper for create modal checkbox
export default {
    methods: {
        toggleUserRoleInCreate(roleName: string) {
            const index = this.createForm.roles.indexOf(roleName);
            if (index > -1) {
                this.createForm.roles.splice(index, 1);
            } else {
                this.createForm.roles.push(roleName);
            }
        }
    }
}
</script>
