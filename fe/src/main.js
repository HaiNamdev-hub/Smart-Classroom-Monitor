
import { createApp } from "vue"
import App from "./App.vue"
import router from "./router"
import './services/reverb'

// Bootstrap CSS
import "bootstrap/dist/css/bootstrap.min.css"

// CSS riêng của dự án (nếu có)
// import "./style.css"

const app = createApp(App)

app.use(router)

app.mount("#app")
