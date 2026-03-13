//#region resources/assets/js/service-worker.ts
var AUDIO_CACHE_NAME = "koel-audio-v1";
var STATIC_CACHE_NAME = "koel-static-v1";
/**
* Normalize a play URL to a stable cache key by stripping the auth token query param.
* e.g. "https://example.com/play/abc123?t=token" -> "https://example.com/play/abc123"
*      "https://example.com/play/abc123/1?t=token" -> "https://example.com/play/abc123/1"
*/
var normalizeCacheKey = (url) => {
	const u = new URL(url);
	u.searchParams.delete("t");
	return u.toString();
};
/**
* Check if a request URL is a play (audio streaming) URL.
*/
var isPlayUrl = (url) => {
	try {
		const u = new URL(url);
		return /\/play\/[^/]+(\/1)?$/.test(u.pathname);
	} catch {
		return false;
	}
};
/**
* Check if a request URL is a static asset (JS, CSS, images, fonts).
*/
var isStaticAsset = (url) => {
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
/**
* For audio play requests: serve from cache if available, otherwise fetch from network.
* Audio is cached under a normalized key (without auth token).
* Supports HTTP Range requests for seeking in cached audio.
*/
var handlePlayRequest = async (request) => {
	const cache = await caches.open(AUDIO_CACHE_NAME);
	const cacheKey = normalizeCacheKey(request.url);
	const cached = await cache.match(cacheKey);
	if (cached) return handleRangeRequest(request, cached);
	return fetch(request);
};
/**
* Handle Range requests for cached audio to enable seeking.
* The browser sends Range headers when the user seeks in the audio player.
*/
var handleRangeRequest = async (request, cached) => {
	const rangeHeader = request.headers.get("Range");
	if (!rangeHeader) return cached;
	const blob = await cached.blob();
	const totalSize = blob.size;
	const match = rangeHeader.match(/bytes=(\d+)-(\d*)/);
	if (!match) return cached;
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
/**
* Static assets: network-first for JS (to pick up new deploys), cache-first for images/fonts.
*/
var handleStaticAsset = async (request) => {
	const url = new URL(request.url);
	return /\.js(\?.*)?$/.test(url.pathname) ? handleJsAsset(request) : handleOtherStaticAsset(request);
};
/**
* JS assets: network-first strategy. Try to fetch from network, falling back to cache.
*/
var handleJsAsset = async (request) => {
	try {
		const response = await fetch(request);
		if (response.ok) (await caches.open(STATIC_CACHE_NAME)).put(request, response.clone());
		return response;
	} catch {
		return await caches.open(STATIC_CACHE_NAME).then((c) => c.match(request)) || new Response("Service Unavailable", { status: 503 });
	}
};
/**
* Non-JS static assets (images, fonts, CSS): cache-first strategy.
*/
var handleOtherStaticAsset = async (request) => {
	const cache = await caches.open(STATIC_CACHE_NAME);
	const cached = await cache.match(request);
	if (cached) return cached;
	const response = await fetch(request);
	if (response.ok) cache.put(request, response.clone());
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
var cacheAudio = async (data, client) => {
	const { songId, sourceUrl } = data;
	const cacheKey = normalizeCacheKey(sourceUrl);
	const cache = await caches.open(AUDIO_CACHE_NAME);
	if (await cache.match(cacheKey)) {
		client.postMessage({
			type: "CACHE_AUDIO_COMPLETE",
			songId
		});
		return;
	}
	try {
		const response = await fetch(sourceUrl);
		if (!response.ok) throw new Error(`HTTP ${response.status}`);
		const contentLength = Number(response.headers.get("Content-Length") || 0);
		const reader = response.body?.getReader();
		if (!reader) throw new Error("ReadableStream not supported");
		const chunks = [];
		let received = 0;
		while (true) {
			const { done, value } = await reader.read();
			if (done) break;
			chunks.push(value);
			received += value.length;
			if (contentLength > 0) client.postMessage({
				type: "CACHE_AUDIO_PROGRESS",
				songId,
				progress: received / contentLength,
				received,
				total: contentLength
			});
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
		client.postMessage({
			type: "CACHE_AUDIO_COMPLETE",
			songId
		});
	} catch (error) {
		client.postMessage({
			type: "CACHE_AUDIO_ERROR",
			songId,
			error: error instanceof Error ? error.message : "Unknown error"
		});
	}
};
var deleteAudioCache = async (data, client) => {
	const { songId, sourceUrl } = data;
	const cacheKey = normalizeCacheKey(sourceUrl);
	const deleted = await (await caches.open(AUDIO_CACHE_NAME)).delete(cacheKey);
	client.postMessage({
		type: "DELETE_AUDIO_CACHE_COMPLETE",
		songId,
		deleted
	});
};
var getCacheStatus = async (data, client) => {
	const cache = await caches.open(AUDIO_CACHE_NAME);
	const statuses = {};
	for (const url of data.sourceUrls) {
		const cacheKey = normalizeCacheKey(url);
		const match = await cache.match(cacheKey);
		statuses[url] = Boolean(match);
	}
	client.postMessage({
		type: "CACHE_STATUS",
		statuses
	});
};
self.addEventListener("install", () => {
	self.skipWaiting();
});
self.addEventListener("activate", (event) => {
	event.waitUntil(caches.keys().then((names) => Promise.all(names.filter((name) => name !== AUDIO_CACHE_NAME && name !== STATIC_CACHE_NAME).map((name) => caches.delete(name)))).then(() => self.clients.claim()));
});
//#endregion
