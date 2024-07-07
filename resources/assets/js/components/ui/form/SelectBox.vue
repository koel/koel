<template>
  <label class="relative bg-white text-gray-800 rounded">
    <select
      ref="el"
      v-model="value"
      class="appearance-none w-full pl-4 pr-8 py-2.5 text-base text-current"
    >
      <slot />
    </select>
    <Icon
      :icon="faCaretDown"
      class="text-k-highlight pointer-events-none absolute top-1/3 right-[8px]"
      size="sm"
    />
  </label>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { faCaretDown } from '@fortawesome/free-solid-svg-icons'

const props = withDefaults(defineProps<{ modelValue?: any }>(), { modelValue: null })
const emit = defineEmits<{ (e: 'update:modelValue', value: any): void }>()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})

const el = ref<HTMLInputElement>()

defineExpose({
  el
})
</script>

<style lang="postcss" scoped>
select {
  background: none; /* remove background AND the dropdown arrow. Tailwind doesn't have a way to do this. */
}
</style>
