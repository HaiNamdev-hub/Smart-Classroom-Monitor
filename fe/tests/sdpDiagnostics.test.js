import test from 'node:test'
import assert from 'node:assert/strict'
import { inspectSdp, prepareRemoteDescription } from '../src/services/webrtc/sdpDiagnostics.js'

const validSdp = [
  'v=0',
  'o=- 1 1 IN IP4 127.0.0.1',
  's=-',
  't=0 0',
  'm=video 9 UDP/TLS/RTP/SAVPF 96',
  'c=IN IP4 0.0.0.0',
  'a=mid:0',
  'a=ssrc:1234 msid:stream track',
].join('\r\n')

test('accepts valid CRLF SDP missing only the terminal CRLF and preserves its content', async () => {
  const input = { type: 'offer', sdp: validSdp }
  const rawDiagnostics = await inspectSdp(input, 'test offer')
  const { description, diagnostics } = await prepareRemoteDescription(input, 'test offer')

  assert.equal(rawDiagnostics.endsWithCrlf, false)
  assert.equal(rawDiagnostics.bareLf, 0)
  assert.equal(rawDiagnostics.bareCr, 0)
  assert.equal(description.sdp, `${validSdp}\r\n`)
  assert.equal(description.sdp.slice(0, -2), validSdp)
  assert.equal(input.sdp, validSdp)
  assert.equal(diagnostics.checksum, rawDiagnostics.checksum)
})

test('does not append another terminator when SDP already ends in CRLF', async () => {
  const sdp = `${validSdp}\r\n`
  const { description } = await prepareRemoteDescription({ type: 'answer', sdp }, 'test answer')
  assert.equal(description.sdp, sdp)
})

test('continues rejecting malformed line endings and invalid SDP structure', async () => {
  await assert.rejects(
    inspectSdp({ type: 'offer', sdp: validSdp.replaceAll('\r\n', '\n') }, 'bare LF offer'),
    /CRLF/,
  )
  await assert.rejects(
    inspectSdp({ type: 'offer', sdp: validSdp.replace('a=mid:0', 'a=other:0') }, 'missing mid'),
    /a=mid/,
  )
})
