import { copyFile, mkdir, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';

const files = [
  ['node_modules/vue/dist/vue.global.prod.js', 'public/assets/vendor/vue/vue.global.prod.js'],
  ['node_modules/lucide/dist/umd/lucide.min.js', 'public/assets/vendor/lucide/lucide.min.js'],
  ['node_modules/flatpickr/dist/flatpickr.min.css', 'public/assets/vendor/flatpickr/flatpickr.min.css'],
  ['node_modules/flatpickr/dist/flatpickr.min.js', 'public/assets/vendor/flatpickr/flatpickr.min.js'],
  ['node_modules/flatpickr/dist/l10n/id.js', 'public/assets/vendor/flatpickr/l10n/id.js'],
  ['node_modules/qrcodejs/qrcode.min.js', 'public/assets/vendor/qrcodejs/qrcode.min.js'],
];

const fonts = [
  ['Inter', 'inter', [400, 500, 600, 700, 800, 900]],
  ['Outfit', 'outfit', [400, 500, 600, 700, 800]],
  ['IBM Plex Mono', 'ibm-plex-mono', [400, 500, 700]],
];

for (const [source, target] of files) {
  const sourcePath = resolve(source);
  const targetPath = resolve(target);

  await mkdir(dirname(targetPath), { recursive: true });
  await copyFile(sourcePath, targetPath);
  console.log(`${source} -> ${target}`);
}

let fontCss = '';

for (const [family, packageName, weights] of fonts) {
  for (const weight of weights) {
    const fileName = `${packageName}-latin-${weight}-normal.woff2`;
    const source = `node_modules/@fontsource/${packageName}/files/${fileName}`;
    const target = `public/assets/vendor/fonts/files/${fileName}`;

    await mkdir(dirname(resolve(target)), { recursive: true });
    await copyFile(resolve(source), resolve(target));
    console.log(`${source} -> ${target}`);

    fontCss += [
      '@font-face {',
      `  font-family: "${family}";`,
      `  font-style: normal;`,
      `  font-weight: ${weight};`,
      '  font-display: swap;',
      `  src: url("./files/${fileName}") format("woff2");`,
      '}',
      '',
    ].join('\n');
  }
}

await writeFile(resolve('public/assets/vendor/fonts/fonts.css'), fontCss);
console.log('generated public/assets/vendor/fonts/fonts.css');
