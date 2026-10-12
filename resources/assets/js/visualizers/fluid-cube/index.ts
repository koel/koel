import { configureAnalyser } from '@/visualizers/analyser'
import { startFrameLoop } from '@/visualizers/frameLoop'
import { logger } from '@/utils/logger'
import shaders from './shaders'

export const init = async (container: HTMLElement) => {
  const canvas = container.appendChild(document.createElement('canvas'))
  const glow = container.appendChild(document.createElement('canvas'))
  canvas.style.cssText = 'position: absolute; inset: 0; width: 100%; height: 100%'
  glow.style.cssText = `${canvas.style.cssText}; filter: blur(4px); mix-blend-mode: screen`

  const gl = canvas.getContext('webgl')!
  const glowContext = glow.getContext('2d')!
  const cubeSize = 15

  const analyzer = configureAnalyser({ fftSize: 128, smoothing: 0.6 })

  const spectrumData = new Uint8Array(analyzer.frequencyBinCount)

  const compileShader = (type, source) => {
    const shader = gl.createShader(type)!
    gl.shaderSource(shader, source)
    gl.compileShader(shader)

    const status = gl.getShaderParameter(shader, gl.COMPILE_STATUS)
    if (status) {
      return shader
    }

    logger.error('shader compile error', gl.getShaderInfoLog(shader))
    gl.deleteShader(shader)
  }

  const createProgram = function (vertexShader, fragmentShader) {
    const program = gl.createProgram()!
    gl.attachShader(program, vertexShader)
    gl.attachShader(program, fragmentShader)
    gl.linkProgram(program)

    const status = gl.getProgramParameter(program, gl.LINK_STATUS)
    if (status) {
      return program
    }

    logger.error('program link error', gl.getProgramInfoLog(program))
    gl.deleteProgram(program)
  }

  const vertexShader = compileShader(gl.VERTEX_SHADER, shaders.vertex(cubeSize))
  const fragmentShader = compileShader(gl.FRAGMENT_SHADER, shaders.fragment())

  const program = createProgram(vertexShader, fragmentShader)!

  const aPosition = gl.getAttribLocation(program, 'a_pos')
  const uResolution = gl.getUniformLocation(program, 'u_res')
  const uFrame = gl.getUniformLocation(program, 'u_frame')
  const uSpectrumValue = gl.getUniformLocation(program, 'u_spectrumValue')

  const vertices: number[] = []
  const vertexBuffer = gl.createBuffer()
  let frame = 0

  const render = () => {
    frame++

    analyzer.getByteFrequencyData(spectrumData)

    // Transfer spectrum data to shader program
    gl.uniform1iv(uSpectrumValue, spectrumData)

    if (canvas.width !== canvas.offsetWidth || canvas.height !== canvas.offsetHeight) {
      canvas.width = canvas.offsetWidth
      canvas.height = canvas.offsetHeight
      glow.width = canvas.width
      glow.height = canvas.height
      gl.uniform2fv(uResolution, [canvas.width, canvas.height])
      gl.viewport(0, 0, canvas.width, canvas.height)
    }

    gl.uniform1f(uFrame, frame)
    gl.clear(gl.COLOR_BUFFER_BIT)
    gl.drawArrays(gl.POINTS, 0, vertices.length / 3)

    glowContext.drawImage(canvas, 0, 0)
  }

  gl.clearColor(0, 0, 0, 1)
  gl.viewport(0, 0, canvas.width, canvas.height)
  gl.useProgram(program)
  gl.uniform2fv(uResolution, new Float32Array([canvas.width, canvas.height]))

  for (let i = 0; i < cubeSize ** 3; i++) {
    let x = (i % cubeSize)
    let y = Math.floor(i / cubeSize) % cubeSize
    let z = Math.floor(i / cubeSize ** 2)
    x -= cubeSize / 2 - 0.5
    y -= cubeSize / 2 - 0.5
    z -= cubeSize / 2 - 0.5

    vertices.push(x)
    vertices.push(y)
    vertices.push(z)
  }

  gl.enableVertexAttribArray(aPosition)
  gl.bindBuffer(gl.ARRAY_BUFFER, vertexBuffer)
  gl.vertexAttribPointer(aPosition, 3, gl.FLOAT, false, 0, 0)
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(vertices), gl.STATIC_DRAW)

  const stopFrameLoop = startFrameLoop(render)

  return () => {
    stopFrameLoop()
    gl.getExtension('WEBGL_lose_context')?.loseContext()
  }
}
