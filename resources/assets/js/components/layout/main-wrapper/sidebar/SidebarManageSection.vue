<template>
  <SidebarSection>
    <template #header>
      <SidebarSectionHeader>Manage</SidebarSectionHeader>
    </template>

    <ul class="menu">
      <SidebarItem v-if="isAdmin" :href="url('settings')" :active="isCurrentScreen('Settings')">
        <template #icon>
          <Icon :icon="faTools" fixed-width />
        </template>
        Settings
      </SidebarItem>
      <SidebarItem v-if="allowsUpload" :href="url('upload')" :active="isCurrentScreen('Upload')">
        <template #icon>
          <Icon :icon="faUpload" fixed-width />
        </template>
        Upload
      </SidebarItem>
      <SidebarItem v-if="isAdmin" :href="url('users.index')" :active="isCurrentScreen('Users', 'Profile')">
        <template #icon>
          <Icon :icon="faUsers" fixed-width />
        </template>
        Users
      </SidebarItem>
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { faTools, faUpload, faUsers } from '@fortawesome/free-solid-svg-icons'
import { useAuthorization } from '@/composables/useAuthorization'
import { useUpload } from '@/composables/useUpload'
import { useRouter } from '@/composables/useRouter'

import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarItem from '@/components/layout/main-wrapper/sidebar/SidebarItem.vue'

const { url, isCurrentScreen } = useRouter()
const { isAdmin } = useAuthorization()
const { allowsUpload } = useUpload()
</script>
