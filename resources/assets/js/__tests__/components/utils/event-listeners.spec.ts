import Component from '@/components/utils/event-listeners.vue'
import factory from '@/__tests__/factory'
import { playlistStore, userStore } from '@/stores'
import router from '@/router'
import { auth } from '@/services'
import { alerts, eventBus } from '@/utils'
import { mock } from '@/__tests__/__helpers__'
import { mount } from '@/__tests__/adapter'

describe('utils/event-listeners', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('confirms a playlist deleting if the playlist is not empty', () => {
    mount(Component)

    const confirmMock = mock(alerts, 'confirm')
    eventBus.emit('PLAYLIST_DELETE', factory('playlist', {
      name: 'Foo',
      populated: true,
      songs: factory('song', 3)
    }))

    expect(confirmMock).toHaveBeenCalledWith(`Delete the playlist "Foo"?`, expect.any(Function))
  })

  it("doesn't confirm deleting a playlist if the playlist is empty", () => {
    const playlist = factory('playlist', {
      populated: true,
      songs: []
    })

    mount(Component)
    const confirmMock = mock(alerts, 'confirm')
    const deleteMock = mock(playlistStore, 'delete')
    eventBus.emit('PLAYLIST_DELETE', playlist)

    expect(confirmMock).not.toHaveBeenCalled()
    expect(deleteMock).toHaveBeenCalledWith(playlist)
  })

  it('listens to log out event', () => {
    const wrapper = mount(Component)
    const authDestroyMock = mock(auth, 'destroy')
    const logOutMock = mock(userStore, 'logout')

    eventBus.emit('LOG_OUT')

    wrapper.vm.$nextTick(() => {
      expect(authDestroyMock).toHaveBeenCalled()
      expect(logOutMock).toHaveBeenCalled()
    })
  })

  it('listen to koel-ready event', () => {
    mount(Component)
    const initRouterMock = mock(router, 'init')
    eventBus.emit('KOEL_READY')
    expect(initRouterMock).toHaveBeenCalled()
  })
})
