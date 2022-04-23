import Home from '@/components/screens/HomeScreen.vue'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { mock } from '@/__tests__/__helpers__'
import { mount } from '@/__tests__/adapter'

describe('components/screens/HomeScreen', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('refreshes when a new song is played', async () => {
    const wrapper = mount(Home)

    await wrapper.vm.$nextTick()
    // @ts-ignore
    const m = mock(wrapper.vm, 'refreshDashboard')
    eventBus.emit('SONG_STARTED', factory('song'))
    expect(m).toHaveBeenCalled()
  })
})
