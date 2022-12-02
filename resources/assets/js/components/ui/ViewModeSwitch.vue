<template>
  <span class="view-modes">
    <label
      v-koel-tooltip
      :class="{ active: value === 'thumbnails' }"
      class="thumbnails"
      data-testid="view-mode-thumbnail"
      title="View as thumbnails"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="thumbnails">
      <icon :icon="faThumbnailsHehe" />
      <span class="hidden">View as thumbnails</span>
    </label>

    <label
      v-koel-tooltip
      :class="{ active: value === 'list' }"
      class="list"
      data-testid="view-mode-list"
      title="View as list"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="list">
      <icon :icon="faList" />
      <span class="hidden">View as list</span>
    </label>
  </span>
</template>

<script lang="ts" setup>
import { faMicrosoft as faThumbnailsHehe } from '@fortawesome/free-brands-svg-icons'
import { faList } from '@fortawesome/free-solid-svg-icons'
import { computed } from 'vue'

const props = withDefaults(defineProps<{ modelValue?: ArtistAlbumViewMode }>(), { modelValue: 'thumbnails' })

const emit = defineEmits<{ (e: 'update:modelValue', value: ArtistAlbumViewMode): void }>()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})
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
    display: flex;
    align-items: center;
    justify-content: center;
    height: 2rem;
    margin-bottom: 0;
    cursor: pointer;

    &.active {
      background: var(--color-text-primary);
      color: var(--color-bg-primary);
    }
  }

  @media only screen and (max-width: 768px) {
    margin-top: 8px;
  }
}
</style>
