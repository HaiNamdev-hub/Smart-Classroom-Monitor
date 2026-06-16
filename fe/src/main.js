import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";

import DefaultLayout from "./layout/wrapper/default.vue";
import IndexLayout from "./layout/wrapper/index.vue";

const app = createApp(App);

app.component("default-layout", DefaultLayout);
app.component("index-layout", IndexLayout);

app.use(router);
app.mount("#app");