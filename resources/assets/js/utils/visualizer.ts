/* eslint no-undef: 0 */
import Sketch from 'sketch-js'
import { audio as audioService } from '@/services'
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
]
const TWO_PI = Math.PI * 2

class AudioAnalyser {
  numBands: number
  smoothing: number
  audio: HTMLMediaElement
  context: AudioContext
  source: any
  jsNode: any
  analyser: any
  bands: Uint8Array
  onUpdate: any

  constructor (numBands = 256, smoothing = 0.3) {
    this.numBands = numBands
    this.smoothing = smoothing

    this.audio = audioService.getElement()
    this.context = audioService.getContext()
    this.source = audioService.getSource()
    this.jsNode = this.context.createScriptProcessor(2048, 1, 1)

    this.analyser = this.context.createAnalyser()
    this.analyser.smoothingTimeConstant = this.smoothing
    this.analyser.fftSize = this.numBands * 2

    this.bands = new Uint8Array(this.analyser.frequencyBinCount)

    this.source.connect(this.analyser)
    this.analyser.connect(this.jsNode)

    this.jsNode.connect(this.context.destination)
    this.source.connect(this.context.destination)

    this.jsNode.onaudioprocess = () => {
      this.analyser.getByteFrequencyData(this.bands)

      if (!this.audio.paused) {
        return typeof this.onUpdate === 'function' ? this.onUpdate(this.bands) : undefined
      }
    }
  }
}

class Particle {
  x: number
  y: number
  level: any
  scale: any
  alpha: any
  speed: any
  color: any
  size: any
  spin: any
  band: any
  smoothedScale: number = 0
  smoothedAlpha: number = 0
  decayScale: number = 0
  decayAlpha: number = 0
  rotation: any = 0
  energy: number = 0

  constructor (x = 0, y = 0) {
    this.x = x
    this.y = y
    this.reset()
  }

  reset (): number {
    this.level = 1 + Math.floor(random(4))
    this.scale = random(SCALE.MIN, SCALE.MAX)
    this.alpha = random(ALPHA.MIN, ALPHA.MAX)
    this.speed = random(SPEED.MIN, SPEED.MAX)
    this.color = sample(COLORS)
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
    this.energy = 0.0

    return this.energy
  }

  move (): number {
    this.rotation += this.spin
    this.y -= this.speed * this.level

    return this.y
  }

  draw (ctx: any) {
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

    return ctx.restore()
  }
}

export default (container: HTMLElement): void => {
  Sketch.create({
    container,
    particles: [],
    setup () {
      // generate some particles
      for (let i = 0; i < NUM_PARTICLES; i++) {
        let particle = new Particle(random(this.width), random(this.height))
        particle.energy = random(particle.band / 256)

        this.particles.push(particle)
      }

      // setup the audio analyser
      const analyser = new AudioAnalyser(NUM_BANDS, SMOOTHING)

      // update particles based on fft transformed audio frequencies
      analyser.onUpdate = (bands: Uint8Array) => this.particles.map((particle: Particle): Particle => {
        particle.energy = bands[particle.band] / 256

        return particle
      })
    },

    draw () {
      this.globalCompositeOperation = 'lighter'

      return this.particles.map((particle: Particle) => {
        if (particle.y < (-particle.size * particle.level * particle.scale * 2)) {
          particle.reset()
          particle.x = random(this.width)
          particle.y = this.height + (particle.size * particle.scale * particle.level * 2)
        }

        particle.move()

        return particle.draw(this)
      })
    }
  })
}
