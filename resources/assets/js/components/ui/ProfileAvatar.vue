<template>
  <a
    v-if="currentUser"
    v-koel-tooltip.left
    class="view-profile"
    data-testid="view-profile-link"
    href="/#/profile"
    title="Profile and preferences"
  >
    <UserAvatar
      :user="currentUser"
      class="p-0.5 border border-solid border-white/10 transition duration-200 ease-in-out hover:border-white/30 active:scale-95"
      width="40"
    />
  </a>
</template>

<script lang="ts" setup>
import { useAuthorization } from '@/composables'
import UserAvatar from '@/components/user/UserAvatar.vue'

const { currentUser } = useAuthorization()
</script>
