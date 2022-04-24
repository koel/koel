<template>
  <div class="text-zoomer">
    <button title="Zoom out" @click.prevent="zoom(-1)">
      <i class="fa fa-search-minus"></i>
    </button>
    <button title="Zoom in" @click.prevent="zoom(1)">
      <i class="fa fa-search-plus"></i>
    </button>
  </div>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

const props = defineProps<{ target: HTMLElement | null }>()
const { target } = toRefs(props)

const zoom = (level: number) => {
  if (!target.value) {
    return
  }

  const style = target.value.style

  if (style.fontSize === '') {
    style.fontSize = '1em'
    style.lineHeight = '1.6'
  }

  style.fontSize = parseFloat(style.fontSize) + level * 0.2 + 'em'
  style.lineHeight = String(parseFloat(style.lineHeight) + level * 0.15)
}
</script>

<style lang="scss" scoped>
.text-zoomer {
  display: flex;
  transition: .2s;

  button {
    @include inset-when-pressed();

    background: var(--color-bg-primary);
    border: 1px solid rgba(255, 255, 255, .2);
    opacity: .8;
    color: var(--color-text-primary);
    transition: background .2s;
    padding: .5rem .75rem;

    &:hover {
      opacity: 1;
      background: var(--color-bg-primary);
      color: var(--color-text-primary);
    }

    &:first-of-type {
      border-radius: 4px 0 0 4px;
      border-right: 0;
    }

    &:last-of-type {
      border-radius: 0 4px 4px 0;
    }
  }
}
</style>
