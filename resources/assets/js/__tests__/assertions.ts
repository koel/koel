import type { Mock } from 'vite-plus/test'
import { expect } from 'vite-plus/test'
import type { Component } from 'vue'

const ASYNC_LOADER_KEY = '__asyncLoader'

const resolveComponent = async (arg: any): Promise<Component> => {
  const loader = arg?.[ASYNC_LOADER_KEY]
  return typeof loader === 'function' ? await loader() : arg
}

export const assertOpenContextMenu = async (
  openContextMenuMock: Mock,
  menu: Component,
  props: Record<string, any> = {},
) => {
  const [firstArg, secondArg, thirdArg] = openContextMenuMock.mock.calls[0]

  expect(await resolveComponent(firstArg)).toBe(menu)
  expect(secondArg).toBeInstanceOf(MouseEvent)
  expect(thirdArg).toEqual(props)
}

export const assertOpenModal = async (openModalMock: Mock, modal: Component, props?: Record<string, any>) => {
  expect(openModalMock).toHaveBeenCalled()
  const [firstArg, secondArg] = openModalMock.mock.calls[0]

  expect(await resolveComponent(firstArg)).toBe(modal)

  if (props !== undefined) {
    expect(secondArg).toEqual(props)
  }
}
