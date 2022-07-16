<template>
  <header class="screen-header" :class="layout">
    <div class="thumbnail-wrapper" :class="{ 'non-empty': hasThumbnail }">
      <slot name="thumbnail"></slot>
    </div>

    <div class="right">
      <div class="heading-wrapper">
        <h1 class="name">
          <slot></slot>
        </h1>
        <span class="meta text-secondary">
          <slot name="meta"></slot>
        </span>
      </div>

      <slot name="controls"></slot>
    </div>
  </header>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

const props = withDefaults(defineProps<{ hasThumbnail?: boolean, layout?: 'expanded' | 'collapsed' }>(), {
  hasThumbnail: false,
  layout: 'expanded'
})

const { hasThumbnail } = toRefs(props)
</script>

<style lang="scss">
header.screen-header {
  display: flex;
  align-items: flex-end;
  border-bottom: 1px solid var(--color-bg-secondary);
  position: relative;
  align-content: stretch;
  line-height: normal;
  gap: 1.5rem;
  padding: .8rem 1rem .8rem 0;
  will-change: height;

  &.expanded {
    padding: 1.8rem;

    .thumbnail-wrapper {
      width: 192px;
    }

    h1.name {
      font-size: 4rem;
      font-weight: bold;
    }

    .meta {
      display: block;
    }

    .right {
      flex-direction: column;
      align-items: flex-start;
    }
  }

  .thumbnail-wrapper {
    overflow: hidden;
    width: 0;
    display: none;
    will-change: width, height;
    transition: width .3s;
    box-shadow: 0 10px 20px 0 rgba(0, 0, 0, 0.3);
    border-radius: 5px;

    &.non-empty {
      display: block;
    }
  }

  .right {
    flex: 1;
    display: flex;
    gap: 1.5rem;
    align-items: center;
    overflow: hidden;
  }

  h1.name {
    font-size: 2.75rem;
    font-weight: var(--font-weight-thin);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  .heading-wrapper {
    width: 100%;
    overflow: hidden;
    flex: 1;
  }

  .meta {
    display: none;
    font-size: .9rem;
    line-height: 2;
    font-weight: var(--font-weight-light);

    a {
      color: var(--color-text-primary);

      &:hover {
        color: var(--color-highlight);
      }
    }

    > * + * {
      margin-left: .2rem;
      display: inline-block;

      &::before {
        content: '•';
        margin-right: .2rem;
        color: var(--color-text-secondary);
        font-weight: unset;
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
