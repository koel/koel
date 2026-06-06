<template>
  <article
    class="album-row group h-[64px] pl-5 flex items-center border-b border-k-fg-10 hover:bg-k-fg-5 transition-colors"
    data-testid="album-row"
    :draggable="true"
    @contextmenu.prevent="onContextMenu"
    @dblclick.prevent.stop="goToAlbum"
    @dragstart="onDragStart"
  >
    <span class="name flex gap-3 items-center min-w-0">
      <span class="size-[48px] flex-none">
        <AlbumOrArtistThumbnail :entity="album" size="sm" />
      </span>
      <a :href="url('albums.show', { id: album.id })" class="truncate">{{ album.name }}</a>
    </span>
    <span v-if="shouldShowColumn('artist')" class="artist truncate">
      <a v-if="artistStore.isStandard(album.artist_id)" :href="url('artists.show', { id: album.artist_id })">
        {{ album.artist_name }}
      </a>
      <template v-else>{{ album.artist_name }}</template>
    </span>
    <span v-if="shouldShowColumn('time')" class="time text-k-fg-50 tabular-nums">
      {{ formatLength(album.length) }}
    </span>
    <span v-if="shouldShowColumn('year')" class="year text-k-fg-50 tabular-nums">{{ album.year ?? '—' }}</span>
    <span v-if="shouldShowColumn('rating')" class="rating">
      <StarRating :rateable="album" size="xs" />
    </span>
    <span v-if="shouldShowColumn('favorite')" class="favorite">
      <FavoriteButton :favorite="album.favorite" @toggle="emit('toggle-favorite', album)" />
    </span>
    <span class="extra">
      <button class="text-k-fg-50 hover:text-k-fg p-1" title="More actions" @click="onContextMenu">
        <Icon :icon="faEllipsis" />
      </button>
    </span>
  </article>
</template>

<script lang="ts" setup>
import { faEllipsis } from '@fortawesome/free-solid-svg-icons'
import { artistStore } from '@/stores/artistStore'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { albumTableColumnConfig } from '@/config/tables'
import { secondsToHis } from '@/utils/formatters'
import { defineAsyncComponent } from '@/utils/helpers'

import StarRating from '@/components/ui/StarRating.vue'
import FavoriteButton from '@/components/ui/FavoriteButton.vue'
import AlbumOrArtistThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'

const props = defineProps<{ album: Album }>()

const emit = defineEmits<{
  (e: 'toggle-favorite', album: Album): void
}>()

const AlbumContextMenu = defineAsyncComponent(() => import('@/components/album/AlbumContextMenu.vue'))

const { go, url } = useRouter()
const { openContextMenu } = useContextMenu()
const { startDragging } = useDraggable('album')
const { shouldShowColumn } = useTableColumnVisibility(albumTableColumnConfig)

const formatLength = (seconds: number) => (seconds > 0 ? secondsToHis(seconds) : '—')

const onContextMenu = (event: MouseEvent) => openContextMenu<'ALBUM'>(AlbumContextMenu, event, { album: props.album })

const goToAlbum = () => go(url('albums.show', { id: props.album.id }))

const onDragStart = (event: DragEvent) => startDragging(event, props.album)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.album-row > span {
  @apply text-left p-2 align-middle truncate;

  &.name {
    @apply flex-1 min-w-0 flex items-center;
  }

  &.artist {
    @apply basis-48;
  }

  &.time {
    @apply basis-24;
  }

  &.year {
    @apply basis-24;
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
