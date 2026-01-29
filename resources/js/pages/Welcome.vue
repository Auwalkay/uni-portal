<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    BookOpen, 
    GraduationCap, 
    Users, 
    Trophy, 
    ArrowRight, 
    CheckCircle,
    Globe,
    Shield
} from 'lucide-vue-next';
import { dashboard, login, register } from '@/routes';

defineProps<{
    canRegister?: boolean;
}>();

const currentYear = new Date().getFullYear();
</script>

<template>
    <Head title="Welcome to University Portal" />

    <div class="min-h-screen bg-background text-foreground font-sans selection:bg-secondary selection:text-secondary-foreground">
        
        <!-- Navigation -->
        <nav class="fixed top-0 z-50 w-full border-b border-white/10 bg-primary/95 backdrop-blur supports-[backdrop-filter]:bg-primary/60">
            <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-secondary text-secondary-foreground">
                        <GraduationCap class="h-5 w-5" />
                    </div>
                    <span class="text-lg font-bold tracking-tight text-white">UniPortal</span>
                </div>

                <div class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link :href="dashboard()" class="text-sm font-medium text-white hover:text-secondary transition-colors">
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()" class="text-sm font-medium text-white/80 hover:text-white transition-colors">
                            Log in
                        </Link>
                        <Link 
                            v-if="canRegister" 
                            :href="register()" 
                            class="rounded-md bg-secondary px-4 py-2 text-sm font-semibold text-secondary-foreground shadow-sm hover:bg-secondary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary transition-all"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main>
            <div class="relative isolate overflow-hidden bg-primary pt-14 pb-16 sm:pb-20">
                <img 
                    src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" 
                    alt="University Campus" 
                    class="absolute inset-0 -z-10 h-full w-full object-cover opacity-20"
                />
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-primary via-primary/80 to-transparent"></div>
                
                <div class="mx-auto max-w-7xl px-6 lg:px-8 pt-24 sm:pt-32">
                    <div class="mx-auto max-w-2xl text-center">
                        <div class="mb-6 flex justify-center">
                            <span class="rounded-full bg-secondary/10 px-3 py-1 text-sm font-semibold leading-6 text-secondary ring-1 ring-inset ring-secondary/20">
                                Admissions Open for {{ currentYear }}
                            </span>
                        </div>
                        <h1 class="text-4xl font-bold tracking-tight text-white sm:text-6xl">
                            Empowering Future Leaders Through Excellence
                        </h1>
                        <p class="mt-6 text-lg leading-8 text-gray-300">
                            Join a community dedicated to academic innovation, research, and holistic development. accurate records, seamless processes, and a world-class education await.
                        </p>
                        <div class="mt-10 flex items-center justify-center gap-x-6">
                            <Link 
                                v-if="canRegister"
                                :href="register()" 
                                class="rounded-md bg-secondary px-5 py-3 text-sm font-semibold text-secondary-foreground shadow-sm hover:bg-secondary/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary transition-all flex items-center gap-2"
                            >
                                Apply Now <ArrowRight class="h-4 w-4" />
                            </Link>
                            <a href="#programs" class="text-sm font-semibold leading-6 text-white hover:text-secondary transition-colors">
                                Explore Programs <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="bg-muted/30 py-24 sm:py-32" id="programs">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-base font-semibold leading-7 text-primary">Why Choose Us?</h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">Everything you need to succeed</p>
                        <p class="mt-6 text-lg leading-8 text-muted-foreground">
                            Our portal provides a seamless experience for students, staff, and applicants.
                        </p>
                    </div>
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                            <div class="flex flex-col rounded-2xl bg-card p-8 shadow-sm ring-1 ring-border transition-all hover:shadow-md">
                                <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-foreground">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-lg bg-primary/10">
                                        <BookOpen class="h-6 w-6 text-primary" />
                                    </div>
                                    World-Class Academics
                                </dt>
                                <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-muted-foreground">
                                    <p class="flex-auto">Access diverse programmes and courses designed to equip you with modern skills and knowledge.</p>
                                </dd>
                            </div>
                            <div class="flex flex-col rounded-2xl bg-card p-8 shadow-sm ring-1 ring-border transition-all hover:shadow-md">
                                <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-foreground">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-lg bg-primary/10">
                                        <Globe class="h-6 w-6 text-primary" />
                                    </div>
                                    Global Community
                                </dt>
                                <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-muted-foreground">
                                    <p class="flex-auto">Connect with a diverse student body and faculty from around the world.</p>
                                </dd>
                            </div>
                            <div class="flex flex-col rounded-2xl bg-card p-8 shadow-sm ring-1 ring-border transition-all hover:shadow-md">
                                <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-foreground">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-lg bg-primary/10">
                                        <Shield class="h-6 w-6 text-primary" />
                                    </div>
                                    Secure & Efficient
                                </dt>
                                <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-muted-foreground">
                                    <p class="flex-auto">Your data is safe with our secure portal. Enjoy fast processing for admissions and results.</p>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="bg-primary py-24 sm:py-32">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-16 text-center lg:grid-cols-4">
                        <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                            <dt class="text-base leading-7 text-gray-400">Students Enrolled</dt>
                            <dd class="order-first text-3xl font-semibold tracking-tight text-white sm:text-5xl">15,000+</dd>
                        </div>
                        <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                            <dt class="text-base leading-7 text-gray-400">Academic Programs</dt>
                            <dd class="order-first text-3xl font-semibold tracking-tight text-white sm:text-5xl">120+</dd>
                        </div>
                        <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                            <dt class="text-base leading-7 text-gray-400">Expert Faculty</dt>
                            <dd class="order-first text-3xl font-semibold tracking-tight text-white sm:text-5xl">850+</dd>
                        </div>
                        <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                            <dt class="text-base leading-7 text-gray-400">Years of Excellence</dt>
                            <dd class="order-first text-3xl font-semibold tracking-tight text-white sm:text-5xl">50+</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-white">
                <div class="px-6 py-24 sm:px-6 sm:py-32 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                            Ready to start your journey?
                            <br />
                            Apply for admission today.
                        </h2>
                        <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-gray-600">
                            Take the first step towards a bright future. Our application process is simple and completely online.
                        </p>
                        <div class="mt-10 flex items-center justify-center gap-x-6">
                            <Link 
                                v-if="canRegister"
                                :href="register()" 
                                class="rounded-md bg-primary px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-all"
                            >
                                Get Started
                            </Link>
                            <Link :href="login()" class="text-sm font-semibold leading-6 text-gray-900">
                                Log in <span aria-hidden="true">→</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-primary text-gray-300" aria-labelledby="footer-heading">
            <h2 id="footer-heading" class="sr-only">Footer</h2>
            <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8 lg:py-16">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <GraduationCap class="h-6 w-6 text-secondary" />
                            <span class="text-xl font-bold text-white">UniPortal</span>
                        </div>
                        <p class="text-sm leading-6">
                            Constructing the path to knowledge and success for the next generation of leaders.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-white">Quick Links</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li><a href="#" class="text-sm leading-6 hover:text-white">About Us</a></li>
                            <li><a href="#" class="text-sm leading-6 hover:text-white">Admissions</a></li>
                            <li><a href="#" class="text-sm leading-6 hover:text-white">Academics</a></li>
                            <li><a href="#" class="text-sm leading-6 hover:text-white">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-white">Contact</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li class="text-sm leading-6">123 University Ave, Education City</li>
                            <li class="text-sm leading-6">contact@uniportal.edu</li>
                            <li class="text-sm leading-6">+1 (555) 123-4567</li>
                        </ul>
                    </div>
                </div>
                <div class="mt-12 border-t border-white/10 pt-8 sm:mt-16 lg:mt-24">
                    <p class="text-xs leading-5">&copy; {{ currentYear }} University Portal. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
