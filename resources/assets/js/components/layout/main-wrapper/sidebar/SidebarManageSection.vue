<template>
  <SidebarSection>
    <template #header>
      <SidebarSectionHeader>Manage</SidebarSectionHeader>
    </template>

    <ul class="menu">
      <SidebarItem v-if="canManageSettings" :href="url('settings')" :active="isCurrentScreen('Settings')">
        <template #icon>
          <Icon :icon="faTools" fixed-width />
        </template>
        Settings
      </SidebarItem>
      <SidebarItem v-if="canUpload" :href="url('upload')" :active="isCurrentScreen('Upload')">
        <template #icon>
          <Icon :icon="faUpload" fixed-width />
        </template>
        Upload
      </SidebarItem>
      <SidebarItem v-if="canManageUsers" :href="url('users.index')" :active="isCurrentScreen('Users', 'Profile')">
        <template #icon>
          <Icon :icon="faUsers" fixed-width />
        </template>
        Users
      </SidebarItem>
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { faTools, faUpload, faUsers } from '@fortawesome/free-solid-svg-icons'
import { useRouter } from '@/composables/useRouter'
import { usePolicies } from '@/composables/usePolicies'

import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarItem from '@/components/layout/main-wrapper/sidebar/SidebarItem.vue'

const { url, isCurrentScreen } = useRouter()
const { currentUserCan } = usePolicies()

const canManageSettings = computed(() => currentUserCan.manageSettings())
const canManageUsers = computed(() => currentUserCan.manageUsers())
const canUpload = computed(() => currentUserCan.uploadSongs())
</script>
