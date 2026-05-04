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
        class="relative grid min-h-screen flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0 bg-background overflow-hidden"
    >
        <!-- Left Side: Image & Branding -->
        <div
            class="relative hidden h-full flex-col p-10 text-white lg:flex border-r border-border/50"
        >
            <div class="absolute inset-0 bg-primary/20 z-10" />
            <div class="absolute inset-0 bg-gradient-to-t from-primary/90 via-primary/40 to-transparent z-10" />
            
            <!-- University Bg Image -->
             <img
                src="/images/auth-bg.jpg"
                alt="University Students"
                class="absolute inset-0 h-full w-full object-cover transition-transform duration-10000 hover:scale-110"
            />
            
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-xl font-bold tracking-tight gap-3 transition-transform hover:scale-105 active:scale-95 w-fit"
            >
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-lg p-1.5 overflow-hidden group-hover:rotate-6 transition-transform">
                    <img src="/miu-logo.jpeg" alt="MIU Logo" class="h-full w-full object-contain" />
                </div>
                <div class="flex flex-col leading-none">
                    <span class="text-white drop-shadow-md">MIU Nigeria</span>
                    <span class="text-[10px] uppercase tracking-[0.2em] text-white/70 font-medium">Knowledge & Innovation</span>
                </div>
            </Link>
            
            <div class="relative z-20 mt-auto">
                <div class="animate-fade-in-up delay-300">
                    <blockquote class="space-y-3 rounded-2xl bg-white/10 p-8 backdrop-blur-xl border border-white/20 shadow-2xl relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 text-white/10 group-hover:text-white/20 transition-colors">
                            <GraduationCap class="w-24 h-24 rotate-12" />
                        </div>
                        <p class="text-xl font-medium leading-relaxed italic relative z-10 text-white drop-shadow-sm">
                            &ldquo;Empowering minds, fostering innovation, and shaping the leaders of tomorrow.&rdquo;
                        </p>
                        <footer class="flex items-center gap-2 text-sm font-semibold text-white/90 relative z-10">
                            <div class="h-px w-8 bg-white/50" />
                            Mewar International University
                        </footer>
                    </blockquote>
                </div>
            </div>
        </div>

        <!-- Right Side: Form -->
        <div class="lg:p-8 flex items-center justify-center min-h-screen">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-8 sm:w-[400px] animate-fade-in-up"
            >
                <div class="flex flex-col space-y-3 text-center">
                    <div class="lg:hidden flex justify-center mb-4">
                         <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 p-3">
                            <img src="/miu-logo.jpeg" alt="MIU Logo" class="h-full w-full object-contain" />
                        </div>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-foreground" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-base text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                
                <div class="bg-card p-2 rounded-3xl shadow-sm border border-border/50">
                    <slot />
                </div>
                
                <p class="px-8 text-center text-xs text-muted-foreground leading-relaxed">
                    By clicking continue, you agree to our 
                    <a href="#" class="font-medium text-foreground underline underline-offset-4 hover:text-primary transition-colors">
                        Terms of Service
                    </a> 
                    and 
                    <a href="#" class="font-medium text-foreground underline underline-offset-4 hover:text-primary transition-colors">
                        Privacy Policy
                    </a>.
                </p>
            </div>
        </div>
    </div>
</template>
