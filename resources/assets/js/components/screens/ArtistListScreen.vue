<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed" :disabled="loading">
        Artists
        <template #controls>
          <div class="flex gap-2">
            <FavoritesOnlyToggle :active="preferences.artists_favorites_only" @toggle="toggleFavoritesOnly" />

            <ArtistListSorter
              v-if="preferences.artists_view_mode !== 'table'"
              :field="preferences.artists_sort_field"
              :order="preferences.artists_sort_order"
              @sort="sort"
            />

            <ViewModeSwitch v-model="preferences.artists_view_mode" secondary="table" />
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <Icon :icon="faMicrophoneSlash" />
      </template>
      No artists found.
      <EmptyLibraryHint />
    </ScreenEmptyState>

    <ScreenEmptyState v-else-if="noFavoriteArtists">
      <template #icon>
        <Icon :icon="faMicrophoneSlash" />
      </template>
      No favorite artists.
    </ScreenEmptyState>

    <template v-else>
      <div
        v-if="showSkeletons && preferences.artists_view_mode === 'table'"
        class="-m-6 flex flex-col"
        role="status"
        aria-busy="true"
        aria-label="Loading"
      >
        <ArtistTableRowSkeleton v-for="i in 12" :key="i" />
      </div>
      <div
        v-else-if="showSkeletons"
        class="grid gap-5 p-6"
        :style="{ gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))' }"
        role="status"
        aria-busy="true"
        aria-label="Loading"
      >
        <ArtistCardSkeleton v-for="i in 10" :key="i" />
      </div>
      <div class="-m-6 flex-1 flex flex-col min-h-0" v-else>
        <ArtistTable
          v-if="preferences.artists_view_mode === 'table'"
          :artists="displayedArtists"
          :loading-more
          :field="preferences.artists_sort_field"
          :order="preferences.artists_sort_order"
          @sort="sort"
          @toggle-favorite="toggleFavorite"
          @scrolled-to-end="fetchArtists"
        />
        <ArtistGrid v-else ref="grid" :artists="displayedArtists" :loading-more @scrolled-to-end="fetchArtists" />
      </div>
    </template>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faMicrophoneSlash } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref, toRef } from 'vue'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { useCursorPaginatedList } from '@/composables/useCursorPaginatedList'

import ArtistCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import ArtistGrid from '@/components/artist/ArtistGrid.vue'
import ArtistTable from '@/components/artist/ArtistTable.vue'
import ArtistTableRowSkeleton from '@/components/artist/ArtistTableRowSkeleton.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import FavoritesOnlyToggle from '@/components/ui/FavoritesOnlyToggle.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import ArtistListSorter from '@/components/artist/ArtistListSorter.vue'
import EmptyLibraryHint from '@/components/ui/EmptyLibraryHint.vue'

const grid = ref<InstanceType<typeof ArtistGrid>>()
const artists = toRef(artistStore.state, 'artists')

const {
  loading,
  displayedItems: displayedArtists,
  noFavorites: noFavoriteArtists,
  showSkeletons,
  loadingMore,
  fetchMore: fetchArtists,
  sort,
  toggleFavoritesOnly,
} = useCursorPaginatedList({
  store: artistStore,
  items: artists,
  favoritesOnly: toRef(preferences, 'artists_favorites_only'),
  sortField: toRef(preferences, 'artists_sort_field'),
  sortOrder: toRef(preferences, 'artists_sort_order'),
  onReset: () => grid.value?.scrollToTop(),
})

const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const toggleFavorite = (artist: Artist) => artistStore.toggleFavorite(artist)

onMounted(() => {
  if (!libraryEmpty.value) {
    fetchArtists()
  }
})
</script>
