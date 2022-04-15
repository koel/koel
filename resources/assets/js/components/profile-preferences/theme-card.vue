<template>
  <div
    class="theme"
    :class="{ selected: theme.selected }"
    :style="thumbnailStyles"
    @click="$emit('selected', theme)"
    :data-testid="`theme-card-${theme.id}`"
  >
    <div class="name">{{ name }}</div>
  </div>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { slugToTitle } from '@/utils'

export default Vue.extend({
  props: {
    theme: {
      type: Object,
      required: true
    } as PropOptions<Theme>
  },

  computed: {
    name (): string {
      return this.theme.name ? this.theme.name : slugToTitle(this.theme.id)
    },

    thumbnailStyles (): Record<string, string> {
      const styles = {
        'background-color': this.theme.thumbnailColor
      } as Record<string, string>

      if (this.theme.thumbnailUrl) {
        styles['background-image'] = `url(${this.theme.thumbnailUrl})`
      }

      return styles
    }
  }
})
</script>

<style lang="scss" scoped>
.theme {
  height: 100%;
  background-position: center;
  background-size: cover;
  position: relative;
  cursor: pointer;
  border-radius: 5px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, .1);

  &.selected {
    border: 2px solid rgba(255, 255, 255, .5);
  }

  &:hover {
    .name {
      opacity: 1;
    }
  }

  .name {
    position: absolute;
    height: 100%;
    width: 100%;
    bottom: 0;
    left: 0;
    display: flex;
    place-items: center;
    place-content: center;
    font-size: 1.5rem;
    background: rgba(0, 0, 0, .2);
    opacity: 0;
    transition: .3s opacity;
  }
}
</style>
