<template>
  <span>
    <button title="Zoom out" type="button" @click.prevent="zoom(-1)">
      <i class="fa fa-search-minus"/>
    </button>
    <button title="Zoom in" type="button" @click.prevent="zoom(1)">
      <i class="fa fa-search-plus"/>
    </button>
  </span>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

const props = defineProps<{ target: HTMLElement | null }>()
const { target } = toRefs(props)

const zoom = (delta: number) => {
  if (!target.value) {
    return
  }

  const style = target.value.style

  if (style.fontSize === '') {
    style.fontSize = '1em'
    style.lineHeight = '1.6'
  }

  style.fontSize = parseFloat(style.fontSize) + delta * 0.2 + 'em'
  style.lineHeight = String(parseFloat(style.lineHeight) + delta * 0.15)
}
</script>

<style lang="scss" scoped>
span {
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

    &:first-child {
      border-radius: 4px 0 0 4px;
      border-right: 0;
    }

    &:last-child {
      border-radius: 0 4px 4px 0;
    }
  }
}
</style>
