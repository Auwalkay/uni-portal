<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import HostelForm from '@/components/Hostels/HostelForm.vue';
import { Building, ArrowLeft } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    hostel: {
        id: string;
        name: string;
        gender_type: string;
        description?: string;
        payment_gateway?: string;
        squadco_secret_key?: string;
        squadco_public_key?: string;
        paystack_secret_key?: string;
        paystack_public_key?: string;
        seerbit_secret_key?: string;
        seerbit_public_key?: string;
    };
}>();

const form = useForm({
    name: props.hostel.name,
    gender_type: props.hostel.gender_type,
    description: props.hostel.description || '',
    payment_gateway: props.hostel.payment_gateway || 'none',
    squadco_secret_key: props.hostel.squadco_secret_key || '',
    squadco_public_key: props.hostel.squadco_public_key || '',
    paystack_secret_key: props.hostel.paystack_secret_key || '',
    paystack_public_key: props.hostel.paystack_public_key || '',
    seerbit_secret_key: props.hostel.seerbit_secret_key || '',
    seerbit_public_key: props.hostel.seerbit_public_key || '',
});

const submit = () => {
    form.put(route('admin.hostels.update', props.hostel.id));
};
</script>

<template>
    <Head :title="`Edit ${hostel.name}`" />

    <AdminLayout>
        <div class="space-y-8 p-6 lg:p-10 max-w-[1300px] mx-auto">
            <!-- Header Ribbon -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-gradient-to-r from-card via-card to-primary/5 border rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <Link :href="route('admin.hostels.show', hostel.id)" class="inline-flex items-center text-xs font-bold text-muted-foreground hover:text-primary transition-colors bg-muted/50 px-3 py-1.5 rounded-full border">
                            <ArrowLeft class="h-3.5 w-3.5 mr-1.5" /> Back to Hostel Details
                        </Link>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl flex items-center gap-3">
                        <div class="h-12 w-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shadow-inner">
                            <Building class="h-7 w-7" />
                        </div>
                        Edit Hostel: {{ hostel.name }}
                    </h1>
                    <p class="text-muted-foreground max-w-2xl text-base">
                        Update hostel configuration, gender allocation rules, descriptions, or modify custom payment gateway API keys.
                    </p>
                </div>
            </div>

            <HostelForm 
                :form="form" 
                :is-editing="true"
                submit-label="Update Hostel"
                :cancel-href="route('admin.hostels.show', hostel.id)" 
                @submit="submit" 
            />
        </div>
    </AdminLayout>
</template>
