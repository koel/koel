/**
 * A utility that aims to replace jQuery for the most basic DOM methods.
 */
export const $ = {
  is: (el: Element, selector: string): boolean => {
    return el.matches(selector)
  },

  addClass: (el: Element | null, className: string): void => {
    if (el) {
      el.classList.add(className)
    }
  },

  removeClass: (el: Element | null, className: string): void => {
    if (el) {
      el.classList.remove(className)
    }
  },

  scrollTo (el: Element, to: number, duration: number, cb?: Function): void {
    if (duration <= 0 || !el) {
      return
    }

    const difference = to - el.scrollTop
    const perTick = difference / duration * 10

    window.setTimeout((): void => {
      el.scrollTop = el.scrollTop + perTick

      if (el.scrollTop === to) {
        cb && cb()
        return
      }

      this.scrollTo(el, to, duration - 10)
    }, 10)
  }
}
