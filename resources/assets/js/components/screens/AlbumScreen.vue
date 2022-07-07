<template>
  <section id="albumWrapper">
    <ScreenHeader has-thumbnail>
      {{ album.name }}
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <AlbumThumbnail :entity="album"/>
      </template>

      <template v-slot:meta>
        <span>
          by
          <a v-if="isNormalArtist" :href="`#!/artist/${album.artist_id}`" class="artist">{{ album.artist_name }}</a>
          <span class="nope" v-else>{{ album.artist_name }}</span>
          <template v-if="songs.length">
          •
          {{ pluralize(songs.length, 'song') }}
          •
          {{ duration }}
          </template>

          <template v-if="useLastfm">
            •
            <a class="info" href title="View album's extra information" @click.prevent="showInfo">Info</a>
          </template>

          <template v-if="allowDownload && songs.length">
            •
            <a class="download" href role="button" title="Download all songs in album" @click.prevent="download">
              Download All
            </a>
          </template>
        </span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList ref="songList" @press:enter="onPressEnter"/>

    <section v-if="useLastfm && showingInfo" class="info-wrapper">
      <CloseModalBtn @click="showingInfo = false"/>
      <div class="inner">
        <AlbumInfo :album="album" mode="full"/>
      </div>
    </section>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, onMounted, ref, toRef, toRefs, watch } from 'vue'
import { eventBus, pluralize } from '@/utils'
import { albumStore, artistStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useSongList } from '@/composables'
import router from '@/router'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'

const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const AlbumThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))
const CloseModalBtn = defineAsyncComponent(() => import('@/components/ui/BtnCloseModal.vue'))

const props = defineProps<{ album: Album }>()
const { album } = toRefs(props)

const albumSongs = ref<Song[]>([])

const {
  SongList,
  SongListControls,
  ControlsToggle,
  songs,
  songList,
  duration,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls
} = useSongList(albumSongs, 'album', { columns: ['track', 'title', 'artist', 'length'] })

const useLastfm = toRef(commonStore.state, 'use_last_fm')
const allowDownload = toRef(commonStore.state, 'allow_download')
const showingInfo = ref(false)

const isNormalArtist = computed(() => {
  return !artistStore.isVarious(album.value.artist_id) && !artistStore.isUnknown(album.value.artist_id)
})

/**
 * Watch the album's song count.
 * If this is changed to 0, the user has edited the songs on this album
 * and moved all of them into another album.
 * We should then go back to the album list.
 */
watch(() => album.value.song_count, newSongCount => newSongCount || router.go('albums'))

const download = () => downloadService.fromAlbum(album.value)
const showInfo = () => (showingInfo.value = true)

onMounted(async () => {
  albumSongs.value = await songStore.fetchForAlbum(album.value)
})

eventBus.on('SONGS_UPDATED', () => {
  // if the current album has been deleted, go back to home
  albumStore.byId(album.value.id) || router.go('home')
})
</script>

<style lang="scss" scoped>
#albumWrapper {
  @include artist-album-info-wrapper();
}
</style>
