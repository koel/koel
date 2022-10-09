<template>
  <section id="albumWrapper">
    <ScreenHeaderSkeleton v-if="loading"/>

    <ScreenHeader v-if="!loading && album" :layout="songs.length === 0 ? 'collapsed' : headerLayout">
      {{ album.name }}
      <ControlsToggle v-model="showingControls"/>

      <template v-slot:thumbnail>
        <AlbumThumbnail :entity="album"/>
      </template>

      <template v-slot:meta>
        <a v-if="isNormalArtist" :href="`#!/artist/${album.artist_id}`" class="artist">{{ album.artist_name }}</a>
        <span v-else class="nope">{{ album.artist_name }}</span>
        <span>{{ pluralize(songs, 'song') }}</span>
        <span>{{ duration }}</span>
        <a v-if="useLastfm" class="info" href title="View album information" @click.prevent="showInfo">Info</a>

        <a
          v-if="allowDownload"
          class="download"
          href role="button"
          title="Download all songs in album"
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

    <SongListSkeleton v-if="loading"/>
    <SongList v-else ref="songList" @sort="sort" @press:enter="onPressEnter" @scroll-breakpoint="onScrollBreakpoint"/>

    <section v-if="!loading && useLastfm && showingInfo" class="info-wrapper">
      <CloseModalBtn class="close-modal" @click="showingInfo = false"/>
      <div class="inner">
        <AlbumInfo :album="album" mode="full"/>
      </div>
    </section>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, onMounted, ref, toRef } from 'vue'
import { eventBus, logger, pluralize, requireInjection } from '@/utils'
import { albumStore, artistStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useSongList } from '@/composables'
import { DialogBoxKey, RouterKey } from '@/symbols'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import AlbumThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'

const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const CloseModalBtn = defineAsyncComponent(() => import('@/components/ui/BtnCloseModal.vue'))

const dialog = requireInjection(DialogBoxKey)
const router = requireInjection(RouterKey)

const album = ref<Album>()
const songs = ref<Song[]>([])
const showingInfo = ref(false)
const loading = ref(false)

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
} = useSongList(songs, 'Album', { columns: ['track', 'title', 'artist', 'length'] })

const useLastfm = toRef(commonStore.state, 'use_last_fm')
const allowDownload = toRef(commonStore.state, 'allow_download')

const isNormalArtist = computed(() => {
  if (!album.value) return true
  return !artistStore.isVarious(album.value.artist_id) && !artistStore.isUnknown(album.value.artist_id)
})

const download = () => downloadService.fromAlbum(album.value!)
const showInfo = () => (showingInfo.value = true)

onMounted(async () => {
  const id = parseInt(router.$currentRoute.value?.params!.id)
  loading.value = true

  try {
    [album.value, songs.value] = await Promise.all([
      albumStore.resolve(id),
      songStore.fetchForAlbum(id)
    ])

    sort('track')
  } catch (e) {
    logger.error(e)
    dialog.value.error('Failed to load album. Please try again.')
  } finally {
    loading.value = false
  }
})

// if the current album has been deleted, go back to the list
eventBus.on('SONGS_UPDATED', () => albumStore.byId(album.value!.id) || router.go('albums'))
</script>

<style lang="scss" scoped>
#albumWrapper {
  @include artist-album-info-wrapper();
}
</style>
