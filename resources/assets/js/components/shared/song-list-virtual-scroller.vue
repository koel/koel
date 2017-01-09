<template>
  <div class="virtual-scroller" @scroll="updateVisibleItems">
    <div class="item-container" :style="itemContainerStyle">
      <table class="items song-list-wrap">
        <component class="item"
          v-for="item in visibleItems"
          :key="item.id"
          :is="renderers[item[typeField]]"
          :item="item" />
      </table>
    </div>

    <iframe ref="resizeObserver" class="resize-observer" tabindex="-1"></iframe>
  </div>
</template>

<script>
import { debounce } from 'lodash'

export default {
  name: 'virtual-scroller',
  props: {
    items: {
      type: Array,
      required: true
    },
    renderers: {
      required: true
    },
    itemHeight: {
      type: [Number, String],
      required: true
    },
    typeField: {
      type: String,
      default: 'type'
    },
    keyField: {
      type: String,
      default: 'id'
    }
  },

  data () {
    return {
      visibleItems: [],
      itemContainerStyle: null
    }
  },

  watch: {
    items () {
      this.updateVisibleItems()
    },
  },

  methods: {
    updateVisibleItems: debounce(function () {
      const l = this.items.length
      const el = this.$el
      const scroll = {
        top: el.scrollTop,
        bottom: el.scrollTop + el.clientHeight
      }

      this._startIndex = Math.floor(scroll.top / this.itemHeight)
      this._endIndex = Math.ceil(scroll.bottom / this.itemHeight)

      let startIndex = this._startIndex - 1
      if (startIndex < 0) {
        startIndex = 0
      }

      let endIndex = this._endIndex + 2
      if (endIndex > l) {
        endIndex = l
      }

      this.visibleItems = this.items.slice(startIndex, endIndex)
      this.itemContainerStyle = {
        height: l * this.itemHeight + 'px',
        paddingTop: startIndex * this.itemHeight + 'px',
      }

      this.$forceUpdate()
    }, 100),

    scrollToItem (index) {
      this.$el.scrollTop = index * this.itemHeight
    },

    addResizeHandlers () {
      const iframe = this.$refs.resizeObserver
      const w = iframe.contentWindow
      // If the iframe is re-attached to the DOM, the resize listener is removed because the
      // content is reloaded, so make sure to install the handler after the iframe is loaded.
      iframe.addEventListener('load', this.refreshResizeHandlers)
      if (w) {
        w.addEventListener('resize', this.updateVisibleItems)
        w.addEventListener('close', this.removeResizeHandlers)
      }
    },

    removeResizeHandlers () {
      const iframe = this.$refs.resizeObserver
      const w = iframe.contentWindow
      iframe.removeEventListener('load', this.refreshResizeHandlers)
      if (w) {
        w.removeEventListener('resize', this.updateVisibleItems)
        w.removeEventListener('close', this.removeResizeHandlers)
      }
    },

    refreshResizeHandlers () {
      this.removeResizeHandlers()
      this.addResizeHandlers()
      // The iframe size might have changed while loading, which can also
      // happen if the size has been changed while detached from the DOM.
      this.updateVisibleItems()
    },
  },

  mounted () {
    this.updateVisibleItems()
    this.addResizeHandlers()
  }
}
</script>

<style scoped>
.virtual-scroller {
  overflow-y: auto;
  position: relative;
}

.item-container {
  box-sizing: border-box;
}

.resize-observer {
  position: absolute;
  top: 0;
  left: 0;
  z-index: -1;
  width: 100%;
  height: 100%;
  border: none;
  background-color: transparent;
}
</style>
