<template>
  <section id="artistWrapper">
    <ScreenHeader :layout="headerLayout" has-thumbnail>
      {{ artist.name }}
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <ArtistThumbnail :entity="artist"/>
      </template>

      <template v-slot:meta>
        <span>{{ pluralize(artist.album_count, 'album') }}</span>
        <span>{{ pluralize(artist.song_count, 'song') }}</span>
        <span>{{ secondsToHis(artist.length) }}</span>
        <a v-if="useLastfm" class="info" href title="View artist information" @click.prevent="showInfo">Info</a>

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

    <SongList ref="songList" @press:enter="onPressEnter" @scroll-breakpoint="onScrollBreakpoint"/>

    <section class="info-wrapper" v-if="useLastfm && showingInfo">
      <CloseModalBtn class="close-modal" @click="showingInfo = false"/>
      <div class="inner">
        <ArtistInfo :artist="artist" mode="full"/>
      </div>
    </section>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, ref, toRef, toRefs } from 'vue'
import { eventBus, pluralize, secondsToHis } from '@/utils'
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useSongList, useThirdPartyServices } from '@/composables'
import router from '@/router'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ArtistThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'

const props = defineProps<{ artist: Artist }>()
const { artist } = toRefs(props)

const artistSongs = ref<Song[]>([])

const {
  SongList,
  SongListControls,
  ControlsToggle,
  headerLayout,
  songList,
  songs,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls,
  onScrollBreakpoint
} = useSongList(artistSongs, 'artist', { columns: ['track', 'title', 'album', 'length'] })

const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const CloseModalBtn = defineAsyncComponent(() => import('@/components/ui/BtnCloseModal.vue'))

const { useLastfm } = useThirdPartyServices()
const allowDownload = toRef(commonStore.state, 'allow_download')

const showingInfo = ref(false)

const download = () => downloadService.fromArtist(artist.value)
const showInfo = () => (showingInfo.value = true)

onMounted(async () => {
  artistSongs.value = await songStore.fetchForArtist(artist.value)
})

eventBus.on('SONGS_UPDATED', () => {
  // if the current artist has been deleted, go back to the list
  artistStore.byId(artist.value.id) || router.go('artists')
})
</script>

<style lang="scss" scoped>
@import "#/partials/_mixins.scss";

#artistWrapper {
  @include artist-album-info-wrapper();
}
</style>
