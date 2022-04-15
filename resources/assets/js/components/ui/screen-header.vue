<template>
  <header class="screen-header">
    <div class="thumbnail-wrapper" :class="{ 'non-empty': hasThumbnail }" ref="thumbnailWrapper">
      <slot name="thumbnail"></slot>
    </div>

    <div class="heading-wrapper">
      <h1>
        <slot></slot>
      </h1>
      <span class="meta text-secondary">
        <slot name="meta"></slot>
      </span>
    </div>

    <slot name="controls"></slot>
  </header>
</template>

<script lang="ts" setup>
import { onUpdated, ref } from 'vue'

const thumbnailWrapper = ref(null as unknown as HTMLElement)
const hasThumbnail = ref(false)

onUpdated(() => {
  // until :empty is supported, we'll have to resort to this manual check
  hasThumbnail.value = Boolean(thumbnailWrapper.value.children.length)
})
</script>

<style lang="scss" scoped>
header {
  display: flex;
  font-weight: var(--font-weight-thin);
  padding: 1rem 1.8rem;
  border-bottom: 1px solid var(--color-bg-secondary);
  min-height: 96px;
  position: relative;
  align-items: center;
  align-content: stretch;
  line-height: normal;
  gap: 1.5rem;

  .thumbnail-wrapper {
    width: 64px;
    display: none;

    &.non-empty {
      display: block;
    }
  }

  h1 {
    font-size: 2.75rem;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  .heading-wrapper {
    overflow: hidden;
    flex: 1;
  }

  .meta {
    display: block;
    font-size: .9rem;
    line-height: 2;
    font-weight: var(--font-weight-light);

    a {
      color: var(--color-text-primary);

      &:hover {
        color: var(--color-highlight);
      }
    }
  }

  @media (max-width: 768px) {
    min-height: 0;
    flex-direction: column;

    .thumbnail-wrapper {
      display: none;
    }

    h1 {
      font-size: 1.38rem;
    }

    .meta {
      display: none;
    }
  }
}
</style>
