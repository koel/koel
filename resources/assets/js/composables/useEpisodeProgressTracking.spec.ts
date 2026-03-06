import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { useEpisodeProgressTracking } from './useEpisodeProgressTracking'

const mockResolve = vi.fn()

vi.mock('@/stores/podcastStore', () => ({
  podcastStore: {
    resolve: (...args: any[]) => mockResolve(...args),
  },
}))

describe('useEpisodeProgressTracking', () => {
  const h = createHarness({
    beforeEach: () => {
      mockResolve.mockReset()
      eventBus.removeAllListeners('EPISODE_PROGRESS_UPDATED')
    },
  })

  it('returns trackEpisode function', () => {
    const { trackEpisode } = useEpisodeProgressTracking()
    expect(typeof trackEpisode).toBe('function')
  })

  it('updates progress on tracked episode', async () => {
    const podcast = h.factory('podcast')
    podcast.state.current_episode = null
    podcast.state.progresses = {}
    mockResolve.mockResolvedValue(podcast)

    const { trackEpisode } = useEpisodeProgressTracking()

    const episode = h.factory('episode')
    trackEpisode(episode)

    eventBus.emit('EPISODE_PROGRESS_UPDATED', episode, 0.75)

    await vi.waitFor(() => {
      expect(podcast.state.current_episode).toBe(episode.id)
      expect(podcast.state.progresses[episode.id]).toBe(0.75)
    })
  })

  it('ignores progress for non-tracked episode', async () => {
    const { trackEpisode } = useEpisodeProgressTracking()

    const tracked = h.factory('episode')
    const other = h.factory('episode')

    trackEpisode(tracked)

    eventBus.emit('EPISODE_PROGRESS_UPDATED', other, 0.5)

    // Give it a tick to process
    await new Promise(resolve => setTimeout(resolve, 10))

    expect(mockResolve).not.toHaveBeenCalled()
  })

  it('registers listener only once (singleton)', () => {
    useEpisodeProgressTracking()
    useEpisodeProgressTracking()
    useEpisodeProgressTracking()

    expect(eventBus.listeners('EPISODE_PROGRESS_UPDATED')).toHaveLength(1)
  })
})
