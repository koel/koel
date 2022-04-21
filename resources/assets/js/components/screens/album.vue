<template>
  <section id="albumWrapper">
    <ScreenHeader has-thumbnail>
      {{ album.name }}
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <AlbumThumbnail :entity="album"/>
      </template>

      <template v-slot:meta>
        <span v-if="album.songs.length">
          by
          <a class="artist" v-if="isNormalArtist" :href="`#!/artist/${album.artist.id}`">{{ album.artist.name }}</a>
          <span class="nope" v-else>{{ album.artist.name }}</span>
          •
          {{ pluralize(album.songs.length, 'song') }}
          •
          {{ fmtLength }}

          <template v-if="sharedState.useLastfm">
            •
            <a class="info" href @click.prevent="showInfo" title="View album's extra information">Info</a>
          </template>
          <template v-if="sharedState.allowDownload">
            •
            <a class="download" href @click.prevent="download" title="Download all songs in album" role="button">
              Download All
            </a>
          </template>
        </span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="album.songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          :songs="album.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </ScreenHeader>

    <SongList :items="album.songs" type="album" :config="listConfig" ref="songList"/>

    <section class="info-wrapper" v-if="sharedState.useLastfm && showing">
      <CloseModalBtn @click="showing = false"/>
      <div class="inner">
        <div class="loading" v-if="loading">
          <SoundBar/>
        </div>
        <AlbumInfo :album="album" mode="full" v-else/>
      </div>
    </section>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, reactive, ref, toRefs, watch } from 'vue'
import { pluralize } from '@/utils'
import { artistStore, sharedStore } from '@/stores'
import { albumInfo as albumInfoService, download as downloadService } from '@/services'
import router from '@/router'
import { useAlbumAttributes, useSongList } from '@/composables'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/sound-bar.vue'))
const AlbumThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))
const CloseModalBtn = defineAsyncComponent(() => import('@/components/ui/close-modal-btn.vue'))

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songList,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  playAll,
  playSelected,
  toggleControls
} = useSongList()

const props = defineProps<{ album: Album }>()
const { album } = toRefs(props)

const { length, fmtLength } = useAlbumAttributes(album.value)

const listConfig: Partial<SongListConfig> = { columns: ['track', 'title', 'length'] }
const sharedState = reactive(sharedStore.state)
const showing = ref(false)
const loading = ref(true)

const isNormalArtist = computed(() => {
  return !artistStore.isVariousArtists(album.value.artist) && !artistStore.isUnknownArtist(album.value.artist)
})

/**
 * Watch the album's song count.
 * If this is changed to 0, the user has edited the songs on this album
 * and moved all of them into another album.
 * We should then go back to the album list.
 */
watch(() => album.value.songs.length, newSongCount => newSongCount || router.go('albums'))

watch(album, () => {
  showing.value = false
  // @ts-ignore
  songList.value?.sort()
})

const download = () => downloadService.fromAlbum(album.value)

const showInfo = async () => {
  showing.value = true

  if (!album.value.info) {
    try {
      await albumInfoService.fetch(album.value)
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
#albumWrapper {
  @include artist-album-info-wrapper();
}
</style>
