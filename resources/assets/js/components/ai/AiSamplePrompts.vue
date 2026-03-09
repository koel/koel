<template>
  <div class="px-5 space-y-2 transition-opacity duration-500" :style="{ opacity }">
    <button
      v-for="example in examples"
      :key="example"
      class="flex w-full items-center gap-2 text-k-fg-40 text-lg hover:text-k-fg-80 cursor-pointer text-left"
      type="button"
      @click="emit('select', example)"
    >
      <SparkleIcon class="size-4 inline"/>
      <span class="flex-1">{{ example }}</span>
    </button>
  </div>
</template>

<script lang="ts" setup>
import { SparkleIcon } from 'lucide-vue-next'
import { onBeforeUnmount, ref } from 'vue'

const emit = defineEmits<{ (e: 'select', prompt: string): void }>()

const allExamples = [
  'Play the album Abbey Road',
  'Play my favorite songs',
  'Play my most played tracks',
  'Replay what I listened to recently',
  'Play some jazz',
  "I'm in the mood for some heavy metal",
  'Tell me about Radiohead',
  "Who's Queen?",
  'Tell me about the album Dark Side of the Moon',
  'Create a smart playlist with Dance songs from the 90s',
  'Stream some classical music',
  'Play Pink Floyd',
  'I added some songs recently. Play them.',
  'Play the song that goes "Is this the real life, is this just fantasy"',
  'What song is playing right now?',
  'Play songs similar to this',
]

const DURATION = 500
const COUNT = 3

const pickRandom = () => [...allExamples].sort(() => Math.random() - 0.5).slice(0, COUNT)

const examples = ref(pickRandom())
const opacity = ref(1)

const sleep = (ms: number) => new Promise(resolve => setTimeout(resolve, ms))

const shuffle = async () => {
  opacity.value = 0
  await sleep(DURATION)
  examples.value = pickRandom()
  opacity.value = 1
}

const interval = setInterval(shuffle, 8_000)
onBeforeUnmount(() => clearInterval(interval))
</script>
