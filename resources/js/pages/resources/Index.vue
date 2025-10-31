<script lang="ts" setup>
import { computed } from 'vue'
import { Deferred, router } from '@inertiajs/vue3'
import JsonViewer from '@/components/JsonViewer.vue'
import { Library as ResourcesIcon } from 'lucide-vue-next'
import {
    ResizableHandle,
    ResizablePanel,
    ResizablePanelGroup,
} from '@/components/ui/resizable'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemGroup,
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
import { Button } from '@/components/ui/button'

const props = defineProps({
    data: Object,
    result: Object,
})

const resources = computed(() => props.data?.resources?.result?.resources || [])
const resourceResult = computed(() => props.result?.result || null)

const readResource = (name: string) => {
    router.reload({
        method: 'post',
        only: ['result'],
        data: { name },
    })
}
</script>

<template>
    <div class="relative h-full">
        <Deferred data="data">
            <template #fallback>
                Loading resources...
            </template>

            <ResizablePanelGroup direction="horizontal" class="h-full">
                <ResizablePanel :default-size="25" class="h-full overflow-auto">
                    <div class="p-4">
                        <ItemGroup class="gap-4">
                            <Item
                                v-for="resource in resources"
                                :key="resource.name"
                                variant="outline"
                            >
                                <ItemContent>
                                    <ItemTitle>{{ resource.name }}</ItemTitle>

                                    <ItemDescription>
                                        {{ resource.description }}
                                    </ItemDescription>
                                </ItemContent>

                                <ItemActions>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="readResource(resource.uri)"
                                    >
                                        Run Resource
                                    </Button>
                                </ItemActions>
                            </Item>
                        </ItemGroup>
                    </div>
                </ResizablePanel>

                <ResizableHandle class="h-full" :with-handle="true"/>

                <ResizablePanel :default-size="75" class="h-full overflow-hidden">
                    <div class="h-full overflow-auto">
                        <Empty v-if="!resourceResult" class="p-4">
                            <EmptyMedia>
                                <ResourcesIcon class="text-ring size-24"/>
                            </EmptyMedia>

                            <EmptyContent>
                                <EmptyHeader>
                                    <EmptyTitle>
                                        No Tool Selected
                                    </EmptyTitle>
                                </EmptyHeader>

                                <EmptyDescription>
                                    Select a tool to see its details here.
                                </EmptyDescription>
                            </EmptyContent>
                        </Empty>

                        <div v-else class="p-4">
                            <JsonViewer :data="resourceResult"/>
                        </div>
                    </div>
                </ResizablePanel>
            </ResizablePanelGroup>
        </Deferred>
    </div>
</template>
