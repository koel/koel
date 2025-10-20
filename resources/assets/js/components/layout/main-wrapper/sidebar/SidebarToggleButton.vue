<template>
  <label
    class="btn-toggle hidden md:flex w-[24px] aspect-square absolute rounded-full -right-[12px] top-[27px]
    items-center justify-center z-10 bg-k-fg-5 border-[1.5px] border-white/20 cursor-pointer
    hover:text-k-highlight-fg hover:bg-k-highlight"
  >
    <input v-model="value" class="hidden" type="checkbox">
    <Icon :class="value || 'scale-x-[-1]'" :icon="faAngleLeft" class="transition" />
  </label>
</template>

<script lang="ts" setup>
import { faAngleLeft } from '@fortawesome/free-solid-svg-icons'
import { computed } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: boolean }>(), { modelValue: false })
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})
</script>
