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
        <span>{{ pluralize(artist.album_count, 'album') }}</span>
        <span>{{ pluralize(songs, 'song') }}</span>
        <span>{{ duration }}</span>
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

    <SongListSkeleton v-if="loading"/>
    <SongList v-else ref="songList" @sort="sort" @press:enter="onPressEnter" @scroll-breakpoint="onScrollBreakpoint"/>

    <section v-if="!loading && useLastfm && showingInfo" class="info-wrapper">
      <CloseModalBtn class="close-modal" @click="showingInfo = false"/>
      <div class="inner">
        <ArtistInfo :artist="artist" mode="full"/>
      </div>
    </section>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, ref, toRef, toRefs } from 'vue'
import { eventBus, logger, pluralize, requireInjection } from '@/utils'
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { useSongList, useThirdPartyServices } from '@/composables'
import router from '@/router'
import { DialogBoxKey } from '@/symbols'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ArtistThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'

const dialog = requireInjection(DialogBoxKey)

const props = defineProps<{ artist: number }>()
const { artist: id } = toRefs(props)

const artist = ref<Artist>()
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
} = useSongList(songs, 'artist', { columns: ['track', 'title', 'album', 'length'] })

const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const CloseModalBtn = defineAsyncComponent(() => import('@/components/ui/BtnCloseModal.vue'))

const { useLastfm } = useThirdPartyServices()
const allowDownload = toRef(commonStore.state, 'allow_download')

const download = () => downloadService.fromArtist(artist.value)
const showInfo = () => (showingInfo.value = true)

onMounted(async () => {
  loading.value = true

  try {
    [artist.value, songs.value] = await Promise.all([
      artistStore.resolve(id.value),
      songStore.fetchForArtist(id.value)
    ])
  } catch (e) {
    logger.error(e)
    dialog.value.error('Failed to load artist. Please try again.')
  } finally {
    loading.value = false
  }
})

eventBus.on('SONGS_UPDATED', () => {
  // if the current artist has been deleted, go back to the list
  artistStore.byId(id.value) || router.go('artists')
})
</script>

<style lang="scss" scoped>
@import "#/partials/_mixins.scss";

#artistWrapper {
  @include artist-album-info-wrapper();
}
</style>
