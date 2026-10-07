<template>
  <div class="radio-station-table-wrap relative flex flex-col flex-1 overflow-auto" data-testid="radio-station-table">
    <div class="radio-station-table-header sortable flex z-2 bg-k-fg-3 pl-5 sticky top-0">
      <SortableColumnHeader :active="field === 'name'" :order class="name" title="Sort by name" @sort="onSort('name')">
        Name
      </SortableColumnHeader>
      <span v-if="shouldShowColumn('description')" class="description">Description</span>
      <SortableColumnHeader
        v-if="shouldShowColumn('created_at')"
        :active="field === 'created_at'"
        :order
        class="created-at"
        title="Sort by date added"
        label="Date Added"
        @sort="onSort('created_at')"
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
        <RadioStationTableHeaderActionMenu :field :order @sort="onSort" />
      </span>
    </div>

    <VirtualScroller :items="stations" :item-height="64">
      <template #default="{ item }: { item: RadioStation }">
        <RadioStationRow :station="item" @toggle-favorite="emit('toggle-favorite', $event)" />
      </template>
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { faHeart } from '@fortawesome/free-solid-svg-icons'
import { toRefs } from 'vue'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { radioStationTableColumnConfig } from '@/config/tables'

import SortableColumnHeader from '@/components/ui/SortableColumnHeader.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import RadioStationRow from '@/components/radio/RadioStationRow.vue'
import RadioStationTableHeaderActionMenu from '@/components/radio/RadioStationTableHeaderActionMenu.vue'

const props = defineProps<{
  stations: RadioStation[]
  field: RadioStationListSortField
  order: SortOrder
}>()

const emit = defineEmits<{
  (e: 'sort', field: RadioStationListSortField, order: SortOrder): void
  (e: 'toggle-favorite', station: RadioStation): void
}>()

const { shouldShowColumn } = useTableColumnVisibility(radioStationTableColumnConfig)
const { field, order } = toRefs(props)

const onSort = (clicked: RadioStationListSortField) => {
  const nextOrder: SortOrder = field.value === clicked && order.value === 'asc' ? 'desc' : 'asc'
  emit('sort', clicked, nextOrder)
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.radio-station-table-wrap {
  .radio-station-table-header > span {
    @apply text-left p-2 align-middle truncate;

    &.name {
      @apply flex-1 min-w-0 flex items-center;
    }

    &.description {
      @apply flex-[2_1_0%] min-w-0;
    }

    &.created-at {
      @apply basis-32;
    }

    &.favorite {
      @apply basis-16 text-center;
    }

    &.extra {
      @apply basis-12 text-center;
    }
  }

  .radio-station-table-header {
    @apply tracking-widest uppercase cursor-pointer text-k-fg-70;

    .extra {
      @apply px-0;
    }
  }
}
</style>
