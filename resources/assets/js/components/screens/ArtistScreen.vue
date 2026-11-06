<template>
  <ScreenBase :background-image="artist?.image || undefined">
    <template #header>
      <ScreenHeaderSkeleton v-if="loading && !artist" role="status" aria-busy="true" aria-label="Loading" />

      <ScreenHeader v-if="artist" :disabled="loading" :layout="songs.length ? 'expanded' : 'collapsed'">
        {{ artist.name }}

        <template #thumbnail>
          <ArtistThumbnail :entity="artist" />
        </template>

        <template #meta>
          <span>{{ pluralize(albumCount, 'album') }}</span>
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
              v-if="artist.favorite"
              :favorite="artist.favorite"
              class="px-3.5 py-2"
              @toggle="toggleFavorite"
            />
            <StarRating :rateable="artist" class="px-2" />
            <Btn variant="ghost" @click="requestContextMenu">
              <Icon :icon="faEllipsis" fixed-width />
              <span class="sr-only">More Actions</span>
            </Btn>
          </SongListControls>
        </template>
      </ScreenHeader>
    </template>

    <ScreenTabs v-if="artist" class="-m-6" :class="loading && 'pointer-events-none'">
      <div v-if="view === 'songs'" class="songs-pane">
        <ScreenHeaderSpace v-if="loading" />
        <SongListSkeleton v-if="loading" role="status" aria-busy="true" aria-label="Loading" />
        <SongList v-if="!loading && artist" ref="songList" @sort="saveSort" @press:enter="onPressEnter" />
      </div>

      <ScreenHeaderScrollBox v-else class="overview-pane flex-1 px-6 pb-6">
        <div class="space-y-12">
          <ShelfSection v-if="loading || songs.length" data-testid="artist-top-songs">
            <template #header>Songs</template>
            <template #actions>
              <Btn
                v-if="songs.length > topSongs.length"
                :href="url('artists.show', { id: artist.id, tab: 'songs' })"
                rounded
                size="small"
                tag="a"
                variant="highlight"
              >
                Show All
              </Btn>
            </template>
            <PlayableCardGridSkeleton
              v-if="loading"
              class="-mx-6"
              role="status"
              aria-busy="true"
              aria-label="Loading"
            />
            <PlayableCardGrid v-else class="-mx-6" :playables="topSongs" />
          </ShelfSection>

          <ShelfSection v-if="!albums || albums.length" data-testid="artist-albums">
            <template #header>Albums</template>
            <Carousel>
              <template v-if="albums">
                <AlbumCard v-for="album in albums" :key="album.id" :album :show-release-year="true" />
              </template>
              <template v-else>
                <AlbumCardSkeleton v-for="i in 6" :key="i" />
              </template>
            </Carousel>
          </ShelfSection>

          <ShelfSection v-if="showsInformation && !infoEmpty" data-testid="artist-about">
            <template #header>About</template>
            <ExpandableContentBlock :key="artist.id">
              <ArtistInfo :artist mode="full" @empty="infoEmpty = true" />
            </ExpandableContentBlock>
          </ShelfSection>

          <ShelfSection v-if="useTicketmaster" data-testid="artist-events">
            <template #header>Events</template>
            <ArtistEventList :artist />
          </ShelfSection>
        </div>
      </ScreenHeaderScrollBox>
    </ScreenTabs>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faEllipsis } from '@fortawesome/free-solid-svg-icons'
import { orderBy } from 'lodash-es'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { eventBus } from '@/utils/eventBus'
import { pluralize } from '@/utils/formatters'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore, type SongUpdateResult } from '@/stores/playableStore'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { usePersistedSort } from '@/composables/usePersistedSort'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { useRouter } from '@/composables/useRouter'
import { usePageTitle } from '@/composables/usePageTitle'
import { useContextMenu } from '@/composables/useContextMenu'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ArtistThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'
import ScreenTabs from '@/components/ui/ArtistAlbumScreenTabs.vue'
import ScreenHeaderScrollBox from '@/components/ui/ScreenHeaderScrollBox.vue'
import ScreenHeaderSpace from '@/components/ui/ScreenHeaderSpace.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import ShelfSection from '@/components/ui/ShelfSection.vue'
import Carousel from '@/components/ui/Carousel.vue'
import AlbumCard from '@/components/album/AlbumCard.vue'
import AlbumCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import ExpandableContentBlock from '@/components/ui/album-artist/ExpandableContentBlock.vue'
import PlayableCardGrid from '@/components/screens/home/PlayableCardGrid.vue'
import PlayableCardGridSkeleton from '@/components/screens/home/PlayableCardGridSkeleton.vue'
import Btn from '@/components/ui/form/Btn.vue'

const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const ArtistEventList = defineAsyncComponent(() => import('@/components/artist/ArtistEventList.vue'))
const FavoriteButton = defineAsyncComponent(() => import('@/components/ui/FavoriteButton.vue'))
const StarRating = defineAsyncComponent(() => import('@/components/ui/StarRating.vue'))
const ArtistContextMenu = defineAsyncComponent(() => import('@/components/artist/ArtistContextMenu.vue'))

const TOP_SONG_COUNT = 10

const { PlayableListControls: SongListControls, config } = usePlayableListControls('Artist')
const { useLastfm, useMusicBrainz, useTicketmaster } = useThirdPartyServices()
const { getRouteParam, go, onScreenActivated, onRouteChanged, url, triggerNotFound } = useRouter()
const { openContextMenu } = useContextMenu()
const { getSavedSort, saveSort } = usePersistedSort('artist', 'track')

const view = ref<'overview' | 'songs'>('overview')
const artist = ref<Artist>()
const songs = ref<Song[]>([])
const loading = ref(false)
const infoEmpty = ref(false)
const albums = ref<Album[] | undefined>()

usePageTitle().useScreenTitle('Artist', () => artist.value?.name)

const {
  PlayableList: SongList,
  playableList: songList,
  context,
  duration,
  sort,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
} = usePlayableList(songs, { type: 'Artist' })

const useEncyclopedia = computed(() => useMusicBrainz.value || useLastfm.value)
const showsInformation = computed(() => useEncyclopedia.value || Boolean(artist.value?.description))

const topSongs = computed(() => orderBy(songs.value, 'play_count', 'desc').slice(0, TOP_SONG_COUNT))

watch(view, currentView => (config.filter = currentView === 'songs'), { immediate: true })

const albumCount = computed(() => {
  const albums = new Set()
  songs.value.forEach(song => albums.add(song.album_id))
  return albums.size
})

const toggleFavorite = () => artistStore.toggleFavorite(artist.value!)

const fetchAlbums = async (forArtist: Artist) => {
  try {
    albums.value = await albumStore.fetchForArtist(forArtist)
  } catch (error: unknown) {
    albums.value = []
    useErrorHandler().handleHttpError(error)
  }
}

const fetchScreenData = async () => {
  if (loading.value) {
    return
  }

  const id = getRouteParam('id')
  view.value = getRouteParam('tab') === 'songs' ? 'songs' : 'overview'
  albums.value = undefined
  infoEmpty.value = false

  loading.value = true

  try {
    ;[artist.value, songs.value] = await Promise.all([artistStore.resolve(id), playableStore.fetchSongsForArtist(id)])

    if (!artist.value) {
      triggerNotFound()
      return
    }

    context.entity = artist.value

    const { sortField, sortOrder } = getSavedSort()
    sort(sortField, sortOrder)

    if (view.value === 'overview') {
      void fetchAlbums(artist.value)
    }
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

onScreenActivated('Artist', () => fetchScreenData())
onRouteChanged(route => route.name === 'artists.show' && fetchScreenData())

watch(
  () => artist.value?.description,
  () => (infoEmpty.value = false),
)

const requestContextMenu = (event: MouseEvent) =>
  openContextMenu<'ARTIST'>(ArtistContextMenu, event, {
    artist: artist.value!,
  })

const leaveIfArtistRemoved = (result: SongUpdateResult) => {
  if (artist.value && result.removed.artist_ids.includes(artist.value.id)) {
    go(url('artists.index'))
  }
}

const onArtistMerged = (source: Artist, target: Artist) => {
  if (artist.value?.id === source.id) {
    go(url('artists.show', { id: target.id }))
    return
  }

  void fetchScreenData()
}

const refreshScreenData = () => void fetchScreenData()

eventBus
  .on('SONGS_UPDATED', leaveIfArtistRemoved)
  .on('ARTIST_MERGED', onArtistMerged)
  .on('ALBUM_MERGED', refreshScreenData)

onBeforeUnmount(() =>
  eventBus
    .off('SONGS_UPDATED', leaveIfArtistRemoved)
    .off('ARTIST_MERGED', onArtistMerged)
    .off('ALBUM_MERGED', refreshScreenData),
)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.screen-header :deep(.play-icon) {
  @apply scale-[2];
}
</style>
