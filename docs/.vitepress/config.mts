import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "koel",
  description: "The official documentation for Koel, the music streaming solution that works",
  head: [
    [
      'script',
      {
        defer: 'defer',
        src: 'https://app.lemonsqueezy.com/js/lemon.js'
      }
    ],
    ['link', { rel: 'icon', href: '/favicon.png', type: 'image/x-png' }]
  ],
  cleanUrls: true,
  markdown: {
    linkify: false,
    image: {
      lazyLoading: true
    }
  },
  themeConfig: {
    search: {
      provider: 'algolia',
      options: {
        appId: '2EQKL1O1UU',
        apiKey: 'e9d7b5be73f29af4030535a726ec7196',
        indexName: 'koel'
      }
    },

    outline: {
      level: 'deep'
    },

    logo: '/logo.svg',
    logoLink: '/guide/what-is-koel',

    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Home', link: 'https://koel.dev' },
      { text: 'Getting Started', link: '/guide/getting-started' },
      { text: 'Koel Plus', link: '/plus/what-is-koel-plus' },
    ],

    sidebar: [
      {
        text: 'Introduction',
        items: [
          { text: 'What Is Koel?',  link: '/guide/what-is-koel' },
          { text: 'Getting Started', link: '/guide/getting-started' },
        ]
      },
      {
        text: 'Usage',
        items: [
          { text: 'Music Discovery', link: '/usage/music-discovery' },
          { text: 'Streaming Music', link: '/usage/streaming' },
          { text: 'Using the Web Interface', link: '/usage/web-interface' },
          { text: 'Instant Search', link: '/usage/search' },
          { text: 'Themes', link: '/usage/themes' },
          { text: 'Artist, Album, & Playlist Arts', link: '/usage/artist-album-playlist-arts' },
          { text: 'Podcasts', link: '/usage/podcasts' },
          { text: 'User Management', link: '/usage/user-management' },
          { text: 'Profile & Preferences', link: '/usage/profile-preferences' },
          { text: 'Remote Controller', link: '/usage/remote-controller' },
        ]
      },
      {
        text: 'Koel Plus',
        items: [
          { text: 'What Is Koel Plus?', link: '/plus/what-is-koel-plus' },
          { text: 'Purchase & Activation', link: '/plus/purchase-activation' },
          { text: 'Cloud Storage Support', link: '/plus/cloud-storage-support' },
          { text: 'Collaboration', link: '/plus/collaboration' },
          { text: 'Single Sign-On', link: '/plus/sso' },
          { text: 'Proxy Authentication', link: '/plus/proxy-auth' },
        ]
      },
      {
        text: 'Service Integrations',
        link: '/service-integrations.md'
      },
      {
        text: 'Mobile Apps',
        link: '/mobile-apps'
      },
      {
        text: 'CLI Commands',
        link: '/cli-commands'
      },
      {
        text: 'Local Development',
        link: '/development'
      },
      {
        text: 'Troubleshooting',
        link: '/troubleshooting'
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/koel/koel' }
    ]
  }
})
