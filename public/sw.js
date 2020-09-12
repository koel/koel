importScripts('https://storage.googleapis.com/workbox-cdn/releases/4.0.0/workbox-sw.js')

workbox.routing.registerRoute(
  new RegExp('.*\.js'),
  new workbox.strategies.NetworkFirst()
)

workbox.routing.registerRoute(
  /\.(?:png|jpg|jpeg|svg|gif|eot|ttf|woff2?|otf)$/,
  new workbox.strategies.CacheFirst({
    cacheName: 'image-font-cache',
    plugins: [
      new workbox.expiration.Plugin({
        maxEntries: 20,
        maxAgeSeconds: 7 * 24 * 60 * 60
      })
    ]
  })
)
