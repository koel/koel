<template>
  <span class="view-modes">
    <label
      :class="{ active: value === 'thumbnails' }"
      class="thumbnails"
      data-test="view-mode-thumbnail"
      title="View as thumbnails"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="thumbnails">
      <i class="fa fa-th-large"></i>
      <span class="hidden">View as thumbnails</span>
    </label>

    <label
      :class="{ active: value === 'list' }"
      class="list"
      data-test="view-mode-list"
      title="View as list"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="list">
      <i class="fa fa-list"></i>
      <span class="hidden">View as list</span>
    </label>
  </span>
</template>

<script lang="ts" setup>
import { computed} from 'vue'

const props = withDefaults(defineProps<{ mode?: ArtistAlbumViewMode }>(), { mode: 'thumbnails' })
const emit = defineEmits(['update:mode'])

const value = computed({
  get: () => props.mode,
  set: value => emit('update:mode', value)
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
