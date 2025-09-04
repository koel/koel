<template>
  <textarea
    ref="el"
    v-model="value"
    :aria-label="ariaLabel"
    class="px-4 w-full h-48 text-base py-2.5 rounded text-k-text-input bg-k-bg-input"
  />
</template>

<script lang="ts" setup>
import { computed, ref, useAttrs } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: any }>(), { modelValue: null })
const emit = defineEmits<{ (e: 'update:modelValue', value: any): void }>()

const attrs = useAttrs()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})

// Aria label is optional, but if it's not provided, we'll use the name prop as a fallback.
// This is useful for both accessibility and testing, since getByRole('textbox', { name: '...' })
// looks for aria-label.
const ariaLabel = computed(() => String(attrs.ariaLabel || attrs.name))

const el = ref<HTMLTextAreaElement>()

defineExpose({
  el,
})
</script>
