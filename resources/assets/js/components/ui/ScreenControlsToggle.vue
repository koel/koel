<template>
  <label v-if="isMobile.phone" class="text-highlight">
    <input type="checkbox" v-model="value"/>
    <icon :icon="value ? faCaretUp : faCaretDown" class="toggle"/>
    <span>Toggle the song list controls</span>
  </label>
</template>

<script lang="ts" setup>
import { faCaretDown, faCaretUp } from '@fortawesome/free-solid-svg-icons'
import isMobile from 'ismobilejs'
import { computed } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: boolean }>(), { modelValue: false })

const emit = defineEmits(['update:modelValue'])

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})
</script>

<style lang="scss" scoped>
label {
  font-size: 1rem;
  display: inline-block;
  margin-left: 4px;
}

input, span {
  display: none;
}
</style>
