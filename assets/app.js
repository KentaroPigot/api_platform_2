import { createApp } from "vue";
import CheeseWhizApp from "./components/CheeseWhizApp.vue";
import "bootstrap/dist/css/bootstrap.css";

const app = createApp(CheeseWhizApp);

app.mount("#cheese-app");
