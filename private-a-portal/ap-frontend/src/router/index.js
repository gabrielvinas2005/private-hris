import { createRouter, createWebHistory } from "vue-router";

// Import components
import Home from "../views/home.vue";
import Registration from "../views/auth/registration.vue";
import Login from "../views/auth/Login.vue";
import VerifyOTP from "../views/auth/VerifyOTP.vue";
import ChangePassword from "../views/auth/ChangePassword.vue";
import ResetPassword from "../views/auth/ResetPassword.vue";
import ApplicantDashboard from "../views/ApplicantDashboard.vue";
import PositionsPage from "../views/PositionsPage.vue";
import ExaminationPage from "../views/ExaminationPage.vue";
import ExaminationResult from "../views/ExaminationResult.vue";

// Define routes
const routes = [
  {
    path: "/",
    name: "Home",
    component: Home,
  },
  {
    path: "/login",
    name: "Login",
    component: Login,
  },
  {
    path: "/registration",
    name: "Registration",
    component: Registration,
  },

  {
    path: "/verify-otp",
    name: "VerifyOTP",
    component: VerifyOTP,
    meta: { requiresAuth: true },
  },
  {
    path: "/change-password",
    name: "ChangePassword",
    component: ChangePassword,
    meta: { requiresAuth: true },
  },
  {
    path: "/reset-password",
    name: "ResetPassword",
    component: ResetPassword,
  },
  {
    path: "/dashboard",
    name: "Dashboard",
    component: ApplicantDashboard,
    meta: { requiresAuth: true },
  },
  {
    path: "/positions",
    name: "Positions",
    component: PositionsPage,
    meta: { requiresAuth: true },
  },
  {
    path: "/exam/:id",
    name: "Examination",
    component: ExaminationPage,
    meta: { requiresAuth: true },
  },
  {
    path: "/exam-result/:id",
    name: "ExaminationResult",
    component: ExaminationResult,
    meta: { requiresAuth: true },
  },
  // Catch all route - redirect to home
  {
    path: "/:pathMatch(.*)*",
    redirect: "/",
  },
];

// Create router
const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Route guard - protect routes that require authentication
router.beforeEach((to, from, next) => {
  // Check if route requires authentication
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);

  if (requiresAuth) {
    // Check authentication token
    const tokenData = localStorage.getItem("auth_token");

    if (!tokenData) {
      // No token, redirect to login
      next({ name: "Login", query: { redirect: to.fullPath } });
      return;
    }

    // Validate token format and expiration
    try {
      const parsed = JSON.parse(tokenData);
      if (parsed.expiresAt && Date.now() > parsed.expiresAt) {
        // Token expired, clear and redirect to login
        localStorage.removeItem("auth_token");
        localStorage.removeItem("user_data");
        next({ name: "Login", query: { redirect: to.fullPath } });
        return;
      }
      // Token is valid
      next();
    } catch (error) {
      // Invalid token format, but might be old format - allow through
      // The API will handle invalid tokens
      next();
    }
  } else {
    // Route doesn't require auth, allow access
    next();
  }
});

export default router;
