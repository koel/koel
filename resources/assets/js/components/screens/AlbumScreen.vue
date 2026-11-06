<template>
  <ScreenBase :background-image="album?.cover">
    <template #header>
      <ScreenHeaderSkeleton v-if="loading && !album" role="status" aria-busy="true" aria-label="Loading" />

      <ScreenHeader v-if="album" :disabled="loading" :layout="songs.length ? 'expanded' : 'collapsed'">
        {{ album.name }}

        <template #thumbnail>
          <AlbumThumbnail :entity="album" />
        </template>

        <template #meta>
          <a v-if="isStandardArtist" :href="url('artists.show', { id: album.artist_id })" class="artist">
            {{ album.artist_name }}
          </a>
          <span v-else class="text-k-fg">{{ album.artist_name }}</span>
          <span v-if="album.year">{{ album.year }}</span>
          <span>{{ pluralize(songs, 'song') }}</span>
          <span>{{ duration }}</span>
        </template>

        <template #controls>
          <SongListControls
            v-if="songs.length"
            :config
            @filter="applyFilter"
            @play-all="playAll"
            @play-selected="playSelected"
          >
            <FavoriteButton
              v-if="album.favorite"
              :favorite="album.favorite"
              class="px-3.5 py-2"
              @toggle="toggleFavorite"
            />

            <StarRating :rateable="album" class="px-2" />

            <Btn variant="ghost" @click="requestContextMenu">
              <Icon :icon="faEllipsis" fixed-width />
              <span class="sr-only">More Actions</span>
            </Btn>
          </SongListControls>
        </template>
      </ScreenHeader>
    </template>

    <ScreenTabs v-if="album" class="-m-6" :class="loading && 'pointer-events-none'">
      <ScreenHeaderScrollBox class="overview-pane flex-1 px-6 pb-6">
        <div class="space-y-12">
          <SongListSkeleton v-if="loading" class="-mx-6" role="status" aria-busy="true" aria-label="Loading" />
          <SongList v-else ref="songList" class="-mx-6" unvirtualized @sort="saveSort" @press:enter="onPressEnter" />

          <ShelfSection v-if="!otherAlbums || otherAlbums.length" data-testid="album-other-albums">
            <template #header>More by {{ album.artist_name }}</template>
            <Carousel>
              <template v-if="otherAlbums">
                <AlbumCard v-for="otherAlbum in otherAlbums" :key="otherAlbum.id" :album="otherAlbum" />
              </template>
              <template v-else>
                <AlbumCardSkeleton v-for="i in 6" :key="i" />
              </template>
            </Carousel>
          </ShelfSection>

          <ShelfSection v-if="showsInformation && !infoEmpty" data-testid="album-about">
            <template #header>About</template>
            <ExpandableContentBlock>
              <AlbumInfo :album mode="full" @empty="infoEmpty = true" />
            </ExpandableContentBlock>
          </ShelfSection>
        </div>
      </ScreenHeaderScrollBox>
    </ScreenTabs>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faEllipsis } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, onBeforeUnmount, ref, watch } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { pluralize } from '@/utils/formatters'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore, type SongUpdateResult } from '@/stores/playableStore'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { usePersistedSort } from '@/composables/usePersistedSort'
import { useRouter } from '@/composables/useRouter'
import { usePageTitle } from '@/composables/usePageTitle'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { useContextMenu } from '@/composables/useContextMenu'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import AlbumThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'
import ScreenTabs from '@/components/ui/ArtistAlbumScreenTabs.vue'
import ScreenHeaderScrollBox from '@/components/ui/ScreenHeaderScrollBox.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import ShelfSection from '@/components/ui/ShelfSection.vue'
import Carousel from '@/components/ui/Carousel.vue'
import AlbumCard from '@/components/album/AlbumCard.vue'
import AlbumCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import ExpandableContentBlock from '@/components/ui/album-artist/ExpandableContentBlock.vue'
import Btn from '@/components/ui/form/Btn.vue'

const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const ContextMenu = defineAsyncComponent(() => import('@/components/album/AlbumContextMenu.vue'))
const FavoriteButton = defineAsyncComponent(() => import('@/components/ui/FavoriteButton.vue'))
const StarRating = defineAsyncComponent(() => import('@/components/ui/StarRating.vue'))

const { getRouteParam, go, onScreenActivated, onRouteChanged, url, triggerNotFound } = useRouter()
const { PlayableListControls: SongListControls, config } = usePlayableListControls('Album')
const { getSavedSort, saveSort } = usePersistedSort('album', 'track')
const { useLastfm, useMusicBrainz } = useThirdPartyServices()
const { openContextMenu } = useContextMenu()

const album = ref<Album | undefined>()
const songs = ref<Song[]>([])
const loading = ref(false)
const infoEmpty = ref(false)
const otherAlbums = ref<Album[] | undefined>()
const info = ref<ArtistInfo | undefined>()

usePageTitle().useScreenTitle('Album', () => album.value?.name)

const {
  PlayableList: SongList,
  playableList: songList,
  duration,
  context,
  sort,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
} = usePlayableList(songs, { type: 'Album' })

const useEncyclopedia = computed(() => useMusicBrainz.value || useLastfm.value)
const showsInformation = computed(() => useEncyclopedia.value || Boolean(album.value?.description))

const isStandardArtist = computed(() => {
  if (!album.value) {
    return true
  }

  return !artistStore.isVarious(album.value.artist_name) && !artistStore.isUnknown(album.value.artist_name)
})

const toggleFavorite = () => albumStore.toggleFavorite(album.value!)

const fetchOtherAlbums = async (ofAlbum: Album) => {
  try {
    const albums = await albumStore.fetchForArtist(ofAlbum.artist_id)
    otherAlbums.value = albums.filter(({ id }) => id !== ofAlbum.id)
  } catch (error: unknown) {
    otherAlbums.value = []
    useErrorHandler().handleHttpError(error)
  }
}

const fetchScreenData = async () => {
  if (loading.value) {
    return
  }

  const id = getRouteParam('id')

  album.value = undefined
  info.value = undefined
  otherAlbums.value = undefined
  infoEmpty.value = false

  loading.value = true

  try {
    ;[album.value, songs.value] = await Promise.all([albumStore.resolve(id), playableStore.fetchSongsForAlbum(id)])

    if (!album.value) {
      // If the album does not exist, redirect to the album list.
      triggerNotFound()
      return
    }

    context.entity = album.value

    const { sortField, sortOrder } = getSavedSort()
    sort(sortField, sortOrder)

    void fetchOtherAlbums(album.value)
  } catch (error: unknown) {
    if ((error as any)?.status === 404) {
      triggerNotFound()
      return
    }

    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    loading.value = false
  }
}

onScreenActivated('Album', () => fetchScreenData())
onRouteChanged(route => route.name === 'albums.show' && fetchScreenData())

watch(
  () => album.value?.description,
  () => (infoEmpty.value = false),
)

const requestContextMenu = (event: MouseEvent) =>
  openContextMenu<'ALBUM'>(ContextMenu, event, {
    album: album.value!,
  })

const leaveIfAlbumRemoved = (result: SongUpdateResult) => {
  if (album.value && result.removed.album_ids.includes(album.value.id)) {
    go(url('albums.index'))
  }
}

const onAlbumMerged = (source: Album, target: Album) => {
  if (album.value?.id === source.id) {
    go(url('albums.show', { id: target.id }))
    return
  }

  void fetchScreenData()
}

eventBus.on('SONGS_UPDATED', leaveIfAlbumRemoved).on('ALBUM_MERGED', onAlbumMerged)

onBeforeUnmount(() => eventBus.off('SONGS_UPDATED', leaveIfAlbumRemoved).off('ALBUM_MERGED', onAlbumMerged))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.screen-header :deep(.play-icon) {
  @apply scale-[2];
}
</style>
