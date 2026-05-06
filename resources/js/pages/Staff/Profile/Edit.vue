<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { 
    User, Mail, Phone, MapPin, BookOpen, ClipboardList, 
    Heart, Shield, KeyRound, Camera, ArrowLeft, Loader2,
    CheckCircle2, Eye, Info, BadgeCheck
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps<{
    staff: any;
    status?: string;
}>();

const page = usePage();
const authUser = computed(() => (page.props.auth as any).user);

const breadcrumbs = [
    { title: 'My Profile', href: route('staff.profile.edit') },
];

const photoInput = ref<HTMLInputElement | null>(null);
const photoPreview = ref<string | null>(null);

const form = useForm({
    _method: 'POST', // For file uploads
    phone_number: props.staff.phone_number || '',
    address: props.staff.address || '',
    specialization: props.staff.specialization || '',
    research_interests: props.staff.research_interests || '',
    next_of_kin_name: props.staff.next_of_kin_name || '',
    next_of_kin_phone: props.staff.next_of_kin_phone || '',
    next_of_kin_address: props.staff.next_of_kin_address || '',
    next_of_kin_relationship: props.staff.next_of_kin_relationship || '',
    profile_photo: null as File | null,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const selectNewPhoto = () => {
    photoInput.value?.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value?.files?.[0];
    if (!photo) return;

    form.profile_photo = photo;

    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(photo);
};

const updateProfile = () => {
    form.post(route('staff.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.profile_photo = null;
            if (photoInput.value) {
                photoInput.value.value = '';
            }
        }
    });
};

const updatePassword = () => {
    passwordForm.put(route('staff.profile.password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};
</script>

<template>
    <Head title="Staff Profile Settings" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-8 space-y-8 pb-20 w-full mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-xl border shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="relative group">
                        <div class="w-16 h-16 rounded-full overflow-hidden bg-slate-100 border-2 border-primary/20">
                            <img v-if="photoPreview || authUser.profile_photo_path" 
                                 :src="photoPreview || `/storage/${authUser.profile_photo_path}`" 
                                 class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-primary/40">
                                <User class="w-8 h-8" />
                            </div>
                        </div>
                        <button @click="selectNewPhoto" class="absolute -bottom-1 -right-1 p-1.5 bg-primary text-white rounded-full border-2 border-white shadow-sm hover:scale-110 transition-transform">
                            <Camera class="w-3 h-3" />
                        </button>
                        <input type="file" ref="photoInput" class="hidden" @change="updatePhotoPreview" accept="image/*" />
                    </div>
                    <div class="space-y-1">
                        <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">{{ authUser.name }}</h1>
                        <p class="text-sm text-muted-foreground flex items-center gap-1.5">
                            <BadgeCheck class="w-3.5 h-3.5 text-blue-500" v-if="staff.staff_number" />
                            {{ staff.staff_number }} • {{ staff.designation }}
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 animate-in fade-in slide-in-from-right-2">Saved successfully!</p>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" as-child class="h-9 gap-2">
                            <Link :href="route('staff.profile.show')">
                                <Eye class="w-4 h-4" /> Preview Public Profile
                            </Link>
                        </Button>
                        <Button @click="updateProfile" :disabled="form.processing" class="h-9 gap-2">
                            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                            <CheckCircle2 v-else class="w-4 h-4" />
                            Save Changes
                        </Button>
                    </div>
                </div>
            </div>

            <Tabs default-value="personal" class="w-full space-y-6">
                <TabsList class="grid w-full grid-cols-4 h-12 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl">
                    <TabsTrigger value="personal" class="rounded-lg font-bold text-xs uppercase tracking-widest">
                        <User class="w-3.5 h-3.5 mr-2" /> Personal
                    </TabsTrigger>
                    <TabsTrigger value="academic" class="rounded-lg font-bold text-xs uppercase tracking-widest">
                        <BookOpen class="w-3.5 h-3.5 mr-2" /> Academic
                    </TabsTrigger>
                    <TabsTrigger value="nok" class="rounded-lg font-bold text-xs uppercase tracking-widest">
                        <Heart class="w-3.5 h-3.5 mr-2" /> Next of Kin
                    </TabsTrigger>
                    <TabsTrigger value="security" class="rounded-lg font-bold text-xs uppercase tracking-widest">
                        <Shield class="w-3.5 h-3.5 mr-2" /> Security
                    </TabsTrigger>
                </TabsList>

                <!-- Personal Information -->
                <TabsContent value="personal" class="space-y-6 animate-in fade-in-50 duration-300">
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg flex items-center gap-2">
                                <Info class="w-5 h-5 text-primary" /> Contact & Residence
                            </CardTitle>
                            <CardDescription>Update your personal contact details and residential address.</CardDescription>
                        </CardHeader>
                        <CardContent class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label class="text-sm font-semibold">Full Name (Read-only)</Label>
                                    <Input :value="authUser.name" disabled class="bg-slate-50 border-dashed" />
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-sm font-semibold opacity-70">Email Address (Read-only)</Label>
                                    <Input :value="authUser.email" disabled class="bg-slate-50 border-dashed" />
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-sm font-semibold">Phone Number</Label>
                                    <div class="relative group">
                                        <Phone class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                        <Input v-model="form.phone_number" class="pl-10" placeholder="+234..." />
                                    </div>
                                    <p v-if="form.errors.phone_number" class="text-xs text-destructive mt-1">{{ form.errors.phone_number }}</p>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <Label class="text-sm font-semibold">Residential Address</Label>
                                    <div class="relative group">
                                        <MapPin class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                        <Textarea v-model="form.address" class="pl-10 min-h-[100px]" placeholder="Your current home address..." />
                                    </div>
                                    <p v-if="form.errors.address" class="text-xs text-destructive mt-1">{{ form.errors.address }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Academic Focus -->
                <TabsContent value="academic" class="space-y-6 animate-in fade-in-50 duration-300">
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg flex items-center gap-2">
                                <BookOpen class="w-5 h-5 text-primary" /> Research & Specialization
                            </CardTitle>
                            <CardDescription>Manage your areas of expertise and research focus.</CardDescription>
                        </CardHeader>
                        <CardContent class="p-6 space-y-6">
                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <Label class="text-sm font-semibold">Area of Specialization</Label>
                                    <div class="relative group">
                                        <BookOpen class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                        <Input v-model="form.specialization" class="pl-10" placeholder="e.g. Artificial Intelligence, Constitutional Law..." />
                                    </div>
                                    <p v-if="form.errors.specialization" class="text-xs text-destructive mt-1">{{ form.errors.specialization }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-sm font-semibold">Research Interests</Label>
                                    <div class="relative group">
                                        <ClipboardList class="absolute left-3 top-3 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                        <Textarea v-model="form.research_interests" class="pl-10 min-h-[150px]" placeholder="Describe your current research projects and areas of interest..." />
                                    </div>
                                    <p v-if="form.errors.research_interests" class="text-xs text-destructive mt-1">{{ form.errors.research_interests }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Next of Kin -->
                <TabsContent value="nok" class="space-y-6 animate-in fade-in-50 duration-300">
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg flex items-center gap-2">
                                <Heart class="w-5 h-5 text-red-500" /> Next of Kin Details
                            </CardTitle>
                            <CardDescription>Emergency contact information.</CardDescription>
                        </CardHeader>
                        <CardContent class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label class="text-sm font-semibold">Next of Kin Name</Label>
                                    <Input v-model="form.next_of_kin_name" placeholder="Full name" />
                                    <p v-if="form.errors.next_of_kin_name" class="text-xs text-destructive mt-1">{{ form.errors.next_of_kin_name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-sm font-semibold">Relationship</Label>
                                    <Input v-model="form.next_of_kin_relationship" placeholder="e.g. Spouse, Parent, Sibling" />
                                    <p v-if="form.errors.next_of_kin_relationship" class="text-xs text-destructive mt-1">{{ form.errors.next_of_kin_relationship }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-sm font-semibold">Contact Phone</Label>
                                    <Input v-model="form.next_of_kin_phone" placeholder="+234..." />
                                    <p v-if="form.errors.next_of_kin_phone" class="text-xs text-destructive mt-1">{{ form.errors.next_of_kin_phone }}</p>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <Label class="text-sm font-semibold">Residential Address</Label>
                                    <Textarea v-model="form.next_of_kin_address" placeholder="Full address of next of kin" />
                                    <p v-if="form.errors.next_of_kin_address" class="text-xs text-destructive mt-1">{{ form.errors.next_of_kin_address }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Security -->
                <TabsContent value="security" class="space-y-6 animate-in fade-in-50 duration-300">
                    <Card class="border shadow-sm">
                        <CardHeader class="pb-4">
                            <CardTitle class="text-lg flex items-center gap-2">
                                <KeyRound class="w-5 h-5 text-amber-500" /> Change Password
                            </CardTitle>
                            <CardDescription>Ensure your account is using a long, random password to stay secure.</CardDescription>
                        </CardHeader>
                        <CardContent class="p-6 space-y-6">
                            <form @submit.prevent="updatePassword" class="grid grid-cols-1 gap-6 max-w-md">
                                <div class="space-y-2">
                                    <Label for="current_password">Current Password</Label>
                                    <Input id="current_password" type="password" v-model="passwordForm.current_password" />
                                    <p v-if="passwordForm.errors.current_password" class="text-xs text-destructive">{{ passwordForm.errors.current_password }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="password">New Password</Label>
                                    <Input id="password" type="password" v-model="passwordForm.password" />
                                    <p v-if="passwordForm.errors.password" class="text-xs text-destructive">{{ passwordForm.errors.password }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="password_confirmation">Confirm Password</Label>
                                    <Input id="password_confirmation" type="password" v-model="passwordForm.password_confirmation" />
                                </div>
                                <div class="flex items-center gap-4">
                                    <Button :disabled="passwordForm.processing">
                                        <Loader2 v-if="passwordForm.processing" class="w-4 h-4 animate-spin mr-2" />
                                        Update Password
                                    </Button>
                                    <p v-if="passwordForm.recentlySuccessful" class="text-sm text-green-600">Password updated successfully.</p>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AdminLayout>
</template>
