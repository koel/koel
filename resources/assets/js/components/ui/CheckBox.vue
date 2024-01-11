<template>
  <span :class="checked && 'checked'">
    <input :checked="checked" type="checkbox" v-bind="$attrs" @input="onInput">
  </span>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: any }>(), {
  modelValue: false
})

const checked = ref(props.modelValue)

const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()

const onInput = (event: Event) => {
  checked.value = (event.target as HTMLInputElement).checked
  emit('update:modelValue', checked.value)
}
</script>

<style lang="scss" scoped>
span {
  position: relative;
  vertical-align: bottom;
  display: inline-block;
  width: 32px;
  height: 20px;
  background: #c2c2c2;
  border-radius: 99rem;
  box-shadow: inset 0 1px 5px 0 rgba(0, 0, 0, .2);
  cursor: pointer;
  transition: all .2s ease-in-out;
  margin-right: .5rem;

  &::after {
    content: '';
    height: 16px;
    aspect-ratio: 1/1;
    position: absolute;
    background: #fff;
    top: 2px;
    left: 2px;
    border-radius: 99rem;
    transition: all .2s ease-in-out;
  }

  &.checked {
    background: var(--color-highlight);

    &::after {
      left: 14px;
    }
  }

  input {
    display: none;
  }
}

svg {
  color: var(--color-highlight);
  position: absolute;
  top: 1px;
  left: 1px;
}
</style>
