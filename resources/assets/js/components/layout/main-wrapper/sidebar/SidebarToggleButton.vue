<template>
  <label
    class="btn-toggle no-hover:flex hidden md:flex w-[24px] aspect-square absolute rounded-full -right-[12px] top-[27px]
    items-center justify-center z-10 text-k-text-secondary bg-k-bg-secondary border-[1.5px] border-white/20 cursor-pointer
    hover:text-k-text-primary hover:bg-k-highlight"
  >
    <input v-model="value" class="hidden" type="checkbox">
    <Icon v-if="value" :icon="faAngleLeft" />
    <Icon v-else :icon="faAngleRight" />
  </label>
</template>

<script lang="ts" setup>
import { faAngleLeft, faAngleRight } from '@fortawesome/free-solid-svg-icons'
import { computed } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: boolean }>(), { modelValue: false })
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})
</script>
