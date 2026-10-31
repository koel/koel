<template>
  <article
    class="artist-row selectable-list-item group pl-5 flex items-center h-[64px] border-b border-k-fg-10 hover:bg-k-fg-5 transition-colors"
    data-testid="artist-row"
    :draggable="true"
    @contextmenu.prevent="emit('open-context-menu', $event)"
    @dblclick.prevent.stop="goToArtist"
    @dragstart="onDragStart"
  >
    <span class="name flex gap-3 items-center min-w-0">
      <span class="size-[48px] flex-none">
        <AlbumOrArtistThumbnail :entity="artist" size="sm" />
      </span>
      <a :href="url('artists.show', { id: artist.id })" class="truncate">{{ artist.name }}</a>
    </span>
    <span v-if="shouldShowColumn('rating')" class="rating">
      <StarRating :rateable="artist" size="xs" />
    </span>
    <span v-if="shouldShowColumn('favorite')" class="favorite">
      <FavoriteButton :favorite="artist.favorite" @toggle="emit('toggle-favorite', artist)" />
    </span>
    <span class="extra">
      <button class="text-k-fg-50 hover:text-k-fg p-1" @click.stop="emit('open-context-menu', $event)">
        <Icon :icon="faEllipsis" />
        <span class="sr-only">More actions</span>
      </button>
    </span>
  </article>
</template>

<script lang="ts" setup>
import { faEllipsis } from '@fortawesome/free-solid-svg-icons'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useRouter } from '@/composables/useRouter'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { artistTableColumnConfig } from '@/config/tables'

import StarRating from '@/components/ui/StarRating.vue'
import FavoriteButton from '@/components/ui/FavoriteButton.vue'
import AlbumOrArtistThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'

const props = defineProps<{ artist: Artist }>()

const emit = defineEmits<{
  (e: 'toggle-favorite', artist: Artist): void
  (e: 'open-context-menu', event: MouseEvent): void
}>()

const { go, url } = useRouter()
const { startDragging } = useDraggable('artist')
const { shouldShowColumn } = useTableColumnVisibility(artistTableColumnConfig)

const goToArtist = () => go(url('artists.show', { id: props.artist.id }))

const onDragStart = (event: DragEvent) => startDragging(event, props.artist)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.artist-row > span {
  @apply text-left p-2 align-middle truncate;

  &.name {
    @apply flex-1 min-w-0 flex items-center;
  }

  &.rating {
    @apply basis-32 flex items-center;
  }

  &.favorite {
    @apply basis-16 text-center;
  }

  &.extra {
    @apply basis-12 text-center;
  }
}
</style>
