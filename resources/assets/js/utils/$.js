/**
 * A utility that aims to replace jQuery for the most basic DOM methods.
 * @type {Object}
 */
export const $ = {
  is (el, selector) {
    return (el.matches ||
      el.matchesSelector ||
      el.msMatchesSelector ||
      el.mozMatchesSelector ||
      el.webkitMatchesSelector ||
      el.oMatchesSelector).call(el, selector)
  },

  addClass (el, className) {
    if (!el) {
      return
    }

    if (el.classList) {
      el.classList.add(className)
    } else {
      el.className += ` ${className}`
    }
  },

  removeClass (el, className) {
    if (!el) {
      return
    }

    if (el.classList) {
      el.classList.remove(className)
    } else {
      el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ')
    }
  },

  scrollTo (el, to, duration, cb = null) {
    if (duration <= 0 || !el) {
      return
    }
    const difference = to - el.scrollTop
    const perTick = difference / duration * 10
    window.setTimeout(() => {
      el.scrollTop = el.scrollTop + perTick
      if (el.scrollTop === to) {
        cb && cb()
        return
      }
      this.scrollTo(el, to, duration - 10)
    }, 10)
  }
}
