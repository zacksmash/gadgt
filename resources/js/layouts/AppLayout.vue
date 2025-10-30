<script setup lang="ts">
import { SidebarProvider } from '@/components/ui/sidebar'
import { ScrollArea } from '@/components/ui/scroll-area'
import AppSidebar from '@/components/AppSidebar.vue'
import AppHeader from '@/components/AppHeader.vue'
import { usePage } from '@inertiajs/vue3'

interface Props {
    variant?: 'header' | 'sidebar';
}

defineProps<Props>()

const isOpen: boolean = usePage().props.sidebarOpen as boolean
</script>

<template>
    <SidebarProvider :default-open="isOpen" class="bg-sidebar relative">
        <AppSidebar/>

        <main class="bg-sidebar relative flex flex-1 flex-col overflow-x-hidden">
            <AppHeader/>

            <slot/>
        </main>
    </SidebarProvider>
</template>
