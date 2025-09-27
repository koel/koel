<template>
  <ScreenBase>
    <template #header>
      <ScreenHeaderSkeleton v-if="loading && !artist" />

      <ScreenHeader v-if="artist" :disabled="loading" :layout="songs.length ? headerLayout : 'collapsed'">
        {{ artist.name }}

        <template #thumbnail>
          <ArtistThumbnail :entity="artist" />
        </template>

        <template #meta>
          <span class="flex meta-content">
            <span>{{ pluralize(albumCount, 'album') }}</span>
            <span>{{ pluralize(songs, 'song') }}</span>
            <span>{{ duration }}</span>
          </span>
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
            <Btn gray @click="requestContextMenu">
              <Icon :icon="faEllipsis" fixed-width />
              <span class="sr-only">More Actions</span>
            </Btn>
          </SongListControls>
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
            <li v-if="useTicketmaster" :class="activeTab === 'events' && 'active'">
              <a :href="url('artists.show', { id: artist.id, tab: 'events' })">Events</a>
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
          @swipe="onSwipe"
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

      <div v-if="useTicketmaster && artist" v-show="activeTab === 'events'" class="events-pane">
        <ArtistEventList :artist="artist" />
      </div>
    </ScreenTabs>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faEllipsis } from '@fortawesome/free-solid-svg-icons'
import { computed, ref } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { eventBus } from '@/utils/eventBus'
import { pluralize } from '@/utils/formatters'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { useRouter } from '@/composables/useRouter'
import { usePolicies } from '@/composables/usePolicies'
import { useContextMenu } from '@/composables/useContextMenu'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ArtistThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'
import ScreenTabs from '@/components/ui/ArtistAlbumScreenTabs.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import GridListView from '@/components/ui/GridListView.vue'
import Btn from '@/components/ui/form/Btn.vue'

const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))
const ArtistEventList = defineAsyncComponent(() => import('@/components/artist/ArtistEventList.vue'))
const AlbumCardSkeleton = defineAsyncComponent(() => import('@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'))
const FavoriteButton = defineAsyncComponent(() => import('@/components/ui/FavoriteButton.vue'))
const ArtistContextMenu = defineAsyncComponent(() => import('@/components/artist/ArtistContextMenu.vue'))

const validTabs = ['songs', 'albums', 'information', 'events'] as const
type Tab = typeof validTabs[number]

const { PlayableListControls: SongListControls, config } = usePlayableListControls('Artist')
const { useLastfm, useMusicBrainz, useTicketmaster } = useThirdPartyServices()
const { getRouteParam, go, onScreenActivated, onRouteChanged, url, triggerNotFound } = useRouter()
const { currentUserCan } = usePolicies()
const { openContextMenu } = useContextMenu()

const activeTab = ref<Tab>('songs')
const artist = ref<Artist>()
const songs = ref<Song[]>([])
const loading = ref(false)
const albums = ref<Album[] | undefined>()
const editable = ref(false)

const {
  PlayableList: SongList,
  headerLayout,
  playableList: songList,
  context,
  duration,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onSwipe,
} = usePlayableList(songs, { type: 'Artist' })

const useEncyclopedia = computed(() => useMusicBrainz.value || useLastfm.value)

const albumCount = computed(() => {
  const albums = new Set()
  songs.value.forEach(song => albums.add(song.album_id))
  return albums.size
})

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
      triggerNotFound()
      return
    }

    if (activeTab.value === 'albums') {
      albums.value = await albumStore.fetchForArtist(artist.value)
    }

    context.entity = artist.value
    editable.value = await currentUserCan.editArtist(artist.value!)
  } catch (error: unknown) {
    if (error?.status === 404) {
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

const requestContextMenu = (event: MouseEvent) => openContextMenu<'ARTIST'>(ArtistContextMenu, event, {
  artist: artist.value!,
})

eventBus.on('SONGS_UPDATED', result => {
  // After songs are updated, check if the current artist still exists.
  // If not, redirect to the artist index screen.
  if (result.removed.artist_ids.includes(artist.value!.id)) {
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
