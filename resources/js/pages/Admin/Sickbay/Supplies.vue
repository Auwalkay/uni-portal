<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Activity, Plus, Bed, BookOpen, Clock, Heart, Search, FileText, AlertCircle, X
} from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    supplies: {
        data: Array<{
            id: number;
            name: string;
            category: string;
            stock_quantity: number;
            alert_threshold: number;
            expiry_date: string | null;
        }>;
        links: any;
        total: number;
    };
    stats: {
        low_stock_count: number;
    };
}>();

const showInventoryModal = ref(false);

const inventoryForm = useForm({
    name: '',
    category: 'OTC Drug',
    stock_quantity: 0 as any,
    alert_threshold: 5 as any,
    expiry_date: '',
});

const submitInventory = () => {
    inventoryForm.post('/admin/sickbay/inventory', {
        onSuccess: () => {
            showInventoryModal.value = false;
            inventoryForm.reset();
        }
    });
};
</script>

<template>
    <Head title="Supplies Ledger" />

    <AdminLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4 border-b pb-5">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2.5">
                            <Plus class="h-8 w-8 text-indigo-650 dark:text-indigo-400" />
                            Supplies & Medication Ledger
                        </h1>
                        <p class="text-sm text-gray-550 dark:text-gray-400 mt-1">
                            Browse medical supplies inventory, check low-stock metrics, and register first-aid item stock levels.
                        </p>
                    </div>
                    <div>
                        <button
                            @click="showInventoryModal = true"
                            class="inline-flex items-center px-4 py-2.5 border border-transparent rounded-xl shadow-md text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition"
                        >
                            <Plus class="h-4.5 w-4.5 mr-2" />
                            Add Supply Item
                        </button>
                    </div>
                </div>

                <!-- Page Navigation Sub-menu -->
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <Link
                            href="/admin/sickbay"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <Activity class="h-4.5 w-4.5" />
                            Active Queue
                        </Link>
                        <Link
                            href="/admin/sickbay/beds"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <Bed class="h-4.5 w-4.5" />
                            Beds Matrix
                        </Link>
                        <Link
                            href="/admin/sickbay/logs"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <BookOpen class="h-4.5 w-4.5" />
                            Treatment Logs
                        </Link>
                        <Link
                            href="/admin/sickbay/supplies"
                            class="border-indigo-500 text-indigo-600 dark:text-indigo-400 whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2"
                        >
                            <Plus class="h-4.5 w-4.5" />
                            Supplies Ledger
                        </Link>
                        <Link
                            href="/admin/sickbay/patients"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <Search class="h-4.5 w-4.5" />
                            Patient Search
                        </Link>
                        <Link
                            href="/admin/sickbay/reports"
                            class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-350 whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm flex items-center gap-2"
                        >
                            <FileText class="h-4.5 w-4.5" />
                            Reports & Stats
                        </Link>
                    </nav>
                </div>

                <!-- Stock Alerts Callout -->
                <div v-if="stats.low_stock_count > 0" class="p-4 mb-6 bg-red-50 dark:bg-red-950/20 border border-red-150 dark:border-red-900/30 rounded-xl flex gap-3 text-sm text-red-650 dark:text-red-300 items-start">
                    <AlertCircle class="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" />
                    <div>
                        <span class="font-bold">Inventory Refills Required!</span>
                        <p class="text-xs text-muted-foreground mt-1">
                            There are currently {{ stats.low_stock_count }} items in stock that have fallen below their minimum replenishment threshold alert levels.
                        </p>
                    </div>
                </div>

                <!-- Supplies Table -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alert Level</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in supplies.data" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                    {{ item.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-555 dark:text-gray-400 font-medium">
                                    {{ item.category }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold">
                                    <span :class="item.stock_quantity <= item.alert_threshold ? 'text-red-500' : 'text-gray-900 dark:text-white'">
                                        {{ item.stock_quantity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-semibold">
                                    Under {{ item.alert_threshold }} units
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.expiry_date ? new Date(item.expiry_date).toLocaleDateString() : 'N/A' }}
                                </td>
                            </tr>
                            <tr v-if="supplies.data.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400 font-medium">
                                    No first-aid items registered in stock.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <Pagination :links="supplies.links" />

            </div>
        </div>

        <!-- Add First Aid Item Modal -->
        <div v-if="showInventoryModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b dark:border-gray-750 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Add Supply / Medication</h3>
                    <button @click="showInventoryModal = false" class="text-gray-400 hover:text-gray-600"><X class="h-6 w-6" /></button>
                </div>
                <form @submit.prevent="submitInventory" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Supply Name</label>
                        <input v-model="inventoryForm.name" type="text" required placeholder="e.g. Paracetamol 500mg, Iodine Liquid" class="w-full px-3 py-2 border rounded-lg bg-background text-sm" />
                        <InputError :message="inventoryForm.errors.name" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Category</label>
                            <select v-model="inventoryForm.category" class="w-full px-3 py-2 border rounded-lg bg-background text-sm">
                                <option value="OTC Drug">OTC Drug</option>
                                <option value="First Aid Supply">First Aid Supply</option>
                                <option value="Equipment">Equipment / Accessory</option>
                            </select>
                            <InputError :message="inventoryForm.errors.category" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Expiry Date (Optional)</label>
                            <input v-model="inventoryForm.expiry_date" type="date" class="w-full px-3 py-2 border rounded-lg bg-background text-sm" />
                            <InputError :message="inventoryForm.errors.expiry_date" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Initial Qty</label>
                            <input v-model.number="inventoryForm.stock_quantity" type="number" min="0" required class="w-full px-3 py-2 border rounded-lg bg-background text-sm" />
                            <InputError :message="inventoryForm.errors.stock_quantity" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Low Stock Warning At</label>
                            <input v-model.number="inventoryForm.alert_threshold" type="number" min="0" required class="w-full px-3 py-2 border rounded-lg bg-background text-sm" />
                            <InputError :message="inventoryForm.errors.alert_threshold" class="mt-1" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t dark:border-gray-750">
                        <button type="button" @click="showInventoryModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="inventoryForm.processing" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">Save Supply</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
