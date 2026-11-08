
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Sửa đường dẫn import cho đúng vị trí file
// checkToken.js thực tế của dự án.
import { getToken } from '../router/checkToken'

window.Pusher = Pusher

const scheme =
    import.meta.env.VITE_REVERB_SCHEME || 'http'

const apiBaseUrl = (
    import.meta.env.VITE_API_URL ||
    'http://127.0.0.1:8000'
).replace(/\/$/, '')

const echo = new Echo({
    broadcaster: 'reverb',

    key: import.meta.env.VITE_REVERB_APP_KEY,

    wsHost:
        import.meta.env.VITE_REVERB_HOST ||
        '127.0.0.1',

    wsPort: Number(
        import.meta.env.VITE_REVERB_PORT || 8080
    ),

    wssPort: Number(
        import.meta.env.VITE_REVERB_PORT || 8080
    ),

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
