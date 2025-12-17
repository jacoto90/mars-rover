import { createRouter, createWebHistory } from 'vue-router'
import SimuladorRoverView from '../views/SimuladorRoverView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'simulador',
      component: SimuladorRoverView,
    },
  ],
})

export default router
