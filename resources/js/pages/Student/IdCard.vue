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
            <div class="h-32 bg-[#016634] relative overflow-hidden">
                <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
                <!-- Logo & Tenant Info -->
                 <div class="absolute inset-0 flex flex-col items-center justify-center text-white px-4">
                    <div v-if="$page.props.tenant.logo" class="h-14 w-14 bg-white rounded-full flex items-center justify-center mb-1 p-1 shadow-inner">
                        <img :src="$page.props.tenant.logo" class="h-full w-full object-contain" alt="Logo" />
                    </div>
                    <div v-else class="h-12 w-12 bg-white/20 rounded-full flex items-center justify-center mb-1 backdrop-blur-sm border border-white/30">
                        <span class="font-bold text-xl text-yellow-400">NB</span>
                    </div>
                    <h1 class="text-xs font-black uppercase tracking-widest text-white text-center leading-tight drop-shadow-md">
                        {{ $page.props.tenant.name }}
                    </h1>
                </div>
            </div>

            <!-- Content -->
            <div class="pt-24 pb-8 px-6 text-center bg-white/50 backdrop-blur-sm relative">
                <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight leading-none mb-1">{{ student.user.name }}</h2>
                <div class="text-[#016634] font-extrabold text-sm tracking-widest mb-4">{{ student.matriculation_number }}</div>
                
                <div class="mt-4 space-y-3 text-[11px] font-bold">
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-400 font-medium uppercase tracking-tighter">Department</span>
                        <span class="text-gray-800 uppercase">{{ student.academic_department?.name || 'N/A' }}</span>
                    </div>
                     <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-400 font-medium uppercase tracking-tighter">Faculty</span>
                        <span class="text-gray-800 uppercase">{{ student.academic_department?.faculty?.name || 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-400 font-medium uppercase tracking-tighter">Level</span>
                        <span class="text-gray-800">{{ student.current_level }}L</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                         <span class="text-gray-400 font-medium uppercase tracking-tighter">Blood Group</span>
                         <span class="text-red-600 font-black">{{ student.blood_group || 'N/A' }}</span>
                    </div>
                </div>

                <div class="mt-5 text-[9px] text-gray-400 uppercase font-semibold leading-tight px-2">
                    {{ $page.props.tenant.address }}
                </div>

                <div class="mt-4">
                     <div class="inline-block px-8 py-1.5 bg-[#016634] text-[#FFCC00] rounded-lg text-[10px] font-black uppercase tracking-[0.2em] border border-[#FFCC00]/30 shadow-sm">
                        Student
                    </div>
                </div>
            </div>

            <!-- Profile Photo -->
            <div class="absolute top-20 left-1/2 transform -translate-x-1/2 z-10">
                <div class="h-32 w-32 rounded-full border-4 border-white shadow-xl overflow-hidden bg-gray-50 ring-4 ring-[#FFCC00]/30 transition-all hover:scale-105 duration-300">
                    <img 
                        :src="$page.props.auth.user.avatar" 
                        class="h-full w-full object-cover"
                        alt="Student Photo"
                    />
                </div>
            </div>

            <!-- Footer Bar -->
            <div class="absolute bottom-0 w-full h-4 bg-[#FFCC00] border-t border-[#016634]/10"></div>
        </div>
    </div>
</template>
