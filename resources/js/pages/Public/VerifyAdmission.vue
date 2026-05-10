<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, XCircle, ShieldCheck, Mail, Building, User, School, Calendar } from 'lucide-vue-next';

defineProps<{
    type?: 'student' | 'applicant';
    record?: {
        name: string;
        identifier: string;
        program: string;
        faculty: string;
        session: string;
        status: string;
    };
    error?: string;
}>();

const currentYear = new Date().getFullYear();
</script>

<template>
    <Head title="Admission Verification | MIU Nigeria" />

    <div class="min-h-screen bg-slate-50 font-sans flex flex-col">
        <!-- Minimal Header -->
        <nav class="bg-white border-b border-slate-200 py-4">
            <div class="container mx-auto px-6 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3">
                    <img src="/miu-logo.png" alt="Logo" class="h-10 w-10 object-contain" />
                    <div class="flex flex-col">
                        <span class="text-lg font-black tracking-tighter uppercase text-slate-900">MIU Nigeria</span>
                        <span class="text-[9px] uppercase tracking-widest text-slate-500 font-bold">University Verification</span>
                    </div>
                </Link>
                <div class="hidden sm:block">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Official Document Verification System</span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow flex items-center justify-center p-6">
            <div class="max-w-xl w-full">
                
                <div v-if="record" class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-200">
                    <!-- Verification Badge -->
                    <div class="bg-emerald-500 p-8 text-white text-center space-y-3">
                        <div class="mx-auto w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-md">
                            <CheckCircle2 class="w-10 h-10" />
                        </div>
                        <h2 class="text-2xl font-black uppercase tracking-tight">Admission Verified</h2>
                        <p class="text-emerald-100 text-sm font-medium">This record has been officially confirmed by the Admissions Office.</p>
                    </div>

                    <div class="p-10 space-y-8">
                        <!-- Student Profile -->
                        <div class="flex items-center gap-6 pb-8 border-b border-slate-100">
                            <div class="h-20 w-20 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-300">
                                <User class="w-10 h-10" />
                            </div>
                            <div class="space-y-1">
                                <p class="text-2xl font-black text-slate-900 leading-tight">{{ record.name }}</p>
                                <p class="text-sm font-mono text-slate-500 uppercase tracking-widest">{{ record.identifier }}</p>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid sm:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Programme</p>
                                    <div class="flex items-center gap-2 text-slate-700 font-bold">
                                        <Building class="w-4 h-4 text-slate-400" /> {{ record.program }}
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Faculty</p>
                                    <div class="flex items-center gap-2 text-slate-700 font-bold text-sm">
                                        <School class="w-4 h-4 text-slate-400" /> {{ record.faculty }}
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Academic Session</p>
                                    <div class="flex items-center gap-2 text-slate-700 font-bold">
                                        <Calendar class="w-4 h-4 text-slate-400" /> {{ record.session }} Session
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Admission Status</p>
                                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-[10px] font-black uppercase tracking-widest">
                                        {{ record.status }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Safety Note -->
                        <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 space-y-3">
                            <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                                <ShieldCheck class="w-4 h-4 text-emerald-500" /> Security Guarantee
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                This digital verification confirms that the admission letter with reference <strong>{{ record.identifier }}</strong> was issued by Mewar International University Nigeria. For further enquiries, contact <strong>admission@miu-nigeria.edu.ng</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Error State -->
                <div v-else class="bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-200">
                    <div class="bg-rose-500 p-8 text-white text-center space-y-3">
                        <div class="mx-auto w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-md">
                            <XCircle class="w-10 h-10" />
                        </div>
                        <h2 class="text-2xl font-black uppercase tracking-tight">Verification Failed</h2>
                        <p class="text-rose-100 text-sm font-medium">We could not find any valid admission record matching this identifier.</p>
                    </div>
                    <div class="p-10 text-center space-y-6">
                        <p class="text-slate-600">
                            The identifier provided might be incorrect, or the record does not exist in our database. Please ensure you scanned the correct QR code or contact the Admissions Office.
                        </p>
                        <Button as-child class="w-full h-12 rounded-2xl bg-slate-900 text-white font-bold uppercase tracking-widest">
                            <Link href="/">Return to Home</Link>
                        </Button>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="mt-8 text-center text-slate-400">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em]">
                        &copy; {{ currentYear }} MIU Nigeria University. All Rights Reserved.
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
