import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { usePlayableListControls } from './usePlayableListControls'

describe('usePlayableListControls', () => {
  createHarness()

  it('enables addTo.queue for non-Queue screens', () => {
    const { config } = usePlayableListControls('Album')
    expect(config.addTo.queue).toBe(true)
  })

  it('disables addTo.queue for Queue screen', () => {
    const { config } = usePlayableListControls('Queue')
    expect(config.addTo.queue).toBe(false)
  })

  it('enables addTo.favorites for non-Favorites screens', () => {
    const { config } = usePlayableListControls('Album')
    expect(config.addTo.favorites).toBe(true)
  })

  it('disables addTo.favorites for Favorites screen', () => {
    const { config } = usePlayableListControls('Favorites')
    expect(config.addTo.favorites).toBe(false)
  })

  it('enables clearQueue only for Queue screen', () => {
    expect(usePlayableListControls('Queue').config.clearQueue).toBe(true)
    expect(usePlayableListControls('Album').config.clearQueue).toBe(false)
  })

  it('enables refresh only for Playlist screen', () => {
    expect(usePlayableListControls('Playlist').config.refresh).toBe(true)
    expect(usePlayableListControls('Album').config.refresh).toBe(false)
  })

  it('enables filter for supported screens', () => {
    expect(usePlayableListControls('Queue').config.filter).toBe(true)
    expect(usePlayableListControls('Artist').config.filter).toBe(true)
    expect(usePlayableListControls('Favorites').config.filter).toBe(true)
  })

  it('accepts config overrides as object', () => {
    const { config } = usePlayableListControls('Album', { clearQueue: true })
    expect(config.clearQueue).toBe(true)
  })

  it('accepts config overrides as function', () => {
    const { config } = usePlayableListControls('Album', () => ({ clearQueue: true }))
    expect(config.clearQueue).toBe(true)
  })
})
