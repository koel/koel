<template>
  <div class="artist-table-wrap relative flex flex-col flex-1 overflow-auto" data-testid="artist-table">
    <VirtualScroller
      carries-screen-header
      :items="artists"
      :item-height="64"
      @scrolled-to-end="$emit('scrolled-to-end')"
    >
      <template #before>
        <ScreenHeaderPinned>
          <div class="artist-table-header sortable flex bg-k-fg-3 pl-5">
            <SortableColumnHeader
              :active="field === 'name'"
              :order
              class="name"
              title="Sort by name"
              @sort="onSort('name')"
            >
              Name
            </SortableColumnHeader>
            <SortableColumnHeader
              v-if="shouldShowColumn('rating')"
              :active="field === 'rating'"
              :order
              class="rating"
              title="Sort by rating"
              label="Rating"
              @sort="onSort('rating')"
            />
            <SortableColumnHeader
              v-if="shouldShowColumn('favorite')"
              :active="field === 'favorite'"
              :order
              class="favorite"
              title="Sort by favorite"
              @sort="onSort('favorite')"
            >
              <Icon :icon="faHeart"
            /></SortableColumnHeader>
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
      <template #default="{ item }: { item: Artist }">
        <ArtistRow :artist="item" @toggle-favorite="emit('toggle-favorite', $event)" />
      </template>
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { faHeart } from '@fortawesome/free-solid-svg-icons'
import { toRefs } from 'vue'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { artistTableColumnConfig, artistTableMenuItems } from '@/config/tables'

import SortableColumnHeader from '@/components/ui/SortableColumnHeader.vue'
import TableHeaderActionMenu from '@/components/ui/TableHeaderActionMenu.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import ScreenHeaderPinned from '@/components/ui/ScreenHeaderPinned.vue'
import ArtistRow from '@/components/artist/ArtistRow.vue'

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

const { shouldShowColumn } = useTableColumnVisibility(artistTableColumnConfig)
const { field, order } = toRefs(props)

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
