<template>
  <section v-if="artist" id="artistWrapper">
    <ScreenHeaderSkeleton v-if="loading" />

    <ScreenHeader v-if="!loading && artist" :layout="songs.length === 0 ? 'collapsed' : headerLayout">
      {{ artist.name }}
      <ControlsToggle v-model="showingControls" />

      <template #thumbnail>
        <ArtistThumbnail :entity="artist" />
      </template>

      <template #meta>
        <span>{{ pluralize(albumCount, 'album') }}</span>
        <span>{{ pluralize(songs, 'song') }}</span>
        <span>{{ duration }}</span>

        <a
          v-if="allowDownload"
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

    <ScreenTabs>
      <template #header>
        <label :class="{ active: activeTab === 'Songs' }">
          Songs
          <input v-model="activeTab" type="radio" name="tab" value="Songs">
        </label>
        <label :class="{ active: activeTab === 'Albums' }">
          Albums
          <input v-model="activeTab" type="radio" name="tab" value="Albums">
        </label>
        <label v-if="useLastfm" :class="{ active: activeTab === 'Info' }">
          Information
          <input v-model="activeTab" type="radio" name="tab" value="Info">
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

      <div v-show="activeTab === 'Albums'" class="albums-pane">
        <ul v-if="albums" v-koel-overflow-fade class="as-list">
          <li v-for="album in albums" :key="album.id">
            <AlbumCard :album="album" layout="compact" />
          </li>
        </ul>
        <ul v-else class="as-list">
          <li v-for="i in 12" :key="i">
            <AlbumCardSkeleton layout="compact" />
          </li>
        </ul>
      </div>

      <div v-show="activeTab === 'Info'" v-if="useLastfm && artist" class="info-pane">
        <ArtistInfo :artist="artist" mode="full" />
      </div>
    </ScreenTabs>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref, toRef, watch } from 'vue'
import { eventBus, logger, pluralize } from '@/utils'
import { albumStore, artistStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useDialogBox, useRouter, useSongList, useSongListControls, useThirdPartyServices } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ArtistThumbnail from '@/components/ui/ArtistAlbumThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import ScreenTabs from '@/components/ui/ArtistAlbumScreenTabs.vue'

const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))
const AlbumCardSkeleton = defineAsyncComponent(() => import('@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'))

type Tab = 'Songs' | 'Albums' | 'Info'
const activeTab = ref<Tab>('Songs')

const { showErrorDialog } = useDialogBox()
const { getRouteParam, go, onScreenActivated } = useRouter()

const artistId = ref<number>()
const artist = ref<Artist>()
const songs = ref<Song[]>([])
const loading = ref(false)
let albums = ref<Album[] | undefined>()
let info = ref<ArtistInfo | undefined | null>()

const {
  SongList,
  ControlsToggle,
  headerLayout,
  songList,
  showingControls,
  isPhone,
  duration,
  sort,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onScrollBreakpoint
} = useSongList(songs)

const { SongListControls, config } = useSongListControls('Artist')

const { useLastfm } = useThirdPartyServices()
const allowDownload = toRef(commonStore.state, 'allows_download')

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
  if (!id || loading.value) return

  loading.value = true

  try {
    [artist.value, songs.value] = await Promise.all([
      artistStore.resolve(id),
      songStore.fetchForArtist(id)
    ])
  } catch (error) {
    logger.error(error)
    showErrorDialog('Failed to load artist. Please try again.', 'Error')
  } finally {
    loading.value = false
  }
})

const download = () => downloadService.fromArtist(artist.value!)

onScreenActivated('Artist', () => (artistId.value = parseInt(getRouteParam('id')!)))

// if the current artist has been deleted, go back to the list
eventBus.on('SONGS_UPDATED', () => artistStore.byId(artist.value!.id) || go('artists'))
</script>

<style lang="scss" scoped>
@import "#/partials/_mixins.scss";

#artistWrapper {
  @include artist-album-info-wrapper();
}
</style>
