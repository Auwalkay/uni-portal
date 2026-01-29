<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r"
        >
            <div class="absolute inset-0 bg-primary" />
            <!-- University Bg Image (Placeholder) -->
             <img
                src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=2670&auto=format&fit=crop"
                alt="University Campus"
                class="absolute inset-0 h-full w-full object-cover opacity-40 mix-blend-overlay"
            />
            
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium font-serif tracking-tight"
            >
                <AppLogoIcon class="mr-2 size-8 fill-current text-white" />
                {{ name }}
            </Link>
            
            <div class="relative z-20 mt-auto">
                <blockquote class="space-y-2">
                    <p class="text-lg font-serif italic">
                        &ldquo;Education is the passport to the future, for tomorrow belongs to those who prepare for it today.&rdquo;
                    </p>
                    <footer class="text-sm">Malcolm X</footer>
                </blockquote>
            </div>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-2xl font-serif font-bold tracking-tight text-primary" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
                
                <p class="px-8 text-center text-sm text-muted-foreground">
                    By clicking continue, you agree to our 
                    <a href="#" class="underline underline-offset-4 hover:text-primary">
                        Terms of Service
                    </a> 
                    and 
                    <a href="#" class="underline underline-offset-4 hover:text-primary">
                        Privacy Policy
                    </a>.
                </p>
            </div>
        </div>
    </div>
</template>
