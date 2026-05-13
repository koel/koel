/**
 * Minimal popover-API shim for JSDOM, which doesn't yet implement
 * the native HTML popover attribute or its `showPopover` /
 * `hidePopover` / `togglePopover` methods. Real browsers ship these
 * natively (Baseline 2024) and the guards below self-deactivate
 * once JSDOM catches up.
 *
 * The shim covers the surface our unit tests need: open/close state
 * transitions, the `popover` attribute, ToggleEvent dispatch,
 * cancelable beforetoggle on show, and InvalidStateError when
 * preconditions aren't met. It does NOT replicate browser
 * light-dismiss (Esc, click-outside) — those are verified manually.
 *


 * The trigger detects whether any of the three popover methods are
 * missing; if so, the body patches the full API consistently
 * (accessor + all three methods) using a shared WeakMap. This avoids
 * mixing partial native methods with a shim that would have desynced
 * state — partial native support is replaced wholesale rather than
 * augmented.
 */

type ToggleState = 'open' | 'closed'

if (typeof globalThis.ToggleEvent === 'undefined') {
  class ToggleEventShim extends Event {
    oldState: ToggleState
    newState: ToggleState

    constructor(type: string, init: EventInit & { oldState?: ToggleState; newState?: ToggleState } = {}) {
      super(type, init)
      this.oldState = init.oldState ?? 'closed'
      this.newState = init.newState ?? 'closed'
    }
  }

  ;(globalThis as unknown as { ToggleEvent: typeof ToggleEventShim }).ToggleEvent = ToggleEventShim
}

const popoverApiMissing =
  !('showPopover' in HTMLElement.prototype) ||
  !('hidePopover' in HTMLElement.prototype) ||
  !('togglePopover' in HTMLElement.prototype)

if (popoverApiMissing) {
  const popoverOpen = new WeakMap<HTMLElement, boolean>()

  const invalidState = (message: string) => new DOMException(message, 'InvalidStateError')

  Object.defineProperty(HTMLElement.prototype, 'popover', {
    configurable: true,
    get(this: HTMLElement) {
      return this.getAttribute('popover')
    },
    set(this: HTMLElement, value: string | null) {
      value === null ? this.removeAttribute('popover') : this.setAttribute('popover', String(value))
    },
  })

  HTMLElement.prototype.showPopover = function (this: HTMLElement) {
    if (!this.hasAttribute('popover')) {
      throw invalidState('Element does not have a popover attribute')
    }

    if (popoverOpen.get(this)) {
      throw invalidState('Popover is already showing')
    }

    const beforeEvent = new ToggleEvent('beforetoggle', {
      cancelable: true,
      oldState: 'closed',
      newState: 'open',
    })
    this.dispatchEvent(beforeEvent)

    if (beforeEvent.defaultPrevented) {
      return
    }

    popoverOpen.set(this, true)
    this.dispatchEvent(new ToggleEvent('toggle', { oldState: 'closed', newState: 'open' }))
  }

  HTMLElement.prototype.hidePopover = function (this: HTMLElement) {
    if (!this.hasAttribute('popover')) {
      throw invalidState('Element does not have a popover attribute')
    }

    if (!popoverOpen.get(this)) {
      throw invalidState('Popover is not showing')
    }

    // beforetoggle for hide is non-cancelable per the HTML spec.
    this.dispatchEvent(new ToggleEvent('beforetoggle', { oldState: 'open', newState: 'closed' }))
    popoverOpen.set(this, false)
    this.dispatchEvent(new ToggleEvent('toggle', { oldState: 'open', newState: 'closed' }))
  }

  HTMLElement.prototype.togglePopover = function (this: HTMLElement, _options?: boolean): boolean {
    popoverOpen.get(this) ? this.hidePopover() : this.showPopover()
    return Boolean(popoverOpen.get(this))
  }
}
