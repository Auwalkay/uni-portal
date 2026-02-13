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
        class="relative grid min-h-screen flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0 bg-background"
    >
        <div
            class="relative hidden h-full flex-col p-10 text-white lg:flex dark:border-r border-border"
        >
            <div class="absolute inset-0 bg-primary" />
            <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary/90 to-secondary/20 mix-blend-multiply" />
            <!-- University Bg Image -->
             <img
                src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=2670&auto=format&fit=crop"
                alt="University Campus"
                class="absolute inset-0 h-full w-full object-cover opacity-30 mix-blend-overlay"
            />
            
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-bold tracking-tight gap-2"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 backdrop-blur-sm">
                    <GraduationCap class="h-5 w-5 text-white" />
                </div>
                {{ name }}
            </Link>
            
            <div class="relative z-20 mt-auto">
                <blockquote class="space-y-2 rounded-xl bg-white/5 p-6 backdrop-blur-md border border-white/10 shadow-xl">
                    <p class="text-lg font-medium leading-relaxed">
                        &ldquo;Education is the passport to the future, for tomorrow belongs to those who prepare for it today.&rdquo;
                    </p>
                    <footer class="text-sm font-semibold text-white/80">Malcolm X</footer>
                </blockquote>
            </div>
        </div>
        <div class="lg:p-8 animate-fade-in-up">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
                
                <p class="px-8 text-center text-sm text-muted-foreground">
                    By clicking continue, you agree to our 
                    <a href="#" class="underline underline-offset-4 hover:text-primary transition-colors">
                        Terms of Service
                    </a> 
                    and 
                    <a href="#" class="underline underline-offset-4 hover:text-primary transition-colors">
                        Privacy Policy
                    </a>.
                </p>
            </div>
        </div>
    </div>
</template>
