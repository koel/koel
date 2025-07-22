/**
 * Set target="_blank" on all anchor tags within an element.
 */

import type { Directive } from 'vue'

const setTargetBlank = (el: HTMLElement) => Array.from(el.getElementsByTagName('a'))
  .forEach(a => a.setAttribute('target', '_blank'))

export const newTab: Directive = {
  mounted: (el: HTMLElement) => setTargetBlank(el),
  updated: (el: HTMLElement) => setTargetBlank(el),
}
