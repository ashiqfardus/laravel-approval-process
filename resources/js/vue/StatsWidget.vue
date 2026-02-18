<template>
    <div ref="container"></div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { StatsWidget as CoreStatsWidget } from '../core/widgets/StatsWidget.js';

const props = defineProps({
    refreshInterval: {
        type: Number,
        default: 0
    },
    showTrends: {
        type: Boolean,
        default: true
    }
});

const container = ref(null);
let widget = null;

onMounted(() => {
    widget = new CoreStatsWidget(container.value, {
        refreshInterval: props.refreshInterval,
        showTrends: props.showTrends
    });
    widget.render();
});

watch(() => props.refreshInterval, () => {
    if (widget) {
        widget.destroy();
        widget = new CoreStatsWidget(container.value, {
            refreshInterval: props.refreshInterval,
            showTrends: props.showTrends
        });
        widget.render();
    }
});

onUnmounted(() => {
    if (widget) {
        widget.destroy();
    }
});
</script>
