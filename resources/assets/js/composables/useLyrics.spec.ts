import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { useLyrics } from '@/composables/useLyrics'

describe('useLyrics', () => {
  const h = createHarness()

  it('handles non-LRC lyrics', () => {
    const song = ref(h.factory('song', {
      lyrics: 'This is a normal text\n without any LRC format',
    }))

    const { lrcLyrics, hasLyrics, plainTextLyrics, isLrc } = useLyrics(song)

    expect(lrcLyrics.value).toHaveLength(0)
    expect(hasLyrics.value).toBe(true)
    expect(plainTextLyrics.value).toBe('This is a normal text\nwithout any LRC format')
    expect(isLrc.value).toBe(false)
  })

  it('handles LRC format', () => {
    const song = ref(h.factory('song', {
      lyrics: '[00:05.00]First line\n[00:12.00]Second line\n[00:21.00]Third line',
    }))

    const { lrcLyrics, hasLyrics, plainTextLyrics, isLrc } = useLyrics(song)

    expect(lrcLyrics.value).toEqual([
      { text: 'First line', time: 5 },
      { text: 'Second line', time: 12 },
      { text: 'Third line', time: 21 },
    ])

    expect(hasLyrics.value).toBe(true)
    expect(plainTextLyrics.value).toBe('First line\nSecond line\nThird line')
    expect(isLrc.value).toBe(true)
  })

  it('handles empty lyrics', () => {
    const song = ref(h.factory('song', {
      lyrics: '',
    }))

    const { lrcLyrics, hasLyrics, plainTextLyrics, isLrc } = useLyrics(song)

    expect(lrcLyrics.value).toHaveLength(0)
    expect(hasLyrics.value).toBe(false)
    expect(plainTextLyrics.value).toBe('')
    expect(isLrc.value).toBe(false)
  })

  it('assigns 00:00.00 timestamp first line if it has no timestamp', () => {
    const song = ref(h.factory('song', {
      lyrics: 'First line without timestamp\n[00:12.00]Second line\n[00:21.00]Third line',
    }))

    const { lrcLyrics } = useLyrics(song)
    expect(lrcLyrics.value[0].text).toBe('First line without timestamp')
    expect(lrcLyrics.value[0].time).toBe(0)
  })

  it('assigns previous timestamp to last line if it has no timestamp', () => {
    const song = ref(h.factory('song', {
      lyrics: '[00:12.00]First line\n[00:21.00]Second line\nLast line without timestamp',
    }))

    const { lrcLyrics } = useLyrics(song)
    expect(lrcLyrics.value[2].text).toBe('Last line without timestamp')
    expect(lrcLyrics.value[2].time).toBe(21)
  })

  it('assigns an average timestamp to a line if it has no timestamp', () => {
    const song = ref(h.factory('song', {
      lyrics: '[00:12.00]First line\nSecond line without timestamp\n[00:16.00]Last line',
    }))

    const { lrcLyrics } = useLyrics(song)
    expect(lrcLyrics.value[1].text).toBe('Second line without timestamp')
    expect(lrcLyrics.value[1].time).toBe(14)
  })
})
