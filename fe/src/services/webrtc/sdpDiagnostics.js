const encoder = new TextEncoder()

async function checksum(sdp) {
  const bytes = encoder.encode(sdp)
  if (globalThis.crypto?.subtle) {
    const digest = await globalThis.crypto.subtle.digest('SHA-256', bytes)
    return [...new Uint8Array(digest)].map((byte) => byte.toString(16).padStart(2, '0')).join('')
  }

  // Deterministic fallback for browsers without Web Crypto.
  let hash = 2166136261
  for (const byte of bytes) {
    hash ^= byte
    hash = Math.imul(hash, 16777619)
  }
  return `fnv1a-${(hash >>> 0).toString(16).padStart(8, '0')}`
}

export async function inspectSdp(description, label) {
  const sdp = description?.sdp
  if (typeof sdp !== 'string' || !sdp.length) {
    const error = new Error(`${label}: SDP phải là chuỗi không rỗng.`)
    error.diagnostics = { type: description?.type, valueType: typeof sdp }
    throw error
  }

  const lines = sdp.split('\r\n')
  const bareLf = (sdp.match(/(?<!\r)\n/g) || []).length
  const bareCr = (sdp.match(/\r(?!\n)/g) || []).length
  const videoIndex = lines.findIndex((line) => line.startsWith('m=video '))
  const videoSection = []
  if (videoIndex >= 0) {
    for (const line of lines.slice(videoIndex + 1)) {
      if (line.startsWith('m=')) break
      videoSection.push(line)
    }
  }
  const malformedSsrc = lines.filter((line) => line.startsWith('a=ssrc:') && !/^a=ssrc:\d+ [A-Za-z0-9-]+:.+/.test(line))
  const diagnostics = {
    type: description.type,
    length: sdp.length,
    lineCount: lines.filter(Boolean).length,
    crlfCount: (sdp.match(/\r\n/g) || []).length,
    bareLf,
    bareCr,
    endsWithCrlf: sdp.endsWith('\r\n'),
    hasV0: lines[0] === 'v=0',
    hasVideo: videoIndex >= 0,
    hasVideoMid: videoSection.some((line) => line.startsWith('a=mid:')),
    ssrcLineCount: lines.filter((line) => line.startsWith('a=ssrc:')).length,
    msidLineCount: lines.filter((line) => line.startsWith('a=msid:') || /\smsid:/.test(line)).length,
    malformedSsrcCount: malformedSsrc.length,
    checksum: await checksum(sdp),
  }

  const problems = []
  if (!diagnostics.hasV0) problems.push('thiếu dòng v=0 ở đầu SDP')
  if (!diagnostics.hasVideo) problems.push('không có media section m=video')
  if (diagnostics.hasVideo && !diagnostics.hasVideoMid) problems.push('media video thiếu a=mid')
  if (bareLf || bareCr) problems.push('có ký tự xuống dòng không theo cặp CRLF')
  if (malformedSsrc.length) problems.push(`có ${malformedSsrc.length} dòng a=ssrc sai cấu trúc`)
  if (problems.length) {
    const error = new Error(`${label}: SDP không hợp lệ: ${problems.join('; ')}.`)
    error.diagnostics = diagnostics
    throw error
  }

  return diagnostics
}

/**
 * Browsers may expose an otherwise complete SDP without its final CRLF.
 * Preserve every existing character and normalize only the required terminator
 * on a copy before handing it to setRemoteDescription().
 */
export async function prepareRemoteDescription(description, label) {
  const diagnostics = await inspectSdp(description, label)
  const prepared = diagnostics.endsWithCrlf
    ? { ...description }
    : { ...description, sdp: `${description.sdp}\r\n` }

  return { description: prepared, diagnostics }
}

export function logSdpDiagnostics(side, direction, diagnostics) {
  if (import.meta.env.DEV) {
    console.debug(`[WebRTC ${side}] SDP ${direction}`, diagnostics)
  }
}
