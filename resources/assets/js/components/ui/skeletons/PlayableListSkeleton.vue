<template>
  <div class="skeleton">
    <div class="song-list-header h-[35px] flex bg-k-bg-secondary gap-4 px-4">
      <span class="title">
        <span class="text" />
      </span>
      <span class="album">
        <span class="text" />
      </span>
      <span class="time">
        <span class="text" />
      </span>
    </div>
    <div v-for="i in 40" :key="i" class="flex gap-4 px-4 py-3 border-b border-k-border">
      <span class="title flex gap-3">
        <span class="thumbnail block h-[48px] aspect-square rounded pulse" />
        <span class="flex-1 flex-col space-y-2 content-center">
          <span class="block h-5 rounded-full w-4/5 pulse" />
          <span class="block h-5 rounded-full w-2/5 pulse" />
        </span>
      </span>
      <span class="album flex-col content-center">
        <span class="block h-5 w-3/5 rounded-full pulse" />
      </span>
      <span class="time flex-col content-center">
        <span class="block h-5 w-3/5 rounded-full pulse" />
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
</script>

<style lang="postcss" scoped>
.song-list-header {
  @apply h-[35px] flex bg-k-bg-secondary;
}

.song-list-header > span {
  @apply flex items-center;
}

.song-list-header span span {
  @apply block h-[1.2rem] rounded-full bg-white/10 w-2/5;
}

.title {
  @apply flex-1;
}

.album {
  @apply basis-[27%];
}

.time {
  @apply basis-[96px];
}

@media screen and (max-width: 768px) {
  span {
    @apply hidden;
  }

  .title,
  .album {
    @apply flex;
  }

  .song-list-header {
    @apply py-0 px-[16px];
  }
}
</style>
