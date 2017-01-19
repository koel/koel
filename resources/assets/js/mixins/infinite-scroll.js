import toTopButton from '../components/shared/to-top-button.vue'

/**
 * Add a "infinite scroll" functionality to any component using this mixin.
 * Such a component should have a `scrolling` method bound to `scroll` event on
 * the wrapper element: @scroll="scrolling"
 */
export default {
  components: { toTopButton },

  data () {
    return {
      numOfItems: 30, // Number of currently loaded and displayed items
      perPage: 30  // Number of items to be loaded per "page"
    }
  },

  methods: {
    scrolling (e) {
      // Here we check if the user has scrolled to the end of the wrapper (or 32px to the end).
      // If that's true, load more items.
      if (e.target.scrollTop + e.target.clientHeight >= e.target.scrollHeight - 32) {
        this.displayMore()
      }
    },

    /**
     * Load and display more items into the scrollable area.
     */
    displayMore () {
      this.numOfItems += this.perPage
    }
  }
}
