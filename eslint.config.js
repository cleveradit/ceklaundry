import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import globals from 'globals';

export default tseslint.config(
  { ignores: ['vendor/**', 'node_modules/**', 'public/build/**', 'bootstrap/**', 'storage/**'] },
  js.configs.recommended,
  ...tseslint.configs.recommended,
  { files: ['**/*.{js,ts,tsx}'], languageOptions: { globals: { ...globals.browser, ...globals.node } } },
);
