import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { uploadService } from '@/services/uploadService'
import Component from './UnfinishedUploadsNotice.vue'

describe('unfinishedUploadsNotice.vue', () => {
  const h = createHarness()

  it('lists the unfinished files and forgets them once dismissed', async () => {
    const forgetMock = h.mock(uploadService, 'forgetUnfinishedUploads')
    h.render(Component, { props: { names: ['one.mp3', 'two.mp3'] } })

    expect(screen.getAllByRole('listitem')).toHaveLength(2)

    await h.user.click(screen.getByTestId('dismiss-unfinished-uploads'))

    expect(forgetMock).toHaveBeenCalled()
  })
})
