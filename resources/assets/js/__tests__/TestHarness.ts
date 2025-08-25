import isMobile from 'ismobilejs'
import { isObject, mergeWith } from 'lodash'
import type { EventType, RenderOptions } from '@testing-library/vue'
import { cleanup, createEvent, fireEvent, render } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import type { UserEvent } from '@testing-library/user-event/dist/types/setup/setup'
import { afterEach, beforeEach, vi } from 'vitest'
import { defineComponent, nextTick } from 'vue'
import factory from '@/__tests__/factory'
import { DialogBoxStub, MessageToasterStub, OverlayStub } from '@/__tests__/stubs'
import { commonStore } from '@/stores/commonStore'
import { userStore } from '@/stores/userStore'
import { http } from '@/services/http'
import { DialogBoxKey, MessageToasterKey, OverlayKey, RouterKey } from '@/symbols'
import Router from '@/router'
import { preferenceStore } from '@/stores/preferenceStore'
import { noop } from '@/utils/helpers'

// A deep-merge function that
// - supports symbols as keys (_.merge doesn't)
// - supports Vue's Ref type without losing reactivity (deepmerge doesn't)
// Credit: https://stackoverflow.com/a/60598589/794641
const deepMerge = (first: object, second: object) => {
  return mergeWith(first, second, (a, b) => {
    if (!isObject(b)) {
      return b
    }

    // @ts-ignore
    return Array.isArray(a) ? [...a, ...b] : { ...a, ...b }
  })
}

const setPropIfNotExists = (obj: object | null, prop: any, value: any) => {
  if (!obj) {
    return
  }

  if (!Object.prototype.hasOwnProperty.call(obj, prop)) {
    obj[prop] = value
  }
}

class TestHarness {
  public router: Router
  public user: UserEvent
  private backupMethods = new Map()

  public constructor () {
    this.router = new Router()
    this.user = userEvent.setup({ delay: null }) // @see https://github.com/testing-library/user-event/issues/833

    this.setReadOnlyProperty(navigator, 'clipboard', {
      writeText: vi.fn(),
    })

    this.beforeEach()
    this.afterEach()
  }

  public beforeEach (cb?: Closure) {
    beforeEach(() => {
      this.mock(http, 'request').mockResolvedValue({}) // prevent actual HTTP requests from being made
      preferenceStore.init()

      commonStore.state.song_length = 10
      commonStore.state.allows_download = true
      commonStore.state.uses_i_tunes = true
      commonStore.state.supports_batch_downloading = true
      commonStore.state.supports_transcoding = true
      cb?.()
    })
  }

  public afterEach (cb?: Closure) {
    afterEach(() => {
      document.body.innerHTML = ''
      isMobile.any = false
      commonStore.state.song_length = 10
      cleanup()
      this.restoreAllMocks()
      this.disablePlusEdition()
      this.disableDemoMode()
      cb?.()
    })
  }

  public readonly auth = this.be

  public be (user?: User) {
    userStore.state.current = user || factory('user')
    return this
  }

  public beAdmin () {
    return this.be(factory.states('admin')('user'))
  }

  public mock<T, M extends MethodOf<Required<T>>> (obj: T, methodName: M, implementation?: any) {
    // check if the method is already mocked, and if so, use it instead of creating a new mock
    for (const [key, _] of this.backupMethods.entries()) {
      if (key[0] !== obj || key[1] !== methodName) {
        continue
      }

      const existingMock = obj[methodName] as unknown as ReturnType<typeof vi.fn>

      if (implementation !== undefined) {
        existingMock.mockImplementation(implementation instanceof Function ? implementation : () => implementation)
      }

      return existingMock
    }

    const mock = vi.fn()

    if (implementation !== undefined) {
      mock.mockImplementation(implementation instanceof Function ? implementation : () => implementation)
    }

    this.backupMethods.set([obj, methodName], obj[methodName])

    // @ts-ignore
    obj[methodName] = mock

    return mock
  }

  public restoreAllMocks () {
    this.backupMethods.forEach((fn, [obj, methodName]) => (obj[methodName] = fn))
    this.backupMethods.clear()
    return this
  }

  public render (component: any, options: RenderOptions = {}) {
    return render(component, deepMerge({
      global: {
        directives: {
          'koel-focus': {},
          'koel-tooltip': {},
          'koel-hide-broken-icon': {},
          'koel-overflow-fade': {},
          'koel-new-tab': {},
        },
        components: {
          Icon: this.stub('Icon'),
        },
      },
    }, this.supplyRequiredProvides(options)))
  }

  public enablePlusEdition () {
    commonStore.state.koel_plus = {
      active: true,
      short_key: '****-XXXX',
      customer_name: 'John Doe',
      customer_email: 'Koel Plus',
      product_id: 'koel-plus',
    }

    return this
  }

  public disablePlusEdition () {
    commonStore.state.koel_plus = {
      active: false,
      short_key: '',
      customer_name: '',
      customer_email: '',
      product_id: '',
    }

    return this
  }

  public enableDemoMode () {
    window.IS_DEMO = true
    return this
  }

  public disableDemoMode () {
    window.IS_DEMO = false
    return this
  }

  public stub (testId = 'stub') {
    return defineComponent({
      template: `<br data-testid="${testId}"/>`,
    })
  }

  public async tick (count = 1) {
    for (let i = 0; i < count; ++i) {
      await nextTick()
    }
  }

  public setReadOnlyProperty<T> (obj: T, prop: keyof T, value: any) {
    return Object.defineProperties(obj, {
      [prop]: {
        value,
        configurable: true,
      },
    })
  }

  public async type (element: HTMLElement, value: string) {
    await this.user.clear(element)
    await this.user.type(element, value)
  }

  public async trigger (element: HTMLElement, key: EventType | string, options: object = {}) {
    await fireEvent(element, createEvent[key](element, options))
  }

  private supplyRequiredProvides (options: RenderOptions) {
    options.global = options.global || {}
    options.global.provide = options.global.provide || {}

    setPropIfNotExists(options.global.provide, DialogBoxKey, DialogBoxStub)
    setPropIfNotExists(options.global.provide, MessageToasterKey, MessageToasterStub)
    setPropIfNotExists(options.global.provide, OverlayKey, OverlayStub)
    setPropIfNotExists(options.global.provide, RouterKey, this.router)

    return options
  }

  public createAudioPlayer () {
    if (document.querySelector('.plyr')) {
      return
    }

    document.body.innerHTML = '<div class="plyr"><audio crossorigin="anonymous" controls/></div>'

    window.AudioContext = vi.fn().mockImplementation(() => ({
      createMediaElementSource: vi.fn(noop),
    }))
  }

  public readonly factory = factory
}

export function createHarness (overrides?: { beforeEach?: () => void, afterEach?: () => void }) {
  const h = new (class extends TestHarness {
  })

  if (overrides?.beforeEach) {
    h.beforeEach(overrides.beforeEach)
  }
  if (overrides?.afterEach) {
    h.afterEach(overrides.afterEach)
  }

  return h
}
