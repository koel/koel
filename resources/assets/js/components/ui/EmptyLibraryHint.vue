<template>
  <span v-if="libraryNeedsSetup || allowsUpload" class="secondary block">
    <template v-if="libraryNeedsSetup">Have you set up your library yet?</template>
    <template v-else> <a :href="url('upload')" class="inline">Upload some music</a> to get started. </template>
  </span>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { usePolicies } from '@/composables/usePolicies'
import { useRouter } from '@/composables/useRouter'
import { useUpload } from '@/composables/useUpload'

const { currentUserCan } = usePolicies()
const { url } = useRouter()
const { allowsUpload, mediaPathSetUp } = useUpload()

const libraryNeedsSetup = computed(() => !mediaPathSetUp.value && currentUserCan.manageSettings())
</script>
