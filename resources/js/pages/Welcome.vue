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
    Shield,
    Sparkles,
    Clock,
    Search
} from 'lucide-vue-next';
import { dashboard, login, register } from '@/routes';

defineProps<{
    canRegister?: boolean;
}>();

const currentYear = new Date().getFullYear();
</script>

<template>
    <Head title="Welcome to University Portal" />

    <div class="min-h-screen bg-background text-foreground font-sans selection:bg-primary selection:text-primary-foreground">
        
        <!-- Navigation -->
        <nav class="fixed top-0 z-50 w-full border-b border-border/40 bg-background/80 backdrop-blur-md supports-[backdrop-filter]:bg-background/60">
            <div class="container mx-auto flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-lg shadow-primary/20">
                        <GraduationCap class="h-5 w-5" />
                    </div>
                    <span class="text-xl font-bold tracking-tight text-foreground">UniPortal</span>
                </div>

                <div class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link :href="dashboard()" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()" class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors">
                            Log in
                        </Link>
                        <Link 
                            v-if="canRegister" 
                            :href="register()" 
                            class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-md hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-all duration-200"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main>
            <div class="relative isolate overflow-hidden pt-14">
                <!-- Background effects -->
                <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
                    <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-primary to-secondary opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
                </div>

                <div class="mx-auto max-w-7xl px-6 lg:px-8 pt-24 pb-16 sm:pt-32 sm:pb-24">
                    <div class="mx-auto max-w-2xl text-center">
                        <div class="mb-8 flex justify-center animate-fade-in-up">
                            <span class="inline-flex items-center rounded-full bg-secondary/10 px-3 py-1 text-sm font-medium text-secondary ring-1 ring-inset ring-secondary/20">
                                <Sparkles class="mr-1.5 h-3.5 w-3.5" />
                                Admissions Open for {{ currentYear }}
                            </span>
                        </div>
                        <h1 class="text-5xl font-bold tracking-tight text-foreground sm:text-7xl bg-clip-text text-transparent bg-gradient-to-r from-primary via-primary/80 to-secondary animate-fade-in-up delay-100">
                            Empowering Future Leaders
                        </h1>
                        <p class="mt-6 text-lg leading-8 text-muted-foreground animate-fade-in-up delay-200">
                            Join a community dedicated to academic innovation, research, and holistic development. Experience world-class education with seamless digital integration.
                        </p>
                        <div class="mt-10 flex items-center justify-center gap-x-6 animate-fade-in-up delay-300">
                            <Link 
                                v-if="canRegister"
                                :href="register()" 
                                class="rounded-lg bg-primary px-6 py-3.5 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/25 hover:bg-primary/90 hover:scale-105 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-all duration-200 flex items-center gap-2"
                            >
                                Apply Now <ArrowRight class="h-4 w-4" />
                            </Link>
                            <a href="#programs" class="text-sm font-semibold leading-6 text-foreground hover:text-primary transition-colors flex items-center gap-1 group">
                                Explore Programs <ArrowRight class="h-3 w-3 transition-transform group-hover:translate-x-1" />
                            </a>
                        </div>
                    </div>
                </div>

                <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
                    <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="py-24 sm:py-32 bg-muted/20" id="programs">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-base font-semibold leading-7 text-secondary">Why Choose Us?</h2>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">Everything you need to succeed</p>
                        <p class="mt-6 text-lg leading-8 text-muted-foreground">
                            Our platform merges traditional academic excellence with modern digital efficiency.
                        </p>
                    </div>
                    <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                        <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
                            <div class="flex flex-col rounded-2xl bg-card p-8 shadow-sm ring-1 ring-border transition-all hover:shadow-lg hover:-translate-y-1 hover:ring-primary/20 group">
                                <dt class="flex items-center gap-x-3 text-lg font-semibold leading-7 text-card-foreground">
                                    <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-primary/10 group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                                        <BookOpen class="h-6 w-6 text-primary group-hover:text-current transition-colors" />
                                    </div>
                                    World-Class Academics
                                </dt>
                                <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-muted-foreground">
                                    <p class="flex-auto">Access diverse programmes and courses designed to equip you with modern skills and knowledge.</p>
                                </dd>
                            </div>
                            <div class="flex flex-col rounded-2xl bg-card p-8 shadow-sm ring-1 ring-border transition-all hover:shadow-lg hover:-translate-y-1 hover:ring-primary/20 group">
                                <dt class="flex items-center gap-x-3 text-lg font-semibold leading-7 text-card-foreground">
                                    <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-primary/10 group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                                        <Globe class="h-6 w-6 text-primary group-hover:text-current transition-colors" />
                                    </div>
                                    Global Community
                                </dt>
                                <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-muted-foreground">
                                    <p class="flex-auto">Connect with a diverse student body and faculty from around the world in a collaborative environment.</p>
                                </dd>
                            </div>
                            <div class="flex flex-col rounded-2xl bg-card p-8 shadow-sm ring-1 ring-border transition-all hover:shadow-lg hover:-translate-y-1 hover:ring-primary/20 group">
                                <dt class="flex items-center gap-x-3 text-lg font-semibold leading-7 text-card-foreground">
                                    <div class="h-12 w-12 flex items-center justify-center rounded-xl bg-primary/10 group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                                        <Shield class="h-6 w-6 text-primary group-hover:text-current transition-colors" />
                                    </div>
                                    Secure & Efficient
                                </dt>
                                <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-muted-foreground">
                                    <p class="flex-auto">State-of-the-art security for your records and streamlined processes for all administrative tasks.</p>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="relative py-24 sm:py-32 overflow-hidden">
                <div class="absolute inset-0 bg-primary -z-20"></div>
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80')] bg-cover bg-center opacity-10 -z-10 mix-blend-overlay"></div>
                
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <dl class="grid grid-cols-1 gap-x-8 gap-y-16 text-center lg:grid-cols-4">
                        <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                            <dt class="text-base leading-7 text-primary-foreground/80">Students Enrolled</dt>
                            <dd class="order-first text-4xl font-bold tracking-tight text-white sm:text-5xl">15,000+</dd>
                        </div>
                        <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                            <dt class="text-base leading-7 text-primary-foreground/80">Academic Programs</dt>
                            <dd class="order-first text-4xl font-bold tracking-tight text-white sm:text-5xl">120+</dd>
                        </div>
                        <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                            <dt class="text-base leading-7 text-primary-foreground/80">Expert Faculty</dt>
                            <dd class="order-first text-4xl font-bold tracking-tight text-white sm:text-5xl">850+</dd>
                        </div>
                        <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                            <dt class="text-base leading-7 text-primary-foreground/80">Years of Excellence</dt>
                            <dd class="order-first text-4xl font-bold tracking-tight text-white sm:text-5xl">50+</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-background">
                <div class="px-6 py-24 sm:px-6 sm:py-32 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                            Ready to start your journey?
                            <br />
                            <span class="text-primary">Apply for admission today.</span>
                        </h2>
                        <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-muted-foreground">
                            Take the first step towards a bright future. Our application process is simple, secure, and completely online.
                        </p>
                        <div class="mt-10 flex items-center justify-center gap-x-6">
                            <Link 
                                v-if="canRegister"
                                :href="register()" 
                                class="rounded-lg bg-primary px-6 py-3.5 text-sm font-semibold text-primary-foreground shadow-lg shadow-primary/20 hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-all"
                            >
                                Get Started
                            </Link>
                            <Link :href="login()" class="text-sm font-semibold leading-6 text-foreground hover:text-primary transition-colors flex items-center gap-1">
                                Log in <span aria-hidden="true">→</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-card text-muted-foreground border-t border-border" aria-labelledby="footer-heading">
            <h2 id="footer-heading" class="sr-only">Footer</h2>
            <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8 lg:py-16">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <GraduationCap class="h-5 w-5" />
                            </div>
                            <span class="text-xl font-bold text-foreground">UniPortal</span>
                        </div>
                        <p class="text-sm leading-6">
                            Constructing the path to knowledge and success for the next generation of leaders.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-foreground">Quick Links</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li><a href="#" class="text-sm leading-6 hover:text-primary transition-colors">About Us</a></li>
                            <li><a href="#" class="text-sm leading-6 hover:text-primary transition-colors">Admissions</a></li>
                            <li><a href="#" class="text-sm leading-6 hover:text-primary transition-colors">Academics</a></li>
                            <li><a href="#" class="text-sm leading-6 hover:text-primary transition-colors">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold leading-6 text-foreground">Contact</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li class="text-sm leading-6 flex items-center gap-2">
                                <div class="w-6"><Globe class="h-4 w-4" /></div>
                                123 University Ave, Education City
                            </li>
                            <li class="text-sm leading-6 flex items-center gap-2">
                                <div class="w-6">@</div>
                                contact@uniportal.edu
                            </li>
                            <li class="text-sm leading-6 flex items-center gap-2">
                                <div class="w-6"><Clock class="h-4 w-4" /></div>
                                +1 (555) 123-4567
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="mt-12 border-t border-border pt-8 sm:mt-16 lg:mt-24">
                    <p class="text-xs leading-5">&copy; {{ currentYear }} University Portal. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
