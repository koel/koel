<template>
  <div class="artist-table-wrap relative flex flex-col flex-1 overflow-auto" data-testid="artist-table">
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
          <div class="artist-table-header sortable flex bg-k-fg-3 pl-5">
            <SortableColumnHeader :active="field === 'name'" :order class="name" @sort="onSort('name')">
              Name
            </SortableColumnHeader>
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
                :items="artistTableMenuItems"
                :column-config="artistTableColumnConfig"
                @sort="onSort"
              />
            </span>
          </div>
        </ScreenHeaderPinned>
      </template>
      <template #default="{ item: row }: { item: SelectableRow<Artist> }">
        <ArtistRow
          :artist="row.item"
          :class="{ selected: row.selected }"
          @click="onRowClick(row, $event)"
          @open-context-menu="openContextMenuForRow(row, $event)"
          @toggle-favorite="emit('toggle-favorite', $event)"
        />
      </template>
      <template #placeholder>
        <ArtistTableRowSkeleton />
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
import { artistTableColumnConfig, artistTableMenuItems } from '@/config/tables'

import SortableColumnHeader from '@/components/ui/SortableColumnHeader.vue'
import TableHeaderActionMenu from '@/components/ui/TableHeaderActionMenu.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import ScreenHeaderPinned from '@/components/ui/ScreenHeaderPinned.vue'
import ArtistRow from '@/components/artist/ArtistRow.vue'
import ArtistTableRowSkeleton from '@/components/artist/ArtistTableRowSkeleton.vue'

const props = defineProps<{
  artists: Artist[]
  field: ArtistListSortField
  order: SortOrder
  loadingMore?: boolean
}>()

const emit = defineEmits<{
  (e: 'sort', field: ArtistListSortField, order: SortOrder): void
  (e: 'toggle-favorite', artist: Artist): void
  (e: 'scrolled-to-end'): void
}>()

const { shouldShowColumn } = useTableColumnVisibility(artistTableColumnConfig)
const { field, order } = toRefs(props)

const ArtistContextMenu = defineAsyncComponent(() => import('@/components/artist/ArtistContextMenu.vue'))
const SelectedArtistsContextMenu = defineAsyncComponent(
  () => import('@/components/artist/SelectedArtistsContextMenu.vue'),
)

const { artists } = toRefs(props)
const scroller = ref<InstanceType<typeof VirtualScroller>>()

const { openContextMenu } = useContextMenu()
const { go, url } = useRouter()

const { rows, selectedItems, onRowClick, onArrowKey, selectAllWithKeyboard, selectRowForContextMenu } =
  useSelectableRows(artists, scroller)

const openContextMenuForRow = async (row: SelectableRow<Artist>, event: MouseEvent) => {
  await selectRowForContextMenu(row)

  if (selectedItems.value.length > 1) {
    openContextMenu<'SELECTED_ARTISTS'>(SelectedArtistsContextMenu, event, { artists: selectedItems.value })
  } else {
    openContextMenu<'ARTIST'>(ArtistContextMenu, event, { artist: row.item })
  }
}

const openSelectedItem = () => {
  if (selectedItems.value.length === 1) {
    go(url('artists.show', { id: selectedItems.value[0].id }))
  }
}

const onSort = (clicked: ArtistListSortField) => {
  const nextOrder: SortOrder = field.value === clicked && order.value === 'asc' ? 'desc' : 'asc'
  emit('sort', clicked, nextOrder)
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.artist-table-wrap {
  .artist-table-header > span {
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
