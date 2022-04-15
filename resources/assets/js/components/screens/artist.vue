<template>
  <section id="artistWrapper">
    <ScreenHeader>
      {{ artist.name }}
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <ArtistThumbnail :entity="artist"/>
      </template>

      <template v-slot:meta>
        <span v-if="artist.songs.length">
          {{ pluralize(artist.albums.length, 'album') }}
          •
          {{ pluralize(artist.songs.length, 'song') }}
          •
          {{ fmtLength }}

          <template v-if="sharedState.useLastfm">
            •
            <a class="info" href @click.prevent="showInfo" title="View artist's extra information">Info</a>
          </template>

          <template v-if="sharedState.allowDownload">
            •
            <a
              @click.prevent="download"
              class="download"
              href
              role="button"
              title="Download all songs by this artist"
            >
              Download All
            </a>
          </template>
        </span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="artist.songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          :songs="artist.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </ScreenHeader>

    <SongList :items="artist.songs" type="artist" :config="listConfig" ref="songList"/>

    <section class="info-wrapper" v-if="sharedState.useLastfm && showing">
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
import { defineAsyncComponent, reactive, ref, toRefs, watch } from 'vue'
import { pluralize } from '@/utils'
import { sharedStore } from '@/stores'
import { artistInfo as artistInfoService, download as downloadService } from '@/services'
import router from '@/router'
import { useArtistAttributes, useSongList } from '@/composables'

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songList,
  state,
  meta,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  playAll,
  playSelected,
  toggleControls
} = useSongList()

const props = defineProps<{ artist: Artist }>()
const { artist } = toRefs(props)
const { length, fmtLength, image } = useArtistAttributes(artist.value)

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/info.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/sound-bar.vue'))
const ArtistThumbnail = defineAsyncComponent(() => import('@/components/ui/album-artist-thumbnail.vue'))
const CloseModalBtn = defineAsyncComponent(() => import('@/components/ui/close-modal-btn.vue'))

const listConfig: Partial<SongListConfig> = { columns: ['track', 'title', 'album', 'length'] }
const sharedState = reactive(sharedStore.state)

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
  // @ts-ignore
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
