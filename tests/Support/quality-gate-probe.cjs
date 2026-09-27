const fs = require('node:fs');
const { spawnSync } = require('node:child_process');
const file = 'resources/js/quality-probe.ts';
if (fs.existsSync(file)) throw new Error('Probe would overwrite a file');
try {
  fs.writeFileSync(file, 'const invalid: number = "invalid"; export { invalid };\n');
  const types = spawnSync('npm', ['run', 'typecheck'], { encoding: 'utf8' });
  if (types.status === 0 || !types.stdout.includes('TS2322')) throw new Error('Type gate did not reject injected error');
  fs.writeFileSync(file, 'const unusedProbe = 1;\n');
  const lint = spawnSync('npm', ['run', 'lint'], { encoding: 'utf8' });
  if (lint.status === 0 || !lint.stdout.includes('no-unused-vars')) throw new Error('Lint gate did not reject injected error');
  console.log('PASS: disposable type/lint faults both exit nonzero.');
} finally {
  fs.unlinkSync(file);
}
