<template>
  <ul>
    <template v-if="onlyOneSelected">
      <MenuItem @click="doPlayback">{{ firstSongPlaying ? 'Pause' : 'Play' }}</MenuItem>
      <Separator />
      <MenuItem>
        Go to
        <template #subMenuItems>
          <template v-if="isSong(playables[0])">
            <MenuItem @click="viewAlbum(playables[0] as Song)">
              <template #icon>
                <Icon :icon="faCompactDisc" fixed-width />
              </template>
              {{ playables[0].album_name }}
            </MenuItem>
            <MenuItem @click="viewArtist(playables[0] as Song)">
              <template #icon>
                <MicVocalIcon :size="16" class="inline-block" />
              </template>
              {{ playables[0].artist_name }}
            </MenuItem>
          </template>
          <template v-else>
            <MenuItem @click="viewPodcast(playables[0] as Episode)">
              <template #icon>
                <Icon :icon="faPodcast" fixed-width />
              </template>
              Podcast
            </MenuItem>
            <MenuItem @click="viewEpisode(playables[0] as Episode)">
              <template #icon>
                <Icon :icon="faHeadphones" fixed-width />
              </template>
              Episode
            </MenuItem>
            <MenuItem
              v-if="(playables[0] as Episode).episode_link"
              @click="visitEpisodeWebpage(playables[0] as Episode)"
            >
              <template #icon>
                <Icon :icon="faExternalLink" fixed-width />
              </template>
              Webpage
            </MenuItem>
          </template>
        </template>
      </MenuItem>
    </template>
    <MenuItem>
      Add To
      <template #subMenuItems>
        <template v-if="queue.length">
          <MenuItem v-if="currentSong" @click="queueAfterCurrent">After Current</MenuItem>
          <MenuItem @click="queueToBottom">Bottom of Queue</MenuItem>
          <MenuItem @click="queueToTop">Top of Queue</MenuItem>
        </template>
        <MenuItem v-else @click="queueToBottom">Queue</MenuItem>
        <template v-if="!isFavoritesScreen && !(onlyOneSelected && playables[0].favorite)">
          <Separator />
          <MenuItem @click="addToFavorites">Favorites</MenuItem>
        </template>
        <Separator v-if="normalPlaylists.length" />
        <template class="block">
          <ul v-if="normalPlaylists.length" v-koel-overflow-fade class="relative max-h-48 overflow-y-auto">
            <MenuItem v-for="p in normalPlaylists" :key="p.id" @click="addToExistingPlaylist(p)">
              {{ p.name }}
            </MenuItem>
          </ul>
        </template>
        <Separator />
        <MenuItem @click="addToNewPlaylist">New Playlist…</MenuItem>
      </template>
    </MenuItem>

    <template v-if="isQueueScreen">
      <Separator />
      <MenuItem @click="removeFromQueue">Remove from Queue</MenuItem>
      <Separator />
    </template>

    <template v-if="isFavoritesScreen">
      <Separator />
      <MenuItem @click="removeFromFavorites">Remove from Favorites</MenuItem>
    </template>

    <template v-if="visibilityActions.length">
      <Separator />
      <MenuItem v-for="{ label, handler } in visibilityActions" :key="label" @click="handler">
        {{ label }}
      </MenuItem>
    </template>

    <MenuItem v-if="onlyOneSelected">
      Share
      <template #subMenuItems>
        <MenuItem v-if="canBeShared" @click="copyUrl">
          <template #icon>
            <Icon :icon="faLink" fixed-width />
          </template>
          Copy URL
        </MenuItem>
        <MenuItem @click="showEmbedModal">
          <template #icon>
            <Icon :icon="faCode" fixed-width />
          </template>
          Embed…
        </MenuItem>
      </template>
    </MenuItem>

    <MenuItem v-if="allowEdit" @click="openEditForm">Edit…</MenuItem>
    <MenuItem v-if="downloadable" @click="download">Download</MenuItem>

    <template v-if="canBeRemovedFromPlaylist">
      <Separator />
      <MenuItem @click="removePlayablesFromPlaylist">Remove from Playlist</MenuItem>
    </template>

    <template v-if="allowEdit">
      <Separator />
      <MenuItem @click="deleteFromFilesystem">Delete from Filesystem</MenuItem>
    </template>
  </ul>
</template>

<script lang="ts" setup>
import {
  faCode,
  faCompactDisc,
  faExternalLink,
  faHeadphones,
  faLink,
  faPodcast,
} from '@fortawesome/free-solid-svg-icons'
import { MicVocalIcon } from 'lucide-vue-next'
import { computed, ref, toRef } from 'vue'
import { pluralize } from '@/utils/formatters'
import { eventBus } from '@/utils/eventBus'
import { arrayify, copyText } from '@/utils/helpers'
import { getPlayableCollectionContentType, isSong } from '@/utils/typeGuards'
import { commonStore } from '@/stores/commonStore'
import { playlistStore } from '@/stores/playlistStore'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useRouter } from '@/composables/useRouter'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import { usePlaylistContentManagement } from '@/composables/usePlaylistContentManagement'
import { usePlayableMenuMethods } from '@/composables/usePlayableMenuMethods'
import { usePolicies } from '@/composables/usePolicies'
import { useContextMenu } from '@/composables/useContextMenu'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { playback } from '@/services/playbackManager'

const props = defineProps<{ playables: MaybeArray<Playable> }>()
const playables = ref(arrayify(props.playables))

const { toastSuccess, toastError, toastWarning } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, getRouteParam, isCurrentScreen, url } = useRouter()
const { MenuItem, Separator, closeContextMenu, trigger } = useContextMenu()
const { removeFromPlaylist } = usePlaylistContentManagement()
const { isPlus } = useKoelPlus()

const {
  queueAfterCurrent,
  queueToBottom,
  queueToTop,
  addToFavorites,
  addToExistingPlaylist,
  removeFromFavorites,
  removeFromQueue,
  addToNewPlaylist,
} = usePlayableMenuMethods(playables, closeContextMenu)

const playlists = toRef(playlistStore.state, 'playlists')

const downloadable = computed(() => {
  if (!commonStore.state.allows_download) {
    return false
  }

  // If multiple playables are selected, make sure zip extension is available on the server
  return playables.value.length === 1 || commonStore.state.supports_batch_downloading
})

const queue = toRef(queueStore.state, 'playables')
const currentSong = toRef(queueStore, 'current')

const { currentUserCan } = usePolicies()

const contentType = computed(() => getPlayableCollectionContentType(playables.value))
const allowEdit = computed(() => contentType.value === 'songs' && currentUserCan.editSong(playables.value as Song[]))
const onlyOneSelected = computed(() => playables.value.length === 1)
const firstSongPlaying = computed(() => playables.value.length ? playables.value[0].playback_state === 'Playing' : false)
const normalPlaylists = computed(() => playlists.value.filter(({ is_smart }) => !is_smart))
const canBeShared = computed(() => !isPlus.value || (isSong(playables.value[0]) && playables.value[0].is_public))

const makePublic = () => trigger(async () => {
  if (contentType.value !== 'songs') {
    throw new Error('Only songs can be marked as public or private')
  }

  await playableStore.publicizeSongs(playables.value as Song[])
  toastSuccess(`Unmarked ${pluralize(playables.value, 'song')} as private.`)
})

const makePrivate = () => trigger(async () => {
  if (contentType.value !== 'songs') {
    throw new Error('Only songs can be marked as public or private')
  }

  const privatizedIds = await playableStore.privatizeSongs(playables.value as Song[])

  if (!privatizedIds.length) {
    toastError('Songs cannot be marked as private if they’re part of a collaborative playlist.')
    return
  }

  if (privatizedIds.length < playables.value.length) {
    toastWarning('Some songs cannot be marked as private as they’re part of a collaborative playlist.')
    return
  }

  toastSuccess(`Marked ${pluralize(playables.value, 'song')} as private.`)
})

const visibilityActions = computed(() => {
  if (contentType.value !== 'songs' || !allowEdit.value) {
    return []
  }

  if (!isPlus.value) {
    return []
  }

  const visibilities = Array.from(new Set((playables.value as Song[]).map(song => song.is_public
    ? 'public'
    : 'private',
  )))

  if (visibilities.length === 2) {
    return [
      {
        label: 'Unmark as Private',
        handler: makePublic,
      },
      {
        label: 'Mark as Private',
        handler: makePrivate,
      },
    ]
  }

  return visibilities[0] === 'public'
    ? [{ label: 'Mark as Private', handler: makePrivate }]
    : [{ label: 'Unmark as Private', handler: makePublic }]
})

const canBeRemovedFromPlaylist = computed(() => {
  if (!isCurrentScreen('Playlist')) {
    return false
  }
  const playlist = playlistStore.byId(getRouteParam('id')!)
  return playlist && !playlist.is_smart
})

const isQueueScreen = computed(() => isCurrentScreen('Queue'))
const isFavoritesScreen = computed(() => isCurrentScreen('Favorites'))

const doPlayback = () => trigger(async () => {
  if (!playables.value.length) {
    return
  }

  switch (playables.value[0].playback_state) {
    case 'Playing':
      await playback().pause()
      break

    case 'Paused':
      await playback().resume()
      break

    default:
      await playback().play(playables.value[0])
      break
  }
})

const openEditForm = () => trigger(() =>
  playables.value.length
  && contentType.value === 'songs'
  && eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', playables.value as Song[]),
)

const viewAlbum = (song: Song) => trigger(() => go(url('albums.show', { id: song.album_id })))
const viewArtist = (song: Song) => trigger(() => go(url('artists.show', { id: song.artist_id })))
const viewPodcast = (episode: Episode) => trigger(() => go(url('podcasts.show', { id: episode.podcast_id })))
const viewEpisode = (episode: Episode) => trigger(() => go(url('episodes.show', { id: episode.id })))
const visitEpisodeWebpage = (episode: Episode) => trigger(() => window.open(episode.episode_link!, '_blank'))
const download = () => trigger(() => downloadService.fromPlayables(playables.value))

const removePlayablesFromPlaylist = () => trigger(async () => {
  const playlist = playlistStore.byId(getRouteParam('id'))

  if (!playlist) {
    return
  }

  await removeFromPlaylist(playlist, playables.value)
})

const copyUrl = () => trigger(async () => {
  await copyText(playableStore.getShareableUrl(playables.value[0]))
  toastSuccess('URL copied to clipboard.')
})

const showEmbedModal = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_EMBED_FORM', playables.value[0]))

const deleteFromFilesystem = () => trigger(async () => {
  if (await showConfirmDialog('Delete selected playable(s) from the filesystem? This action is NOT reversible!')) {
    await playableStore.deleteSongsFromFilesystem(playables.value as Song[])
    toastSuccess(`Deleted ${pluralize(playables.value, 'song')} from the filesystem.`)
    eventBus.emit('SONGS_DELETED', playables.value as Song[])
  }
})
</script>
