<script setup lang="ts">
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { watch } from 'vue';
import Swal from 'sweetalert2';

import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import PasswordInput from '@/components/PasswordInput.vue';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

const page = usePage();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(store.url(), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

watch(() => page.props.errors, (errors) => {
    if (Object.keys(errors).length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Registration Failed',
            text: 'Please check the inputs.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000
        });
    }
}, { deep: true });
</script>

<template>
    <AuthBase
        title="Welcome to Portal"
        description="Access your account or apply for admission"
    >
        <Head title="Register" />

        <Tabs defaultValue="register" @update:modelValue="(v) => v === 'login' && router.visit(login().url)" class="w-full">
            <TabsList class="grid w-full grid-cols-2 mb-6">
                <TabsTrigger value="login">Sign In</TabsTrigger>
                <TabsTrigger value="register">Fresh Applicant</TabsTrigger>
            </TabsList>
            <TabsContent value="register">
                <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        v-model="form.name"
                        placeholder="Full name"
                        class="shadow-sm"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                        class="shadow-sm"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <PasswordInput
                        id="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        v-model="form.password"
                        placeholder="Password"
                        :error="form.errors.password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                        :error="form.errors.password_confirmation"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="w-full bg-primary text-primary-foreground hover:bg-primary/90 shadow-md transition-all active:scale-95"
                    tabindex="5"
                    :disabled="form.processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="form.processing" class="mr-2" />
                    Create account
                </Button>
                </div>
                </form>
            </TabsContent>
        </Tabs>

        <div class="mt-6 border-t border-slate-200/80 pt-5 text-center dark:border-slate-800">
            <Link
                href="/programmes"
                class="inline-flex w-full items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-bold tracking-wider text-slate-700 uppercase transition hover:border-red-300 hover:bg-red-50 hover:text-red-700 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:border-red-700 dark:hover:bg-red-950/40 dark:hover:text-red-300"
            >
                View Programmes &amp; School Fees
            </Link>
        </div>
    </AuthBase>
</template>
