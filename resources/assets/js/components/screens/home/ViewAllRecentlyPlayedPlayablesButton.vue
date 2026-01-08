<template>
  <Btn highlight rounded small @click.prevent="goToRecentlyPlayedScreen">
    {{ t('ui.buttons.viewAll') }}
  </Btn>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { useRouter } from '@/composables/useRouter'

import Btn from '@/components/ui/form/Btn.vue'

const { t } = useI18n()
const { go, url } = useRouter()
const goToRecentlyPlayedScreen = () => go(url('recently-played'))
</script>

<style lang="postcss" scoped>

</style>
