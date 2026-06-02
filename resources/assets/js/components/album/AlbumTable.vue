<template>
  <div class="album-table-wrap relative flex flex-col flex-1 overflow-auto" data-testid="album-table">
    <div class="album-table-header sortable flex z-2 bg-k-fg-3 pl-5 sticky top-0">
      <span
        class="name"
        role="button"
        tabindex="0"
        title="Sort by name"
        @click="onSort('name')"
        @keydown.enter.space.prevent="onSort('name')"
      >
        Name
        <Icon v-if="field === 'name' && order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
        <Icon v-if="field === 'name' && order === 'desc'" :icon="faCaretDown" class="ml-2 text-k-highlight" />
      </span>
      <span
        v-if="shouldShowColumn('artist')"
        class="artist"
        role="button"
        tabindex="0"
        title="Sort by artist"
        @click="onSort('artist_name')"
        @keydown.enter.space.prevent="onSort('artist_name')"
      >
        Artist
        <Icon v-if="field === 'artist_name' && order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
        <Icon v-if="field === 'artist_name' && order === 'desc'" :icon="faCaretDown" class="ml-2 text-k-highlight" />
      </span>
      <span
        v-if="shouldShowColumn('time')"
        class="time"
        role="button"
        tabindex="0"
        title="Sort by duration"
        @click="onSort('length')"
        @keydown.enter.space.prevent="onSort('length')"
      >
        Time
        <Icon v-if="field === 'length' && order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
        <Icon v-if="field === 'length' && order === 'desc'" :icon="faCaretDown" class="ml-2 text-k-highlight" />
      </span>
      <span
        v-if="shouldShowColumn('year')"
        class="year"
        role="button"
        tabindex="0"
        title="Sort by year"
        @click="onSort('year')"
        @keydown.enter.space.prevent="onSort('year')"
      >
        Year
        <Icon v-if="field === 'year' && order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
        <Icon v-if="field === 'year' && order === 'desc'" :icon="faCaretDown" class="ml-2 text-k-highlight" />
      </span>
      <span
        v-if="shouldShowColumn('rating')"
        class="rating"
        role="button"
        tabindex="0"
        title="Sort by rating"
        @click="onSort('rating')"
        @keydown.enter.space.prevent="onSort('rating')"
      >
        Rating
        <Icon v-if="field === 'rating' && order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
        <Icon v-if="field === 'rating' && order === 'desc'" :icon="faCaretDown" class="ml-2 text-k-highlight" />
      </span>
      <span
        v-if="shouldShowColumn('favorite')"
        class="favorite"
        role="button"
        tabindex="0"
        title="Sort by favorite"
        @click="onSort('favorite')"
        @keydown.enter.space.prevent="onSort('favorite')"
      >
        <Icon :icon="faHeart" />
        <Icon v-if="field === 'favorite' && order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
        <Icon v-if="field === 'favorite' && order === 'desc'" :icon="faCaretDown" class="ml-2 text-k-highlight" />
      </span>
      <span class="extra">
        <AlbumTableHeaderActionMenu :field="field" :order="order" @sort="onSort" />
      </span>
    </div>

    <VirtualScroller :items="albums" :item-height="64" @scrolled-to-end="$emit('scrolled-to-end')">
      <template #default="{ item }: { item: Album }">
        <article
          class="album-row group pl-2 flex items-center h-[64px] border-b border-k-fg-10 hover:bg-k-fg-5 transition-colors"
          data-testid="album-row"
          :draggable="true"
          @contextmenu.prevent="onContextMenu(item, $event)"
          @dblclick.prevent.stop="goToAlbum(item)"
          @dragstart="onDragStart(item, $event)"
        >
          <span class="name flex gap-3 items-center min-w-0">
            <span
              :style="{ backgroundImage: `url(${defaultCover})` }"
              class="w-[48px] aspect-square rounded-sm bg-cover bg-center flex-none overflow-hidden"
            >
              <img
                v-if="item.cover"
                :src="item.cover"
                alt=""
                class="w-full aspect-square object-cover"
                loading="lazy"
              />
            </span>
            <a :href="url('albums.show', { id: item.id })" class="truncate">{{ item.name }}</a>
          </span>
          <span v-if="shouldShowColumn('artist')" class="artist truncate">
            <a v-if="artistStore.isStandard(item.artist_id)" :href="url('artists.show', { id: item.artist_id })">
              {{ item.artist_name }}
            </a>
            <template v-else>{{ item.artist_name }}</template>
          </span>
          <span v-if="shouldShowColumn('time')" class="time text-k-fg-50 tabular-nums">
            {{ formatLength(item.length) }}
          </span>
          <span v-if="shouldShowColumn('year')" class="year text-k-fg-50 tabular-nums">{{ item.year ?? '—' }}</span>
          <span v-if="shouldShowColumn('rating')" class="rating">
            <StarRating :rateable="item" size="xs" />
          </span>
          <span v-if="shouldShowColumn('favorite')" class="favorite">
            <FavoriteButton :favorite="item.favorite" @toggle="emit('toggle-favorite', item)" />
          </span>
          <span class="extra">
            <button class="text-k-fg-50 hover:text-k-fg p-1" title="More actions" @click="onContextMenu(item, $event)">
              <Icon :icon="faEllipsis" />
            </button>
          </span>
        </article>
      </template>
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { faCaretDown, faCaretUp, faEllipsis, faHeart } from '@fortawesome/free-solid-svg-icons'
import { toRefs } from 'vue'
import { artistStore } from '@/stores/artistStore'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { useBranding } from '@/composables/useBranding'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { albumTableColumnConfig } from '@/config/tables'
import { secondsToHis } from '@/utils/formatters'
import { defineAsyncComponent } from '@/utils/helpers'

import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import StarRating from '@/components/ui/StarRating.vue'
import FavoriteButton from '@/components/ui/FavoriteButton.vue'
import AlbumTableHeaderActionMenu from '@/components/album/AlbumTableHeaderActionMenu.vue'

const props = defineProps<{
  albums: Album[]
  field: AlbumListSortField
  order: SortOrder
}>()

const emit = defineEmits<{
  (e: 'sort', field: AlbumListSortField, order: SortOrder): void
  (e: 'toggle-favorite', album: Album): void
  (e: 'scrolled-to-end'): void
}>()

const AlbumContextMenu = defineAsyncComponent(() => import('@/components/album/AlbumContextMenu.vue'))

const { go, url } = useRouter()
const { openContextMenu } = useContextMenu()
const { startDragging } = useDraggable('album')
const { cover: defaultCover } = useBranding()
const { shouldShowColumn } = useTableColumnVisibility(albumTableColumnConfig)
const { field, order } = toRefs(props)

const formatLength = (seconds: number) => (seconds > 0 ? secondsToHis(seconds) : '—')

const onSort = (clicked: AlbumListSortField) => {
  const nextOrder: SortOrder = field.value === clicked && order.value === 'asc' ? 'desc' : 'asc'
  emit('sort', clicked, nextOrder)
}

const onContextMenu = (album: Album, event: MouseEvent) => openContextMenu<'ALBUM'>(AlbumContextMenu, event, { album })

const goToAlbum = (album: Album) => go(url('albums.show', { id: album.id }))

const onDragStart = (album: Album, event: DragEvent) => startDragging(event, album)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.album-table-wrap {
  .album-table-header > span,
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

  .album-table-header {
    @apply tracking-widest uppercase cursor-pointer text-k-fg-70;

    .extra {
      @apply px-0;
    }
  }
}
</style>
