<script lang="ts" setup>
import VueJsonPretty from 'vue-json-pretty'
import 'vue-json-pretty/lib/styles.css'
const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <div>
        <VueJsonPretty
            :data="props.data"
            :deep="9999"
        />
    </div>
</template>
