import { describe, expect, it, vi } from 'vite-plus/test'

const onRouteChangedMock = vi.fn()
const resolveMock = vi.fn()
const triggerNotFoundMock = vi.fn()

const currentRoute = {
  value: {
    screen: 'Home' as ScreenName,
    path: '/',
    params: { id: '42' },
  },
}

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  requireInjection: () => ({
    $currentRoute: currentRoute,
    onRouteChanged: onRouteChangedMock,
    resolve: resolveMock,
    triggerNotFound: triggerNotFoundMock,
  }),
}))

vi.mock('@/router', () => {
  const goMock = vi.fn()
  const urlMock = vi.fn((name: string) => `/#/${name}`)

  return {
    default: Object.assign(function () {}, { go: goMock, url: urlMock }),
  }
})

import { useRouter } from './useRouter'

describe('useRouter', () => {
  it('gets route param', () => {
    const { getRouteParam } = useRouter()
    expect(getRouteParam('id')).toBe('42')
  })

  it('gets current screen', () => {
    const { getCurrentScreen } = useRouter()
    expect(getCurrentScreen()).toBe('Home')
  })

  it('checks current screen', () => {
    const { isCurrentScreen } = useRouter()
    expect(isCurrentScreen('Home' as ScreenName)).toBe(true)
    expect(isCurrentScreen('Queue' as ScreenName)).toBe(false)
  })
})
