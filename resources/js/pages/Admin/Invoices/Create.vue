<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Card, CardHeader, CardTitle, CardContent, CardDescription, CardFooter } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Loader2, Search, X, Check } from 'lucide-vue-next';
import { debounce } from 'lodash';
import axios from 'axios';

const props = defineProps<{
    sessions: Array<{ id: string; name: string }>;
}>();

const form = useForm({
    user_id: '',
    session_id: '',
    amount: '',
    type: '',
    description: '',
    due_date: '',
});

const invoiceTypes = [
    { value: 'tuition', label: 'Tuition Fee' },
    { value: 'hostel', label: 'Hostel Fee' },
    { value: 'library', label: 'Library Fine' },
    { value: 'damage', label: 'Damage Assessment' },
    { value: 'acceptance_fee', label: 'Acceptance Fee' },
    { value: 'other', label: 'Other/Miscellaneous' },
];

// Student Search State
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);
const selectedStudent = ref<any>(null);
const showResults = ref(false);

const handleSearch = debounce(async (query: string) => {
    if (!query || query.length < 2) {
        searchResults.value = [];
        return;
    }
    
    isSearching.value = true;
    try {
        const response = await axios.get(route('admin.invoices.search-students'), {
            params: { query }
        });
        searchResults.value = response.data;
        showResults.value = true;
    } catch (error) {
        console.error('Search failed', error);
    } finally {
        isSearching.value = false;
    }
}, 300);

watch(searchQuery, (newVal) => {
    if (!selectedStudent.value) { // Only search if not currently selected (or if user is typing to change)
        handleSearch(newVal);
    }
});

const selectStudent = (student: any) => {
    selectedStudent.value = student;
    form.user_id = student.id;
    searchQuery.value = student.name;
    showResults.value = false;
};

const clearSelection = () => {
    selectedStudent.value = null;
    form.user_id = '';
    searchQuery.value = '';
    searchResults.value = [];
};

const submit = () => {
    form.post(route('admin.invoices.store'), {
        preserveScroll: true,
    });
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Invoices', href: '/admin/invoices' },
    { title: 'Create Invoice', href: '/admin/invoices/create' },
];
</script>

<template>
    <Head title="Create Invoice" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl mx-auto w-full">
            <h1 class="text-2xl font-bold tracking-tight mb-6">Create New Invoice</h1>

            <Card>
                <CardHeader>
                    <CardTitle>Invoice Details</CardTitle>
                    <CardDescription>Manually generate an invoice for a student.</CardDescription>
                </CardHeader>
                <form @submit.prevent="submit">
                    <CardContent class="space-y-6">
                        
                        <!-- Student Search -->
                        <div class="space-y-2 relative">
                            <Label>Student <span class="text-red-500">*</span></Label>
                            
                            <div v-if="selectedStudent" class="flex items-center justify-between p-3 border rounded-md bg-slate-50 dark:bg-slate-900">
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-10 w-10">
                                        <AvatarImage :src="selectedStudent.profile_photo_url" />
                                        <AvatarFallback>{{ selectedStudent.name.charAt(0) }}</AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <p class="font-medium text-sm">{{ selectedStudent.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ selectedStudent.email }}</p>
                                        <p v-if="selectedStudent.student" class="text-xs font-mono text-indigo-600">
                                            {{ selectedStudent.student.matriculation_number }}
                                        </p>
                                    </div>
                                </div>
                                <Button type="button" variant="ghost" size="icon" @click="clearSelection">
                                    <X class="w-4 h-4" />
                                </Button>
                            </div>

                            <div v-else class="relative">
                                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                <Input 
                                    v-model="searchQuery" 
                                    placeholder="Search by name, matric number, or email..." 
                                    class="pl-9"
                                    @focus="showResults = true"
                                />
                                <div v-if="isSearching" class="absolute right-3 top-3">
                                    <Loader2 class="w-4 h-4 animate-spin text-muted-foreground" />
                                </div>

                                <!-- Dropdown Results -->
                                <div v-if="showResults && searchResults.length > 0" class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-950 border rounded-md shadow-lg max-h-60 overflow-y-auto">
                                    <div 
                                        v-for="student in searchResults" 
                                        :key="student.id"
                                        class="p-3 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer flex items-center gap-3 transition-colors"
                                        @click="selectStudent(student)"
                                    >
                                        <Avatar class="h-8 w-8">
                                            <AvatarImage :src="student.profile_photo_url" />
                                            <AvatarFallback>{{ student.name.charAt(0) }}</AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <p class="text-sm font-medium">{{ student.name }}</p>
                                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                                <span>{{ student.email }}</span>
                                                <span v-if="student.student" class="text-indigo-500 font-mono">• {{ student.student.matriculation_number }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="showResults && searchQuery.length > 2 && !isSearching && searchResults.length === 0" class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-950 border rounded-md shadow-lg p-4 text-center text-sm text-muted-foreground">
                                    No students found.
                                </div>
                            </div>
                            <p v-if="form.errors.user_id" class="text-sm text-red-500">{{ form.errors.user_id }}</p>
                        </div>

                        <!-- Session & Type Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label>Academic Session <span class="text-red-500">*</span></Label>
                                <Select v-model="form.session_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Session" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="session in sessions" :key="session.id" :value="session.id">
                                            {{ session.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.session_id" class="text-sm text-red-500">{{ form.errors.session_id }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label>Invoice Type <span class="text-red-500">*</span></Label>
                                <Select v-model="form.type">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in invoiceTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.type" class="text-sm text-red-500">{{ form.errors.type }}</p>
                            </div>
                        </div>

                        <!-- Amount & Due Date Row -->
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label>Amount (NGN) <span class="text-red-500">*</span></Label>
                                <Input type="number" v-model="form.amount" placeholder="0.00" min="0" step="0.01" />
                                <p v-if="form.errors.amount" class="text-sm text-red-500">{{ form.errors.amount }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label>Due Date <span class="text-red-500">*</span></Label>
                                <Input type="date" v-model="form.due_date" />
                                <p v-if="form.errors.due_date" class="text-sm text-red-500">{{ form.errors.due_date }}</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label>Description <span class="text-red-500">*</span></Label>
                            <Textarea v-model="form.description" placeholder="Enter details about this invoice..." rows="3" />
                            <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
                        </div>

                    </CardContent>
                    <CardFooter class="flex justify-end gap-3 border-t bg-slate-50/50 p-4">
                        <Button type="button" variant="ghost" @click="form.reset()">Reset</Button>
                        <Button type="submit" :disabled="form.processing || !form.user_id">
                            <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                            Create Invoice
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AdminLayout>
</template>
