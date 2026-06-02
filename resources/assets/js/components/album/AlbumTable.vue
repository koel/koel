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
import { faCaretDown, faCaretUp, faHeart } from '@fortawesome/free-solid-svg-icons'
import { toRefs } from 'vue'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { albumTableColumnConfig } from '@/config/tables'

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
