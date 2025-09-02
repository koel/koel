<template>
  <ScreenBase>
    <template #header>
      <ScreenHeaderSkeleton v-if="loading" />

      <ScreenHeader v-if="!loading && artist" :layout="songs.length === 0 ? 'collapsed' : headerLayout">
        {{ artist.name }}
        <ControlsToggle v-model="showingControls" />

        <template #thumbnail>
          <ArtistThumbnail :entity="artist" />
        </template>

        <template #meta>
          <span class="flex meta-content">
            <span>{{ pluralize(albumCount, 'album') }}</span>
            <span>{{ pluralize(songs, 'item') }}</span>
            <span>{{ duration }}</span>

            <a
              v-if="downloadable"
              class="download"
              role="button"
              title="Download all songs by this artist"
              @click.prevent="download"
            >
              Download All
            </a>

            <span v-if="editable">
              <a role="button" title="Edit artist" @click.prevent="edit">Edit</a>
            </span>
          </span>
        </template>

        <template #controls>
          <div class="flex gap-2">
            <SongListControls
              v-if="songs.length && (!isPhone || showingControls)"
              :config
              @filter="applyFilter"
              @play-all="playAll"
              @play-selected="playSelected"
            />

            <FavoriteButton :favorite="artist.favorite" class="px-3.5 py-2" @toggle="toggleFavorite" />
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenTabs v-if="artist" class="-m-6" :class="loading && 'pointer-events-none'">
      <template #header>
        <nav>
          <ul>
            <li :class="activeTab === 'songs' && 'active'">
              <a :href="url('artists.show', { id: artist.id, tab: 'songs' })">Songs</a>
            </li>
            <li :class="activeTab === 'albums' && 'active'">
              <a :href="url('artists.show', { id: artist.id, tab: 'albums' })">Albums</a>
            </li>
            <li v-if="useEncyclopedia" :class="activeTab === 'information' && 'active'">
              <a :href="url('artists.show', { id: artist.id, tab: 'information' })">Information</a>
            </li>
          </ul>
        </nav>
      </template>

      <div v-show="activeTab === 'songs'" class="songs-pane">
        <SongListSkeleton v-if="loading" />
        <SongList
          v-if="!loading && artist"
          ref="songList"
          @press:enter="onPressEnter"
          @scroll-breakpoint="onScrollBreakpoint"
        />
      </div>

      <div v-show="activeTab === 'albums'" class="albums-pane">
        <GridListView v-koel-overflow-fade view-mode="list">
          <template v-if="albums">
            <AlbumCard
              v-for="album in albums"
              :key="album.id"
              :album="album"
              :show-release-year="true"
              layout="compact"
            />
          </template>
          <template v-else>
            <AlbumCardSkeleton v-for="i in 6" :key="i" layout="compact" />
          </template>
        </GridListView>
      </div>

      <div v-if="useEncyclopedia && artist" v-show="activeTab === 'information'" class="info-pane">
        <ArtistInfo :artist="artist" mode="full" />
      </div>
    </ScreenTabs>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { pluralize } from '@/utils/formatters'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { useRouter } from '@/composables/useRouter'
import { usePolicies } from '@/composables/usePolicies'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ArtistThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/ui/skeletons/PlayableListSkeleton.vue'
import ScreenTabs from '@/components/ui/ArtistAlbumScreenTabs.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import GridListView from '@/components/ui/GridListView.vue'
import FavoriteButton from '@/components/ui/FavoriteButton.vue'

const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))
const AlbumCardSkeleton = defineAsyncComponent(() => import('@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'))

const validTabs = ['songs', 'albums', 'information'] as const
type Tab = typeof validTabs[number]

const { PlayableListControls: SongListControls, config } = usePlayableListControls('Artist')
const { useLastfm, useMusicBrainz } = useThirdPartyServices()
const { getRouteParam, go, onScreenActivated, onRouteChanged, url, triggerNotFound } = useRouter()
const { currentUserCan } = usePolicies()

const activeTab = ref<Tab>('songs')
const artist = ref<Artist>()
const songs = ref<Song[]>([])
const loading = ref(false)
const albums = ref<Album[] | undefined>()
const editable = ref(false)

const {
  PlayableList: SongList,
  ControlsToggle,
  headerLayout,
  playableList: songList,
  showingControls,
  isPhone,
  context,
  duration,
  downloadable,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onScrollBreakpoint,
} = usePlayableList(songs, { type: 'Artist' })

const useEncyclopedia = computed(() => useMusicBrainz.value || useLastfm.value)

const albumCount = computed(() => {
  const albums = new Set()
  songs.value.forEach(song => albums.add(song.album_id))
  return albums.size
})

const download = () => downloadService.fromArtist(artist.value!)
const toggleFavorite = () => artistStore.toggleFavorite(artist.value!)

const fetchScreenData = async () => {
  if (loading.value) {
    return
  }

  const id = getRouteParam('id')
  const tabParam = getRouteParam<Tab>('tab') || 'songs'
  activeTab.value = validTabs.includes(tabParam) ? tabParam : 'songs'

  loading.value = true

  try {
    [artist.value, songs.value] = await Promise.all([
      artistStore.resolve(id),
      playableStore.fetchSongsForArtist(id),
    ])

    if (!artist.value) {
      await triggerNotFound()
      return
    }

    if (activeTab.value === 'albums') {
      albums.value = await albumStore.fetchForArtist(artist.value)
    }

    context.entity = artist.value
    editable.value = await currentUserCan.editArtist(artist.value!)
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    loading.value = false
  }
}

onScreenActivated('Artist', () => fetchScreenData())
onRouteChanged(route => route.name === 'artists.show' && fetchScreenData())

eventBus.on('SONGS_UPDATED', result => {
  // After songs are updated, check if the current artist still exists.
  // If not, redirect to the artist index screen.
  if (result.removed.artists.find(({ id }) => id === artist.value?.id)) {
    go(url('artists.index'))
  }
})
</script>

<style lang="postcss" scoped>
.meta-content > *:not(:first-child)::before {
  content: '•';
  margin: 0 0.25em;
}

.screen-header :deep(.play-icon) {
  @apply scale-[2];
}
</style>
