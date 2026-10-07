<template>
  <div class="album-table-wrap relative flex flex-col flex-1 overflow-auto" data-testid="album-table">
    <div class="album-table-header sortable flex z-2 bg-k-fg-3 pl-5 sticky top-0">
      <SortableColumnHeader :active="field === 'name'" :order class="name" title="Sort by name" @sort="onSort('name')">
        Name
      </SortableColumnHeader>
      <SortableColumnHeader
        v-if="shouldShowColumn('artist')"
        :active="field === 'artist_name'"
        :order
        class="artist"
        title="Sort by artist"
        label="Artist"
        @sort="onSort('artist_name')"
      />
      <SortableColumnHeader
        v-if="shouldShowColumn('time')"
        :active="field === 'length'"
        :order
        class="time"
        title="Sort by duration"
        label="Time"
        @sort="onSort('length')"
      />
      <SortableColumnHeader
        v-if="shouldShowColumn('year')"
        :active="field === 'year'"
        :order
        class="year"
        title="Sort by year"
        label="Year"
        @sort="onSort('year')"
      />
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
        <AlbumTableHeaderActionMenu :field :order @sort="onSort" />
      </span>
    </div>

    <VirtualScroller :items="albums" :item-height="64" @scrolled-to-end="$emit('scrolled-to-end')">
      <template #default="{ item }: { item: Album }">
        <AlbumRow :album="item" @toggle-favorite="emit('toggle-favorite', $event)" />
      </template>
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { faHeart } from '@fortawesome/free-solid-svg-icons'
import { toRefs } from 'vue'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { albumTableColumnConfig } from '@/config/tables'

import SortableColumnHeader from '@/components/ui/SortableColumnHeader.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import AlbumRow from '@/components/album/AlbumRow.vue'
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

const { shouldShowColumn } = useTableColumnVisibility(albumTableColumnConfig)
const { field, order } = toRefs(props)

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
