<template>
  <div>
    <FooterBtn ref="button" class="text-sm tabular-nums">
      {{ formatSpeed(currentSpeed) }}
      <span class="sr-only">Playback speed</span>
    </FooterBtn>

    <Popover ref="popover" :anchor="button?.$el" placement="top" class="context-menu">
      <menu>
        <li
          v-for="speed in playbackSpeeds"
          :key="speed"
          :class="speed === currentSpeed && 'active'"
          class="cursor-pointer hover:bg-k-highlight hover:text-k-highlight-fg"
          @click="changeSpeed(speed)"
        >
          {{ formatSpeed(speed) }}
        </li>
      </menu>
    </Popover>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { playback } from '@/services/playbackManager'
import { crossfadeService } from '@/services/crossfadeService'
import { playbackSpeedService, playbackSpeeds } from '@/services/playbackSpeedService'

import FooterBtn from '@/components/layout/app-footer/FooterButton.vue'
import Popover from '@/components/ui/Popover.vue'

const props = defineProps<{ playable: Playable }>()

const button = ref<InstanceType<typeof FooterBtn>>()
const popover = ref<InstanceType<typeof Popover>>()

const formatSpeed = (speed: number) => `${Number.isInteger(speed) ? speed.toFixed(1) : speed}×`

const currentSpeed = computed(() => playbackSpeedService.getSpeed(props.playable))

const changeSpeed = (speed: number) => {
  playbackSpeedService.saveSpeed(props.playable, speed)

  const media = playback('current')?.media

  if (media) {
    playbackSpeedService.applyToMedia(media, speed)
  }

  if (crossfadeService.state) {
    playbackSpeedService.applyToMedia(crossfadeService.state.incomingAudio, speed)
  }

  popover.value?.hide()
}
</script>
