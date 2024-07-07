<template>
  <header
    :class="[ layout, disabled ? 'disabled' : '' ]"
    class="screen-header min-h-0 md:min-h-full flex items-end flex-shrink-0 relative content-stretch leading-normal p-6
    border-b border-b-k-bg-secondary"
  >
    <aside v-if="$slots.thumbnail" class="thumbnail-wrapper hidden md:block overflow-hidden w-0 rounded-md">
      <slot name="thumbnail" />
    </aside>

    <main class="flex flex-1 gap-5 items-center overflow-hidden">
      <div class="w-full flex-1 overflow-hidden">
        <h1 class="name overflow-hidden whitespace-nowrap text-ellipsis mr-4 font-thin md:font-bold my-0 leading-tight">
          <slot />
        </h1>
        <span v-if="$slots.meta" class="meta text-k-text-secondary hidden text-[0.9rem] leading-loose">
          <slot name="meta" />
        </span>
      </div>

      <slot name="controls" />
    </main>
  </header>
</template>

<script lang="ts" setup>
withDefaults(defineProps<{
  layout?: ScreenHeaderLayout,
  disabled?: boolean,
}>(), {
  layout: 'expanded',
  disabled: false
})
</script>

<style lang="postcss" scoped>
header.screen-header {
  --transition-duration: 300ms;

  @media (prefers-reduced-motion) {
    --transition-duration: 0;
  }

  &.disabled {
    @apply opacity-50 cursor-not-allowed;

    *, *::before, *::after {
      @apply pointer-events-none;
    }
  }

  &.expanded {
    .thumbnail-wrapper {
      @apply mr-6 w-[192px];

      > * {
        @apply scale-100;
      }
    }

    .meta {
      @apply block;
    }

    main {
      @apply flex-col items-start;
    }
  }

  .thumbnail-wrapper {
    transition: width var(--transition-duration);

    > * {
      @apply scale-0 origin-bottom-left;
      transition: transform var(--transition-duration), width var(--transition-duration);
    }

    &:empty {
      @apply hidden;
    }
  }

  h1.name {
    font-size: clamp(1.8rem, 3vw, 4rem);
  }

  .meta {
    a {
      @apply text-k-text-primary hover:text-k-highlight;
    }

    > :slotted(* + *) {
      @apply ml-1 inline-block before:content-['•'] before:mr-1 before:text-k-text-secondary;
    }
  }
}
</style>
