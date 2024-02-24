<template>
  <article
    class="item"
    :class="layout"
    draggable="true"
    tabindex="0"
    @dblclick="onDblClick"
    @dragstart="onDragStart"
    @contextmenu.prevent="onContextMenu"
  >
    <AlbumArtistThumbnail :entity="entity" />
    <footer>
      <div class="name">
        <slot name="name" />
      </div>
      <p class="meta">
        <slot name="meta" />
      </p>
    </footer>
  </article>
</template>

<script lang="ts" setup>
import AlbumArtistThumbnail from '@/components/ui/ArtistAlbumThumbnail.vue'

const props = withDefaults(
  defineProps<{ layout?: ArtistAlbumCardLayout, entity: Artist | Album }>(),
  { layout: 'full' }
)

const emit = defineEmits<{
  (e: 'dblclick'): void,
  (e: 'dragstart', event: DragEvent): void,
  (e: 'contextmenu', event: MouseEvent): void
}>()

const onDblClick = () => emit('dblclick')
const onDragStart = (e: DragEvent) => emit('dragstart', e)
const onContextMenu = (e: MouseEvent) => emit('contextmenu', e)
</script>

<style lang="scss" scoped>
.item {
  position: relative;
  max-width: 256px;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-bg-secondary);
  padding: 16px;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  transition: border-color .2s ease-in-out;

  &:hover {
    border-color: rgba(255, 255, 255, .15);
  }

  .name {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    white-space: nowrap;

    &:deep(a) {
      color: var(--color-text-primary);
      overflow: hidden;
      text-overflow: ellipsis;
    }

    &:deep(a:hover), &:deep(a:active), &:deep(a:focus) {
      color: var(--color-accent);
    }
  }

  &:focus, &:focus-within {
    box-shadow: 0 0 1px 1px var(--color-accent);
  }

  &.compact {
    gap: 1rem;
    flex-direction: row;
    align-items: center;
    max-width: 100%;
    padding: 10px;
    border-radius: 5px;

    .cover {
      width: 80px;
      border-radius: 5px;
    }
  }

  footer {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: .4rem;
  }

  .meta {
    font-size: .9rem;
    display: flex;
    gap: .3rem;
    opacity: .7;

    :deep(a) {
      & + a {
        &::before {
          content: '•';
          margin-right: .2rem;
          color: var(--color-text-secondary);
          font-weight: unset;
        }
      }
    }

    &:hover {
      opacity: 1;
    }
  }

  @media only screen and (max-width: 768px) {
    max-width: 100%;
  }
}
</style>
