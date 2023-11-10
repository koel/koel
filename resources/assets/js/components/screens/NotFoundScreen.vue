<template>
  <section>
    <ScreenHeader layout="collapsed">Not Found</ScreenHeader>

    <div class="main-scroll-wrap">
      <ScreenEmptyState>
        <template #icon>
          <Icon :icon="faKiwiBird" :mask="faMap" transform="shrink-12" />
        </template>

        The requested content cannot be found.
      </ScreenEmptyState>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { faKiwiBird, faMap } from '@fortawesome/free-solid-svg-icons'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
//
</script>
