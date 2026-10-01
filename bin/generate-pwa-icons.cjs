const fs = require('node:fs');
const path = require('node:path');
const { PNG } = require('pngjs');

const color = [18, 107, 88];
const white = [255, 255, 255];
const out = path.join(__dirname, '..', 'public', 'icons');
fs.mkdirSync(out, { recursive: true });

function curve(a, b, c, d, steps = 24) {
  return Array.from({ length: steps }, (_, i) => {
    const t = (i + 1) / steps;
    const m = 1 - t;
    return [m ** 3 * a[0] + 3 * m ** 2 * t * b[0] + 3 * m * t ** 2 * c[0] + t ** 3 * d[0],
      m ** 3 * a[1] + 3 * m ** 2 * t * b[1] + 3 * m * t ** 2 * c[1] + t ** 3 * d[1]];
  });
}

const drop = [[0.5, 0.14],
  ...curve([0.5, 0.14], [0.43, 0.31], [0.22, 0.44], [0.22, 0.59]),
  ...curve([0.22, 0.59], [0.22, 0.76], [0.34, 0.86], [0.5, 0.86]),
  ...curve([0.5, 0.86], [0.66, 0.86], [0.78, 0.76], [0.78, 0.59]),
  ...curve([0.78, 0.59], [0.78, 0.44], [0.57, 0.31], [0.5, 0.14])];

function inside(x, y) {
  let result = false;
  for (let i = 0, j = drop.length - 1; i < drop.length; j = i++) {
    const [xi, yi] = drop[i];
    const [xj, yj] = drop[j];
    if ((yi > y) !== (yj > y) && x < ((xj - xi) * (y - yi)) / (yj - yi) + xi) result = !result;
  }
  return result;
}

for (const size of [180, 192, 512]) {
  const png = new PNG({ width: size, height: size });
  for (let y = 0; y < size; y++) {
    for (let x = 0; x < size; x++) {
      let coverage = 0;
      for (let sy = 0; sy < 2; sy++) for (let sx = 0; sx < 2; sx++) {
        const u = (x + (sx + 0.5) / 2) / size;
        const v = (y + (sy + 0.5) / 2) / size;
        if (inside(u, v) && (u - 0.5) ** 2 + (v - 0.64) ** 2 > 0.065 ** 2) coverage++;
      }
      const offset = (y * size + x) * 4;
      for (let channel = 0; channel < 3; channel++) png.data[offset + channel] = Math.round(color[channel] + (white[channel] - color[channel]) * coverage / 4);
      png.data[offset + 3] = 255;
    }
  }
  fs.writeFileSync(path.join(out, `ceklaundry-${size}.png`), PNG.sync.write(png));
}
