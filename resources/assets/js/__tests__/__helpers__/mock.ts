import FunctionNames = jest.FunctionPropertyNames
import { noop } from '@/utils'

export const mock = <T extends {}, M extends FunctionNames<Required<T>>>(
  object: T,
  method: M,
  implementation: any = noop
) => {
  const m = jest.spyOn(object, method)

  if (implementation instanceof Function) {
    m.mockImplementation(implementation)
  } else {
    m.mockImplementation((): any => implementation)
  }

  return m
}
