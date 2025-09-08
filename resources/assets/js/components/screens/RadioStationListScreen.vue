<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed" :disabled="loading">
        Radio Stations

        <template #controls>
          <div class="flex gap-2">
            <Btn
              v-koel-tooltip
              :title="preferences.radio_stations_favorites_only ? 'Show all' : 'Show favorites only'"
              class="border border-white/10"
              small
              transparent
              @click.prevent="toggleFavoritesOnly"
            >
              <Icon
                :icon="preferences.radio_stations_favorites_only ? faStar : faEmptyStar"
                :class="preferences.radio_stations_favorites_only && 'text-k-highlight'"
                size="sm"
              />
            </Btn>

            <RadioStationListSorter
              :field="preferences.radio_stations_sort_field"
              :order="preferences.radio_stations_sort_order"
              @sort="sort"
            />

            <ListFilter />

            <Btn
              v-if="canAdd"
              v-koel-tooltip
              highlight
              small
              title="Add a new station"
              @click.prevent="requestAddStationForm"
            >
              <Icon :icon="faAdd" fixed-width />
              <span class="sr-only">Add a new station</span>
            </Btn>

            <ViewModeSwitch v-model="preferences.radio_stations_view_mode" />
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="noStations">
      <template #icon>
        <RadioIcon :size="96" />
      </template>
      No stations found.
      <span class="secondary block">Add a station to get started.</span>
    </ScreenEmptyState>

    <div v-else ref="gridContainer" v-koel-overflow-fade class="-m-6 overflow-auto">
      <GridListView ref="grid" :view-mode="preferences.radio_stations_view_mode" data-testid="radio-station-grid">
        <template v-if="showSkeletons">
          <AlbumCardSkeleton v-for="i in 10" :key="i" :layout="itemLayout" />
        </template>
        <template v-else>
          <RadioStationCard
            v-for="station in stations"
            :key="station.id"
            :station
            :layout="itemLayout"
          />
          <BtnScrollToTop />
        </template>
      </GridListView>
    </div>
  </ScreenBase>
</template>

<script setup lang="ts">
import { faAdd, faStar } from '@fortawesome/free-solid-svg-icons'
import { RadioIcon } from 'lucide-vue-next'
import { faStar as faEmptyStar } from '@fortawesome/free-regular-svg-icons'

import { computed, onMounted, provide, ref } from 'vue'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { eventBus } from '@/utils/eventBus'
import { useFuzzySearch } from '@/composables/useFuzzySearch'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { orderBy } from 'lodash'
import { FilterKeywordsKey } from '@/symbols'
import { radioStationStore } from '@/stores/radioStationStore'
import { usePolicies } from '@/composables/usePolicies'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import Btn from '@/components/ui/form/Btn.vue'
import ListFilter from '@/components/ui/ListFilter.vue'
import RadioStationListSorter from '@/components/radio/RadioStationListSorter.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import RadioStationCard from '@/components/radio/RadioStationCard.vue'
import GridListView from '@/components/ui/GridListView.vue'
import AlbumCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import BtnScrollToTop from '@/components/ui/BtnScrollToTop.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'

const { currentUserCan } = usePolicies()
const fuzzy = useFuzzySearch<RadioStation>(radioStationStore.state.stations, ['name', 'description'])

const loading = ref(false)
const keywords = ref('')
const gridContainer = ref<HTMLDivElement>()
const grid = ref<InstanceType<typeof GridListView>>()

provide(FilterKeywordsKey, keywords)

const stations = computed(() => {
  return orderBy(
    keywords.value ? fuzzy.search(keywords.value) : radioStationStore.state.stations,
    preferences.radio_stations_sort_field,
    preferences.radio_stations_sort_order,
  ).filter(station => preferences.radio_stations_favorites_only ? station.favorite : true)
})

const canAdd = computed(() => currentUserCan.addRadioStation())
const noStations = computed(() => !loading.value && stations.value.length === 0)
const showSkeletons = computed(() => loading.value && stations.value.length === 0)
const itemLayout = computed<CardLayout>(() => preferences.radio_stations_view_mode === 'list' ? 'compact' : 'full')

const fetchStations = async () => {
  if (loading.value) {
    return
  }

  loading.value = true

  try {
    await radioStationStore.fetchAll()
  } catch (error: any) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const toggleFavoritesOnly = () => {
  preferences.radio_stations_favorites_only = !preferences.radio_stations_favorites_only
}

const sort = (field: RadioStationListSortField, order: SortOrder) => {
  preferences.radio_stations_sort_field = field
  preferences.radio_stations_sort_order = order
}

const requestAddStationForm = () => eventBus.emit('MODAL_SHOW_ADD_RADIO_STATION_FORM')

onMounted(async () => await fetchStations())
</script>
