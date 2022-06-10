<template>
  <section id="artistWrapper">
    <ScreenHeader has-thumbnail>
      {{ artist.name }}
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <ArtistThumbnail :entity="artist"/>
      </template>

      <template v-slot:meta>
        <span v-if="songs.length">
          {{ pluralize(artist.album_count, 'album') }}
          •
          {{ pluralize(songs.length, 'song') }}
          •
          {{ duration }}

          <template v-if="useLastfm">
            •
            <a class="info" href title="View artist's extra information" @click.prevent="showInfo">Info</a>
          </template>

          <template v-if="allowDownload">
            •
            <a
              class="download"
              href
              role="button"
              title="Download all songs by this artist"
              @click.prevent="download"
            >
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

    <section class="info-wrapper" v-if="useLastfm && showingInfo">
      <CloseModalBtn @click="showingInfo = false"/>
      <div class="inner">
        <ArtistInfo :artist="artist" mode="full"/>
      </div>
    </section>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, ref, toRef, toRefs, watch } from 'vue'
import { pluralize } from '@/utils'
import { commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useSongList, useThirdPartyServices } from '@/composables'
import router from '@/router'

const props = defineProps<{ artist: Artist }>()
const { artist } = toRefs(props)

const artistSongs = ref<Song[]>([])

const {
  SongList,
  SongListControls,
  ControlsToggle,
  songList,
  songs,
  duration,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls
} = useSongList(artistSongs, 'artist', { columns: ['track', 'title', 'album', 'length'] })

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/SoundBar.vue'))
const ArtistThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))
const CloseModalBtn = defineAsyncComponent(() => import('@/components/ui/BtnCloseModal.vue'))

const { useLastfm } = useThirdPartyServices()
const allowDownload = toRef(commonStore.state, 'allow_download')

const showingInfo = ref(false)

/**
 * Watch the artist's album count.
 * If this is changed to 0, the user has edited the songs by this artist
 * and moved all of them to another artist (thus deleted this artist entirely).
 * We should then go back to the artist list.
 */
watch(() => artist.value.album_count, newAlbumCount => newAlbumCount || router.go('artists'))

const download = () => downloadService.fromArtist(artist.value)
const showInfo = () => (showingInfo.value = true)

onMounted(async () => {
  artistSongs.value = await songStore.fetchForArtist(artist.value)
})
</script>

<style lang="scss" scoped>
#artistWrapper {
  @include artist-album-info-wrapper();
}
</style>
