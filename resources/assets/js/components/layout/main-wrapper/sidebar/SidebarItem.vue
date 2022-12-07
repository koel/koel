<template>
  <li>
    <a :class="active && 'active'" :href="props.href">
      <icon :icon="props.icon" fixed-width />
      <span>
        <slot />
      </span>
    </a>
  </li>
</template>

<script lang="ts" setup>
import { Component, ref } from 'vue'
import  { useRouter } from '@/composables'

const props = defineProps<{ href: string; icon: Component, screen: ScreenName }>()

const active = ref(false)

const { onRouteChanged } = useRouter()

onRouteChanged(route => active.value = route.screen === props.screen)
</script>
