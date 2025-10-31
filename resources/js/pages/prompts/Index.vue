<script setup>
import { computed, ref } from 'vue'
import { Deferred, router } from '@inertiajs/vue3'
import { Asterisk, MessageSquareText as OutputIcon, TextCursorInput as PromptsIcon } from 'lucide-vue-next'
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
import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

const props = defineProps({
    data: Object,
    result: Object,
})

const selectedPrompt = ref(null)
const promptArguments = ref({})
const promptResult = ref(null)

const prompts = computed(() => props.data?.result?.prompts || [])
const promptError = computed(() => {
    return promptResult.value?.error
        ? promptResult.value?.error
        : null
})

const selectPrompt = (prompt) => {
    selectedPrompt.value = prompt
}

const executePrompt = (name, params) => {
    router.reload({
        method: 'post',
        only: ['result'],
        data: {
            name,
            arguments: params,
        },
        onSuccess: (page) => {
            promptResult.value = page.props.result
        },
    })
}
</script>

<template>
    <div class="relative h-full">
        <Deferred data="data">
            <template #fallback>
                Loading Prompts...
            </template>

            <ResizablePanelGroup direction="horizontal" class="h-full">
                <ResizablePanel :default-size="25" class="h-full overflow-hidden">
                    <div class="p-4">
                        <ItemGroup class="gap-4">
                            <Item
                                v-for="prompt in prompts"
                                :key="prompt.name"
                                variant="outline"
                            >
                                <ItemContent>
                                    <ItemTitle>{{ prompt.name }}</ItemTitle>

                                    <ItemDescription>
                                        {{ prompt.description }}
                                    </ItemDescription>
                                </ItemContent>

                                <ItemActions>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="selectPrompt(prompt)"
                                    >
                                        Select Prompt
                                    </Button>
                                </ItemActions>
                            </Item>
                        </ItemGroup>
                    </div>
                </ResizablePanel>

                <ResizableHandle class="h-full" :with-handle="true"/>

                <ResizablePanel :default-size="75" class="h-full overflow-hidden">
                    <ResizablePanelGroup direction="horizontal" class="h-full">
                        <ResizablePanel :default-size="50" class="h-full overflow-hidden">
                            <Empty v-if="!selectedPrompt" class="p-4">
                                <EmptyMedia>
                                    <PromptsIcon class="text-ring size-24"/>
                                </EmptyMedia>

                                <EmptyContent>
                                    <EmptyHeader>
                                        <EmptyTitle>
                                            No Prompt Selected
                                        </EmptyTitle>
                                    </EmptyHeader>

                                    <EmptyDescription>
                                        Select a prompt to see its details here.
                                    </EmptyDescription>
                                </EmptyContent>
                            </Empty>

                            <div v-else class="relative isolate">
                                <form class="divide-y" @submit.prevent="executePrompt(selectedPrompt.name, promptArguments)">
                                    <div
                                        :class="[
                                            promptError ? 'bg-red-500 dark:bg-red-800' : 'bg-accent',
                                            'sticky top-0 left-0 z-10 flex h-14 w-full items-center justify-between gap-4 px-4'
                                        ]"
                                    >
                                        <span class="leading-none font-semibold tracking-tight">
                                            {{ selectedPrompt.name }}
                                        </span>

                                        <Button
                                            size="sm"
                                            :disabled="!selectedPrompt"
                                            type="submit"
                                        >
                                            Send
                                        </Button>
                                    </div>

                                    <div class="p-4">
                                        <p class="text-muted-foreground line-clamp-2 text-sm leading-normal font-normal text-balance">
                                            {{ selectedPrompt.description }}
                                        </p>

                                        <p v-if="promptError" class="mt-4 line-clamp-2 text-sm leading-normal font-normal text-balance text-rose-500">
                                            {{ promptError.message ?? 'Fill out all required arguments' }}
                                        </p>

                                        <div class="divide-y py-4 first:pt-0 last:pb-0">
                                            <FormField
                                                v-for="argument in selectedPrompt.arguments"
                                                :key="argument.id"
                                                :name="argument.name"
                                            >
                                                <FormItem class="py-4 first:pt-0 last:pb-0">
                                                    <FormLabel>
                                                        <span class="flex items-center gap-px">
                                                            {{ argument.name }}
                                                            <span v-if="argument.required" class="text-4xl text-red-500">
                                                                <Asterisk class="size-3" title="Required"/>
                                                            </span>
                                                        </span>
                                                    </FormLabel>

                                                    <FormControl>
                                                        <Input
                                                            v-model="promptArguments[argument.name]"
                                                            :placeholder="argument.placeholder || ''"
                                                        />
                                                    </FormControl>

                                                    <FormDescription>
                                                        {{ argument.description }}
                                                    </FormDescription>

                                                    <FormMessage>
                                                        Please enter a valid {{ argument.name }}.
                                                    </FormMessage>
                                                </FormItem>
                                            </FormField>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </ResizablePanel>

                        <ResizableHandle class="h-full" :with-handle="true"/>

                        <ResizablePanel :default-size="50" class="h-full overflow-hidden">
                            <Empty v-if="(!promptResult || promptError)" class="p-4">
                                <EmptyMedia>
                                    <OutputIcon class="text-ring size-24"/>
                                </EmptyMedia>

                                <EmptyContent>
                                    <EmptyHeader>
                                        <EmptyTitle>
                                            No Prompt Result
                                        </EmptyTitle>
                                    </EmptyHeader>

                                    <EmptyDescription>
                                        Send a prompt to see the output here.
                                    </EmptyDescription>
                                </EmptyContent>
                            </Empty>

                            <div v-else class="relative isolate divide-y">
                                <div class="bg-accent sticky top-0 left-0 z-10 flex h-14 w-full items-center px-4">
                                    <span class="leading-none font-semibold tracking-tight">
                                        Prompt Result
                                    </span>
                                </div>

                                <div class="mx-auto flex w-full max-w-lg flex-col gap-6 p-4">
                                    <Item variant="outline">
                                        <ItemMedia variant="icon">
                                            <OutputIcon/>
                                        </ItemMedia>

                                        <ItemContent>
                                            <ItemTitle class="flex items-center gap-2">
                                                {{ promptResult?.result?.messages?.[0]?.role }}

                                                <Badge variant="outline" class="text-xs">
                                                    {{ promptResult?.result?.messages?.[0]?.content?.type }}
                                                </Badge>
                                            </ItemTitle>

                                            <ItemDescription>
                                                {{ promptResult?.result?.messages?.[0]?.content?.text }}
                                            </ItemDescription>
                                        </ItemContent>
                                    </Item>
                                </div>
                            </div>
                        </ResizablePanel>
                    </ResizablePanelGroup>
                </ResizablePanel>
            </ResizablePanelGroup>
        </Deferred>
    </div>
</template>
