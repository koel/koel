import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { isEpisode } from '@/utils/typeGuards'

export const playbackSpeeds = [0.75, 1, 1.25, 1.5, 1.75, 2]

const speedPreferenceKey = (playable: Playable) =>
  isEpisode(playable) ? 'podcast_playback_speed' : 'music_playback_speed'

export const playbackSpeedService = {
  getSpeed(playable: Playable) {
    return preferences[speedPreferenceKey(playable)]
  },

  saveSpeed(playable: Playable, speed: number) {
    preferences[speedPreferenceKey(playable)] = speed
  },

  applyToMedia(media: HTMLMediaElement, speed: number) {
    media.preservesPitch = true
    media.defaultPlaybackRate = speed
    media.playbackRate = speed
  },

  applyForPlayable(media: HTMLMediaElement, playable: Playable) {
    this.applyToMedia(media, this.getSpeed(playable))
  },
}
