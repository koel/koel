import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import Component from './PlaylistCollaboratorListItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('does not show a badge when current user is not the collaborator', async () => {
      const currentUser = factory<User>('user')
      this.be(currentUser).renderComponent({
        collaborator: factory<PlaylistCollaborator>('playlist-collaborator', { id: currentUser.id + 1 }),
        removable: true,
        manageable: true,
        role: 'owner'
      })

      expect(screen.queryByTitle('This is you!')).toBeNull()
    })

    it('shows a badge when current user is the collaborator', async () => {
      const currentUser = factory<User>('user')
      this.be(currentUser).renderComponent({
        collaborator: factory<PlaylistCollaborator>('playlist-collaborator',
          {
            id: currentUser.id,
            name: currentUser.name,
            avatar: currentUser.avatar
          }
        ),
        removable: true,
        manageable: true,
        role: 'owner'
      })

      screen.getByTitle('This is you!')
    })

    it('shows the role', async () => {
      const collaborator = factory<PlaylistCollaborator>('playlist-collaborator')

      this.be().renderComponent({
        collaborator,
        removable: true,
        manageable: true,
        role: 'owner'
      })

      screen.getByText('Owner')

      this.be().renderComponent({
        collaborator,
        removable: true,
        manageable: true,
        role: 'contributor'
      })

      screen.getByText('Contributor')
    })

    it('emits the remove event when the remove button is clicked', async () => {
      const collaborator = factory<PlaylistCollaborator>('playlist-collaborator')
      const { emitted } = this.be().renderComponent({
        collaborator,
        removable: true,
        manageable: true,
        role: 'owner'
      })

      await this.user.click(screen.getByRole('button', { name: 'Remove' }))

      expect(emitted('remove')).toBeTruthy()
    })
  }

  private renderComponent (props: {
    collaborator: PlaylistCollaborator,
    removable: boolean,
    manageable: boolean,
    role: 'owner' | 'contributor'
  }) {
    return this.render(Component, {
      props,
      global: {
        stubs: {
          UserAvatar: this.stub('UserAvatar')
        }
      }
    })
  }
}
