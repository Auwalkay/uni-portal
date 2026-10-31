import { ref, computed, type ComputedRef, type Ref } from 'vue'

export function useBulkSelection<T extends { id: string }>(items: Ref<T[]> | ComputedRef<T[]>) {
    const selectedIds = ref<string[]>([])

    const currentPageIds = computed(() => {
        return (items.value || []).map(item => item.id)
    })

    const isAllSelected = computed(() => {
        if (!currentPageIds.value.length) return false
        return currentPageIds.value.every(id => selectedIds.value.includes(id))
    })

    const isSomeSelected = computed(() => {
        if (!currentPageIds.value.length) return false
        return currentPageIds.value.some(id => selectedIds.value.includes(id)) && !isAllSelected.value
    })

    const toggleSelectAll = (checked: boolean | 'indeterminate') => {
        if (checked === true) {
            const merged = new Set([...selectedIds.value, ...currentPageIds.value])
            selectedIds.value = Array.from(merged)
        } else {
            selectedIds.value = selectedIds.value.filter(id => !currentPageIds.value.includes(id))
        }
    }

    const toggleSelectOne = (id: string, checked: boolean | 'indeterminate') => {
        if (checked === true) {
            if (!selectedIds.value.includes(id)) {
                selectedIds.value.push(id)
            }
        } else {
            selectedIds.value = selectedIds.value.filter(item => item !== id)
        }
    }

    const clearSelection = () => {
        selectedIds.value = []
    }

    return {
        selectedIds,
        currentPageIds,
        isAllSelected,
        isSomeSelected,
        toggleSelectAll,
        toggleSelectOne,
        clearSelection,
    }
}
