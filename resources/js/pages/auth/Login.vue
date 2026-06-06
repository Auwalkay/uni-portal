<script setup lang="ts">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { watch } from 'vue';
import Swal from 'sweetalert2';

import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import PasswordInput from '@/components/PasswordInput.vue';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const page = usePage();

watch(() => page.props.errors, (errors) => {
    if (Object.keys(errors).length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: 'Please check your credentials.',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000
        });
    }
}, { deep: true });

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(store.url(), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase
        title="Welcome to Portal"
        description="Access your account or apply for admission"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Tabs defaultValue="login" @update:modelValue="(v) => v === 'register' && router.visit(register().url)" class="w-full">
            <TabsList class="grid w-full grid-cols-2 mb-6" v-if="canRegister">
                <TabsTrigger value="login">Sign In</TabsTrigger>
                <TabsTrigger value="register">Register</TabsTrigger>
            </TabsList>
            <TabsContent value="login">
                <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-5">
                <div class="space-y-2">
                    <Label for="email" class="text-xs font-bold uppercase tracking-widest text-slate-500 ml-1">Account Access</Label>
                    <Input
                        id="email"
                        type="text"
                        name="email"
                        v-model="form.email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="username"
                        placeholder="Email or Matriculation Number"
                        class="h-12 border-slate-200 focus:border-primary focus:ring-primary/5 rounded-xl shadow-sm bg-slate-50/50"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between ml-1">
                        <Label for="password" class="text-xs font-bold uppercase tracking-widest text-slate-500">Security Key</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request().url"
                            class="text-[11px] font-bold text-primary hover:text-primary/70 transition-colors uppercase tracking-tight"
                            :tabindex="5"
                        >
                            Recover Password
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        v-model="form.password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="h-12 border-slate-200 focus:border-primary focus:ring-primary/5 rounded-xl shadow-sm bg-slate-50/50"
                        :error="form.errors.password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between px-1">
                    <Label for="remember" class="flex items-center space-x-2 text-xs font-bold text-slate-500 cursor-pointer group">
                        <Checkbox id="remember" name="remember" v-model:checked="form.remember" :tabindex="3" class="rounded border-slate-300 text-primary focus:ring-primary/20" />
                        <span class="group-hover:text-slate-700 transition-colors">Keep me signed in</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="w-full h-12 bg-slate-900 dark:bg-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100 rounded-xl font-bold shadow-xl shadow-slate-900/10 transition-all active:scale-[0.98] mt-2"
                    :tabindex="4"
                    :disabled="form.processing"
                    data-test="login-button"
                >
                    <Spinner v-if="form.processing" class="mr-2" />
                    Sign In to Portal
                </Button>
                    </div>
                </form>
            </TabsContent>
        </Tabs>
    </AuthBase>
</template>
