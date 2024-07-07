<template>
  <div class="skeleton">
    <div class="song-list-header">
      <span class="track-number">
        <span class="text" />
      </span>
      <span class="title">
        <span class="text" />
      </span>
      <span class="artist">
        <span class="text" />
      </span>
      <span class="album">
        <span class="text" />
      </span>
      <span class="time">
        <span class="text" />
      </span>
      <span class="favorite" />
    </div>
    <div v-for="i in 40" :key="i" class="song-item">
      <span class="track-number">
        <span class="text pulse" />
      </span>
      <span class="title">
        <span class="thumbnail pulse" />
        <span class="text pulse" />
      </span>
      <span class="artist">
        <span class="text pulse" />
      </span>
      <span class="album">
        <span class="text pulse" />
      </span>
      <span class="time">
        <span class="text pulse" />
      </span>
      <span class="favorite">
        <span class="text pulse" />
      </span>
    </div>
  </div>
</template>

<style lang="postcss" scoped>
.song-list-header {
  @apply h-[35px] flex bg-k-bg-secondary;
}

.song-item {
  @apply flex h-[64px] border-b border-k-border;
}

.song-list-header span span, .pulse {
  @apply inline-block h-[1.2rem] rounded-full w-4/5;
}

.song-list-header span span {
  @apply bg-white/10 w-2/5;
}

span:not(.text) {
  @apply p-[8px] align-middle;
}

.track-number {
  @apply basis-[66px] pl-[24px];
}

.thumbnail {
  @apply block w-[48px] aspect-square rounded;
}

.title {
  @apply flex items-center gap-3 flex-1;
}

.artist {
  @apply basis-[23%];
}

.album {
  @apply basis-[27%];
}

.time {
  @apply basis-[96px];
}

.favorite {
  @apply basis-[36px];
}

@media screen and (max-width: 768px) {
  span {
    @apply hidden;
  }

  .title, .artist {
    @apply flex;
  }

  .artist .text {
    @apply w-full;
  }

  .song-list-header, .song-item {
    @apply py-0 px-[16px];
  }
}
</style>
