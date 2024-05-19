import { eventBus } from '@/utils'
import { podcastStore } from '@/stores'

let progressTrackedEpisode: Episode | null = null

export const useEpisodeProgressTracking = () => {
  if (eventBus.listeners('EPISODE_PROGRESS_UPDATED').length === 0) {
    eventBus.on('EPISODE_PROGRESS_UPDATED', async ({ id }, progress: number) => {
      if (!progressTrackedEpisode || progressTrackedEpisode.id !== id) return

      const podcast = await podcastStore.resolve(progressTrackedEpisode.podcast_id)
      podcast.state.current_episode = progressTrackedEpisode.id
      podcast.state.progresses[progressTrackedEpisode.id] = progress
    })
  }

  const trackEpisode = (episode: Episode) => (progressTrackedEpisode = episode)

  return {
    trackEpisode
  }
}
