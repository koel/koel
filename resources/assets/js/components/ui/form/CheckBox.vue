<template>
  <span
    class="relative align-bottom inline-block w-[32px] h-[20px] bg-gray-300 rounded-full shadow-inner cursor-pointer transition-all duration-200 ease-in-out mr-2
    after:h-[16px] after:aspect-square after:absolute after:bg-white after:top-[2px] after:left-[2px] after:rounded-full after:transition-left after:duration-200 after:ease-in-out
    has-[:checked]:bg-k-highlight has-[:checked]:after:left-[14px]"
  >
    <input v-bind="$attrs" v-model="value" :checked="value" type="checkbox" class="hidden">
  </span>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: any }>(), { modelValue: false })

const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})
</script>
