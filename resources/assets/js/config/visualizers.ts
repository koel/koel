export const visualizers: Visualizer[] = [
  {
    id: 'default',
    name: 'Color Pills',
    init: async (container) => (await import('@/visualizers/default')).init(container),
    credits: {
      author: 'Justin Windle (@soulwire)',
      url: 'https://codepen.io/soulwire/pen/Dscga'
    }
  },
  {
    id: 'plane-mesh',
    name: 'Plane Mesh',
    init: async (container) => (await import('@/visualizers/plane-mesh')).init(container),
    credits: {
      author: 'Steven Marelly (@l1ve4code)',
      url: 'https://github.com/l1ve4code/3d-music-visualizer'
    }
  },
  {
    id: 'waveform',
    name: 'Waveform',
    init: async (container) => (await import('@/visualizers/waveform')).init(container),
    credits: {
      author: 'Suboptimal Engineer (@SuboptimalEng)',
      url: 'https://github.com/SuboptimalEng/gamedex/tree/main/audio-visualizer'
    }
  },
  {
    id: 'fluid-cube',
    name: 'Fluid Cube',
    init: async (container) => (await import('@/visualizers/fluid-cube')).init(container),
    credits: {
      author: 'Radik (@H2xDev)',
      url: 'https://codepen.io/H2xDev/pen/rRRGbv'
    }
  }
]
