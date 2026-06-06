<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { GraduationCap } from 'lucide-vue-next';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative min-h-screen grid lg:grid-cols-2 bg-white dark:bg-slate-950 overflow-hidden"
    >
        <!-- Left Side: Professional Institutional Branding -->
        <div class="hidden lg:flex relative flex-col p-16 overflow-hidden">
            <!-- Dynamic Background Image -->
            <img
                src="/images/auth-bg.jpg"
                alt="University Campus"
                class="absolute inset-0 h-full w-full object-cover transition-transform duration-[20s] animate-slow-zoom"
            />
            
            <!-- Sophisticated Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900/90 via-slate-900/40 to-transparent z-10" />
            <div class="absolute inset-0 bg-primary/10 z-10 mix-blend-overlay" />
            
            <!-- Top Branding -->
            <div class="relative z-20">
                <Link :href="home()" class="inline-flex items-center gap-4 group">
                    <div class="h-14 w-14 bg-white rounded-2xl p-2.5 shadow-2xl shadow-black/20 group-hover:rotate-3 transition-transform duration-500">
                        <img src="/miu-logo.png" alt="MIU Logo" class="h-full w-full object-contain" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black text-white tracking-tighter uppercase leading-none">Mewar International University</span>
                        <span class="text-[10px] font-bold text-white/60 uppercase tracking-[0.4em] mt-1.5">Knowledge & Prosperity</span>
                    </div>
                </Link>
            </div>

            <!-- Bottom Content -->
            <div class="relative z-20 mt-auto">
                <div class="max-w-md space-y-8 animate-fade-in-up">
                    <div class="h-1.5 w-16 bg-primary rounded-full shadow-lg shadow-primary/50" />
                    <div class="space-y-4">
                        <h2 class="text-4xl font-black text-white leading-[1.1] tracking-tight drop-shadow-2xl">
                            Empowering the next <span class="text-primary italic font-medium">generation</span> of global leaders.
                        </h2>
                        <p class="text-lg text-slate-300 font-medium leading-relaxed max-w-sm">
                            Access your comprehensive academic ecosystem and administrative resources through our unified digital gateway.
                        </p>
                    </div>
                    
                    <div class="pt-8 flex items-center gap-6 text-white/40">
                         <div class="flex flex-col">
                            <span class="text-xl font-bold text-white">10+</span>
                            <span class="text-[10px] uppercase font-bold tracking-widest">Programs</span>
                         </div>
                         <div class="w-px h-8 bg-white/10" />
                         <div class="flex flex-col">
                            <span class="text-xl font-bold text-white">5k+</span>
                            <span class="text-[10px] uppercase font-bold tracking-widest">Students</span>
                         </div>
                         <div class="w-px h-8 bg-white/10" />
                         <div class="flex flex-col">
                            <span class="text-xl font-bold text-white">100%</span>
                            <span class="text-[10px] uppercase font-bold tracking-widest">Digital</span>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Clean Professional Form -->
        <div class="flex flex-col justify-center px-8 py-12 lg:px-24 xl:px-32 relative">
            <!-- Background Decoration for Right Side -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl -mr-32 -mt-32 opacity-50" />
            
            <div class="w-full max-w-[400px] mx-auto space-y-10 relative z-10">
                <!-- Mobile Only Logo -->
                <div class="lg:hidden flex flex-col items-center gap-6 mb-12">
                    <div class="h-20 w-20 bg-white rounded-3xl p-4 shadow-2xl border border-slate-100 animate-bounce-slow">
                        <img src="/miu-logo.png" alt="MIU Logo" class="h-full w-full object-contain" />
                    </div>
                    <div class="text-center">
                        <h1 class="text-3xl font-black tracking-tighter uppercase text-slate-900 dark:text-white">MIU Nigeria</h1>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Student & Staff Portal</p>
                    </div>
                </div>

                <!-- Header Content -->
                <div class="space-y-3 text-center lg:text-left">
                    <h1 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-lg" v-if="description">
                        {{ description }}
                    </p>
                </div>

                <!-- Form Area -->
                <div class="animate-fade-in-up delay-100">
                    <slot />
                </div>

                <!-- Global Footer Info -->
                <div class="pt-8 border-t border-slate-100 dark:border-slate-800">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <p>&copy; {{ new Date().getFullYear() }} MIU Nigeria</p>
                        <div class="flex items-center gap-4">
                            <a href="#" class="hover:text-primary transition-colors">Terms</a>
                            <span class="text-slate-200">|</span>
                            <a href="#" class="hover:text-primary transition-colors">Privacy</a>
                            <span class="text-slate-200">|</span>
                            <a href="mailto:support@miu.edu.ng" class="hover:text-primary transition-colors">Support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes slow-zoom {
    0% { transform: scale(1); }
    100% { transform: scale(1.15); }
}
.animate-slow-zoom {
    animation: slow-zoom 30s infinite alternate ease-in-out;
}
@keyframes fade-in-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fade-in-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.delay-100 { animation-delay: 0.1s; }
@keyframes bounce-slow {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-bounce-slow {
    animation: bounce-slow 4s infinite ease-in-out;
}
</style>
