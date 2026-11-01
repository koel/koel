<template>
  <div class="album-table-wrap relative flex flex-col flex-1 overflow-auto" data-testid="album-table">
    <VirtualScroller
      ref="scroller"
      carries-screen-header
      tabindex="0"
      class="focus-visible:outline-hidden"
      :items="rows"
      @keydown.a.prevent="selectAllWithKeyboard"
      @keydown.up.down.prevent.stop="onArrowKey"
      @keydown.enter.prevent.stop="openSelectedItem"
      :item-height="64"
      :loading-more
      @scrolled-to-end="$emit('scrolled-to-end')"
    >
      <template #before>
        <ScreenHeaderPinned>
          <div class="album-table-header sortable flex bg-k-fg-3 pl-5">
            <SortableColumnHeader :active="field === 'name'" :order class="name" @sort="onSort('name')">
              Name
            </SortableColumnHeader>
            <SortableColumnHeader
              v-if="shouldShowColumn('artist')"
              :active="field === 'artist_name'"
              :order
              class="artist max-md:hidden"
              label="Artist"
              @sort="onSort('artist_name')"
            />
            <SortableColumnHeader
              v-if="shouldShowColumn('time')"
              :active="field === 'length'"
              :order
              class="time"
              label="Time"
              @sort="onSort('length')"
            />
            <SortableColumnHeader
              v-if="shouldShowColumn('year')"
              :active="field === 'year'"
              :order
              class="year"
              label="Year"
              @sort="onSort('year')"
            />
            <SortableColumnHeader
              v-if="shouldShowColumn('rating')"
              :active="field === 'rating'"
              :order
              class="rating"
              label="Rating"
              @sort="onSort('rating')"
            />
            <SortableColumnHeader
              v-if="shouldShowColumn('favorite')"
              :active="field === 'favorite'"
              :order
              class="favorite"
              @sort="onSort('favorite')"
            >
              <Icon :icon="faHeart" />
              <span class="sr-only">Favorite</span>
            </SortableColumnHeader>
            <span class="extra">
              <TableHeaderActionMenu
                :field
                :order
                :items="albumTableMenuItems"
                :column-config="albumTableColumnConfig"
                @sort="onSort"
              />
            </span>
          </div>
        </ScreenHeaderPinned>
      </template>
      <template #default="{ item: row }: { item: SelectableRow<Album> }">
        <AlbumRow
          :album="row.item"
          :class="{ selected: row.selected }"
          @click="onRowClick(row, $event)"
          @open-context-menu="openContextMenuForRow(row, $event)"
          @toggle-favorite="emit('toggle-favorite', $event)"
        />
      </template>
      <template #placeholder>
        <AlbumTableRowSkeleton />
      </template>
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { faHeart } from '@fortawesome/free-solid-svg-icons'
import { ref, toRefs } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import { useRouter } from '@/composables/useRouter'
import type { SelectableRow } from '@/composables/useSelectableRows'
import { useSelectableRows } from '@/composables/useSelectableRows'
import { defineAsyncComponent } from '@/utils/helpers'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { albumTableColumnConfig, albumTableMenuItems } from '@/config/tables'

import SortableColumnHeader from '@/components/ui/SortableColumnHeader.vue'
import TableHeaderActionMenu from '@/components/ui/TableHeaderActionMenu.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import ScreenHeaderPinned from '@/components/ui/ScreenHeaderPinned.vue'
import AlbumRow from '@/components/album/AlbumRow.vue'
import AlbumTableRowSkeleton from '@/components/album/AlbumTableRowSkeleton.vue'

const props = defineProps<{
  albums: Album[]
  field: AlbumListSortField
  order: SortOrder
  loadingMore?: boolean
}>()

const emit = defineEmits<{
  (e: 'sort', field: AlbumListSortField, order: SortOrder): void
  (e: 'toggle-favorite', album: Album): void
  (e: 'scrolled-to-end'): void
}>()

const { shouldShowColumn } = useTableColumnVisibility(albumTableColumnConfig)
const { field, order } = toRefs(props)

const AlbumContextMenu = defineAsyncComponent(() => import('@/components/album/AlbumContextMenu.vue'))
const SelectedAlbumsContextMenu = defineAsyncComponent(() => import('@/components/album/SelectedAlbumsContextMenu.vue'))

const { albums } = toRefs(props)
const scroller = ref<InstanceType<typeof VirtualScroller>>()

const { openContextMenu } = useContextMenu()
const { go, url } = useRouter()

const { rows, selectedItems, onRowClick, onArrowKey, selectAllWithKeyboard, selectRowForContextMenu } =
  useSelectableRows(albums, scroller)

const openContextMenuForRow = async (row: SelectableRow<Album>, event: MouseEvent) => {
  await selectRowForContextMenu(row)

  if (selectedItems.value.length > 1) {
    openContextMenu<'SELECTED_ALBUMS'>(SelectedAlbumsContextMenu, event, { albums: selectedItems.value })
  } else {
    openContextMenu<'ALBUM'>(AlbumContextMenu, event, { album: row.item })
  }
}

const openSelectedItem = () => {
  if (selectedItems.value.length === 1) {
    go(url('albums.show', { id: selectedItems.value[0].id }))
  }
}

const onSort = (clicked: AlbumListSortField) => {
  const nextOrder: SortOrder = field.value === clicked && order.value === 'asc' ? 'desc' : 'asc'
  emit('sort', clicked, nextOrder)
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.album-table-wrap {
  .album-table-header > span {
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
