<template>
  <Btn highlight rounded small @click.prevent="goToRecentlyPlayedScreen">
    View All
  </Btn>
</template>

<script setup lang="ts">
import { useRouter } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'

const { go } = useRouter()
const goToRecentlyPlayedScreen = () => go('recently-played')
</script>

<style scoped lang="postcss">

</style>
