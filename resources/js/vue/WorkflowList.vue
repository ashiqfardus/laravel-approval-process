<template>
    <div ref="container"></div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { WorkflowListWidget as CoreWorkflowListWidget } from '../core/widgets/WorkflowListWidget.js';

const props = defineProps({
    limit: {
        type: Number,
        default: 10
    },
    status: {
        type: String,
        default: 'all'
    },
    showActions: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['select']);

const container = ref(null);

onMounted(() => {
    const widget = new CoreWorkflowListWidget(container.value, {
        limit: props.limit,
        status: props.status,
        showActions: props.showActions,
        onSelect: (id) => emit('select', id)
    });
    widget.render();
});
</script>
