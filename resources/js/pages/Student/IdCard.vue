<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    student: Object,
});

const printCard = () => {
    window.print();
};
</script>

<template>
    <Head title="Student ID Card" />

    <div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-4 print:bg-white print:p-0">
        <div class="mb-6 flex gap-4 print:hidden">
             <button @click="printCard" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Print ID Card
            </button>
             <button @click="$inertia.visit(route('student.dashboard'))" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                Back to Dashboard
            </button>
        </div>

        <!-- ID Card Container -->
        <div class="w-[350px] h-[550px] bg-white rounded-xl shadow-xl overflow-hidden border border-gray-200 relative print:shadow-none print:border-0">
            <!-- Header Pattern -->
            <div class="h-32 bg-blue-900 relative overflow-hidden">
                <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
                <!-- Logo Placeholders -->
                 <div class="absolute inset-0 flex flex-col items-center justify-center text-white">
                    <div class="h-12 w-12 bg-white/20 rounded-full flex items-center justify-center mb-1 backdrop-blur-sm">
                        <span class="font-bold text-xl">UP</span>
                    </div>
                    <h1 class="text-sm font-bold uppercase tracking-widest text-blue-100">University Portal</h1>
                </div>
            </div>

            <!-- Profile Photo -->
            <div class="absolute top-20 left-1/2 transform -translate-x-1/2">
                <div class="h-32 w-32 rounded-full border-4 border-white shadow-lg overflow-hidden bg-gray-100">
                    <img 
                        :src="`/storage/${student.passport_photo_path}`" 
                        class="h-full w-full object-cover"
                        alt="Student Photo"
                    />
                </div>
            </div>

            <!-- Content -->
            <div class="pt-24 pb-8 px-6 text-center">
                <h2 class="text-2xl font-bold text-gray-900 uppercase">{{ student.user.name }}</h2>
                <div class="text-blue-600 font-semibold mt-1">{{ student.matriculation_number }}</div>
                
                <div class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500">Department</span>
                        <span class="font-medium text-gray-900">{{ student.academic_department?.name || 'N/A' }}</span>
                    </div>
                     <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500">Faculty</span>
                        <span class="font-medium text-gray-900">{{ student.academic_department?.faculty?.name || 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500">Level</span>
                        <span class="font-medium text-gray-900">{{ student.current_level }}L</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                         <span class="text-gray-500">Blood Group</span>
                         <span class="font-medium text-gray-900">{{ student.blood_group || 'N/A' }}</span>
                    </div>
                </div>

                <div class="mt-8">
                     <div class="inline-block px-6 py-2 bg-blue-50 text-blue-700 rounded-full text-xs font-bold uppercase tracking-wider border border-blue-100">
                        Student
                    </div>
                </div>
            </div>

            <!-- Footer Bar -->
            <div class="absolute bottom-0 w-full h-4 bg-blue-600"></div>
        </div>
    </div>
</template>
