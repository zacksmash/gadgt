<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { Deferred, router } from '@inertiajs/vue3'
import VueJsonPretty from 'vue-json-pretty'
import 'vue-json-pretty/lib/styles.css'
import { Asterisk, ListCheck as DetailsIcon, Hammer as InputIcon, ServerCog as OutputIcon, Component as UiIcon } from 'lucide-vue-next'
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
import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

const props = defineProps({
    data: Object,
    result: Object,
})

const iframeRef = ref(null)
const tools = computed(() => props.data?.result?.tools || [])
const selectedTool = ref(null)
const toolResult = ref(null)
const resourceResult = ref(null)
const openAiSrc = ref(null)
const inputSchema = computed(() => {
    return selectedTool.value?.inputSchema?.properties
        ? Object.keys(selectedTool.value.inputSchema.properties).map((key) => ({
            name: key,
            ...selectedTool.value.inputSchema.properties[key],
        }))
        : null
})

const toolParams = ref({})

const isRequired = (input) => {
    return selectedTool.value?.inputSchema?.required?.includes(input.name)
}

const selectTool = (tool) => {
    selectedTool.value = tool
    openAiSrc.value = null
    toolResult.value = null
    toolParams.value = {}
}

const runTool = async (tool, params, fromWidget) => {
    toolResult.value = null

    router.reload({
        method: 'post',
        only: ['result'],
        data: {
            name: tool,
            params: params,
        },
        onSuccess: (page) => {
            toolResult.value = page.props.result

            // force a iframe load event
            if (iframeRef.value) {
                const event = new Event('load')
                iframeRef.value.dispatchEvent(event)
            }

            if (!fromWidget) {
                if (selectedTool.value?._meta?.['openai/outputTemplate']) {
                    fetchOpenAITemplate(selectedTool.value)
                }
            }
        },
    })
}

const fetchOpenAITemplate = (tool) => {
    router.reload({
        method: 'post',
        only: ['resource'],
        data: {
            name: tool._meta['openai/outputTemplate'],
            tool: {
                toolInput: {},
                toolOutput: toolResult.value?.result?.structuredContent || {},
                toolResponseMetadata: toolResult.value?.result?._meta || {},
                toolName: tool.name,
            },
        },
        onSuccess: (page) => {
            resourceResult.value = page.props.resource.result
            openAiSrc.value = page.props.resource.template

            nextTick(() => {
                if (iframeRef.value) {
                    // set the iframe height based on the content
                    const iframe = iframeRef.value

                    const resize = () => {
                        iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 4 + 'px'
                    }

                    iframe.addEventListener('load', resize)
                    new ResizeObserver(resize).observe(iframe.contentDocument.body)
                }
            })
        },
    })
}

const openAiWindowHandler = async ({ data }) => {
    switch (data.type) {
        case 'openai:callTool':
            if (runTool) {
                try {
                    await runTool(
                        data.toolName,
                        data.params || {},
                        true,
                    )

                    setTimeout(() => {
                        iframeRef.value?.contentWindow?.postMessage(
                            {
                                type: 'openai:callTool:response',
                                requestId: data.requestId,
                                result: JSON.parse(JSON.stringify(toolResult.value.result)),
                            },
                            '*',
                        )

                        setTimeout(() => {
                            if (iframeRef.value) {
                                const event = new Event('load')
                                iframeRef.value.dispatchEvent(event)
                            }
                        }, 10)
                    }, 500)
                } catch (err) {
                    iframeRef.value?.contentWindow?.postMessage(
                        {
                            type: 'openai:callTool:response',
                            requestId: data.requestId,
                            error: err instanceof Error ? err.message : 'Unknown error',
                        },
                        '*',
                    )
                }
            }
            break
    }
}

onMounted(() => {
    window.addEventListener('message', openAiWindowHandler)
})

onUnmounted(() => {
    window.removeEventListener('message', openAiWindowHandler)
    if (iframeRef.value) {
        iframeRef.value.removeEventListener('load', null)
    }
})
</script>

<template>
    <Deferred data="data">
        <template #fallback>
            Loading Tools...
        </template>

        <ResizablePanelGroup id="tool-group" direction="horizontal">
            <ResizablePanel
                id="tool-list-panel"
                :default-size="25"
            >
                <div class="flex flex-col gap-6 p-4">
                    <ItemGroup class="gap-4">
                        <Item
                            v-for="tool in tools"
                            :key="tool.name"
                            variant="outline"
                        >
                            <ItemContent>
                                <ItemTitle>{{ tool.name }}</ItemTitle>

                                <ItemDescription>
                                    {{ tool.description }}
                                </ItemDescription>
                            </ItemContent>

                            <ItemActions>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="selectTool(tool)"
                                >
                                    Select Tool
                                </Button>
                            </ItemActions>
                        </Item>
                    </ItemGroup>
                </div>
            </ResizablePanel>

            <ResizableHandle id="tool-group-handle" with-handle/>

            <ResizablePanel id="tool-details-panel" :default-size="75">
                <ResizablePanelGroup id="tool-info-group" direction="vertical">
                    <ResizablePanel
                        id="tool-info-panel"
                        :default-size="50"
                    >
                        <ResizablePanelGroup
                            id="tool-details-group"
                            direction="horizontal"
                            class="h-full"
                        >
                            <ResizablePanel id="tool-description-panel" :default-size="50">
                                <div class="h-full overflow-auto">
                                    <Empty v-if="!selectedTool" class="p-4">
                                        <EmptyMedia>
                                            <InputIcon class="text-ring size-24"/>
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

                                    <div v-else class="relative isolate divide-y">
                                        <div class="bg-accent sticky top-0 left-0 z-10 flex h-14 w-full items-center px-4">
                                            <span class="leading-none font-semibold tracking-tight">Tool Details</span>
                                        </div>

                                        <div class="p-4">
                                            <VueJsonPretty
                                                :data="selectedTool"
                                                :deep="2"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </ResizablePanel>

                            <ResizableHandle id="tool-description-handle" with-handle/>

                            <ResizablePanel id="tool-input-panel" :default-size="50">
                                <div class="h-full overflow-auto">
                                    <Empty v-if="!selectedTool" class="p-4">
                                        <EmptyMedia>
                                            <DetailsIcon class="text-ring size-24"/>
                                        </EmptyMedia>

                                        <EmptyContent>
                                            <EmptyHeader>
                                                <EmptyTitle>
                                                    No Tool Selected
                                                </EmptyTitle>
                                            </EmptyHeader>

                                            <EmptyDescription>
                                                When a tool is selected, its input parameters will be displayed here.
                                            </EmptyDescription>
                                        </EmptyContent>
                                    </Empty>

                                    <div v-else class="relative isolate divide-y">
                                        <div class="bg-accent sticky top-0 left-0 z-10 flex h-14 w-full items-center justify-between gap-4 px-4">
                                            <span class="leading-none font-semibold tracking-tight">Tool Input</span>

                                            <Button
                                                size="sm"
                                                :disabled="!selectedTool"
                                                @click="runTool(selectedTool.name, toolParams)"
                                            >
                                                Execute
                                            </Button>
                                        </div>

                                        <div class="p-4">
                                            <div
                                                v-for="input in inputSchema"
                                                :key="input.name"
                                                class="py-4"
                                            >
                                                <FormField :name="input.name">
                                                    <FormItem>
                                                        <FormLabel :for="input.name">
                                                            <div class="flex w-full items-center justify-between gap-2">
                                                                <span class="flex items-center gap-1">
                                                                    {{ input.name }}
                                                                    <span v-if="isRequired(input)" class="text-4xl text-red-500">
                                                                        <Asterisk class="size-3" title="Required"/>
                                                                    </span>
                                                                </span>

                                                                <Badge variant="outline">{{ input.type }}</Badge>
                                                            </div>
                                                        </FormLabel>

                                                        <FormControl>
                                                            <template v-if="input.type === 'array' || input.type === 'enum'">
                                                                <Select v-if="input?.items?.enum" v-model="toolParams[input.name]">
                                                                    <SelectTrigger class="w-full">
                                                                        <SelectValue placeholder="Choose an option"/>
                                                                    </SelectTrigger>

                                                                    <SelectContent>
                                                                        <SelectGroup>
                                                                            <SelectLabel>Options</SelectLabel>

                                                                            <SelectItem
                                                                                v-for="option in input.items.enum"
                                                                                :key="option"
                                                                                :value="option"
                                                                            >
                                                                                {{ option }}
                                                                            </SelectItem>
                                                                        </SelectGroup>
                                                                    </SelectContent>
                                                                </Select>

                                                                <template v-else>
                                                                    <code class="bg-muted ring-ring rounded-sm p-2 text-xs ring">
                                                                        <p>Options:</p>

                                                                        <br>
                                                                        {{ JSON.stringify(input.default) }}
                                                                    </code>

                                                                    <Textarea
                                                                        :id="input.name"
                                                                        v-model="toolParams[input.name]"
                                                                        rows="4"
                                                                        placeholder="Enter a JSON array"
                                                                        @vue:mounted="toolParams[input.name] = `[  ]`"
                                                                    />
                                                                </template>
                                                            </template>

                                                            <template v-else-if="input.type === 'boolean'">
                                                                <div class="flex items-center gap-2">
                                                                    <Switch
                                                                        :id="input.name"
                                                                        v-model="toolParams[input.name]"
                                                                    />

                                                                    <Badge
                                                                        variant="outline"
                                                                        :class="[
                                                                            toolParams[input.name] ? 'ring-ring ring' : ''
                                                                        ]"
                                                                    >
                                                                        {{ toolParams[input.name] ? 'Enabled' : 'Disabled' }}
                                                                    </Badge>
                                                                </div>
                                                            </template>

                                                            <template v-else-if="input.type === 'object'">
                                                                <code class="bg-muted ring-ring rounded-sm p-2 text-xs ring">
                                                                    <p>Options:</p>

                                                                    <br>
                                                                    {{
                                                                        JSON.stringify(Object.fromEntries(
                                                                            Object.entries(input.properties).map(([key, value]) => [key, value.default || null])
                                                                        ))
                                                                    }}
                                                                </code>

                                                                <Textarea
                                                                    :id="input.name"
                                                                    v-model="toolParams[input.name]"
                                                                    rows="4"
                                                                    @vue:mounted="toolParams[input.name] = `{  }`"
                                                                />
                                                            </template>

                                                            <template v-else>
                                                                <Input
                                                                    :id="input.name"
                                                                    v-model="toolParams[input.name]"
                                                                    :type="input.type === 'number' || input.type === 'integer' ? 'number' : 'text'"
                                                                />
                                                            </template>
                                                        </FormControl>

                                                        <FormDescription>
                                                            {{ input.description }}
                                                        </FormDescription>

                                                        <FormMessage/>
                                                    </FormItem>
                                                </FormField>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </ResizablePanel>
                        </ResizablePanelGroup>
                    </ResizablePanel>

                    <ResizableHandle id="tool-response-handle" with-handle/>

                    <ResizablePanel
                        id="tool-output-panel"
                        :default-size="50"
                    >
                        <ResizablePanelGroup id="tool-response-group" direction="horizontal">
                            <ResizablePanel id="tool-response-panel" :default-size="50">
                                <!-- Tool Output -->
                                <div class="h-full overflow-auto">
                                    <Empty v-if="!toolResult" class="p-4">
                                        <EmptyMedia>
                                            <OutputIcon class="text-ring size-24"/>
                                        </EmptyMedia>

                                        <EmptyContent>
                                            <EmptyHeader>
                                                <EmptyTitle>
                                                    No Tool Output
                                                </EmptyTitle>
                                            </EmptyHeader>

                                            <EmptyDescription>
                                                Execute the selected tool to see its output here.
                                            </EmptyDescription>
                                        </EmptyContent>
                                    </Empty>

                                    <div v-else class="relative isolate divide-y">
                                        <div class="bg-accent sticky top-0 left-0 z-10 flex h-14 w-full items-center px-4">
                                            <span class="leading-none font-semibold tracking-tight">Tool Output</span>
                                        </div>

                                        <div class="relative p-4">
                                            <VueJsonPretty
                                                :data="toolResult"
                                                :deep="3"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </ResizablePanel>

                            <ResizableHandle id="tool-response-handle" with-handle/>

                            <ResizablePanel id="tool-ui-panel" :default-size="50">
                                <!-- Tool UI -->
                                <div class="relative h-full overflow-auto">
                                    <Empty v-if="!openAiSrc" class="p-4">
                                        <EmptyMedia>
                                            <UiIcon class="text-ring size-24"/>
                                        </EmptyMedia>

                                        <EmptyContent>
                                            <EmptyHeader>
                                                <EmptyTitle>
                                                    No Tool UI
                                                </EmptyTitle>
                                            </EmptyHeader>

                                            <EmptyDescription>
                                                If a UI component is available for this tool, it will be displayed here.
                                            </EmptyDescription>
                                        </EmptyContent>
                                    </Empty>

                                    <template v-else>
                                        <div class="bg-blueprint !absolute inset-0 size-full"/>

                                        <div class="flex p-4">
                                            <iframe
                                                ref="iframeRef"
                                                :src="openAiSrc"
                                                :class="[
                                                    'relative mx-auto h-auto max-h-[600px] w-full max-w-4xl rounded-md border-2',
                                                    resourceResult.result.contents[0]._meta?.['openai/widgetPrefersBorder'] ? 'border-border shadow-lg' : 'border-transparent',
                                                ]"
                                                sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-modals"
                                                :title="`OpenAI Component: ${selectedTool.name}`"
                                                allow="web-share"
                                            ></iframe>
                                        </div>
                                    </template>
                                </div>
                            </ResizablePanel>
                        </ResizablePanelGroup>
                    </ResizablePanel>
                </ResizablePanelGroup>
            </ResizablePanel>
        </ResizablePanelGroup>
    </Deferred>
</template>
