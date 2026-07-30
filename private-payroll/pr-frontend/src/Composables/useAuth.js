import { ref, computed } from "vue";
import { ElMessage } from "element-plus";
import { authApi, setSuppress401Clear } from "@/services/api";

const user = ref(null);
const loading = ref(false);

let lastPayrollKeysFetchAt = 0;
let authBootstrapped = false;
let ePortalRedirect = false;
const PAYROLL_KEYS_EMPTY_REFETCH_MS = 8000;
const PAYROLL_KEYS_REFRESH_MS = 15000;

function saveAuthToken(token) {
  localStorage.setItem(
    "auth_token",
    JSON.stringify({
      token,
      expiresAt: Date.now() + 24 * 60 * 60 * 1000,
    }),
  );
}

function normalizeSharedAuthToken(raw) {
  if (!raw) return "";
  let token = decodeURIComponent(String(raw)).trim();
  if (token.startsWith("{")) {
    try {
      const parsed = JSON.parse(token);
      token = parsed.token || token;
    } catch {
      // keep raw value
    }
  }
  return token;
}

function getSharedAuthParams() {
  const urlParams = new URLSearchParams(window.location.search);
  const employeeNo = urlParams.get("employee_no");
  const email = urlParams.get("email");
  const authToken = urlParams.get("auth_token");
  const redirectFrom = urlParams.get("redirect_from");

  if (employeeNo && email && authToken && redirectFrom === "e_portal") {
    return {
      employee_no: employeeNo,
      email,
      auth_token: normalizeSharedAuthToken(authToken),
      redirect_from: redirectFrom,
    };
  }
  return null;
}

function isNetworkError(error) {
  return !error?.response && (error?.code === "ERR_NETWORK" || error?.message === "Network Error");
}

async function exchangeSharedAuth(sharedParams) {
  try {
    return await authApi.sharedAuth(sharedParams);
  } catch (error) {
    if (!isNetworkError(error)) {
      throw error;
    }
    return authApi.sharedAuthQuery(sharedParams);
  }
}

function applySharedAuthResponse(response) {
  const payload = response.data?.data || response.data;
  const token = payload?.token;
  const userData = payload?.user;

  if (!token || !userData) {
    throw new Error("Invalid shared authentication response");
  }

  saveAuthToken(token);
  user.value = userData;
  lastPayrollKeysFetchAt = Date.now();
  clearSharedAuthParams();
}

async function fallbackToPortalToken(sharedParams) {
  saveAuthToken(sharedParams.auth_token);
  const u = await ensurePayrollAccessUser();
  if (!u) {
    throw new Error("E-Portal token was not accepted by payroll API");
  }
  clearSharedAuthParams();
}

function clearSharedAuthParams() {
  const url = new URL(window.location);
  url.searchParams.delete("employee_no");
  url.searchParams.delete("email");
  url.searchParams.delete("auth_token");
  url.searchParams.delete("redirect_from");
  window.history.replaceState({}, "", url);
}

/** Loads user + payroll_menu_keys; shared across layout and router. */
export async function ensurePayrollAccessUser() {
  if (!localStorage.getItem("auth_token")) return null;

  const now = Date.now();
  const keys = user.value?.payroll_menu_keys;
  const hasKeys = Array.isArray(keys) && keys.length > 0;
  if (
    user.value?.id != null &&
    hasKeys &&
    now - lastPayrollKeysFetchAt < PAYROLL_KEYS_REFRESH_MS
  ) {
    return user.value;
  }

  if (
    user.value?.id != null &&
    Array.isArray(keys) &&
    keys.length === 0 &&
    now - lastPayrollKeysFetchAt < PAYROLL_KEYS_EMPTY_REFETCH_MS
  ) {
    return user.value;
  }

  try {
    const response = await authApi.getCurrentUser();
    const u = response.data?.data?.user;
    if (u) user.value = u;
    lastPayrollKeysFetchAt = Date.now();
    return user.value;
  } catch {
    return user.value?.id != null ? user.value : null;
  }
}

/** Run before app mount so router never fires with a stale or E-Portal token. */
export async function bootstrapAuth() {
  if (authBootstrapped) return false;
  authBootstrapped = true;

  const sharedParams = getSharedAuthParams();
  if (sharedParams) {
    ePortalRedirect = true;
    localStorage.removeItem("auth_token");
    user.value = null;

    setSuppress401Clear(true);
    try {
      const response = await exchangeSharedAuth(sharedParams);
      applySharedAuthResponse(response);
      return true;
    } catch (error) {
      console.error("E-Portal shared auth failed:", error);
      try {
        await fallbackToPortalToken(sharedParams);
        return true;
      } catch (fallbackError) {
        console.error("E-Portal token fallback failed:", fallbackError);
        localStorage.removeItem("auth_token");
        user.value = null;
        return false;
      }
    } finally {
      setSuppress401Clear(false);
    }
  }

  if (localStorage.getItem("auth_token")) {
    try {
      await ensurePayrollAccessUser();
    } catch {
      // keep stored token; user can log in manually
    }
  }

  return false;
}

export function isEPortalSharedAuth() {
  return getSharedAuthParams() !== null;
}

export function wasEPortalRedirect() {
  return ePortalRedirect;
}

export function useAuth() {
  const isAuthenticated = computed(() => !!localStorage.getItem("auth_token"));

  const login = async (credentials) => {
    try {
      loading.value = true;
      const response = await authApi.login(credentials);

      const responseData = response.data.data || response.data;
      const { token, user: userData } = responseData;

      if (!token) {
        throw new Error("No token received from server");
      }

      saveAuthToken(token);
      user.value = userData;

      ElMessage.success("Login successful!");
      return response.data;
    } catch (error) {
      console.error("Login failed:", error);
      const errorMessage = error.response?.data?.message || "Login failed";
      ElMessage.error(errorMessage);
      throw error;
    } finally {
      loading.value = false;
    }
  };

  const clearAuth = () => {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user_email");
    user.value = null;
  };

  const logout = async () => {
    try {
      loading.value = true;
      await authApi.logout();
    } catch (error) {
      console.error("Logout API call failed:", error);
    } finally {
      clearAuth();
      ElMessage.success("Logged out successfully!");
    }
  };

  const getCurrentUser = async () => {
    try {
      const response = await authApi.getCurrentUser();
      user.value = response.data.data.user;
      return response.data.data.user;
    } catch (error) {
      console.error("Failed to get current user:", error);
      if (error.response?.status === 401) {
        clearAuth();
      }
      throw error;
    }
  };

  const checkAuth = () => {
    const tokenData = localStorage.getItem("auth_token");
    if (!tokenData) return false;

    try {
      const parsed = JSON.parse(tokenData);
      if (parsed.expiresAt && Date.now() > parsed.expiresAt) {
        clearAuth();
        return false;
      }
      return !!parsed.token;
    } catch (error) {
      return !!tokenData;
    }
  };

  const initAuth = async () => {
    await bootstrapAuth();
  };

  return {
    user,
    loading,
    isAuthenticated,
    login,
    logout,
    clearAuth,
    getCurrentUser,
    checkAuth,
    initAuth,
  };
}
