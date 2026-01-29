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
        user: {
            name: string;
            email: string;
        };
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
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">{{ applicant.user.name }}</h2>
                    <p class="text-muted-foreground">Application ID: {{ applicant.id }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a v-if="applicant.status === 'admitted'" :href="route('admin.admissions.letter', applicant.id)" target="_blank">
                        <Button variant="outline" class="mr-2">
                            <Download class="h-4 w-4 mr-2" />
                            Admission Letter
                        </Button>
                    </a>
                    
                    <Select v-model="form.status">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="submitted">Submitted</SelectItem>
                            <SelectItem value="screening">In Screening</SelectItem>
                            <SelectItem value="admitted">Admitted</SelectItem>
                            <SelectItem value="rejected">Rejected</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button @click="updateStatus" :disabled="form.processing">Update Status</Button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Applicant Details -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Application Details</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-2 gap-4">
                        <div>
                            <Label class="text-muted-foreground">Full Name</Label>
                            <p class="font-medium">{{ applicant.user.name }}</p>
                        </div>
                         <div>
                            <Label class="text-muted-foreground">Email</Label>
                            <p class="font-medium">{{ applicant.user.email }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">JAMB Reg. No</Label>
                            <p class="font-medium">{{ applicant.jamb_registration_number || 'N/A' }}</p>
                        </div>
                        <div>
                            <Label class="text-muted-foreground">Mode</Label>
                            <p class="font-medium">{{ applicant.application_mode }}</p>
                        </div>
                         <div>
                            <Label class="text-muted-foreground">Date Submitted</Label>
                            <p class="font-medium">{{ format(new Date(applicant.created_at), 'PPP') }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Documents Side Panel -->
                <Card>
                    <CardHeader>
                        <CardTitle>Submitted Documents</CardTitle>
                        <CardDescription>Review uploaded files</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="applicant.documents.length === 0" class="text-sm text-muted-foreground italic">
                            No documents uploaded yet.
                        </div>
                        <div v-for="doc in applicant.documents" :key="doc.id" class="flex items-center justify-between p-2 border rounded hover:bg-muted/50 transition">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <FileText class="h-4 w-4 text-primary shrink-0" />
                                <div class="truncate">
                                    <p class="text-sm font-medium truncate">{{ doc.type }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ doc.original_name }}</p>
                                </div>
                            </div>
                            <a :href="route('admin.documents.show', doc.id)" target="_blank">
                                <Button variant="ghost" size="icon" class="h-8 w-8">
                                    <Download class="h-4 w-4" />
                                </Button>
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
