<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Package, FileText, UserCheck, FolderTree, Activity } from 'lucide-vue-next';

defineProps<{
    activeTab: 'items' | 'requisitions' | 'assignments' | 'categories' | 'audit-logs';
}>();

const page = usePage();
</script>

<template>
    <div class="border-b border-slate-200 dark:border-slate-700/80 mb-6">
        <nav class="-mb-px flex space-x-2 sm:space-x-6 overflow-x-auto">
            <Link
                :href="route('admin.inventory.index')"
                :class="[
                    activeTab === 'items'
                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/30'
                        : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40',
                    'whitespace-nowrap py-3 px-3.5 rounded-t-xl border-b-2 text-xs sm:text-sm flex items-center gap-2 transition-all font-medium'
                ]"
            >
                <Package class="h-4 w-4 shrink-0" />
                <span>Store Items Directory</span>
            </Link>

            <Link
                :href="route('admin.inventory.requisitions.index')"
                :class="[
                    activeTab === 'requisitions'
                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/30'
                        : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40',
                    'whitespace-nowrap py-3 px-3.5 rounded-t-xl border-b-2 text-xs sm:text-sm flex items-center gap-2 transition-all font-medium'
                ]"
            >
                <FileText class="h-4 w-4 shrink-0" />
                <span>Store Requisitions (SIV)</span>
            </Link>

            <Link
                :href="route('admin.inventory.assignments.index')"
                :class="[
                    activeTab === 'assignments'
                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/30'
                        : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40',
                    'whitespace-nowrap py-3 px-3.5 rounded-t-xl border-b-2 text-xs sm:text-sm flex items-center gap-2 transition-all font-medium'
                ]"
            >
                <UserCheck class="h-4 w-4 shrink-0" />
                <span>Staff Equipment Assignments</span>
            </Link>

            <Link
                :href="route('admin.inventory.categories.index')"
                :class="[
                    activeTab === 'categories'
                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/30'
                        : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40',
                    'whitespace-nowrap py-3 px-3.5 rounded-t-xl border-b-2 text-xs sm:text-sm flex items-center gap-2 transition-all font-medium'
                ]"
            >
                <FolderTree class="h-4 w-4 shrink-0" />
                <span>Store Categories</span>
            </Link>

            <Link
                :href="route('admin.inventory.audit-logs.index')"
                :class="[
                    activeTab === 'audit-logs'
                        ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-950/30'
                        : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40',
                    'whitespace-nowrap py-3 px-3.5 rounded-t-xl border-b-2 text-xs sm:text-sm flex items-center gap-2 transition-all font-medium'
                ]"
            >
                <Activity class="h-4 w-4 shrink-0" />
                <span>Stock Audit Log</span>
            </Link>
        </nav>
    </div>
</template>
