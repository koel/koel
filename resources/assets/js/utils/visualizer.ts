import Sketch from 'sketch-js'
import { audioService } from '@/services'
import { random, sample } from 'lodash'

// Audio visualization originally created by Justin Windle (@soulwire)
// as seen on https://codepen.io/soulwire/pen/Dscga

const NUM_PARTICLES = 128
const NUM_BANDS = 128
const SMOOTHING = 0.5
const SCALE = { MIN: 5.0, MAX: 80.0 }
const SPEED = { MIN: 0.2, MAX: 1.0 }
const ALPHA = { MIN: 0.8, MAX: 0.9 }
const SPIN = { MIN: 0.001, MAX: 0.005 }
const SIZE = { MIN: 0.5, MAX: 1.25 }
const TWO_PI = Math.PI * 2

const COLORS = [
  '#69D2E7',
  '#1B676B',
  '#BEF202',
  '#EBE54D',
  '#00CDAC',
  '#1693A5',
  '#F9D423',
  '#FF4E50',
  '#E7204E',
  '#0CCABA',
  '#FF006F'
] as const

type Color = typeof COLORS[number]

class AudioAnalyser {
  bandCount: number
  smoothing: number
  audio: HTMLMediaElement
  source: MediaElementAudioSourceNode
  analyser: AnalyserNode
  bands: Uint8Array
  onUpdate: Closure

  constructor (bandCount: number, smoothing: number, onUpdate: (bands: Uint8Array) => void) {
    this.bandCount = bandCount
    this.smoothing = smoothing
    this.onUpdate = onUpdate

    this.audio = audioService.getElement()
    this.source = audioService.getSource()

    this.analyser = audioService.getContext().createAnalyser()
    this.analyser.smoothingTimeConstant = this.smoothing
    this.analyser.fftSize = this.bandCount * 2

    this.bands = new Uint8Array(this.analyser.frequencyBinCount)

    this.source.connect(this.analyser)
    this.update()
  }

  update () {
    requestAnimationFrame(this.update.bind(this))

    if (!this.audio.paused) {
      this.analyser.getByteFrequencyData(this.bands)
      this.onUpdate(this.bands)
    }
  }
}

class Particle {
  x: number
  y: number
  level = 0
  scale = 0
  alpha = 0
  speed = 0
  color: Color = COLORS[0]
  size = 0
  spin = 0
  band = 0
  smoothedScale = 0
  smoothedAlpha = 0
  decayScale = 0
  decayAlpha = 0
  rotation = 0
  energy = 0

  constructor (x: number, y: number) {
    this.x = x
    this.y = y
    this.reset()
  }

  reset () {
    this.level = 1 + Math.floor(random(4))
    this.scale = random(SCALE.MIN, SCALE.MAX)
    this.alpha = random(ALPHA.MIN, ALPHA.MAX)
    this.speed = random(SPEED.MIN, SPEED.MAX)
    this.color = sample(COLORS)!
    this.size = random(SIZE.MIN, SIZE.MAX)
    this.spin = random(SPIN.MAX, SPIN.MAX)
    this.band = Math.floor(random(NUM_BANDS))

    if (Math.random() < 0.5) {
      this.spin = -this.spin
    }

    this.smoothedScale = 0.0
    this.smoothedAlpha = 0.0
    this.decayScale = 0.0
    this.decayAlpha = 0.0
    this.rotation = random(TWO_PI)
    this.energy = random(this.band / 256)
  }

  move () {
    this.rotation += this.spin
    this.y -= this.speed * this.level
  }

  draw (ctx: CanvasRenderingContext2D) {
    const power = Math.exp(this.energy)
    const scale = this.scale * power
    const alpha = this.alpha * this.energy * 2

    this.decayScale = Math.max(this.decayScale, scale)
    this.decayAlpha = Math.max(this.decayAlpha, alpha)

    this.smoothedScale += (this.decayScale - this.smoothedScale) * 0.3
    this.smoothedAlpha += (this.decayAlpha - this.smoothedAlpha) * 0.3

    this.decayScale *= 0.985
    this.decayAlpha *= 0.975

    ctx.save()
    ctx.beginPath()
    ctx.translate(this.x + Math.cos(this.rotation * this.speed) * 250, this.y)
    ctx.rotate(this.rotation)
    ctx.scale(this.smoothedScale * this.level, this.smoothedScale * this.level)
    ctx.moveTo(this.size * 0.5, 0)
    ctx.lineTo(this.size * -0.5, 0)
    ctx.lineWidth = 1
    ctx.lineCap = 'round'
    ctx.globalAlpha = this.smoothedAlpha / this.level
    ctx.strokeStyle = this.color
    ctx.stroke()
    ctx.restore()
  }
}

export default (container: HTMLElement) => {
  const particles: Particle[] = []

  Sketch.create({
    container,

    setup () {
      for (let i = 0; i < NUM_PARTICLES; ++i) {
        particles.push(new Particle(random(this.width), random(this.height)))
      }

      new AudioAnalyser(NUM_BANDS, SMOOTHING, bands => {
        // update particles based on fft transformed audio frequencies
        particles.forEach(particle => (particle.energy = bands[particle.band] / 256))
      })
    },

    draw () {
      this.globalCompositeOperation = 'lighter'

      particles.map(particle => {
        if (particle.y < (-particle.size * particle.level * particle.scale * 2)) {
          particle.reset()
          particle.x = random(this.width)
          particle.y = this.height + (particle.size * particle.scale * particle.level * 2)
        }

        particle.move()
        particle.draw(this as CanvasRenderingContext2D)
      })
    }
  })
}
