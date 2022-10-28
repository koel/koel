import { Directive } from 'vue'

export const hideBrokenIcon: Directive = {
  mounted: async (el: HTMLImageElement) => {
    el.addEventListener('error', () => (el.style.visibility = 'hidden'))

    // For v-bind, an empty source e.g. :src="emptySrc" will NOT be rendered
    // and the error event will not be triggered.
    // We'll work around by explicitly setting the src to an empty string, which will trigger the error.
    el.src = el.src || ''
  }
}
