import isMobile from 'ismobilejs'
import { isObject, mergeWith } from 'lodash'
import { cleanup, render, RenderOptions } from '@testing-library/vue'
import { afterEach, beforeEach, vi } from 'vitest'
import { defineComponent, nextTick } from 'vue'
import { commonStore, userStore } from '@/stores'
import { http } from '@/services'
import factory from '@/__tests__/factory'
import { DialogBoxKey, MessageToasterKey, OverlayKey, RouterKey } from '@/symbols'
import { DialogBoxStub, MessageToasterStub, OverlayStub } from '@/__tests__/stubs'
import { routes } from '@/config'
import Router from '@/router'
import userEvent from '@testing-library/user-event'
import { UserEvent } from '@testing-library/user-event/dist/types/setup/setup'

// A deep-merge function that
// - supports symbols as keys (_.merge doesn't)
// - supports Vue's Ref type without losing reactivity (deepmerge doesn't)
// Credit: https://stackoverflow.com/a/60598589/794641
const deepMerge = (first: object, second: object) => {
  return mergeWith(first, second, (a, b) => {
    if (!isObject(b)) return b

    // @ts-ignore
    return Array.isArray(a) ? [...a, ...b] : { ...a, ...b }
  })
}

export default abstract class UnitTestCase {
  private backupMethods = new Map()
  protected router: Router
  protected user: UserEvent

  public constructor () {
    this.router = new Router(routes)
    this.mock(http, 'request') // prevent actual HTTP requests from being made
    this.user = userEvent.setup({ delay: null }) // @see https://github.com/testing-library/user-event/issues/833

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
          'koel-clickaway': {},
          'koel-focus': {},
          'koel-tooltip': {},
          'koel-hide-broken-icon': {},
          'koel-overflow-fade': {}
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

    // @ts-ignore
    if (!options.global.provide?.hasOwnProperty(DialogBoxKey)) {
      // @ts-ignore
      options.global.provide[DialogBoxKey] = DialogBoxStub
    }

    // @ts-ignore
    if (!options.global.provide?.hasOwnProperty(MessageToasterKey)) {
      // @ts-ignore
      options.global.provide[MessageToasterKey] = MessageToasterStub
    }

    // @ts-ignore
    if (!options.global.provide.hasOwnProperty(OverlayKey)) {
      // @ts-ignore
      options.global.provide[OverlayKey] = OverlayStub
    }

    // @ts-ignore
    if (!options.global.provide.hasOwnProperty(RouterKey)) {
      // @ts-ignore
      options.global.provide[RouterKey] = this.router
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

  protected async type (element: HTMLElement, value: string) {
    await this.user.clear(element)
    await this.user.type(element, value)
  }

  protected abstract test ()
}
