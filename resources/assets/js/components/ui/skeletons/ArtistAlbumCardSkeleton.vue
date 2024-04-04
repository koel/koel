<template>
  <article class="skeleton" :class="layout">
    <aside class="thumbnail pulse" />

    <footer>
      <p class="name pulse" />
      <p class="artist pulse" />
      <p class="meta pulse" />
    </footer>
  </article>
</template>

<script lang="ts" setup>
const props = withDefaults(defineProps<{ layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
</script>

<style lang="postcss" scoped>
.skeleton {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  border: 1px solid var(--color-bg-secondary);
  padding: 16px;
  border-radius: 8px;
  max-width: 256px;

  aside {
    aspect-ratio: 1/1;
    border-radius: 8px;
  }

  footer {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: center;
    gap: 0.7rem;

    p {
      height: 1.2em;
      width: 80%;

      &.artist {
        width: 33%;
      }

      &.meta {
        width: 55%;
      }
    }
  }

  &.compact {
    flex-direction: row;
    align-items: center;
    max-width: 100%;
    padding: 10px;
    border-radius: 5px;

    aside {
      width: 80px;
    }
  }
}
</style>
