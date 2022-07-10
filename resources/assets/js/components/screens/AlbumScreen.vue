<template>
  <section id="albumWrapper">
    <ScreenHeader has-thumbnail>
      {{ album.name }}
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <AlbumThumbnail :entity="album"/>
      </template>

      <template v-slot:meta>
        <a v-if="isNormalArtist" :href="`#!/artist/${album.artist_id}`" class="artist">{{ album.artist_name }}</a>
        <span class="nope" v-else>{{ album.artist_name }}</span>
        <span>{{ pluralize(album.song_count, 'song') }}</span>
        <span>{{ secondsToHis(album.length) }}</span>
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

    <SongList ref="songList" @press:enter="onPressEnter"/>

    <section v-if="useLastfm && showingInfo" class="info-wrapper">
      <CloseModalBtn class="close-modal" @click="showingInfo = false"/>
      <div class="inner">
        <AlbumInfo :album="album" mode="full"/>
      </div>
    </section>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, onMounted, ref, toRef, toRefs } from 'vue'
import { eventBus, pluralize, secondsToHis } from '@/utils'
import { albumStore, artistStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useSongList } from '@/composables'
import router from '@/router'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import AlbumThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'

const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
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

const download = () => downloadService.fromAlbum(album.value)
const showInfo = () => (showingInfo.value = true)

onMounted(async () => {
  albumSongs.value = await songStore.fetchForAlbum(album.value)
})

eventBus.on('SONGS_UPDATED', () => {
  // if the current album has been deleted, go back to the list
  albumStore.byId(album.value.id) || router.go('albums')
})
</script>

<style lang="scss" scoped>
#albumWrapper {
  @include artist-album-info-wrapper();
}
</style>
