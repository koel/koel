<template>
  <section id="artistWrapper">
    <ScreenHeader has-thumbnail>
      {{ artist.name }}
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <ArtistThumbnail :entity="artist"/>
      </template>

      <template v-slot:meta>
        <span v-if="songs.length">
          {{ pluralize(artist.albums.length, 'album') }}
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
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
          :songs="songs"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList ref="songList" :config="listConfig" :items="songs" type="artist" @press:enter="onPressEnter"/>

    <section class="info-wrapper" v-if="useLastfm && showing">
      <CloseModalBtn @click="showing = false"/>
      <div class="inner">
        <div class="loading" v-if="loading">
          <SoundBar/>
        </div>
        <ArtistInfo :artist="artist" mode="full" v-else/>
      </div>
    </section>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref, toRef, toRefs, watch } from 'vue'
import { pluralize } from '@/utils'
import { commonStore } from '@/stores'
import { artistInfoService, downloadService } from '@/services'
import { useSongList, useThirdPartyServices } from '@/composables'
import router from '@/router'

const props = defineProps<{ artist: Artist }>()
const { artist } = toRefs(props)

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songList,
  songs,
  duration,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls
} = useSongList(ref(artist.value.songs))

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/SoundBar.vue'))
const ArtistThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))
const CloseModalBtn = defineAsyncComponent(() => import('@/components/ui/BtnCloseModal.vue'))

const listConfig: Partial<SongListConfig> = { columns: ['track', 'title', 'album', 'length'] }
const { useLastfm } = useThirdPartyServices()
const allowDownload = toRef(commonStore.state, 'allowDownload')

const showing = ref(false)
const loading = ref(true)

/**
 * Watch the artist's album count.
 * If this is changed to 0, the user has edited the songs by this artist
 * and moved all of them to another artist (thus deleted this artist entirely).
 * We should then go back to the artist list.
 */
watch(() => artist.value.albums.length, newAlbumCount => newAlbumCount || router.go('artists'))

watch(artist, () => {
  showing.value = false
  songList.value?.sort()
})

const download = () => downloadService.fromArtist(artist.value)

const showInfo = async () => {
  showing.value = true

  if (!artist.value.info) {
    try {
      await artistInfoService.fetch(artist.value)
    } catch (e) {
      /* eslint no-console: 0 */
      console.error(e)
    } finally {
      loading.value = false
    }
  } else {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
#artistWrapper {
  @include artist-album-info-wrapper();
}
</style>
