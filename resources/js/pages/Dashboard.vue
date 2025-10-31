<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue'
import { Deferred, Link, router } from '@inertiajs/vue3'
import { ChevronRight, Cloud, TextCursorInput as PromptsIcon, RefreshCcw, Library as ResourcesIcon, Hammer as ToolsIcon } from 'lucide-vue-next'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item'
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const props = defineProps<{
    tools?: { result: { tools: Array<object> } },
    prompts?: { result: { prompts: Array<object> } },
    resources?: { result: { resources: Array<object> } },
}>()

const toolsCount = computed(() => props.tools?.result?.tools?.length ?? 0)
const promptsCount = computed(() => props.prompts?.result?.prompts?.length ?? 0)
const resourcesCount = computed(() => props.resources?.result?.resources?.length ?? 0)

const refreshing = ref(false)
const cancelToken = ref<(() => void) | null>(null)

const refreshData = () => {
    refreshing.value = true

    router.reload({
        only: ['tools', 'prompts', 'resources'],
        onCancelToken({ cancel }) {
            if (cancelToken.value) {
                cancelToken.value()
            }
            cancelToken.value = cancel
        },
        onFinish: () => {
            refreshing.value = false
        },
    })
}

const itemsList = computed(() => {
    return [
        {
            name: 'Tools',
            icon: ToolsIcon,
            count: toolsCount.value,
            href: '/tools',
        },
        {
            name: 'Prompts',
            icon: PromptsIcon,
            count: promptsCount.value,
            href: '/prompts',
        },
        {
            name: 'Resources',
            icon: ResourcesIcon,
            count: resourcesCount.value,
            href: '/resources',
        },
    ].filter(item => item.count > 0)
})

onUnmounted(() => {
    if (cancelToken.value) {
        cancelToken.value()
    }
})
</script>

<template>
    <div class="h-full p-4">
        <Deferred :data="['tools', 'prompts', 'resources']">
            <template #fallback>
                Getting data...
            </template>

            <div class="mx-auto w-full max-w-5xl">
                <Empty class="border border-dashed">
                    <EmptyHeader>
                        <EmptyMedia variant="icon" class="size-24">
                            <Cloud class="size-18"/>
                        </EmptyMedia>

                        <EmptyTitle>Current MCP Server</EmptyTitle>

                        <EmptyDescription>
                            Here are the available items you can manage. Click on any to get started.
                        </EmptyDescription>
                    </EmptyHeader>

                    <EmptyContent v-if="itemsList.length > 0">
                        <div class="flex w-full max-w-lg flex-col gap-4">
                            <div class="flex justify-end">
                                <Button
                                    variant="outline"
                                    size="icon"
                                    :disabled="refreshing"
                                    @click="refreshData"
                                >
                                    <RefreshCcw
                                        :class="[
                                            refreshing ? 'animate-spin' : '',
                                            'size-4'
                                        ]"
                                    />
                                </Button>
                            </div>

                            <template v-for="item in itemsList" :key="item.name">
                                <Link :href="item.href">
                                    <Item variant="outline" class="hover:border-ring/50 transition-colors">
                                        <ItemMedia variant="icon">
                                            <component :is="item.icon"/>
                                        </ItemMedia>

                                        <ItemContent>
                                            <ItemTitle>
                                                {{ item.name }} <Badge variant="outline">{{ item.count }}</Badge>
                                            </ItemTitle>
                                        </ItemContent>

                                        <ItemActions>
                                            <ChevronRight class="size-4"/>
                                        </ItemActions>
                                    </Item>
                                </Link>
                            </template>
                        </div>
                    </EmptyContent>

                    <EmptyContent v-else>
                        <div class="text-center">
                            <p class="mb-4">No items available to display.</p>
                        </div>
                    </EmptyContent>
                </Empty>
            </div>
        </Deferred>
    </div>
</template>
