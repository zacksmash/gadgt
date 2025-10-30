<script setup lang="ts">
import { SidebarTrigger } from '@/components/ui/sidebar'
</script>

<template>
    <header
        class="border-border bg-sidebar/50 sticky top-0 left-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b px-6 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1"/>
        </div>
    </header>
</template>
