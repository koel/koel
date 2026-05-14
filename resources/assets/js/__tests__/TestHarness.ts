import isMobile from 'ismobilejs'
import type { RenderOptions } from '@testing-library/vue'
import { cleanup, createEvent, fireEvent, render } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import type { UserEvent } from '@testing-library/user-event'
import { afterEach, beforeEach, vi } from 'vite-plus/test'
import { defineComponent, nextTick, shallowRef } from 'vue'
import factory from '@/__tests__/factory'
import { DialogBoxStub, MessageToasterStub, OverlayStub } from '@/__tests__/stubs'
import { commonStore } from '@/stores/commonStore'
import { userStore } from '@/stores/userStore'
import { http } from '@/services/http'
import { ContextMenuKey, DialogBoxKey, MessageToasterKey, ModalKey, OverlayKey, RouterKey } from '@/config/symbols'
import Router from '@/router'
import { preferenceStore } from '@/stores/preferenceStore'
import { noop } from '@/utils/helpers'
import { deepMerge, setPropIfNotExists } from '@/__tests__/utils'
import { eventBus } from '@/utils/eventBus'
import { cache } from '@/services/cache'

class TestHarness {
  public router: Router
  public user: UserEvent
  private backupMethods = new Map()

  public constructor() {
    this.router = new Router()
    this.user = userEvent.setup({ delay: null }) // @see https://github.com/testing-library/user-event/issues/833

    this.setReadOnlyProperty(navigator, 'clipboard', {
      writeText: vi.fn(),
    })
  }

  public beforeEach(cb?: Closure) {
    beforeEach(() => {
      this.mock(http, 'request').mockResolvedValue({}) // prevent actual HTTP requests from being made

      commonStore.state.song_length = 10
      commonStore.state.allows_download = true
      commonStore.state.uses_i_tunes = true
      commonStore.state.supports_batch_downloading = true
      commonStore.state.supports_transcoding = true

      this.setDefaultBranding()
      cb?.()
    })
  }

  public afterEach(cb?: Closure) {
    afterEach(() => {
      document.body.innerHTML = ''
      isMobile.any = false
      commonStore.state.song_length = 10
      cache.clear()
      cleanup()
      this.restoreAllMocks()
      eventBus.removeAllListeners()
      cb?.()
    })
  }

  private setDefaultBranding() {
    window.KOEL.branding = {
      name: 'Koel',
      logo: '',
      cover: '',
    }
  }

  public readonly auth = (user?: CurrentUser) => this.actingAsUser(user)

  public actingAsUser(user?: CurrentUser) {
    userStore.state.current = user || (factory('user').state('current').make() as CurrentUser)
    preferenceStore.init(userStore.state.current.preferences)
    return this
  }

  public actingAsAdmin() {
    return this.actingAsUser(factory('user').state('admin').make() as CurrentUser)
  }

  public mock<T, M extends MethodOf<Required<T>>>(obj: T, methodName: M, implementation?: any) {
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

  public restoreAllMocks() {
    this.backupMethods.forEach((fn, [obj, methodName]) => (obj[methodName] = fn))
    this.backupMethods.clear()

    return this
  }

  public render(component: any, options: RenderOptions = {}) {
    return render(
      component,
      deepMerge(
        {
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
        },
        this.supplyRequiredProvides(options),
      ),
    )
  }

  public async withPlusEdition(cb: Closure) {
    commonStore.state.koel_plus = {
      active: true,
      short_key: '****-XXXX',
      customer_name: 'John Doe',
      customer_email: 'Koel Plus',
      product_id: 'koel-plus',
    }

    await cb()

    commonStore.state.koel_plus = {
      active: false,
      short_key: '',
      customer_name: '',
      customer_email: '',
      product_id: '',
    }

    return this
  }

  public async withCustomBranding(branding: Branding, cb: Closure) {
    // Custom branding implicitly requires Plus edition.
    return await this.withPlusEdition(async () => {
      window.KOEL.branding = branding
      await cb()
      this.setDefaultBranding()
    })
  }

  public async withDemoMode(cb: Closure) {
    window.KOEL.is_demo = true
    await cb()
    window.KOEL.is_demo = false

    return this
  }

  public stub(testId = 'stub', asModelComponent = false, defaultValue?: any) {
    if (!asModelComponent) {
      return defineComponent({
        template: `<br data-testid="${testId}"/>`,
      })
    }

    return defineComponent({
      template: `<input data-testid="${testId}" @input="$emit('update:modelValue', $event.target.value)" />`,
      emits: ['update:modelValue'],
      mounted() {
        defaultValue && this.$emit('update:modelValue', defaultValue)
      },
    })
  }

  public async tick(count = 1) {
    for (let i = 0; i < count; ++i) {
      await nextTick()
    }
  }

  public setReadOnlyProperty<T>(obj: T, prop: keyof T, value: any) {
    return Object.defineProperties(obj, {
      [prop]: {
        value,
        configurable: true,
        writable: true,
      },
    })
  }

  public async type(element: HTMLElement, value: string) {
    await this.user.clear(element)
    await this.user.type(element, value)
  }

  public async trigger(element: HTMLElement, key: string, options: object = {}) {
    await fireEvent(element, createEvent[key](element, options))
  }

  private supplyRequiredProvides(options: RenderOptions) {
    options.global = options.global || {}
    options.global.provide = options.global.provide || {}

    setPropIfNotExists(options.global.provide, DialogBoxKey, DialogBoxStub)
    setPropIfNotExists(options.global.provide, MessageToasterKey, MessageToasterStub)
    setPropIfNotExists(options.global.provide, OverlayKey, OverlayStub)
    setPropIfNotExists(options.global.provide, RouterKey, this.router)

    setPropIfNotExists(
      options.global.provide,
      ContextMenuKey,
      shallowRef({
        component: null,
        position: { top: 0, left: 0 },
      }),
    )

    setPropIfNotExists(
      options.global.provide,
      ModalKey,
      shallowRef({
        component: null,
      }),
    )

    return options
  }

  public createAudioPlayer() {
    if (document.querySelector('#audio-player')) {
      return
    }

    document.body.innerHTML = '<audio id="audio-player" crossorigin="anonymous"/>'

    window.AudioContext = vi.fn().mockImplementation(() => ({
      createMediaElementSource: vi.fn(noop),
    }))
  }

  public visit(hash: string) {
    if (!hash.startsWith('/')) {
      hash = `/${hash}`
    }

    this.router.resolve(hash)
    return this
  }

  public readonly factory = factory
}

export function createHarness(overrides?: {
  beforeEach?: () => void
  afterEach?: () => void
  authenticated?: boolean
}) {
  const h = new TestHarness()

  if (overrides?.authenticated ?? true) {
    h.actingAsUser()
  }

  h.beforeEach(overrides?.beforeEach)
  h.afterEach(overrides?.afterEach)

  return h
}
