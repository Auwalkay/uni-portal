<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Card, CardHeader, CardTitle, CardContent, CardDescription, CardFooter } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
  Loader2, Search, X, Check, ArrowLeft, Receipt, Calendar, 
  GraduationCap, Building, BookOpen, AlertTriangle, ShieldCheck, Tag,
  Clock, Sparkles, User, FileText, CheckCircle2, Info
} from 'lucide-vue-next';
import { debounce } from 'lodash';
import axios from 'axios';

const props = defineProps<{
    sessions: Array<{ id: string; name: string }>;
}>();

const searchContainer = ref<HTMLElement | null>(null);

const handleClickOutside = (e: MouseEvent) => {
    if (searchContainer.value && !searchContainer.value.contains(e.target as Node)) {
        showResults.value = false;
    }
};

const handleKeyDown = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
        if (form.user_id && !form.processing) {
            submit();
        }
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    if (props.sessions && props.sessions.length > 0 && !form.session_id) {
        form.session_id = props.sessions[0].id;
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
});

const form = useForm({
    user_id: '',
    session_id: '',
    amount: '',
    type: 'school_fee',
    description: '',
    due_date: '',
});

const invoiceTypes = [
    { value: 'school_fee', label: 'Tuition / School Fees', description: 'Auto-calculated based on level & department', icon: GraduationCap, colorClass: 'border-indigo-200 bg-indigo-50/50 text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/30 dark:text-indigo-300' },
    { value: 'hostel', label: 'Hostel Accommodation', description: 'Auto-calculated hostel lodging charges', icon: Building, colorClass: 'border-emerald-200 bg-emerald-50/50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300' },
    { value: 'library', label: 'Library Fine', description: 'Overdue or damaged book charges', icon: BookOpen, colorClass: 'border-amber-200 bg-amber-50/50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300' },
    { value: 'damage', label: 'Property Damage', description: 'Assessment for damaged campus facility', icon: AlertTriangle, colorClass: 'border-rose-200 bg-rose-50/50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300' },
    { value: 'acceptance_fee', label: 'Acceptance Fee', description: 'Freshman admission acceptance fee', icon: ShieldCheck, colorClass: 'border-purple-200 bg-purple-50/50 text-purple-700 dark:border-purple-900/60 dark:bg-purple-950/30 dark:text-purple-300' },
    { value: 'other', label: 'Other / Miscellaneous', description: 'Manual custom fee line item', icon: Tag, colorClass: 'border-slate-200 bg-slate-50/50 text-slate-700 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-300' },
];

const selectedTypeDetails = computed(() => {
    return invoiceTypes.find(t => t.value === form.type) || invoiceTypes[0];
});

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
    if (!selectedStudent.value) {
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

const setDueDatePreset = (days: number) => {
    const date = new Date();
    date.setDate(date.getDate() + days);
    form.due_date = date.toISOString().split('T')[0];
};

const setEndOfMonthPreset = () => {
    const date = new Date();
    const endOfMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    form.due_date = endOfMonth.toISOString().split('T')[0];
};

const selectedSessionName = computed(() => {
    const s = props.sessions?.find(sess => String(sess.id) === String(form.session_id));
    return s ? s.name : 'Not selected';
});

const formattedToday = computed(() => {
    return new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
});

const formattedDueDate = computed(() => {
    if (!form.due_date) return 'Not set';
    const parts = form.due_date.split('-');
    if (parts.length !== 3) return form.due_date;
    const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
});

const submit = () => {
    form.post(route('admin.invoices.store'), {
        preserveScroll: false,
        onError: (errors) => {
            console.error('Invoice creation failed with validation errors:', errors);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
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
        <div class="p-6 max-w-7xl mx-auto w-full space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-2 border-b dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <Button variant="outline" size="icon" as-child class="h-9 w-9 rounded-lg">
                        <Link href="/admin/invoices">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Create New Invoice</h1>
                            <Badge variant="outline" class="bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800">
                                <Sparkles class="w-3 h-3 mr-1 text-indigo-500 animate-pulse" /> Manual Billing
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground mt-0.5">Generate customized billing statements or automated tuition fee invoices for students.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" @click="form.reset(); clearSelection()" class="text-xs text-muted-foreground hover:text-slate-900">
                        Reset Form
                    </Button>
                </div>
            </div>

            <!-- Global Error Banner -->
            <div v-if="form.hasErrors || $page.props.errors?.error || $page.props.flash?.error" class="p-4 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/90 dark:bg-rose-950/40 text-rose-900 dark:text-rose-200 space-y-2 shadow-sm animate-in fade-in duration-200">
                <div class="flex items-center gap-2 font-bold text-sm text-rose-700 dark:text-rose-400">
                    <AlertTriangle class="w-4 h-4 flex-shrink-0" />
                    <span>Unable to Create Invoice</span>
                </div>
                <ul class="list-disc list-inside text-xs space-y-1 text-rose-800 dark:text-rose-300 font-medium">
                    <li v-if="$page.props.flash?.error">{{ $page.props.flash.error }}</li>
                    <li v-if="$page.props.errors?.error">{{ $page.props.errors.error }}</li>
                    <li v-for="(msg, field) in form.errors" :key="field">
                        <span class="font-semibold capitalize">{{ String(field).replace('_', ' ') }}:</span> {{ msg }}
                    </li>
                </ul>
            </div>

            <!-- Main 2-Column Split Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left Column: Form Controls (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- Card 1: Student Selection -->
                        <Card class="border-slate-200 dark:border-slate-800 shadow-sm transition duration-200 hover:shadow-md">
                            <CardHeader class="pb-4">
                                <CardTitle class="text-base font-semibold flex items-center gap-2">
                                    <User class="w-4 h-4 text-indigo-600 dark:text-indigo-400" /> Select Recipient Student
                                </CardTitle>
                                <CardDescription class="text-xs">Search for a student by full name, email address, or matriculation number.</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div class="space-y-2 relative" ref="searchContainer">
                                    
                                    <!-- Selected Student Pill -->
                                    <div v-if="selectedStudent" class="relative overflow-hidden p-4 rounded-xl border border-indigo-200/80 dark:border-indigo-900/50 bg-gradient-to-r from-indigo-50/60 via-white to-indigo-50/30 dark:from-indigo-950/30 dark:via-slate-900 dark:to-indigo-950/20 shadow-sm flex items-center justify-between transition duration-300">
                                        <div class="flex items-center gap-3.5 min-w-0">
                                            <div class="relative flex-shrink-0">
                                                <Avatar class="h-12 w-12 border-2 border-indigo-200 dark:border-indigo-900 shadow-sm">
                                                    <AvatarImage :src="selectedStudent.profile_photo_url" />
                                                    <AvatarFallback class="bg-indigo-600 text-white font-bold text-base">{{ selectedStudent.name.charAt(0) }}</AvatarFallback>
                                                </Avatar>
                                                <div class="absolute -bottom-1 -right-1 bg-emerald-500 text-white rounded-full p-0.5 border border-white dark:border-slate-900 shadow-sm">
                                                    <Check class="w-3 h-3" />
                                                </div>
                                            </div>
                                            <div class="space-y-1 min-w-0">
                                                <p class="font-semibold text-base text-slate-900 dark:text-slate-100 truncate">{{ selectedStudent.name }}</p>
                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                                    <span class="truncate max-w-[180px]">{{ selectedStudent.email }}</span>
                                                    <template v-if="selectedStudent.student">
                                                        <span class="text-slate-300 dark:text-slate-700">•</span>
                                                        <span class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold bg-indigo-100/70 dark:bg-indigo-950/60 px-1.5 py-0.5 rounded text-[11px]">{{ selectedStudent.student.matriculation_number }}</span>
                                                    </template>
                                                    <template v-if="selectedStudent.student?.department">
                                                        <span class="text-slate-300 dark:text-slate-700">•</span>
                                                        <span class="text-emerald-700 dark:text-emerald-400 font-medium">{{ selectedStudent.student.department.name }}</span>
                                                    </template>
                                                    <template v-if="selectedStudent.student?.current_level">
                                                        <span class="text-slate-300 dark:text-slate-700">•</span>
                                                        <span class="bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 px-1.5 py-0.5 rounded text-[10px] uppercase font-bold">{{ selectedStudent.student.current_level }} Lvl</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <Button type="button" variant="ghost" size="icon" @click="clearSelection" class="hover:bg-rose-100 hover:text-rose-600 dark:hover:bg-rose-950/40 text-slate-400 transition-colors rounded-full flex-shrink-0" title="Clear student selection">
                                            <X class="w-4 h-4" />
                                        </Button>
                                    </div>

                                    <!-- Search Input -->
                                    <div v-else class="relative">
                                        <Search class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground" />
                                        <Input 
                                            v-model="searchQuery" 
                                            placeholder="Type student name, matric number, or email address..." 
                                            class="pl-10 pr-10 h-11 border-slate-200 dark:border-slate-800 focus-visible:ring-indigo-500"
                                            @focus="showResults = true"
                                        />
                                        <div v-if="isSearching" class="absolute right-3.5 top-3.5">
                                            <Loader2 class="w-4 h-4 animate-spin text-indigo-600 dark:text-indigo-400" />
                                        </div>

                                        <!-- Autocomplete Results Menu -->
                                        <div v-if="showResults && searchResults.length > 0" class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl max-h-64 overflow-y-auto divide-y dark:divide-slate-800/60">
                                            <div 
                                                v-for="student in searchResults" 
                                                :key="student.id"
                                                class="p-3 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/30 cursor-pointer flex items-center gap-3 transition-colors"
                                                @click="selectStudent(student)"
                                            >
                                                <Avatar class="h-10 w-10 border border-slate-200 dark:border-slate-800">
                                                    <AvatarImage :src="student.profile_photo_url" />
                                                    <AvatarFallback class="bg-indigo-100 text-indigo-700 font-semibold">{{ student.name.charAt(0) }}</AvatarFallback>
                                                </Avatar>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between">
                                                        <p class="text-sm font-semibold truncate text-slate-900 dark:text-slate-100">{{ student.name }}</p>
                                                        <span v-if="student.student?.current_level" class="text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 px-1.5 py-0.2 rounded">{{ student.student.current_level }} Lvl</span>
                                                    </div>
                                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-muted-foreground mt-0.5">
                                                        <span class="truncate max-w-[160px]">{{ student.email }}</span>
                                                        <span v-if="student.student" class="text-slate-300 dark:text-slate-700">•</span>
                                                        <span v-if="student.student" class="font-mono text-indigo-600 dark:text-indigo-400 font-medium">{{ student.student.matriculation_number }}</span>
                                                        <span v-if="student.student?.department" class="text-slate-300 dark:text-slate-700">•</span>
                                                        <span v-if="student.student?.department" class="truncate text-emerald-600 dark:text-emerald-400">{{ student.student.department.name }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Empty State Search Result -->
                                        <div v-if="showResults && searchQuery.length > 2 && !isSearching && searchResults.length === 0" class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl shadow-lg p-5 text-center text-sm text-muted-foreground">
                                            <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-2">
                                                <Search class="w-4 h-4" />
                                            </div>
                                            <p class="font-medium text-slate-700 dark:text-slate-300">No matching students found</p>
                                            <p class="text-xs text-muted-foreground mt-0.5">Try searching by student email or exact matriculation number.</p>
                                        </div>
                                    </div>

                                    <p v-if="form.errors.user_id" class="text-xs text-rose-500 font-medium flex items-center gap-1 mt-1">
                                        <AlertTriangle class="w-3.5 h-3.5" /> {{ form.errors.user_id }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Card 2: Invoice Type & Session Details -->
                        <Card class="border-slate-200 dark:border-slate-800 shadow-sm transition duration-200 hover:shadow-md">
                            <CardHeader class="pb-4">
                                <CardTitle class="text-base font-semibold flex items-center gap-2">
                                    <Receipt class="w-4 h-4 text-indigo-600 dark:text-indigo-400" /> Invoice Type & Academic Session
                                </CardTitle>
                                <CardDescription class="text-xs">Choose the fee category and target academic session.</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-5">
                                
                                <!-- Session Selector -->
                                <div class="space-y-2">
                                    <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Academic Session <span class="text-rose-500">*</span></Label>
                                    <Select v-model="form.session_id">
                                        <SelectTrigger class="h-10 border-slate-200 dark:border-slate-800">
                                            <SelectValue placeholder="Select Academic Session" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="session in sessions" :key="session.id" :value="session.id">
                                                <div class="flex items-center gap-2">
                                                    <Calendar class="w-3.5 h-3.5 text-indigo-500" />
                                                    <span>{{ session.name }}</span>
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.session_id" class="text-xs text-rose-500 font-medium">{{ form.errors.session_id }}</p>
                                </div>

                                <!-- Visual Invoice Type Grid Cards -->
                                <div class="space-y-2">
                                    <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Invoice Category <span class="text-rose-500">*</span></Label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div 
                                            v-for="item in invoiceTypes" 
                                            :key="item.value"
                                            @click="form.type = item.value"
                                            :class="[
                                                'p-3 rounded-xl border cursor-pointer transition-all duration-200 flex items-start gap-3 relative overflow-hidden',
                                                form.type === item.value 
                                                    ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/40 shadow-sm ring-2 ring-indigo-500/20' 
                                                    : 'border-slate-200 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-800 hover:bg-slate-50 dark:hover:bg-slate-900/60'
                                            ]"
                                        >
                                            <div :class="['p-2 rounded-lg border flex-shrink-0', item.colorClass]">
                                                <component :is="item.icon" class="w-4 h-4" />
                                            </div>
                                            <div class="space-y-0.5 min-w-0 pr-4">
                                                <p :class="['text-xs font-bold truncate', form.type === item.value ? 'text-indigo-900 dark:text-indigo-100' : 'text-slate-800 dark:text-slate-200']">
                                                    {{ item.label }}
                                                </p>
                                                <p class="text-[11px] text-muted-foreground line-clamp-2 leading-snug">
                                                    {{ item.description }}
                                                </p>
                                            </div>
                                            <div v-if="form.type === item.value" class="absolute top-2.5 right-2.5 text-indigo-600 dark:text-indigo-400">
                                                <CheckCircle2 class="w-4 h-4" />
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.type" class="text-xs text-rose-500 font-medium">{{ form.errors.type }}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Card 3: Amounts, Due Date & Description -->
                        <Card class="border-slate-200 dark:border-slate-800 shadow-sm transition duration-200 hover:shadow-md">
                            <CardHeader class="pb-4">
                                <CardTitle class="text-base font-semibold flex items-center gap-2">
                                    <FileText class="w-4 h-4 text-indigo-600 dark:text-indigo-400" /> Billing Details & Schedule
                                </CardTitle>
                                <CardDescription class="text-xs">Specify total invoice amount, due date deadline, and line item description.</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-5">
                                
                                <!-- Amount & Due Date Row -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    
                                    <!-- Amount -->
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center">
                                            <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Total Amount (NGN) <span class="text-rose-500">*</span></Label>
                                            <span v-if="isCalculatingFee" class="text-[10px] text-indigo-600 dark:text-indigo-400 flex items-center gap-1 font-medium">
                                                <Loader2 class="w-3 h-3 animate-spin" /> Calculating...
                                            </span>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">₦</span>
                                            <Input 
                                                type="number" 
                                                v-model="form.amount" 
                                                placeholder="0.00" 
                                                min="0" 
                                                step="0.01" 
                                                :disabled="isCalculatingFee || form.type === 'school_fee' || form.type === 'hostel'" 
                                                :class="[
                                                    'pl-7 font-mono font-semibold h-10',
                                                    (form.type === 'school_fee' || form.type === 'hostel') 
                                                        ? 'bg-slate-100 dark:bg-slate-900 opacity-80 cursor-not-allowed border-slate-200' 
                                                        : 'border-slate-200 dark:border-slate-800'
                                                ]" 
                                            />
                                        </div>
                                        <p v-if="form.type === 'school_fee' || form.type === 'hostel'" class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                            <Info class="w-3 h-3 text-indigo-500" /> Amount is locked based on active fee configuration.
                                        </p>
                                        <p v-if="form.errors.amount" class="text-xs text-rose-500 font-medium">{{ form.errors.amount }}</p>
                                    </div>

                                    <!-- Due Date -->
                                    <div class="space-y-2">
                                        <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Payment Due Date <span class="text-rose-500">*</span></Label>
                                        <Input type="date" v-model="form.due_date" class="h-10 border-slate-200 dark:border-slate-800" />
                                        <p v-if="form.errors.due_date" class="text-xs text-rose-500 font-medium">{{ form.errors.due_date }}</p>
                                    </div>
                                </div>

                                <!-- Quick Due Date Presets -->
                                <div class="space-y-1.5 pt-1">
                                    <span class="text-[11px] font-semibold text-muted-foreground uppercase tracking-wider block">Quick Presets for Due Date:</span>
                                    <div class="flex flex-wrap gap-2">
                                        <Button type="button" variant="outline" size="sm" @click="setDueDatePreset(7)" class="h-7 text-xs px-2.5 rounded-lg border-slate-200 hover:border-indigo-300">
                                            +7 Days
                                        </Button>
                                        <Button type="button" variant="outline" size="sm" @click="setDueDatePreset(14)" class="h-7 text-xs px-2.5 rounded-lg border-slate-200 hover:border-indigo-300">
                                            +14 Days
                                        </Button>
                                        <Button type="button" variant="outline" size="sm" @click="setDueDatePreset(30)" class="h-7 text-xs px-2.5 rounded-lg border-slate-200 hover:border-indigo-300">
                                            +30 Days
                                        </Button>
                                        <Button type="button" variant="outline" size="sm" @click="setEndOfMonthPreset" class="h-7 text-xs px-2.5 rounded-lg border-slate-200 hover:border-indigo-300">
                                            End of Month
                                        </Button>
                                    </div>
                                </div>

                                <!-- Description / Remarks -->
                                <div class="space-y-2 pt-2">
                                    <Label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Invoice Description <span class="text-rose-500">*</span></Label>
                                    <Textarea 
                                        v-model="form.description" 
                                        placeholder="Describe the invoice items, payment terms, or billing notes..." 
                                        rows="3" 
                                        :disabled="isCalculatingFee || form.type === 'school_fee' || form.type === 'hostel'" 
                                        :class="[
                                            'border-slate-200 dark:border-slate-800 text-xs leading-relaxed',
                                            (form.type === 'school_fee' || form.type === 'hostel') ? 'bg-slate-100 dark:bg-slate-900 opacity-80 cursor-not-allowed' : ''
                                        ]" 
                                    />
                                    <p v-if="form.errors.description" class="text-xs text-rose-500 font-medium">{{ form.errors.description }}</p>
                                </div>

                            </CardContent>

                            <CardFooter class="flex items-center justify-between gap-3 border-t bg-slate-50/60 dark:bg-slate-900/40 px-6 py-4 rounded-b-xl">
                                <div class="hidden sm:flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Clock class="w-3.5 h-3.5" />
                                    <span>Press <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-200 dark:bg-slate-800 rounded border border-slate-300 dark:border-slate-700">Cmd + Enter</kbd> to submit</span>
                                </div>

                                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                                    <Button type="button" variant="outline" @click="form.reset(); clearSelection()" class="border-slate-200">
                                        Reset
                                    </Button>
                                    <Button type="submit" :disabled="form.processing || !form.user_id" class="bg-indigo-600 hover:bg-indigo-700 text-white min-w-[140px] shadow-sm">
                                        <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                                        <span v-else>Generate Invoice</span>
                                    </Button>
                                </div>
                            </CardFooter>
                        </Card>
                    </form>
                </div>

                <!-- Right Column: Live Interactive Draft Preview Card (5 cols) -->
                <div class="lg:col-span-5 lg:sticky lg:top-6 space-y-4">
                    
                    <Card class="border-indigo-100 dark:border-indigo-950/60 bg-gradient-to-b from-white via-slate-50/30 to-indigo-50/20 dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950/10 shadow-md overflow-hidden">
                        
                        <!-- Header Banner -->
                        <div class="bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 text-white p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Receipt class="w-5 h-5 text-indigo-300" />
                                    <span class="text-sm font-bold tracking-wide uppercase text-indigo-200">Invoice Draft</span>
                                </div>
                                <Badge class="bg-amber-500/20 text-amber-300 border-amber-400/40 text-[10px] uppercase font-bold tracking-wider px-2 py-0.5">
                                    Draft Preview
                                </Badge>
                            </div>

                            <div class="flex items-baseline justify-between pt-1">
                                <div>
                                    <span class="text-xs text-indigo-300 block">Reference No.</span>
                                    <span class="font-mono text-sm font-semibold tracking-wider text-slate-100">INV-{{ new Date().getFullYear() }}-XXXX</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-indigo-300 block">Issue Date</span>
                                    <span class="text-xs font-medium text-slate-200">{{ formattedToday }}</span>
                                </div>
                            </div>
                        </div>

                        <CardContent class="p-5 space-y-5">
                            
                            <!-- Bill To Recipient Preview -->
                            <div class="space-y-2 pb-4 border-b dark:border-slate-800">
                                <span class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider block">Billed To</span>
                                
                                <div v-if="selectedStudent" class="flex items-center gap-3 p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                                    <Avatar class="h-10 w-10 border border-indigo-200 dark:border-indigo-800">
                                        <AvatarImage :src="selectedStudent.profile_photo_url" />
                                        <AvatarFallback class="bg-indigo-600 text-white font-bold">{{ selectedStudent.name.charAt(0) }}</AvatarFallback>
                                    </Avatar>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">{{ selectedStudent.name }}</p>
                                        <p class="text-[11px] text-muted-foreground truncate">{{ selectedStudent.email }}</p>
                                        <p v-if="selectedStudent.student" class="text-[11px] font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">
                                            Matric: {{ selectedStudent.student.matriculation_number }}
                                        </p>
                                    </div>
                                </div>

                                <div v-else class="p-4 rounded-lg border border-dashed border-slate-300 dark:border-slate-800 text-center bg-slate-50/50 dark:bg-slate-900/20 text-xs text-muted-foreground">
                                    <User class="w-5 h-5 mx-auto mb-1 text-slate-400" />
                                    <span>No student selected yet</span>
                                </div>
                            </div>

                            <!-- Invoice Attributes Summary -->
                            <div class="grid grid-cols-2 gap-3 text-xs pb-4 border-b dark:border-slate-800">
                                <div>
                                    <span class="text-muted-foreground block text-[11px]">Academic Session:</span>
                                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ selectedSessionName }}</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block text-[11px]">Category:</span>
                                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ selectedTypeDetails.label }}</span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block text-[11px]">Due Date:</span>
                                    <span :class="['font-medium', form.due_date ? 'text-slate-900 dark:text-slate-100' : 'text-slate-400']">
                                        {{ formattedDueDate }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-muted-foreground block text-[11px]">Payment Status:</span>
                                    <span class="font-semibold text-amber-600 dark:text-amber-400">UNPAID (Pending)</span>
                                </div>
                            </div>

                            <!-- Line Items / Breakdown -->
                            <div class="space-y-2">
                                <span class="text-[11px] font-bold text-muted-foreground uppercase tracking-wider block">Line Item Breakdown</span>

                                <!-- Structured Breakdown for Tuition/Hostel Fees -->
                                <div v-if="feeBreakdown" class="space-y-2">
                                    <div class="rounded-xl border border-indigo-100 dark:border-indigo-950/60 bg-white dark:bg-slate-900 overflow-hidden divide-y dark:divide-slate-800">
                                        <div v-for="item in feeBreakdown.items" :key="item.name" class="flex items-center justify-between p-2.5 text-xs">
                                            <span class="text-slate-700 dark:text-slate-300 font-medium">{{ item.name }}</span>
                                            <span class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ formatCurrency(item.amount) }}</span>
                                        </div>
                                    </div>

                                    <!-- Scholarship Discount Card -->
                                    <div v-if="feeBreakdown.scholarship" class="p-3 rounded-xl border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/60 dark:bg-emerald-950/30 flex items-center justify-between text-xs text-emerald-800 dark:text-emerald-300">
                                        <div class="flex items-center gap-2">
                                            <Sparkles class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                                            <div>
                                                <span class="font-semibold block">{{ feeBreakdown.scholarship.name }}</span>
                                                <span class="text-[10px] text-emerald-600 dark:text-emerald-400">Scholarship Discount Applied</span>
                                            </div>
                                        </div>
                                        <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400 text-sm">
                                            -{{ formatCurrency(feeBreakdown.scholarship.discount) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Manual Single Line Item -->
                                <div v-else class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between text-xs">
                                    <div class="space-y-0.5 pr-2">
                                        <span class="font-semibold text-slate-900 dark:text-slate-100 block">{{ selectedTypeDetails.label }}</span>
                                        <span class="text-[11px] text-muted-foreground line-clamp-1">
                                            {{ form.description || 'Manual fee billing item' }}
                                        </span>
                                    </div>
                                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100 text-sm flex-shrink-0">
                                        {{ formatCurrency(form.amount) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Prominent Total Payable Display -->
                            <div class="p-4 rounded-xl bg-gradient-to-r from-indigo-50 via-slate-50 to-indigo-50/50 dark:from-indigo-950/40 dark:via-slate-900 dark:to-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 space-y-1">
                                <div class="flex items-center justify-between text-xs text-muted-foreground">
                                    <span>Total Invoice Amount</span>
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-indigo-600 dark:text-indigo-400">NGN Currency</span>
                                </div>
                                <div class="text-2xl font-black font-mono tracking-tight text-indigo-950 dark:text-indigo-100">
                                    {{ formatCurrency(form.amount) }}
                                </div>
                            </div>

                        </CardContent>
                    </Card>

                    <!-- Helpful Tip Banner -->
                    <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/50 dark:bg-amber-950/20 text-xs text-amber-900 dark:text-amber-300 flex items-start gap-3">
                        <Info class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                        <div class="space-y-0.5">
                            <p class="font-bold">Student Portal Visibility</p>
                            <p class="text-[11px] leading-relaxed text-amber-800 dark:text-amber-400">
                                Once created, this invoice will immediately reflect on the student's finance dashboard with payment instructions.
                            </p>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </AdminLayout>
</template>

