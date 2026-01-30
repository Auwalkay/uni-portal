<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    User, 
    GraduationCap, 
    Banknote,
    FileText
} from 'lucide-vue-next';

const props = defineProps({
    student: Object,
    academicHistory: Object,
    financialHistory: Object,
});

const activeTab = ref('overview');

const tabs = [
    { id: 'overview', name: 'Overview', icon: User },
    { id: 'academic', name: 'Academic Records', icon: GraduationCap },
    { id: 'finance', name: 'Financials', icon: Banknote },
];

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric'
    });
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'paid': return 'bg-green-100 text-green-800';
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'failed': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};
</script>

<template>
    <Head :title="`${student.user.name} - Details`" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Student Profile
                </h2>
                <Link :href="route('admin.students.index')" class="text-sm text-blue-600 hover:text-blue-900">
                    &larr; Back to Students
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                
                <!-- Profile Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200 flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                        <img class="h-24 w-24 rounded-full ring-4 ring-gray-50" :src="`https://ui-avatars.com/api/?name=${student.user.name}&size=128`" alt="" />
                        
                        <div class="text-center md:text-left flex-1">
                            <h1 class="text-2xl font-bold text-gray-900">{{ student.user.name }}</h1>
                            <p class="text-gray-500">{{ student.department || 'No Department' }} &bull; {{ student.current_level }} Level</p>
                            
                            <div class="mt-4 flex flex-wrap justify-center md:justify-start gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ student.matriculation_number }}
                                </span>
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ student.program || 'N/A' }}
                                </span>
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Enrolled: {{ formatDate(student.created_at) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2">
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition">
                                Edit Profile
                            </button>
                             <button class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md text-sm hover:bg-gray-50 transition">
                                Reset Password
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mb-6 border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm'
                            ]"
                        >
                            <component :is="tab.icon" class="-ml-0.5 mr-2 h-5 w-5" :class="activeTab === tab.id ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500'" aria-hidden="true" />
                            <span>{{ tab.name }}</span>
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Overview Tab -->
                    <div v-show="activeTab === 'overview'" class="lg:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ student.user.email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ student.phone_number || 'N/A' }}</dd>
                                </div>
                                 <div class="sm:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ student.address || 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="bg-white shadow rounded-lg p-6">
                             <h3 class="text-lg font-medium text-gray-900 mb-4">Admissions Info</h3>
                             <dl class="grid grid-cols-1 gap-x-4 gap-y-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Entry Mode</dt>
                                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ student.entry_mode || 'UTME' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Admitted Session</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ student.admitted_session?.name || 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Faculty</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ student.faculty || 'N/A' }}</dd>
                                </div>
                             </dl>
                        </div>
                    </div>

                    <!-- Academic Tab -->
                    <div v-show="activeTab === 'academic'" class="lg:col-span-3 space-y-6">
                        <div v-if="Object.keys(academicHistory || {}).length === 0" class="bg-white shadow rounded-lg p-12 text-center text-gray-500">
                             <AcademicCapIcon class="mx-auto h-12 w-12 text-gray-400" />
                             <h3 class="mt-2 text-sm font-medium text-gray-900">No Course Registrations</h3>
                             <p class="mt-1 text-sm text-gray-500">Student has not registered for any courses yet.</p>
                        </div>

                        <div v-else v-for="(sessionRegs, sessionName) in academicHistory" :key="sessionName" class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Session ID: {{ sessionName }}</h3> 
                                <!-- Note: Ideally fetch session name, currently using ID due to grouping logic -->
                            </div>
                            <ul class="divide-y divide-gray-200">
                                <li v-for="reg in sessionRegs" :key="reg.id" class="px-6 py-4 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ reg.course.code }} - {{ reg.course.title }}</p>
                                        <p class="text-xs text-gray-500">{{ reg.course.units }} Units</p>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ reg.score || '-' }} / {{ reg.grade || '-' }}
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Finance Tab -->
                    <div v-show="activeTab === 'finance'" class="lg:col-span-3 space-y-6">
                        <!-- Invoices -->
                         <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900">Invoices</h3>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="invoice in financialHistory.invoices" :key="invoice.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">{{ invoice.reference }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ invoice.type.replace('_', ' ') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">₦{{ invoice.amount?.toLocaleString() }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusBadgeClass(invoice.status)">
                                                {{ invoice.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(invoice.created_at) }}</td>
                                    </tr>
                                    <tr v-if="financialHistory.invoices.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No invoices found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                         <!-- Payments -->
                        <div class="bg-white shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-lg font-medium text-gray-900">Payment History</h3>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="payment in financialHistory.payments" :key="payment.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">{{ payment.reference }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">₦{{ payment.amount?.toLocaleString() }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ payment.channel || 'Card' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getStatusBadgeClass(payment.status)">
                                                {{ payment.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(payment.paid_at) }}</td>
                                    </tr>
                                    <tr v-if="financialHistory.payments.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No payments found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
