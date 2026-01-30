<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import StudentLayout from '@/layouts/StudentLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { route } from 'ziggy-js';
import { Camera, CheckCircle, FileText, User, MapPin, School, ShieldAlert, Save } from 'lucide-vue-next';

const props = defineProps<{
    student: {
        id: string;
        gender: string;
        dob: string;
        phone_number: string;
        address: string;
        state_id: number;
        lga_id: number;
        next_of_kin_name: string;
        next_of_kin_phone: string;
        next_of_kin_address: string;
        passport_photo_path?: string;
        indigene_letter_path?: string;
        matric_number: string;
        level: string;
        program_duration: string;
        user: {
            name: string;
            email: string;
        };
        state?: { name: string };
        lga?: { name: string };
    };
    states: Array<{ id: number; name: string; lgas: Array<{ id: number; name: string }> }>;
    status?: string;
}>();

const page = usePage();

const form = useForm({
    _method: 'PATCH',
    gender: props.student.gender || '',
    dob: props.student.dob || '',
    phone_number: props.student.phone_number || '',
    address: props.student.address || '',
    state_id: props.student.state_id ? String(props.student.state_id) : '',
    lga_id: props.student.lga_id ? String(props.student.lga_id) : '',
    next_of_kin_name: props.student.next_of_kin_name || '',
    next_of_kin_phone: props.student.next_of_kin_phone || '',
    next_of_kin_address: props.student.next_of_kin_address || '',
    passport_photograph: null as File | null,
    indigene_letter: null as File | null,
});

const previewPassport = ref<string | null>(props.student.passport_photo_path ? `/storage/${props.student.passport_photo_path}` : null);

const filteredLgas = computed(() => {
    if (!form.state_id) return [];
    const state = props.states.find(s => s.id === Number(form.state_id));
    return state ? state.lgas : [];
});

const handlePassportUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.passport_photograph = target.files[0];
        previewPassport.value = URL.createObjectURL(target.files[0]);
    }
};

const updateProfile = () => {
    form.post(route('student.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Flash message handled globally
        },
    });
};
</script>

<template>
    <Head title="Update Profile" />

    <StudentLayout>
        <!-- Removed max-w class to use full width -->
        <div class="w-full space-y-6 pb-10">
            <!-- Header Section -->
            <div class="relative h-64 md:h-56 rounded-none md:rounded-b-2xl bg-gradient-to-r from-primary/90 to-primary overflow-hidden shadow-md">
                <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))]"></div>
                <!-- Adjusted positioning to avoid overlap with invalid negative margin cards -->
                <div class="absolute top-10 left-6 md:left-10 text-white z-10 max-w-2xl">
                    <Badge variant="outline" class="mb-3 text-white border-white/30 bg-white/10 hover:bg-white/20">Student Portal</Badge>
                    <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-2">Update Profile</h1>
                    <p class="text-primary-foreground/90 text-lg">Ensure your personal records and documents are up to date.</p>
                </div>
            </div>

            <!-- Content Grid with Z-Index fix to appear ON TOP of header -->
            <div class="relative z-30 grid grid-cols-1 lg:grid-cols-12 gap-8 -mt-20 px-4 md:px-8 pb-12">
                <!-- Left Column: Sticky Profile Card -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="sticky top-6 space-y-6">
                        <Card class="shadow-lg border-none relative overflow-visible z-20">
                            <CardContent class="pt-6 text-center space-y-4">
                                <div class="relative w-32 h-32 mx-auto group">
                                    <Avatar class="w-32 h-32 border-4 border-background shadow-md transition-transform group-hover:scale-105">
                                        <AvatarImage v-if="previewPassport" :src="previewPassport" class="object-cover" />
                                        <AvatarFallback class="text-4xl bg-muted">{{ student.user.name.charAt(0) }}</AvatarFallback>
                                    </Avatar>
                                    <label for="passport-upload" class="absolute bottom-0 right-0 p-2 bg-primary text-primary-foreground rounded-full cursor-pointer hover:bg-primary/90 transition shadow-sm" title="Upload Passport">
                                        <Camera class="w-4 h-4" />
                                        <input id="passport-upload" type="file" class="hidden" accept="image/*" @change="handlePassportUpload">
                                    </label>
                                </div>
                                
                                <div>
                                    <h2 class="text-xl font-bold text-foreground">{{ student.user.name }}</h2>
                                    <p class="text-sm text-muted-foreground">{{ student.matric_number }}</p>
                                    <div class="flex justify-center gap-2 mt-2">
                                         <Badge variant="secondary" class="text-xs">{{ student.level || '100' }} Level</Badge>
                                         <Badge variant="outline" class="text-xs">{{ student.user.email }}</Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Document Status Summary -->
                        <Card>
                            <CardHeader class="pb-3">
                                <CardTitle class="text-base flex items-center gap-2">
                                    <FileText class="w-4 h-4 text-primary" />
                                    Required Documents
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg border">
                                    <div class="flex items-center gap-3">
                                        <CheckCircle v-if="student.passport_photo_path" class="w-5 h-5 text-green-500" />
                                        <div v-else class="w-5 h-5 rounded-full border-2 border-muted-foreground/30"></div>
                                        <span class="text-sm font-medium">Passport Photo</span>
                                    </div>
                                    <Badge v-if="!student.passport_photo_path" variant="destructive" class="text-[10px]">Missing</Badge>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg border">
                                    <div class="flex items-center gap-3">
                                        <CheckCircle v-if="student.indigene_letter_path" class="w-5 h-5 text-green-500" />
                                        <div v-else class="w-5 h-5 rounded-full border-2 border-muted-foreground/30"></div>
                                        <span class="text-sm font-medium">Indigene Letter</span>
                                    </div>
                                    <Badge v-if="!student.indigene_letter_path" variant="outline" class="text-[10px] border-yellow-500 text-yellow-600">Pending</Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Right Column: Stacked Forms -->
                <div class="lg:col-span-8">
                    <form @submit.prevent="updateProfile" class="space-y-8">
                        
                        <!-- Personal Info Section -->
                        <Card>
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-primary/10 rounded-lg">
                                        <User class="w-5 h-5 text-primary" />
                                    </div>
                                    <div>
                                        <CardTitle>Personal Information</CardTitle>
                                        <CardDescription>Basic personal details.</CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label>Full Name</Label>
                                    <Input :model-value="student.user.name" disabled class="bg-muted opacity-80" />
                                </div>
                                <div class="space-y-2">
                                    <Label>Email</Label>
                                    <Input :model-value="student.user.email" disabled class="bg-muted opacity-80" />
                                </div>
                                <div class="space-y-2">
                                    <Label>Date of Birth</Label>
                                    <Input type="date" v-model="form.dob" />
                                </div>
                                <div class="space-y-2">
                                    <Label>Gender</Label>
                                    <Select v-model="form.gender">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Gender" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Male">Male</SelectItem>
                                            <SelectItem value="Female">Female</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <Label>Phone Number</Label>
                                    <Input v-model="form.phone_number" placeholder="Enter phone number" />
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <Label>Residential Address</Label>
                                    <Input v-model="form.address" placeholder="Enter your full address" />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Origin & Docs Section -->
                        <Card>
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-orange-500/10 rounded-lg">
                                        <MapPin class="w-5 h-5 text-orange-600" />
                                    </div>
                                    <div>
                                        <CardTitle>Origin & Documents</CardTitle>
                                        <CardDescription>Confirm your origin and upload files.</CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label>State of Origin</Label>
                                    <Select v-model="form.state_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select State" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="state in states" :key="state.id" :value="String(state.id)">
                                                {{ state.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <Label>Local Govt. Area</Label>
                                    <Select v-model="form.lga_id" :disabled="!form.state_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select LGA" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="lga in filteredLgas" :key="lga.id" :value="String(lga.id)">
                                                {{ lga.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                
                                <Separator class="md:col-span-2 my-2" />

                                <div class="space-y-4 md:col-span-2">
                                    <Label>Indigene Letter Upload</Label>
                                    <div class="border-2 border-dashed rounded-lg p-8 flex flex-col items-center justify-center text-center hover:bg-muted/50 transition cursor-pointer relative" :class="{'bg-green-50 border-green-200': form.indigene_letter}">
                                        <input type="file" @input="form.indigene_letter = ($event.target as HTMLInputElement).files?.[0] || null" accept=".pdf,.jpg,.png" class="absolute inset-0 opacity-0 cursor-pointer" />
                                        <div class="bg-orange-100 p-3 rounded-full mb-3">
                                            <FileText class="w-6 h-6 text-orange-600" />
                                        </div>
                                        <p class="text-sm font-medium" v-if="!form.indigene_letter">
                                            Click to upload Indigene Letter
                                        </p>
                                        <p class="text-sm font-medium text-green-600" v-else>
                                            Selected: {{ form.indigene_letter.name }}
                                        </p>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            Supported: PDF, JPG, PNG (Max 2MB)
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Next of Kin Section -->
                        <Card>
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-500/10 rounded-lg">
                                        <ShieldAlert class="w-5 h-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <CardTitle>Next of Kin</CardTitle>
                                        <CardDescription>In case of emergency.</CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label>Full Name</Label>
                                    <Input v-model="form.next_of_kin_name" placeholder="Name of relative" />
                                </div>
                                <div class="space-y-2">
                                    <Label>Phone Number</Label>
                                    <Input v-model="form.next_of_kin_phone" placeholder="Contact number" />
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <Label>Address</Label>
                                    <Input v-model="form.next_of_kin_address" placeholder="Address (Optional)" />
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Floating Action Bar or Bottom Actions -->
                        <div class="sticky bottom-4 z-50">
                            <Card class="bg-background/95 backdrop-blur shadow-xl border-t">
                                <CardContent class="p-4 flex justify-between items-center">
                                    <p class="text-sm text-muted-foreground hidden md:block">
                                        Ensure all information is accurate before saving.
                                    </p>
                                    <div class="flex gap-4 w-full md:w-auto">
                                        <Button variant="outline" type="button" @click="form.reset()" class="flex-1 md:flex-none">Reset</Button>
                                        <Button type="submit" :disabled="form.processing" class="flex-1 md:flex-none min-w-[150px]">
                                            <Save class="w-4 h-4 mr-2" />
                                            {{ form.processing ? 'Saving Changes...' : 'Save Profile' }}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
