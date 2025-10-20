<template>
  <article
    :title="isCurrentTheme ? `${theme.name} (current theme)` : `Set current theme to ${theme.name}`"
    class="theme h-[96px] bg-center bg-cover relative outline outline-1 outline-k-fg-10 cursor-pointer rounded-lg transition duration-300 shadow-lg"
    data-testid="theme-card"
    @contextmenu.prevent="onContextMenu"
  >
    <Icon
      v-if="isCurrentTheme"
      :icon="faBookmark"
      class="absolute z-10 -top-1 right-3 text-k-highlight"
      size="xl"
    />

    <button
      class="opacity-0 hover:opacity-100 absolute h-full rounded-lg w-full top-0 left-0 flex items-center justify-center text-lg transition-opacity bg-k-bg-70 !text-k-fg"
      type="button"
      @click="onClick"
    >
      {{ theme.name }}
    </button>
  </article>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { faBookmark } from '@fortawesome/free-solid-svg-icons'
import { themeStore } from '@/stores/themeStore'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'

const props = defineProps<{ theme: Theme }>()

const ContextMenu = defineAsyncComponent(() => import('@/components/profile-preferences/theme/ThemeContextMenu.vue'))

const { theme } = toRefs(props)

const { openContextMenu } = useContextMenu()

const isCurrentTheme = computed(() => themeStore.isCurrentTheme(theme.value))
const thumbnailColor = computed(() => theme.value.thumbnail_color)
const thumbnailImage = computed(() => theme.value.thumbnail_image ? `url(${theme.value.thumbnail_image})` : 'none')

const onClick = () => themeStore.setTheme(theme.value)

const onContextMenu = (e: MouseEvent) => openContextMenu<'THEME'>(ContextMenu, e, {
  theme: theme.value,
})
</script>

<style lang="postcss" scoped>
article {
  background-color: v-bind(thumbnailColor);
  background-image: v-bind(thumbnailImage);
}
</style>
