import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { userStore } from '@/stores/userStore'
import Component from './EditableProfileAvatar.vue'

describe('editableProfileAvatar.vue', () => {
  const h = createHarness()

  const renderComponent = (avatar?: string | null) => {
    return h.render(Component, {
      props: {
        name: 'John Doe',
        avatar,
      },
    })
  }

  it('previews the current avatar when unchanged', () => {
    userStore.state.current.avatar = 'https://example.com/current.png'

    renderComponent()

    expect(screen.getByRole('img').getAttribute('src')).toBe('https://example.com/current.png')
    screen.getByTitle('Pick a new avatar')
    screen.getByTitle('Remove avatar')
  })

  it('previews the new avatar and allows resetting it', () => {
    const { emitted } = renderComponent('data:image/png;base64,Ynl0ZXM=')

    expect(screen.getByRole('img').getAttribute('src')).toBe('data:image/png;base64,Ynl0ZXM=')

    screen.getByTitle('Reset avatar').click()

    expect(emitted()['update:avatar']).toEqual([[undefined]])
  })

  it('removes the avatar', async () => {
    const { emitted } = renderComponent()

    await h.user.click(screen.getByTitle('Remove avatar'))

    await waitFor(() => expect(emitted()['update:avatar']).toEqual([[null]]))
  })
})
