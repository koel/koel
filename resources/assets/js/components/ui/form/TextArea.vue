<template>
  <textarea
    ref="el"
    v-model="value"
    class="px-4 w-full min-h-48 text-base py-2.5 rounded text-k-text-input bg-k-bg-input"
  />
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: any }>(), { modelValue: null })
const emit = defineEmits<{ (e: 'update:modelValue', value: any): void }>()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})

const el = ref<HTMLTextAreaElement>()

defineExpose({
  el
})
</script>
