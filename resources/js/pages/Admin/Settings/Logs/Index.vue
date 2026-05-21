<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Database, 
    Search, 
    Filter, 
    X,
    User,
    Calendar,
    Clock,
    Shield,
    Info,
    ArrowLeft,
    Eye,
    ChevronDown,
    Activity,
    Layers,
    Monitor,
    Users,
    GraduationCap,
    Banknote,
    Settings
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';

// Shadcn UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import {
  format
} from "date-fns"

const props = defineProps<{
    logs: {
        data: Array<any>;
        links: Array<any>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        user_id?: string;
        module?: string;
        action?: string;
        search?: string;
    };
    users: Array<{ id: string, name: string }>;
    modules: Array<string>;
    actions: Array<string>;
}>();

const search = ref(props.filters.search || '');
const selectedUser = ref(props.filters.user_id || 'ALL');
const selectedModule = ref(props.filters.module || 'ALL');
const selectedAction = ref(props.filters.action || 'ALL');

const updateFilters = debounce(() => {
    router.get(route('admin.settings.logs.index'), {
        search: search.value,
        user_id: selectedUser.value === 'ALL' ? '' : selectedUser.value,
        module: selectedModule.value === 'ALL' ? '' : selectedModule.value,
        action: selectedAction.value === 'ALL' ? '' : selectedAction.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}, 300);

watch([search, selectedUser, selectedModule, selectedAction], () => {
    updateFilters();
});

const clearFilters = () => {
    search.value = '';
    selectedUser.value = 'ALL';
    selectedModule.value = 'ALL';
    selectedAction.value = 'ALL';
};

const getMethodColor = (action: string) => {
    const colors: Record<string, string> = {
        'post': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-green-200',
        'put': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200',
        'patch': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200',
        'delete': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border-red-200',
    };
    return colors[action.toLowerCase()] || 'bg-slate-100 text-slate-700 border-slate-200';
};

const getModuleIcon = (module: string) => {
    const icons: Record<string, any> = {
        'staff': Users,
        'students': GraduationCap,
        'finance': Banknote,
        'settings': Settings,
        'rbac': Shield,
    };
    return icons[module.toLowerCase()] || Layers;
};

const breadcrumbs = [
    { title: 'System Settings', href: route('admin.settings.index') },
    { title: 'Audit Logs', href: '#' }
];

const selectedLogDetails = ref<any>(null);
const showDetailsModal = ref(false);

const viewDetails = (log: any) => {
    selectedLogDetails.value = log;
    showDetailsModal.value = true;
};
</script>

<template>
    <Head title="System Audit Logs" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="py-10 px-6 space-y-8 w-full max-w-6xl mx-auto">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                   <Button variant="ghost" size="icon" as-child>
                       <Link :href="route('admin.settings.index')">
                            <ArrowLeft class="w-5 h-5" />
                       </Link>
                   </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-foreground">System Audit Logs</h1>
                        <p class="text-muted-foreground mt-1 text-sm">Review administrative actions and portal state changes.</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
             <div class="bg-white dark:bg-slate-950 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            type="search"
                            placeholder="Search logs..."
                            class="pl-10 h-10 border-slate-200"
                            v-model="search"
                        />
                    </div>
                    
                    <Select v-model="selectedUser">
                        <SelectTrigger class="w-full md:w-[200px] h-10 border-slate-200">
                            <SelectValue placeholder="Performed By" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ALL">All Users</SelectItem>
                            <SelectItem v-for="user in users" :key="user.id" :value="String(user.id)">
                                {{ user.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex flex-col md:flex-row gap-4">
                    <Select v-model="selectedModule">
                        <SelectTrigger class="w-full md:w-[200px] h-10 border-slate-200">
                            <SelectValue placeholder="Filter Module" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ALL">All Modules</SelectItem>
                            <SelectItem v-for="mod in modules" :key="mod" :value="mod" class="capitalize">
                                {{ mod }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="selectedAction">
                        <SelectTrigger class="w-full md:w-[200px] h-10 border-slate-200">
                            <SelectValue placeholder="Action Type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ALL">All Actions</SelectItem>
                            <SelectItem v-for="act in actions" :key="act" :value="act" class="uppercase">
                                {{ act }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Button v-if="search || selectedUser !== 'ALL' || selectedModule !== 'ALL' || selectedAction !== 'ALL'" variant="ghost" @click="clearFilters" class="text-destructive h-10">
                        <X class="w-4 h-4 mr-2" /> Reset
                    </Button>
                </div>
            </div>

            <!-- Logs Table -->
            <Card class="border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <Table>
                    <TableHeader class="bg-slate-50 dark:bg-slate-900/50">
                        <TableRow>
                            <TableHead class="w-[200px]">Timestamp</TableHead>
                            <TableHead>User</TableHead>
                            <TableHead>Activity</TableHead>
                            <TableHead>Module</TableHead>
                            <TableHead>IP Address</TableHead>
                            <TableHead class="text-right">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="log in logs.data" :key="log.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-900/20">
                            <TableCell class="text-xs font-medium text-muted-foreground whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="flex items-center gap-1.5"><Calendar class="w-3 h-3" /> {{ format(new Date(log.created_at), 'MMM dd, yyyy') }}</span>
                                    <span class="flex items-center gap-1.5 mt-0.5"><Clock class="w-3 h-3" /> {{ format(new Date(log.created_at), 'HH:mm:ss') }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <div class="h-7 w-7 rounded-full bg-slate-100 flex items-center justify-center border text-[10px] font-bold text-slate-500">
                                        {{ log.user?.name.charAt(0) || 'S' }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold leading-none">{{ log.user?.name || 'System' }}</span>
                                        <span class="text-[10px] text-muted-foreground mt-1">{{ log.user?.email || 'automated-task' }}</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline" :class="[getMethodColor(log.action), 'text-[10px] uppercase font-black px-1.5 py-0']">
                                    {{ log.action }}
                                </Badge>
                                <span class="ml-2 text-xs font-medium text-slate-600 dark:text-slate-400 capitalize">
                                    Performed on {{ log.details?.route?.split('.').pop() || 'resource' }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-1.5 px-2 py-1 rounded bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 w-fit">
                                    <component :is="getModuleIcon(log.module)" class="w-3 h-3 text-primary" />
                                    <span class="text-[10px] uppercase font-bold tracking-tight">{{ log.module }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-1.5 text-[10px] font-mono text-muted-foreground">
                                    <Monitor class="w-3 h-3" /> {{ log.ip_address }}
                                </div>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="sm" @click="viewDetails(log)" class="h-8 text-primary hover:bg-primary/5">
                                    <Eye class="w-4 h-4 mr-2" /> Details
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="logs.data.length === 0">
                            <TableCell colspan="6" class="h-64 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="p-4 bg-slate-50 rounded-full">
                                        <Activity class="w-8 h-8 text-muted-foreground" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-bold">No logs found</p>
                                        <p class="text-sm text-muted-foreground">Try adjusting your filters or search keywords.</p>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div class="flex items-center justify-between border-t p-4 px-6 bg-slate-50/30">
                    <div class="text-[11px] text-muted-foreground font-medium uppercase tracking-widest">
                        Logs: {{ logs.from }}-{{ logs.to }} of {{ logs.total }} Total Entries
                    </div>
                    <div class="flex gap-1">
                         <Button 
                            v-for="(link, i) in logs.links" 
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
                </div>
            </Card>
        </div>

        <!-- Detail Modal -->
        <Dialog :open="showDetailsModal" @update:open="showDetailsModal = $event">
            <DialogContent class="sm:max-w-xl p-0 overflow-hidden border-0 shadow-2xl bg-white dark:bg-slate-950">
                <div :class="[getMethodColor(selectedLogDetails?.action || ''), 'p-6 b-0 border-b relative']">
                    <Activity class="absolute -right-6 -top-6 w-32 h-32 opacity-10 rotate-12" />
                    <DialogTitle class="text-xl font-black uppercase tracking-wider flex items-center gap-2">
                        Audit Entry #{{ selectedLogDetails?.id.split('-')[0] }}
                    </DialogTitle>
                    <DialogDescription class="text-inherit opacity-80 font-medium mt-1">
                        Detailed breakdown of the action captured on {{ selectedLogDetails ? format(new Date(selectedLogDetails.created_at), 'PPPp') : '' }}.
                    </DialogDescription>
                </div>

                <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border">
                            <p class="text-[10px] uppercase font-bold text-muted-foreground mb-1">Method / Action</p>
                            <p class="font-mono text-sm font-bold uppercase">{{ selectedLogDetails?.action }}</p>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border">
                            <p class="text-[10px] uppercase font-bold text-muted-foreground mb-1">Target Module</p>
                            <p class="font-semibold text-sm capitalize">{{ selectedLogDetails?.module }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">URL & Routing</p>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 font-mono text-xs break-all border">
                            <p class="text-primary font-bold mb-1">[{{ selectedLogDetails?.details?.status_code }}] {{ selectedLogDetails?.details?.url }}</p>
                            <p class="text-muted-foreground">Route: {{ selectedLogDetails?.details?.route }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Payload (Scrubbed)</p>
                        <div class="p-4 rounded-xl bg-slate-950 text-indigo-300 font-mono text-[11px] border border-slate-800 overflow-x-auto shadow-inner">
                            <pre>{{ JSON.stringify(selectedLogDetails?.details?.payload || {}, null, 2) }}</pre>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t bg-slate-50 dark:bg-slate-900/50 flex justify-end">
                    <Button @click="showDetailsModal = false">Close Detail Viewer</Button>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #1e293b;
}
</style>
