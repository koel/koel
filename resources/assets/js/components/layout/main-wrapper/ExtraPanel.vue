<template>
  <div id="extraPanel" :class="{ 'showing-pane': activeTab }">
    <div class="controls">
      <div class="top">
        <SidebarMenuToggleButton class="burger"/>
        <ExtraPanelTabHeader v-if="song" v-model="activeTab"/>
      </div>

      <div class="bottom">
        <button
          v-koel-tooltip.left
          :title="shouldNotifyNewVersion ? 'New version available!' : 'About Koel'"
          type="button"
          @click.prevent="openAboutKoelModal"
        >
          <icon :icon="faInfoCircle"/>
          <span v-if="shouldNotifyNewVersion" class="new-version-notification"/>
        </button>

        <button v-koel-tooltip.left title="Log out" type="button" @click.prevent="logout">
          <icon :icon="faArrowRightFromBracket"/>
        </button>

        <ProfileAvatar @click="onProfileLinkClick"/>
      </div>
    </div>

    <div class="panes" v-if="song" v-show="activeTab">
      <div
        v-show="activeTab === 'Lyrics'"
        id="extraPanelLyrics"
        aria-labelledby="extraTabLyrics"
        role="tabpanel"
        tabindex="0"
      >
        <LyricsPane :song="song"/>
      </div>

      <div
        v-show="activeTab === 'Artist'"
        id="extraPanelArtist"
        aria-labelledby="extraTabArtist"
        role="tabpanel"
        tabindex="0"
      >
        <ArtistInfo v-if="artist" :artist="artist" mode="aside"/>
        <span v-else>Loading…</span>
      </div>

      <div
        v-show="activeTab === 'Album'"
        id="extraPanelAlbum"
        aria-labelledby="extraTabAlbum"
        role="tabpanel"
        tabindex="0"
      >
        <AlbumInfo v-if="album" :album="album" mode="aside"/>
        <span v-else>Loading…</span>
      </div>

      <div
        v-show="activeTab === 'YouTube'"
        data-testid="extra-panel-youtube"
        id="extraPanelYouTube"
        aria-labelledby="extraTabYouTube"
        role="tabpanel"
        tabindex="0"
      >
        <YouTubeVideoList v-if="useYouTube && song" :song="song"/>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { faArrowRightFromBracket, faInfoCircle } from '@fortawesome/free-solid-svg-icons'
import { defineAsyncComponent, onMounted, ref, watch } from 'vue'
import { albumStore, artistStore, preferenceStore } from '@/stores'
import { useAuthorization, useNewVersionNotification, useThirdPartyServices } from '@/composables'
import { eventBus, logger, requireInjection } from '@/utils'
import { CurrentSongKey } from '@/symbols'

import ProfileAvatar from '@/components/ui/ProfileAvatar.vue'
import SidebarMenuToggleButton from '@/components/ui/SidebarMenuToggleButton.vue'

const LyricsPane = defineAsyncComponent(() => import('@/components/ui/LyricsPane.vue'))
const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/ArtistInfo.vue'))
const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const YouTubeVideoList = defineAsyncComponent(() => import('@/components/ui/YouTubeVideoList.vue'))
const ExtraPanelTabHeader = defineAsyncComponent(() => import('@/components/ui/ExtraPanelTabHeader.vue'))

const { currentUser } = useAuthorization()
const { useYouTube } = useThirdPartyServices()
const { shouldNotifyNewVersion } = useNewVersionNotification()

const song = requireInjection(CurrentSongKey, ref(null))
const activeTab = ref<ExtraPanelTab | null>(null)

const artist = ref<Artist | null>(null)
const album = ref<Album | null>(null)

watch(song, song => song && fetchSongInfo(song))
watch(activeTab, tab => (preferenceStore.activeExtraPanelTab = tab))

const fetchSongInfo = async (_song: Song) => {
  song.value = _song
  artist.value = null
  album.value = null

  try {
    artist.value = await artistStore.resolve(_song.artist_id)
    album.value = await albumStore.resolve(_song.album_id)
  } catch (error) {
    logger.log('Failed to fetch media information', error)
  }
}

const openAboutKoelModal = () => eventBus.emit('MODAL_SHOW_ABOUT_KOEL')
const onProfileLinkClick = () => isMobile.any && (activeTab.value = null)
const logout = () => eventBus.emit('LOG_OUT')

onMounted(() => isMobile.any || (activeTab.value = preferenceStore.activeExtraPanelTab))
</script>

<style lang="scss" scoped>
#extraPanel {
  display: flex;
  flex-direction: row-reverse;
  color: var(--color-text-secondary);
  height: var(--header-height);
  z-index: 1;

  @media screen and (max-width: 768px) {
    @include themed-background();
    flex-direction: column;
    position: fixed;
    top: 0;
    width: 100%;

    &.showing-pane {
      height: 100%;
    }
  }
}

.panes {
  width: var(--extra-panel-width);
  padding: 2rem 1.7rem;
  background: var(--color-bg-secondary);
  overflow: auto;
  box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0.1);

  @media (hover: none) {
    // Enable scroll with momentum on touch devices
    overflow-y: scroll;
    -webkit-overflow-scrolling: touch;
  }

  @media screen and (max-width: 768px) {
    width: 100%;
    height: calc(100vh - var(--header-height) - var(--footer-height));
  }
}

.controls {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  height: 100%;
  width: 64px;
  padding: 1.6rem 0 1.2rem;
  background-color: rgba(0, 0, 0, .05);
  border-left: 1px solid rgba(255, 255, 255, .05);

  @media screen and (max-width: 768px) {
    z-index: 2;
    height: auto;
    width: 100%;
    flex-direction: row;
    padding: .5rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, .05);
    box-shadow: 0 0 30px 0 rgba(0, 0, 0, .5);
  }

  .top, .bottom {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;

    @media screen and (max-width: 768px) {
      flex-direction: row;
      gap: .25rem;
    }
  }

  :deep(button) {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    aspect-ratio: 1/1;
    border-radius: 999rem;
    background: rgba(0, 0, 0, .3);
    font-size: 1.2rem;
    opacity: .7;
    transition: opacity .2s ease-in-out;
    color: currentColor;
    cursor: pointer;

    @media screen and (max-width: 768px) {
      background: none;
    }

    &:hover, &.active {
      opacity: 1;
      color: var(--color-text-primary);
    }

    &:active {
      transform: scale(.9);
    }

    &.burger {
      display: none;

      @media screen and (max-width: 768px) {
        display: block;
      }
    }
  }

  .new-version-notification {
    position: absolute;
    width: 10px;
    height: 10px;
    background: var(--color-highlight);
    right: 1px;
    top: 1px;
    border-radius: 50%;
  }
}
</style>
