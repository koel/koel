<template>
  <div>
    <input v-model="value" :type="type" minlength="10" v-bind="$attrs">
    <button type="button" @click.prevent="toggleReveal">
      <Icon v-if="type === 'password'" :icon="faEye" />
      <Icon v-else :icon="faEyeSlash" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { faEye, faEyeSlash } from '@fortawesome/free-regular-svg-icons'

// we don't want the wrapping div to inherit the fallthrough attrs
defineOptions({ inheritAttrs: false })

const props = withDefaults(defineProps<{ modelValue?: string }>(), { modelValue: '' })
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()

const type = ref<'password' | 'text'>('password')

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})

const toggleReveal = () => (type.value = type.value === 'password' ? 'text' : 'password')
</script>

<style scoped lang="scss">
div {
  position: relative;
}

input {
  width: 100%;
}

button {
  position: absolute;
  padding: 8px 6px;
  right: 0;
  top: 0;
  color: var(--color-bg-primary);
}
</style>
