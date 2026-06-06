<script setup lang="ts">
useForm;
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    BookOpen, Plus, Search, Trash2, Edit, Filter, 
    FolderTree, Check, FileText, Globe, AlertCircle, 
    Calendar, User, Bookmark, X, BookOpenCheck, Info
} from 'lucide-vue-next';

const props = defineProps<{
    books: {
        data: Array<{
            id: number;
            title: string;
            author: string;
            isbn: string | null;
            publisher: string | null;
            publish_year: number | null;
            is_ebook: boolean;
            ebook_file_path: string | null;
            ebook_url: string | null;
            ebook_download_url: string | null;
            total_copies: number;
            available_copies: number;
            shelf_location: string | null;
            description: string | null;
            cover_image_path: string | null;
            category: { id: number; name: string };
        }>;
        links: any;
        total: number;
    };
    categories: Array<{ 
        id: number; 
        name: string; 
        description: string | null;
        books_count?: number;
    }>;
    pendingLoans: Array<{
        id: number;
        status: string;
        user_notes: string | null;
        created_at: string;
        book: { title: string; author: string; shelf_location: string | null };
        user: { name: string; email: string };
    }>;
    activeLoans: Array<{
        id: number;
        status: string;
        borrowed_at: string;
        due_at: string;
        book: { title: string; author: string; shelf_location: string | null };
        user: { name: string; email: string };
    }>;
    stats: {
        total_books: number;
        ebooks_count: number;
        active_loans: number;
        overdue_loans: number;
        pending_requests: number;
    };
    filters: {
        search?: string;
        category_id?: string;
        is_ebook?: string;
    };
}>();

const currentTab = ref('books');
const showBookModal = ref(false);
const showCategoryModal = ref(false);
const showRejectModal = ref(false);
const editingBook = ref<any>(null);
const selectedLoan = ref<any>(null);

const bookForm = useForm({
    title: '',
    author: '',
    library_category_id: '',
    isbn: '',
    publisher: '',
    publish_year: '' as any,
    is_ebook: false,
    total_copies: 1 as any,
    shelf_location: '',
    description: '',
    cover_image: null as File | null,
    ebook_file: null as File | null,
    ebook_url: '',
});

const categoryForm = useForm({
    name: '',
    description: '',
});

const rejectForm = useForm({
    admin_notes: '',
});

const searchFilter = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category_id || '');
const ebookFilter = ref(props.filters.is_ebook || '');

const handleFilter = () => {
    router.get(
        '/admin/library',
        { 
            search: searchFilter.value, 
            category_id: categoryFilter.value,
            is_ebook: ebookFilter.value 
        },
        { preserveState: true, replace: true }
    );
};

const openAddModal = () => {
    editingBook.value = null;
    bookForm.reset();
    showBookModal.value = true;
};

const openEditModal = (book: any) => {
    editingBook.value = book;
    bookForm.title = book.title;
    bookForm.author = book.author;
    bookForm.library_category_id = book.library_category_id;
    bookForm.isbn = book.isbn || '';
    bookForm.publisher = book.publisher || '';
    bookForm.publish_year = book.publish_year || '';
    bookForm.is_ebook = book.is_ebook;
    bookForm.total_copies = book.total_copies;
    bookForm.shelf_location = book.shelf_location || '';
    bookForm.description = book.description || '';
    bookForm.ebook_url = book.ebook_url || '';
    bookForm.cover_image = null;
    bookForm.ebook_file = null;
    showBookModal.value = true;
};

const submitBook = () => {
    if (editingBook.value) {
        // Inertia file uploads require a POST request with _method field spoofing for updates
        bookForm.post(`/admin/library/books/${editingBook.value.id}`, {
            _method: 'put',
            forceFormData: true,
            onSuccess: () => {
                showBookModal.value = false;
                bookForm.reset();
            }
        } as any);
    } else {
        bookForm.post('/admin/library/books', {
            onSuccess: () => {
                showBookModal.value = false;
                bookForm.reset();
            }
        });
    }
};

const deleteBook = (id: number) => {
    if (confirm('Are you sure you want to remove this book from the catalog?')) {
        router.delete(`/admin/library/books/${id}`);
    }
};

const submitCategory = () => {
    categoryForm.post('/admin/library/categories', {
        onSuccess: () => {
            showCategoryModal.value = false;
            categoryForm.reset();
        }
    });
};

const approveRequest = (loanId: number) => {
    if (confirm('Are you sure you want to approve this borrow request?')) {
        router.post(`/admin/library/loans/${loanId}/approve`);
    }
};

const openRejectModal = (loan: any) => {
    selectedLoan.value = loan;
    rejectForm.admin_notes = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    rejectForm.post(`/admin/library/loans/${selectedLoan.value.id}/reject`, {
        onSuccess: () => {
            showRejectModal.value = false;
            selectedLoan.value = null;
        }
    });
};

const recordReturn = (loanId: number) => {
    if (confirm('Are you sure you want to record this book as returned?')) {
        router.post(`/admin/library/loans/${loanId}/return`);
    }
};
</script>

<template>
    <Head title="Library Management" />

    <AdminLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <BookOpen class="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                            Library Management
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Manage institutional book inventory, e-books downloads, and loan requests.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="showCategoryModal = true"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none"
                        >
                            <FolderTree class="h-4 w-4 mr-2 text-green-600" />
                            Add Category
                        </button>
                        <button
                            @click="openAddModal"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <Plus class="h-4 w-4 mr-2" />
                            Add Book
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Catalog Books</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total_books }}</p>
                        </div>
                        <BookOpen class="h-8 w-8 text-indigo-600" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Digital E-Books</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.ebooks_count }}</p>
                        </div>
                        <Globe class="h-8 w-8 text-blue-600" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Active Loans</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.active_loans }}</p>
                        </div>
                        <BookOpenCheck class="h-8 w-8 text-green-600" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Overdue Items</p>
                            <p class="text-xl font-bold text-red-600 mt-1">{{ stats.overdue_loans }}</p>
                        </div>
                        <AlertCircle class="h-8 w-8 text-red-600" />
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Pending Requests</p>
                            <p class="text-xl font-bold text-orange-600 mt-1">{{ stats.pending_requests }}</p>
                        </div>
                        <Bookmark class="h-8 w-8 text-orange-500" />
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            @click="currentTab = 'books'"
                            :class="[
                                currentTab === 'books'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                            ]"
                        >
                            <BookOpen class="h-4 w-4" />
                            Books Catalog
                            <span class="ml-2 bg-gray-100 dark:bg-gray-750 text-gray-600 dark:text-gray-300 py-0.5 px-2 rounded-full text-xs">
                                {{ books.total }}
                            </span>
                        </button>
                        <button
                            @click="currentTab = 'categories'"
                            :class="[
                                currentTab === 'categories'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                            ]"
                        >
                            <FolderTree class="h-4 w-4" />
                            Categories
                            <span class="ml-2 bg-gray-100 dark:bg-gray-750 text-gray-600 dark:text-gray-300 py-0.5 px-2 rounded-full text-xs">
                                {{ categories.length }}
                            </span>
                        </button>
                        <button
                            @click="currentTab = 'requests'"
                            :class="[
                                currentTab === 'requests'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                            ]"
                        >
                            <Bookmark class="h-4 w-4" />
                            Pending Requests
                            <span v-if="pendingLoans.length > 0" class="ml-2 bg-orange-100 text-orange-850 dark:bg-orange-950 dark:text-orange-300 py-0.5 px-2 rounded-full text-xs font-bold">
                                {{ pendingLoans.length }}
                            </span>
                        </button>
                        <button
                            @click="currentTab = 'loans'"
                            :class="[
                                currentTab === 'loans'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2'
                            ]"
                        >
                            <BookOpenCheck class="h-4 w-4" />
                            Active Checkouts
                            <span class="ml-2 bg-gray-100 dark:bg-gray-750 text-gray-600 dark:text-gray-300 py-0.5 px-2 rounded-full text-xs">
                                {{ activeLoans.length }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Books Tab -->
                <div v-if="currentTab === 'books'">
                    <!-- Filters -->
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                v-model="searchFilter"
                                @input="handleFilter"
                                type="text"
                                placeholder="Search by title, author, or ISBN..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-250 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-900 dark:text-white"
                            />
                        </div>
                        <div class="w-full md:w-64 relative">
                            <Filter class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <select
                                v-model="categoryFilter"
                                @change="handleFilter"
                                class="w-full pl-10 pr-4 py-2 border border-gray-250 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-900 dark:text-white"
                            >
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        <div class="w-full md:w-48 relative">
                            <select
                                v-model="ebookFilter"
                                @change="handleFilter"
                                class="w-full px-3 py-2 border border-gray-250 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-900 dark:text-white"
                            >
                                <option value="">All Formats</option>
                                <option value="false">Physical Books</option>
                                <option value="true">Digital E-Books</option>
                            </select>
                        </div>
                    </div>

                    <!-- Book Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Book Detail</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ISBN</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type / Location</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Copies Available</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="book in books.data" :key="book.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-14 bg-gray-100 dark:bg-gray-900 rounded border overflow-hidden flex items-center justify-center flex-shrink-0">
                                                    <img v-if="book.cover_image_path" :src="`/storage/${book.cover_image_path}`" class="object-cover w-full h-full" />
                                                    <BookOpen v-else class="h-6 w-6 text-gray-400" />
                                                </div>
                                                <div>
                                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ book.title }}</div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">By {{ book.author }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ book.category?.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ book.isbn || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span v-if="book.is_ebook" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                                <Globe class="h-3 w-3 mr-1" /> E-Book
                                            </span>
                                            <span v-else class="text-gray-700 dark:text-gray-300">
                                                {{ book.shelf_location || 'Not Specified' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <span v-if="book.is_ebook" class="text-gray-400">Unlimited</span>
                                            <span v-else :class="book.available_copies > 0 ? 'text-green-600 font-semibold' : 'text-red-500'">
                                                {{ book.available_copies }} / {{ book.total_copies }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="openEditModal(book)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                                <Edit class="h-5 w-5" />
                                            </button>
                                            <button @click="deleteBook(book.id)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                <Trash2 class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="books.data.length === 0">
                                        <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                            No books found in catalog.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Categories Tab -->
                <div v-if="currentTab === 'categories'">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Book Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ cat.name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ cat.description || 'No description provided' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-semibold">
                                        {{ cat.books_count ?? 0 }}
                                    </td>
                                </tr>
                                <tr v-if="categories.length === 0">
                                    <td colspan="3" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        No categories found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Requests Tab -->
                <div v-if="currentTab === 'requests'">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Book Requested</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Request Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User Notes</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="loan in pendingLoans" :key="loan.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white text-sm">{{ loan.user?.name }}</div>
                                        <div class="text-xs text-gray-500">{{ loan.user?.email }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ loan.book?.title }}</div>
                                        <div class="text-xs text-gray-500">Shelf: {{ loan.book?.shelf_location || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ new Date(loan.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                        {{ loan.user_notes || 'No note' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="approveRequest(loan.id)" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-4 inline-flex items-center gap-1">
                                            <Check class="h-4 w-4" /> Approve
                                        </button>
                                        <button @click="openRejectModal(loan)" class="text-red-650 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 inline-flex items-center gap-1">
                                            <X class="h-4 w-4" /> Reject
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="pendingLoans.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        No pending borrow requests.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Checkouts Tab -->
                <div v-if="currentTab === 'loans'">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Borrower</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Book Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Loan Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="loan in activeLoans" :key="loan.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white text-sm">{{ loan.user?.name }}</div>
                                        <div class="text-xs text-gray-550">{{ loan.user?.email }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ loan.book?.title }}</div>
                                        <div class="text-xs text-gray-500">Shelf: {{ loan.book?.shelf_location || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col">
                                            <span>Out: {{ loan.borrowed_at ? new Date(loan.borrowed_at).toLocaleDateString() : 'Pending' }}</span>
                                            <span>Due: {{ loan.due_at ? new Date(loan.due_at).toLocaleDateString() : 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'px-2 py-0.5 text-xs font-semibold rounded-full',
                                            loan.status === 'overdue' 
                                                ? 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-400' 
                                                : 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-400'
                                        ]">
                                            {{ loan.status.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="recordReturn(loan.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 inline-flex items-center gap-1">
                                            <Check class="h-4 w-4" /> Record Return
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="activeLoans.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        No active book checkouts.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Book Add/Edit Modal -->
        <div v-if="showBookModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b dark:border-gray-750 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ editingBook ? 'Edit Book Details' : 'Add Book to Catalog' }}
                    </h3>
                    <button @click="showBookModal = false" class="text-gray-400 hover:text-gray-600"><X class="h-6 w-6" /></button>
                </div>
                <form @submit.prevent="submitBook" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Book Title</label>
                            <input v-model="bookForm.title" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Author</label>
                            <input v-model="bookForm.author" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                            <select v-model="bookForm.library_category_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white">
                                <option value="">Select Category</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ISBN</label>
                            <input v-model="bookForm.isbn" type="text" placeholder="e.g. 978-3-16..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Publisher</label>
                            <input v-model="bookForm.publisher" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Publish Year</label>
                            <input v-model.number="bookForm.publish_year" type="number" min="1000" :max="new Date().getFullYear() + 1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Book Type Format</label>
                            <div class="flex items-center space-x-6 h-10">
                                <label class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <input type="radio" :value="false" v-model="bookForm.is_ebook" class="mr-2 h-4 w-4 text-indigo-650" />
                                    Physical Copy
                                </label>
                                <label class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <input type="radio" :value="true" v-model="bookForm.is_ebook" class="mr-2 h-4 w-4 text-indigo-650" />
                                    Digital E-Book
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- E-Book details conditional fields -->
                    <div v-if="bookForm.is_ebook" class="p-4 bg-blue-50 dark:bg-blue-950/20 rounded-lg border border-blue-100 dark:border-blue-900/40 space-y-3">
                        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 flex items-center gap-1">
                            <Info class="h-4 w-4" /> Digital Resource Details
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Upload Digital File (PDF/EPUB)</label>
                                <input type="file" @input="bookForm.ebook_file = ($event.target as HTMLInputElement).files?.[0] || null" accept=".pdf,.epub" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:hover:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Or Provide External URL Resource Link</label>
                                <input v-model="bookForm.ebook_url" type="url" placeholder="https://example.com/ebook-link" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white text-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- Physical details conditional fields -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-900/30 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Stock Copies</label>
                            <input v-model.number="bookForm.total_copies" type="number" min="0" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shelf Location Library Rack</label>
                            <input v-model="bookForm.shelf_location" type="text" placeholder="e.g. Rack A-4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Upload Cover Artwork Image</label>
                            <input type="file" @input="bookForm.cover_image = ($event.target as HTMLInputElement).files?.[0] || null" accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:hover:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-400" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Book Description Summary</label>
                        <textarea v-model="bookForm.description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t dark:border-gray-750">
                        <button type="button" @click="showBookModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="bookForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50">
                            {{ editingBook ? 'Update Book' : 'Save Book' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Category Add Modal -->
        <div v-if="showCategoryModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b dark:border-gray-750 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Add New Book Category</h3>
                    <button @click="showCategoryModal = false" class="text-gray-400 hover:text-gray-600"><X class="h-6 w-6" /></button>
                </div>
                <form @submit.prevent="submitCategory" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category Name</label>
                        <input v-model="categoryForm.name" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea v-model="categoryForm.description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showCategoryModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="categoryForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg disabled:opacity-50">Save Category</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Notes Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b dark:border-gray-750 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Reject Borrow Request</h3>
                    <button @click="showRejectModal = false" class="text-gray-400 hover:text-gray-600"><X class="h-6 w-6" /></button>
                </div>
                <form @submit.prevent="submitReject" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rejection Reason</label>
                        <textarea v-model="rejectForm.admin_notes" rows="3" placeholder="Provide notes regarding the rejection..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showRejectModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="rejectForm.processing" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg disabled:opacity-50">Reject Request</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
