<template>
  <input :type="type" v-model="mutableValue" name="value[]" required>
</template>

<script lang="ts" setup>
import { toRefs, watch } from 'vue'
import types from '@/config/smart-playlist/types'

const props = withDefaults(defineProps<{ type: keyof typeof types, value: string }>(), { value: '' })
const { type, value } = toRefs(props)

const mutableValue = value.value

const emit = defineEmits(['input'])

watch(() => mutableValue, () => emit('input', mutableValue))
</script>

<style lang="scss" scoped>
input {
  display: inline-block;
  width: 140px !important;
}
</style>
