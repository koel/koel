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
  createHarness({
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
    const podcast = { state: { current_episode: null, progresses: {} as Record<string, number> } }
    mockResolve.mockResolvedValue(podcast)

    const { trackEpisode } = useEpisodeProgressTracking()

    const episode = { id: 'ep-1', podcast_id: 'pod-1' } as unknown as Episode
    trackEpisode(episode)

    eventBus.emit('EPISODE_PROGRESS_UPDATED', episode, 0.75)

    await vi.waitFor(() => {
      expect(podcast.state.current_episode).toBe('ep-1')
      expect(podcast.state.progresses['ep-1']).toBe(0.75)
    })
  })

  it('ignores progress for non-tracked episode', async () => {
    const { trackEpisode } = useEpisodeProgressTracking()

    const tracked = { id: 'ep-1', podcast_id: 'pod-1' } as unknown as Episode
    const other = { id: 'ep-2', podcast_id: 'pod-2' } as unknown as Episode

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
