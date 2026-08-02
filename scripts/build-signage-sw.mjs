import { build } from 'esbuild';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

await build({
  absWorkingDir: root,
  entryPoints: ['./resources/js/signage-sw.js'],
  outfile: path.join(root, 'public/signage-sw.js'),
  bundle: true,
  format: 'iife',
  minify: true,
  legalComments: 'none',
  target: ['chrome100', 'firefox100', 'safari15'],
});
