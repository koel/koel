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
              class="border border-k-fg-10"
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
      <span v-if="currentUserCan.manageSettings()" class="secondary block"> Have you set up your library yet? </span>
    </ScreenEmptyState>

    <ScreenEmptyState v-else-if="noFavoriteArtists">
      <template #icon>
        <Icon :icon="faMicrophoneSlash" />
      </template>
      No favorite artists.
    </ScreenEmptyState>

    <template v-else>
      <template v-if="showSkeletons">
        <div class="grid gap-5 p-6" :style="{ gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))' }">
          <ArtistCardSkeleton v-for="i in 10" :key="i" :layout="itemLayout" />
        </div>
      </template>
      <div class="-m-6 flex-1 flex flex-col min-h-0" v-else>
        <VirtualGridScroller
          ref="grid"
          :items="displayedArtists"
          :min-item-width="minItemWidth"
          :class="itemLayout === 'full' ? 'gap-y-5' : 'gap-y-3'"
          class="p-6 gap-x-5"
          data-testid="artist-list"
          @scrolled-to-end="fetchArtists"
        >
          <template #default="{ item }">
            <ArtistCard :artist="item" :layout="itemLayout" />
          </template>
        </VirtualGridScroller>
      </div>
    </template>
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
import { usePolicies } from '@/composables/usePolicies'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import VirtualGridScroller from '@/components/ui/VirtualGridScroller.vue'
import ArtistListSorter from '@/components/artist/ArtistListSorter.vue'
import Btn from '@/components/ui/form/Btn.vue'

const { currentUserCan } = usePolicies()

const grid = ref<InstanceType<typeof VirtualGridScroller>>()
const artists = toRef(artistStore.state, 'artists')

const loading = ref(false)
const page = ref<number | null>(1)

const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const itemLayout = computed<CardLayout>(() => (preferences.artists_view_mode === 'thumbnails' ? 'full' : 'compact'))
const minItemWidth = computed(() => (preferences.artists_view_mode === 'thumbnails' ? 240 : 350))

const displayedArtists = computed(() =>
  preferences.artists_favorites_only ? artists.value.filter((a: Artist) => a.favorite) : artists.value,
)

const noFavoriteArtists = computed(
  () =>
    !loading.value &&
    preferences.artists_favorites_only &&
    displayedArtists.value.length === 0 &&
    !moreArtistsAvailable.value,
)
const moreArtistsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && artists.value.length === 0)

const fetchArtists = async () => {
  if (loading.value || !moreArtistsAvailable.value) {
    return
  }

  loading.value = true

  try {
    page.value = await artistStore.paginate({
      favorites_only: preferences.artists_favorites_only,
      page: page!.value || 1,
      sort: preferences.artists_sort_field,
      order: preferences.artists_sort_order,
    })
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const resetState = async () => {
  page.value = 1

  artistStore.reset()
  grid.value?.scrollToTop()
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

onMounted(() => {
  if (!libraryEmpty.value) {
    fetchArtists()
  }
})
</script>
