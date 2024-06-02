<template>
  <ContextMenu ref="base" data-testid="song-context-menu" extra-class="song-menu">
    <template v-if="onlyOneSongSelected">
      <li @click.stop.prevent="doPlayback">
        <span v-if="firstSongPlaying">Pause</span>
        <span v-else>Play</span>
      </li>
      <template v-if="isSong(playables[0])">
        <li @click="viewAlbum(playables[0])">Go to Album</li>
        <li @click="viewArtist(playables[0])">Go to Artist</li>
      </template>
      <template v-else>
        <li @click="viewPodcast(playables[0] as Episode)">Go to Podcast</li>
        <li @click="viewEpisode(playables[0] as Episode)">See Episode Description</li>
      </template>
    </template>
    <li class="has-sub">
      Add To
      <ul class="submenu menu-add-to context-menu">
        <template v-if="queue.length">
          <li v-if="currentSong" @click="queueAfterCurrent">After Current</li>
          <li @click="queueToBottom">Bottom of Queue</li>
          <li @click="queueToTop">Top of Queue</li>
        </template>
        <li v-else @click="queueToBottom">Queue</li>
        <template v-if="!isFavoritesScreen">
          <li class="separator" />
          <li @click="addToFavorites">Favorites</li>
        </template>
        <li v-if="normalPlaylists.length" class="separator" />
        <template class="d-block">
          <ul v-if="normalPlaylists.length" v-koel-overflow-fade class="relative max-h-48 overflow-y-auto">
            <li v-for="p in normalPlaylists" :key="p.id" @click="addToExistingPlaylist(p)">{{ p.name }}</li>
          </ul>
        </template>
        <li class="separator" />
        <li @click="addToNewPlaylist">New Playlist…</li>
      </ul>
    </li>

    <template v-if="isQueueScreen">
      <li class="separator" />
      <li @click="removeFromQueue">Remove from Queue</li>
      <li class="separator" />
    </template>

    <template v-if="isFavoritesScreen">
      <li class="separator" />
      <li @click="removeFromFavorites">Remove from Favorites</li>
    </template>

    <template v-if="visibilityActions.length">
      <li class="separator" />
      <li v-for="action in visibilityActions" :key="action.label" @click="action.handler">{{ action.label }}</li>
    </template>

    <li v-if="canEditSongs" @click="openEditForm">Edit…</li>
    <li v-if="allowsDownload" @click="download">Download</li>
    <li v-if="onlyOneSongSelected && canBeShared" @click="copyUrl">Copy Shareable URL</li>

    <template v-if="canBeRemovedFromPlaylist">
      <li class="separator" />
      <li @click="removePlayablesFromPlaylist">Remove from Playlist</li>
    </template>

    <template v-if="canEditSongs">
      <li class="separator" />
      <li @click="deleteFromFilesystem">Delete from Filesystem</li>
    </template>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
import { arrayify, getPlayableCollectionContentType, copyText, eventBus, isSong, pluralize } from '@/utils'
import { commonStore, favoriteStore, playlistStore, queueStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import {
  useContextMenu,
  useDialogBox,
  useKoelPlus,
  useMessageToaster,
  usePlayableMenuMethods,
  usePlaylistManagement,
  usePolicies,
  useRouter
} from '@/composables'

const { toastSuccess, toastError, toastWarning } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, getRouteParam, isCurrentScreen } = useRouter()
const { base, ContextMenu, open, close, trigger } = useContextMenu()
const { removeFromPlaylist } = usePlaylistManagement()
const { isPlus } = useKoelPlus()

const playables = ref<Playable[]>([])

const {
  queueAfterCurrent,
  queueToBottom,
  queueToTop,
  addToFavorites,
  addToExistingPlaylist,
  addToNewPlaylist
} = usePlayableMenuMethods(playables, close)

const playlists = toRef(playlistStore.state, 'playlists')
const allowsDownload = toRef(commonStore.state, 'allows_download')
const queue = toRef(queueStore.state, 'playables')
const currentSong = toRef(queueStore, 'current')

const { currentUserCan } = usePolicies()

const canEditSongs = computed(() => contentType.value === 'songs' && currentUserCan.editSong(playables.value as Song[]))
const onlyOneSongSelected = computed(() => playables.value.length === 1)
const firstSongPlaying = computed(() => playables.value.length ? playables.value[0].playback_state === 'Playing' : false)
const normalPlaylists = computed(() => playlists.value.filter(({ is_smart }) => !is_smart))

const makePublic = () => trigger(async () => {
  if (contentType.value !== 'songs') {
    throw 'Only songs can be marked as public or private'
  }

  await songStore.publicize(playables.value as Song[])
  toastSuccess(`Unmarked ${pluralize(playables.value, 'song')} as private.`)
})

const makePrivate = () => trigger(async () => {
  if (contentType.value !== 'songs') {
    throw 'Only songs can be marked as public or private'
  }

  const privatizedIds = await songStore.privatize(playables.value as Song[])

  if (!privatizedIds.length) {
    toastError('Songs cannot be marked as private if they’part of a collaborative playlist.')
    return
  }

  if (privatizedIds.length < playables.value.length) {
    toastWarning('Some songs cannot be marked as private as they’re part of a collaborative playlist.')
    return
  }

  toastSuccess(`Marked ${pluralize(playables.value, 'song')} as private.`)
})

const canBeShared = computed(() => !isPlus.value || (isSong(playables.value[0]) && playables.value[0].is_public))
const contentType = computed(() => getPlayableCollectionContentType(playables.value))

const visibilityActions = computed(() => {
  if (contentType.value !== 'songs' || !canEditSongs.value) return []

  const visibilities = Array.from(new Set((playables.value as Song[]).map((song => song.is_public
      ? 'public'
      : 'private'
  ))))

  if (visibilities.length === 2) {
    return [
      {
        label: 'Unmark as Private',
        handler: makePublic
      },
      {
        label: 'Mark as Private',
        handler: makePrivate
      }
    ]
  }

  return visibilities[0] === 'public'
    ? [{ label: 'Mark as Private', handler: makePrivate }]
    : [{ label: 'Unmark as Private', handler: makePublic }]
})

const canBeRemovedFromPlaylist = computed(() => {
  if (!isCurrentScreen('Playlist')) return false
  const playlist = playlistStore.byId(getRouteParam('id')!)
  return playlist && !playlist.is_smart
})

const isQueueScreen = computed(() => isCurrentScreen('Queue'))
const isFavoritesScreen = computed(() => isCurrentScreen('Favorites'))

const doPlayback = () => trigger(() => {
  if (!playables.value.length) return

  switch (playables.value[0].playback_state) {
    case 'Playing':
      playbackService.pause()
      break

    case 'Paused':
      playbackService.resume()
      break

    default:
      playbackService.play(playables.value[0])
      break
  }
})

const openEditForm = () => trigger(() =>
  playables.value.length
  && contentType.value === 'songs'
  && eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', playables.value as Song[])
)

const viewAlbum = (song: Song) => trigger(() => go(`album/${song.album_id}`))
const viewArtist = (song: Song) => trigger(() => go(`artist/${song.artist_id}`))
const viewPodcast = (episode: Episode) => trigger(() => go(`podcasts/${episode.podcast_id}`))
const viewEpisode = (episode: Episode) => trigger(() => go(`episodes/${episode.id}`))
const download = () => trigger(() => downloadService.fromPlayables(playables.value))

const removePlayablesFromPlaylist = () => trigger(async () => {
  const playlist = playlistStore.byId(getRouteParam('id')!)
  if (!playlist) return

  await removeFromPlaylist(playlist, playables.value)
})

const removeFromQueue = () => trigger(() => queueStore.unqueue(playables.value))
const removeFromFavorites = () => trigger(() => favoriteStore.unlike(playables.value))

const copyUrl = () => trigger(async () => {
  await copyText(songStore.getShareableUrl(playables.value[0]))
  toastSuccess('URL copied to clipboard.')
})

const deleteFromFilesystem = () => trigger(async () => {
  if (await showConfirmDialog('Delete selected song(s) from the filesystem? This action is NOT reversible!')) {
    await songStore.deleteFromFilesystem(playables.value as Song[])
    toastSuccess(`Deleted ${pluralize(playables.value, 'song')} from the filesystem.`)
    eventBus.emit('SONGS_DELETED', playables.value as Song[])
  }
})

eventBus.on('PLAYABLE_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _songs) => {
  playables.value = arrayify(_songs)
  await open(pageY, pageX)
})
</script>
