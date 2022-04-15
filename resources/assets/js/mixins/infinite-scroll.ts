import { defineAsyncComponent, ref } from 'vue'

/**
 * Add a "infinite scroll" functionality to any component using this mixin.
 * Such a component should have a `scrolling` method bound to `scroll` event on
 * the wrapper element: @scroll="scrolling"
 */
export const useInfiniteScroll = (perPage: number = 30) => {
  const ToTopButton = defineAsyncComponent(() => import('@/components/ui/to-top-button.vue'))

  const displayedItemCount = ref(perPage)

  const displayMore = () => displayedItemCount.value += perPage

  const scrolling = (target: HTMLElement) => {
    // Here we check if the user has scrolled to the end of the wrapper (or 32px to the end).
    // If that's true, load more items.
    if (target.scrollTop + target.clientHeight >= target.scrollHeight - 32) {
      displayMore()
    }
  }

  const makeScrollable = (container: HTMLElement, totalItemCount: number) => {
    if (container.scrollHeight <= container.clientHeight && displayedItemCount.value < totalItemCount) {
      displayMore()
      // we can't use $nextTick here because it's instant and scrollHeight wouldn't have been udpated.
      window.setTimeout(() => makeScrollable(container, totalItemCount), 200)
    }
  }

  return {
    ToTopButton,
    displayedItemCount,
    scrolling,
    makeScrollable
  }
}
