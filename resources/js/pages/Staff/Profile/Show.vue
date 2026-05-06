<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { 
    User, Mail, Phone, MapPin, BookOpen, ClipboardList, 
    Briefcase, GraduationCap, Building2, Calendar, 
    ArrowLeft, Edit, Globe, Award
} from 'lucide-vue-next';

import { route } from 'ziggy-js';
import { computed } from 'vue';

const props = defineProps<{
    staff: any;
}>();

const page = usePage();
const authUser = computed(() => (page.props.auth as any).user);

const breadcrumbs = [
    { title: 'My Profile', href: route('staff.profile.edit') },
    { title: 'Preview', href: '#' },
];
</script>

<template>
    <Head title="Staff Profile Preview" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-8 w-full mx-auto space-y-8 pb-20">
            <!-- Back Action -->
            <div class="flex items-center justify-between">
                <Button variant="ghost" size="sm" as-child class="gap-2">
                    <Link :href="route('staff.profile.edit')">
                        <ArrowLeft class="w-4 h-4" /> Back to Edit
                    </Link>
                </Button>
                <Button size="sm" as-child class="gap-2">
                    <Link :href="route('staff.profile.edit')">
                        <Edit class="w-4 h-4" /> Edit Profile
                    </Link>
                </Button>
            </div>

            <!-- Profile Header -->
            <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-slate-900 border shadow-sm">
                <div class="h-32 bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border-b"></div>
                <div class="px-8 pb-8 flex flex-col md:flex-row gap-8 items-end -mt-12">
                    <div class="relative">
                        <div class="w-32 h-32 rounded-3xl overflow-hidden bg-white p-1 shadow-xl border border-slate-100">
                            <img v-if="authUser.profile_photo_path" 
                                 :src="`/storage/${authUser.profile_photo_path}`" 
                                 class="w-full h-full object-cover rounded-2xl" />
                            <div v-else class="w-full h-full flex items-center justify-center text-primary/20 bg-slate-50 rounded-2xl">
                                <User class="w-16 h-16" />
                            </div>
                        </div>
                        <div class="absolute -bottom-2 -right-2 w-8 h-8 rounded-full bg-green-500 border-4 border-white flex items-center justify-center shadow-sm">
                            <div class="w-2 h-2 rounded-full bg-white animate-pulse"></div>
                        </div>
                    </div>
                    
                    <div class="flex-1 space-y-2 pb-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ authUser.name }}</h1>
                            <Badge variant="secondary" class="bg-primary/10 text-primary border-primary/20" v-if="staff.is_academic">
                                <Award class="w-3 h-3 mr-1" /> Academic Staff
                            </Badge>
                        </div>
                        <p class="text-lg text-muted-foreground flex items-center gap-2">
                            {{ staff.designation }} • {{ staff.department?.name }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- About / Research -->
                    <Card class="border-none shadow-none bg-transparent">
                        <CardHeader class="px-0">
                            <CardTitle class="text-xl flex items-center gap-2">
                                <BookOpen class="w-5 h-5 text-primary" /> Research & Expertise
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="px-0 space-y-6">
                            <div v-if="staff.specialization" class="bg-white dark:bg-slate-900 p-6 rounded-2xl border shadow-sm space-y-2">
                                <Label class="text-xs font-bold uppercase tracking-widest text-primary opacity-70">Area of Specialization</Label>
                                <p class="text-lg font-medium">{{ staff.specialization }}</p>
                            </div>

                            <div v-if="staff.research_interests" class="bg-white dark:bg-slate-900 p-6 rounded-2xl border shadow-sm space-y-4">
                                <Label class="text-xs font-bold uppercase tracking-widest text-primary opacity-70">Research Interests</Label>
                                <div class="text-gray-600 dark:text-slate-400 leading-relaxed whitespace-pre-wrap">
                                    {{ staff.research_interests }}
                                </div>
                            </div>

                            <div v-if="!staff.specialization && !staff.research_interests" class="p-12 text-center border-2 border-dashed rounded-3xl opacity-40">
                                <p>No research details shared yet.</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Professional Background -->
                    <Card class="border shadow-sm rounded-3xl overflow-hidden">
                        <CardHeader class="bg-slate-50 dark:bg-slate-900/50 border-b py-4 px-6">
                            <CardTitle class="text-base flex items-center gap-2">
                                <Briefcase class="w-4 h-4 text-primary" /> Professional Background
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div class="divide-y divide-slate-100">
                                <div class="p-6 flex items-start gap-4">
                                    <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                                        <GraduationCap class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Highest Qualification</p>
                                        <p class="text-muted-foreground">{{ staff.highest_qualification || 'Not specified' }}</p>
                                    </div>
                                </div>
                                <div class="p-6 flex items-start gap-4">
                                    <div class="p-2 bg-amber-50 rounded-lg text-amber-600">
                                        <Calendar class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Member Since</p>
                                        <p class="text-muted-foreground">{{ staff.date_joined ? new Date(staff.date_joined).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Sidebar info -->
                <div class="space-y-8">
                    <!-- Contact Information -->
                    <Card class="border shadow-sm rounded-3xl overflow-hidden">
                        <CardHeader class="bg-slate-50 dark:bg-slate-900/50 border-b py-4 px-6">
                            <CardTitle class="text-base flex items-center gap-2">
                                <Phone class="w-4 h-4 text-primary" /> Contact Info
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 space-y-4">
                            <div class="flex items-center gap-3 group">
                                <div class="p-2 bg-slate-100 rounded-lg group-hover:bg-primary/10 transition-colors">
                                    <Mail class="w-4 h-4 text-slate-500 group-hover:text-primary" />
                                </div>
                                <div class="overflow-hidden">
                                    <p class="text-[10px] uppercase font-bold text-slate-400">Email Address</p>
                                    <p class="text-sm font-medium truncate">{{ authUser.email }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3 group">
                                <div class="p-2 bg-slate-100 rounded-lg group-hover:bg-primary/10 transition-colors">
                                    <Phone class="w-4 h-4 text-slate-500 group-hover:text-primary" />
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-bold text-slate-400">Phone Number</p>
                                    <p class="text-sm font-medium">{{ staff.phone_number || 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 group pt-2 border-t mt-2">
                                <div class="p-2 bg-slate-100 rounded-lg group-hover:bg-primary/10 transition-colors">
                                    <MapPin class="w-4 h-4 text-slate-500 group-hover:text-primary" />
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-bold text-slate-400">Office/Residence</p>
                                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ staff.address || 'Address not listed' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Institutional Info -->
                    <Card class="border shadow-sm rounded-3xl overflow-hidden">
                        <CardHeader class="bg-slate-50 dark:bg-slate-900/50 border-b py-4 px-6">
                            <CardTitle class="text-base flex items-center gap-2">
                                <Building2 class="w-4 h-4 text-primary" /> Institutional
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 space-y-4 text-sm">
                            <div class="flex justify-between items-center py-2 border-b border-dashed">
                                <span class="text-muted-foreground">Faculty</span>
                                <span class="font-semibold">{{ staff.department?.faculty?.name || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-dashed">
                                <span class="text-muted-foreground">Department</span>
                                <span class="font-semibold">{{ staff.department?.name }}</span>
                            </div>
                            <div v-if="staff.unit" class="flex justify-between items-center py-2 border-b border-dashed">
                                <span class="text-muted-foreground">Unit</span>
                                <span class="font-semibold">{{ staff.unit.name }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-muted-foreground">Staff ID</span>
                                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">{{ staff.staff_number }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
