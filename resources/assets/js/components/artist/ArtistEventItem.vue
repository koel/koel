<template>
  <li class="block w-full">
    <a
      class="relative flex items-stretch transition bg-k-bg-secondary rounded-md border border-transparent
      hover:border-white/10 overflow-hidden group"
      :href="event.url"
      target="_blank"
      title="View and buy tickets for this event"
    >
      <aside class="w-44 min-w-44 p-4 flex items-center justify-center">
        <img :src="event.image" alt="" class="w-full aspect-[3/2] object-cover rounded-md">
      </aside>
      <dl
        class="flex-1 flex flex-col justify-center text-k-text-secondary border-l border-white/20 border-dashed p-4 overflow-hidden"
      >
        <dt class="sr-only">Name</dt>
        <dd class="text-xl text-k-text-primary mb-2 overflow-hidden whitespace-nowrap text-ellipsis">
          {{ event.name }}
        </dd>
        <dt class="sr-only">Date and Time</dt>
        <dd>
          {{ event.dates.start }}
          <template v-if="event.dates.end">
            - {{ event.dates.end }}
          </template>
        </dd>
        <dt class="sr-only">Venue</dt>
        <dd>
          <template v-if="event.venue.name">{{ event.venue.name }},</template>
          {{ event.venue.city }}
        </dd>
      </dl>

      <Icon
        :icon="faTicket"
        class="size-32 transition-[bottom_color] duration-300 text-white/5 -rotate-45 absolute right-6 -bottom-3/4
        group-hover:text-white/20 group-hover:-bottom-1/2"
      />
    </a>
  </li>
</template>

<script setup lang="ts">
import { faTicket } from '@fortawesome/free-solid-svg-icons'

defineProps<{ event: LiveEvent }>()
</script>
