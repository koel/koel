import { ls } from '@/services'
import { preferenceStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import factory from '@/__tests__/factory'

const user = factory<User>('user', { id: 1 })

describe('stores/preference', () => {
  beforeEach(() => {
    preferenceStore.init(user)
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('sets preferences', () => {
    const m = mock(ls, 'set')
    preferenceStore.set('volume', 5)
    expect(m).toHaveBeenCalledWith('preferences_1', expect.objectContaining({ volume: 5 }))

    // test the proxy
    preferenceStore.volume = 6
    expect(m).toHaveBeenCalledWith('preferences_1', expect.objectContaining({ volume: 6 }))
  })

  it('returns preference values', () => {
    preferenceStore.set('volume', 4.2)
    expect(preferenceStore.get('volume')).toBe(4.2)

    // test the proxy
    expect(preferenceStore.volume).toBe(4.2)
  })
})
