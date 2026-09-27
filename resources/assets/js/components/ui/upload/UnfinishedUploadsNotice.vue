<template>
  <section class="flex flex-col gap-2 rounded-lg bg-k-fg-5 p-4" data-testid="unfinished-uploads">
    <p>These files didn't finish uploading last time. Drop them here again to upload them.</p>
    <ul class="list-disc pl-6 text-k-fg-70">
      <li v-for="name in names" :key="name">{{ name }}</li>
    </ul>
    <Btn class="self-start" data-testid="dismiss-unfinished-uploads" variant="ghost" @click="dismiss">Dismiss</Btn>
  </section>
</template>

<script lang="ts" setup>
import { uploadService } from '@/services/uploadService'

import Btn from '@/components/ui/form/Btn.vue'

defineProps<{ names: string[] }>()

const dismiss = () => uploadService.forgetUnfinishedUploads()
</script>
