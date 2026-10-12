<template>
  <div>
    <ProfileForm />
    <HookSlot name="profile-tab.footer" />
  </div>
</template>

<script lang="ts" setup>
import HookSlot from '@/components/utils/HookSlot.vue'
import ProfileForm from '@/components/profile-preferences/ProfileForm.vue'
</script>
