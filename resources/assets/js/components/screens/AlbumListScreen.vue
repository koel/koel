<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed" :disabled="loading">
        Albums
        <template #controls>
          <div class="flex gap-2">
            <Btn
              v-koel-tooltip
              :title="preferences.albums_favorites_only ? 'Show all' : 'Show favorites only'"
              variant="ghost"
              class="border border-k-fg-10"
              @click.prevent="toggleFavoritesOnly"
            >
              <Icon
                :icon="preferences.albums_favorites_only ? faHeart : faEmptyHeart"
                :class="preferences.albums_favorites_only && 'text-k-love'"
              />
            </Btn>

            <AlbumListSorter
              v-if="preferences.albums_view_mode !== 'table'"
              :field="preferences.albums_sort_field"
              :order="preferences.albums_sort_order"
              @sort="sort"
            />

            <ViewModeSwitch v-model="preferences.albums_view_mode" secondary="table" />
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <Icon :icon="faCompactDisc" />
      </template>
      No albums found.
      <EmptyLibraryHint />
    </ScreenEmptyState>

    <ScreenEmptyState v-else-if="noFavoriteAlbums">
      <template #icon>
        <Icon :icon="faCompactDisc" />
      </template>
      No favorite albums.
    </ScreenEmptyState>

    <template v-else>
      <div
        v-if="showSkeletons && preferences.albums_view_mode === 'table'"
        class="-m-6 flex flex-col"
        role="status"
        aria-busy="true"
        aria-label="Loading"
      >
        <AlbumTableRowSkeleton v-for="i in 12" :key="i" />
      </div>
      <div
        v-else-if="showSkeletons"
        class="grid gap-5 p-6"
        :style="{ gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))' }"
        role="status"
        aria-busy="true"
        aria-label="Loading"
      >
        <AlbumCardSkeleton v-for="i in 10" :key="i" />
      </div>
      <div class="-m-6 flex-1 flex flex-col min-h-0" v-else>
        <AlbumTable
          v-if="preferences.albums_view_mode === 'table'"
          :albums="displayedAlbums"
          :field="preferences.albums_sort_field"
          :order="preferences.albums_sort_order"
          @sort="sort"
          @toggle-favorite="toggleFavorite"
          @scrolled-to-end="fetchAlbums"
        />
        <AlbumGrid
          v-else
          ref="grid"
          :albums="displayedAlbums"
          :show-release-year="preferences.albums_sort_field === 'year'"
          @scrolled-to-end="fetchAlbums"
        />
      </div>
    </template>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faHeart as faEmptyHeart } from '@fortawesome/free-regular-svg-icons'
import { faCompactDisc, faHeart } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref, toRef } from 'vue'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { useCursorPaginatedList } from '@/composables/useCursorPaginatedList'

import AlbumCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import AlbumGrid from '@/components/album/AlbumGrid.vue'
import AlbumTable from '@/components/album/AlbumTable.vue'
import AlbumTableRowSkeleton from '@/components/album/AlbumTableRowSkeleton.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import AlbumListSorter from '@/components/album/AlbumListSorter.vue'
import Btn from '@/components/ui/form/Btn.vue'
import EmptyLibraryHint from '@/components/ui/EmptyLibraryHint.vue'

const grid = ref<InstanceType<typeof AlbumGrid>>()
const albums = toRef(albumStore.state, 'albums')

const {
  loading,
  displayedItems: displayedAlbums,
  noFavorites: noFavoriteAlbums,
  showSkeletons,
  fetchMore: fetchAlbums,
  sort,
  toggleFavoritesOnly,
} = useCursorPaginatedList({
  store: albumStore,
  items: albums,
  favoritesOnly: toRef(preferences, 'albums_favorites_only'),
  sortField: toRef(preferences, 'albums_sort_field'),
  sortOrder: toRef(preferences, 'albums_sort_order'),
  onReset: () => grid.value?.scrollToTop(),
})

const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const toggleFavorite = (album: Album) => albumStore.toggleFavorite(album)

onMounted(() => {
  if (!libraryEmpty.value) {
    fetchAlbums()
  }
})
</script>
