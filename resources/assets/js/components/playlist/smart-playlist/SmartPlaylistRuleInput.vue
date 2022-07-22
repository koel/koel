<template>
  <input v-model="value" :type="type" name="value[]" required>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import inputTypes from '@/config/smart-playlist/inputTypes'

const props = withDefaults(defineProps<{ type?: keyof typeof inputTypes, value?: any }>(), { value: undefined })
const { type } = toRefs(props)

const emit = defineEmits(['update:modelValue'])

const value = computed({
  get: () => props.value,
  set: value => emit('update:modelValue', value)
})
</script>

<style lang="scss" scoped>
input {
  width: 140px !important;
}
</style>
