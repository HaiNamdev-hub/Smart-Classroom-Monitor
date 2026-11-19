import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Import helper lấy token từ checkToken.js
import { getToken } from '../router/checkToken'

window.Pusher = Pusher

const isProduction =
    typeof window !== 'undefined' &&
    window.location.hostname !== 'localhost' &&
    window.location.hostname !== '127.0.0.1'

const scheme =
    import.meta.env.VITE_REVERB_SCHEME || (isProduction ? 'https' : 'http')

const rawApiBase =
    import.meta.env.VITE_API_BASE_URL ||
    import.meta.env.VITE_API_URL ||
    (isProduction ? 'https://smart-classroom-monitor.onrender.com' : 'http://127.0.0.1:8000')

const apiBaseUrl = rawApiBase.replace(/\/+$/, '')

const defaultWsHost = isProduction
    ? 'smart-classroom-monitor.onrender.com'
    : '127.0.0.1'

const rawWsHost =
    import.meta.env.VITE_REVERB_HOST ||
    import.meta.env.VITE_PUSHER_HOST

// Khi chạy trên web Vercel, tự động trỏ về Render domain nếu bị set nhầm localhost
const wsHost =
    isProduction && (!rawWsHost || rawWsHost === 'localhost' || rawWsHost === '127.0.0.1')
        ? defaultWsHost
        : (rawWsHost || defaultWsHost)

const wsPort = Number(
    import.meta.env.VITE_REVERB_PORT ||
    import.meta.env.VITE_PUSHER_PORT ||
    (isProduction ? 443 : 8080)
)

const wssPort = Number(
    import.meta.env.VITE_REVERB_PORT ||
    import.meta.env.VITE_PUSHER_PORT ||
    443
)

const echo = new Echo({
    broadcaster: 'reverb',

    key:
        import.meta.env.VITE_REVERB_APP_KEY ||
        import.meta.env.VITE_PUSHER_APP_KEY ||
        'wb5jfofeqpobmdzyoz0i',

    wsHost,
    wsPort,
    wssPort,

    forceTLS: scheme === 'https',

    enabledTransports:
        scheme === 'https' ? ['wss'] : ['ws'],

    // Xác thực Private Channel bằng Sanctum
    authorizer: (channel) => ({
        authorize: async (socketId, callback) => {
            const token = getToken()

            if (!token) {
                callback(
                    new Error('Bạn chưa đăng nhập.'),
                    null
                )
                return
            }

            try {
                const response = await fetch(
                    `${apiBaseUrl}/api/broadcasting/auth`,
                    {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`,
                        },
                        body: JSON.stringify({
                            socket_id: socketId,
                            channel_name: channel.name,
                        }),
                    }
                )

                const data = await response.json()

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                        `Không thể xác thực kênh: ${response.status}`
                    )
                }

                callback(null, data)
            } catch (error) {
                console.error(
                    'Lỗi xác thực Private Channel:',
                    error
                )

                callback(error, null)
            }
        },
    }),
})

// Kết nối WebSocket thành công
echo.connector.pusher.connection.bind(
    'connected',
    () => {
        console.log(
            '✅ Đã kết nối Laravel Reverb thành công!'
        )
    }
)

// Mất kết nối WebSocket
echo.connector.pusher.connection.bind(
    'disconnected',
    () => {
        console.warn(
            '⚠️ Đã ngắt kết nối Laravel Reverb'
        )
    }
)

// Lỗi WebSocket
echo.connector.pusher.connection.bind(
    'error',
    (error) => {
        console.error(
            '❌ Lỗi kết nối Laravel Reverb:',
            error
        )
    }
)

export default echo