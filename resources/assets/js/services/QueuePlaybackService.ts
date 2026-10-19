import { shuffle } from 'lodash-es'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { queueStore } from '@/stores/queueStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { playableStore } from '@/stores/playableStore'
import { userStore } from '@/stores/userStore'
import { logger } from '@/utils/logger'
import { isEpisode, isSong } from '@/utils/typeGuards'
import { arrayify, getPlayableProp, use } from '@/utils/helpers'
import { eventBus } from '@/utils/eventBus'
import { isAudioContextSupported } from '@/utils/supports'
import { audioService } from '@/services/audioService'
import { http } from '@/services/http'
import { signedUrlService } from '@/services/signedUrlService'
import { lastWriteWins } from '@/utils/lastWriteWins'
import { socketService } from '@/services/socketService'
import { useEpisodeProgressTracking } from '@/composables/useEpisodeProgressTracking'
import { BasePlaybackService } from '@/services/BasePlaybackService'
import { crossfadeService } from '@/services/crossfadeService'
import { volumeNormalizer } from '@/services/volumeNormalizer'
import { encyclopediaService } from '@/services/encyclopediaService'
import { volumeManager } from '@/services/volumeManager'
import { useBranding } from '@/composables/useBranding'
import { usePageTitle } from '@/composables/usePageTitle'

/**
 * The number of seconds before the current playable ends to start preloading the next one.
 */
const PRELOAD_BUFFER = 30
const EXPIRED_SOURCE_RELOAD_COOLDOWN_MS = 60_000

// Both Last.fm and ListenBrainz consider a track listened to once it has been played for half its
// length or four minutes, whichever comes first. Tracks shorter than 30 seconds never count.
const SCROBBLE_AFTER_SECONDS = 240
const MIN_SCROBBLE_LENGTH = 30

const savePlaybackStatus = lastWriteWins((status: { song: Playable['id']; position: number }) =>
  http.silently.withRetries.put('queue/playback-status', status),
)

export class QueuePlaybackService extends BasePlaybackService {
  private repeatModes: RepeatMode[] = ['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE']
  private upNext: Playable | null = null
  private latestPlayRequest = 0
  private lastExpiredSourceReload: { playableId: Playable['id']; at: number } | null = null
  private lastSavedPosition: { playableId: Playable['id']; second: number } | null = null

  /**
   * The next item in the queue.
   * If we're in REPEAT_ALL mode and there's no next item, just get the first item.
   */
  public get next() {
    if (queueStore.next) {
      return queueStore.next
    }

    return preferences.repeat_mode === 'REPEAT_ALL' ? queueStore.first : undefined
  }

  /**
   * The previous item in the queue.
   * If we're in REPEAT_ALL mode and there's no prev item, get the last item.
   */
  public get previous() {
    if (queueStore.previous) {
      return queueStore.previous
    }

    return preferences.repeat_mode === 'REPEAT_ALL' ? queueStore.last : undefined
  }

  public registerPlay(playable: Playable) {
    recentlyPlayedStore.add(playable)
    playableStore.registerPlay(playable)
    playable.play_count_registered = true

    if (isSong(playable) && !playable.album_cover) {
      encyclopediaService.fetchForAlbum({ id: playable.album_id } as Album).catch(logger.error)
    }
  }

  public async preload(playable: Playable) {
    playable.preloaded = true

    try {
      const audioElement = document.createElement('audio')
      audioElement.setAttribute('src', await playableStore.resolveSourceUrl(playable))
      audioElement.setAttribute('preload', 'auto')
      audioElement.load()
    } catch (error: unknown) {
      logger.warn(error)
    }
  }

  /**
   * Play a song. Because
   *
   * So many adventures couldn't happen today,
   * So many songs we forgot to play
   * So many dreams swinging out of the blue
   * We'll let them come true
   */
  public async play(playable: Playable, position = 0) {
    const isCrossfadeFinalization = crossfadeService.active && crossfadeService.state!.playable.id === playable.id

    // Cancel any active crossfade unless we're finalizing it
    if (!isCrossfadeFinalization) {
      this.cancelCrossfade()
    }

    if (isEpisode(playable)) {
      useEpisodeProgressTracking().trackEpisode(playable)
    }

    queueStore.queueIfNotQueued(playable, 'after-current')

    // If for any reason (most likely a bug), the requested playable has been deleted, attempt the next item in the queue.
    if (isSong(playable) && playable.deleted) {
      logger.warn('Attempted to play a deleted playable', playable)

      if (this.next && this.next.id !== playable.id) {
        await this.playNext()
      }

      return
    }

    if (queueStore.current) {
      queueStore.current.playback_state = 'Stopped'
    }

    playable.playback_state = 'Playing'

    await this.setNowPlayingMeta(playable)

    if (isCrossfadeFinalization) {
      this.takeOverCrossfadingTrack()
      this.showNotification(playable)
    } else {
      const playRequest = ++this.latestPlayRequest

      this.media.pause()
      let sourceUrl: string

      try {
        sourceUrl = await playableStore.resolveSourceUrl(playable)
      } catch (error: unknown) {
        logger.error(error)

        if (playRequest === this.latestPlayRequest) {
          playable.playback_state = 'Stopped'
        }

        return
      }

      if (playRequest !== this.latestPlayRequest) {
        return
      }

      this.media.src = sourceUrl
      audioService.routeElement(this.media)

      if (playable.playback_state !== 'Playing') {
        this.recordStartTime(playable)
        this.seekTo(position)
        return
      }

      if (position === 0) {
        await this.restart()
      } else {
        this.seekTo(position)
        await this.resume()
      }

      use(this.next, next => playableStore.resolveSourceUrl(next).catch(logger.warn))
    }

    this.setMediaSessionActionHandlers()
  }

  public showNotification(playable: Playable) {
    if (!isSong(playable) && !isEpisode(playable)) {
      throw new Error('Invalid playable type.')
    }

    if (preferences.show_now_playing_notification) {
      try {
        const notification = new window.Notification(`♫ ${playable.title}`, {
          icon: getPlayableProp(playable, 'album_cover', 'episode_image'),
          body: isSong(playable) ? `${playable.album_name} – ${playable.artist_name}` : playable.title,
        })

        notification.onclick = () => window.focus()

        window.setTimeout(() => notification.close(), 5000)
      } catch (error: unknown) {
        // Notification fails.
        // @link https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
        logger.error(error)
      }
    }

    if (!navigator.mediaSession) {
      return
    }

    const cover = getPlayableProp(playable, 'album_cover', 'episode_image') || useBranding().cover

    navigator.mediaSession.metadata = new MediaMetadata({
      title: playable.title,
      artist: getPlayableProp(playable, 'artist_name', 'podcast_author'),
      album: getPlayableProp(playable, 'album_name', 'podcast_title'),
      artwork: [48, 64, 96, 128, 192, 256, 384, 512].map(d => ({
        src: cover,
        sizes: `${d}x${d}`,
      })),
    })
  }

  public async restart() {
    const playable = queueStore.current!

    // Reset the "up next" value to let subscribers know that the next item is cleared
    // (because another playable, likely the "next" one, is being played)
    this.announceUpNext(null)

    this.recordStartTime(playable)
    socketService.broadcast('SOCKET_STREAMABLE', playable)

    this.savePosition(playable, 0)

    this.media.currentTime = 0

    try {
      await this.media.play()
      navigator.mediaSession && (navigator.mediaSession.playbackState = 'playing')
      this.showNotification(playable)
    } catch (error: unknown) {
      // convert this into a warning to avoid breaking the app
      logger.warn(error)
    }
  }

  public rotateRepeatMode() {
    let index = this.repeatModes.indexOf(preferences.repeat_mode) + 1

    if (index >= this.repeatModes.length) {
      index = 0
    }

    preferences.repeat_mode = this.repeatModes[index]
  }

  /**
   * Play the prev item the queue, if one is found.
   * If there's no prev item and the current mode is NO_REPEAT, we stop completely.
   */
  public async playPrev() {
    this.settleCrossfade()

    // If the item's duration is greater than 5 seconds, and we've passed 5 seconds into it,
    // restart playing instead.
    if (this.media.currentTime > 5 && queueStore.current!.length > 5) {
      this.media.currentTime = 0

      return
    }

    if (!this.previous && preferences.repeat_mode === 'NO_REPEAT') {
      await this.stop()
    } else {
      this.previous && (await this.play(this.previous))
    }
  }

  /**
   * Play the next item in the queue if one is found.
   * If there's no next item and the current mode is NO_REPEAT, we stop completely.
   */
  public async playNext() {
    if (!this.next && preferences.repeat_mode === 'NO_REPEAT') {
      await this.stop() //  Nothing lasts forever, even cold November rain.
    } else {
      this.next && (await this.play(this.next))
    }
  }

  /**
   * Remove playables from the queue. If the playing one is among them, move on to the one that followed it.
   */
  public async removeFromQueue(playables: Playable[]) {
    const current = queueStore.current
    const removesCurrent = Boolean(current && playables.some(({ id }) => id === current.id))
    const nextRemaining = removesCurrent ? queueStore.findNextRemaining(playables) : undefined

    queueStore.unqueue(playables)

    if (!removesCurrent) {
      return
    }

    const next = nextRemaining ?? (preferences.repeat_mode === 'REPEAT_ALL' ? queueStore.first : undefined)

    next ? await this.play(next) : await this.stop()
  }

  public async stop() {
    this.cancelCrossfade()

    if (this.media) {
      this.media.pause()
      this.seekTo(0)
    }

    usePageTitle().setNowPlayingTitle(null)

    queueStore.current && (queueStore.current.playback_state = 'Stopped')

    navigator.mediaSession && (navigator.mediaSession.playbackState = 'none')

    socketService.broadcast('SOCKET_PLAYBACK_STOPPED')
  }

  public async pause() {
    this.settleCrossfade()
    this.media.pause()

    this.savePosition(queueStore.current!, Math.ceil(this.media.currentTime))

    queueStore.current!.playback_state = 'Paused'
    navigator.mediaSession && (navigator.mediaSession.playbackState = 'paused')

    socketService.broadcast('SOCKET_STREAMABLE', queueStore.current)
  }

  public async resume() {
    const playable = queueStore.current!

    if (!this.media.src) {
      // on first load when the queue is loaded from saved state, the player's src is empty
      // we need to properly set it as well as any kind of playback metadata
      try {
        this.media.src = await playableStore.resolveSourceUrl(playable)
      } catch (error: unknown) {
        logger.error(error)
        return
      }

      this.seekTo(commonStore.state.queue_state.playback_position)

      await this.setNowPlayingMeta(queueStore.current!)
      this.recordStartTime(playable)
    } else if (signedUrlService.hasExpired(this.media.src)) {
      await this.reloadExpiredSource(playable)
    }

    try {
      await this.media.play()
    } catch (error: unknown) {
      logger.error(error)
    }

    queueStore.current!.playback_state = 'Playing'
    navigator.mediaSession && (navigator.mediaSession.playbackState = 'playing')

    socketService.broadcast('SOCKET_STREAMABLE', playable)
  }

  public async toggle() {
    if (!queueStore.current) {
      await this.playFirstInQueue()
      return
    }

    if (queueStore.current.playback_state !== 'Playing') {
      await this.resume()
      return
    }

    this.pause()
  }

  /**
   * Queue up playables (replace them into the queue) and start playing right away.
   */
  public async queueAndPlay(playables: MaybeArray<Playable>, shuffled = false) {
    playables = arrayify(playables)

    if (shuffled) {
      playables = shuffle(playables)
    }

    await this.stop()
    queueStore.replaceQueueWith(playables)
    await this.play(queueStore.first)
  }

  public async playFirstInQueue() {
    queueStore.all.length && (await this.play(queueStore.first))
  }

  private async setNowPlayingMeta(playable: Playable) {
    usePageTitle().setNowPlayingTitle(playable.title)
    this.media.setAttribute('title', isSong(playable) ? `${playable.artist_name} - ${playable.title}` : playable.title)

    if (isAudioContextSupported) {
      await audioService.context.resume()
    }
  }

  // Record the UNIX timestamp the playable starts playing, for scrobbling purpose
  private recordStartTime(song: Playable) {
    if (!isSong(song)) {
      return
    }

    song.play_start_time = Math.floor(Date.now() / 1000)
    song.play_count_registered = false
    song.scrobble_registered = false
  }

  private get scrobblingEnabled() {
    const { preferences } = userStore.current

    return (
      (commonStore.state.uses_last_fm && Boolean(preferences.lastfm_session_key)) ||
      Boolean(preferences.listenbrainz_token)
    )
  }

  private scrobbleIfEligible(playable: Playable, currentTime: number, duration: number) {
    if (playable.scrobble_registered || !isSong(playable) || !this.scrobblingEnabled) {
      return
    }

    if (duration < MIN_SCROBBLE_LENGTH || currentTime < Math.min(duration / 2, SCROBBLE_AFTER_SECONDS)) {
      return
    }

    playable.scrobble_registered = true
    playableStore.scrobble(playable)
  }

  public forward(seconds: number): void {
    this.media.currentTime += seconds
  }

  protected onEnded(): void {
    // If a crossfade is active (completed or not), the outgoing track has ended — transition to the next song
    if (crossfadeService.active) {
      const { playable } = crossfadeService.state!
      this.play(playable)
      return
    }

    preferences.repeat_mode === 'REPEAT_ONE' ? this.restart() : this.playNext()
  }

  protected async onError(error: ErrorEvent) {
    logger.error(error)

    const playable = queueStore.current

    if (playable && !this.reloadedSourceRecently(playable) && signedUrlService.hasExpired(this.media.src)) {
      this.lastExpiredSourceReload = { playableId: playable.id, at: Date.now() }

      try {
        await this.reloadExpiredSource(playable)
      } catch (reloadError: unknown) {
        logger.error(reloadError)
      }

      return
    }

    await this.playNext()
  }

  private reloadedSourceRecently(playable: Playable) {
    const lastReload = this.lastExpiredSourceReload

    return lastReload?.playableId === playable.id && Date.now() - lastReload.at < EXPIRED_SOURCE_RELOAD_COOLDOWN_MS
  }

  private async reloadExpiredSource(playable: Playable) {
    const position = this.media.currentTime

    playableStore.forgetResolvedSourceUrl(playable)
    this.media.src = await playableStore.resolveSourceUrl(playable)
    this.seekTo(position)

    if (playable.playback_state === 'Playing') {
      await this.media.play()
    }
  }

  private isPositionSaved(playable: Playable, second: number) {
    return this.lastSavedPosition?.playableId === playable.id && this.lastSavedPosition.second === second
  }

  private savePosition(playable: Playable, second: number) {
    this.lastSavedPosition = { playableId: playable.id, second }
    savePlaybackStatus({ song: playable.id, position: second })
  }

  protected onTimeUpdate(): void {
    if (crossfadeService.active) {
      this.fadeCrossfadingTracks()

      return
    }

    const currentPlayable = queueStore.current

    if (!currentPlayable) {
      return
    }

    const media = this.media

    // If we've passed 25% of the playable, it's safe to say it has been "played".
    // See https://github.com/koel/koel/issues/1087
    if (!currentPlayable.play_count_registered && media.currentTime * 4 >= media.duration) {
      this.registerPlay(currentPlayable)
    }

    this.scrobbleIfEligible(currentPlayable, media.currentTime, media.duration)

    const second = Math.ceil(media.currentTime)

    if (second % 5 === 0 && !this.isPositionSaved(currentPlayable, second)) {
      this.savePosition(currentPlayable, second)

      // if the current item is an episode, we emit an event to update the progress on the client side as well
      if (isEpisode(currentPlayable)) {
        eventBus.emit('EPISODE_PROGRESS_UPDATED', currentPlayable, second)
      }
    }

    const nextPlayable = this.next

    if (!nextPlayable || nextPlayable.id === currentPlayable.id) {
      this.announceUpNext(null)
      return
    }

    this.announceUpNext(media.currentTime + 15 > media.duration ? nextPlayable : null)

    // Preload the next playable if we're near the end of the current playback.
    if (media.currentTime + PRELOAD_BUFFER > media.duration && !nextPlayable.preloaded) {
      this.preload(nextPlayable)
    }

    // Initiate crossfade if enabled and near the end of the track
    const crossfadeDuration = preferences.crossfade_duration

    if (
      crossfadeDuration > 0 &&
      !crossfadeService.active &&
      preferences.repeat_mode !== 'REPEAT_ONE' &&
      media.duration > crossfadeDuration * 2 && // skip for short tracks
      media.currentTime + crossfadeDuration >= media.duration
    ) {
      if (!playableStore.getResolvedSourceUrl(nextPlayable)) {
        playableStore.resolveSourceUrl(nextPlayable).catch(logger.warn)
      } else if (crossfadeService.start(nextPlayable, media.duration - media.currentTime, volumeManager.get())) {
        // Show the incoming track as "now playing" immediately
        queueStore.current!.playback_state = 'Stopped'
        nextPlayable.playback_state = 'Playing'
        this.setNowPlayingMeta(nextPlayable)
        this.showNotification(nextPlayable)
        this.recordStartTime(nextPlayable)
        this.registerPlay(nextPlayable)
        this.fadeCrossfadingTracks()
      }
    }
  }

  private announceUpNext(playable: Playable | null) {
    if (this.upNext?.id === playable?.id) {
      return
    }

    this.upNext = playable
    eventBus.emit('UP_NEXT', playable)
  }

  private fadeCrossfadingTracks() {
    const remaining = this.media.duration - this.media.currentTime
    const progress = Math.max(0, 1 - remaining / preferences.crossfade_duration)

    this.setVolume(volumeManager.get() * (1 - progress))
    crossfadeService.fadeInIncomingTrack(progress, volumeManager.get())
  }

  public rewind(seconds: number): void {
    this.settleCrossfade()
    this.media.currentTime -= seconds
  }

  public fastSeek(position: number): void {
    this.media.fastSeek(position || 0)
  }

  public seekTo(position: number): void {
    this.settleCrossfade()
    this.media.currentTime = position || 0
  }

  /**
   * The incoming track is already playing through the crossfade element: make it the primary one
   * without a src change, a seek, or an interruption, and discard the outgoing element.
   */
  private takeOverCrossfadingTrack() {
    const { incomingAudio, playable } = crossfadeService.state!

    this.media.pause()
    this.media.removeAttribute('src')
    this.media.load()

    this.swapMediaElement(incomingAudio)
    this.setVolume(volumeManager.get())

    if (isAudioContextSupported && audioService.context) {
      volumeNormalizer.applyToStreamable(playable)
      audioService.reconnectSource(incomingAudio)
    }

    crossfadeService.state = null
  }

  /** Finish an active crossfade at once, so pausing or seeking acts on the song shown as playing. */
  private settleCrossfade() {
    if (crossfadeService.active) {
      this.takeOverCrossfadingTrack()
    }
  }

  /** Cancel any active crossfade and restore volume */
  private cancelCrossfade() {
    if (crossfadeService.active) {
      crossfadeService.cancel()
      this.setVolume(volumeManager.get())
    }
  }
}

export const playbackService = new QueuePlaybackService()
