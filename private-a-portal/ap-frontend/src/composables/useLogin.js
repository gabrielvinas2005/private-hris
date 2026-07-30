import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { ApiService } from "@/services/api.js";
import { ElNotification } from "element-plus";

const HIGHLIGHTS = [
  {
    title: "Track Your Applications",
    description: "Monitor application status in real-time",
  },
  {
    title: "Instant Notifications",
    description: "Get updates on new positions",
  },
  {
    title: "Manage Your Profile",
    description: "Keep your information up to date",
  },
];

export function useLogin() {
  const router = useRouter();

  const form = reactive({
    email: "",
    password: "",
    rememberMe: false,
  });

  const isLoading = ref(false);
  const showPassword = ref(false);
  const showForgotPassword = ref(false);
  const isSendingReset = ref(false);
  const forgotPasswordEmail = ref("");

  const togglePassword = () => {
    showPassword.value = !showPassword.value;
  };

  const handleNavigate = (path) => {
    router.push(path);
  };

  const openForgotPassword = () => {
    forgotPasswordEmail.value = form.email || "";
    showForgotPassword.value = true;
  };

  const closeForgotPassword = () => {
    showForgotPassword.value = false;
  };

  const submitForgotPassword = async () => {
    if (!forgotPasswordEmail.value) {
      ElNotification({
        title: "Missing email",
        message: "Please enter your email address to reset your password.",
        type: "warning",
        duration: 5000,
        position: "top-right",
      });
      return;
    }

    if (isSendingReset.value) {
      return;
    }

    isSendingReset.value = true;

    try {
      await ApiService.requestPasswordReset(forgotPasswordEmail.value);
      ElNotification({
        title: "Email sent",
        message:
          "If an account exists for this email, a password reset link has been sent.",
        type: "success",
        duration: 7000,
        position: "top-right",
      });
      showForgotPassword.value = false;
    } catch (error) {
      console.error("Password reset error:", error);
      const message =
        error.response?.data?.message ||
        "We were unable to send a reset link. Please try again later.";
      ElNotification({
        title: "Request failed",
        message,
        type: "error",
        duration: 7000,
        position: "top-right",
      });
    } finally {
      isSendingReset.value = false;
    }
  };

  const handleLogin = async (event) => {
    // Prevent form submission and page refresh
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    // Prevent if already loading
    if (isLoading.value) {
      return;
    }

    isLoading.value = true;

    try {
      const response = await ApiService.login({
        email: form.email,
        password: form.password,
        remember_me: form.rememberMe,
      });

      const loginData = response?.data?.data;
      if (!loginData) {
        throw new Error("Invalid response format");
      }

      localStorage.setItem("auth_token", loginData.token);
      localStorage.setItem("user_data", JSON.stringify(loginData.user));

      if (loginData.requires_otp) {
        router.push("/verify-otp");
      } else {
        router.push(loginData.next || "/dashboard");
      }
    } catch (error) {
      console.error("Login error:", error);
      let message =
        error.response?.data?.message ||
        "Login failed. Please check your credentials.";
      if (error.response?.data?.data?.errors) {
        const errors = error.response.data.data.errors;
        message = Object.values(errors).flat().join(", ");
      }
      ElNotification({
        title: "Login failed",
        message,
        type: "error",
        duration: 5000,
        position: "top-right",
      });
    } finally {
      isLoading.value = false;
    }
  };

  return {
    form,
    highlights: HIGHLIGHTS,
    isLoading,
    showPassword,
    showForgotPassword,
    isSendingReset,
    forgotPasswordEmail,
    togglePassword,
    handleNavigate,
    handleLogin,
    openForgotPassword,
    closeForgotPassword,
    submitForgotPassword,
  };
}
