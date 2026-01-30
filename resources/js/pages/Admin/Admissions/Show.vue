<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardContent, CardFooter, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Label } from '@/components/ui/label';
import { format } from 'date-fns';
import { FileText, Download } from 'lucide-vue-next';

const props = defineProps<{
    applicant: {
        id: string;
        jamb_registration_number: string;
        status: string;
        created_at: string;
        application_mode: string;
        
        // New Fields
        first_name?: string;
        last_name?: string;
        phone?: string;
        address?: string;
        dob?: string;
        gender?: string;
        jamb_score?: string;
        previous_institution?: string;
        
        // Relationships
        user: { name: string; email: string; avatar_url?: string };
        state?: { id: number; name: string };
        lga?: { id: number; name: string };
        programme?: { 
            name: string; 
            department?: { 
                faculty?: { name: string } 
            } 
        };
        
        // NOK
        next_of_kin_name?: string;
        next_of_kin_phone?: string;
        next_of_kin_relationship?: string;
        
        documents: Array<{
            id: string;
            type: string;
            original_name: string;
            path: string;
        }>;
    };
}>();

const form = useForm({
    status: props.applicant.status,
});

const updateStatus = () => {
    form.put(route('admin.admissions.update', props.applicant.id), {
        onSuccess: () => {
            // Toast success
        }
    });
};
</script>

<template>
    <Head :title="`Application: ${applicant.user.name}`" />

    <AdminLayout>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Actions & Key Status -->
            <div class="space-y-6">
                <!-- User Profile Card -->
                <Card class="text-center overflow-hidden">
                    <div class="bg-primary/10 h-24"></div>
                    <div class="px-6 pb-6 -mt-12">
                        <div class="relative w-24 h-24 mx-auto rounded-full border-4 border-background bg-muted flex items-center justify-center overflow-hidden">
                            <span v-if="!applicant.user.avatar_url" class="text-3xl">👤</span>
                             <img v-else :src="applicant.user.avatar_url" class="object-cover w-full h-full" />
                        </div>
                        <h2 class="mt-4 text-xl font-bold">{{ applicant.user.name }}</h2>
                        <p class="text-muted-foreground text-sm">{{ applicant.user.email }}</p>
                        
                        <div class="mt-4 flex justify-center">
                            <Badge variant="outline" class="uppercase tracking-wider px-3 py-1" :class="{
                                'bg-yellow-100 text-yellow-800 border-yellow-200': applicant.status === 'draft',
                                'bg-blue-100 text-blue-800 border-blue-200': applicant.status === 'submitted',
                                'bg-purple-100 text-purple-800 border-purple-200': applicant.status === 'screening',
                                'bg-green-100 text-green-800 border-green-200': applicant.status === 'admitted',
                                'bg-red-100 text-red-800 border-red-200': applicant.status === 'rejected',
                            }">
                                {{ applicant.status }}
                            </Badge>
                        </div>
                    </div>
                </Card>

                <!-- Action Panel -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Actions</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-3">
                        <Select v-model="form.status">
                            <SelectTrigger>
                                <SelectValue placeholder="Change Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft (Incomplete)</SelectItem>
                                <SelectItem value="submitted">Submitted</SelectItem>
                                <SelectItem value="screening">Under Screening</SelectItem>
                                <SelectItem value="admitted">Admit Student</SelectItem>
                                <SelectItem value="rejected">Reject Application</SelectItem>
                            </SelectContent>
                        </Select>
                        
                        <Button @click="updateStatus" :disabled="form.processing" class="w-full">
                            Update Status
                        </Button>

                         <a v-if="applicant.status === 'admitted'" :href="route('admin.admissions.letter', applicant.id)" target="_blank" class="w-full">
                            <Button variant="outline" class="w-full">
                                <Download class="h-4 w-4 mr-2" />
                                Download Letter
                            </Button>
                        </a>
                    </CardContent>
                </Card>

                <!-- Contact Info -->
                <Card>
                    <CardHeader><CardTitle class="text-lg">Contact Info</CardTitle></CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div>
                            <span class="block text-muted-foreground text-xs uppercase">Phone</span>
                            <span class="font-medium">{{ applicant.phone || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-muted-foreground text-xs uppercase">Address</span>
                            <span class="font-medium">{{ applicant.address || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="block text-muted-foreground text-xs uppercase">Next of Kin</span>
                            <span class="font-medium">{{ applicant.next_of_kin_name }} ({{ applicant.next_of_kin_relationship }})</span>
                            <span class="block text-xs text-muted-foreground">{{ applicant.next_of_kin_phone }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Detailed Information -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Academic Profile -->
                <Card>
                    <CardHeader>
                        <CardTitle>Academic Profile</CardTitle>
                        <CardDescription>JAMB and Educational background</CardDescription>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-muted/30 p-4 rounded-lg border">
                            <h4 class="text-sm font-semibold uppercase text-muted-foreground mb-2">JAMB DETAILS</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Reg. Number</span>
                                    <span class="font-mono font-bold">{{ applicant.jamb_registration_number }}</span>
                                </div>
                                <Separator />
                                <div class="flex justify-between items-center text-lg">
                                    <span>Score</span>
                                    <Badge :variant="Number(applicant.jamb_score) >= 200 ? 'default' : 'destructive'">
                                        {{ applicant.jamb_score || 'N/A' }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="bg-muted/30 p-4 rounded-lg border">
                            <h4 class="text-sm font-semibold uppercase text-muted-foreground mb-2">Applied Programme</h4>
                            <div class="space-y-1">
                                <p class="text-lg font-bold text-primary">{{ applicant.programme?.name || 'N/A' }}</p>
                                <p class="text-sm text-muted-foreground">{{ applicant.programme?.department?.faculty?.name }}</p>
                                <Badge variant="secondary" class="mt-2">{{ applicant.application_mode }}</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Personal Information -->
                <Card>
                    <CardHeader><CardTitle>Personal Details</CardTitle></CardHeader>
                    <CardContent class="grid grid-cols-2 lg:grid-cols-3 gap-6">
                         <div>
                            <Label class="text-muted-foreground">Date of Birth</Label>
                            <p class="font-medium">{{ applicant.dob ? format(new Date(applicant.dob), 'PPP') : 'N/A' }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">Gender</Label>
                            <p class="font-medium">{{ applicant.gender || 'N/A' }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">State of Origin</Label>
                            <p class="font-medium">{{ applicant.state?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">LGA</Label>
                            <p class="font-medium">{{ applicant.lga?.name || 'N/A' }}</p>
                        </div>
                         <div>
                            <Label class="text-muted-foreground">Previous Institution</Label>
                            <p class="font-medium">{{ applicant.previous_institution || 'N/A' }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Documents Gallery -->
                <Card>
                    <CardHeader>
                        <CardTitle>Submitted Documents</CardTitle>
                        <CardDescription>Review credentials and uploads</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="applicant.documents.length === 0" class="flex flex-col items-center justify-center py-12 text-muted-foreground border-2 border-dashed rounded-lg bg-muted/20">
                            <FileText class="w-10 h-10 mb-2 opacity-20" />
                            <p>No documents uploaded yet</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-for="doc in applicant.documents" :key="doc.id" class="group relative flex items-center gap-4 p-4 border rounded-xl hover:shadow-md transition bg-card">
                                <div class="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                    <FileText class="h-6 w-6 text-primary" />
                                </div>
                                <div class="overflow-hidden">
                                    <p class="font-semibold truncate">{{ doc.type.replace('_', ' ').toUpperCase() }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ doc.original_name }}</p>
                                </div>
                                <a :href="route('admin.documents.show', doc.id)" target="_blank" class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition rounded-xl">
                                    <Button variant="secondary" size="sm">
                                        <Download class="w-4 h-4 mr-2" /> View
                                    </Button>
                                </a>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
