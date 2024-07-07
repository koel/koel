<template>
  <FooterExtraControlBtn :title="title" @click.stop="toggleLike">
    <Icon v-if="playable.liked" :icon="faHeart" />
    <Icon v-else :icon="faEmptyHeart" />
  </FooterExtraControlBtn>
</template>

<script lang="ts" setup>
import { faHeart } from '@fortawesome/free-solid-svg-icons'
import { faHeart as faEmptyHeart } from '@fortawesome/free-regular-svg-icons'
import { computed, toRefs } from 'vue'
import { favoriteStore } from '@/stores'

import FooterExtraControlBtn from '@/components/layout/app-footer/FooterButton.vue'

const props = defineProps<{ playable: Playable }>()
const { playable } = toRefs(props)

const title = computed(() => playable.value.liked ? 'Unlike' : 'Like')

const toggleLike = () => favoriteStore.toggleOne(playable.value)
</script>
