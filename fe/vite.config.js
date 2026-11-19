import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

export function handleApiProxyError(error, request, response) {
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

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const proxyTarget = env.VITE_API_BASE_URL || 'http://127.0.0.1:8000'

  return {
    plugins: [vue()],
    server: {
      proxy: {
        '/api': {
          target: proxyTarget,
          changeOrigin: true,
          configure: proxy => proxy.on('error', handleApiProxyError),
        },
      },
    },
  }
})
