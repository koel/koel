import { describe, expect, it } from 'vite-plus/test'
import { isNearScrollEnd } from './infiniteScroll'

describe('infiniteScroll', () => {
  const createScroller = (scrollTop: number) =>
    ({ scrollHeight: 5000, clientHeight: 1000, scrollTop }) as unknown as HTMLElement

  it('is near the end with two screens of content left', () => {
    expect(isNearScrollEnd(createScroller(2000))).toBe(true)
    expect(isNearScrollEnd(createScroller(4000))).toBe(true)
  })

  it('is not near the end with more than two screens of content left', () => {
    expect(isNearScrollEnd(createScroller(1999))).toBe(false)
  })
})
