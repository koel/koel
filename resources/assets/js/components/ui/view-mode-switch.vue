<template>
  <span class="view-modes">
    <label
      class="thumbnails"
      :class="{ active: mutatedValue === 'thumbnails' }"
      title="View as thumbnails"
      data-test="view-mode-thumbnail"
    >
      <input class="hidden" type="radio" value="thumbnails" v-model="mutatedValue" @input="onInput">
      <i class="fa fa-th-large"></i>
      <span class="hidden">View as thumbnails</span>
    </label>

    <label
      class="list"
      :class="{ active: mutatedValue === 'list' }"
      title="View as list"
      data-test="view-mode-list"
    >
      <input class="hidden" type="radio" value="list" v-model="mutatedValue" @input="onInput">
      <i class="fa fa-list"></i>
      <span class="hidden">View as list</span>
    </label>
  </span>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'

export default Vue.extend({
  props: {
    value: {
      type: String
    } as PropOptions<ArtistAlbumViewMode>
  },

  data: () => ({
    mutatedValue: null as ArtistAlbumViewMode | null
  }),

  watch: {
    value: {
      handler (mode: ArtistAlbumViewMode) {
        this.mutatedValue = mode
      },
      immediate: true
    }
  },

  methods: {
    onInput (e: InputEvent): void {
      this.$emit('input', (e.target as HTMLInputElement).value)
    }
  }
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
