<template>
    <div ref="container"></div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { PendingApprovalsWidget as CorePendingApprovalsWidget } from '../core/widgets/PendingApprovalsWidget.js';

const props = defineProps({
    limit: {
        type: Number,
        default: 5
    },
    showActions: {
        type: Boolean,
        default: true
    },
    refreshInterval: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['approve', 'reject']);

const container = ref(null);
let widget = null;

onMounted(() => {
    widget = new CorePendingApprovalsWidget(container.value, {
        limit: props.limit,
        showActions: props.showActions,
        refreshInterval: props.refreshInterval,
        onApprove: (id) => emit('approve', id),
        onReject: (id) => emit('reject', id)
    });
    widget.render();
});

onUnmounted(() => {
    if (widget) {
        widget.destroy();
    }
});
</script>
