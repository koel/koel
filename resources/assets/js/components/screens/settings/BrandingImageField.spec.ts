import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen, waitFor } from '@testing-library/vue'
import { defineComponent, ref } from 'vue'
import Component from './BrandingImageField.vue'

describe('brandImageField.vue', () => {
  const h = createHarness()

  const renderComponent = (currentValue: string | null = null) => {
    const model = ref(currentValue)

    const parentComponent = defineComponent({
      components: {
        Component,
      },
      setup () {
        return { model }
      },
      template: `
        <Component default="default-image.jpg" name="field" v-model="model" />`,
    })

    const rendered = h.render(parentComponent)

    return {
      ...rendered,
      currentValue,
      model,
    }
  }

  it('emits the input event when a file is selected', async () => {
    const { model } = renderComponent()

    await h.user.upload(
      screen.getByLabelText('Select an image'),
      new File(['bytes'], 'cover.png', { type: 'image/png' }),
    )

    await waitFor(() => expect(model.value).toBe('data:image/png;base64,Ynl0ZXM='))
  })

  it('resets the image to the default value', async () => {
    const { model } = renderComponent('custom.jpg')
    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    await waitFor(() => expect(model.value).toBe('default-image.jpg'))
  })

  it('picking an image and clicking Remove should reset the image to the custom value', async () => {
    const { model } = renderComponent('custom.jpg')

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))
    await waitFor(() => expect(model.value).toBe('default-image.jpg'))

    await h.user.upload(
      screen.getByLabelText('Select an image'),
      new File(['bytes'], 'cover.png', { type: 'image/png' }),
    )

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'Remove' }))
      await waitFor(() => expect(model.value).toBe('custom.jpg'))
    })
  })
})
