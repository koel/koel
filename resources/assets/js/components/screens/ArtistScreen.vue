<template>
  <section id="artistWrapper">
    <ScreenHeaderSkeleton v-if="loading"/>

    <ScreenHeader v-if="!loading && artist" :layout="songs.length === 0 ? 'collapsed' : headerLayout">
      {{ artist.name }}
      <ControlsToggle v-model="showingControls"/>

      <template v-slot:thumbnail>
        <ArtistThumbnail :entity="artist"/>
      </template>

      <template v-slot:meta>
        <span>{{ pluralize(albumCount, 'album') }}</span>
        <span>{{ pluralize(songs, 'song') }}</span>
        <span>{{ duration }}</span>

        <a
          v-if="allowDownload"
          class="download"
          href
          role="button"
          title="Download all songs by this artist"
          @click.prevent="download"
        >
          Download All
        </a>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <ScreenTabs>
      <template #header>
        <label :class="{ active: activeTab === 'Songs' }">
          Songs
          <input type="radio" name="tab" value="Songs" v-model="activeTab"/>
        </label>
        <label :class="{ active: activeTab === 'Albums' }">
          Albums
          <input type="radio" name="tab" value="Albums" v-model="activeTab"/>
        </label>
        <label :class="{ active: activeTab === 'Info' }" v-if="useLastfm">
          Information
          <input type="radio" name="tab" value="Info" v-model="activeTab"/>
        </label>
      </template>

      <div v-show="activeTab === 'Songs'" class="songs-pane">
        <SongListSkeleton v-if="loading"/>
        <SongList
          v-else
          ref="songList"
          @sort="sort"
          @press:enter="onPressEnter"
          @scroll-breakpoint="onScrollBreakpoint"
        />
      </div>

      <div v-show="activeTab === 'Albums'" class="albums-pane">
        <ul v-if="albums" class="as-list">
          <li v-for="album in albums" :key="album.id">
            <AlbumCard :album="album" layout="compact"/>
          </li>
        </ul>
        <ul v-else class="as-list">
          <li v-for="i in 12" :key="i">
            <AlbumCardSkeleton layout="compact"/>
          </li>
        </ul>
      </div>

      <div v-show="activeTab === 'Info'" class="info-pane" v-if="useLastfm && artist">
        <ArtistInfo :artist="artist" mode="full"/>
      </div>
    </ScreenTabs>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, onMounted, ref, toRef, watch } from 'vue'
import { eventBus, logger, pluralize, requireInjection } from '@/utils'
import { albumStore, artistStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useSongList, useThirdPartyServices } from '@/composables'
import { DialogBoxKey, RouterKey } from '@/symbols'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ArtistThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import ScreenTabs from '@/components/ui/ArtistAlbumScreenTabs.vue'

const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))
const AlbumCardSkeleton = defineAsyncComponent(() => import('@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'))

type Tab = 'Songs' | 'Albums' | 'Info'
const activeTab = ref<Tab>('Songs')

const dialog = requireInjection(DialogBoxKey)
const router = requireInjection(RouterKey)

const artistId = ref<number>()
const artist = ref<Artist>()
const songs = ref<Song[]>([])
const loading = ref(false)
let albums = ref<Album[] | undefined>()
let info = ref<ArtistInfo | undefined | null>()

const {
  SongList,
  SongListControls,
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
  onScrollBreakpoint
} = useSongList(songs, 'Artist', { columns: ['track', 'thumbnail', 'title', 'album', 'length'] })

const { useLastfm } = useThirdPartyServices()
const allowDownload = toRef(commonStore.state, 'allow_download')

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
  if (!id) return

  loading.value = true

  try {
    [artist.value, songs.value] = await Promise.all([
      artistStore.resolve(id),
      songStore.fetchForArtist(id)
    ])
  } catch (error) {
    logger.error(error)
    dialog.value.error('Failed to load artist. Please try again.')
  } finally {
    loading.value = false
  }
})

const download = () => downloadService.fromArtist(artist.value!)

onMounted(() => (artistId.value = parseInt(router.$currentRoute.value.params!.id)))

// if the current artist has been deleted, go back to the list
eventBus.on('SONGS_UPDATED', () => artistStore.byId(artist.value!.id) || router.go('artists'))
</script>

<style lang="scss" scoped>
@import "#/partials/_mixins.scss";

#artistWrapper {
  @include artist-album-info-wrapper();
}

.albums-pane {
  padding: 1.8rem;

  > ul {
    @include artist-album-wrapper();
  }
}

.info-pane {
  padding: 1.8rem;
}
</style>
