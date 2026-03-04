<template>
  <!--
    A very thin wrapper around Plyr, extracted as a standalone component for easier styling and to work better with HMR.
    We have two audio elements:
    1. The main one (with Plyr) for queue playback (songs/episodes) - can be linked to AudioContext
    2. A separate hidden one for radio playback - NEVER linked to AudioContext to avoid CORS issues
  -->
  <div class="plyr w-full h-[4px]">
    <audio id="audio-queue" class="hidden" controls crossorigin="anonymous" />
    <audio id="audio-radio" class="hidden" controls />
  </div>
</template>

<style lang="postcss">
/* can't be scoped as it would be overridden by the plyr css */
.plyr {
  .plyr__controls {
    @apply bg-transparent shadow-none absolute top-0 w-full;
    @apply p-0 !important;
  }

  .plyr__progress--played[value] {
    @apply transition duration-300 ease-in-out text-k-fg-10;

    :fullscreen & {
      @apply text-k-fg-50 rounded-full overflow-hidden;
    }
  }

  &:hover {
    .plyr__progress--played[value] {
      @apply text-k-highlight;
    }
  }

  .plyr__progress--played[value] {
    @apply no-hover:text-k-highlight;
  }

  :fullscreen & {
    @apply z-[4] bg-white/20 rounded-full;

    .plyr__progress--played[value] {
      @apply text-white !important;
    }
  }
}
</style>
