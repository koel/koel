<template>
  <div class="artist-table-wrap relative flex flex-col flex-1 overflow-auto" data-testid="artist-table">
    <div class="artist-table-header sortable flex z-2 bg-k-fg-3 pl-5 sticky top-0">
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
        <ArtistTableHeaderActionMenu :field="field" :order="order" @sort="onSort" />
      </span>
    </div>

    <VirtualScroller :items="artists" :item-height="64" @scrolled-to-end="$emit('scrolled-to-end')">
      <template #default="{ item }: { item: Artist }">
        <article
          class="artist-row group pl-2 flex items-center h-[64px] border-b border-k-fg-10 hover:bg-k-fg-5 transition-colors"
          data-testid="artist-row"
          :draggable="true"
          @contextmenu.prevent="onContextMenu(item, $event)"
          @dblclick.prevent.stop="goToArtist(item)"
          @dragstart="onDragStart(item, $event)"
        >
          <span class="name flex gap-3 items-center min-w-0">
            <span
              :style="{ backgroundImage: `url(${defaultCover})` }"
              class="w-[48px] aspect-square rounded-full bg-cover bg-center flex-none overflow-hidden"
            >
              <img
                v-if="item.image"
                :src="item.image"
                alt=""
                class="w-full aspect-square object-cover"
                loading="lazy"
              />
            </span>
            <a :href="url('artists.show', { id: item.id })" class="truncate">{{ item.name }}</a>
          </span>
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
import { useDraggable } from '@/composables/useDragAndDrop'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { useBranding } from '@/composables/useBranding'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { artistTableColumnConfig } from '@/config/tables'
import { defineAsyncComponent } from '@/utils/helpers'

import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import StarRating from '@/components/ui/StarRating.vue'
import FavoriteButton from '@/components/ui/FavoriteButton.vue'
import ArtistTableHeaderActionMenu from '@/components/artist/ArtistTableHeaderActionMenu.vue'

const props = defineProps<{
  artists: Artist[]
  field: ArtistListSortField
  order: SortOrder
}>()

const emit = defineEmits<{
  (e: 'sort', field: ArtistListSortField, order: SortOrder): void
  (e: 'toggle-favorite', artist: Artist): void
  (e: 'scrolled-to-end'): void
}>()

const ArtistContextMenu = defineAsyncComponent(() => import('@/components/artist/ArtistContextMenu.vue'))

const { go, url } = useRouter()
const { openContextMenu } = useContextMenu()
const { startDragging } = useDraggable('artist')
const { cover: defaultCover } = useBranding()
const { shouldShowColumn } = useTableColumnVisibility(artistTableColumnConfig)
const { field, order } = toRefs(props)

const onSort = (clicked: ArtistListSortField) => {
  const nextOrder: SortOrder = field.value === clicked && order.value === 'asc' ? 'desc' : 'asc'
  emit('sort', clicked, nextOrder)
}

const onContextMenu = (artist: Artist, event: MouseEvent) =>
  openContextMenu<'ARTIST'>(ArtistContextMenu, event, { artist })

const goToArtist = (artist: Artist) => go(url('artists.show', { id: artist.id }))

const onDragStart = (artist: Artist, event: DragEvent) => startDragging(event, artist)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.artist-table-wrap {
  .artist-table-header > span,
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

  .artist-table-header {
    @apply tracking-widest uppercase cursor-pointer text-k-fg-70;

    .extra {
      @apply px-0;
    }
  }
}
</style>
