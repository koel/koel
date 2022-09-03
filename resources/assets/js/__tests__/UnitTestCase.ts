import isMobile from 'ismobilejs'
import { isObject, mergeWith } from 'lodash'
import { cleanup, render, RenderOptions } from '@testing-library/vue'
import { afterEach, beforeEach, vi } from 'vitest'
import { clickaway, focus } from '@/directives'
import { defineComponent, nextTick } from 'vue'
import { commonStore, userStore } from '@/stores'
import factory from '@/__tests__/factory'
import { DialogBoxKey, MessageToasterKey } from '@/symbols'
import { DialogBoxStub, MessageToasterStub } from '@/__tests__/stubs'

// A deep-merge function that
// - supports symbols as keys (_.merge doesn't)
// - supports Vue's Ref type without losing reactivity (deepmerge doesn't)
// Credit: https://stackoverflow.com/a/60598589/794641
const deepMerge = (first: object, second: object) => {
  return mergeWith(first, second, (a, b) => {
    if (!isObject(b)) return b

    return Array.isArray(a) ? [...a, ...b] : { ...a, ...b }
  })
}

export default abstract class UnitTestCase {
  private backupMethods = new Map()

  public constructor () {
    this.beforeEach()
    this.afterEach()
    this.test()
  }

  protected beforeEach (cb?: Closure) {
    beforeEach(() => {
      commonStore.state.allow_download = true
      commonStore.state.use_i_tunes = true
      cb && cb()
    })
  }

  protected afterEach (cb?: Closure) {
    afterEach(() => {
      cleanup()
      this.restoreAllMocks()
      isMobile.any = false
      cb && cb()
    })
  }

  protected actingAs (user?: User) {
    userStore.state.current = user || factory<User>('user')
    return this
  }

  protected actingAsAdmin () {
    return this.actingAs(factory.states('admin')<User>('user'))
  }

  protected mock<T, M extends MethodOf<Required<T>>> (obj: T, methodName: M, implementation?: any) {
    const mock = vi.fn()

    if (implementation !== undefined) {
      mock.mockImplementation(implementation instanceof Function ? implementation : () => implementation)
    }

    this.backupMethods.set([obj, methodName], obj[methodName])

    // @ts-ignore
    obj[methodName] = mock

    return mock
  }

  protected restoreAllMocks () {
    this.backupMethods.forEach((fn, [obj, methodName]) => (obj[methodName] = fn))
    this.backupMethods = new Map()
  }

  protected render (component: any, options: RenderOptions = {}) {
    return render(component, deepMerge({
      global: {
        directives: {
          'koel-clickaway': clickaway,
          'koel-focus': focus
        },
        components: {
          icon: this.stub('icon')
        }
      }
    }, this.supplyRequiredProvides(options)))
  }

  private supplyRequiredProvides (options: RenderOptions) {
    options.global = options.global || {}
    options.global.provide = options.global.provide || {}

    if (!options.global.provide?.hasOwnProperty(DialogBoxKey)) {
      options.global.provide[DialogBoxKey] = DialogBoxStub
    }

    if (!options.global.provide?.hasOwnProperty(MessageToasterKey)) {
      options.global.provide[MessageToasterKey] = MessageToasterStub
    }

    return options
  }

  protected stub (testId = 'stub') {
    return defineComponent({
      template: `<br data-testid="${testId}"/>`
    })
  }

  protected async tick (count = 1) {
    for (let i = 0; i < count; ++i) {
      await nextTick()
    }
  }

  protected setReadOnlyProperty<T> (obj: T, prop: keyof T, value: any) {
    return Object.defineProperties(obj, {
      [prop]: {
        value,
        configurable: true
      }
    })
  }

  protected abstract test ()
}
