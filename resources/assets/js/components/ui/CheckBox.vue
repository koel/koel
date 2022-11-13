<template>
  <span>
    <input :checked="checked" type="checkbox" v-bind="$attrs" @input="onInput">
    <icon :icon="faCheck" v-if="checked"/>
  </span>
</template>

<script lang="ts" setup>
import { faCheck } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: any }>(), {
  modelValue: false
})

const checked = ref(props.modelValue)

const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()

const onInput = (event: InputEvent) => {
  checked.value = (event.target as HTMLInputElement).checked
  emit('update:modelValue', checked.value)
}
</script>

<style scoped>
span {
  position: relative;
}

svg {
  color: var(--color-highlight);
  position: absolute;
  top: 1px;
  left: 2px;
}
</style>
