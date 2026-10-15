<template>
  <li class="block w-full">
    <a
      class="relative flex items-stretch transition bg-k-fg-5 rounded-md border border-transparent hover:border-k-fg-10 overflow-hidden group"
      :href="event.url"
      target="_blank"
    >
      <aside class="w-44 min-w-44 p-4 flex items-center justify-center">
        <img :src="event.image" alt="" class="w-full aspect-3/2 object-cover rounded-md" />
      </aside>
      <dl class="flex-1 flex flex-col justify-center border-l border-white/20 border-dashed p-4 overflow-hidden">
        <dt class="sr-only">Name</dt>
        <dd :title="event.name" class="text-xl text-k-fg mb-2 truncate">
          {{ event.name }}
        </dd>
        <dt class="sr-only">Date and Time</dt>
        <dd>
          {{ event.dates.start }}
          <template v-if="event.dates.end"> - {{ event.dates.end }} </template>
        </dd>
        <dt class="sr-only">Venue</dt>
        <dd>
          {{ [event.venue.name, event.venue.city, event.venue.country].filter(Boolean).join(', ') }}
        </dd>
      </dl>

      <Icon
        :icon="faTicket"
        class="size-32 transition-[bottom_color] duration-300 text-k-fg-5 -rotate-45 absolute right-6 -bottom-3/4 group-hover:text-white/20 group-hover:-bottom-1/2"
      />
    </a>
  </li>
</template>

<script setup lang="ts">
import { faTicket } from '@fortawesome/free-solid-svg-icons'

defineProps<{ event: LiveEvent }>()
</script>
