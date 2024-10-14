import './bootstrap';
import 'bootstrap'

import {createApp} from "vue";
import ClientForm from './Components/ClientForm.vue';
import DriverForm from './Components/DriverForm.vue';

const app = createApp({});

app.component('client-form', ClientForm)
app.component('driver-form', DriverForm)

app.mount("#app");
