<template>
  <ScreenBase v-if="album">
    <template #header>
      <ScreenHeaderSkeleton v-if="loading" />

      <ScreenHeader v-if="!loading && album" :layout="songs.length === 0 ? 'collapsed' : headerLayout">
        {{ album.name }}
        <ControlsToggle v-model="showingControls" />

        <template #thumbnail>
          <AlbumThumbnail :entity="album" />
        </template>

        <template #meta>
          <a v-if="isNormalArtist" :href="`#/artist/${album.artist_id}`" class="artist">{{ album.artist_name }}</a>
          <span v-else class="nope">{{ album.artist_name }}</span>
          <span>{{ pluralize(songs, 'item') }}</span>
          <span>{{ duration }}</span>

          <a
            v-if="allowDownload"
            class="download"
            role="button"
            title="Download all songs in album"
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
          <input v-model="activeTab" name="tab" type="radio" value="Songs">
        </label>
        <label :class="{ active: activeTab === 'OtherAlbums' }">
          Other Albums
          <input v-model="activeTab" name="tab" type="radio" value="OtherAlbums">
        </label>
        <label v-if="useLastfm" :class="{ active: activeTab === 'Info' }">
          Information
          <input v-model="activeTab" name="tab" type="radio" value="Info">
        </label>
      </template>

      <div v-show="activeTab === 'Songs'" class="songs-pane">
        <SongListSkeleton v-if="loading" />
        <SongList
          v-else
          ref="songList"
          @sort="sort"
          @press:enter="onPressEnter"
          @scroll-breakpoint="onScrollBreakpoint"
        />
      </div>

      <div v-show="activeTab === 'OtherAlbums'" class="albums-pane" data-testid="albums-pane">
        <template v-if="otherAlbums">
          <AlbumGrid v-if="otherAlbums.length" v-koel-overflow-fade view-mode="list">
            <AlbumCard v-for="otherAlbum in otherAlbums" :key="otherAlbum.id" :album="otherAlbum" layout="compact" />
          </AlbumGrid>
          <p v-else class="text-k-text-secondary p-6">
            No other albums by {{ album.artist_name }} found in the library.
          </p>
        </template>
        <AlbumGrid v-else view-mode="list">
          <AlbumCardSkeleton v-for="i in 6" :key="i" layout="compact" />
        </AlbumGrid>
      </div>

      <div v-if="useLastfm && album" v-show="activeTab === 'Info'" class="info-pane">
        <AlbumInfo :album="album" mode="full" />
      </div>
    </ScreenTabs>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref, toRef, watch } from 'vue'
import { eventBus, pluralize } from '@/utils'
import { albumStore, artistStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useErrorHandler, useRouter, useSongList, useSongListControls } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import AlbumThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import ScreenTabs from '@/components/ui/ArtistAlbumScreenTabs.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import AlbumGrid from '@/components/ui/album-artist/AlbumOrArtistGrid.vue'

type Tab = 'Songs' | 'OtherAlbums' | 'Info'
const activeTab = ref<Tab>('Songs')

const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))
const AlbumCardSkeleton = defineAsyncComponent(() => import('@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'))

const { getRouteParam, go, onScreenActivated } = useRouter()

const albumId = ref<number>()
const album = ref<Album | undefined>()
const songs = ref<Song[]>([])
const loading = ref(false)
let otherAlbums = ref<Album[] | undefined>()
let info = ref<ArtistInfo | undefined>()

const {
  SongList,
  ControlsToggle,
  headerLayout,
  songList,
  showingControls,
  isPhone,
  duration,
  context,
  sort,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onScrollBreakpoint
} = useSongList(songs, { type: 'Album' })

const { SongListControls, config } = useSongListControls('Album')

const useLastfm = toRef(commonStore.state, 'uses_last_fm')
const allowDownload = toRef(commonStore.state, 'allows_download')

const isNormalArtist = computed(() => {
  if (!album.value) return true
  return !artistStore.isVarious(album.value.artist_id) && !artistStore.isUnknown(album.value.artist_id)
})

const download = () => downloadService.fromAlbum(album.value!)

watch(activeTab, async tab => {
  if (tab === 'OtherAlbums' && !otherAlbums.value) {
    const albums = await albumStore.fetchForArtist(album.value!.artist_id)
    otherAlbums.value = albums.filter(({ id }) => id !== album.value!.id)
  }
})

watch(albumId, async id => {
  if (!id || loading.value) return

  album.value = undefined
  info.value = undefined
  otherAlbums.value = undefined
  activeTab.value = 'Songs'

  loading.value = true

  try {
    [album.value, songs.value] = await Promise.all([
      albumStore.resolve(id),
      songStore.fetchForAlbum(id)
    ])

    context.entity = album.value

    sort('track')
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    loading.value = false
  }
})

onScreenActivated('Album', () => (albumId.value = parseInt(getRouteParam('id')!)))

// if the current album has been deleted, go back to the list
eventBus.on('SONGS_UPDATED', () => albumStore.byId(albumId.value!) || go('albums'))
</script>
