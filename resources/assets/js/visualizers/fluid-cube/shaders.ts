export default {
  vertex: (cubeSize: number) => `
  attribute vec3 a_pos;
  uniform vec2 u_res;
  uniform float u_frame;
  uniform int u_spectrumValue[128];
  varying float v_frame;
  varying vec3 vv_pos;
  void main () {
  	v_frame = u_frame;
  	float pi = 3.141592653589793;
  	float rad = u_frame / 2.0 / 180.0 * pi;
    int spectrumIndex = 12 + int(mod(a_pos.x + ${Math.floor(cubeSize / 2)}.0, ${cubeSize}.0) + mod(a_pos.y + ${Math.floor(cubeSize / 2)}.0, ${cubeSize ** 2}.0) + (a_pos.z + ${Math.floor(cubeSize / 2)}.0) / ${cubeSize ** 2}.0);
    float value = float(u_spectrumValue[spectrumIndex]) / 100.0;
  	vec3 v_pos = a_pos;
    vec3 t = vec3(1, 1, 1);

    vv_pos = v_pos;
  	float dist = abs(${Math.floor(cubeSize / 2)}.0 - sqrt(vv_pos.x * vv_pos.x + vv_pos.y * vv_pos.y + vv_pos.z * vv_pos.z));

    t.x = v_pos.x * cos(rad) + v_pos.z * sin(rad);
    t.y = v_pos.y;
    t.z = - v_pos.x * sin(rad) + v_pos.z * cos(rad);

    v_pos = t;


    t.x = v_pos.x * cos(rad) - v_pos.y * sin(rad);
    t.y = v_pos.x * sin(rad) + v_pos.y * cos(rad);
    t.z = v_pos.z;

    v_pos = t;

    t.x = v_pos.x;
    t.y = v_pos.y * cos(rad) - v_pos.z * sin(rad);
    t.z = v_pos.y * sin(rad) + v_pos.z * cos(rad);

    v_pos = t;

    v_pos.z -= 20.0;

    // Make reaction on spectrum
    v_pos.z += value * dist;
    v_pos.y += value / 100.0;

    v_pos.x += sin(u_frame / 30.0 + v_pos.y / 4.0) * 1.2;
    v_pos.y += cos(u_frame / 20.0 + v_pos.z / 5.0) * 1.0;


    v_pos.x /= v_pos.z;
    v_pos.y /= v_pos.z;

    v_pos.x /= u_res.x / u_res.y;

  	gl_Position = vec4(v_pos.xy, 0.0, 1.0);
    gl_PointSize = dist;
  }`,
  fragment: () => `
	precision mediump float;
  uniform vec4 u_color;
  varying float v_frame;
  varying vec3 vv_pos;
  float hue2rgb(float f1, float f2, float hue) {
      if (hue < 0.0)
          hue += 1.0;
      else if (hue > 1.0)
          hue -= 1.0;
      float res;
      if ((6.0 * hue) < 1.0)
          res = f1 + (f2 - f1) * 6.0 * hue;
      else if ((2.0 * hue) < 1.0)
          res = f2;
      else if ((3.0 * hue) < 2.0)
          res = f1 + (f2 - f1) * ((2.0 / 3.0) - hue) * 6.0;
      else
          res = f1;
      return res;
  }

  vec3 hsl2rgb(vec3 hsl) {
      vec3 rgb;

      if (hsl.y == 0.0) {
          rgb = vec3(hsl.z); // Luminance
      } else {
          float f2;

          if (hsl.z < 0.5)
              f2 = hsl.z * (1.0 + hsl.y);
          else
              f2 = hsl.z + hsl.y - hsl.y * hsl.z;

          float f1 = 2.0 * hsl.z - f2;

          rgb.r = hue2rgb(f1, f2, hsl.x + (1.0/3.0));
          rgb.g = hue2rgb(f1, f2, hsl.x);
          rgb.b = hue2rgb(f1, f2, hsl.x - (1.0/3.0));
      }
      return rgb;
  }

  vec3 hsl2rgb(float h, float s, float l) {
      return hsl2rgb(vec3(h, s, l));
  }
  void main () {
  	float dist = sqrt(vv_pos.x * vv_pos.x + vv_pos.y * vv_pos.y + vv_pos.z * vv_pos.z);
    float i_frame = mod(v_frame + dist * 20.0, 360.0);
  	gl_FragColor = vec4(hsl2rgb((i_frame) / 360.0, 1.0, .5), 1.0);
  }`
}
