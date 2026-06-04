import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'
import { podcastStore } from '@/stores/podcastStore'
import Component from './StarRating.vue'

describe('starRating.vue', () => {
  const h = createHarness()

  it('renders five radio inputs with the current rating checked (rating prop)', () => {
    h.render(Component, { props: { rating: 3 } })

    const stars = screen.getAllByRole('radio') as HTMLInputElement[]
    expect(stars).toHaveLength(5)
    expect(stars[2].checked).toBe(true)
    expect(stars[0].checked).toBe(false)
  })

  it('reads currentRating from a rateable when provided', () => {
    const album = h.factory('album').make({ rating: 4 })
    h.render(Component, { props: { rateable: album } })

    const stars = screen.getAllByRole('radio') as HTMLInputElement[]
    expect(stars[3].checked).toBe(true)
  })

  it('emits the chosen rating on click (rating prop only)', async () => {
    const { emitted } = h.render(Component, { props: { rating: 0 } })

    await h.user.click(screen.getByRole('radio', { name: 'Rate 4 of 5' }))

    expect(emitted('rate')?.[0]).toEqual([4])
  })

  it('clicking the active star clears the rating', async () => {
    const { emitted } = h.render(Component, { props: { rating: 3 } })

    await h.user.click(screen.getByRole('radio', { name: 'Rate 3 of 5' }))

    expect(emitted('rate')?.[0]).toEqual([0])
  })

  it('dispatches to albumStore.rate when given an album rateable', async () => {
    const album = h.factory('album').make({ rating: 0 })
    const spy = vi.spyOn(albumStore, 'rate').mockResolvedValue()

    h.render(Component, { props: { rateable: album } })
    await h.user.click(screen.getByRole('radio', { name: 'Rate 5 of 5' }))

    expect(spy).toHaveBeenCalledWith(album, 5)
  })

  it('dispatches to artistStore.rate when given an artist rateable', async () => {
    const artist = h.factory('artist').make({ rating: 0 })
    const spy = vi.spyOn(artistStore, 'rate').mockResolvedValue()

    h.render(Component, { props: { rateable: artist } })
    await h.user.click(screen.getByRole('radio', { name: 'Rate 2 of 5' }))

    expect(spy).toHaveBeenCalledWith(artist, 2)
  })

  it('dispatches to playableStore.rate when given a song rateable', async () => {
    const song = h.factory('song').make({ rating: 0 })
    const spy = vi.spyOn(playableStore, 'rate').mockResolvedValue()

    h.render(Component, { props: { rateable: song } })
    await h.user.click(screen.getByRole('radio', { name: 'Rate 3 of 5' }))

    expect(spy).toHaveBeenCalledWith(song, 3)
  })

  it('dispatches to podcastStore.rate when given a podcast rateable', async () => {
    const podcast = h.factory('podcast').make({ rating: 0 })
    const spy = vi.spyOn(podcastStore, 'rate').mockResolvedValue()

    h.render(Component, { props: { rateable: podcast } })
    await h.user.click(screen.getByRole('radio', { name: 'Rate 4 of 5' }))

    expect(spy).toHaveBeenCalledWith(podcast, 4)
  })

  it('labels stars with their numeric value when they do not match the current rating', () => {
    h.render(Component, { props: { rating: 3 } })

    expect(screen.getByRole('radio', { name: 'Rate 1 of 5' }).closest('label')!.title).toBe('1 star')
    expect(screen.getByRole('radio', { name: 'Rate 2 of 5' }).closest('label')!.title).toBe('2 stars')
    expect(screen.getByRole('radio', { name: 'Rate 4 of 5' }).closest('label')!.title).toBe('4 stars')
    expect(screen.getByRole('radio', { name: 'Rate 5 of 5' }).closest('label')!.title).toBe('5 stars')
  })

  it('labels the star matching the current rating with "Remove rating"', () => {
    h.render(Component, { props: { rating: 3 } })

    expect(screen.getByRole('radio', { name: 'Rate 3 of 5' }).closest('label')!.title).toBe('Remove rating')
  })

  it('shows "Remove rating" on the singular star when current rating is 1', () => {
    h.render(Component, { props: { rating: 1 } })

    expect(screen.getByRole('radio', { name: 'Rate 1 of 5' }).closest('label')!.title).toBe('Remove rating')
    expect(screen.getByRole('radio', { name: 'Rate 2 of 5' }).closest('label')!.title).toBe('2 stars')
  })

  it('falls back to numeric labels for every star when no rating is set', () => {
    h.render(Component, { props: { rating: 0 } })

    expect(screen.getByRole('radio', { name: 'Rate 1 of 5' }).closest('label')!.title).toBe('1 star')
    expect(screen.getByRole('radio', { name: 'Rate 5 of 5' }).closest('label')!.title).toBe('5 stars')
  })
})
