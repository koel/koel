import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { ModalContextKey } from '@/symbols'
import Modal from './PlaylistCollaborationModal.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders the modal', async () => {
      const { html } = this.render(Modal, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ playlist: factory('playlist') })],
          },
          stubs: {
            InviteCollaborators: this.stub('InviteCollaborators'),
            CollaboratorList: this.stub('CollaboratorList'),
          },
        },
      })

      expect(html()).toMatchSnapshot()
    })
  }
}
