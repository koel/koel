import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { ModalContextKey } from '@/symbols'
import Component from './PlaylistCollaborationModal.vue'

describe('playlistCollaborationModal.vue', () => {
  const h = createHarness()

  it('renders the modal', async () => {
    const { html } = h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ playlist: h.factory('playlist') }),
        },
        stubs: {
          InviteCollaborators: h.stub('InviteCollaborators'),
          CollaboratorList: h.stub('CollaboratorList'),
        },
      },
    })

    expect(html()).toMatchSnapshot()
  })
})
