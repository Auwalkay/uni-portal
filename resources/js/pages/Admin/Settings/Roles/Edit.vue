<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    Shield, 
    ShieldCheck, 
    ArrowLeft,
    Save,
    CheckCircle2,
    XCircle,
    Info,
    Search,
    Lock,
    Unlock,
    Settings
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  CardFooter
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';

const props = defineProps<{
    role: {
        id: number;
        name: string;
        permissions: Array<{ id: number; name: string }>;
    };
    allPermissions: Record<string, Array<{ id: number; name: string }>>;
}>();

const breadcrumbs = [
    { title: 'System Settings', href: route('admin.settings.index') },
    { title: 'Roles', href: route('admin.settings.roles.index') },
    { title: `Manage ${props.role.name}`, href: '#' }
];

const form = useForm({
    permissions: props.role.permissions.map(p => p.name)
});

const togglePermission = (name: string) => {
    const index = form.permissions.indexOf(name);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(name);
    }
};

const submit = () => {
    form.put(route('admin.settings.roles.update', props.role.id));
};

const formatRoleName = (name: string) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
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
        // Remove all
        groupPerms.forEach(p => {
            const index = form.permissions.indexOf(p.name);
            if (index > -1) form.permissions.splice(index, 1);
        });
    } else {
        // Add all
        groupPerms.forEach(p => {
            if (!form.permissions.includes(p.name)) form.permissions.push(p.name);
        });
    }
};
</script>

<template>
    <Head :title="`Permissions for ${formatRoleName(role.name)}`" />

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
                        <h1 class="text-3xl font-bold tracking-tight text-foreground">{{ formatRoleName(role.name) }} Permissions</h1>
                        <p class="text-muted-foreground mt-1 text-sm">Fine-tune exactly what users with this role can see and do.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative w-64 mr-2">
                        <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input v-model="searchQuery" placeholder="Filter capabilities..." class="pl-8 h-9 text-sm" />
                    </div>
                    <Button @click="submit" :disabled="form.processing" class="gap-2 shadow-md">
                        <Save class="w-4 h-4" /> Save Changes
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <Card class="bg-indigo-600 text-white border-0 shadow-lg">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-lg flex items-center gap-2">
                                <ShieldCheck class="w-5 h-5 text-indigo-300" /> Security Note
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm text-indigo-100 leading-relaxed">
                            Permissions defined here grant systemic authority. Ensure you only enable capabilities necessary for the role's business function.
                        </CardContent>
                    </Card>

                    <Card class="border border-slate-200 dark:border-slate-800">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-bold uppercase tracking-wider text-muted-foreground">Selection Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-muted-foreground">Selected Capabilities</span>
                                <Badge variant="secondary" class="font-bold underline decoration-primary decoration-2">{{ form.permissions.length }}</Badge>
                            </div>
                            <Separator />
                            <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl text-[11px] leading-relaxed text-slate-500 italic">
                                Use the "Toggle Group" button on the right to quickly select or deselect all items in a category.
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Matrix -->
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
                                {{ isGroupAllSelected(String(group)) ? 'Deselect Group' : 'Select All in Group' }}
                            </Button>
                        </div>
                        <div class="p-6 grid sm:grid-cols-2 md:grid-cols-3 gap-6">
                             <div v-for="permission in perms" :key="permission.id" 
                                class="flex items-start gap-3 p-4 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-900/10 transition-all cursor-pointer group"
                                @click="togglePermission(permission.name)"
                             >
                                <Checkbox 
                                    :id="`perm-${permission.id}`" 
                                    :checked="form.permissions.includes(permission.name)"
                                    @update:checked="togglePermission(permission.name)"
                                    class="mt-0.5 data-[state=checked]:bg-primary"
                                />
                                <div class="space-y-0.5 pointer-events-none">
                                    <Label class="text-sm font-bold leading-none cursor-pointer group-hover:text-primary transition-colors">
                                        {{ formatPermissionName(permission.name) }}
                                    </Label>
                                    <p class="text-[10px] text-muted-foreground uppercase tracking-widest font-medium">Capability UID</p>
                                </div>
                             </div>
                        </div>
                    </div>

                    <div v-if="Object.keys(filteredPermissions).length === 0" class="h-64 flex flex-col items-center justify-center space-y-4 bg-slate-50 dark:bg-slate-900/20 rounded-2xl border border-dashed border-slate-300 dark:border-slate-800">
                        <Search class="w-12 h-12 text-slate-300" />
                        <div class="text-center">
                            <p class="font-bold">No matching permissions</p>
                            <p class="text-sm text-muted-foreground">Adjust your search query to find specific capabilities.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
             <div class="fixed bottom-8 right-8 z-50 md:hidden">
                <Button size="lg" @click="submit" :disabled="form.processing" class="shadow-2xl h-14 w-14 rounded-full p-0">
                    <Save class="w-6 h-6" />
                </Button>
            </div>
        </div>
    </AdminLayout>
</template>
