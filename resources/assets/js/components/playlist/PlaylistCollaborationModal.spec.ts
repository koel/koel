import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlaylistCollaborationModal.vue'

describe('playlistCollaborationModal.vue', () => {
  const h = createHarness()

  it('renders the modal', async () => {
    const { html } = h.render(Component, {
      props: {
        playlist: h.factory('playlist'),
      },
      global: {
        stubs: {
          InviteCollaborators: h.stub('InviteCollaborators'),
          CollaboratorList: h.stub('CollaboratorList'),
        },
      },
    })

    expect(html()).toMatchSnapshot()
  })
})
