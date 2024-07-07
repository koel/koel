<template>
  <Btn highlight rounded small @click.prevent="goToRecentlyPlayedScreen">
    View All
  </Btn>
</template>

<script lang="ts" setup>
import { useRouter } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'

const { go } = useRouter()
const goToRecentlyPlayedScreen = () => go('recently-played')
</script>

<style lang="postcss" scoped>

</style>
