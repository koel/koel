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
import thumbMono from '../img/themes/thumbnails/mono.avif'

export default [
  {
    id: 'classic',
    name: 'Classic',
    thumbnail_color: '#181818',
  },
  {
    id: 'mono',
    name: 'Mono',
    thumbnail_color: '#181818',
    thumbnail_image: thumbMono,
    properties: {
      '--font-family': 'ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", monospace',
      '--color-highlight': '#bc2e2e',
    },
  },
  {
    id: 'violet',
    name: 'Violet',
    thumbnail_color: '#31094e',
    properties: {
      '--color-bg': '#31094e',
      '--color-highlight': '#c23de5',
    },
  },
  {
    id: 'oak',
    name: 'Oak',
    thumbnail_color: '#560d25',
    properties: {
      '--color-bg': '#560d25',
      '--color-highlight': '#fd4b67',
    },
  },
  {
    id: 'slate',
    name: 'Slate',
    thumbnail_color: '#29434e',
    properties: {
      '--color-bg': '#29434e',
      '--color-highlight': '#6c8b99',
    },
  },
  {
    id: 'madison',
    name: 'Madison',
    thumbnail_color: '#0e3463',
    properties: {
      '--color-bg': '#0e3463',
      '--color-highlight': '#fbab18',
    },
  },
  {
    id: 'astronaut',
    name: 'Astronaut',
    thumbnail_color: '#2a3074',
    properties: {
      '--color-bg': '#2a3074',
      '--color-highlight': '#7a78dd',
    },
  },
  {
    id: 'chocolate',
    name: 'Chocolate',
    thumbnail_color: '#3f2724',
    properties: {
      '--color-bg': '#3f2724',
      '--color-highlight': '#d96759',
    },
  },
  {
    id: 'laura',
    name: 'Laura',
    thumbnail_color: '#126673',
    properties: {
      '--color-bg': '#126673',
      '--color-highlight': 'rgba(10, 244, 255, .64)',
    },
  },
  {
    id: 'dawn',
    name: 'Before the Dawn',
    thumbnail_image: thumbDawn,
    properties: {
      '--color-highlight': '#ed5135',
      '--bg-image': `url(${bgDawn})`,
      '--color-bg': '#1e2747',
      '--bg-position': 'center bottom',
    },
  },
  {
    id: 'rose-petals',
    name: '…Has Its Thorns',
    thumbnail_color: '#7d083b',
    thumbnail_image: bgRosePetal,
    properties: {
      '--color-bg': '#7d083b',
      '--bg-image': `url(${bgRosePetal})`,
    },
  },
  {
    id: 'purple-waves',
    name: 'Fortune Waves',
    thumbnail_color: '#44115c',
    thumbnail_image: bgPurpleWaves,
    properties: {
      '--color-bg': '#44115c',
      '--bg-image': `url(${bgPurpleWaves})`,
    },
  },
  {
    id: 'pop-culture',
    name: 'Pop Culture',
    thumbnail_color: '#ad0937',
    thumbnail_image: thumbPopCulture,
    properties: {
      '--color-bg': '#ad0937',
      '--color-highlight': 'rgba(234, 208, 110, .9)',
      '--bg-image': `url(${bgPopCulture})`,
    },
  },
  {
    id: 'jungle',
    name: 'To the Jungle',
    thumbnail_color: '#0f0f03',
    thumbnail_image: thumbJungle,
    properties: {
      '--color-bg': '#0f0f03',
      '--color-highlight': '#4f9345',
      '--bg-image': `url(${bgJungle})`,
    },
  },
  {
    id: 'mountains',
    name: 'Rocky Mountain High',
    thumbnail_color: '#0e2656',
    thumbnail_image: thumbMountains,
    properties: {
      '--color-bg': '#0e2656',
      '--color-highlight': '#6488c3',
      '--bg-image': `url(${bgMountains})`,
    },
  },
  {
    id: 'pines',
    name: 'In the Pines',
    thumbnail_color: '#06090c',
    thumbnail_image: thumbPines,
    properties: {
      '--color-bg': '#06090c',
      '--color-highlight': '#5984b9',
      '--bg-image': `url(${bgPines})`,
    },
  },
  {
    id: 'nemo',
    name: 'Nemo',
    thumbnail_color: '#031724',
    thumbnail_image: thumbNemo,
    properties: {
      '--color-bg': '#031724',
      '--color-highlight': '#2896b8',
      '--bg-image': `url(${bgNemo})`,
    },
  },
  {
    id: 'cat',
    name: 'What\'s New Pussycat?',
    thumbnail_color: '#000',
    thumbnail_image: thumbCat,
    properties: {
      '--color-bg': '#000',
      '--color-highlight': '#d26c37',
      '--bg-image': `url(${bgCat})`,
      '--bg-position': 'left',
    },
  },
] as Theme[]
