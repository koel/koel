const shaders = {
  vertex: `
        varying float x;
        varying float y;
        varying float z;
        varying vec3 vUv;
        uniform float u_time;
        uniform float u_amplitude;
        uniform float[64] u_data_arr;
        void main() {
          vUv = position;
          x = abs(position.x);
          y = abs(position.y);
          float floor_x = round(x);
          float floor_y = round(y);
          float x_multiplier = (64.0 - x) / 4.0;
          float y_multiplier = (64.0 - y) / 4.0;
          z = sin(u_data_arr[int(floor_x)] / 40.0 + u_data_arr[int(floor_y)] / 40.0) * u_amplitude;
          gl_Position = projectionMatrix * modelViewMatrix * vec4(position.x, position.y, z, 1.0);
        }
    `,
  fragment: `
        varying float x;
        varying float y;
        varying float z;
        varying vec3 vUv;
        uniform float u_time;
        uniform float[64] u_data_arr;
        void main() {
          // gl_FragColor = vec4((u_data_arr[32])/205.0, 0, (u_data_arr[8])/205.0, 1.0);
          gl_FragColor = vec4((64.0 - abs(x)) / 32.0, (32.0 - abs(y)) / 32.0, (abs(x + y) / 2.0) / 32.0, 1.0);
        }
    `
}

export default shaders
