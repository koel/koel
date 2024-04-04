<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">Not Found</ScreenHeader>
    </template>

    <ScreenEmptyState>
      <template #icon>
        <Icon :icon="faKiwiBird" :mask="faMap" transform="shrink-12" />
      </template>

      The requested content cannot be found.
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faKiwiBird, faMap } from '@fortawesome/free-solid-svg-icons'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
</script>
