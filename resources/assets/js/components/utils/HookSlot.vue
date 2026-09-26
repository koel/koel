<template>
  <component :is="component" v-for="(component, index) in components" :key="index" v-bind="context" />
</template>

<script lang="ts" setup>
import { useHookSlot } from '@/composables/useHookSlot'

const props = withDefaults(defineProps<{ name: HookSlotName; context?: Record<string, unknown> }>(), {
  context: () => ({}),
})

const components = useHookSlot(props.name, () => props.context)
</script>
