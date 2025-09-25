<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed" :disabled="loading">
        Artists
        <template #controls>
          <div class="flex gap-2">
            <Btn
              v-koel-tooltip
              :title="preferences.artists_favorites_only ? 'Show all' : 'Show favorites only'"
              class="border border-white/10"
              transparent
              @click.prevent="toggleFavoritesOnly"
            >
              <Icon
                :icon="preferences.artists_favorites_only ? faStar : faEmptyStar"
                :class="preferences.artists_favorites_only && 'text-k-highlight'"
              />
            </Btn>

            <ArtistListSorter
              :field="preferences.artists_sort_field"
              :order="preferences.artists_sort_order"
              @sort="sort"
            />

            <ViewModeSwitch v-model="preferences.artists_view_mode" />
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <Icon :icon="faMicrophoneSlash" />
      </template>
      No artists found.
      <span v-if="currentUserCan.manageSettings()" class="secondary block">
        Have you set up your library yet?
      </span>
    </ScreenEmptyState>

    <div v-else ref="gridContainer" v-koel-overflow-fade class="-m-6 overflow-auto">
      <GridListView :view-mode="preferences.artists_view_mode" data-testid="artist-list">
        <template v-if="showSkeletons">
          <ArtistCardSkeleton v-for="i in 10" :key="i" :layout="itemLayout" />
        </template>
        <template v-else>
          <ArtistCard v-for="artist in artists" :key="artist.id" :artist="artist" :layout="itemLayout" />
          <ToTopButton />
        </template>
      </GridListView>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faMicrophoneSlash, faStar } from '@fortawesome/free-solid-svg-icons'
import { faStar as faEmptyStar } from '@fortawesome/free-regular-svg-icons'
import { computed, nextTick, onMounted, ref, toRef } from 'vue'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useInfiniteScroll } from '@/composables/useInfiniteScroll'
import { usePolicies } from '@/composables/usePolicies'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import GridListView from '@/components/ui/GridListView.vue'
import ArtistListSorter from '@/components/artist/ArtistListSorter.vue'
import Btn from '@/components/ui/form/Btn.vue'

const { currentUserCan } = usePolicies()

const gridContainer = ref<HTMLDivElement>()
const grid = ref<InstanceType<typeof GridListView>>()
const artists = toRef(artistStore.state, 'artists')

const loading = ref(false)
const page = ref<number | null>(1)

const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const itemLayout = computed<CardLayout>(
  () => preferences.artists_view_mode === 'thumbnails' ? 'full' : 'compact',
)

const moreArtistsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && artists.value.length === 0)

const fetchArtists = async () => {
  if (loading.value || !moreArtistsAvailable.value) {
    return
  }

  loading.value = true

  page.value = await artistStore.paginate({
    favorites_only: preferences.artists_favorites_only,
    page: page!.value || 1,
    sort: preferences.artists_sort_field,
    order: preferences.artists_sort_order,
  })

  loading.value = false
}

const {
  ToTopButton,
  makeScrollable,
} = useInfiniteScroll(gridContainer, async () => await fetchArtists())

const resetState = async () => {
  page.value = 1

  artistStore.reset()
  await grid.value?.scrollToTop()
}

const sort = async (field: ArtistListSortField, order: SortOrder) => {
  preferences.artists_sort_field = field
  preferences.artists_sort_order = order

  await resetState()
  await nextTick()
  await fetchArtists()
}

const toggleFavoritesOnly = async () => {
  preferences.artists_favorites_only = !preferences.artists_favorites_only

  await resetState()
  await nextTick()
  await fetchArtists()
}

onMounted(async () => {
  if (libraryEmpty.value) {
    return
  }

  try {
    await makeScrollable()
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  }
})
</script>
