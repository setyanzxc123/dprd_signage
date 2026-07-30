import { build } from 'esbuild';
import { copyFile, mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

await build({
  absWorkingDir: root,
  entryPoints: ['./resources/js/admin/settings-upload.js'],
  outfile: path.join(root, 'public/assets/js/admin/settings-upload.js'),
  bundle: true,
  format: 'iife',
  minify: true,
  target: ['es2020'],
});

const statusBarCssOutput = path.join(root, 'public/assets/css/admin/uppy-status-bar.min.css');
await mkdir(path.dirname(statusBarCssOutput), { recursive: true });
await copyFile(
  path.join(root, 'node_modules/@uppy/status-bar/dist/style.min.css'),
  statusBarCssOutput,
);
