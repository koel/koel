<template>
  <div class="px-5 space-y-2 transition-opacity duration-500" :style="{ opacity }">
    <button
      v-for="example in examples"
      :key="example"
      class="flex w-full items-center gap-2 text-k-fg-40 text-lg hover:text-k-fg-80 cursor-pointer text-left"
      type="button"
      @click="emit('select', example)"
    >
      <SparkleIcon class="size-4 inline" />
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
  'Add some jazz to the queue',
  'Queue the album Thriller',
  'I added some songs recently. Play them.',
  'Play the song that goes "Is this the real life, is this just fantasy"',
  'What song is playing right now?',
  'Play songs similar to this',
  'Add this song to my favorites',
  'Remove this song from my favorites',
  'Add this album to my favorites',
  'Favorite the artist Radiohead',
  'Add this to my Road Trip playlist',
  'Remove this song from my Workout playlist',
  'Play my Chill Vibes playlist',
  'Rename my playlist to Summer Hits',
  'Delete my old workout playlist',
  'Rename the album "Abbay Road" to "Abbey Road"',
  'Set the release year of Abbey Road to 1969',
  'Rename the artist "Beetles" to "Beatles"',
  'Show me the lyrics of this song',
  'Find the lyrics for this song if there are none',
  'Play my most listened-to album',
  'Play my top artist',
  'Play songs I rarely listen to',
  "Play something I've never heard before",
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
