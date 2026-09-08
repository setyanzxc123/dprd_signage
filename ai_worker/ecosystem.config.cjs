const path = require('node:path');

module.exports = {
  apps: [
    {
      name: 'dprd-ai-worker',
      cwd: __dirname,
      script: path.resolve(__dirname, 'worker.js'),
      args: '--daemon',
      exec_mode: 'fork',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        NODE_ENV: 'production',
      },
    },
  ],
};
