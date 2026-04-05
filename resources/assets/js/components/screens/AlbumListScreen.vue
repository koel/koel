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
              class="border border-k-fg-10"
              transparent
              @click.prevent="toggleFavoritesOnly"
            >
              <Icon
                :icon="preferences.albums_favorites_only ? faStar : faEmptyStar"
                :class="preferences.albums_favorites_only && 'text-k-highlight'"
              />
            </Btn>

            <AlbumListSorter
              :field="preferences.albums_sort_field"
              :order="preferences.albums_sort_order"
              @sort="sort"
            />

            <ViewModeSwitch v-model="preferences.albums_view_mode" />
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <Icon :icon="faCompactDisc" />
      </template>
      No albums found.
      <span v-if="currentUserCan.manageSettings()" class="secondary block"> Have you set up your library yet? </span>
    </ScreenEmptyState>

    <ScreenEmptyState v-else-if="noFavoriteAlbums">
      <template #icon>
        <Icon :icon="faCompactDisc" />
      </template>
      No favorite albums.
    </ScreenEmptyState>

    <template v-else>
      <template v-if="showSkeletons">
        <div class="grid gap-5 p-6" :style="{ gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))' }">
          <AlbumCardSkeleton v-for="i in 10" :key="i" :layout="itemLayout" />
        </div>
      </template>
      <div class="-m-6 flex-1 flex flex-col min-h-0" v-else>
        <VirtualGridScroller
          ref="grid"
          :items="displayedAlbums"
          :min-item-width="minItemWidth"
          :class="itemLayout === 'full' ? 'gap-y-5' : 'gap-y-3'"
          class="p-6 gap-x-5"
          data-testid="album-grid"
          @scrolled-to-end="fetchAlbums"
        >
          <template #default="{ item }">
            <AlbumCard
              :album="item"
              :layout="itemLayout"
              :show-release-year="preferences.albums_sort_field === 'year'"
            />
          </template>
        </VirtualGridScroller>
      </div>
    </template>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faStar as faEmptyStar } from '@fortawesome/free-regular-svg-icons'
import { faCompactDisc, faStar } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, onMounted, ref, toRef } from 'vue'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePolicies } from '@/composables/usePolicies'

import AlbumCard from '@/components/album/AlbumCard.vue'
import AlbumCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import VirtualGridScroller from '@/components/ui/VirtualGridScroller.vue'
import AlbumListSorter from '@/components/album/AlbumListSorter.vue'
import Btn from '@/components/ui/form/Btn.vue'

const { currentUserCan } = usePolicies()

const grid = ref<InstanceType<typeof VirtualGridScroller>>()
const albums = toRef(albumStore.state, 'albums')

const loading = ref(false)
const page = ref<number | null>(1)

const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const itemLayout = computed<CardLayout>(() => (preferences.albums_view_mode === 'thumbnails' ? 'full' : 'compact'))
const minItemWidth = computed(() => (preferences.albums_view_mode === 'thumbnails' ? 240 : 350))

const displayedAlbums = computed(() =>
  preferences.albums_favorites_only ? albums.value.filter((a: Album) => a.favorite) : albums.value,
)

const noFavoriteAlbums = computed(
  () =>
    !loading.value &&
    preferences.albums_favorites_only &&
    displayedAlbums.value.length === 0 &&
    !moreAlbumsAvailable.value,
)
const moreAlbumsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && albums.value.length === 0)

const fetchAlbums = async () => {
  if (loading.value || !moreAlbumsAvailable.value) {
    return
  }

  loading.value = true

  try {
    page.value = await albumStore.paginate({
      favorites_only: preferences.albums_favorites_only,
      page: page!.value || 1,
      sort: preferences.albums_sort_field,
      order: preferences.albums_sort_order,
    })
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const resetState = async () => {
  page.value = 1

  albumStore.reset()
  grid.value?.scrollToTop()
}

const sort = async (field: AlbumListSortField, order: SortOrder) => {
  preferences.albums_sort_field = field
  preferences.albums_sort_order = order

  await resetState()
  await nextTick()
  await fetchAlbums()
}

const toggleFavoritesOnly = async () => {
  preferences.albums_favorites_only = !preferences.albums_favorites_only

  await resetState()
  await nextTick()
  await fetchAlbums()
}

onMounted(() => {
  if (!libraryEmpty.value) {
    fetchAlbums()
  }
})
</script>
