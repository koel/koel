import type { Ref } from 'vue'
import { ref } from 'vue'
import { shuffle } from 'lodash-es'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { queueStore } from '@/stores/queueStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { playableStore } from '@/stores/playableStore'
import { userStore } from '@/stores/userStore'
import { logger } from '@/utils/logger'
import { isEpisode, isSong } from '@/utils/typeGuards'
import { arrayify, getPlayableProp } from '@/utils/helpers'
import { eventBus } from '@/utils/eventBus'
import { isAudioContextSupported } from '@/utils/supports'
import { audioService } from '@/services/audioService'
import { http } from '@/services/http'
import { socketService } from '@/services/socketService'
import { useEpisodeProgressTracking } from '@/composables/useEpisodeProgressTracking'
import { BasePlaybackService } from '@/services/BasePlaybackService'
import { crossfadeService } from '@/services/crossfadeService'
import type { CrossfadeState } from '@/services/crossfadeService'
import { encyclopediaService } from '@/services/encyclopediaService'
import { volumeManager } from '@/services/volumeManager'
import { useBranding } from '@/composables/useBranding'
import { progressiveTranscodingService } from '@/services/progressiveTranscodingService'
import { playbackPreloadService } from '@/services/playbackPreloadService'

/**
 * The number of seconds before the current playable ends to start preloading the next one.
 */
const PRELOAD_BUFFER = 30

interface ProgressivePlaybackState {
  playableId: Playable['id']
  playableLength: number
  sourceOffset: number
  timestampMode: 'unknown' | 'absolute' | 'relative'
}

interface PendingCrossfade {
  outgoingPlayableId: Playable['id']
  incomingPlayableId: Playable['id']
  state: CrossfadeState | null
  startPromise: Promise<boolean>
}

export class QueuePlaybackService extends BasePlaybackService {
  private repeatModes: RepeatMode[] = ['NO_REPEAT', 'REPEAT_ALL', 'REPEAT_ONE']
  private upNext: Ref<Playable | null> = ref(null)
  private progressivePlayback: ProgressivePlaybackState | null = null
  private cancelPendingSourceReadiness: Closure | null = null
  private pendingCrossfade: PendingCrossfade | null = null

  public get position(): number {
    return this.toLogicalTime(this.media.currentTime)
  }

  public get duration(): number {
    return this.progressivePlayback?.playableLength ?? this.media.duration
  }

  public get bufferedThrough(): number {
    const { buffered } = this.media

    if (buffered.length === 0) {
      return 0
    }

    const bufferedEnd = buffered.end(buffered.length - 1)

    return this.progressivePlayback ? Math.min(this.duration, this.toLogicalTime(bufferedEnd, false)) : bufferedEnd
  }

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

  public preload(playable: Playable) {
    playbackPreloadService.preload(playable)
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
    const readyCrossfadeState =
      position === 0 && crossfadeService.active && crossfadeService.state?.playable.id === playable.id
        ? crossfadeService.state
        : null

    if (readyCrossfadeState) {
      const pendingCrossfade = this.pendingCrossfade
      const promoted = await this.promoteCrossfade(readyCrossfadeState, pendingCrossfade)

      if (
        promoted ||
        (pendingCrossfade && this.pendingCrossfade !== pendingCrossfade) ||
        (!pendingCrossfade && crossfadeService.state !== readyCrossfadeState)
      ) {
        return
      }
    }

    this.cancelCrossfade()

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

    const source = this.setPlaybackSource(playable, position)

    if (position === 0) {
      await this.restart()
    } else {
      void this.prepareSourceAtPosition(source, position)
      await this.resume()
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

    navigator.mediaSession.metadata = new MediaMetadata({
      title: playable.title,
      artist: getPlayableProp(playable, 'artist_name', 'podcast_author'),
      album: getPlayableProp(playable, 'album_name', 'podcast_title'),
      artwork: [48, 64, 96, 128, 192, 256, 384, 512].map(d => ({
        src: getPlayableProp(playable, 'album_cover', 'episode_image'),
        sizes: `${d}x${d}`,
        type: 'image/png',
      })),
    })
  }

  public async restart() {
    const playable = queueStore.current!

    // Reset the "up next" value to let subscribers know that the next item is cleared
    // (because another playable, likely the "next" one, is being played)
    this.upNext.value = null

    this.recordStartTime(playable)
    socketService.broadcast('SOCKET_STREAMABLE', playable)

    try {
      http.silently.put('queue/playback-status', {
        song: playable.id,
        position: 0,
      })
    } catch (error: unknown) {
      logger.error(error)
    }

    const source = progressiveTranscodingService.getSource(playable)

    if (source.progressive) {
      if (!this.isProgressivePlayback(playable) || this.progressivePlayback!.sourceOffset !== 0 || this.media.ended) {
        this.setPlaybackSource(playable, 0)
      }
    } else {
      if (this.isProgressivePlayback(playable)) {
        this.setPlaybackSource(playable, 0)
      }

      this.media.currentTime = 0
    }

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
    // If the item's duration is greater than 5 seconds, and we've passed 5 seconds into it,
    // restart playing instead.
    if (this.position > 5 && queueStore.current!.length > 5) {
      this.seekTo(0)

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

  public async stop() {
    this.cancelCrossfade()
    this.clearPendingSourceReadiness()
    playbackPreloadService.clear()

    if (this.media) {
      this.media.pause()

      if (this.progressivePlayback) {
        this.replaceMediaElement()
        this.progressivePlayback = null
      } else {
        this.media.currentTime = 0
      }
    }

    document.title = useBranding().name

    queueStore.current && (queueStore.current.playback_state = 'Stopped')

    navigator.mediaSession && (navigator.mediaSession.playbackState = 'none')

    socketService.broadcast('SOCKET_PLAYBACK_STOPPED')
  }

  public async pause() {
    this.cancelCrossfade()
    this.media.pause()

    queueStore.current!.playback_state = 'Paused'
    navigator.mediaSession && (navigator.mediaSession.playbackState = 'paused')

    socketService.broadcast('SOCKET_STREAMABLE', queueStore.current)
  }

  public async resume() {
    const playable = queueStore.current!

    if (!this.media.src) {
      // on first load when the queue is loaded from saved state, the player's src is empty
      // we need to properly set it as well as any kind of playback metadata
      const position = commonStore.state.queue_state.playback_position
      const source = this.setPlaybackSource(playable, position)

      if (position > 0) {
        void this.prepareSourceAtPosition(source, position)
      }

      await this.setNowPlayingMeta(queueStore.current!)
      this.recordStartTime(playable)
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
    document.title = `${playable.title} ♫ ${useBranding().name}`
    this.media.setAttribute('title', isSong(playable) ? `${playable.artist_name} - ${playable.title}` : playable.title)

    await this.prepareAudioContext()
  }

  private async prepareAudioContext(): Promise<void> {
    if (audioService.context) {
      try {
        await audioService.context.resume()
      } catch (error: unknown) {
        logger.warn('Failed to resume the audio context:', error)
      }
    }
  }

  // Record the UNIX timestamp the playable starts playing, for scrobbling purpose
  private recordStartTime(song: Playable) {
    if (!isSong(song)) {
      return
    }

    song.play_start_time = Math.floor(Date.now() / 1000)
    song.play_count_registered = false
  }

  public forward(seconds: number): void {
    this.seekTo(this.position + seconds)
  }

  protected onEnded(): void {
    const endedPlayable = queueStore.current

    if (
      endedPlayable &&
      isSong(endedPlayable) &&
      commonStore.state.uses_last_fm &&
      userStore.current.preferences.lastfm_session_key
    ) {
      playableStore.scrobble(endedPlayable)
    }

    if (this.pendingCrossfade) {
      void this.finishPendingCrossfadeAfterEnd(this.pendingCrossfade, endedPlayable)
      return
    }

    if (crossfadeService.active && crossfadeService.state) {
      void this.promoteCrossfade(crossfadeService.state, null)
      return
    }

    if (crossfadeService.inProgress) {
      this.cancelCrossfade()
    }

    preferences.repeat_mode === 'REPEAT_ONE' ? this.restart() : this.playNext()
  }

  protected onError(error: ErrorEvent): void {
    logger.error(error)
    this.playNext()
  }

  protected onTimeUpdate(): void {
    const currentPlayable = queueStore.current

    if (!currentPlayable) {
      return
    }

    const position = this.position
    const duration = this.duration

    // If we've passed 25% of the playable, it's safe to say it has been "played".
    // See https://github.com/koel/koel/issues/1087
    if (!currentPlayable.play_count_registered && position * 4 >= duration) {
      this.registerPlay(currentPlayable)
    }

    if (Math.ceil(position) % 5 === 0) {
      // every 5 seconds, we save the current playback position to the server
      try {
        http.silently.put('queue/playback-status', {
          song: currentPlayable.id,
          position: Math.ceil(position),
        })
      } catch (error: unknown) {
        logger.error(error)
      }

      // if the current item is an episode, we emit an event to update the progress on the client side as well
      if (isEpisode(currentPlayable)) {
        eventBus.emit('EPISODE_PROGRESS_UPDATED', currentPlayable, Math.ceil(position))
      }
    }

    const nextPlayable = queueStore.next

    if (!nextPlayable) {
      return
    }

    // Set the "up next" value to the next playable if we're near the end of the current playback.
    this.upNext.value = position + 15 > duration ? nextPlayable : null

    // Preload the next playable if we're near the end of the current playback.
    if (position + PRELOAD_BUFFER > duration && !nextPlayable.preloaded && !crossfadeService.inProgress) {
      this.preload(nextPlayable)
    }

    // Initiate crossfade if enabled and near the end of the track
    const crossfadeDuration = preferences.crossfade_duration

    if (
      crossfadeDuration > 0 &&
      !crossfadeService.inProgress &&
      preferences.repeat_mode !== 'REPEAT_ONE' &&
      duration > crossfadeDuration * 2 && // skip for short tracks
      position + crossfadeDuration >= duration
    ) {
      let pendingCrossfade: PendingCrossfade | null = null
      const startPromise = crossfadeService.start(nextPlayable, crossfadeDuration, volumeManager.get(), () => {
        if (
          pendingCrossfade &&
          this.pendingCrossfade === pendingCrossfade &&
          queueStore.current?.id === pendingCrossfade.outgoingPlayableId
        ) {
          this.setVolume(volumeManager.get())
        }
      })

      pendingCrossfade = {
        outgoingPlayableId: currentPlayable.id,
        incomingPlayableId: nextPlayable.id,
        state: crossfadeService.state,
        startPromise,
      }
      this.pendingCrossfade = pendingCrossfade
    }

    // Fade out the primary player during an active crossfade
    if (crossfadeService.active && crossfadeService.state) {
      const remaining = duration - position
      const progress = Math.max(0, 1 - remaining / crossfadeDuration)
      this.setVolume(volumeManager.get() * (1 - progress))
    }
  }

  public rewind(seconds: number): void {
    this.seekTo(this.position - seconds)
  }

  public fastSeek(position: number): void {
    if (this.progressivePlayback) {
      this.seekTo(position)
      return
    }

    this.media.fastSeek(position || 0)
  }

  public seekTo(position: number): void {
    this.cancelCrossfade()

    const currentPlayable = queueStore.current
    const clampedPosition = Math.max(0, Math.min(position || 0, currentPlayable?.length || position || 0))

    if (
      currentPlayable &&
      (this.isProgressivePlayback(currentPlayable) || progressiveTranscodingService.isEligible(currentPlayable))
    ) {
      void this.reloadSourceAtPosition(currentPlayable, clampedPosition)
      return
    }

    this.media.currentTime = clampedPosition
  }

  private setPlaybackSource(playable: Playable, position: number) {
    this.clearPendingSourceReadiness()

    const source = progressiveTranscodingService.getSource(playable, position)
    const preloadedMedia = source.progressive && position === 0 ? playbackPreloadService.take(playable) : null

    if (!source.progressive || position > 0) {
      playbackPreloadService.clear()
    }

    const shouldReplaceMediaElement = !!this.media.src && (!!this.progressivePlayback || source.progressive)

    this.progressivePlayback = source.progressive
      ? {
          playableId: playable.id,
          playableLength: playable.length,
          sourceOffset: Math.max(0, position),
          timestampMode: 'unknown',
        }
      : null

    if (preloadedMedia) {
      this.adoptMediaElement(preloadedMedia)
    } else if (shouldReplaceMediaElement) {
      this.replaceMediaElement(source.url)
    } else {
      this.media.src = source.url
    }

    return source
  }

  private async reloadSourceAtPosition(playable: Playable, position: number) {
    const shouldResume = playable.playback_state === 'Playing'
    const source = this.setPlaybackSource(playable, position)
    const media = this.media
    void this.prepareSourceAtPosition(source, position)

    if (shouldResume) {
      try {
        await media.play()
      } catch (error: unknown) {
        if (media === this.media) {
          logger.warn(error)
        }
      }
    }
  }

  private isProgressivePlayback(playable?: Playable | null): playable is Song {
    return !!playable && this.progressivePlayback?.playableId === playable.id
  }

  private toLogicalTime(mediaTime: number, rememberTimestampMode = true): number {
    const state = this.progressivePlayback

    if (!state || state.sourceOffset === 0) {
      return mediaTime
    }

    if (state.timestampMode === 'absolute') {
      return mediaTime
    }

    if (state.timestampMode === 'relative') {
      return state.sourceOffset + mediaTime
    }

    if (mediaTime <= 0.05) {
      return state.sourceOffset
    }

    const timestampMode = Math.abs(mediaTime - state.sourceOffset) <= mediaTime ? 'absolute' : 'relative'

    if (rememberTimestampMode) {
      state.timestampMode = timestampMode
    }

    return timestampMode === 'absolute' ? mediaTime : state.sourceOffset + mediaTime
  }

  private prepareSourceAtPosition(source: { progressive: boolean }, position: number): Promise<boolean> {
    const media = this.media

    this.clearPendingSourceReadiness()

    const sourceReadiness = new Promise<boolean>(resolve => {
      let resolved = false
      let cleanedUp = false

      const resolveOnce = (ready: boolean) => {
        if (resolved) {
          return
        }

        resolved = true
        resolve(ready)
      }

      const cleanUp = () => {
        if (cleanedUp) {
          return
        }

        cleanedUp = true
        media.removeEventListener('loadedmetadata', onLoadedMetadata)
        media.removeEventListener('error', onLoadFailed)
        media.removeEventListener('abort', onLoadFailed)
        window.clearTimeout(timeoutId)

        if (this.cancelPendingSourceReadiness === cancel) {
          this.cancelPendingSourceReadiness = null
        }
      }

      const onLoadedMetadata = () => {
        if (media !== this.media) {
          cleanUp()
          resolveOnce(false)
          return
        }

        if (!source.progressive || Number.isFinite(media.duration)) {
          media.currentTime = position
        }

        cleanUp()
        resolveOnce(true)
      }

      const onLoadFailed = () => {
        cleanUp()
        resolveOnce(false)
      }

      const cancel = onLoadFailed
      const timeoutId = window.setTimeout(() => resolveOnce(false), 10_000)

      this.cancelPendingSourceReadiness = cancel
      media.addEventListener('loadedmetadata', onLoadedMetadata)
      media.addEventListener('error', onLoadFailed)
      media.addEventListener('abort', onLoadFailed)
      media.load()

      if (media.readyState >= 1) {
        onLoadedMetadata()
      }
    })

    return sourceReadiness
  }

  private clearPendingSourceReadiness(): void {
    this.cancelPendingSourceReadiness?.()
    this.cancelPendingSourceReadiness = null
  }

  private async finishPendingCrossfadeAfterEnd(
    pendingCrossfade: PendingCrossfade,
    endedPlayable: Playable | null | undefined,
  ): Promise<void> {
    const started = await pendingCrossfade.startPromise

    if (this.pendingCrossfade !== pendingCrossfade) {
      return
    }

    const outgoingIsStillCurrent =
      endedPlayable?.id === pendingCrossfade.outgoingPlayableId &&
      queueStore.current?.id === pendingCrossfade.outgoingPlayableId

    if (!outgoingIsStillCurrent) {
      this.cancelCrossfade()
      return
    }

    if (
      started &&
      crossfadeService.active &&
      crossfadeService.state === pendingCrossfade.state &&
      pendingCrossfade.state?.playable.id === pendingCrossfade.incomingPlayableId
    ) {
      const promoted = await this.promoteCrossfade(pendingCrossfade.state, pendingCrossfade)

      if (promoted || this.pendingCrossfade !== pendingCrossfade) {
        return
      }
    }

    if (!started || pendingCrossfade.state?.failed) {
      this.cancelCrossfade()
      await this.playNext()
    }
  }

  private async promoteCrossfade(state: CrossfadeState, pendingCrossfade: PendingCrossfade | null): Promise<boolean> {
    await this.prepareAudioContext()

    if (pendingCrossfade && this.pendingCrossfade !== pendingCrossfade) {
      return false
    }

    if (
      pendingCrossfade &&
      (pendingCrossfade.state !== state || queueStore.current?.id !== pendingCrossfade.outgoingPlayableId)
    ) {
      return false
    }

    const consumedState = crossfadeService.consumeReadyState(state)

    if (!consumedState) {
      return false
    }

    this.pendingCrossfade = null

    const playable = consumedState.playable

    if (isEpisode(playable)) {
      useEpisodeProgressTracking().trackEpisode(playable)
    }

    queueStore.queueIfNotQueued(playable, 'after-current')

    if (queueStore.current) {
      queueStore.current.playback_state = 'Stopped'
    }

    playable.playback_state = 'Playing'
    document.title = `${playable.title} ♫ ${useBranding().name}`

    const previousMedia = this.media
    const { incomingAudio, progressive } = consumedState

    incomingAudio.setAttribute(
      'title',
      isSong(playable) ? `${playable.artist_name} - ${playable.title}` : playable.title,
    )
    this.swapMediaElement(incomingAudio)
    this.setVolume(volumeManager.get())
    this.progressivePlayback = progressive
      ? { playableId: playable.id, playableLength: playable.length, sourceOffset: 0, timestampMode: 'unknown' }
      : null

    previousMedia.pause()
    previousMedia.removeAttribute('src')
    previousMedia.load()

    if (isAudioContextSupported && audioService.context) {
      audioService.reconnectSource(incomingAudio)
    }

    this.recordStartTime(playable)
    this.showNotification(playable)
    this.setMediaSessionActionHandlers()

    return true
  }

  private replaceMediaElement(sourceUrl?: string): void {
    const replacementMedia = document.createElement('audio')

    this.adoptMediaElement(replacementMedia)

    if (sourceUrl) {
      replacementMedia.src = sourceUrl
    }
  }

  private adoptMediaElement(replacementMedia: HTMLMediaElement): void {
    const previousMedia = this.media

    replacementMedia.crossOrigin = 'anonymous'
    replacementMedia.volume = previousMedia.volume
    replacementMedia.muted = previousMedia.muted
    replacementMedia.playbackRate = previousMedia.playbackRate
    replacementMedia.title = previousMedia.title

    this.swapMediaElement(replacementMedia)

    if (audioService.context) {
      audioService.reconnectSource(replacementMedia)
    }

    previousMedia.pause()
    previousMedia.removeAttribute('src')
    previousMedia.load()
  }

  /** Cancel any in-progress crossfade and restore volume */
  private cancelCrossfade() {
    const hadCrossfade = this.pendingCrossfade !== null || crossfadeService.inProgress

    this.pendingCrossfade = null

    if (crossfadeService.inProgress) {
      crossfadeService.cancel()
    }

    if (hadCrossfade) {
      this.setVolume(volumeManager.get())
    }
  }
}

export const playbackService = new QueuePlaybackService()
