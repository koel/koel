import { afterEach, beforeEach, expect, it, vi } from 'vitest'
import { cleanup, fireEvent } from '@testing-library/vue'
import { render } from '@/__tests__/__helpers__'
import { eventBus } from '@/utils'
import { nextTick } from 'vue'
import { preferenceStore } from '@/stores'
import SupportKoel from './SupportKoel.vue'

beforeEach(() => {
  cleanup()
  vi.useFakeTimers()
})

afterEach(() => {
  vi.useRealTimers()
  preferenceStore.state.supportBarNoBugging = false
})

const mountComponent = async () => {
  const result = render(SupportKoel)
  eventBus.emit('KOEL_READY')

  vi.advanceTimersByTime(30 * 60 * 1000)
  await nextTick()

  return result
}

it('shows after a delay', async () => {
  const { html } = await mountComponent()

  expect(html()).toMatchSnapshot()
})

it('does not show if user so demands', async () => {
  preferenceStore.state.supportBarNoBugging = true
  const { queryByTestId } = await mountComponent()

  expect(await queryByTestId('support-bar')).toBe(null)
})

it('hides', async () => {
  const { getByTestId, queryByTestId } = await mountComponent()

  await fireEvent.click(getByTestId('hide-support-koel'))

  expect(await queryByTestId('support-bar')).toBe(null)
})

it('hides and does not bug again', async () => {
  const { getByTestId, queryByTestId } = await mountComponent()

  await fireEvent.click(getByTestId('stop-support-koel-bugging'))

  expect(await queryByTestId('btn-stop-support-koel-bugging')).toBe(null)
  expect(preferenceStore.state.supportBarNoBugging).toBe(true)
})
