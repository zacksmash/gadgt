<script setup lang="ts">
import { SidebarTrigger } from '@/components/ui/sidebar'
import { useAppearance } from '@/composables/useAppearance'
import { Monitor, Moon, Sun } from 'lucide-vue-next'

const { appearance, updateAppearance } = useAppearance()

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const
</script>

<template>
    <header
        class="border-border bg-sidebar/50 sticky top-0 left-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b px-6 backdrop-blur transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex flex-1 items-center justify-between gap-2">
            <SidebarTrigger class="-ml-1"/>

            <div
                class="bg-accent inline-flex gap-1 rounded-lg p-1"
            >
                <button
                    v-for="{ value, Icon, label } in tabs"
                    :key="value"
                    :class="[
                        'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                        appearance === value && 'dark:bg-sidebar bg-background'
                    ]"
                    @click="updateAppearance(value)"
                >
                    <component :is="Icon" class="-ml-1 h-4 w-4"/>

                    <span class="ml-1.5 text-sm">{{ label }}</span>
                </button>
            </div>
        </div>
    </header>
</template>
