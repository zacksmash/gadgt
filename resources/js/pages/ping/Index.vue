<script lang="js" setup>
import { onUnmounted, ref } from 'vue'
import { AlertCircle, Bell, CheckCircle } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

const pong = ref(undefined)
const pinging = ref(false)
const cancelToken = ref(null)

const ping = () => router.reload({
    method: 'post',
    only: ['pong'],
    onCancelToken: ({ cancel }) => {
        if (cancelToken.value) {
            cancelToken.value()
        }

        cancelToken.value = cancel
    },
    onBefore: () => {
        pinging.value = true
        pong.value = undefined
    },
    onSuccess: (page) => {
        pong.value = page.props.pong !== false
    },
    onFinish: () => {
        pinging.value = false
    },
})

onUnmounted(() => {
    if (cancelToken.value) {
        cancelToken.value()
    }
})
</script>

<template>
    <div class="h-full p-4">
        <div class="mx-auto w-full max-w-5xl">
            <Empty class="border border-dashed">
                <EmptyHeader>
                    <EmptyMedia variant="icon" class="size-24">
                        <Bell class="size-18"/>
                    </EmptyMedia>

                    <EmptyTitle>
                        Ping Status
                    </EmptyTitle>

                    <EmptyDescription>
                        Click the button below to ping the server.
                    </EmptyDescription>
                </EmptyHeader>

                <EmptyContent>
                    <Button
                        variant="outline"
                        size="lg"
                        @click="ping"
                    >
                        Ping Server
                    </Button>

                    <Alert
                        v-if="pong === false"
                        variant="destructive"
                        class="text-left"
                    >
                        <AlertCircle class="h-4 w-4"/>

                        <AlertTitle>Error</AlertTitle>

                        <AlertDescription>
                            Unable to reach the server.
                        </AlertDescription>
                    </Alert>

                    <Alert
                        v-if="pong === true"
                        variant="success"
                        class="text-left"
                    >
                        <CheckCircle class="h-4 w-4"/>

                        <AlertTitle>Success</AlertTitle>

                        <AlertDescription>
                            Successfully reached the server.
                        </AlertDescription>
                    </Alert>
                </EmptyContent>
            </Empty>
        </div>
    </div>
</template>
