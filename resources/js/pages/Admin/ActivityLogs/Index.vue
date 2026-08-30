<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import { format, formatDistanceToNow } from 'date-fns';
import { 
    ShieldCheck, 
    Eye, 
    Clock, 
    User, 
    Activity as ActivityIcon, 
    Search, 
    RotateCcw, 
    FileText, 
    PlusCircle, 
    Edit3, 
    Trash2, 
    Layers, 
    Calendar,
    Code,
    Sparkles,
    Database,
    Hash,
    UserCheck,
    CheckCircle2
} from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import Pagination from '@/components/Pagination.vue';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';

const props = defineProps<{
    logs: any;
    stats: {
        total_events: number;
        today_events: number;
        creates_count: number;
        updates_count: number;
        deletes_count: number;
    };
    subjectTypes: Array<{
        full: string;
        short: string;
        label: string;
    }>;
    filters: {
        search: string;
        event: string;
        subject_type: string;
        start_date: string;
        end_date: string;
        per_page: number;
    };
}>();

const searchTerm = ref(props.filters.search || '');
const filterEvent = ref(props.filters.event || 'all');
const filterSubjectType = ref(props.filters.subject_type || 'all');
const filterStartDate = ref(props.filters.start_date || '');
const filterEndDate = ref(props.filters.end_date || '');
const perPage = ref(String(props.filters.per_page || 20));

const applyFilters = () => {
    router.get(route('admin.activity-logs.index'), {
        search: searchTerm.value,
        event: filterEvent.value,
        subject_type: filterSubjectType.value,
        start_date: filterStartDate.value,
        end_date: filterEndDate.value,
        per_page: perPage.value,
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    searchTerm.value = '';
    filterEvent.value = 'all';
    filterSubjectType.value = 'all';
    filterStartDate.value = '';
    filterEndDate.value = '';
    applyFilters();
};

const debouncedApply = debounce(applyFilters, 400);

watch(searchTerm, () => {
    debouncedApply();
});

const selectedLog = ref<any>(null);
const isDialogOpen = ref(false);
const activeTab = ref<'diff' | 'json'>('diff');

const viewLog = (log: any) => {
    selectedLog.value = log;
    activeTab.value = 'diff';
    isDialogOpen.value = true;
};

const getActionConfig = (action: string) => {
    switch (action?.toLowerCase()) {
        case 'created':
            return { label: 'Created', class: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800', icon: PlusCircle };
        case 'updated':
            return { label: 'Updated', class: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800', icon: Edit3 };
        case 'deleted':
        case 'cancelled':
            return { label: 'Deleted', class: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800', icon: Trash2 };
        default:
            return { label: action || 'Activity', class: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700', icon: ActivityIcon };
    }
};

const formatRelativeTime = (dateStr: string) => {
    try {
        return formatDistanceToNow(new Date(dateStr), { addSuffix: true });
    } catch {
        return '';
    }
};

const getSubjectName = (type: string) => {
    if (!type) return 'System Entity';
    const parts = type.split('\\');
    const name = parts[parts.length - 1];
    return name.replace(/([A-Z])/g, ' $1').trim();
};

const diffRows = computed(() => {
    if (!selectedLog.value || !selectedLog.value.properties) return [];
    
    const oldProps = selectedLog.value.properties.old || {};
    const newProps = selectedLog.value.properties.attributes || selectedLog.value.properties;
    
    const allKeys = Array.from(new Set([...Object.keys(oldProps), ...Object.keys(newProps)]));
    
    return allKeys.map((key) => {
        const oldValue = oldProps[key] !== undefined ? oldProps[key] : null;
        const newValue = newProps[key] !== undefined ? newProps[key] : null;
        const isChanged = oldValue !== null && newValue !== null && JSON.stringify(oldValue) !== JSON.stringify(newValue);
        
        return {
            field: key.replace(/_/g, ' '),
            rawField: key,
            oldValue: oldValue === null ? '—' : (typeof oldValue === 'object' ? JSON.stringify(oldValue) : String(oldValue)),
            newValue: newValue === null ? '—' : (typeof newValue === 'object' ? JSON.stringify(newValue) : String(newValue)),
            isChanged,
        };
    });
});
</script>

<template>
    <Head title="Audit Ledger & System Logs" />

    <AdminLayout>
        <div class="space-y-8 p-4 md:p-8 w-full max-w-[1600px] mx-auto animate-in fade-in duration-500">
            
            <!-- Executive Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 border-b pb-6 dark:border-slate-800">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-primary/10 rounded-xl text-primary">
                            <ShieldCheck class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-50">Audit Ledger</h1>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Real-time security auditing, data mutations, and administrative event tracking.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Badge variant="outline" class="px-3 py-1.5 bg-slate-50 dark:bg-slate-900 font-mono text-xs border-slate-200 dark:border-slate-800 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Audit Stream Active
                    </Badge>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <Card class="border shadow-xs bg-card">
                    <CardContent class="p-5 space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-wider">Total Scoped Events</span>
                            <Database class="w-4 h-4 text-primary" />
                        </div>
                        <h3 class="text-2xl font-black tracking-tight text-slate-900 dark:text-slate-50">{{ stats.total_events }}</h3>
                    </CardContent>
                </Card>

                <Card class="border shadow-xs bg-card">
                    <CardContent class="p-5 space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-wider">Logged Today</span>
                            <Clock class="w-4 h-4 text-blue-500" />
                        </div>
                        <h3 class="text-2xl font-black tracking-tight text-blue-600 dark:text-blue-400">{{ stats.today_events }}</h3>
                    </CardContent>
                </Card>

                <Card class="border shadow-xs bg-card">
                    <CardContent class="p-5 space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-wider">Created Records</span>
                            <PlusCircle class="w-4 h-4 text-emerald-500" />
                        </div>
                        <h3 class="text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">{{ stats.creates_count }}</h3>
                    </CardContent>
                </Card>

                <Card class="border shadow-xs bg-card">
                    <CardContent class="p-5 space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-wider">Updated Records</span>
                            <Edit3 class="w-4 h-4 text-amber-500" />
                        </div>
                        <h3 class="text-2xl font-black tracking-tight text-amber-600 dark:text-amber-400">{{ stats.updates_count }}</h3>
                    </CardContent>
                </Card>

                <Card class="border shadow-xs bg-card">
                    <CardContent class="p-5 space-y-2">
                        <div class="flex items-center justify-between text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-wider">Deleted / Cancelled</span>
                            <Trash2 class="w-4 h-4 text-rose-500" />
                        </div>
                        <h3 class="text-2xl font-black tracking-tight text-rose-600 dark:text-rose-400">{{ stats.deletes_count }}</h3>
                    </CardContent>
                </Card>
            </div>

            <!-- Smart Filter Bar -->
            <div class="bg-card border rounded-2xl p-5 shadow-xs space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Search Input -->
                    <div class="relative lg:col-span-2">
                        <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input 
                            v-model="searchTerm"
                            placeholder="Search description, causer name, email, or subject ID..." 
                            class="pl-10 h-10 bg-muted/30 focus-visible:ring-primary/30"
                        />
                    </div>

                    <!-- Action Event Selector -->
                    <div>
                        <Select v-model="filterEvent" @update:modelValue="applyFilters">
                            <SelectTrigger class="h-10 bg-muted/30 w-full text-left">
                                <SelectValue placeholder="All Actions" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">-- All Actions --</SelectItem>
                                <SelectItem value="created">Created</SelectItem>
                                <SelectItem value="updated">Updated</SelectItem>
                                <SelectItem value="deleted">Deleted</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Module Subject Selector -->
                    <div>
                        <Select v-model="filterSubjectType" @update:modelValue="applyFilters">
                            <SelectTrigger class="h-10 bg-muted/30 w-full text-left">
                                <SelectValue placeholder="All Modules" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">-- All Modules --</SelectItem>
                                <SelectItem v-for="type in subjectTypes" :key="type.short" :value="type.short">
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Date Range Range Selector -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <input 
                                type="date" 
                                v-model="filterStartDate" 
                                @change="applyFilters"
                                title="Date From"
                                class="flex h-10 w-full rounded-md border border-input bg-muted/30 px-2 py-1 text-xs text-muted-foreground focus-visible:outline-none"
                            />
                        </div>
                        <div>
                            <input 
                                type="date" 
                                v-model="filterEndDate" 
                                @change="applyFilters"
                                title="Date To"
                                class="flex h-10 w-full rounded-md border border-input bg-muted/30 px-2 py-1 text-xs text-muted-foreground focus-visible:outline-none"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t pt-3 mt-1">
                    <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                        <Sparkles class="w-4 h-4 text-primary" />
                        <span>Showing {{ logs.total }} Audit Records</span>
                    </div>
                    <Button variant="ghost" size="sm" class="text-xs text-muted-foreground hover:text-foreground" @click="resetFilters">
                        <RotateCcw class="w-3.5 h-3.5 mr-1.5" /> Reset Filters
                    </Button>
                </div>
            </div>

            <!-- Audit Logs Ledger Table -->
            <div class="border rounded-2xl shadow-xs bg-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 border-b text-muted-foreground font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-6 py-4">Timestamp</th>
                                <th class="px-6 py-4">Causer / Executed By</th>
                                <th class="px-6 py-4">Action</th>
                                <th class="px-6 py-4">Target Subject</th>
                                <th class="px-6 py-4 text-right">Audit Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-muted/40 transition-colors group">
                                <!-- Timestamp -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5 font-mono text-xs font-bold text-slate-800 dark:text-slate-200">
                                            <Clock class="w-3.5 h-3.5 text-slate-400" />
                                            {{ format(new Date(log.created_at), 'dd MMM yyyy, hh:mm:ss a') }}
                                        </div>
                                        <span class="text-[10px] text-muted-foreground pl-5 font-medium">
                                            {{ formatRelativeTime(log.created_at) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Causer -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3" v-if="log.causer">
                                        <div class="h-8 w-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-xs text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 uppercase">
                                            {{ log.causer.name ? log.causer.name.substring(0, 2) : 'US' }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">{{ log.causer.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ log.causer.email }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center gap-2 text-slate-400">
                                        <UserCheck class="w-4 h-4 text-slate-400" />
                                        <span class="italic text-xs font-semibold">Automated System Process</span>
                                    </div>
                                </td>

                                <!-- Action Badge -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Badge variant="outline" :class="['px-3 py-1 text-xs font-bold rounded-lg border flex items-center gap-1.5 w-fit capitalize', getActionConfig(log.description).class]">
                                        <component :is="getActionConfig(log.description).icon" class="w-3.5 h-3.5" />
                                        {{ getActionConfig(log.description).label }}
                                    </Badge>
                                </td>

                                <!-- Target Subject -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <Badge variant="secondary" class="font-mono text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ getSubjectName(log.subject_type) }}
                                        </Badge>
                                        <span v-if="log.subject_id" class="font-mono text-xs text-muted-foreground font-semibold">
                                            #{{ String(log.subject_id).split('-')[0] }}...
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <Button variant="outline" size="sm" class="rounded-lg h-8 text-xs font-bold border-slate-200 dark:border-slate-700 hover:bg-primary hover:text-white transition-all shadow-2xs" @click="viewLog(log)">
                                        <Eye class="w-3.5 h-3.5 mr-1.5" /> Inspect Changes
                                    </Button>
                                </td>
                            </tr>

                            <tr v-if="logs.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-muted-foreground">
                                    <ShieldCheck class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-700 mb-3" />
                                    <p class="font-bold text-base text-slate-700 dark:text-slate-300">No Audit Logs Found</p>
                                    <p class="text-xs text-muted-foreground mt-1">Try broadening your search term or date range filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t bg-muted/20 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-xs text-muted-foreground font-medium">
                        Page {{ logs.current_page }} of {{ logs.last_page }} &bull; Total {{ logs.total }} Audit Records
                    </div>
                    <Pagination :links="logs.links" />
                </div>
            </div>
        </div>

        <!-- Sleek Inspection Modal with Visual Diff -->
        <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
            <DialogContent class="max-w-4xl max-h-[85vh] overflow-y-auto rounded-2xl p-6">
                <DialogHeader class="border-b pb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-primary/10 text-primary">
                                <ActivityIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <DialogTitle class="text-lg font-bold">Audit Event Inspection</DialogTitle>
                                <DialogDescription class="text-xs">
                                    Detailed state modification analysis & audit record payload.
                                </DialogDescription>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button 
                                :variant="activeTab === 'diff' ? 'default' : 'outline'" 
                                size="sm" 
                                class="h-8 text-xs font-bold rounded-lg"
                                @click="activeTab = 'diff'"
                            >
                                Visual Diff
                            </Button>
                            <Button 
                                :variant="activeTab === 'json' ? 'default' : 'outline'" 
                                size="sm" 
                                class="h-8 text-xs font-bold rounded-lg"
                                @click="activeTab = 'json'"
                            >
                                <Code class="w-3.5 h-3.5 mr-1" /> Raw JSON
                            </Button>
                        </div>
                    </div>
                </DialogHeader>

                <div v-if="selectedLog" class="space-y-6 pt-2">
                    <!-- Metadata Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border text-xs">
                        <div>
                            <span class="text-muted-foreground uppercase text-[10px] font-bold tracking-wider block mb-1">Executed By</span>
                            <span class="font-bold text-slate-900 dark:text-slate-100 block">{{ selectedLog.causer?.name || 'Automated System' }}</span>
                            <span v-if="selectedLog.causer?.email" class="text-[10px] text-muted-foreground font-mono">{{ selectedLog.causer.email }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground uppercase text-[10px] font-bold tracking-wider block mb-1">Action Type</span>
                            <Badge variant="outline" :class="['px-2 py-0.5 text-[10px] font-bold rounded capitalize', getActionConfig(selectedLog.description).class]">
                                {{ selectedLog.description }}
                            </Badge>
                        </div>
                        <div>
                            <span class="text-muted-foreground uppercase text-[10px] font-bold tracking-wider block mb-1">Target Entity</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-100 block">{{ getSubjectName(selectedLog.subject_type) }}</span>
                            <span v-if="selectedLog.subject_id" class="text-[10px] text-muted-foreground font-mono">ID: {{ selectedLog.subject_id }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground uppercase text-[10px] font-bold tracking-wider block mb-1">Timestamp</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-slate-100 block">{{ format(new Date(selectedLog.created_at), 'dd MMM yyyy') }}</span>
                            <span class="text-[10px] text-muted-foreground font-mono">{{ format(new Date(selectedLog.created_at), 'hh:mm:ss a') }}</span>
                        </div>
                    </div>

                    <!-- Visual Diff View -->
                    <div v-if="activeTab === 'diff'">
                        <div v-if="diffRows.length > 0" class="border rounded-xl overflow-hidden shadow-2xs">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-muted/60 text-muted-foreground font-bold uppercase tracking-wider text-[10px] border-b">
                                    <tr>
                                        <th class="px-4 py-3">Attribute / Field</th>
                                        <th class="px-4 py-3">Previous State (Before)</th>
                                        <th class="px-4 py-3">New State (After)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="row in diffRows" :key="row.rawField" :class="row.isChanged ? 'bg-amber-50/40 dark:bg-amber-950/20' : ''">
                                        <td class="px-4 py-3 font-mono font-bold text-slate-700 dark:text-slate-300">
                                            {{ row.field }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-400">
                                            <span v-if="row.isChanged" class="line-through text-rose-600 dark:text-rose-400 font-medium">
                                                {{ row.oldValue }}
                                            </span>
                                            <span v-else>{{ row.oldValue }}</span>
                                        </td>
                                        <td class="px-4 py-3 font-mono">
                                            <span v-if="row.isChanged" class="font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 rounded">
                                                {{ row.newValue }}
                                            </span>
                                            <span v-else class="text-slate-600 dark:text-slate-400">{{ row.newValue }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="p-8 text-center text-muted-foreground border rounded-xl bg-slate-50/50">
                            <FileText class="w-8 h-8 mx-auto text-slate-300 mb-2" />
                            <p class="font-bold text-sm">No Specific Property Diffs Recorded</p>
                        </div>
                    </div>

                    <!-- Raw JSON View -->
                    <div v-if="activeTab === 'json'" class="space-y-4">
                        <pre class="bg-slate-950 text-slate-50 p-5 rounded-xl text-xs font-mono overflow-x-auto leading-relaxed border shadow-inner">{{ JSON.stringify(selectedLog.properties, null, 2) }}</pre>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
