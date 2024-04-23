<template>
  <SidebarSection>
    <template #header>
      <SidebarSectionHeader>Manage</SidebarSectionHeader>
    </template>

    <ul class="menu">
      <SidebarItem v-if="isAdmin" screen="Settings" href="#/settings">
        <template #icon>
          <Icon :icon="faTools" fixed-width />
        </template>
        Settings
      </SidebarItem>
      <SidebarItem v-if="allowsUpload" screen="Upload" href="#/upload">
        <template #icon>
          <Icon :icon="faUpload" fixed-width />
        </template>
        Upload
      </SidebarItem>
      <SidebarItem v-if="isAdmin" screen="Users" href="#/users">
        <template #icon>
          <Icon :icon="faUsers" fixed-width />
        </template>
        Users
      </SidebarItem>
    </ul>
  </SidebarSection>
</template>
<script setup lang="ts">
import { faTools, faUpload, faUsers } from '@fortawesome/free-solid-svg-icons'
import { useAuthorization, useUpload } from '@/composables'

import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarItem from '@/components/layout/main-wrapper/sidebar/SidebarItem.vue'

const { isAdmin } = useAuthorization()
const { allowsUpload } = useUpload()
</script>
