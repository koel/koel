<template>
  <ScreenBase>
    <template #header>
      <ScreenHeaderSkeleton v-if="loading && !album" />

      <ScreenHeader v-if="album" :disabled="loading" :layout="songs.length ? headerLayout : 'collapsed'">
        {{ album.name }}

        <template #thumbnail>
          <AlbumThumbnail :entity="album" />
        </template>

        <template #meta>
          <span class="flex meta-content">
            <a v-if="isStandardArtist" :href="url('artists.show', { id: album.artist_id })" class="artist">
              {{ album.artist_name }}
            </a>
            <span v-else class="text-k-text-primary">{{ album.artist_name }}</span>
            <span v-if="album.year">{{ album.year }}</span>
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
              v-if="album.favorite"
              :favorite="album.favorite"
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

    <ScreenTabs v-if="album" class="-m-6" :class="loading && 'pointer-events-none'">
      <template #header>
        <nav>
          <ul>
            <li :class="activeTab === 'songs' && 'active'">
              <a :href="url('albums.show', { id: album.id, tab: 'songs' })">Songs</a>
            </li>
            <li :class="activeTab === 'other-albums' && 'active'">
              <a :href="url('albums.show', { id: album.id, tab: 'other-albums' })">Other Albums</a>
            </li>
            <li v-if="useEncyclopedia" :class="activeTab === 'information' && 'active'">
              <a :href="url('albums.show', { id: album.id, tab: 'information' })">Information</a>
            </li>
          </ul>
        </nav>
      </template>

      <div v-show="activeTab === 'songs'" class="songs-pane">
        <SongListSkeleton v-if="loading" />
        <SongList
          v-if="!loading && album"
          ref="songList"
          @press:enter="onPressEnter"
          @swipe="onSwipe"
        />
      </div>

      <div v-show="activeTab === 'other-albums'" class="albums-pane" data-testid="albums-pane">
        <template v-if="otherAlbums">
          <GridListView v-if="otherAlbums.length" v-koel-overflow-fade view-mode="list">
            <AlbumCard v-for="otherAlbum in otherAlbums" :key="otherAlbum.id" :album="otherAlbum" layout="compact" />
          </GridListView>
          <p v-else class="text-k-text-secondary p-6">
            No other albums by {{ album.artist_name }} found in the library.
          </p>
        </template>
        <GridListView v-else view-mode="list">
          <AlbumCardSkeleton v-for="i in 6" :key="i" layout="compact" />
        </GridListView>
      </div>

      <div v-if="useEncyclopedia && album" v-show="activeTab === 'information'" class="info-pane">
        <AlbumInfo :album="album" mode="full" />
      </div>
    </ScreenTabs>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faEllipsis } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { pluralize } from '@/utils/formatters'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { usePolicies } from '@/composables/usePolicies'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { useRouter } from '@/composables/useRouter'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { useContextMenu } from '@/composables/useContextMenu'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import AlbumThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'
import ScreenTabs from '@/components/ui/ArtistAlbumScreenTabs.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import GridListView from '@/components/ui/GridListView.vue'
import Btn from '@/components/ui/form/Btn.vue'

const validTabs = ['songs', 'other-albums', 'information'] as const
type Tab = (typeof validTabs)[number]

const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))
const ContextMenu = defineAsyncComponent(() => import('@/components/album/AlbumContextMenu.vue'))
const AlbumCardSkeleton = defineAsyncComponent(() => import('@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'))
const FavoriteButton = defineAsyncComponent(() => import('@/components/ui/FavoriteButton.vue'))

const { getRouteParam, go, onScreenActivated, onRouteChanged, url, triggerNotFound } = useRouter()
const { currentUserCan } = usePolicies()
const { PlayableListControls: SongListControls, config } = usePlayableListControls('Album')
const { useLastfm, useMusicBrainz } = useThirdPartyServices()
const { openContextMenu } = useContextMenu()

const activeTab = ref<Tab>('songs')
const album = ref<Album | undefined>()
const songs = ref<Song[]>([])
const loading = ref(false)
const otherAlbums = ref<Album[] | undefined>()
const info = ref<ArtistInfo | undefined>()
const editable = ref(false)

const {
  PlayableList: SongList,
  headerLayout,
  playableList: songList,
  duration,
  context,
  sort,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onSwipe,
} = usePlayableList(songs, { type: 'Album' })

const useEncyclopedia = computed(() => useMusicBrainz.value || useLastfm.value)

const isStandardArtist = computed(() => {
  if (!album.value) {
    return true
  }

  return !artistStore.isVarious(album.value.artist_name) && !artistStore.isUnknown(album.value.artist_name)
})

const toggleFavorite = () => albumStore.toggleFavorite(album.value!)

const fetchScreenData = async () => {
  if (loading.value) {
    return
  }

  const id = getRouteParam('id')
  const tabParam = getRouteParam<Tab>('tab') || 'songs'
  activeTab.value = validTabs.includes(tabParam) ? tabParam : 'songs'

  album.value = undefined
  info.value = undefined
  otherAlbums.value = undefined

  loading.value = true

  try {
    [album.value, songs.value] = await Promise.all([
      albumStore.resolve(id),
      playableStore.fetchSongsForAlbum(id),
    ])

    if (!album.value) {
      // If the album does not exist, redirect to the album list.
      triggerNotFound()
      return
    }

    if (activeTab.value === 'other-albums') {
      const albums = await albumStore.fetchForArtist(album.value.artist_id)
      otherAlbums.value = albums.filter(({ id }) => id !== album.value!.id)
    }

    context.entity = album.value

    sort('track')

    editable.value = await currentUserCan.editAlbum(album.value!)
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

onScreenActivated('Album', () => fetchScreenData())
onRouteChanged(route => route.name === 'albums.show' && fetchScreenData())

const requestContextMenu = (event: MouseEvent) => openContextMenu<'ALBUM'>(ContextMenu, event, {
  album: album.value!,
})

eventBus.on('SONGS_UPDATED', result => {
  // After songs are updated, check if the current album still exists.
  // If it doesn't, redirect to the album list.
  if (result.removed.album_ids.includes(album.value!.id)) {
    go(url('albums.index'))
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
