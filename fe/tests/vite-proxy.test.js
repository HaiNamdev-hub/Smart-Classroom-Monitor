import test from 'node:test'
import assert from 'node:assert/strict'
import http from 'node:http'
import { once } from 'node:events'
import { createServer } from 'vite'
import viteConfig, { handleApiProxyError } from '../vite.config.js'

function listen(server) {
  server.listen(0, '127.0.0.1')
  return once(server, 'listening').then(() => server.address().port)
}

test('Vite API proxy returns a fixed-length binary body from a close-delimited upstream', async () => {
  const bytes = Buffer.from([0xff, 0xd8, 0xff, 0x00, 0x10, 0x80, 0xff, 0xd9])
  const upstream = http.createServer((request, response) => {
    response.writeHead(200, {
      'Content-Type': 'image/jpeg',
      'Content-Length': String(bytes.length),
      'Connection': 'close',
      'Cache-Control': 'private, no-store',
    })
    response.end(bytes)
  })
  const upstreamPort = await listen(upstream)
  const config = {
    ...viteConfig,
    configFile: false,
    logLevel: 'silent',
    server: {
      ...viteConfig.server,
      host: '127.0.0.1',
      port: 0,
      proxy: {
        '/api': {
          target: `http://127.0.0.1:${upstreamPort}`,
          changeOrigin: true,
          configure: proxy => proxy.on('error', handleApiProxyError),
        },
      },
    },
  }
  const vite = await createServer(config)

  try {
    await vite.listen()
    const port = vite.httpServer.address().port
    const response = await fetch(`http://127.0.0.1:${port}/api/evidence/124/file`)
    const body = Buffer.from(await response.arrayBuffer())

    assert.equal(response.status, 200)
    assert.equal(response.headers.get('content-type'), 'image/jpeg')
    assert.equal(response.headers.get('content-length'), String(bytes.length))
    assert.deepEqual(body, bytes)
  } finally {
    await vite.close()
    await new Promise(resolve => upstream.close(resolve))
  }
})
