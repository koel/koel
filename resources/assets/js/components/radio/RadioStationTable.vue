<template>
  <div class="radio-station-table-wrap relative flex flex-col flex-1 overflow-auto" data-testid="radio-station-table">
    <VirtualScroller
      ref="scroller"
      carries-screen-header
      tabindex="0"
      class="focus-visible:outline-hidden"
      :items="rows"
      @keydown.a.prevent="selectAllWithKeyboard"
      @keydown.up.down.prevent.stop="onArrowKey"
      :item-height="64"
    >
      <template #before>
        <ScreenHeaderPinned>
          <div class="radio-station-table-header sortable flex bg-k-fg-3 pl-5">
            <SortableColumnHeader :active="field === 'name'" :order class="name" @sort="onSort('name')">
              Name
            </SortableColumnHeader>
            <span v-if="shouldShowColumn('description')" class="description max-md:hidden">Description</span>
            <SortableColumnHeader
              v-if="shouldShowColumn('created_at')"
              :active="field === 'created_at'"
              :order
              class="created-at"
              label="Date Added"
              @sort="onSort('created_at')"
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
                :items="radioStationTableMenuItems"
                :column-config="radioStationTableColumnConfig"
                @sort="onSort"
              />
            </span>
          </div>
        </ScreenHeaderPinned>
      </template>
      <template #default="{ item: row }: { item: SelectableRow<RadioStation> }">
        <RadioStationRow
          :station="row.item"
          :class="{ selected: row.selected }"
          @click="onRowClick(row, $event)"
          @open-context-menu="openContextMenuForRow(row, $event)"
          @toggle-favorite="emit('toggle-favorite', $event)"
        />
      </template>
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { faHeart } from '@fortawesome/free-solid-svg-icons'
import { ref, toRefs } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import type { SelectableRow } from '@/composables/useSelectableRows'
import { useSelectableRows } from '@/composables/useSelectableRows'
import { defineAsyncComponent } from '@/utils/helpers'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { radioStationTableColumnConfig, radioStationTableMenuItems } from '@/config/tables'

import SortableColumnHeader from '@/components/ui/SortableColumnHeader.vue'
import TableHeaderActionMenu from '@/components/ui/TableHeaderActionMenu.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import ScreenHeaderPinned from '@/components/ui/ScreenHeaderPinned.vue'
import RadioStationRow from '@/components/radio/RadioStationRow.vue'

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

const RadioStationContextMenu = defineAsyncComponent(() => import('@/components/radio/RadioStationContextMenu.vue'))
const SelectedRadioStationsContextMenu = defineAsyncComponent(
  () => import('@/components/radio/SelectedRadioStationsContextMenu.vue'),
)

const { stations } = toRefs(props)
const scroller = ref<InstanceType<typeof VirtualScroller>>()

const { openContextMenu } = useContextMenu()

const { rows, selectedItems, onRowClick, onArrowKey, selectAllWithKeyboard, selectRowForContextMenu } =
  useSelectableRows(stations, scroller)

const openContextMenuForRow = async (row: SelectableRow<RadioStation>, event: MouseEvent) => {
  await selectRowForContextMenu(row)

  if (selectedItems.value.length > 1) {
    openContextMenu<'SELECTED_RADIO_STATIONS'>(SelectedRadioStationsContextMenu, event, {
      stations: selectedItems.value,
    })
  } else {
    openContextMenu<'RADIO_STATION'>(RadioStationContextMenu, event, { station: row.item })
  }
}

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
