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

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { slugToTitle } from '@/utils'

const props = defineProps<{ theme: Theme }>()
const { theme } = toRefs(props)

const name = computed(() => theme.value.name ? theme.value.name : slugToTitle(theme.value.id))

const thumbnailStyles = computed((): Record<string, string> => {
  const styles: Record<string, string> = {
    'background-color': theme.value.thumbnailColor
  }

  if (theme.value.thumbnailUrl) {
    styles['background-image'] = `url(${theme.value.thumbnailUrl})`
  }

  return styles
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
