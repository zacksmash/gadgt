<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { Deferred, router } from '@inertiajs/vue3'
import '@mcp-ui/client/ui-resource-renderer.wc.js'
import JsonViewer from '@/components/JsonViewer.vue'
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

const selectedTool = ref(null)
const toolResult = ref(null)
const toolParams = ref({})
const resourceResult = ref(null)

const openAiFrame = ref(null)
const openAiSrc = ref(null)
const openAiFullScreen = ref(false)

const mcpUiFrame = ref(null)
const mcpUiSrc = computed(() => {
    const resourceContent = toolResult.value?.result?.content?.find(
        (content) => content.type === 'resource' && content.resource,
    )

    if (!resourceContent) {
        return false
    }

    if (
        resourceContent.type === 'resource' &&
        resourceContent.resource?.uri?.includes('ui://') &&
        ['text/uri-list', 'text/html'].includes(resourceContent.resource?.mimeType)
    ) {
        return resourceContent.resource
    }

    return false
})

const tools = computed(() => props.data?.result?.tools || [])

const toolError = computed(() => {
    return toolResult.value?.result?.isError
        ? toolResult.value?.result?.content
        : null
})

const inputSchema = computed(() => {
    return selectedTool.value?.inputSchema?.properties
        ? Object.keys(selectedTool.value.inputSchema.properties).map((key) => ({
            name: key,
            ...selectedTool.value.inputSchema.properties[key],
        }))
        : null
})

const isRequired = (input) => {
    return selectedTool.value?.inputSchema?.required?.includes(input.name)
}

const selectTool = (tool) => {
    selectedTool.value = tool
    openAiSrc.value = null
    toolResult.value = null
    toolParams.value = {}
}

const callTool = async (tool, params, fromWidget) => {
    toolResult.value = null

    router.reload({
        method: 'post',
        only: ['result'],
        data: {
            name: tool,
            params: params,
        },
        onStart: () => {
            openAiResetFrameHeight()
        },
        onSuccess: (page) => {
            toolResult.value = page.props.result

            if (toolError.value) {
                openAiSrc.value = null
                return
            }

            if (!fromWidget) {
                if (selectedTool.value?._meta?.['openai/outputTemplate']) {
                    openAiFetchTemplate(selectedTool.value)
                }
            }
        },
    })
}

const openAiFetchTemplate = (tool) => {
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
                if (openAiFrame.value) {
                    // set the iframe height based on the content
                    const iframe = openAiFrame.value

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

const openAiLeaveFullScreen = () => {
    openAiFullScreen.value = false

    openAiResetFrameHeight()
}

const openAiResetFrameHeight = () => {
    if (openAiFrame.value) {
        const event = new Event('load')
        openAiFrame.value.dispatchEvent(event)
    }
}

const openAiActionHandler = async ({ data }) => {
    switch (data.type) {
        case 'openai:callTool':
            if (callTool) {
                try {
                    await callTool(
                        data.toolName,
                        data.params || {},
                        true,
                    )

                    setTimeout(() => {
                        openAiFrame.value?.contentWindow?.postMessage(
                            {
                                type: 'openai:callTool:response',
                                requestId: data.requestId,
                                result: JSON.parse(JSON.stringify(toolResult.value.result)),
                            },
                            '*',
                        )

                        setTimeout(() => {
                            openAiResetFrameHeight()
                        }, 10)
                    }, 500)
                } catch (err) {
                    openAiFrame.value?.contentWindow?.postMessage(
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

        case 'openai:requestDisplayMode':
            openAiFullScreen.value = data.mode === 'fullscreen'
            if (openAiFullScreen.value && openAiFrame.value) {
                openAiFrame.value.style.height = null
            }

            break
    }
}

const mcpUiActionHandler = ({ detail: result }) => {
    switch (result.type) {
        case 'tool':
            console.log('Tool call:', result.payload.toolName, result.payload.params)
            // Handle tool execution
            break
        case 'prompt':
            console.log('Prompt:', result.payload.prompt)
            // Handle prompt display
            break
        case 'link':
            console.log('Link:', result.payload.url)
            // Handle link navigation
            break
        case 'intent':
            console.log('Intent:', result.payload.intent, result.payload.params)
            // Handle intent processing
            break
        case 'notify':
            console.log('Notification:', result.payload.message)
            // Handle notification display
            break
    }
    return { status: 'handled' }
}

onMounted(() => {
    window.addEventListener('message', openAiActionHandler)
})

onUnmounted(() => {
    window.removeEventListener('message', openAiActionHandler)
    if (openAiFrame.value) {
        openAiFrame.value.removeEventListener('load', null)
    }
})
</script>

<template>
    <div class="relative h-full">
        <Deferred data="data">
            <template #fallback>
                Loading Tools...
            </template>

            <ResizablePanelGroup id="tools-group" direction="horizontal">
                <!-- Tool List -->
                <ResizablePanel id="tools-panel" :default-size="25">
                    <div class="p-4">
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

                <ResizableHandle id="tools-group-handle" with-handle/>

                <!-- Tool Details & Execution -->
                <ResizablePanel id="tool-panel" :default-size="75">
                    <ResizablePanelGroup id="tool-group" direction="vertical">
                        <ResizablePanel id="tool-info-panel" :default-size="50">
                            <ResizablePanelGroup id="tool-info-group" direction="horizontal">
                                <!-- Tool Details -->
                                <ResizablePanel id="tool-details-panel" :default-size="50">
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

                                        <div v-else class="relative divide-y">
                                            <div class="bg-accent sticky top-0 left-0 z-10 flex h-14 w-full items-center px-4">
                                                <span class="leading-none font-semibold tracking-tight">Tool Details</span>
                                            </div>

                                            <div class="p-4">
                                                <JsonViewer :data="selectedTool"/>
                                            </div>
                                        </div>
                                    </div>
                                </ResizablePanel>

                                <ResizableHandle id="tool-info-handle" with-handle/>

                                <!-- Tool Input -->
                                <ResizablePanel id="tool-input-panel" :default-size="50">
                                    <form class="h-full overflow-auto" @submit.prevent="callTool(selectedTool.name, toolParams)">
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
                                                <span class="leading-none font-semibold tracking-tight">
                                                    Tool Input
                                                </span>

                                                <Button
                                                    size="sm"
                                                    :disabled="!selectedTool"
                                                    type="submit"
                                                >
                                                    Execute
                                                </Button>
                                            </div>

                                            <div class="divide-y p-4">
                                                <template v-if="toolError">
                                                    <p
                                                        v-for="error in toolError"
                                                        :key="error.text"
                                                        class="line-clamp-2 border-0 text-sm leading-normal font-normal text-balance text-rose-500"
                                                    >
                                                        {{ error.text ?? 'Fill out all required arguments' }}
                                                    </p>
                                                </template>

                                                <div
                                                    v-for="input in inputSchema"
                                                    :key="input.name"
                                                    class="py-4 first:pt-0 last:pb-0"
                                                >
                                                    <FormField :name="input.name">
                                                        <FormItem>
                                                            <FormLabel :for="input.name">
                                                                <div class="flex w-full items-center justify-between gap-2">
                                                                    <span class="flex items-center gap-px">
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
                                    </form>
                                </ResizablePanel>
                            </ResizablePanelGroup>
                        </ResizablePanel>

                        <ResizableHandle id="tool-group-handle" with-handle/>

                        <ResizablePanel id="tool-results-panel" :default-size="50">
                            <ResizablePanelGroup id="tool-results-group" direction="horizontal">
                                <!-- Tool Output -->
                                <ResizablePanel id="tool-output-panel" :default-size="50">
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
                                            <div
                                                :class="[
                                                    toolError ? 'bg-red-500 dark:bg-red-800' : 'bg-accent',
                                                    'sticky top-0 left-0 z-10 flex h-14 w-full items-center px-4'
                                                ]"
                                            >
                                                <span class="leading-none font-semibold tracking-tight">Tool Output</span>
                                            </div>

                                            <div class="relative p-4">
                                                <JsonViewer :data="toolResult"/>
                                            </div>
                                        </div>
                                    </div>
                                </ResizablePanel>

                                <ResizableHandle id="tool-results-handle" with-handle/>

                                <!-- Tool UI -->
                                <ResizablePanel id="tool-ui-panel" :default-size="50">
                                    <div class="h-full overflow-auto">
                                        <Empty v-if="(!openAiSrc && !mcpUiSrc)" class="p-4">
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
                                            <div
                                                v-if="openAiSrc"
                                                :class="[
                                                    openAiFullScreen ? 'absolute inset-0 isolate z-20 bg-white' : 'p-4',
                                                ]"
                                            >
                                                <Button
                                                    v-if="openAiFullScreen"
                                                    size="icon"
                                                    variant="ghost"
                                                    class="absolute top-2 right-2 z-30 text-4xl"
                                                    @click="openAiLeaveFullScreen"
                                                >
                                                    &times;
                                                </Button>

                                                <iframe
                                                    ref="openAiFrame"
                                                    :src="openAiSrc"
                                                    :class="[
                                                        'w-full',
                                                        !openAiFullScreen ? 'mx-auto max-h-[600px] max-w-4xl border-2' : 'h-full',
                                                        resourceResult?.result.contents[0]._meta?.['openai/widgetPrefersBorder'] && !openAiFullScreen
                                                            ? 'border-border rounded-md shadow-lg' : 'border-transparent',
                                                    ]"
                                                    sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-modals"
                                                    :title="`OpenAI Component: ${selectedTool.name}`"
                                                    allow="web-share"
                                                ></iframe>
                                            </div>

                                            <ui-resource-renderer
                                                v-if="mcpUiSrc"
                                                ref="mcpUiFrame"
                                                :resource="mcpUiSrc"
                                                class="[&_div]:h-full"
                                                @onUIAction="mcpUiActionHandler"
                                            >
                                            </ui-resource-renderer>
                                        </template>
                                    </div>
                                </ResizablePanel>
                            </ResizablePanelGroup>
                        </ResizablePanel>
                    </ResizablePanelGroup>
                </ResizablePanel>
            </ResizablePanelGroup>
        </Deferred>
    </div>
</template>
