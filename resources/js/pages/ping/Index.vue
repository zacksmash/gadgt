<script lang="js" setup>
import { ref } from 'vue'
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

const ping = () => router.reload({
    method: 'post',
    only: ['pong'],
    onBefore: () => {
        pong.value = undefined
    },
    onSuccess: (page) => {
        pong.value = page.props.pong !== false
    },
})
</script>

<template>
    <div class="p-4">
        <div class="flex items-center gap-2">
            <Empty class="from-muted/50 to-background h-full bg-gradient-to-b from-30%">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Bell/>
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
