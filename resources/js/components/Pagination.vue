<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';

defineProps<{
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}>();
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap justify-center gap-1 mt-6">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="mr-1 mb-1 px-4 py-2 text-sm leading-4 text-gray-400 border rounded"
                v-html="link.label"
            />
            <Link
                v-else
                class="mr-1 mb-1"
                :href="link.url"
            >
                <Button
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                >
                    <span v-html="link.label"></span>
                </Button>
            </Link>
        </template>
    </div>
</template>

