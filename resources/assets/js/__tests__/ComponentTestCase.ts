import deepmerge from 'deepmerge'
import isMobile from 'ismobilejs'
import { cleanup, render, RenderOptions } from '@testing-library/vue'
import { afterEach, beforeEach } from 'vitest'
import { mockHelper } from '@/__tests__/__helpers__'
import { noop } from '@/utils'
import { clickaway, droppable, focus } from '@/directives'
import { defineComponent, nextTick } from 'vue'
import { commonStore } from '@/stores'

declare type Methods<T> = { [K in keyof T]: T[K] extends Closure ? K : never; }[keyof T] & (string | symbol);

export default abstract class ComponentTestCase {
  public constructor () {
    this.beforeEach()
    this.afterEach()
    this.test()
  }

  protected mock<T, M extends Methods<Required<T>>> (obj: T, methodName: M, implementation: any = noop) {
    return mockHelper.mock(obj, methodName, implementation)
  }

  protected beforeEach (cb?: Closure) {
    beforeEach(() => {
      commonStore.state.allowDownload = true
      commonStore.state.useiTunes = true
      cb && cb()
    })
  }

  protected afterEach (cb?: Closure) {
    afterEach(() => {
      cleanup()
      mockHelper.restoreAllMocks()
      isMobile.any = false
      cb && cb()
    })
  }

  protected abstract test ()

  protected render (component: any, options: RenderOptions = {}) {
    return render(component, deepmerge({
      global: {
        directives: {
          'koel-clickaway': clickaway,
          'koel-focus': focus,
          'koel-droppable': droppable
        }
      }
    }, options))
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
}
