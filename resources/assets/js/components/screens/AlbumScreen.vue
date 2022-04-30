<template>
  <section id="albumWrapper">
    <ScreenHeader has-thumbnail>
      {{ album.name }}
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:thumbnail>
        <AlbumThumbnail :entity="album"/>
      </template>

      <template v-slot:meta>
        <span v-if="songs.length">
          by
          <a v-if="isNormalArtist" :href="`#!/artist/${album.artist.id}`" class="artist">{{ album.artist.name }}</a>
          <span class="nope" v-else>{{ album.artist.name }}</span>
          •
          {{ pluralize(songs.length, 'song') }}
          •
          {{ duration }}

          <template v-if="useLastfm">
            •
            <a class="info" href title="View album's extra information" @click.prevent="showInfo">Info</a>
          </template>
          <template v-if="allowDownload">
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
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
          :songs="songs"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList ref="songList" :config="listConfig" :items="songs" type="album" @press:enter="onPressEnter"/>

    <section v-if="useLastfm && showing" class="info-wrapper">
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
import { computed, defineAsyncComponent, ref, toRef, toRefs, watch } from 'vue'
import { pluralize } from '@/utils'
import { artistStore, commonStore } from '@/stores'
import { albumInfoService, downloadService } from '@/services'
import { useSongList } from '@/composables'
import router from '@/router'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/SoundBar.vue'))
const AlbumThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))
const CloseModalBtn = defineAsyncComponent(() => import('@/components/ui/BtnCloseModal.vue'))

const props = defineProps<{ album: Album }>()
const { album } = toRefs(props)

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songs,
  songList,
  duration,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls
} = useSongList(ref(album.value.songs))

const listConfig: Partial<SongListConfig> = { columns: ['track', 'title', 'length'] }
const useLastfm = toRef(commonStore.state, 'useLastfm')
const allowDownload = toRef(commonStore.state, 'allowDownload')
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
