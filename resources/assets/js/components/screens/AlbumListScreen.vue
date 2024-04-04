<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Albums
        <template #controls>
          <ViewModeSwitch v-model="viewMode" />
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <Icon :icon="faCompactDisc" />
      </template>
      No albums found.
      <span class="secondary d-block">
        {{ isAdmin ? 'Have you set up your library yet?' : 'Contact your administrator to set up your library.' }}
      </span>
    </ScreenEmptyState>

    <div v-else ref="gridContainer" v-koel-overflow-fade class="-m-6 overflow-auto">
      <AlbumGrid :view-mode="viewMode" data-testid="album-grid">
        <template v-if="showSkeletons">
          <AlbumCardSkeleton v-for="i in 10" :key="i" :layout="itemLayout" />
        </template>
        <template v-else>
          <AlbumCard v-for="album in albums" :key="album.id" :album="album" :layout="itemLayout" />
          <ToTopButton />
        </template>
      </AlbumGrid>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faCompactDisc } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRef, watch } from 'vue'
import { albumStore, commonStore, preferenceStore as preferences } from '@/stores'
import { useAuthorization, useInfiniteScroll, useMessageToaster, useRouter } from '@/composables'
import { logger } from '@/utils'

import AlbumCard from '@/components/album/AlbumCard.vue'
import AlbumCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import AlbumGrid from '@/components/ui/album-artist/AlbumOrArtistGrid.vue'

const { isAdmin } = useAuthorization()

const gridContainer = ref<HTMLDivElement>()
const viewMode = ref<ArtistAlbumViewMode>('thumbnails')
const albums = toRef(albumStore.state, 'albums')

const {
  ToTopButton,
  makeScrollable
} = useInfiniteScroll(gridContainer, async () => await fetchAlbums())

watch(viewMode, () => (preferences.albums_view_mode = viewMode.value))

let initialized = false
const loading = ref(false)
const page = ref<number | null>(1)

const libraryEmpty = computed(() => commonStore.state.song_length === 0)
const itemLayout = computed<ArtistAlbumCardLayout>(() => viewMode.value === 'thumbnails' ? 'full' : 'compact')
const moreAlbumsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && albums.value.length === 0)

const fetchAlbums = async () => {
  if (loading.value || !moreAlbumsAvailable.value) return

  loading.value = true
  page.value = await albumStore.paginate(page.value!)
  loading.value = false
}

useRouter().onScreenActivated('Albums', async () => {
  if (libraryEmpty.value) return

  if (!initialized) {
    viewMode.value = preferences.albums_view_mode || 'thumbnails'
    initialized = true

    try {
      await makeScrollable()
    } catch (error) {
      logger.error(error)
      useMessageToaster().toastError('Failed to load albums.')
      initialized = false
    }
  }
})
</script>
