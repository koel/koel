//#region resources/assets/js/utils/isCacheablePlayUrl.ts
var isCacheablePlayUrl = (url) => {
	try {
		const parsedUrl = new URL(url);
		if (parsedUrl.searchParams.get("progressive") === "1") return false;
		return /\/play\/[^/]+(\/1)?$/.test(parsedUrl.pathname);
	} catch {
		return false;
	}
};
//#endregion
//#region resources/assets/js/utils/audioCache.ts
var normalizeAudioCacheKey = (url) => {
	const normalizedUrl = new URL(url);
	normalizedUrl.searchParams.delete("t");
	return normalizedUrl.toString();
};
var createAudioCacheCompletionMessage = (songId, sourceUrl, playable) => ({
	type: "CACHE_AUDIO_COMPLETE",
	songId,
	sourceUrl: normalizeAudioCacheKey(sourceUrl),
	playable
});
var isMatchingAudioCacheCompletion = (completion, songId, sourceUrl) => completion.songId === songId && normalizeAudioCacheKey(completion.sourceUrl) === normalizeAudioCacheKey(sourceUrl);
var storeNewAudioCacheEntry = async (cache, cacheKey, response, complete) => {
	await cache.put(cacheKey, response);
	try {
		await complete();
	} catch (error) {
		await cache.delete(cacheKey).catch(() => false);
		throw error;
	}
};
var handleCachedAudioRangeRequest = async (request, cached) => {
	const rangeHeader = request.headers.get("Range");
	if (!rangeHeader) return cached;
	const blob = await cached.blob();
	const totalSize = blob.size;
	const match = rangeHeader.match(/^bytes=(\d+)-(\d*)$/);
	if (!match) return cached;
	const start = Number(match[1]);
	const requestedEnd = match[2] ? Number(match[2]) : totalSize - 1;
	if (!Number.isSafeInteger(start) || start >= totalSize || requestedEnd < start) return new Response(null, {
		status: 416,
		statusText: "Range Not Satisfiable",
		headers: {
			"Content-Length": "0",
			"Content-Range": `bytes */${totalSize}`,
			"Accept-Ranges": "bytes"
		}
	});
	const end = Math.min(requestedEnd, totalSize - 1);
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
//#endregion
//#region resources/assets/js/service-worker.ts
var AUDIO_CACHE_NAME = "koel-audio-v1";
var AUDIO_CACHE_COMPLETION_NAME = "koel-audio-completions-v1";
var STATIC_CACHE_NAME = "koel-static-v1";
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
	if (isCacheablePlayUrl(request.url)) {
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
	const cacheKey = normalizeAudioCacheKey(request.url);
	const cached = await cache.match(cacheKey);
	if (cached) return handleCachedAudioRangeRequest(request, cached);
	return fetch(request);
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
	const client = event.source;
	switch (data.type) {
		case "CACHE_AUDIO":
			event.waitUntil(cacheAudio(data, client));
			break;
		case "DELETE_AUDIO_CACHE":
			if (client) event.waitUntil(deleteAudioCache(data, client));
			break;
		case "GET_CACHE_STATUS":
			if (client) event.waitUntil(getCacheStatus(data, client));
			break;
		case "RECOVER_AUDIO_CACHE_COMPLETIONS":
			if (client) event.waitUntil(recoverAudioCacheCompletions(client));
			break;
		case "ACK_AUDIO_CACHE_COMPLETION":
			event.waitUntil(acknowledgeAudioCacheCompletion(data));
			break;
	}
});
var postMessageSafely = (client, message) => {
	try {
		client.postMessage(message);
		return true;
	} catch {
		return false;
	}
};
var postCacheResult = async (originatingClient, message) => {
	try {
		if (originatingClient) {
			const currentClient = await self.clients.get(originatingClient.id);
			if (currentClient && postMessageSafely(currentClient, message)) return;
		}
		(await self.clients.matchAll({
			includeUncontrolled: true,
			type: "window"
		})).forEach((client) => postMessageSafely(client, message));
	} catch {
		return;
	}
};
var createCacheProgressReporter = (originatingClient) => {
	let client = originatingClient;
	let recoveryPromise = null;
	return (message) => {
		if (!client || recoveryPromise) return;
		if (postMessageSafely(client, message)) return;
		const clientId = client.id;
		client = null;
		recoveryPromise = self.clients.get(clientId).then(async (currentClient) => {
			client = currentClient ?? (await self.clients.matchAll({
				includeUncontrolled: true,
				type: "window"
			}))[0] ?? null;
			if (client && !postMessageSafely(client, message)) client = null;
		}).catch(() => {
			client = null;
		}).finally(() => {
			recoveryPromise = null;
		});
	};
};
var getAudioCacheCompletionKey = (songId) => new URL(`/__koel/audio-cache-completions/${encodeURIComponent(songId)}`, self.location.origin).toString();
var persistAudioCacheCompletion = async (message) => {
	const cache = await caches.open(AUDIO_CACHE_COMPLETION_NAME);
	const cacheKey = getAudioCacheCompletionKey(message.songId);
	const storedMessage = createAudioCacheCompletionMessage(message.songId, message.sourceUrl, message.playable);
	await cache.put(cacheKey, new Response(JSON.stringify(storedMessage), { headers: { "Content-Type": "application/json" } }));
};
var completeAudioCaching = async (songId, sourceUrl, playable, client) => {
	const completion = createAudioCacheCompletionMessage(songId, sourceUrl, playable);
	await persistAudioCacheCompletion(completion);
	await postCacheResult(client, completion);
};
var recoverAudioCacheCompletions = async (client) => {
	const completionCache = await caches.open(AUDIO_CACHE_COMPLETION_NAME);
	const audioCache = await caches.open(AUDIO_CACHE_NAME);
	const completionRequests = await completionCache.keys();
	const completions = [];
	for (const request of completionRequests) {
		const response = await completionCache.match(request);
		if (!response) continue;
		try {
			const storedCompletion = await response.json();
			const completion = createAudioCacheCompletionMessage(storedCompletion.songId, storedCompletion.sourceUrl, storedCompletion.playable);
			if (await audioCache.match(completion.sourceUrl)) completions.push(completion);
			else await completionCache.delete(request);
		} catch {
			await completionCache.delete(request);
		}
	}
	postMessageSafely(client, {
		type: "AUDIO_CACHE_COMPLETIONS_RECOVERED",
		completions
	});
};
var acknowledgeAudioCacheCompletion = async (data) => {
	await deleteAudioCacheCompletionIfMatching(data.songId, data.sourceUrl);
};
var deleteAudioCacheCompletionIfMatching = async (songId, sourceUrl) => {
	const completionCache = await caches.open(AUDIO_CACHE_COMPLETION_NAME);
	const completionKey = getAudioCacheCompletionKey(songId);
	const response = await completionCache.match(completionKey);
	if (!response) return;
	try {
		if (isMatchingAudioCacheCompletion(await response.json(), songId, sourceUrl)) await completionCache.delete(completionKey);
	} catch {
		await completionCache.delete(completionKey);
	}
};
var cacheAudio = async (data, client) => {
	const { songId, sourceUrl } = data;
	const cacheKey = normalizeAudioCacheKey(sourceUrl);
	const cache = await caches.open(AUDIO_CACHE_NAME);
	const reportProgress = createCacheProgressReporter(client);
	try {
		if (await cache.match(cacheKey)) {
			await completeAudioCaching(songId, cacheKey, data.playable, client);
			return;
		}
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
			if (contentLength > 0) reportProgress({
				type: "CACHE_AUDIO_PROGRESS",
				songId,
				progress: received / contentLength,
				received,
				total: contentLength
			});
		}
		const blob = new Blob(chunks, { type: response.headers.get("Content-Type") || "audio/mpeg" });
		await storeNewAudioCacheEntry(cache, cacheKey, new Response(blob, {
			status: response.status,
			statusText: response.statusText,
			headers: {
				"Content-Type": response.headers.get("Content-Type") || "audio/mpeg",
				"Content-Length": String(blob.size)
			}
		}), () => completeAudioCaching(songId, cacheKey, data.playable, client));
	} catch (error) {
		await postCacheResult(client, {
			type: "CACHE_AUDIO_ERROR",
			songId,
			error: error instanceof Error ? error.message : "Unknown error"
		});
	}
};
var deleteAudioCache = async (data, client) => {
	const { songId, sourceUrl } = data;
	const cacheKey = normalizeAudioCacheKey(sourceUrl);
	const deleted = await (await caches.open(AUDIO_CACHE_NAME)).delete(cacheKey);
	await deleteAudioCacheCompletionIfMatching(songId, cacheKey);
	client.postMessage({
		type: "DELETE_AUDIO_CACHE_COMPLETE",
		songId,
		sourceUrl: cacheKey,
		deleted
	});
};
var getCacheStatus = async (data, client) => {
	const cache = await caches.open(AUDIO_CACHE_NAME);
	const statuses = {};
	for (const url of data.sourceUrls) {
		const cacheKey = normalizeAudioCacheKey(url);
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
	event.waitUntil(caches.keys().then((names) => Promise.all(names.filter((name) => name !== AUDIO_CACHE_NAME && name !== AUDIO_CACHE_COMPLETION_NAME && name !== STATIC_CACHE_NAME).map((name) => caches.delete(name)))).then(() => self.clients.claim()));
});
//#endregion
