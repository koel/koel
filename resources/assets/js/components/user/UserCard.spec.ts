import Router from '@/router'
import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils/eventBus'
import { userStore } from '@/stores/userStore'
import { DialogBoxStub } from '@/__tests__/stubs'
import { invitationService } from '@/services/invitationService'
import Component from './UserCard.vue'

describe('userCard.vue', () => {
  const h = createHarness()

  const renderComponent = (user: User) => {
    return h.render(Component, {
      props: {
        user,
      },
    })
  }

  it('has different behaviors for current user', () => {
    const user = h.factory('user')
    h.be(user)
    renderComponent(user)

    screen.getByTitle('This is you!')
    screen.getByText('Your Profile')
  })

  it('edits user', async () => {
    const user = h.factory('user')
    const emitMock = h.mock(eventBus, 'emit')
    renderComponent(user)

    await h.user.click(screen.getByRole('button', { name: 'Edit' }))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_USER_FORM', user)
  })

  it('redirects to Profile screen if edit current user', async () => {
    const mock = h.mock(Router, 'go')
    const user = h.factory('user')
    h.be(user)
    renderComponent(user)

    await h.user.click(screen.getByRole('button', { name: 'Your Profile' }))

    expect(mock).toHaveBeenCalledWith('/#/profile')
  })

  it('deletes user if confirmed', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(true)
    const user = h.factory('user')
    h.beAdmin()
    renderComponent(user)
    const destroyMock = h.mock(userStore, 'destroy')

    await h.user.click(screen.getByRole('button', { name: 'Delete' }))

    expect(destroyMock).toHaveBeenCalledWith(user)
  })

  it('does not delete user if not confirmed', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(false)
    const user = h.factory('user')
    h.beAdmin()
    renderComponent(user)
    const destroyMock = h.mock(userStore, 'destroy')

    await h.user.click(screen.getByRole('button', { name: 'Delete' }))

    expect(destroyMock).not.toHaveBeenCalled()
  })

  it('revokes invite for prospects', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(true)
    const prospect = factory.states('prospect')('user')
    h.beAdmin()
    renderComponent(prospect)
    const revokeMock = h.mock(invitationService, 'revoke')

    await h.user.click(screen.getByRole('button', { name: 'Revoke' }))

    expect(revokeMock).toHaveBeenCalledWith(prospect)
  })

  it('does not revoke invite for prospects if not confirmed', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(false)
    const prospect = factory.states('prospect')('user')
    h.beAdmin()
    renderComponent(prospect)
    const revokeMock = h.mock(invitationService, 'revoke')

    await h.user.click(screen.getByRole('button', { name: 'Revoke' }))

    expect(revokeMock).not.toHaveBeenCalled()
  })
})
