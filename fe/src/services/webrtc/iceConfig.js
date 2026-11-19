import api from '../api'

const FALLBACK_STUN_URLS = [
  'stun:stun.l.google.com:19302',
  'stun:stun1.l.google.com:19302',
  'stun:stun2.l.google.com:19302',
]

let cachedConfiguration = null
let cachedAt = 0
let cachedUserId = null
let pendingConfiguration = null
let pendingUserId = null
const CACHE_TTL_MS = 8 * 60 * 1000

function currentUserId() {
  try {
    const raw = sessionStorage.getItem('auth_user') || localStorage.getItem('auth_user')
    const user = raw ? JSON.parse(raw) : null
    return user?.id == null ? null : String(user.id)
  } catch {
    return null
  }
}

function urlsFromEnvironment() {
  const configured = String(import.meta.env.VITE_STUN_URLS || '').split(',').map((url) => url.trim()).filter(Boolean)
  return configured.length ? configured : FALLBACK_STUN_URLS
}

export async function getRtcConfiguration() {
  const userId = currentUserId()
  if (cachedConfiguration && cachedUserId === userId && Date.now() - cachedAt < CACHE_TTL_MS) return cachedConfiguration
  if (pendingConfiguration && pendingUserId === userId) return pendingConfiguration

  pendingUserId = userId
  pendingConfiguration = (async () => {
    let iceServers = [{ urls: urlsFromEnvironment() }]
    let turnConfigured = false
    try {
      const { data } = await api.get('/webrtc/ice-config')
      if (Array.isArray(data?.iceServers) && data.iceServers.length) {
        iceServers = data.iceServers
        turnConfigured = Boolean(data.turnConfigured)
      }
    } catch (error) {
      if (import.meta.env.DEV) console.warn('[WebRTC] ICE config unavailable; using STUN fallback.', error.message)
    }
    cachedConfiguration = { iceServers, turnConfigured }
    cachedAt = Date.now()
    cachedUserId = userId
    return cachedConfiguration
  })()

  try {
    return await pendingConfiguration
  } finally {
    if (pendingUserId === userId) {
      pendingConfiguration = null
      pendingUserId = null
    }
  }
}

export function clearRtcConfigurationCache() {
  cachedConfiguration = null
  cachedAt = 0
  cachedUserId = null
}
