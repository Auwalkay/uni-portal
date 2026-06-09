<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    BookOpen, Search, Filter, Globe, Download, 
    Bookmark, Calendar, AlertCircle, FileText, Info, 
    History, Clock, X, BadgeCheck, MessageSquarePlus
} from 'lucide-vue-next';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

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
    categories: Array<{ id: number; name: string }>;
    myLoans: Array<{
        id: number;
        status: string;
        borrowed_at: string | null;
        due_at: string | null;
        returned_at: string | null;
        user_notes: string | null;
        admin_notes: string | null;
        created_at: string;
        book: { 
            title: string; 
            author: string; 
            shelf_location: string | null;
            cover_image_path: string | null;
            category: { name: string }
        };
    }>;
    filters: {
        search?: string;
        category_id?: string;
        is_ebook?: string;
    };
}>();

const currentTab = ref('catalog');
const showRequestModal = ref(false);
const selectedBook = ref<any>(null);

const requestForm = useForm({
    book_id: '',
    user_notes: '',
});

const searchFilter = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category_id || '');
const ebookFilter = ref(props.filters.is_ebook || '');

const handleFilter = () => {
    router.get(
        '/admin/library/history',
        { 
            search: searchFilter.value, 
            category_id: categoryFilter.value,
            is_ebook: ebookFilter.value 
        },
        { preserveState: true, replace: true }
    );
};

const openRequestModal = (book: any) => {
    selectedBook.value = book;
    requestForm.book_id = book.id.toString();
    requestForm.user_notes = '';
    showRequestModal.value = true;
};

const submitRequest = () => {
    requestForm.post('/admin/library/history/request', {
        onSuccess: () => {
            showRequestModal.value = false;
            requestForm.reset();
        }
    });
};

const triggerDownload = (bookId: number) => {
    window.open(`/admin/library/books/${bookId}/download`, '_blank');
};
</script>

<template>
    <AdminLayout :breadcrumbs="[{ title: 'Library History', href: '/admin/library/history' }]">
        <Head title="Library Catalog & History" />

        <div class="flex flex-col min-h-[calc(100vh-4rem)] bg-background/50">
            <!-- Hero Header Section -->
            <div class="relative overflow-hidden border-b bg-background px-6 py-12 md:px-12 lg:py-16">
                <!-- Background Pattern -->
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(45%_45%_at_50%_50%,var(--primary-muted),transparent)] opacity-20"></div>
                <div class="absolute inset-0 -z-10 bg-[grid-line_1px_1px_rgba(0,0,0,0.05)] [mask-image:radial-gradient(ellipse_at_center,black,transparent)]"></div>

                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between max-w-[1600px] mx-auto">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <Badge variant="outline" class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold border-primary text-primary">University Library</Badge>
                        </div>
                        <h1 class="text-4xl font-extrabold tracking-tight lg:text-5xl">Digital & Physical Library</h1>
                        <p class="text-lg text-muted-foreground max-w-2xl">
                            Search our book catalog, access academic journals, and borrow physical resources in seconds.
                        </p>
                    </div>

                    <!-- Fast Tabs Trigger -->
                    <div class="flex items-center gap-3">
                        <div class="flex bg-muted p-1 rounded-lg">
                            <button 
                                @click="currentTab = 'catalog'"
                                :class="[
                                    'px-4 py-1.5 text-xs font-bold uppercase rounded-md transition-all',
                                    currentTab === 'catalog' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:text-foreground'
                                ]"
                            >
                                <BookOpen class="h-3.5 w-3.5 inline mr-1" /> Catalog
                            </button>
                            <button 
                                @click="currentTab = 'loans'"
                                :class="[
                                    'px-4 py-1.5 text-xs font-bold uppercase rounded-md transition-all',
                                    currentTab === 'loans' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:text-foreground'
                                ]"
                            >
                                <History class="h-3.5 w-3.5 inline mr-1" /> My Borrow History
                                <span v-if="myLoans.length > 0" class="ml-1 bg-primary/10 text-primary px-1.5 py-0.2 rounded text-[10px]">
                                    {{ myLoans.length }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <main class="flex-1 w-full p-6 md:p-12 max-w-[1600px] mx-auto">
                
                <!-- Catalog Tab Content -->
                <div v-if="currentTab === 'catalog'" class="space-y-6">
                    
                    <!-- Search Filters Grid -->
                    <div class="bg-card border p-5 rounded-2xl shadow-sm flex flex-col md:flex-row gap-4 items-center">
                        <div class="flex-1 w-full relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <input
                                v-model="searchFilter"
                                @input="handleFilter"
                                type="text"
                                placeholder="Search books by title, author, isbn..."
                                class="w-full pl-9 pr-4 py-2 border rounded-xl bg-background text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                        <div class="w-full md:w-64 relative">
                            <Filter class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <select
                                v-model="categoryFilter"
                                @change="handleFilter"
                                class="w-full pl-9 pr-4 py-2 border rounded-xl bg-background text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option value="">All Categories</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                        <div class="w-full md:w-48">
                            <select
                                v-model="ebookFilter"
                                @change="handleFilter"
                                class="w-full px-3 py-2 border rounded-xl bg-background text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option value="">All Formats</option>
                                <option value="false">Physical Books</option>
                                <option value="true">Digital E-Books</option>
                            </select>
                        </div>
                    </div>

                    <!-- Catalog Cards Grid -->
                    <div v-if="books.data.length > 0" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Card v-for="book in books.data" :key="book.id" class="overflow-hidden border-0 shadow-lg ring-1 ring-border flex flex-col group bg-card transition-all hover:shadow-xl hover:-translate-y-0.5">
                            <div class="relative aspect-[4/5] bg-muted/30 flex items-center justify-center border-b overflow-hidden">
                                <img v-if="book.cover_image_path" :src="`/storage/${book.cover_image_path}`" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" />
                                <div v-else class="flex flex-col items-center text-muted-foreground/30">
                                    <BookOpen class="h-16 w-16" />
                                    <span class="text-[10px] uppercase font-bold mt-2 tracking-widest text-muted-foreground/50">University Library</span>
                                </div>

                                <!-- Badge marker -->
                                <div class="absolute top-3 left-3">
                                    <Badge v-if="book.is_ebook" variant="default" class="bg-blue-650 hover:bg-blue-650 shadow border-0 px-2 py-0.5 gap-1 text-[10px] uppercase tracking-wider font-bold">
                                        <Globe class="h-3 w-3" /> E-Book
                                    </Badge>
                                    <Badge v-else variant="secondary" class="bg-background/90 backdrop-blur border text-[10px] uppercase tracking-wider font-bold">
                                        Physical
                                    </Badge>
                                </div>
                            </div>
                            
                            <CardHeader class="p-4 pb-0 flex-1">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] font-bold text-primary uppercase tracking-wider">{{ book.category?.name }}</span>
                                    <CardTitle class="text-base font-bold line-clamp-2 leading-tight group-hover:text-primary transition-colors">{{ book.title }}</CardTitle>
                                    <CardDescription class="text-xs text-muted-foreground font-semibold">By {{ book.author }}</CardDescription>
                                </div>
                            </CardHeader>

                            <CardContent class="p-4 pt-3 flex-1 flex flex-col justify-between">
                                <p class="text-xs text-muted-foreground line-clamp-3 leading-relaxed mb-4">
                                    {{ book.description || 'No description summary available. Please refer to library desks for detailed overview.' }}
                                </p>
                                
                                <div class="text-[10px] font-bold text-muted-foreground/80 space-y-1">
                                    <div v-if="book.isbn" class="flex justify-between border-t border-dashed pt-2">
                                        <span>ISBN:</span>
                                        <span class="font-mono">{{ book.isbn }}</span>
                                    </div>
                                    <div v-if="!book.is_ebook" class="flex justify-between border-t border-dashed pt-1">
                                        <span>Shelf Location:</span>
                                        <span class="text-foreground">{{ book.shelf_location || 'Rack Not Assigned' }}</span>
                                    </div>
                                    <div v-if="!book.is_ebook" class="flex justify-between border-t border-dashed pt-1">
                                        <span>Available Copies:</span>
                                        <span :class="book.available_copies > 0 ? 'text-green-600 font-bold' : 'text-destructive font-bold'">
                                            {{ book.available_copies }} left of {{ book.total_copies }}
                                        </span>
                                    </div>
                                </div>
                            </CardContent>

                            <CardFooter class="p-4 pt-0 border-t bg-muted/10">
                                <Button 
                                    v-if="book.is_ebook" 
                                    @click="triggerDownload(book.id)"
                                    class="w-full gap-2 shadow font-bold text-xs"
                                >
                                    <Download class="h-4 w-4" /> Download / Read Online
                                </Button>
                                <Button 
                                    v-else-if="book.available_copies > 0" 
                                    @click="openRequestModal(book)"
                                    variant="outline"
                                    class="w-full gap-2 border-primary/20 text-primary hover:bg-primary/5 font-bold text-xs"
                                >
                                    <Bookmark class="h-4 w-4" /> Request Borrow Copy
                                </Button>
                                <Button 
                                    v-else 
                                    disabled
                                    variant="secondary"
                                    class="w-full text-xs font-semibold"
                                >
                                    Temporarily Checked Out
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center p-20 rounded-[2rem] border-2 border-dashed border-border py-32 bg-card">
                        <div class="p-5 bg-muted rounded-full mb-4">
                            <BookOpen class="h-10 w-10 text-muted-foreground/30" />
                        </div>
                        <h3 class="text-xl font-bold text-muted-foreground">No Books Found</h3>
                        <p class="text-sm text-muted-foreground/60 max-w-sm text-center mt-1">Try adjusting your filters or query strings to find what you are looking for.</p>
                    </div>

                </div>

                <!-- My Loans Tab Content -->
                <div v-if="currentTab === 'loans'" class="space-y-6">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-2xl font-bold flex items-center gap-3">
                            <History class="h-6 w-6 text-primary" />
                            My Personal Loans
                        </h2>
                        <span class="text-xs text-muted-foreground font-semibold italic">Listing physical checkouts and historical activities</span>
                    </div>

                    <div v-if="myLoans.length > 0" class="space-y-4">
                        <Card v-for="loan in myLoans" :key="loan.id" class="border-0 shadow-lg ring-1 ring-border bg-card p-6 overflow-hidden relative">
                            <!-- Left status vertical line -->
                            <div :class="[
                                'absolute left-0 top-0 bottom-0 w-1.5',
                                loan.status === 'pending' ? 'bg-orange-500' : '',
                                loan.status === 'approved' || loan.status === 'active' ? 'bg-green-500' : '',
                                loan.status === 'overdue' ? 'bg-destructive' : '',
                                loan.status === 'returned' ? 'bg-muted-foreground/30' : '',
                                loan.status === 'rejected' ? 'bg-red-500' : ''
                            ]"></div>

                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pl-2">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-16 bg-muted/40 rounded border flex items-center justify-center flex-shrink-0">
                                        <img v-if="loan.book.cover_image_path" :src="`/storage/${loan.book.cover_image_path}`" class="object-cover w-full h-full" />
                                        <BookOpen v-else class="h-6 w-6 text-muted-foreground/40" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-primary uppercase tracking-wider">{{ loan.book.category.name }}</span>
                                            <Badge :variant="
                                                loan.status === 'pending' ? 'secondary' : 
                                                loan.status === 'approved' || loan.status === 'active' ? 'default' :
                                                loan.status === 'overdue' ? 'destructive' : 'outline'
                                            " class="px-2 py-0.2 rounded text-[10px] font-bold uppercase tracking-wider">
                                                {{ loan.status }}
                                            </Badge>
                                        </div>
                                        <h3 class="text-lg font-bold text-foreground mt-1 leading-snug">{{ loan.book.title }}</h3>
                                        <p class="text-xs text-muted-foreground font-semibold">By {{ loan.book.author }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-3 gap-6 text-xs font-semibold text-muted-foreground lg:border-l pl-0 lg:pl-8 flex-shrink-0">
                                    <div>
                                        <p class="text-[9px] uppercase tracking-wider text-muted-foreground/75 font-bold mb-1">Requested At</p>
                                        <p class="text-foreground flex items-center gap-1"><Clock class="h-3.5 w-3.5 text-primary" /> {{ new Date(loan.created_at).toLocaleDateString() }}</p>
                                    </div>
                                    <div v-if="loan.borrowed_at">
                                        <p class="text-[9px] uppercase tracking-wider text-muted-foreground/75 font-bold mb-1">Checked Out</p>
                                        <p class="text-foreground flex items-center gap-1"><Calendar class="h-3.5 w-3.5 text-primary" /> {{ new Date(loan.borrowed_at).toLocaleDateString() }}</p>
                                    </div>
                                    <div v-if="loan.due_at && loan.status !== 'returned'">
                                        <p class="text-[9px] uppercase tracking-wider text-muted-foreground/75 font-bold mb-1">Due Date</p>
                                        <p :class="loan.status === 'overdue' ? 'text-destructive flex items-center gap-1 font-bold animate-pulse' : 'text-foreground flex items-center gap-1'"><AlertCircle class="h-3.5 w-3.5" /> {{ new Date(loan.due_at).toLocaleDateString() }}</p>
                                    </div>
                                    <div v-if="loan.returned_at">
                                        <p class="text-[9px] uppercase tracking-wider text-muted-foreground/75 font-bold mb-1">Returned On</p>
                                        <p class="text-foreground flex items-center gap-1"><BadgeCheck class="h-3.5 w-3.5 text-green-650" /> {{ new Date(loan.returned_at).toLocaleDateString() }}</p>
                                    </div>
                                    <div v-if="!loan.borrowed_at && loan.book.shelf_location">
                                        <p class="text-[9px] uppercase tracking-wider text-muted-foreground/75 font-bold mb-1">Shelf Spot</p>
                                        <p class="text-foreground flex items-center gap-1"><Info class="h-3.5 w-3.5 text-primary" /> {{ loan.book.shelf_location }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Notes section -->
                            <div v-if="loan.user_notes || loan.admin_notes" class="mt-4 pt-3 border-t border-dashed flex flex-col gap-2 text-xs">
                                <div v-if="loan.user_notes" class="flex gap-2">
                                    <span class="text-muted-foreground font-bold">My Note:</span>
                                    <span class="text-foreground italic">"{{ loan.user_notes }}"</span>
                                </div>
                                <div v-if="loan.admin_notes" class="flex gap-2 p-2 bg-muted/40 rounded-lg border border-border/50">
                                    <span class="text-primary font-bold">Librarian Note:</span>
                                    <span class="text-foreground italic">"{{ loan.admin_notes }}"</span>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center p-20 rounded-[2rem] border-2 border-dashed border-border py-32 bg-card">
                        <div class="p-5 bg-muted rounded-full mb-4">
                            <History class="h-10 w-10 text-muted-foreground/30" />
                        </div>
                        <h3 class="text-xl font-bold text-muted-foreground">No Borrow Logs</h3>
                        <p class="text-sm text-muted-foreground/60 max-w-sm text-center mt-1">You have not submitted any book checkout requests yet.</p>
                    </div>
                </div>

            </main>
        </div>

        <!-- Request Modal -->
        <div v-if="showRequestModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-card border border-border rounded-xl shadow-xl max-w-md w-full overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center justify-between bg-muted/20">
                    <h3 class="text-lg font-bold text-foreground flex items-center gap-2">
                        <Bookmark class="h-5 w-5 text-primary" />
                        Confirm Borrow Request
                    </h3>
                    <button @click="showRequestModal = false" class="text-muted-foreground hover:text-foreground"><X class="h-5 w-5" /></button>
                </div>
                <form @submit.prevent="submitRequest" class="p-6 space-y-4">
                    <div class="p-4 bg-muted/40 rounded-xl border border-border/50 flex gap-3">
                        <div class="w-10 h-14 bg-background border rounded flex items-center justify-center flex-shrink-0">
                            <img v-if="selectedBook?.cover_image_path" :src="`/storage/${selectedBook.cover_image_path}`" class="object-cover w-full h-full" />
                            <BookOpen v-else class="h-5 w-5 text-muted-foreground/50" />
                        </div>
                        <div class="space-y-0.5">
                            <p class="text-sm font-bold text-foreground leading-tight">{{ selectedBook?.title }}</p>
                            <p class="text-xs text-muted-foreground font-semibold">By {{ selectedBook?.author }}</p>
                            <span class="inline-block text-[9px] font-bold text-primary uppercase tracking-widest mt-1">Shelf Spot: {{ selectedBook?.shelf_location || 'Not Specified' }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-muted-foreground uppercase tracking-wider mb-1">Add Note for Librarian (Optional)</label>
                        <textarea 
                            v-model="requestForm.user_notes" 
                            rows="3" 
                            placeholder="e.g. For class research paper reference..." 
                            class="w-full px-3 py-2 border rounded-lg bg-background text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t">
                        <Button type="button" variant="outline" @click="showRequestModal = false" class="font-bold text-xs">Cancel</Button>
                        <Button type="submit" :disabled="requestForm.processing" class="font-bold text-xs gap-1">
                            <MessageSquarePlus class="h-4 w-4" /> Submit Request
                        </Button>
                    </div>
                </form>
            </div>
        </div>

    </AdminLayout>
</template>
