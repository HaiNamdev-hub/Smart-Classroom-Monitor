import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export function handleApiProxyError(error, request, response) {
  // Client aborts and already-closed responses are normal transport outcomes.
  // Never write a second response to a socket that has ended.
  if (!response || response.destroyed || response.writableEnded) return

  if (response.headersSent) {
    response.destroy(error instanceof Error ? error : undefined)
    return
  }

  response.writeHead(502, {
    'Content-Type': 'application/json; charset=utf-8',
    'Cache-Control': 'no-store',
    'Connection': 'close',
  })
  response.end(JSON.stringify({ error: 'API upstream connection failed.' }))
}

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        configure: proxy => proxy.on('error', handleApiProxyError),
      },
    },
  },
})
