<template>
  <HomeScreenBlock>
    <template #header>
      {{ t('screens.recentlyPlayed') }}
      <ViewAllRecentlyPlayedPlayablesButton v-if="playables.length" class="float-right" />
    </template>

    <PlayableListSkeleton v-if="loading" class="border border-k-fg-5 rounded-lg" />
    <template v-else>
      <PlayableList
        v-if="playables.length"
        ref="playableList"
        class="border border-k-fg-5 rounded-lg overflow-hidden"
        @press:enter="onPressEnter"
      />
      <p v-else>{{ t('emptyStates.mostPlayedEmpty') }}</p>
    </template>
  </HomeScreenBlock>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { overviewStore } from '@/stores/overviewStore'
import { usePlayableList } from '@/composables/usePlayableList'
import { playback } from '@/services/playbackManager'

import HomeScreenBlock from '@/components/screens/home/HomeScreenBlock.vue'
import ViewAllRecentlyPlayedPlayablesButton from '@/components/screens/home/ViewAllRecentlyPlayedPlayablesButton.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'

const { t } = useI18n()
const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const {
  PlayableList,
  playables,
  playableList,
  selectedPlayables,
} = usePlayableList(toRef(overviewStore.state, 'recentlyPlayed'), {}, {
  sortable: false,
})

const onPressEnter = () => selectedPlayables.value.length && playback().play(selectedPlayables.value[0])
</script>
