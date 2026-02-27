<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { route } from 'ziggy-js';
import ApplicationLogo from '@/components/ApplicationLogo.vue';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('central.login.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Super Admin Login" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-100">
        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-md overflow-hidden sm:rounded-xl border border-slate-200">
            <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
                <Link href="/" class="inline-block">
                    <ApplicationLogo class="h-16 w-16 mx-auto mb-6 text-primary" />
                </Link>
                <h2 class="text-3xl font-extrabold text-primary">NBTE Central Portal</h2>
                <h3 class="mt-2 text-xl font-bold text-slate-900 tracking-tight">Super Admin Access</h3>
                <p class="mt-2 text-sm text-slate-500">
                    Secure login for authorized personnel only.
                </p>
            </div>

            <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <p class="text-xs text-red-500" v-if="form.errors.email">{{ form.errors.email }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />
                    <p class="text-xs text-red-500" v-if="form.errors.password">{{ form.errors.password }}</p>
                </div>

                <div class="flex items-center space-x-2">
                    <Checkbox id="remember" :checked="form.remember" @update:checked="form.remember = $event" />
                    <Label for="remember" class="font-normal text-slate-600">Remember me</Label>
                </div>

                <div class="pt-4">
                    <Button type="submit" class="w-full" :disabled="form.processing">
                        Log in
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
