import { build } from 'esbuild';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

await build({
  absWorkingDir: root,
  entryPoints: ['./resources/js/admin/wa-template-editor.js'],
  outfile: path.join(root, 'public/assets/js/admin/wa-template-editor.js'),
  bundle: true,
  format: 'iife',
  minify: true,
});
