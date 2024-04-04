<template>
  <label v-if="isMobile.phone" class="text-k-highlight text-base inline-block ml-2">
    <input v-model="value" class="hidden" type="checkbox">
    <Icon :icon="value ? faCaretUp : faCaretDown" class="toggle" />
    <span class="hidden">Toggle the song list controls</span>
  </label>
</template>

<script lang="ts" setup>
import { faCaretDown, faCaretUp } from '@fortawesome/free-solid-svg-icons'
import isMobile from 'ismobilejs'
import { computed } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: boolean }>(), { modelValue: false })

const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})
</script>
