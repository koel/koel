<template>
  <span class="view-modes">
    <label
      class="thumbnails"
      :class="{ active: modelValue === 'thumbnails' }"
      title="View as thumbnails"
      data-test="view-mode-thumbnail"
    >
      <input class="hidden" type="radio" value="thumbnails" v-model="modelValue" @input="onInput">
      <i class="fa fa-th-large"></i>
      <span class="hidden">View as thumbnails</span>
    </label>

    <label
      class="list"
      :class="{ active: modelValue === 'list' }"
      title="View as list"
      data-test="view-mode-list"
    >
      <input class="hidden" type="radio" value="list" v-model="modelValue" @input="onInput">
      <i class="fa fa-list"></i>
      <span class="hidden">View as list</span>
    </label>
  </span>
</template>

<script lang="ts" setup>
import { PropType, ref, toRefs, watchEffect } from 'vue'

const props = defineProps({
  value: {
    type: String as PropType<ArtistAlbumViewMode>,
    default: 'thumbnails'
  }
})

const { value } = toRefs(props)

let modelValue = ref<ArtistAlbumViewMode>()

watchEffect(() => (modelValue.value = value.value))

const emit = defineEmits(['update:modelValue'])
const onInput = (e: InputEvent) => emit('update:modelValue', (e.target as HTMLInputElement).value)
</script>

<style lang="scss" scoped>
.view-modes {
  display: flex;
  width: 64px;
  border: 1px solid rgba(255, 255, 255, .2);
  border-radius: 5px;
  overflow: hidden;

  label {
    width: 50%;
    text-align: center;
    line-height: 2rem;
    font-size: 1rem;
    margin-bottom: 0;
    cursor: pointer;

    &.active {
      background: var(--color-text-primary);
      color: var(--color-bg-primary);
    }
  }

  @media only screen and(max-width: 768px) {
    margin-top: 8px;
  }
}
</style>
