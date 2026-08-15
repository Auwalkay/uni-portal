<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { 
    BookOpen, 
    User, 
    CreditCard, 
    Home, 
    IdCard, 
    Activity, 
    LifeBuoy, 
    Megaphone, 
    Search,
    ChevronRight,
    Sparkles,
    Download,
    CheckCircle2,
    HelpCircle,
    Printer,
    FileText,
    ArrowRight
} from 'lucide-vue-next';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/student/dashboard' },
    { title: 'Smart User Manual', href: '/student/manual' },
];

const searchQuery = ref('');
const activeTab = ref('guide'); // 'guide' | 'full'
const selectedTopicId = ref('profile');

// Interactive checklist
const journeySteps = ref([
    { label: 'Complete Profile Details', done: false, desc: 'Enter bio-data & upload white background passport photo', linkId: 'profile' },
    { label: 'Generate & Pay Fees', done: false, desc: 'Settle tuition invoice or select split-payment installment', linkId: 'payments' },
    { label: 'Register Course Units', done: false, desc: 'Select semester courses and submit units for approval', linkId: 'courses' },
    { label: 'Get Exam Card & ID', done: false, desc: 'Print examination slip and student identity card', linkId: 'documents' }
]);

const toggleStep = (index: number) => {
    journeySteps.value[index].done = !journeySteps.value[index].done;
};

// Smart Troubleshooter Questions
const faqList = [
    {
        q: 'Why is my Course Registration page locked?',
        a: 'Course registration is automatically locked until you complete your Profile Bio-Data (including passport photograph upload). Go to the "My Profile" section to complete the details.'
    },
    {
        q: 'Can I pay my school fees in installments?',
        a: 'Yes! When you click "Pay Now" on your generated invoice inside the "Payments" module, you can choose to make a Split Payment. Select the installment percentage and pay the partial fee.'
    },
    {
        q: 'Where do I present my hostel booking slip?',
        a: 'Once your hostel room fee invoice is paid on the portal, print the booking receipt and submit it to the Hall Warden at the block gate to collect your keys.'
    },
    {
        q: 'My exam card shows units are pending approval?',
        a: 'After submitting course registration, your department head (HOD) must verify and approve the units. Contact your advisor if approval takes more than 48 hours.'
    }
];

const manualSections = [
    {
        id: 'profile',
        title: 'Profile & Bio-Data',
        icon: User,
        color: 'text-blue-500 bg-blue-50 dark:bg-blue-950/20',
        content: `
            <p class="leading-relaxed">Before accessing portal features like course registration or payments, you must complete your bio-data profile.</p>
            <h4 class="font-bold text-sm text-foreground mt-4 mb-2">Steps to Complete Profile:</h4>
            <ul class="list-decimal pl-5 space-y-2 text-xs md:text-sm text-muted-foreground">
                <li>Go to the <strong class="text-foreground">My Profile</strong> tab on the sidebar.</li>
                <li>Enter all personal details: <em class="text-foreground">Date of Birth, Gender, Nationality, State of Origin, and LGA</em>.</li>
                <li>Upload a high-quality passport photograph. The photo must be a formal portrait with a clean white background (JPEG/PNG format, maximum 500KB size).</li>
                <li>Click <strong class="text-foreground">Save Profile</strong> at the bottom of the page to apply changes.</li>
            </ul>
            <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-400 dark:bg-blue-950/20 dark:border-blue-500 rounded text-xs text-blue-950 dark:text-blue-200 font-medium">
                <strong>Pro-Tip:</strong> Once your profile data is complete, the status widget on your main dashboard will update automatically.
            </div>
        `
    },
    {
        id: 'announcements',
        title: 'Notices & Bulletins',
        icon: Megaphone,
        color: 'text-amber-500 bg-amber-50 dark:bg-amber-950/20',
        content: `
            <p class="leading-relaxed">Official university communications, bulletins, and event alerts are published on the announcements board.</p>
            <h4 class="font-bold text-sm text-foreground mt-4 mb-2">Where to Find Notices:</h4>
            <ul class="list-disc pl-5 space-y-2 text-xs md:text-sm text-muted-foreground">
                <li><strong class="text-foreground">Dashboard Banner:</strong> Critical pinned notices appear at the very top of your dashboard in an amber alert container.</li>
                <li><strong class="text-foreground">Announcements Page:</strong> Click <em class="text-foreground">Announcements</em> on the sidebar to view historical logs.</li>
                <li><strong class="text-foreground">Scanned Document Attachments:</strong> Official scanned memos can be downloaded and viewed directly using the attached link badge on the notice.</li>
            </ul>
        `
    },
    {
        id: 'courses',
        title: 'Course Registration',
        icon: BookOpen,
        color: 'text-indigo-500 bg-indigo-50 dark:bg-indigo-950/20',
        content: `
            <p class="leading-relaxed">Course registration must be completed at the start of each academic session to gain entry into lecture halls and examination venues.</p>
            <h4 class="font-bold text-sm text-foreground mt-4 mb-2">How to Register:</h4>
            <ul class="list-decimal pl-5 space-y-2 text-xs md:text-sm text-muted-foreground">
                <li>Go to <strong class="text-foreground">Course Registration</strong> in the sidebar.</li>
                <li>Select the current academic semester courses.</li>
                <li>Ensure the total unit count satisfies your department’s minimum and maximum constraints.</li>
                <li>Review the list and click <strong class="text-foreground">Submit Registration</strong>.</li>
                <li>Download your generated registration slip and <strong class="text-foreground">Exam Card</strong> to print.</li>
            </ul>
        `
    },
    {
        id: 'payments',
        title: 'Fees & Invoices',
        icon: CreditCard,
        color: 'text-emerald-500 bg-emerald-50 dark:bg-emerald-950/20',
        content: `
            <p class="leading-relaxed">Manage your tuition invoices, generate dynamic transaction slips, and complete online bank queries.</p>
            <h4 class="font-bold text-sm text-foreground mt-4 mb-2">Paying Your Invoice:</h4>
            <ul class="list-disc pl-5 space-y-2 text-xs md:text-sm text-muted-foreground">
                <li>Go to <strong class="text-foreground">Payments</strong> on the sidebar to view active invoices.</li>
                <li>Click <strong class="text-foreground">Generate Invoice</strong> for the current academic session.</li>
                <li><strong class="text-foreground">Split/Installment Payments:</strong> Choose whether you want to pay the full tuition amount or pay a part-payment installment.</li>
                <li>Click <strong class="text-foreground">Pay Now</strong> to proceed to the secure transaction portal.</li>
                <li>Download your PDF payment receipt immediately after validation.</li>
            </ul>
            <div class="mt-4 p-4 bg-emerald-50 border-l-4 border-emerald-400 dark:bg-emerald-950/20 dark:border-emerald-500 rounded text-xs text-emerald-950 dark:text-emerald-200 font-medium">
                <strong>Attention:</strong> Academic promotion can be blocked if outstanding balances remain unpaid at the end of the session.
            </div>
        `
    },
    {
        id: 'accommodation',
        title: 'Hostel Booking',
        icon: Home,
        color: 'text-purple-500 bg-purple-50 dark:bg-purple-950/20',
        content: `
            <p class="leading-relaxed">Bed spaces are allocated each semester. Due to limited spaces, early reservation is highly recommended.</p>
            <h4 class="font-bold text-sm text-foreground mt-4 mb-2">Booking a Bed Space:</h4>
            <ul class="list-decimal pl-5 space-y-2 text-xs md:text-sm text-muted-foreground">
                <li>Navigate to <strong class="text-foreground">Accommodation</strong> on the sidebar.</li>
                <li>Choose a hostel block and room category.</li>
                <li>Click <strong class="text-foreground">Book Bed Space</strong>.</li>
                <li>Complete the required invoice payment to lock your room allocation.</li>
                <li>Present your printed hostel accommodation slip to the Hall Warden for key release.</li>
            </ul>
        `
    },
    {
        id: 'documents',
        title: 'ID Cards & Letters',
        icon: IdCard,
        color: 'text-rose-500 bg-rose-50 dark:bg-rose-950/20',
        content: `
            <p class="leading-relaxed">Print formal credentials and admission documents directly from your portal account.</p>
            <h4 class="font-bold text-sm text-foreground mt-4 mb-2">Available Documents:</h4>
            <ul class="list-disc pl-5 space-y-2 text-xs md:text-sm text-muted-foreground">
                <li><strong class="text-foreground">Student Identity Card:</strong> Generated automatically once you clear your tuition fees and complete passport uploads. Press print on the card preview to download it.</li>
                <li><strong class="text-foreground">Admission Letter:</strong> Available to download in PDF format directly from your quick actions dashboard panel.</li>
            </ul>
        `
    },
    {
        id: 'library',
        title: 'Clinic & Library Logs',
        icon: Activity,
        color: 'text-teal-500 bg-teal-50 dark:bg-teal-950/20',
        content: `
            <p class="leading-relaxed">Keep track of your physical health check-ins and academic reading resources.</p>
            <h4 class="font-bold text-sm text-foreground mt-4 mb-2">Features:</h4>
            <ul class="list-disc pl-5 space-y-2 text-xs md:text-sm text-muted-foreground">
                <li><strong class="text-foreground">Library Catalog:</strong> Query available physical textbooks, request borrow loans, and download ebooks.</li>
                <li><strong class="text-foreground">Sickbay Logs:</strong> View clinic records, logged vitals, nurse consultation comments, and active medical prescriptions.</li>
            </ul>
        `
    },
    {
        id: 'support',
        title: 'Support Helpdesk',
        icon: LifeBuoy,
        color: 'text-cyan-500 bg-cyan-50 dark:bg-cyan-950/20',
        content: `
            <p class="leading-relaxed">File complaints and request technical assistance directly from portal administrators.</p>
            <h4 class="font-bold text-sm text-foreground mt-4 mb-2">Submitting Tickets:</h4>
            <ul class="list-decimal pl-5 space-y-2 text-xs md:text-sm text-muted-foreground">
                <li>Navigate to <strong class="text-foreground">Support Tickets</strong> in the sidebar.</li>
                <li>Click <strong class="text-foreground">Create Ticket</strong>, input the details, choose the category, and submit.</li>
                <li>Admin responses will be visible in the thread list; you can write direct comments to updates.</li>
            </ul>
        `
    }
];

const selectedSection = computed(() => {
    return manualSections.find(s => s.id === selectedTopicId.value) || manualSections[0];
});

const filteredSections = computed(() => {
    if (!searchQuery.value.trim()) return manualSections;
    const query = searchQuery.value.toLowerCase();
    return manualSections.filter(s => 
        s.title.toLowerCase().includes(query) || 
        s.content.toLowerCase().includes(query)
    );
});

// Helper to highlight searched terms in contents
const highlightText = (text: string) => {
    if (!searchQuery.value.trim()) return text;
    const regex = new RegExp(`(${searchQuery.value.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<mark class="bg-amber-100 text-amber-900 rounded px-0.5">$1</mark>');
};

const handlePrint = () => {
    window.print();
};

const selectTopic = (id: string) => {
    selectedTopicId.value = id;
    activeTab.value = 'guide'; // Auto switch to focus view
};
</script>

<template>
    <Head title="Smart Student Manual" />

    <StudentLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-6xl mx-auto print:p-0">
            <!-- Header Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-800 to-indigo-900 p-8 text-white shadow-lg print:hidden">
                <div class="absolute right-0 top-0 h-full w-1/3 bg-white/5 backdrop-blur-3xl -mr-20 transform skew-x-12"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <span class="bg-white/10 text-indigo-200 border-white/10 border text-[10px] uppercase font-bold tracking-widest px-3 py-1 rounded-full mb-3 inline-block">
                            Student Journey & Portal Resource
                        </span>
                        <h1 class="text-3xl font-extrabold tracking-tight mt-1">Smart User Manual</h1>
                        <p class="text-indigo-100 text-sm max-w-2xl mt-2 font-medium">
                            An interactive guide designed to help you solve registration, accommodation, and fee payment queries instantly.
                        </p>
                    </div>
                    <Button @click="handlePrint" variant="secondary" class="bg-white/15 hover:bg-white/25 text-white border-0 gap-2 shrink-0">
                        <Printer class="w-4 h-4" /> Print Manual
                    </Button>
                </div>
            </div>

            <!-- Smart Getting Started Journey Tracker -->
            <div class="grid md:grid-cols-4 gap-4 print:hidden">
                <Card 
                    v-for="(step, index) in journeySteps" 
                    :key="index"
                    class="border shadow-sm transition-all relative overflow-hidden"
                    :class="step.done ? 'bg-slate-50 dark:bg-slate-900/50' : 'bg-white dark:bg-slate-950'"
                >
                    <CardHeader class="pb-2 pt-4 px-4 flex flex-row items-start justify-between space-y-0">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">STEP 0{{ index + 1 }}</span>
                        <button @click="toggleStep(index)" class="focus:outline-none">
                            <CheckCircle2 
                                class="w-5 h-5 transition-colors" 
                                :class="step.done ? 'text-emerald-500 fill-emerald-500/10' : 'text-slate-300 dark:text-slate-800 hover:text-emerald-500'" 
                            />
                        </button>
                    </CardHeader>
                    <CardContent class="pb-4 px-4">
                        <h3 
                            @click="selectTopic(step.linkId)"
                            class="font-bold text-sm text-indigo-700 dark:text-indigo-400 hover:underline cursor-pointer flex items-center gap-1"
                        >
                            {{ step.label }}
                        </h3>
                        <p class="text-xs text-muted-foreground mt-1 line-clamp-2 leading-relaxed">
                            {{ step.desc }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- View Modes Tabs -->
            <div class="flex items-center justify-between border-b pb-1 print:hidden">
                <div class="flex gap-2">
                    <button 
                        @click="activeTab = 'guide'"
                        class="px-4 py-2 text-xs font-bold border-b-2 transition-all"
                        :class="activeTab === 'guide' ? 'border-indigo-600 text-indigo-700 dark:text-indigo-400' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    >
                        Focused Smart Guide
                    </button>
                    <button 
                        @click="activeTab = 'full'"
                        class="px-4 py-2 text-xs font-bold border-b-2 transition-all"
                        :class="activeTab === 'full' ? 'border-indigo-600 text-indigo-700 dark:text-indigo-400' : 'border-transparent text-muted-foreground hover:text-foreground'"
                    >
                        View Full Document ({{ manualSections.length }} Chapters)
                    </button>
                </div>
            </div>

            <!-- Main Layout (Smart Interactive Guide Mode) -->
            <div v-if="activeTab === 'guide'" class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start print:hidden">
                <!-- Navigation Drawer -->
                <div class="lg:col-span-1 space-y-4">
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-3 border-b">
                            <CardTitle class="text-xs font-bold flex items-center gap-2">
                                <Search class="w-4 h-4 text-muted-foreground" /> Topic Finder
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-4 px-3">
                            <Input 
                                v-model="searchQuery" 
                                placeholder="Filter guide topics..." 
                                class="h-9 w-full text-xs" 
                            />
                        </CardContent>
                    </Card>

                    <Card class="border shadow-sm">
                        <CardHeader class="pb-3 border-b">
                            <CardTitle class="text-xs font-bold">Manual Topics</CardTitle>
                        </CardHeader>
                        <CardContent class="p-2">
                            <nav class="space-y-1">
                                <button 
                                    v-for="s in manualSections" 
                                    :key="s.id"
                                    @click="selectedTopicId = s.id"
                                    class="w-full text-left px-3 py-2.5 rounded-md text-xs font-semibold flex items-center justify-between transition-colors"
                                    :class="selectedTopicId === s.id ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300' : 'text-muted-foreground hover:bg-slate-50 dark:hover:bg-slate-900 hover:text-foreground'"
                                >
                                    <span class="flex items-center gap-2">
                                        <component :is="s.icon" class="w-4 h-4" />
                                        {{ s.title }}
                                    </span>
                                    <ChevronRight class="w-3.5 h-3.5 opacity-60" />
                                </button>
                            </nav>
                        </CardContent>
                    </Card>
                </div>

                <!-- Focused Card Viewer -->
                <div class="lg:col-span-3">
                    <Card class="border shadow-sm">
                        <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-xl" :class="selectedSection.color">
                                    <component :is="selectedSection.icon" class="w-5 h-5" />
                                </div>
                                <div>
                                    <CardTitle class="text-base font-bold">{{ selectedSection.title }}</CardTitle>
                                    <span class="text-[10px] text-muted-foreground font-semibold uppercase tracking-wider">Chapter: {{ selectedSection.id }}</span>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="p-6 text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-medium" v-html="selectedSection.content">
                        </CardContent>
                    </Card>

                    <!-- Smart Troubleshooter Panel -->
                    <Card class="border shadow-sm mt-6 overflow-hidden">
                        <CardHeader class="bg-indigo-50/20 dark:bg-indigo-950/10 border-b">
                            <CardTitle class="text-sm font-bold flex items-center gap-2">
                                <HelpCircle class="w-4.5 h-4.5 text-indigo-500" /> FAQ & Smart Troubleshooter
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 divide-y space-y-4">
                            <div v-for="(faq, i) in faqList" :key="i" class="pt-4 first:pt-0">
                                <h4 class="font-bold text-xs md:text-sm text-indigo-700 dark:text-indigo-400 flex items-start gap-1">
                                    <Sparkles class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                                    {{ faq.q }}
                                </h4>
                                <p class="text-xs text-muted-foreground mt-1.5 pl-5 leading-relaxed font-medium">
                                    {{ faq.a }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Full Document Printable Scroll Mode -->
            <div v-else class="space-y-6">
                <!-- Search filter for full scroll view -->
                <div class="relative w-full max-w-md print:hidden">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        placeholder="Live filter all chapters..." 
                        class="pl-10 h-10 w-full" 
                    />
                </div>

                <div v-if="filteredSections.length === 0" class="text-center py-12 bg-white dark:bg-slate-950 border rounded-xl">
                    <h3 class="text-base font-bold text-foreground">No matching chapters found</h3>
                    <p class="text-xs text-muted-foreground mt-1">Try another search keyword.</p>
                </div>

                <Card 
                    v-for="s in filteredSections" 
                    :key="s.id" 
                    :id="s.id" 
                    class="border shadow-sm transition-all hover:shadow-md print:shadow-none print:border-none print:break-inside-avoid"
                >
                    <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 pb-4 print:bg-transparent print:border-b-2">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg print:hidden" :class="s.color">
                                <component :is="s.icon" class="w-5 h-5" />
                            </div>
                            <div>
                                <CardTitle class="text-base font-bold text-slate-900 dark:text-white">{{ s.title }}</CardTitle>
                                <span class="text-[10px] text-muted-foreground font-semibold uppercase tracking-wider">Chapter: {{ s.id }}</span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-6 text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-medium" v-html="highlightText(s.content)">
                    </CardContent>
                </Card>
            </div>
        </div>
    </StudentLayout>
</template>

<style scoped>
@media print {
    .print\:hidden {
        display: none !important;
    }
}
</style>
