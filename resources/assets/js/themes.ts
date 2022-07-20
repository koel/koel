import bgRosePetal from '../img/themes/bg-rose-petals.svg'
import bgPurpleWaves from '../img/themes/bg-purple-waves.svg'
import bgPopCulture from '../img/themes/bg-pop-culture.jpg'
import thumbPopCulture from '../img/themes/thumbnails/pop-culture.jpg'
import bgJungle from '../img/themes/bg-jungle.jpg'
import thumbJungle from '../img/themes/thumbnails/jungle.jpg'
import bgMountains from '../img/themes/bg-mountains.jpg'
import thumbMountains from '../img/themes/thumbnails/mountains.jpg'
import bgPines from '../img/themes/bg-pines.jpg'
import thumbPines from '../img/themes/thumbnails/pines.jpg'
import bgNemo from '../img/themes/bg-nemo.jpg'
import thumbNemo from '../img/themes/thumbnails/nemo.jpg'
import bgCat from '../img/themes/bg-cat.jpg'
import thumbCat from '../img/themes/thumbnails/cat.jpg'
import bgDawn from '../img/themes/bg-dawn.jpg'
import thumbDawn from '../img/themes/thumbnails/dawn.jpg'

export default [
  {
    id: 'classic',
    thumbnailColor: '#181818'
  },
  {
    id: 'violet',
    thumbnailColor: '#31094e',
    properties: {
      '--color-bg-primary': '#31094e',
      '--color-highlight': '#c23de5'
    }
  },
  {
    id: 'oak',
    thumbnailColor: '#560d25',
    properties: {
      '--color-bg-primary': '#560d25',
      '--color-highlight': '#fd4b67'
    }
  },
  {
    id: 'slate',
    thumbnailColor: '#29434e',
    properties: {
      '--color-bg-primary': '#29434e',
      '--color-highlight': '#6c8b99'
    }
  },
  {
    id: 'madison',
    thumbnailColor: '#0e3463',
    properties: {
      '--color-bg-primary': '#0e3463',
      '--color-bg-highlight': '#fbab18'
    }
  },
  {
    id: 'astronaut',
    thumbnailColor: '#2a3074',
    properties: {
      '--color-bg-primary': '#2a3074',
      '--color-highlight': '#7a78dd'
    }
  },
  {
    id: 'chocolate',
    thumbnailColor: '#3f2724',
    properties: {
      '--color-bg-primary': '#3f2724',
      '--color-highlight': '#d96759'
    }
  },
  {
    id: 'laura',
    thumbnailColor: '#126673',
    properties: {
      '--color-bg-primary': '#126673',
      '--color-highlight': 'rgba(10, 244, 255, .64)'
    }
  },
  {
    id: 'dawn',
    name: 'Before the Dawn',
    thumbnailUrl: thumbDawn,
    properties: {
      '--color-highlight': '#ed5135',
      '--bg-image': `url(${bgDawn})`,
      '--color-bg-primary': '#1e2747',
      '--bg-position': 'center bottom'
    }
  },
  {
    id: 'rose-petals',
    name: '…Has Its Thorns',
    thumbnailColor: '#7d083b',
    thumbnailUrl: bgRosePetal,
    properties: {
      '--color-bg-primary': '#7d083b',
      '--bg-image': `url(${bgRosePetal})`
    }
  },
  {
    id: 'purple-waves',
    name: 'Waves of Fortune',
    thumbnailColor: '#44115c',
    thumbnailUrl: bgPurpleWaves,
    properties: {
      '--color-bg-primary': '#44115c',
      '--bg-image': `url(${bgPurpleWaves})`
    }
  },
  {
    id: 'pop-culture',
    thumbnailColor: '#ad0937',
    thumbnailUrl: thumbPopCulture,
    properties: {
      '--color-bg-primary': '#ad0937',
      '--color-highlight': 'rgba(234, 208, 110, .9)',
      '--bg-image': `url(${bgPopCulture})`
    }
  },
  {
    id: 'jungle',
    name: 'Welcome to the Jungle',
    thumbnailColor: '#0f0f03',
    thumbnailUrl: thumbJungle,
    properties: {
      '--color-bg-primary': '#0f0f03',
      '--color-highlight': '#4f9345',
      '--bg-image': `url(${bgJungle})`
    }
  },
  {
    id: 'mountains',
    name: 'Rocky Mountain High',
    thumbnailColor: '#0e2656',
    thumbnailUrl: thumbMountains,
    properties: {
      '--color-bg-primary': '#0e2656',
      '--color-highlight': '#6488c3',
      '--bg-image': `url(${bgMountains})`
    }
  },
  {
    id: 'pines',
    name: 'In the Pines',
    thumbnailColor: '#06090c',
    thumbnailUrl: thumbPines,
    properties: {
      '--color-bg-primary': '#06090c',
      '--color-highlight': '#5984b9',
      '--bg-image': `url(${bgPines})`
    }
  },
  {
    id: 'nemo',
    thumbnailColor: '#031724',
    thumbnailUrl: thumbNemo,
    properties: {
      '--color-bg-primary': '#031724',
      '--color-highlight': '#2896b8',
      '--bg-image': `url(${bgNemo})`
    }
  },
  {
    id: 'cat',
    name: 'What\'s New Pussycat?',
    thumbnailColor: '#000',
    thumbnailUrl: thumbCat,
    properties: {
      '--color-bg-primary': '#000',
      '--color-highlight': '#d26c37',
      '--bg-image': `url(${bgCat})`,
      '--bg-position': 'left'
    }
  }
] as Theme[]
