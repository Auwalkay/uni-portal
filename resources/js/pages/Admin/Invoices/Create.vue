<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted } from 'vue';
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

const searchContainer = ref<HTMLElement | null>(null);

const handleClickOutside = (e: MouseEvent) => {
    if (searchContainer.value && !searchContainer.value.contains(e.target as Node)) {
        showResults.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

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
    { value: 'school_fee', label: 'School Fees / Tuition' },
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

const isCalculatingFee = ref(false);
const feeBreakdown = ref<any>(null);

const formatCurrency = (val: any) => {
    const num = Number(val);
    return isNaN(num) ? 'NGN 0.00' : new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(num);
};

const calculateFee = async () => {
    if (!form.user_id || !form.session_id || !form.type) {
        feeBreakdown.value = null;
        return;
    }

    if (form.type !== 'school_fee' && form.type !== 'hostel') {
        feeBreakdown.value = null;
        return;
    }

    isCalculatingFee.value = true;
    try {
        const response = await axios.get(route('admin.invoices.calculate-fee'), {
            params: {
                user_id: form.user_id,
                session_id: form.session_id,
                type: form.type
            }
        });
        if (response.data) {
            form.amount = response.data.amount !== null ? String(response.data.amount) : '';
            form.description = response.data.description || '';
            feeBreakdown.value = response.data.breakdown || null;
        }
    } catch (error) {
        console.error('Failed to calculate fee:', error);
        feeBreakdown.value = null;
    } finally {
        isCalculatingFee.value = false;
    }
};

watch(() => [form.user_id, form.session_id, form.type], () => {
    calculateFee();
});

watch(() => form.type, (newType) => {
    if (newType !== 'school_fee' && newType !== 'hostel') {
        feeBreakdown.value = null;
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
    feeBreakdown.value = null;
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
                        <div class="space-y-2 relative" ref="searchContainer">
                            <Label>Student <span class="text-red-500">*</span></Label>
                            
                            <div v-if="selectedStudent" class="relative overflow-hidden p-4 rounded-xl border border-indigo-100 dark:border-indigo-950/40 bg-gradient-to-r from-indigo-50/40 via-white to-white dark:from-indigo-950/10 dark:via-slate-900 dark:to-slate-900 shadow-sm flex items-center justify-between transition duration-300 hover:shadow">
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        <Avatar class="h-12 w-12 border-2 border-indigo-200 dark:border-indigo-900 shadow-sm">
                                            <AvatarImage :src="selectedStudent.profile_photo_url" />
                                            <AvatarFallback class="bg-indigo-100 text-indigo-700 font-semibold">{{ selectedStudent.name.charAt(0) }}</AvatarFallback>
                                        </Avatar>
                                        <div class="absolute -bottom-1 -right-1 bg-green-500 text-white rounded-full p-0.5 border border-white dark:border-slate-900 shadow-sm">
                                            <Check class="w-3 h-3" />
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-semibold text-base text-slate-900 dark:text-slate-100">{{ selectedStudent.name }}</p>
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-muted-foreground">
                                            <span>{{ selectedStudent.email }}</span>
                                            <span v-if="selectedStudent.student" class="text-slate-300 dark:text-slate-700">•</span>
                                            <span v-if="selectedStudent.student" class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold">{{ selectedStudent.student.matriculation_number }}</span>
                                            <span v-if="selectedStudent.student?.department" class="text-slate-300 dark:text-slate-700">•</span>
                                            <span v-if="selectedStudent.student?.department" class="text-emerald-600 dark:text-emerald-400 font-medium">{{ selectedStudent.student.department.name }}</span>
                                            <span v-if="selectedStudent.student?.current_level" class="text-slate-300 dark:text-slate-700">•</span>
                                            <span v-if="selectedStudent.student?.current_level" class="bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 px-1.5 py-0.5 rounded text-[10px] uppercase font-bold">{{ selectedStudent.student.current_level }} Level</span>
                                        </div>
                                    </div>
                                </div>
                                <Button type="button" variant="ghost" size="icon" @click="clearSelection" class="hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/20 text-muted-foreground transition-colors rounded-full" title="Clear selection">
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
                                <div v-if="showResults && searchResults.length > 0" class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-950 border rounded-md shadow-lg max-h-60 overflow-y-auto divide-y dark:divide-slate-800">
                                    <div 
                                        v-for="student in searchResults" 
                                        :key="student.id"
                                        class="p-3 hover:bg-slate-50 dark:hover:bg-slate-900 cursor-pointer flex items-center gap-3 transition-colors"
                                        @click="selectStudent(student)"
                                    >
                                        <Avatar class="h-9 w-9">
                                            <AvatarImage :src="student.profile_photo_url" />
                                            <AvatarFallback class="bg-indigo-100 text-indigo-700 font-semibold">{{ student.name.charAt(0) }}</AvatarFallback>
                                        </Avatar>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold truncate text-slate-900 dark:text-slate-100">{{ student.name }}</p>
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-muted-foreground mt-0.5">
                                                <span class="truncate">{{ student.email }}</span>
                                                <span v-if="student.student" class="text-slate-300 dark:text-slate-700">•</span>
                                                <span v-if="student.student" class="font-mono text-indigo-600 dark:text-indigo-400 font-medium">{{ student.student.matriculation_number }}</span>
                                                <span v-if="student.student?.department" class="text-slate-300 dark:text-slate-700">•</span>
                                                <span v-if="student.student?.department" class="truncate text-emerald-600 dark:text-emerald-400">{{ student.student.department.name }}</span>
                                                <span v-if="student.student?.current_level" class="text-slate-300 dark:text-slate-700">•</span>
                                                <span v-if="student.student?.current_level" class="text-amber-600 dark:text-amber-400">{{ student.student.current_level }} Lvl</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="showResults && searchQuery.length > 2 && !isSearching && searchResults.length === 0" class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-950 border rounded-md shadow-lg p-4 text-center text-sm text-muted-foreground">
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

                        <!-- Fee Breakdown -->
                        <div v-if="feeBreakdown" class="p-4 rounded-xl border border-indigo-100 dark:border-indigo-950/40 bg-indigo-50/10 dark:bg-indigo-950/5 space-y-3">
                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Fee Breakdown</h4>
                            <div class="space-y-1.5 divide-y dark:divide-slate-800">
                                <div v-for="item in feeBreakdown.items" :key="item.name" class="flex justify-between py-1.5 text-xs">
                                    <span class="text-muted-foreground">{{ item.name }}</span>
                                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ formatCurrency(item.amount) }}</span>
                                </div>
                                <div v-if="feeBreakdown.scholarship" class="flex justify-between py-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                                    <span>Scholarship: {{ feeBreakdown.scholarship.name }} ({{ feeBreakdown.scholarship.percentage }}%)</span>
                                    <span class="font-bold">-{{ formatCurrency(feeBreakdown.scholarship.discount) }}</span>
                                </div>
                                <div class="flex justify-between pt-2.5 text-sm font-bold border-t">
                                    <span class="text-slate-900 dark:text-slate-100">Total Invoice Amount</span>
                                    <span class="text-indigo-600 dark:text-indigo-400 font-mono">{{ formatCurrency(feeBreakdown.total) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Amount & Due Date Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2 relative">
                                <div class="flex justify-between items-center">
                                    <Label>Amount (NGN) <span class="text-red-500">*</span></Label>
                                    <span v-if="isCalculatingFee" class="text-[10px] text-indigo-600 dark:text-indigo-400 flex items-center gap-1 font-medium">
                                        <Loader2 class="w-3 h-3 animate-spin" /> Calculating...
                                    </span>
                                </div>
                                <Input type="number" v-model="form.amount" placeholder="0.00" min="0" step="0.01" :disabled="isCalculatingFee || form.type === 'school_fee' || form.type === 'hostel'" :class="{'bg-slate-50 dark:bg-slate-900 opacity-80 cursor-not-allowed': form.type === 'school_fee' || form.type === 'hostel'}" />
                                <span v-if="form.type === 'school_fee' || form.type === 'hostel'" class="text-[10px] text-muted-foreground block mt-1">
                                    Locked to configured fee configuration amount.
                                </span>
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
                            <Textarea v-model="form.description" placeholder="Enter details about this invoice..." rows="3" :disabled="isCalculatingFee || form.type === 'school_fee' || form.type === 'hostel'" :class="{'bg-slate-50 dark:bg-slate-900 opacity-80 cursor-not-allowed': form.type === 'school_fee' || form.type === 'hostel'}" />
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
