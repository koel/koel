const AUDIO_CACHE_NAME = "koel-audio-v1";
const STATIC_CACHE_NAME = "koel-static-v1";
const normalizeCacheKey = (url) => {
  const u = new URL(url);
  u.searchParams.delete("t");
  return u.toString();
};
const isPlayUrl = (url) => {
  try {
    const u = new URL(url);
    return /\/play\/[^/]+(\/1)?$/.test(u.pathname);
  } catch {
    return false;
  }
};
const isStaticAsset = (url) => {
  try {
    const u = new URL(url);
    if (u.protocol !== "http:" && u.protocol !== "https:") return false;
    return /\.(js|css|png|jpg|jpeg|svg|gif|ico|woff2?|ttf|eot|otf)(\?.*)?$/.test(u.pathname);
  } catch {
    return false;
  }
};
self.addEventListener("fetch", (event) => {
  const { request } = event;
  if (isPlayUrl(request.url)) {
    event.respondWith(handlePlayRequest(request));
    return;
  }
  if (isStaticAsset(request.url)) {
    event.respondWith(handleStaticAsset(request));
    return;
  }
});
const handlePlayRequest = async (request) => {
  const cache = await caches.open(AUDIO_CACHE_NAME);
  const cacheKey = normalizeCacheKey(request.url);
  const cached = await cache.match(cacheKey);
  if (cached) {
    return handleRangeRequest(request, cached);
  }
  return fetch(request);
};
const handleRangeRequest = async (request, cached) => {
  const rangeHeader = request.headers.get("Range");
  if (!rangeHeader) {
    return cached;
  }
  const blob = await cached.blob();
  const totalSize = blob.size;
  const match = rangeHeader.match(/bytes=(\d+)-(\d*)/);
  if (!match) {
    return cached;
  }
  const start = Number(match[1]);
  const end = match[2] ? Number(match[2]) : totalSize - 1;
  const sliced = blob.slice(start, end + 1);
  return new Response(sliced, {
    status: 206,
    statusText: "Partial Content",
    headers: {
      "Content-Type": cached.headers.get("Content-Type") || "audio/mpeg",
      "Content-Length": String(sliced.size),
      "Content-Range": `bytes ${start}-${end}/${totalSize}`,
      "Accept-Ranges": "bytes"
    }
  });
};
const handleStaticAsset = async (request) => {
  const url = new URL(request.url);
  const isJS = /\.js(\?.*)?$/.test(url.pathname);
  return isJS ? handleJsAsset(request) : handleOtherStaticAsset(request);
};
const handleJsAsset = async (request) => {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(STATIC_CACHE_NAME);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.open(STATIC_CACHE_NAME).then((c) => c.match(request));
    return cached || new Response("Service Unavailable", { status: 503 });
  }
};
const handleOtherStaticAsset = async (request) => {
  const cache = await caches.open(STATIC_CACHE_NAME);
  const cached = await cache.match(request);
  if (cached) {
    return cached;
  }
  const response = await fetch(request);
  if (response.ok) {
    cache.put(request, response.clone());
  }
  return response;
};
self.addEventListener("message", (event) => {
  const data = event.data;
  switch (data.type) {
    case "CACHE_AUDIO":
      event.waitUntil(cacheAudio(data, event.source));
      break;
    case "DELETE_AUDIO_CACHE":
      event.waitUntil(deleteAudioCache(data, event.source));
      break;
    case "GET_CACHE_STATUS":
      event.waitUntil(getCacheStatus(data, event.source));
      break;
  }
});
const cacheAudio = async (data, client) => {
  const { songId, sourceUrl } = data;
  const cacheKey = normalizeCacheKey(sourceUrl);
  const cache = await caches.open(AUDIO_CACHE_NAME);
  const existing = await cache.match(cacheKey);
  if (existing) {
    client.postMessage({ type: "CACHE_AUDIO_COMPLETE", songId });
    return;
  }
  try {
    const response = await fetch(sourceUrl);
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    const contentLength = Number(response.headers.get("Content-Length") || 0);
    const reader = response.body?.getReader();
    if (!reader) {
      throw new Error("ReadableStream not supported");
    }
    const chunks = [];
    let received = 0;
    while (true) {
      const { done, value } = await reader.read();
      if (done) {
        break;
      }
      chunks.push(value);
      received += value.length;
      if (contentLength > 0) {
        client.postMessage({
          type: "CACHE_AUDIO_PROGRESS",
          songId,
          progress: received / contentLength,
          received,
          total: contentLength
        });
      }
    }
    const blob = new Blob(chunks, { type: response.headers.get("Content-Type") || "audio/mpeg" });
    const cachedResponse = new Response(blob, {
      status: response.status,
      statusText: response.statusText,
      headers: {
        "Content-Type": response.headers.get("Content-Type") || "audio/mpeg",
        "Content-Length": String(blob.size)
      }
    });
    await cache.put(cacheKey, cachedResponse);
    client.postMessage({ type: "CACHE_AUDIO_COMPLETE", songId });
  } catch (error) {
    client.postMessage({
      type: "CACHE_AUDIO_ERROR",
      songId,
      error: error instanceof Error ? error.message : "Unknown error"
    });
  }
};
const deleteAudioCache = async (data, client) => {
  const { songId, sourceUrl } = data;
  const cacheKey = normalizeCacheKey(sourceUrl);
  const cache = await caches.open(AUDIO_CACHE_NAME);
  const deleted = await cache.delete(cacheKey);
  client.postMessage({ type: "DELETE_AUDIO_CACHE_COMPLETE", songId, deleted });
};
const getCacheStatus = async (data, client) => {
  const cache = await caches.open(AUDIO_CACHE_NAME);
  const statuses = {};
  for (const url of data.sourceUrls) {
    const cacheKey = normalizeCacheKey(url);
    const match = await cache.match(cacheKey);
    statuses[url] = Boolean(match);
  }
  client.postMessage({ type: "CACHE_STATUS", statuses });
};
self.addEventListener("install", () => {
  self.skipWaiting();
});
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then(
      (names) => Promise.all(
        names.filter((name) => name !== AUDIO_CACHE_NAME && name !== STATIC_CACHE_NAME).map((name) => caches.delete(name))
      )
    ).then(() => self.clients.claim())
  );
});
