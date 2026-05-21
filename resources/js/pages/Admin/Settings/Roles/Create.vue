<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    Shield, 
    ShieldCheck, 
    ArrowLeft,
    Save,
    CheckCircle2,
    Search,
    Lock,
    Unlock,
    Plus
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';

const props = defineProps<{
    allPermissions: Record<string, Array<{ id: number; name: string }>>;
}>();

const breadcrumbs = [
    { title: 'System Settings', href: route('admin.settings.index') },
    { title: 'Roles', href: route('admin.settings.roles.index') },
    { title: 'Create Custom Role', href: '#' }
];

const form = useForm({
    name: '',
    permissions: [] as string[]
});

const togglePermission = (name: string) => {
    const index = form.permissions.indexOf(name);
    if (index > -1) {
        form.permissions = form.permissions.filter(p => p !== name);
    } else {
        form.permissions = [...form.permissions, name];
    }
};

const submit = () => {
    form.post(route('admin.settings.roles.store'));
};

const formatPermissionName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const searchQuery = ref('');

const filteredPermissions = computed(() => {
    if (!searchQuery.value) return props.allPermissions;
    
    const filtered: Record<string, Array<{ id: number; name: string }>> = {};
    const query = searchQuery.value.toLowerCase();

    Object.entries(props.allPermissions).forEach(([group, perms]) => {
        const matching = perms.filter(p => p.name.toLowerCase().includes(query));
        if (matching.length > 0) {
            filtered[group] = matching;
        }
    });

    return filtered;
});

const isGroupAllSelected = (groupName: string) => {
    const groupPerms = props.allPermissions[groupName];
    return groupPerms.every(p => form.permissions.includes(p.name));
};

const toggleGroup = (groupName: string) => {
    const groupPerms = props.allPermissions[groupName];
    const allSelected = isGroupAllSelected(groupName);

    if (allSelected) {
        groupPerms.forEach(p => {
            const index = form.permissions.indexOf(p.name);
            if (index > -1) form.permissions.splice(index, 1);
        });
    } else {
        groupPerms.forEach(p => {
            if (!form.permissions.includes(p.name)) form.permissions.push(p.name);
        });
    }
};
</script>

<template>
    <Head title="Create Custom Role" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-6xl mx-auto">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                   <Button variant="ghost" size="icon" as-child>
                       <Link :href="route('admin.settings.roles.index')">
                            <ArrowLeft class="w-5 h-5" />
                       </Link>
                   </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-foreground">Create Custom Role</h1>
                        <p class="text-muted-foreground mt-1 text-sm">Define a new authority level and assign its initial capabilities.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button @click="submit" :disabled="form.processing" class="gap-2 shadow-md">
                        <Plus class="w-4 h-4" /> Create & Finalize
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Sidebar Config -->
                <div class="space-y-6">
                    <Card class="border-primary/20 bg-primary/5">
                        <CardHeader>
                            <CardTitle class="text-sm font-bold uppercase tracking-wider text-primary">Role Identity</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="role_name">Role Display Name</Label>
                                <Input 
                                    id="role_name" 
                                    v-model="form.name" 
                                    placeholder="e.g. Laboratory Supervisor" 
                                    required
                                />
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                                <p class="text-[10px] text-muted-foreground italic">System will auto-format this for DB compatibility.</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border border-slate-200 dark:border-slate-800">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-bold uppercase tracking-wider text-muted-foreground">Permission Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-muted-foreground">Initial Capabilities</span>
                                <Badge variant="secondary" class="font-bold">{{ form.permissions.length }}</Badge>
                            </div>
                            <Separator />
                            <div class="relative">
                                <Search class="absolute left-2 top-2.5 h-3.5 w-3.5 text-muted-foreground" />
                                <Input v-model="searchQuery" placeholder="Filter capabilities..." class="pl-7 h-8 text-[11px]" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Permission Matrix -->
                <div class="lg:col-span-3 space-y-6">
                    <div v-for="(perms, group) in filteredPermissions" :key="group" class="bg-white dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/40 border-b flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-white dark:bg-slate-900 border flex items-center justify-center text-primary shadow-sm">
                                    <Lock class="w-4 h-4" v-if="!isGroupAllSelected(group)" />
                                    <Unlock class="w-4 h-4" v-else />
                                </div>
                                <h3 class="font-bold text-lg capitalize">{{ group }} Management</h3>
                            </div>
                            <Button variant="ghost" size="sm" class="text-xs h-8 text-primary hover:bg-primary/5" @click="toggleGroup(String(group))">
                                {{ isGroupAllSelected(String(group)) ? 'Toggle Group' : 'Select Group' }}
                            </Button>
                        </div>
                        <div class="p-6 grid sm:grid-cols-2 md:grid-cols-3 gap-6">
                             <div v-for="permission in perms" :key="permission.id" 
                                class="flex items-start gap-3 p-4 rounded-xl border transition-all cursor-pointer group"
                                :class="[
                                    form.permissions.includes(permission.name) 
                                        ? 'border-primary/30 bg-primary/5 shadow-sm' 
                                        : 'border-transparent hover:border-slate-200 dark:hover:border-slate-800 hover:bg-slate-50/50'
                                ]"
                                @click="togglePermission(permission.name)"
                             >
                                <Checkbox 
                                    :id="`perm-${permission.id}`" 
                                    :checked="form.permissions.includes(permission.name)"
                                    @click.stop="togglePermission(permission.name)"
                                    class="mt-0.5"
                                />
                                <div class="space-y-0.5 pointer-events-none">
                                    <Label 
                                        class="text-sm font-bold leading-none cursor-pointer group-hover:text-primary transition-colors"
                                        :class="{ 'text-primary': form.permissions.includes(permission.name) }"
                                    >
                                        {{ formatPermissionName(permission.name) }}
                                    </Label>
                                    <p class="text-[10px] text-muted-foreground uppercase tracking-widest font-medium">Capability</p>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
