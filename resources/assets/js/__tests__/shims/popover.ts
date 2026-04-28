/**
 * Minimal popover-API shim for JSDOM, which doesn't yet implement
 * the native HTML popover attribute or its `showPopover` /
 * `hidePopover` / `togglePopover` methods. Real browsers ship these
 * natively (Baseline 2024) and the guards below self-deactivate
 * once JSDOM catches up.
 *
 * The shim covers the surface our unit tests need: open/close state
 * transitions, the `popover` attribute, and `ToggleEvent` dispatch
 * so reactivity tied to the `toggle` event works. It does NOT
 * replicate browser light-dismiss (Esc, click-outside) — those are
 * verified manually in a real browser.
 */

if (typeof globalThis.ToggleEvent === 'undefined') {
  class ToggleEventShim extends Event {
    oldState: string
    newState: string

    constructor(type: string, init: EventInit & { oldState?: string; newState?: string } = {}) {
      super(type, init)
      this.oldState = init.oldState ?? 'closed'
      this.newState = init.newState ?? 'closed'
    }
  }

  ;(globalThis as unknown as { ToggleEvent: typeof ToggleEventShim }).ToggleEvent = ToggleEventShim
}

if (!('popover' in HTMLElement.prototype)) {
  const popoverOpen = new WeakMap<HTMLElement, boolean>()

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
    if (popoverOpen.get(this)) {
      return
    }

    this.dispatchEvent(new ToggleEvent('beforetoggle', { oldState: 'closed', newState: 'open' }))
    popoverOpen.set(this, true)
    this.dispatchEvent(new ToggleEvent('toggle', { oldState: 'closed', newState: 'open' }))
  }

  HTMLElement.prototype.hidePopover = function (this: HTMLElement) {
    if (!popoverOpen.get(this)) {
      return
    }

    this.dispatchEvent(new ToggleEvent('beforetoggle', { oldState: 'open', newState: 'closed' }))
    popoverOpen.set(this, false)
    this.dispatchEvent(new ToggleEvent('toggle', { oldState: 'open', newState: 'closed' }))
  }

  HTMLElement.prototype.togglePopover = function (this: HTMLElement) {
    popoverOpen.get(this) ? this.hidePopover() : this.showPopover()
  }
}
