import { createApp } from "vue"
import App from "./App.vue"
import router from "./router"
import axios from "axios"

// Thiết lập baseURL và timeout toàn cục cho axios
const apiBase = import.meta.env.VITE_API_BASE_URL || ""
if (apiBase) {
  axios.defaults.baseURL = apiBase
}
axios.defaults.timeout = 65000

const app = createApp(App)

app.use(router)

app.mount("#app")
