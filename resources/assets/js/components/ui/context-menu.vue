<template>
  <nav
    class="menu"
    :class="extraClass"
    :style="{ top: `${top}px`, left: `${left}px` }"
    @contextmenu.prevent
    tabindex="0"
    v-koel-focus
    v-koel-clickaway="close"
    @keydown.esc="close"
    v-if="shown"
  >
    <ul>
      <slot>Menu items go here.</slot>
    </ul>
  </nav>
</template>

<script lang="ts">
import Vue from 'vue'

export default Vue.extend({
  props: {
    extraClass: {
      required: false,
      type: String
    }
  },

  data: () => ({
    shown: false,
    top: 0,
    left: 0
  }),

  methods: {
    async open (top = 0, left = 0): Promise<void> {
      this.top = top
      this.left = left
      this.shown = true

      await this.$nextTick()

      try {
        await this.preventOffScreen(this.$el)
        this.initSubmenus()
      } catch (e) {
        // in a non-browser environment (e.g., unit testing), these two functions are broken due to calls to
        // getBoundingClientRect() and querySelectorAll
      }
    },

    close (): void {
      this.shown = false
    },

    async preventOffScreen (element: HTMLElement, isSubmenu = false): Promise<void> {
      const { bottom, right } = element.getBoundingClientRect()

      if (bottom > window.innerHeight) {
        element.style.top = 'auto'
        element.style.bottom = '0'
      } else {
        element.style.bottom = 'auto'
      }

      if (right > window.innerWidth) {
        element.style.right = isSubmenu ? `${this.$el.getBoundingClientRect().width}px` : '0'
        element.style.left = 'auto'
      } else {
        element.style.right = 'auto'
      }
    },

    initSubmenus (): void {
      Array.from(this.$el.querySelectorAll('.has-sub') as NodeListOf<HTMLElement>).forEach((item): void => {
        const submenu = item.querySelector<HTMLElement>('.submenu')

        if (!submenu) {
          return
        }

        item.addEventListener('mouseenter', (): void => {
          submenu.style.display = 'block'
          this.preventOffScreen(submenu, true)
        })

        item.addEventListener('mouseleave', (): void => {
          submenu.style.top = '0'
          submenu.style.bottom = 'auto'
          submenu.style.display = 'none'
        })
      })
    }
  }
})
</script>

<style lang="scss" scoped>
.menu {
  @include context-menu();
  position: fixed;

  li {
    position: relative;
    padding: 4px 12px;
    cursor: default;
    white-space: nowrap;

    &:hover {
      background: var(--color-highlight);
      color: var(--color-text-primary);
    }

    &.separator {
      pointer-events: none;
      padding: 1px 0;
      border-bottom: 1px solid rgba(255, 255, 255, .1);
    }

    &.has-sub {
      padding-right: 24px;

      &:after {
        position: absolute;
        right: 12px;
        top: 4px;
        content: "‎▶";
        font-size: .9rem;
        width: 16px;
        text-align: right;
      }
    }
  }

  .submenu {
    position: absolute;
    display: none;
    left: 100%;
    top: 0;
  }
}
</style>
