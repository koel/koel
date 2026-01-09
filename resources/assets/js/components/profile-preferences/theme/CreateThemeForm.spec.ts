import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { fireEvent, screen, waitFor } from '@testing-library/vue'
import { themeStore } from '@/stores/themeStore'
import Component from './CreateThemeForm.vue'

describe('createThemeForm.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    const toggleCssClassMock = vi.fn()

    const rendered = h.render(Component, {
      props: {
        toggleCssClass: toggleCssClassMock,
      },
      global: {
        stubs: {
          ColorPicker: h.stub('color-picker', true),
        },
      },
    })

    return {
      ...rendered,
      toggleCssClassMock,
    }
  }

  it('submits', async () => {
    const createdTheme = h.factory('theme')
    const storeMock = h.mock(themeStore, 'store').mockResolvedValueOnce(createdTheme)
    const setThemeMock = h.mock(themeStore, 'setTheme')

    renderComponent()

    await h.type(screen.getByPlaceholderText(/my.*theme/i), 'One Theme to Rule Them All')
    await fireEvent.update(screen.getByRole('textbox', { name: /foreground/i }), '#ff0000')
    await fireEvent.update(screen.getByRole('textbox', { name: /background/i }), '#0000ff')
    await fireEvent.update(screen.getByRole('textbox', { name: /highlight/i }), '#00ff00')

    await h.user.upload(
      screen.getByLabelText('Select a file…'),
      new File(['bytes'], 'wallpaper.png', { type: 'image/png' }),
    )

    await h.user.selectOptions(screen.getByRole('combobox'), 'sans-serif')
    await h.type(screen.getByRole('spinbutton', { name: /font size/i }), '18')
    await h.user.click(screen.getByRole('button', { name: /save/i }))

    await waitFor(() => {
      expect(storeMock).toHaveBeenCalledWith({
        name: 'One Theme to Rule Them All',
        fg_color: '#ff0000',
        bg_color: '#0000ff',
        highlight_color: '#00ff00',
        bg_image: 'data:image/png;base64,Ynl0ZXM=',
        font_family: 'sans-serif',
        font_size: 18,
      })

      expect(setThemeMock).toHaveBeenCalledWith(createdTheme)
    })
  })

  it('previews the theme', async () => {
    const { toggleCssClassMock } = renderComponent()
    expect(screen.queryByRole('button', { name: /exit preview/i })).toBeNull()

    await h.user.click(screen.getByRole('button', { name: /preview/i }))
    expect(toggleCssClassMock).toHaveBeenCalledWith('backdrop:bg-transparent', 'bg-transparent', 'cursor-not-allowed')
    expect(screen.getByTestId('create-theme-form').classList.contains('previewing')).toBe(true)

    await h.user.click(screen.getByRole('button', { name: /exit preview/i }))
    expect(toggleCssClassMock).toHaveBeenCalledWith('backdrop:bg-transparent', 'bg-transparent', 'cursor-not-allowed')
    expect(screen.queryByRole('button', { name: /exit preview/i })).toBeNull()
    expect(screen.getByTestId('create-theme-form').classList.contains('previewing')).toBe(false)
  })
})
