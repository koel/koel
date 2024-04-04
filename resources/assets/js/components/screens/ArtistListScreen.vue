<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Artists
        <template #controls>
          <ViewModeSwitch v-model="viewMode" />
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <Icon :icon="faMicrophoneSlash" />
      </template>
      No artists found.
      <span class="secondary d-block">
        {{ isAdmin ? 'Have you set up your library yet?' : 'Contact your administrator to set up your library.' }}
      </span>
    </ScreenEmptyState>

    <div v-else ref="gridContainer" v-koel-overflow-fade class="-m-6 overflow-auto">
      <ArtistGrid :view-mode="viewMode" data-testid="artist-list">
        <template v-if="showSkeletons">
          <ArtistCardSkeleton v-for="i in 10" :key="i" :layout="itemLayout" />
        </template>
        <template v-else>
          <ArtistCard v-for="artist in artists" :key="artist.id" :artist="artist" :layout="itemLayout" />
          <ToTopButton />
        </template>
      </ArtistGrid>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faMicrophoneSlash } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRef, watch } from 'vue'
import { artistStore, commonStore, preferenceStore as preferences } from '@/stores'
import { useAuthorization, useInfiniteScroll, useMessageToaster, useRouter } from '@/composables'
import { logger } from '@/utils'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import ArtistGrid from '@/components/ui/album-artist/AlbumOrArtistGrid.vue'

const { isAdmin } = useAuthorization()

const gridContainer = ref<HTMLDivElement>()
const viewMode = ref<ArtistAlbumViewMode>('thumbnails')
const artists = toRef(artistStore.state, 'artists')

const {
  ToTopButton,
  makeScrollable
} = useInfiniteScroll(gridContainer, async () => await fetchArtists())

watch(viewMode, () => preferences.artists_view_mode = viewMode.value)

let initialized = false
const loading = ref(false)
const page = ref<number | null>(1)

const libraryEmpty = computed(() => commonStore.state.song_length === 0)
const itemLayout = computed<ArtistAlbumCardLayout>(() => viewMode.value === 'thumbnails' ? 'full' : 'compact')
const moreArtistsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && artists.value.length === 0)

const fetchArtists = async () => {
  if (loading.value || !moreArtistsAvailable.value) return

  loading.value = true
  page.value = await artistStore.paginate(page.value!)
  loading.value = false
}

useRouter().onScreenActivated('Artists', async () => {
  if (libraryEmpty.value) return
  if (!initialized) {
    viewMode.value = preferences.artists_view_mode || 'thumbnails'
    initialized = true

    try {
      await makeScrollable()
    } catch (error: any) {
      logger.error(error)
      useMessageToaster().toastError(error.response.data?.message || 'Failed to load artists.')
      initialized = false
    }
  }
})
</script>
