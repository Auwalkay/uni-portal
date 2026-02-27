<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import CentralLayout from '@/layouts/CentralLayout.vue';
import { route } from 'ziggy-js';
import { 
    Users, 
    Shield, 
    UserPlus, 
    MoreHorizontal, 
    Trash2, 
    ShieldCheck,
    Mail,
    Lock,
    Search,
    Edit2
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { ref } from 'vue';
import { Activity, Calendar } from 'lucide-vue-next';
const props = defineProps<{
    users: {
        data: any[];
        links: any[];
        meta: any;
    };
    roles: any[];
}>();

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
});

const editForm = useForm({
    id: null as number | null,
    role: '',
});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedUser = ref<any>(null);

const submitCreate = () => {
    createForm.post(route('central.central-users.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            createForm.reset();
        },
    });
};

const openEditModal = (user: any) => {
    selectedUser.value = user;
    editForm.id = user.id;
    editForm.role = user.roles[0]?.name || '';
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    if (!editForm.id) return;
    editForm.patch(route('central.central-users.update', editForm.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
        },
    });
};

const deleteUser = (id: number) => {
    if (confirm('Are you sure you want to remove this administrator?')) {
        createForm.delete(route('central.central-users.destroy', id));
    }
};

const getRoleBadgeClass = (roleName: string) => {
    switch (roleName.toLowerCase()) {
        case 'super_admin': return 'bg-rose-50 text-rose-700 border-rose-100';
        case 'nbte_user': return 'bg-indigo-50 text-indigo-700 border-indigo-100';
        case 'auditor': return 'bg-amber-50 text-amber-700 border-amber-100';
        case 'support': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        default: return 'bg-slate-50 text-slate-700 border-slate-100';
    }
};
</script>

<template>
    <Head title="Platform Administrators" />

    <CentralLayout>
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Header & Actions -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/40 backdrop-blur-sm p-6 rounded-3xl border border-white/20 shadow-sm transition-all hover:shadow-md">
                    <div>
                        <h2 class="font-bold text-3xl text-slate-900 tracking-tight flex items-center gap-3">
                            <Users class="h-8 w-8 text-primary" /> Super Admins
                        </h2>
                        <p class="text-slate-500 mt-1 max-w-lg">Manage platform-wide administrative access and oversight roles for the NBTE portal.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Dialog v-model:open="isCreateModalOpen">
                            <DialogTrigger as-child>
                                <Button class="bg-primary hover:bg-primary/90 text-white gap-2 h-12 px-8 rounded-2xl shadow-xl shadow-primary/20 transition-all hover:-translate-y-0.5 active:scale-95 font-bold">
                                    <UserPlus class="h-5 w-5" /> Add Administrator
                                </Button>
                            </DialogTrigger>
                            <DialogContent class="sm:max-w-[425px] rounded-3xl border-none shadow-2xl p-0 overflow-hidden">
                                <div class="bg-primary p-6 text-white text-center sm:text-left">
                                    <DialogHeader>
                                        <DialogTitle class="text-2xl font-black tracking-tight text-white">New Platform Admin</DialogTitle>
                                        <DialogDescription class="text-primary-foreground/80 font-medium">
                                            Assign a dedicated seat for platform-wide management.
                                        </DialogDescription>
                                    </DialogHeader>
                                </div>
                                <form @submit.prevent="submitCreate" class="space-y-5 p-8 bg-white">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold uppercase tracking-widest text-slate-400 pl-1">Full Name</label>
                                        <div class="relative group">
                                            <Users class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300 group-focus-within:text-primary transition-colors" />
                                            <Input v-model="createForm.name" placeholder="John Doe" required class="h-12 pl-11 rounded-xl bg-slate-50 border-slate-200 focus:bg-white transition-all shadow-sm" />
                                        </div>
                                        <span v-if="createForm.errors.name" class="text-xs text-rose-500 font-bold italic">{{ createForm.errors.name }}</span>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold uppercase tracking-widest text-slate-400 pl-1">Email Address</label>
                                        <div class="relative group">
                                            <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300 group-focus-within:text-primary transition-colors" />
                                            <Input type="email" v-model="createForm.email" placeholder="john@nbte.gov.ng" required class="h-12 pl-11 rounded-xl bg-slate-50 border-slate-200 focus:bg-white transition-all shadow-sm" />
                                        </div>
                                        <span v-if="createForm.errors.email" class="text-xs text-rose-500 font-bold italic">{{ createForm.errors.email }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold uppercase tracking-widest text-slate-400 pl-1">Auth Password</label>
                                            <div class="relative group">
                                                <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300 group-focus-within:text-primary transition-colors" />
                                                <Input type="password" v-model="createForm.password" required class="h-12 pl-11 rounded-xl bg-slate-50 border-slate-200 shadow-sm" />
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold uppercase tracking-widest text-slate-400 pl-1">Confirm</label>
                                            <div class="relative group">
                                                <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300 group-focus-within:text-primary transition-colors" />
                                                <Input type="password" v-model="createForm.password_confirmation" required class="h-12 pl-11 rounded-xl bg-slate-50 border-slate-200 shadow-sm" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold uppercase tracking-widest text-slate-400 pl-1">Permission Role</label>
                                        <Select v-model="createForm.role">
                                            <SelectTrigger class="h-12 rounded-xl bg-slate-50 border-slate-200 shadow-sm">
                                                <div class="flex items-center gap-2">
                                                    <ShieldCheck class="h-4 w-4 text-slate-400" />
                                                    <SelectValue placeholder="Select a seat" />
                                                </div>
                                            </SelectTrigger>
                                            <SelectContent class="rounded-2xl border-none shadow-2xl">
                                                <SelectItem v-for="role in roles" :key="role.id" :value="role.name" class="rounded-xl my-1 focus:bg-primary/10 focus:text-primary font-bold text-slate-600 cursor-pointer">
                                                    {{ role.name.replace('_', ' ').toUpperCase() }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <span v-if="createForm.errors.role" class="text-xs text-rose-500 font-bold italic">{{ createForm.errors.role }}</span>
                                    </div>
                                    <DialogFooter class="pt-2">
                                        <Button type="submit" :disabled="createForm.processing" class="w-full bg-primary h-14 rounded-2xl font-black text-lg tracking-tight shadow-xl shadow-primary/10 transition-all hover:-translate-y-0.5 active:scale-95">
                                            {{ createForm.processing ? 'Provisioning...' : 'Provision Admin Account' }}
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <!-- Search & Filters -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b bg-slate-50/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="relative w-full md:w-96 group">
                            <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 group-focus-within:text-primary transition-colors" />
                            <Input placeholder="Search administrators by name or email..." class="pl-11 h-11 bg-white border-slate-200 rounded-xl focus:ring-primary/10 focus:border-primary transition-all shadow-sm" />
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge variant="secondary" class="bg-primary/10 text-primary border-primary/10 flex items-center gap-1.5 px-3 py-1 rounded-full font-bold text-[10px] uppercase tracking-wider">
                                <Activity class="h-3 w-3" /> {{ users.meta?.total || users.data.length }} Active Admins
                            </Badge>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Administrator</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Role</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Added On</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/80 transition-all duration-200">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <div class="h-10 w-10 bg-gradient-to-br from-primary/10 to-slate-100 rounded-2xl flex items-center justify-center text-primary font-bold border border-primary/20 shadow-sm">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900 leading-tight">{{ user.name }}</p>
                                                <div v-if="user.id === $page.props.auth.user.id" class="text-[10px] text-primary font-bold uppercase tracking-tight mt-0.5">Active Session (You)</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            <Badge v-for="role in user.roles" :key="role.id" 
                                                   variant="outline"
                                                   :class="['px-2.5 py-0.5 h-6 text-[10px] font-bold uppercase tracking-wider rounded-lg border-2', getRoleBadgeClass(role.name)]">
                                                {{ role.name.replace('_', ' ') }}
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-sm text-slate-600 font-medium">
                                            <Mail class="h-3.5 w-3.5 text-slate-400" />
                                            {{ user.email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                                            <Calendar class="h-3.5 w-3.5 text-slate-300" />
                                            {{ new Date(user.created_at).toLocaleDateString('en-GB') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <Button variant="ghost" size="icon" @click="openEditModal(user)"
                                                    class="h-9 w-9 text-slate-400 hover:text-primary hover:bg-primary/10 rounded-xl transition-colors">
                                                <Edit2 class="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" @click="deleteUser(user.id)" 
                                                    :disabled="user.id === $page.props.auth.user.id"
                                                    class="h-9 w-9 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors disabled:opacity-30">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Update Role Dialog -->
                <Dialog v-model:open="isEditModalOpen">
                    <DialogContent class="sm:max-w-[425px] rounded-3xl border-none shadow-2xl p-0 overflow-hidden">
                        <div class="bg-primary p-6 text-white text-center sm:text-left">
                            <DialogHeader>
                                <DialogTitle class="text-2xl font-black tracking-tight text-white">Manage Admin</DialogTitle>
                                <DialogDescription class="text-primary-foreground/80 font-medium">
                                    Update roles for {{ selectedUser?.name }}.
                                </DialogDescription>
                            </DialogHeader>
                        </div>
                        <form @submit.prevent="submitEdit" class="space-y-5 p-8 bg-white">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-slate-400 pl-1">Security Role</label>
                                <Select v-model="editForm.role">
                                    <SelectTrigger class="h-12 rounded-xl bg-slate-50 border-slate-200 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <Shield class="h-4 w-4 text-slate-400" />
                                            <SelectValue placeholder="Select a role" />
                                        </div>
                                    </SelectTrigger>
                                    <SelectContent class="rounded-2xl border-none shadow-2xl">
                                        <SelectItem v-for="role in roles" :key="role.id" :value="role.name" class="rounded-xl my-1 focus:bg-primary/10 focus:text-primary font-bold text-slate-600 cursor-pointer">
                                            {{ role.name.replace('_', ' ').toUpperCase() }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p class="text-[10px] text-slate-400 leading-tight pt-1 italic font-medium">Changes take effect on the next session refresh.</p>
                                <span v-if="editForm.errors.role" class="text-xs text-rose-500 font-bold italic">{{ editForm.errors.role }}</span>
                            </div>
                            <DialogFooter class="pt-2">
                                <Button type="submit" :disabled="editForm.processing" class="w-full bg-primary h-14 rounded-2xl font-black text-lg tracking-tight shadow-xl shadow-primary/10 transition-all hover:-translate-y-0.5 active:scale-95">
                                    {{ editForm.processing ? 'Updating...' : 'Save Permissions' }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

            </div>
        </div>
    </CentralLayout>
</template>
