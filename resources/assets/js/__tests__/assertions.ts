import type { Mock } from 'vitest'
import { expect } from 'vitest'
import type { Component } from 'vue'

export const assertOpenContextMenu = async (
  openContextMenuMock: Mock,
  menu: Component,
  props: Record<string, any> = {},
) => {
  const firstArg = openContextMenuMock.mock.calls[0][0]
  const secondArg = openContextMenuMock.mock.calls[0][1]
  const thirdArg = openContextMenuMock.mock.calls[0][2]

  if (typeof firstArg.__asyncLoader === 'function') {
    const actualMenu = await firstArg.__asyncLoader()
    expect(actualMenu).toBe(menu)
  } else {
    expect(firstArg).toBe(menu)
  }

  expect(secondArg).toBeInstanceOf(MouseEvent)
  expect(thirdArg).toEqual(props)
}
