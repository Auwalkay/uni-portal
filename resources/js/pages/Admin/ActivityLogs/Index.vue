<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import { format } from 'date-fns';
import { Shield, Eye, Clock, User, Activity as ActivityIcon } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import Pagination from '@/components/Pagination.vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';

const props = defineProps<{
    logs: any;
    filters: any;
}>();

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(route('admin.activity-logs.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const selectedLog = ref<any>(null);
const isDialogOpen = ref(false);

const viewLog = (log: any) => {
    selectedLog.value = log;
    isDialogOpen.value = true;
};

const getActionColor = (action: string) => {
    if (action === 'created') return 'bg-green-100 text-green-800';
    if (action === 'updated') return 'bg-blue-100 text-blue-800';
    if (action === 'deleted') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Audit Logs" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Audit Logs</h2>
                    <p class="text-muted-foreground">View all system activity and data modifications.</p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
                        <div>
                            <CardTitle>Activity Ledger</CardTitle>
                            <CardDescription>A chronological record of system changes.</CardDescription>
                        </div>
                        <div class="flex w-full md:max-w-sm items-center space-x-2">
                            <Input v-model="search" placeholder="Search logs..." class="w-full" />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-muted/50 text-muted-foreground uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Date & Time</th>
                                    <th class="px-4 py-3 font-medium">User</th>
                                    <th class="px-4 py-3 font-medium">Action</th>
                                    <th class="px-4 py-3 font-medium">Subject Type</th>
                                    <th class="px-4 py-3 font-medium text-right">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-muted/50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center text-muted-foreground">
                                            <Clock class="w-4 h-4 mr-2" />
                                            {{ format(new Date(log.created_at), 'MMM dd, yyyy HH:mm') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center" v-if="log.causer">
                                            <User class="w-4 h-4 mr-2 text-muted-foreground" />
                                            {{ log.causer.name }}
                                        </div>
                                        <span v-else class="text-muted-foreground italic">System</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge variant="outline" :class="getActionColor(log.description)">
                                            {{ log.description }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ log.subject_type ? log.subject_type.split('\\').pop() : 'N/A' }} #{{ log.subject_id }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Button variant="ghost" size="sm" @click="viewLog(log)">
                                            <Eye class="w-4 h-4 mr-2" /> View
                                        </Button>
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                        <Shield class="w-12 h-12 mx-auto text-muted-foreground/50 mb-3" />
                                        No activity logs found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <Pagination :links="logs.links" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
            <DialogContent class="max-w-3xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center text-xl">
                        <ActivityIcon class="w-5 h-5 mr-2" /> Log Details
                    </DialogTitle>
                    <DialogDescription>
                        Detailed view of the activity log entry.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedLog" class="space-y-6 mt-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-muted-foreground block mb-1">Causer</span>
                            <div class="font-medium">
                                {{ selectedLog.causer?.name || 'System' }} 
                                <span v-if="selectedLog.causer?.email" class="text-muted-foreground">({{ selectedLog.causer.email }})</span>
                            </div>
                        </div>
                        <div>
                            <span class="text-muted-foreground block mb-1">Time</span>
                            <div class="font-medium">{{ format(new Date(selectedLog.created_at), 'PPP p') }}</div>
                        </div>
                        <div>
                            <span class="text-muted-foreground block mb-1">Action</span>
                            <Badge variant="outline" :class="getActionColor(selectedLog.description)">
                                {{ selectedLog.description }}
                            </Badge>
                        </div>
                        <div>
                            <span class="text-muted-foreground block mb-1">Subject</span>
                            <div class="font-medium text-muted-foreground">
                                {{ selectedLog.subject_type || 'N/A' }} 
                                <span v-if="selectedLog.subject_id">(ID: {{ selectedLog.subject_id }})</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedLog.properties?.old || selectedLog.properties?.attributes" class="space-y-4">
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground border-b pb-2">Changes</h4>
                        
                        <div v-if="selectedLog.properties.old" class="space-y-2">
                            <span class="text-xs font-semibold text-red-600 bg-red-100 px-2 py-1 rounded">Before</span>
                            <pre class="bg-muted p-4 rounded-md overflow-x-auto text-sm font-mono border border-border/50 text-muted-foreground">{{ JSON.stringify(selectedLog.properties.old, null, 2) }}</pre>
                        </div>

                        <div v-if="selectedLog.properties.attributes" class="space-y-2 mt-4">
                            <span class="text-xs font-semibold text-green-600 bg-green-100 px-2 py-1 rounded">After / Payload</span>
                            <pre class="bg-muted p-4 rounded-md overflow-x-auto text-sm font-mono border border-border/50 text-muted-foreground">{{ JSON.stringify(selectedLog.properties.attributes, null, 2) }}</pre>
                        </div>
                    </div>
                    <div v-else-if="selectedLog.properties && Object.keys(selectedLog.properties).length > 0">
                         <h4 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground border-b pb-2">Data</h4>
                         <pre class="bg-muted p-4 rounded-md overflow-x-auto text-sm font-mono border border-border/50 text-muted-foreground mt-2">{{ JSON.stringify(selectedLog.properties, null, 2) }}</pre>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
