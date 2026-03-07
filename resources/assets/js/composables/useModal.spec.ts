import { describe, expect, it, vi } from 'vitest'
import { markRaw, ref } from 'vue'

const modalOptions = ref<any>({
  component: null,
})

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  requireInjection: () => modalOptions,
}))

import { useModal } from './useModal'

describe('useModal', () => {
  it('opens a modal with a component', () => {
    const { openModal } = useModal()
    const FakeComponent = markRaw({ template: '<div />' })

    openModal(FakeComponent)

    expect(modalOptions.value.component).toBe(FakeComponent)
    expect(modalOptions.value.props).toEqual({})
  })

  it('opens a modal with props', () => {
    const { openModal } = useModal()
    const FakeComponent = markRaw({ template: '<div />' })

    ;(openModal as Function)(FakeComponent, { foo: 'bar' })

    expect(modalOptions.value.component).toBe(FakeComponent)
    expect(modalOptions.value.props).toEqual({ foo: 'bar' })
  })

  it('closes the modal', () => {
    const { openModal, closeModal } = useModal()
    const FakeComponent = markRaw({ template: '<div />' })

    openModal(FakeComponent)
    closeModal()

    expect(modalOptions.value.component).toBeNull()
  })
})
