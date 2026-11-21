<template>
  <FooterBtn :class="{ active: miniPlayerWindow }" @click.prevent="toggleMiniPlayer">
    <PictureInPicture2Icon :size="16" />
    <span class="sr-only">{{ miniPlayerWindow ? 'Close mini player' : 'Open mini player' }}</span>
  </FooterBtn>

  <Teleport v-if="miniPlayerWindow" :to="miniPlayerWindow.document.body">
    <MiniPlayer />
  </Teleport>
</template>

<script lang="ts" setup>
import { PictureInPicture2Icon } from 'lucide-vue-next'
import { onBeforeUnmount, shallowRef } from 'vue'

import FooterBtn from '@/components/layout/app-footer/FooterButton.vue'
import MiniPlayer from '@/components/mini-player/MiniPlayer.vue'

const miniPlayerWindow = shallowRef<Window | null>(null)

const copyStyles = (target: Document) => {
  document.querySelectorAll('link[rel="stylesheet"], style').forEach(node => target.head.append(node.cloneNode(true)))
}

const syncTheme = (target: Document) => {
  target.documentElement.className = document.documentElement.className
  target.documentElement.dataset.theme = document.documentElement.dataset.theme
  target.body.style.cssText = document.body.style.cssText
}

const themeObserver = new MutationObserver(() => miniPlayerWindow.value && syncTheme(miniPlayerWindow.value.document))

const openMiniPlayer = async () => {
  const pipWindow = await window.documentPictureInPicture!.requestWindow({ width: 320, height: 320 })

  copyStyles(pipWindow.document)
  syncTheme(pipWindow.document)

  themeObserver.observe(document.documentElement, { attributeFilter: ['class', 'data-theme'] })
  themeObserver.observe(document.body, { attributeFilter: ['style'] })

  pipWindow.addEventListener('pagehide', () => {
    themeObserver.disconnect()
    miniPlayerWindow.value = null
  })

  miniPlayerWindow.value = pipWindow
}

const toggleMiniPlayer = async () => {
  if (miniPlayerWindow.value) {
    miniPlayerWindow.value.close()
    return
  }

  await openMiniPlayer()
}

onBeforeUnmount(() => miniPlayerWindow.value?.close())
</script>
