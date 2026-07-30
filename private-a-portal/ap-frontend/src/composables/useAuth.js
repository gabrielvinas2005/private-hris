import { ref, computed } from "vue";
import { ElMessage } from "element-plus";
import { authApi } from "@/services/api";

export function useAuth() {
  const user = ref(null);
  const loading = ref(false);
  const isAuthenticated = computed(() => !!localStorage.getItem("auth_token"));

  // Check for shared authentication parameters
  const checkSharedAuth = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const employeeNo = urlParams.get("employee_no");
    const email = urlParams.get("email");
    const authToken = urlParams.get("auth_token");
    const redirectFrom = urlParams.get("redirect_from");

    console.log("Payroll Module - Checking shared auth parameters:", {
      employeeNo,
      email,
      authToken: authToken ? authToken.substring(0, 20) + "..." : null,
      redirectFrom,
    });
    console.log("Payroll Module - Current URL:", window.location.href);

    if (employeeNo && email && authToken && redirectFrom === "e_portal") {
      console.log("Payroll Module - Shared auth detected from E-Portal:", {
        employeeNo,
        email,
      });

      // Use the passed token directly from E-Portal
      const sharedUser = {
        employee_no: employeeNo,
        email: email,
        name: email.split("@")[0],
        id: employeeNo,
      };

      // Store token in the format expected by Payroll Module
      const tokenData = {
        token: authToken,
        expiresAt: Date.now() + 24 * 60 * 60 * 1000, // 24 hours from now
      };

      // Set up frontend auth state with E-Portal's token
      user.value = sharedUser;
      localStorage.setItem("auth_token", JSON.stringify(tokenData));

      console.log(
        "Payroll Module - Frontend auth state set up with E-Portal token"
      );

      // Clean URL parameters
      const url = new URL(window.location);
      url.searchParams.delete("employee_no");
      url.searchParams.delete("email");
      url.searchParams.delete("auth_token");
      url.searchParams.delete("redirect_from");
      window.history.replaceState({}, "", url);

      console.log("Payroll Module - URL cleaned of auth parameters");
      return true;
    }

    console.log("Payroll Module - No shared auth parameters found");
    return false;
  };

  // Login function
  const login = async (credentials) => {
    try {
      loading.value = true;
      const response = await authApi.login(credentials);

      // Store token and user data from backend response
      // Handle different response formats from backend
      const responseData = response.data.data || response.data;
      const { token, user: userData } = responseData;

      if (!token) {
        throw new Error("No token received from server");
      }

      // For Laravel Sanctum tokens, they typically don't have expiration
      // But we'll set a reasonable expiration time (24 hours)
      const tokenData = {
        token: token,
        expiresAt: Date.now() + 24 * 60 * 60 * 1000, // 24 hours from now
      };
      localStorage.setItem("auth_token", JSON.stringify(tokenData));
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

  // Logout function
  const logout = async () => {
    try {
      loading.value = true;
      // Call backend logout API to invalidate session
      await authApi.logout();
    } catch (error) {
      console.error("Logout API call failed:", error);
      // Continue with local logout even if API call fails
    } finally {
      // Always clear local auth data
      clearAuth();
      ElMessage.success("Logged out successfully!");
    }
  };

  // Clear auth data (for token expiration)
  const clearAuth = () => {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user_email");
    user.value = null;
  };

  // Get current user
  const getCurrentUser = async () => {
    try {
      const response = await authApi.getCurrentUser();
      user.value = response.data.data.user;
      return response.data.data.user;
    } catch (error) {
      console.error("Failed to get current user:", error);
      // If token is invalid, clear auth data
      if (error.response?.status === 401) {
        clearAuth();
      }
      throw error;
    }
  };

  // Check if user is authenticated
  const checkAuth = () => {
    const tokenData = localStorage.getItem("auth_token");
    if (!tokenData) return false;

    try {
      const parsed = JSON.parse(tokenData);
      // Check if token is expired
      if (parsed.expiresAt && Date.now() > parsed.expiresAt) {
        clearAuth();
        return false;
      }
      return !!parsed.token;
    } catch (error) {
      // If parsing fails, treat as old format token
      return !!tokenData;
    }
  };

  // Initialize auth state
  const initAuth = () => {
    console.log("Payroll Module - Initializing authentication...");

    // First check for shared authentication parameters
    if (checkSharedAuth()) {
      console.log(
        "Payroll Module - Shared auth successful - skipping other checks"
      );
      return;
    }

    const token = localStorage.getItem("auth_token");
    if (token) {
      // Optionally fetch user data
      getCurrentUser().catch(() => {
        // If fetching user fails, clear invalid token
        clearAuth();
      });
    }
  };

  return {
    // State
    user,
    loading,
    isAuthenticated,

    // Methods
    login,
    logout,
    clearAuth,
    getCurrentUser,
    checkAuth,
    initAuth,
    checkSharedAuth,
  };
}
