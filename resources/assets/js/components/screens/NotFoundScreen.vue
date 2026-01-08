<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">{{ t('screens.notFound.title') }}</ScreenHeader>
    </template>

    <ScreenEmptyState>
      <template #icon>
        <Icon :icon="faKiwiBird" :mask="faMap" transform="shrink-12" />
      </template>

      {{ t('screens.notFound.message') }}
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faKiwiBird, faMap } from '@fortawesome/free-solid-svg-icons'
import { useI18n } from 'vue-i18n'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const { t } = useI18n()
</script>
