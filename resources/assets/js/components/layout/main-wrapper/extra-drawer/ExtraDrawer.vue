<template>
  <aside
    :class="{ 'showing-pane': activeTab }"
    class="fixed sm:relative top-0 w-screen md:w-auto flex flex-col md:flex-row-reverse z-[2] text-k-text-secondary"
  >
    <div
      class="controls flex md:flex-col justify-between items-center md:w-[64px] md:py-6 tw:px-0
      bg-black/5 md:border-l border-solid md:border-l-white/5 md:border-b-0 md:shadow-none
      z-[2] w-screen flex-row border-b border-b-white/5 border-l-0 shadow-xl
      py-0 px-6 h-k-header-height"
    >
      <div class="btn-group">
        <SidebarMenuToggleButton />
        <ExtraDrawerTabHeader v-if="song" v-model="activeTab" />
      </div>

      <div class="btn-group">
        <AboutKoelButton />
        <LogoutButton />
        <ProfileAvatar @click="onProfileLinkClick" />
      </div>
    </div>

    <div v-if="song" v-show="activeTab" class="panes py-8 px-6 overflow-auto bg-k-bg-secondary">
      <div
        v-show="activeTab === 'Lyrics'"
        id="extraPanelLyrics"
        aria-labelledby="extraTabLyrics"
        role="tabpanel"
        tabindex="0"
      >
        <LyricsPane :song="song" />
      </div>

      <div
        v-show="activeTab === 'Artist'"
        id="extraPanelArtist"
        aria-labelledby="extraTabArtist"
        role="tabpanel"
        tabindex="0"
      >
        <ArtistInfo v-if="artist" :artist="artist" mode="aside" />
        <span v-else>Loading…</span>
      </div>

      <div
        v-show="activeTab === 'Album'"
        id="extraPanelAlbum"
        aria-labelledby="extraTabAlbum"
        role="tabpanel"
        tabindex="0"
      >
        <AlbumInfo v-if="album" :album="album" mode="aside" />
        <span v-else>Loading…</span>
      </div>

      <div
        v-show="activeTab === 'YouTube'"
        id="extraPanelYouTube"
        data-testid="extra-drawer-youtube"
        aria-labelledby="extraTabYouTube"
        role="tabpanel"
        tabindex="0"
      >
        <YouTubeVideoList v-if="useYouTube && song" :song="song" />
      </div>
    </div>
  </aside>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { defineAsyncComponent, onMounted, ref, watch } from 'vue'
import { albumStore, artistStore, preferenceStore } from '@/stores'
import { useErrorHandler, useThirdPartyServices } from '@/composables'
import { requireInjection } from '@/utils'
import { CurrentSongKey } from '@/symbols'

import ProfileAvatar from '@/components/ui/ProfileAvatar.vue'
import SidebarMenuToggleButton from '@/components/ui/SidebarMenuToggleButton.vue'
import AboutKoelButton from '@/components/layout/main-wrapper/extra-drawer/AboutKoelButton.vue'
import LogoutButton from '@/components/layout/main-wrapper/extra-drawer/LogoutButton.vue'

const LyricsPane = defineAsyncComponent(() => import('@/components/ui/LyricsPane.vue'))
const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const YouTubeVideoList = defineAsyncComponent(() => import('@/components/ui/youtube/YouTubeVideoList.vue'))
const ExtraDrawerTabHeader = defineAsyncComponent(() => import('./ExtraDrawerTabHeader.vue'))

const { useYouTube } = useThirdPartyServices()

const song = requireInjection(CurrentSongKey, ref(undefined))
const activeTab = ref<ExtraPanelTab | null>(null)

const artist = ref<Artist>()
const album = ref<Album>()

const fetchSongInfo = async (_song: Song) => {
  song.value = _song
  artist.value = undefined
  album.value = undefined

  try {
    artist.value = await artistStore.resolve(_song.artist_id)
    album.value = await albumStore.resolve(_song.album_id)
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  }
}

watch(song, song => song && fetchSongInfo(song), { immediate: true })
watch(activeTab, tab => (preferenceStore.active_extra_panel_tab = tab))

const onProfileLinkClick = () => isMobile.any && (activeTab.value = null)

onMounted(() => isMobile.any || (activeTab.value = preferenceStore.active_extra_panel_tab))
</script>

<style lang="postcss" scoped>
@import '@/../css/partials/mixins.pcss';

@tailwind utilities;

@layer utilities {
  .btn-group {
    @apply flex md:flex-col justify-between items-center gap-1 md:gap-3
  }
}

aside {
  @media screen and (max-width: 768px) {
    @mixin themed-background;

    &.showing-pane {
      height: 100%;
    }
  }
}

.panes {
  @apply no-hover:overflow-y-auto w-k-extra-drawer-width;

  box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0.1);

  @media screen and (max-width: 768px) {
    width: 100%;
    height: calc(100vh - var(--header-height) - var(--footer-height));
  }
}
</style>
