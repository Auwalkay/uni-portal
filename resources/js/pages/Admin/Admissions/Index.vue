<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { format } from 'date-fns';
import { Eye } from 'lucide-vue-next';

defineProps<{
    applicants: {
        data: Array<{
            id: string;
            jamb_registration_number: string;
            status: string;
            created_at: string;
            user: {
                name: string;
                email: string;
            };
        }>;
        links: Array<any>;
    };
}>();

const getStatusColor = (status: string) => {
    switch (status) {
        case 'admitted': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'submitted': return 'bg-blue-100 text-blue-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head title="Admission Requests" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-3xl font-bold tracking-tight">Applications</h2>
                <Button>Download Report</Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Recent Applications</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Applicant</TableHead>
                                <TableHead>JAMB No.</TableHead>
                                <TableHead>Date Applied</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="applicant in applicants.data" :key="applicant.id">
                                <TableCell>
                                    <div class="font-medium">{{ applicant.user.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ applicant.user.email }}</div>
                                </TableCell>
                                <TableCell>{{ applicant.jamb_registration_number || 'N/A' }}</TableCell>
                                <TableCell>{{ format(new Date(applicant.created_at), 'MMM d, yyyy') }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline" :class="getStatusColor(applicant.status)">
                                        {{ applicant.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Link :href="route('admin.admissions.show', applicant.id)">
                                        <Button variant="ghost" size="icon">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
