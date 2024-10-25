<template>
  <ScreenBase v-if="artistId">
    <template #header>
      <ScreenHeaderSkeleton v-if="loading" />

      <ScreenHeader v-if="!loading && artist" :layout="songs.length === 0 ? 'collapsed' : headerLayout">
        {{ artist.name }}
        <ControlsToggle v-model="showingControls" />

        <template #thumbnail>
          <ArtistThumbnail :entity="artist" />
        </template>

        <template #meta>
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
        </template>

        <template #controls>
          <SongListControls
            v-if="songs.length && (!isPhone || showingControls)"
            :config="config"
            @filter="applyFilter"
            @play-all="playAll"
            @play-selected="playSelected"
          />
        </template>
      </ScreenHeader>
    </template>

    <ScreenTabs class="-m-6">
      <template #header>
        <label :class="{ active: activeTab === 'Songs' }">
          Songs
          <input v-model="activeTab" :disabled="loading" name="tab" type="radio" value="Songs">
        </label>
        <label :class="{ active: activeTab === 'Albums' }">
          Albums
          <input v-model="activeTab" :disabled="loading" name="tab" type="radio" value="Albums">
        </label>
        <label v-if="useLastfm" :class="{ active: activeTab === 'Info' }">
          Information
          <input v-model="activeTab" :disabled="loading" name="tab" type="radio" value="Info">
        </label>
      </template>

      <div v-show="activeTab === 'Songs'" class="songs-pane">
        <SongListSkeleton v-if="loading" />
        <SongList
          v-if="!loading && artist"
          ref="songList"
          @press:enter="onPressEnter"
          @scroll-breakpoint="onScrollBreakpoint"
        />
      </div>

      <div v-show="activeTab === 'Albums'" class="albums-pane">
        <AlbumOrArtistGrid v-koel-overflow-fade view-mode="list">
          <template v-if="albums">
            <AlbumCard v-for="album in albums" :key="album.id" :album="album" layout="compact" />
          </template>
          <template v-else>
            <AlbumCardSkeleton v-for="i in 6" :key="i" layout="compact" />
          </template>
        </AlbumOrArtistGrid>
      </div>

      <div v-if="useLastfm && artist" v-show="activeTab === 'Info'" class="info-pane">
        <ArtistInfo :artist="artist" mode="full" />
      </div>
    </ScreenTabs>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { pluralize } from '@/utils/formatters'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { songStore } from '@/stores/songStore'
import { downloadService } from '@/services/downloadService'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useSongList } from '@/composables/useSongList'
import { useSongListControls } from '@/composables/useSongListControls'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { useRouter } from '@/composables/useRouter'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ArtistThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import ScreenTabs from '@/components/ui/ArtistAlbumScreenTabs.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import AlbumOrArtistGrid from '@/components/ui/album-artist/AlbumOrArtistGrid.vue'

const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))
const AlbumCardSkeleton = defineAsyncComponent(() => import('@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'))

type Tab = 'Songs' | 'Albums' | 'Info'
const activeTab = ref<Tab>('Songs')

const { getRouteParam, go, onScreenActivated, url } = useRouter()

const artistId = ref<number>()
const artist = ref<Artist>()
const songs = ref<Song[]>([])
const loading = ref(false)
const albums = ref<Album[] | undefined>()

const {
  SongList,
  ControlsToggle,
  headerLayout,
  songList,
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
} = useSongList(songs, { type: 'Artist' })

const { SongListControls, config } = useSongListControls('Artist')

const { useLastfm } = useThirdPartyServices()

const albumCount = computed(() => {
  const albums = new Set()
  songs.value.forEach(song => albums.add(song.album_id))
  return albums.size
})

watch(activeTab, async tab => {
  if (tab === 'Albums' && !albums.value) {
    albums.value = await albumStore.fetchForArtist(artist.value!)
  }
})

watch(artistId, async id => {
  if (!id || loading.value) {
    return
  }

  loading.value = true

  try {
    [artist.value, songs.value] = await Promise.all([
      artistStore.resolve(id),
      songStore.fetchForArtist(id),
    ])

    context.entity = artist.value
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    loading.value = false
  }
})

const download = () => downloadService.fromArtist(artist.value!)

onScreenActivated('Artist', () => (artistId.value = Number.parseInt(getRouteParam('id')!)))

// if the current artist has been deleted, go back to the list
eventBus.on('SONGS_UPDATED', () => artistStore.byId(artist.value!.id) || go(url('artists.index')))
</script>
