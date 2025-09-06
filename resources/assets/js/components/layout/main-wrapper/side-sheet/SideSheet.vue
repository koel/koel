<template>
  <aside
    :class="{ 'showing-pane': activeTab }"
    class="fixed sm:relative top-0 w-screen md:w-auto flex flex-col md:flex-row-reverse z-[2] text-k-text-secondary"
  >
    <header
      class="controls flex md:flex-col justify-between items-center md:w-[64px] md:py-6 tw:px-0
      bg-black/5 md:border-l border-solid md:border-l-white/5 md:border-b-0 md:shadow-none
      z-[2] w-screen flex-row border-b border-b-white/5 border-l-0 shadow-xl
      py-0 px-6 h-k-header-height"
    >
      <div class="btn-group">
        <SideSheetButton class="md:hidden" @click.prevent="expandSidebar">
          <Icon :icon="faBars" fixed-width />
        </SideSheetButton>
        <SideSheetTabHeader v-if="songPlaying" v-model="activeTab" />
      </div>

      <div class="btn-group">
        <AboutKoelButton />
        <LogoutButton />
        <ProfileAvatar @click="onProfileLinkClick" />
      </div>
    </header>

    <main v-if="songPlaying" v-show="activeTab" class="panes py-8 px-6 overflow-auto bg-k-bg-secondary">
      <SideSheetPanelLazyWrapper
        id="extraPanelLyrics"
        :active="activeTab === 'Lyrics'"
        :should-mount="shouldMountTab('Lyrics')"
        aria-labelledby="extraTabLyrics"
        data-testid="side-sheet-lyrics"
      >
        <LyricsPane v-if="streamable" :song="streamable" />
      </SideSheetPanelLazyWrapper>

      <SideSheetPanelLazyWrapper
        id="extraPanelArtist"
        :active="activeTab === 'Artist'"
        :should-mount="activatedTabs.includes('Artist')"
        data-testid="side-sheet-artist"
        aria-labelledby="extraTabArtist"
      >
        <ArtistInfo v-if="artist && !loadingArtist" :artist="artist" mode="aside" />
        <SideSheetArtistAlbumInfoSkeleton v-else />
      </SideSheetPanelLazyWrapper>

      <SideSheetPanelLazyWrapper
        id="extraPanelAlbum"
        :active="activeTab === 'Album'"
        :should-mount="activatedTabs.includes('Album')"
        data-testid="side-sheet-album"
        aria-labelledby="extraTabAlbum"
      >
        <AlbumInfo v-if="album && !loadingAlbum" :album="album" mode="aside" />
        <SideSheetArtistAlbumInfoSkeleton v-else />
      </SideSheetPanelLazyWrapper>

      <SideSheetPanelLazyWrapper
        id="extraPanelYouTube"
        :active="activeTab === 'YouTube'"
        :should-mount="activatedTabs.includes('YouTube')"
        aria-labelledby="extraTabYouTube"
        data-testid="side-sheet-youtube"
      >
        <YouTubeVideoList v-if="shouldShowYouTubeTab && streamable" :song="streamable" />
      </SideSheetPanelLazyWrapper>
    </main>
  </aside>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { faBars } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref, watch } from 'vue'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { eventBus } from '@/utils/eventBus'
import { isSong } from '@/utils/typeGuards'
import { defineAsyncComponent, requireInjection } from '@/utils/helpers'
import { CurrentStreamableKey } from '@/symbols'

import ProfileAvatar from '@/components/ui/ProfileAvatar.vue'
import AboutKoelButton from '@/components/layout/main-wrapper/side-sheet/AboutKoelButton.vue'
import LogoutButton from '@/components/layout/main-wrapper/side-sheet/LogoutButton.vue'
import SideSheetButton from '@/components/layout/main-wrapper/side-sheet/SideSheetButton.vue'
import SideSheetPanelLazyWrapper from '@/components/layout/main-wrapper/side-sheet/SideSheetPanelLazyWrapper.vue'
import SideSheetArtistAlbumInfoSkeleton
  from '@/components/layout/main-wrapper/side-sheet/SideSheetArtistAlbumInfoSkeleton.vue'
import SideSheetTabHeader from './SideSheetTabHeader.vue'

const LyricsPane = defineAsyncComponent(() => import('@/components/ui/LyricsPane.vue'))
const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const YouTubeVideoList = defineAsyncComponent(() => import('@/components/ui/youtube/YouTubeVideoList.vue'))

const { useYouTube } = useThirdPartyServices()

const streamable = requireInjection(CurrentStreamableKey, ref(undefined))
const activeTab = ref<SideSheetTab | null>(null)
const activatedTabs = ref<SideSheetTab[]>([])

const artist = ref<Artist>()
const album = ref<Album>()
const loadingArtist = ref(false)
const loadingAlbum = ref(false)

const songPlaying = computed(() => streamable.value && isSong(streamable.value))
const shouldShowYouTubeTab = computed(() => useYouTube.value && songPlaying.value)

const shouldMountTab = (tab: SideSheetTab) => activatedTabs.value.includes(tab)

const maybeResolveArtist = async (song: Song) => {
  if (song.artist_id === artist.value?.id) {
    return
  }

  loadingArtist.value = true
  artist.value = await artistStore.resolve(song.artist_id)
  loadingArtist.value = false
}

const maybeResolveAlbum = async (song: Song) => {
  if (song.album_id === album.value?.id) {
    return
  }

  loadingAlbum.value = true
  album.value = await albumStore.resolve(song.album_id)
  loadingAlbum.value = false
}

const resolveArtistOrAlbum = (activeTab: SideSheetTab | null = null, song: Song) => {
  switch (activeTab) {
    case 'Artist':
      return maybeResolveArtist(song)
    case 'Album':
      return maybeResolveAlbum(song)
    default:
      break
  }
}

watch(streamable, song => {
  if (!song || !isSong(song)) {
    return
  }

  streamable.value = song
  resolveArtistOrAlbum(activeTab.value, song)
}, { immediate: true })

watch(activeTab, tab => {
  if (!tab) {
    return
  }

  preferenceStore.active_extra_panel_tab = tab

  if (!activatedTabs.value.includes(tab)) {
    activatedTabs.value.push(tab)
  }

  if (streamable.value && isSong(streamable.value)) {
    resolveArtistOrAlbum(tab, streamable.value)
  }
})

const onProfileLinkClick = () => isMobile.any && (activeTab.value = null)
const expandSidebar = () => eventBus.emit('TOGGLE_SIDEBAR')

onMounted(() => {
  if (isMobile.any) {
    return
  }

  activeTab.value = preferenceStore.active_extra_panel_tab
})
</script>

<style lang="postcss" scoped>
@import '@/../css/partials/mixins.pcss';

@tailwind utilities;

@layer utilities {
  .btn-group {
    @apply flex md:flex-col justify-between items-center gap-1 md:gap-3;
  }
}

aside {
  @media screen and (max-width: 768px) {
    &.showing-pane {
      height: 100%;
    }
  }
}

.panes {
  @apply no-hover:overflow-y-auto w-k-side-sheet-width;

  box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0.1);

  @media screen and (max-width: 768px) {
    width: 100%;
    height: calc(100vh - var(--header-height) - var(--footer-height));
  }
}
</style>
