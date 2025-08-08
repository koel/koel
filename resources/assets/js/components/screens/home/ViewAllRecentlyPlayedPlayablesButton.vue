<template>
  <Btn highlight rounded small @click.prevent="goToRecentlyPlayedScreen">
    View All
  </Btn>
</template>

<script lang="ts" setup>
import { useRouter } from '@/composables/useRouter'

import Btn from '@/components/ui/form/Btn.vue'

const { go, url } = useRouter()
const goToRecentlyPlayedScreen = () => go(url('recently-played'))
</script>

<style lang="postcss" scoped>

</style>
