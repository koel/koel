<template>
  <header class="screen-header" :class="layout">
    <aside class="thumbnail-wrapper">
      <slot name="thumbnail"></slot>
    </aside>

    <main>
      <div class="heading-wrapper">
        <h1 class="name">
          <slot></slot>
        </h1>
        <span class="meta text-secondary">
          <slot name="meta"></slot>
        </span>
      </div>

      <slot name="controls"></slot>
    </main>
  </header>
</template>

<script lang="ts" setup>
const props = withDefaults(defineProps<{ layout?: ScreenHeaderLayout }>(), { layout: 'expanded' })
</script>

<style lang="scss" scoped>
header.screen-header {
  --transition-duration: .3s;

  @media (prefers-reduced-motion) {
    --transition-duration: 0;
  }

  display: flex;
  align-items: flex-end;
  flex-shrink: 0;
  border-bottom: 1px solid var(--color-bg-secondary);
  position: relative;
  align-content: stretch;
  line-height: normal;
  padding: 1.8rem;

  &.expanded {
    .thumbnail-wrapper {
      margin-right: 1.5rem;
      width: 192px;

      > * {
        transform: scale(1);
      }
    }

    .meta {
      display: block;
    }

    main {
      flex-direction: column;
      align-items: flex-start;
    }
  }

  .thumbnail-wrapper {
    overflow: hidden;
    display: block;
    width: 0;
    transition: width var(--transition-duration);
    border-radius: 5px;

    > * {
      transform: scale(0);
      transform-origin: bottom left;
      transition: transform var(--transition-duration), width var(--transition-duration);
    }

    &:empty {
      display: none;
    }
  }

  main {
    flex: 1;
    display: flex;
    gap: 1.5rem;
    align-items: center;
    overflow: hidden;
  }

  h1.name {
    font-size: clamp(1.8rem, 3vw, 4rem);
    font-weight: var(--font-weight-bold);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    margin-right: 1.5rem;
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

    > :slotted(* + *) {
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

    .thumbnail-wrapper {
      display: none;
    }

    h1.name {
      font-weight: var(--font-weight-thin);
    }

    .meta {
      display: none;
    }
  }
}
</style>
