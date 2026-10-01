import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
  title: (title) => `${title} · CekLaundry`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')),
  setup({ el, App, props }) { createRoot(el).render(<App {...props} />); },
  progress: { color: '#126b58' },
});

if ('serviceWorker' in navigator && (location.protocol === 'https:' || ['localhost', '127.0.0.1'].includes(location.hostname))) {
  let hadController = Boolean(navigator.serviceWorker.controller);
  const build = new URL(import.meta.url).pathname.split('/').pop() ?? 'app';
  navigator.serviceWorker.register(`/sw.js?v=${encodeURIComponent(build)}`).then(registration => {
    void registration.update();
    const watch = (worker: ServiceWorker | null) => {
      if (!worker) return;
      worker.addEventListener('statechange', () => {
        if (worker.state === 'installed' && navigator.serviceWorker.controller) {
          window.dispatchEvent(new Event('pwa-update-ready'));
        }
      });
    };
    registration.addEventListener('updatefound', () => watch(registration.installing));
    if (registration.waiting) window.dispatchEvent(new Event('pwa-update-ready'));
  }).catch(() => { /* Panel tetap berfungsi bila PWA tidak tersedia. */ });
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (hadController) window.dispatchEvent(new Event('pwa-controller-changed'));
    hadController = true;
  });
}
