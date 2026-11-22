<template>
  <div>
    <FooterBtn :class="{ active: miniPlayerWindow }" @click.prevent="toggleMiniPlayer">
      <PictureInPicture2Icon :size="16" />
      <span class="sr-only">{{ miniPlayerWindow ? 'Close mini player' : 'Open mini player' }}</span>
    </FooterBtn>

    <Teleport v-if="miniPlayerWindow" :to="miniPlayerWindow.document.body">
      <MiniPlayer />
    </Teleport>
  </div>
</template>

<script lang="ts" setup>
import { PictureInPicture2Icon } from 'lucide-vue-next'
import { onBeforeUnmount, shallowRef } from 'vue'
import { copyStylesheetsToDocument, copyThemeToDocument } from '@/utils/documentStyles'

import FooterBtn from '@/components/layout/app-footer/FooterButton.vue'
import MiniPlayer from '@/components/mini-player/MiniPlayer.vue'

const miniPlayerWindow = shallowRef<Window | null>(null)

const themeObserver = new MutationObserver(
  () => miniPlayerWindow.value && copyThemeToDocument(miniPlayerWindow.value.document),
)

const openMiniPlayer = async () => {
  const pipWindow = await window.documentPictureInPicture!.requestWindow({ width: 320, height: 320 })

  copyStylesheetsToDocument(pipWindow.document)
  copyThemeToDocument(pipWindow.document)

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
