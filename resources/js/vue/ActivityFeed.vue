<template>
    <div ref="container"></div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { ActivityFeedWidget as CoreActivityFeedWidget } from '../core/widgets/ActivityFeedWidget.js';

const props = defineProps({
    limit: {
        type: Number,
        default: 10
    },
    realtime: {
        type: Boolean,
        default: false
    },
    refreshInterval: {
        type: Number,
        default: 30000
    }
});

const container = ref(null);
let widget = null;

onMounted(() => {
    widget = new CoreActivityFeedWidget(container.value, {
        limit: props.limit,
        realtime: props.realtime,
        refreshInterval: props.refreshInterval
    });
    widget.render();
});

onUnmounted(() => {
    if (widget) {
        widget.destroy();
    }
});
</script>
