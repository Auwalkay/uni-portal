<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Plus, Edit, CheckCircle, ChevronDown, ChevronUp, Lock, Unlock, Settings } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref } from 'vue';
import { route } from 'ziggy-js'; // Fix: Import route

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';


interface Semester {
    id: string;
    name: string;
    is_current: boolean;
}

interface Session {
    id: string;
    name: string;
    start_date: string;
    end_date: string;
    is_current: boolean;
    registration_enabled: boolean;
    type?: string;
    semesters?: Semester[]; // Optional if not loaded initially, but we should eager load
}

const props = defineProps<{
    sessions: Session[];
}>();

const isModalOpen = ref(false);
const editingSession = ref<Session | null>(null);
const expandedSessionId = ref<string | null>(null); // Track expanded row

const form = useForm({
    name: '',
    start_date: '',
    end_date: '',
    type: 'regular',
});

const openCreate = () => {
    editingSession.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEdit = (session: Session) => {
    editingSession.value = session;
    form.name = session.name;
    form.start_date = session.start_date.split('T')[0];
    form.end_date = session.end_date.split('T')[0];
    form.type = session.type || 'regular'; // Handle existing sessions
    isModalOpen.value = true;
};

const toggleExpand = (sessionId: string) => {
    expandedSessionId.value = expandedSessionId.value === sessionId ? null : sessionId;
};

const submitForm = () => {
    // ... existing logic ...
    const url = editingSession.value
        ? route('admin.sessions.update', editingSession.value.id)
        : route('admin.sessions.store');

    // Using Inertia form helper directly
    if (editingSession.value) {
        form.put(url, {
             onSuccess: () => { isModalOpen.value = false; Swal.fire('Success', 'Session updated', 'success'); }
        });
    } else {
        form.post(url, {
             onSuccess: () => { isModalOpen.value = false; Swal.fire('Success', 'Session created', 'success'); }
        });
    }
};

const activateSession = (session: Session) => {
    Swal.fire({
        title: 'Activate Session?',
        text: `Set ${session.name} as current? This will promote students and activate the First Semester.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Activate',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.sessions.activate', session.id));
        }
    });
};

const toggleRegistration = (session: Session) => {
    const action = session.registration_enabled ? 'Disable' : 'Enable';
    Swal.fire({
        title: `${action} Registration?`,
        text: `Are you sure you want to ${action.toLowerCase()} course registration for ${session.name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Yes, ${action}`,
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.sessions.toggle_registration', session.id));
        }
    });
};

const activateSemester = (session: Session, semester: Semester) => {
    if (semester.is_current) return;

    Swal.fire({
        title: `Activate ${semester.name}?`,
        text: `Switching active semester for ${session.name}.`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes, Switch',
    }).then((result) => {
        if (result.isConfirmed) {
             router.post(route('admin.sessions.semesters.activate', [session.id, semester.id]));
        }
    });
};
</script>

<template>
    <Head title="Academic Sessions" />
    <AdminLayout>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Academic Sessions</h2>
                    <p class="text-muted-foreground">Manage academic years and semesters.</p>
                </div>
                <Button @click="openCreate"><Plus class="mr-2 h-4 w-4" /> New Session</Button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[50px]"></TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Start Date</TableHead>
                                <TableHead>End Date</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="session in sessions" :key="session.id">
                                <TableRow class="cursor-pointer hover:bg-muted/50" @click="toggleExpand(session.id)">
                                     <TableCell>
                                        <ChevronDown v-if="expandedSessionId === session.id" class="h-4 w-4" />
                                        <ChevronUp v-else class="h-4 w-4" />
                                    </TableCell>
                                    <TableCell class="font-medium">{{ session.name }}</TableCell>
                                    <TableCell>{{ new Date(session.start_date).toLocaleDateString() }}</TableCell>
                                    <TableCell>{{ new Date(session.end_date).toLocaleDateString() }}</TableCell>
                                    <TableCell>
                                        <Badge v-if="session.is_current" variant="default" class="bg-green-600">Current</Badge>
                                        <Badge v-else variant="outline">Inactive</Badge>
                                    </TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Button v-if="!session.is_current" variant="ghost" size="sm" @click.stop="activateSession(session)" title="Activate Session">
                                            <CheckCircle class="h-4 w-4 text-green-600" />
                                        </Button>
                                        <Button variant="ghost" size="sm" @click.stop="toggleRegistration(session)" :title="session.registration_enabled ? 'Disable Registration' : 'Enable Registration'">
                                            <Unlock v-if="session.registration_enabled" class="h-4 w-4 text-blue-600" />
                                            <Lock v-else class="h-4 w-4 text-gray-400" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click.stop="openEdit(session)">
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" as="a" :href="route('admin.sessions.show', session.id)" title="Session Settings">
                                            <Settings class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <!-- Nested Semesters Row -->
                                <TableRow v-if="expandedSessionId === session.id" class="bg-muted/30">
                                    <TableCell colspan="6" class="p-4">
                                        <div class="rounded-md border bg-background p-4">
                                            <h4 class="mb-4 text-sm font-semibold">Semesters</h4>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div v-for="semester in session.semesters" :key="semester.id"
                                                     class="flex items-center justify-between rounded-lg border p-3 hover:bg-accent/50 transition-colors">
                                                    <div class="flex items-center gap-3">
                                                        <div :class="['h-2 w-2 rounded-full', semester.is_current ? 'bg-green-500' : 'bg-gray-300']"></div>
                                                        <span :class="{'font-medium': semester.is_current}">{{ semester.name }}</span>
                                                    </div>
                                                    <Button
                                                        v-if="!semester.is_current && session.is_current"
                                                        size="sm"
                                                        variant="outline"
                                                        @click.stop="activateSemester(session, semester)"
                                                    >
                                                        Activate
                                                    </Button>
                                                    <Badge v-else-if="semester.is_current" variant="secondary">Active</Badge>
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Dialog (Keep existing) -->
            <Dialog v-model:open="isModalOpen">
               <!-- ... Same as before ... -->
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>{{ editingSession ? 'Edit Session' : 'New Session' }}</DialogTitle>
                        <DialogDescription>
                            Define the academic session period.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="name">Session Name</Label>
                            <Input id="name" v-model="form.name" placeholder="2024/2025" />
                            <span v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</span>
                        </div>

                        <div class="grid gap-2">
                            <Label for="type">Session Type</Label>
                            <select
                                id="type"
                                v-model="form.type"
                                :disabled="!!editingSession"
                                class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="regular">Regular Session (2 Semesters)</option>
                                <option value="summer">Summer Session (1 Semester)</option>
                            </select>
                            <span v-if="form.errors.type" class="text-xs text-destructive">{{ form.errors.type }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="start">Start Date</Label>
                                <Input id="start" type="date" v-model="form.start_date" />
                                <span v-if="form.errors.start_date" class="text-xs text-destructive">{{ form.errors.start_date }}</span>
                            </div>
                            <div class="grid gap-2">
                                <Label for="end">End Date</Label>
                                <Input id="end" type="date" v-model="form.end_date" />
                                <span v-if="form.errors.end_date" class="text-xs text-destructive">{{ form.errors.end_date }}</span>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isModalOpen = false">Cancel</Button>
                        <Button @click="submitForm" :disabled="form.processing">Save</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
