export default {
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
      z = sin(u_data_arr[int(floor_x)] / 50.0 + u_data_arr[int(floor_y)] / 20.0) * u_amplitude * 2.0;
      gl_Position = projectionMatrix * modelViewMatrix * vec4(position.x, position.y, z, 1.0);
    }
  `,
  fragment: `
    varying float x;
    varying float y;
    varying float z;
    varying vec3 vUv;
    uniform float u_time;
    void main() {
      gl_FragColor = vec4((32.0 - abs(x)) / 32.0, (32.0 - abs(y)) / 32.0, (abs(x + y) / 2.0) / 32.0, 1.0);
    }
  `
}
