<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { FolderTree, Plus, X, Tag, Layers, Package, Sparkles } from 'lucide-vue-next';

const props = defineProps<{
    categories: Array<{
        id: number;
        name: string;
        description?: string;
        items_count?: number;
    }>;
    permissions?: {
        can_manage?: boolean;
        can_create?: boolean;
    };
}>();

const stats = computed(() => {
    const list = props.categories || [];
    const totalItems = list.reduce((acc, curr) => acc + (curr.items_count || 0), 0);
    return {
        totalCategories: list.length,
        totalItems,
    };
});

const showCategoryModal = ref(false);

const categoryForm = useForm({
    name: '',
    description: '',
});

const submitCategory = () => {
    categoryForm.post(route('admin.inventory.categories.store'), {
        onSuccess: () => {
            showCategoryModal.value = false;
            categoryForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Store Categories — Central Store" />

    <AdminLayout>
        <div class="py-8 min-h-screen bg-slate-50/50 dark:bg-slate-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- Hero Section -->
                <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-2xl border border-slate-800">
                    <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-semibold">
                                <FolderTree class="w-3.5 h-3.5 text-indigo-400" />
                                Store Classification Taxonomy
                            </div>
                            <h1 class="text-3xl sm:text-4xl font-black tracking-tight">
                                Store Categories Directory
                            </h1>
                            <p class="text-slate-300 text-sm max-w-2xl leading-relaxed">
                                Classify university store supplies into structured categories (Consumables, Electronics & IT, Furniture, Farm Tools, Medical).
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <button
                                v-if="permissions?.can_create || true"
                                @click="showCategoryModal = true"
                                class="inline-flex items-center px-5 py-3 rounded-2xl text-xs font-extrabold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 shadow-xl shadow-indigo-500/20 active:scale-95 transition-all border border-indigo-400/30 gap-2"
                            >
                                <Plus class="w-4 h-4" />
                                Add New Store Category
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Registered Categories</p>
                                <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ stats.totalCategories }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold">
                                <FolderTree class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Categorized Items</p>
                                <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.totalItems }}</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold">
                                <Package class="w-6 h-6" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Taxonomy Status</p>
                                <p class="text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1">Active</p>
                            </div>
                            <div class="h-12 w-12 rounded-xl bg-purple-50 dark:bg-purple-950/50 flex items-center justify-center text-purple-600 dark:text-purple-400 font-bold">
                                <Layers class="w-6 h-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datatable -->
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-700/80 overflow-hidden">
                    <div class="p-6 border-b border-slate-200/80 dark:border-slate-700/80 flex justify-between items-center">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white text-base">Store Categories Directory</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Manage store categories and item count statistics.</p>
                        </div>
                        <span class="bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 py-1.5 px-4 rounded-full text-xs font-extrabold border border-indigo-200 dark:border-indigo-800">
                            {{ categories.length }} Categories Listed
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50/80 dark:bg-slate-900/80">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Category Name</th>
                                    <th class="px-6 py-4 text-left text-xs font-extrabold text-slate-500 uppercase">Description</th>
                                    <th class="px-6 py-4 text-right text-xs font-extrabold text-slate-500 uppercase">Registered Items</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <tr v-for="cat in categories" :key="cat.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/40 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0 border border-indigo-200 dark:border-indigo-800">
                                                <Tag class="w-4 h-4" />
                                            </div>
                                            <span class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm">{{ cat.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500 font-medium">
                                        {{ cat.description || 'No specific description provided.' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-black text-indigo-600 dark:text-indigo-400">
                                        <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/60 rounded-xl border border-indigo-200 dark:border-indigo-800">
                                            {{ cat.items_count || 0 }} Items
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!categories || categories.length === 0">
                                    <td colspan="3" class="px-6 py-12 text-center text-slate-400 text-xs">
                                        No categories created yet. Click "Add New Store Category" above.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- CREATE CATEGORY MODAL -->
        <div v-if="showCategoryModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Create New Category</h3>
                    <button @click="showCategoryModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>

                <form @submit.prevent="submitCategory" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Category Name *</label>
                        <input v-model="categoryForm.name" required type="text" placeholder="e.g. Farm Tools, Computers & IT, Consumables" class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                        <textarea v-model="categoryForm.description" rows="3" placeholder="Category classification note..." class="w-full p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="showCategoryModal = false" class="px-4 py-2 font-bold text-slate-500">Cancel</button>
                        <button type="submit" :disabled="categoryForm.processing" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold shadow-lg hover:bg-indigo-500">Save Category</button>
                    </div>
                </form>
            </div>
        </div>

    </AdminLayout>
</template>
