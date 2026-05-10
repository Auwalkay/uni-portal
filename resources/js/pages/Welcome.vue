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
    Search,
    Calendar,
    Newspaper,
    Award,
    Mail,
    Phone,
    MapPin
} from 'lucide-vue-next';
import { dashboard, login, register } from '@/routes';
import { ref, onMounted, onUnmounted } from 'vue';

defineProps<{
    canRegister?: boolean;
}>();

const currentYear = new Date().getFullYear();

const sliderImages = [
    '/images/slider/slide1.jpg',
    '/images/slider/slide2.jpg',
    '/images/slider/slide3.jpg',
    '/images/slider/slide4.jpg',
    '/images/slider/slide5.jpg',
];

const currentSlide = ref(0);
let sliderInterval: any = null;

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % sliderImages.length;
};

onMounted(() => {
    sliderInterval = setInterval(nextSlide, 5000);
});

onUnmounted(() => {
    if (sliderInterval) clearInterval(sliderInterval);
});

const newsItems = [
    { title: 'MIU Nigeria Welcomes New Students for 2024/2025 Session', date: 'May 10, 2024', category: 'Admission' },
    { title: 'New Faculty Research Grant Announced for Digital Innovation', date: 'May 05, 2024', category: 'Research' },
    { title: 'MIU Sports Team Triumphs at National University Games', date: 'April 28, 2024', category: 'Sports' },
];

const events = [
    { title: 'Inaugural Matriculation Ceremony', date: 'June 15, 2024', time: '10:00 AM' },
    { title: 'Digital Skills Workshop for Freshmen', date: 'June 20, 2024', time: '2:00 PM' },
    { title: 'Alumni Networking Dinner', date: 'July 05, 2024', time: '6:00 PM' },
];
</script>

<template>
    <Head title="Welcome to MIU Nigeria University Portal" />

    <div class="min-h-screen bg-[#FDFDFD] text-slate-900 font-sans selection:bg-primary selection:text-primary-foreground overflow-x-hidden">
        
        <!-- Premium Navigation -->
        <nav class="fixed top-0 z-[60] w-full border-b border-slate-200/60 bg-white/80 backdrop-blur-xl transition-all duration-300">
            <div class="container mx-auto flex h-20 items-center justify-between px-6 lg:px-12">
                <div class="flex items-center gap-4 group">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white p-2 shadow-sm border border-slate-100 group-hover:rotate-3 transition-transform duration-300">
                        <img src="/miu-logo.png" alt="MIU Logo" class="h-full w-full object-contain" />
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-xl font-black tracking-tighter text-slate-900 uppercase">MIU Nigeria</span>
                        <span class="text-[10px] uppercase tracking-[0.2em] text-slate-500 font-bold">University Portal</span>
                    </div>
                </div>

                <div class="flex items-center gap-8">
                    <div class="hidden md:flex items-center gap-6">
                        <a href="#about" class="text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-primary transition-colors">About</a>
                        <a href="#academics" class="text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-primary transition-colors">Academics</a>
                        <a href="#news" class="text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-primary transition-colors">News</a>
                    </div>
                    
                    <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

                    <div class="flex items-center gap-3">
                        <template v-if="$page.props.auth.user">
                            <Link :href="dashboard()" class="inline-flex h-10 items-center justify-center rounded-full bg-slate-900 px-6 text-xs font-bold uppercase tracking-widest text-white hover:bg-slate-800 transition-all active:scale-95 shadow-lg shadow-slate-900/10">
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="login()" class="text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-primary transition-colors px-4">
                                Log in
                            </Link>
                            <Link 
                                v-if="canRegister" 
                                :href="register()" 
                                class="inline-flex h-11 items-center justify-center rounded-full bg-primary px-8 text-xs font-bold uppercase tracking-widest text-white shadow-xl shadow-primary/20 hover:bg-primary/90 hover:scale-105 active:scale-95 transition-all duration-300"
                            >
                                Apply Now
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <main class="relative">
            <!-- Hero Section: Mature & Professional -->
            <section class="relative min-h-[90vh] flex items-center pt-20 overflow-hidden">
                <div class="absolute inset-0 bg-slate-900">
                    <transition-group 
                        name="fade" 
                        tag="div" 
                        class="relative h-full w-full"
                    >
                        <div 
                            v-for="(image, index) in sliderImages" 
                            :key="image"
                            v-show="currentSlide === index"
                            class="absolute inset-0"
                        >
                            <img 
                                :src="image" 
                                class="h-full w-full object-cover opacity-50 scale-105 animate-ken-burns"
                                alt="MIU Students"
                            />
                        </div>
                    </transition-group>
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/60 to-transparent z-10"></div>
                </div>

                <div class="container relative z-10 mx-auto px-6 lg:px-12 py-20">
                    <div class="max-w-3xl animate-fade-in-up">
                        <div class="mb-6 flex items-center gap-2">
                            <div class="h-px w-12 bg-primary"></div>
                            <span class="text-xs font-bold uppercase tracking-[0.3em] text-primary">Academic Excellence</span>
                        </div>
                        <h1 class="text-5xl lg:text-7xl font-black text-white leading-[1.1] mb-8 tracking-tight">
                            Building the Future <br/>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-white/60">One Mind at a Time</span>
                        </h1>
                        <p class="text-lg lg:text-xl text-slate-300 leading-relaxed max-w-2xl mb-10 font-medium">
                            Welcome to Mewar International University Nigeria. A sanctuary of knowledge, where tradition meets innovation to create a world-class academic experience.
                        </p>
                        
                        <div class="flex flex-wrap items-center gap-6">
                            <Link 
                                v-if="canRegister"
                                :href="register()" 
                                class="group relative inline-flex h-14 items-center justify-center overflow-hidden rounded-full bg-primary px-10 font-bold text-white shadow-2xl transition-all duration-300 hover:scale-105 active:scale-95"
                            >
                                <span class="relative flex items-center gap-2">
                                    Begin Your Application <ArrowRight class="h-5 w-5 transition-transform group-hover:translate-x-1" />
                                </span>
                            </Link>
                            
                            <a href="#about" class="group flex items-center gap-3 text-white font-bold tracking-widest uppercase text-xs hover:text-primary transition-colors">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full border border-white/20 group-hover:border-primary transition-colors">
                                    <Search class="h-4 w-4" />
                                </div>
                                Explore MIU Nigeria
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Floating Stats Decor -->
                <div class="absolute bottom-12 right-12 hidden lg:block animate-fade-in-up delay-500">
                    <div class="grid grid-cols-2 gap-4 bg-white/5 backdrop-blur-xl p-8 rounded-3xl border border-white/10 shadow-2xl">
                        <div class="space-y-1 pr-8 border-r border-white/10">
                            <p class="text-3xl font-black text-white">120+</p>
                            <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Programs</p>
                        </div>
                        <div class="space-y-1 pl-4">
                            <p class="text-3xl font-black text-white">15K+</p>
                            <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Students</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Philosophy Section -->
            <section id="about" class="py-24 bg-white">
                <div class="container mx-auto px-6 lg:px-12">
                    <div class="grid lg:grid-cols-2 gap-16 items-center">
                        <div class="relative group">
                            <div class="absolute -inset-4 bg-primary/10 rounded-[2.5rem] rotate-2 group-hover:rotate-0 transition-transform duration-500"></div>
                            <img 
                                src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=2070&auto=format&fit=crop" 
                                class="relative rounded-[2rem] shadow-2xl object-cover h-[500px] w-full"
                                alt="University Architecture"
                            />
                            <div class="absolute -bottom-8 -right-8 bg-slate-900 p-8 rounded-3xl shadow-2xl hidden md:block border-4 border-white">
                                <Trophy class="h-12 w-12 text-primary mb-4" />
                                <p class="text-white font-black text-2xl">#1</p>
                                <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">Innovative Campus</p>
                            </div>
                        </div>
                        <div class="space-y-8">
                            <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-1.5 text-[10px] font-black uppercase tracking-widest text-slate-600">
                                <Sparkles class="h-3 w-3 text-primary" /> Our Philosophy
                            </div>
                            <h2 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                                Education for Knowledge, <br/>
                                <span class="text-primary italic font-serif">Peace & Prosperity.</span>
                            </h2>
                            <p class="text-lg text-slate-600 leading-relaxed">
                                At MIU Nigeria, we believe that education is the catalyst for global transformation. Our curriculum is designed to bridge the gap between academic theory and industrial practice, ensuring our graduates are not just job seekers, but job creators.
                            </p>
                            <div class="grid sm:grid-cols-2 gap-6">
                                <div class="flex items-start gap-4">
                                    <div class="h-10 w-10 flex shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                        <GraduationCap class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 mb-1">Global Standard</h4>
                                        <p class="text-sm text-slate-500">International faculty and diverse curriculum.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="h-10 w-10 flex shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                        <Award class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 mb-1">Modern Facilities</h4>
                                        <p class="text-sm text-slate-500">High-tech labs and digital learning environment.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- News & Events Section -->
            <section id="news" class="py-24 bg-slate-50">
                <div class="container mx-auto px-6 lg:px-12">
                    <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
                        <div class="space-y-4">
                            <div class="h-1.5 w-12 bg-primary"></div>
                            <h2 class="text-4xl font-black text-slate-900 tracking-tight">University Pulse</h2>
                            <p class="text-slate-500 font-medium">Keep up with latest happenings and upcoming milestones.</p>
                        </div>
                        <a href="#" class="text-xs font-bold uppercase tracking-widest text-primary hover:underline flex items-center gap-2">
                            View Newsroom <ArrowRight class="h-4 w-4" />
                        </a>
                    </div>

                    <div class="grid lg:grid-cols-3 gap-8">
                        <!-- News Feed -->
                        <div class="lg:col-span-2 grid sm:grid-cols-2 gap-8">
                            <div v-for="(item, idx) in newsItems" :key="idx" class="group bg-white rounded-3xl p-8 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                                <div class="flex items-center justify-between mb-6">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-primary bg-primary/5 px-3 py-1 rounded-full">{{ item.category }}</span>
                                    <span class="text-[10px] font-bold text-slate-400">{{ item.date }}</span>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 mb-6 group-hover:text-primary transition-colors leading-snug">
                                    {{ item.title }}
                                </h3>
                                <a href="#" class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-400 group-hover:text-slate-900 transition-colors">
                                    Read Article <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                                </a>
                            </div>
                        </div>

                        <!-- Upcoming Events -->
                        <div class="bg-slate-900 rounded-[2.5rem] p-10 text-white shadow-2xl relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-8 opacity-10">
                                <Calendar class="h-32 w-32" />
                            </div>
                            <h3 class="text-2xl font-black mb-8 relative z-10">Event Calendar</h3>
                            <div class="space-y-8 relative z-10">
                                <div v-for="(event, idx) in events" :key="idx" class="flex gap-6 group">
                                    <div class="flex flex-col items-center justify-center h-16 w-16 shrink-0 rounded-2xl bg-white/10 group-hover:bg-primary transition-colors">
                                        <span class="text-lg font-black leading-none">{{ event.date.split(' ')[1].replace(',', '') }}</span>
                                        <span class="text-[8px] uppercase font-bold tracking-widest opacity-60">{{ event.date.split(' ')[0] }}</span>
                                    </div>
                                    <div class="space-y-1">
                                        <h4 class="font-bold text-sm leading-tight group-hover:text-primary transition-colors">{{ event.title }}</h4>
                                        <p class="text-[10px] font-bold text-white/40 flex items-center gap-1">
                                            <Clock class="h-3 w-3" /> {{ event.time }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <button class="mt-12 w-full h-12 rounded-2xl border border-white/20 text-xs font-bold uppercase tracking-widest hover:bg-white hover:text-slate-900 transition-all duration-300">
                                View Full Calendar
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Call to Action: Formal & Bold -->
            <section class="py-24 relative overflow-hidden">
                <div class="absolute inset-0 bg-primary">
                    <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary to-slate-900 opacity-90"></div>
                </div>
                <div class="container relative z-10 mx-auto px-6 lg:px-12 text-center text-white">
                    <div class="max-w-4xl mx-auto space-y-10">
                        <h2 class="text-4xl lg:text-6xl font-black tracking-tight leading-tight">
                            The Gateway to Your <br/> 
                            Professional Destiny.
                        </h2>
                        <p class="text-lg text-white/80 font-medium max-w-2xl mx-auto">
                            Join thousands of students who have already chosen Mewar International University Nigeria as their launchpad for success.
                        </p>
                        <div class="flex flex-wrap justify-center gap-6 pt-6">
                            <Link 
                                v-if="canRegister"
                                :href="register()" 
                                class="h-16 inline-flex items-center justify-center rounded-full bg-white px-12 text-sm font-black uppercase tracking-widest text-primary hover:bg-slate-100 hover:scale-105 active:scale-95 transition-all shadow-2xl"
                            >
                                Start Application
                            </Link>
                            <Link :href="login()" class="h-16 inline-flex items-center justify-center rounded-full border-2 border-white/30 px-12 text-sm font-black uppercase tracking-widest text-white hover:bg-white/10 transition-all">
                                Student Portal
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Academic Footer: Formal & Comprehensive -->
        <footer class="bg-slate-900 pt-24 pb-12 text-slate-400">
            <div class="container mx-auto px-6 lg:px-12">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-16 mb-20">
                    <div class="lg:col-span-1 space-y-8">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-white p-1.5 shadow-xl">
                                <img src="/miu-logo.jpeg" alt="MIU Logo" class="h-full w-full object-contain" />
                            </div>
                            <span class="text-xl font-black tracking-tighter text-white uppercase">MIU Nigeria</span>
                        </div>
                        <p class="text-sm leading-relaxed font-medium">
                            Dedicated to providing world-class education that empowers students to innovate, lead, and excel in a rapidly changing world.
                        </p>
                        <div class="flex gap-4">
                            <a href="#" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white/5 hover:bg-primary hover:text-white transition-all"><Globe class="h-4 w-4" /></a>
                            <a href="#" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white/5 hover:bg-primary hover:text-white transition-all"><Search class="h-4 w-4" /></a>
                            <a href="#" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white/5 hover:bg-primary hover:text-white transition-all"><Award class="h-4 w-4" /></a>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-xs font-black uppercase tracking-[0.3em] text-white mb-8">Academics</h4>
                        <ul class="space-y-4 text-sm font-bold uppercase tracking-widest">
                            <li><a href="#" class="hover:text-primary transition-colors">Faculties</a></li>
                            <li><a href="#" class="hover:text-primary transition-colors">Departments</a></li>
                            <li><a href="#" class="hover:text-primary transition-colors">Library</a></li>
                            <li><a href="#" class="hover:text-primary transition-colors">Research</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-xs font-black uppercase tracking-[0.3em] text-white mb-8">Resources</h4>
                        <ul class="space-y-4 text-sm font-bold uppercase tracking-widest">
                            <li><a href="#" class="hover:text-primary transition-colors">Portal Login</a></li>
                            <li><a href="#" class="hover:text-primary transition-colors">Student Mail</a></li>
                            <li><a href="#" class="hover:text-primary transition-colors">Academic Calendar</a></li>
                            <li><a href="#" class="hover:text-primary transition-colors">Help Center</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-xs font-black uppercase tracking-[0.3em] text-white mb-8">Contact Us</h4>
                        <ul class="space-y-6 text-sm font-medium">
                            <li class="flex gap-4">
                                <MapPin class="h-5 w-5 text-primary shrink-0" />
                                KM 21, Abuja-Keffi Road, Masaka, Nasarawa State, Nigeria.
                            </li>
                            <li class="flex gap-4">
                                <Mail class="h-5 w-5 text-primary shrink-0" />
                                info@miu-nigeria.edu.ng
                            </li>
                            <li class="flex gap-4">
                                <Phone class="h-5 w-5 text-primary shrink-0" />
                                +234 800 000 0000
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-6">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] opacity-40">
                        &copy; {{ currentYear }} MIU Nigeria University Portal. All rights reserved.
                    </p>
                    <div class="flex gap-8 text-[10px] font-bold uppercase tracking-[0.2em]">
                        <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                        <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes ken-burns {
    0% { transform: scale(1.0); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1.0); }
}

.animate-ken-burns {
    animation: ken-burns 20s ease-in-out infinite;
}

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 1s ease-out forwards;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 1.5s ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.delay-100 { animation-delay: 100ms; }
.delay-200 { animation-delay: 200ms; }
.delay-300 { animation-delay: 300ms; }
.delay-500 { animation-delay: 500ms; }
</style>
