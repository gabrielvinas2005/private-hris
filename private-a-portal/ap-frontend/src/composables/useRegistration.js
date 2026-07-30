import { reactive, ref, computed } from "vue";
import { useRouter } from "vue-router";
import { ApiService } from "@/services/api.js";
import { ElNotification } from "element-plus";

export function useRegistration() {
  const router = useRouter();
  const isSubmitting = ref(false);
  const submitError = ref("");
  const ageError = ref(false);
  const phoneError = ref("");
  const resumeError = ref("");

  const formData = reactive({
    firstName: "",
    lastName: "",
    email: "",
    phone: "",
    dateOfBirth: "",
    middleName: "",
    address: "",
    city: "",
    postalCode: "",
    resume: null,
  });

  const computedAge = computed(() => {
    if (!formData.dateOfBirth) return "";
    const today = new Date();
    const dob = new Date(formData.dateOfBirth);
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
      age--;
    }
    return age.toString();
  });

  const validateAge = () => {
    const today = new Date();
    const dob = new Date(formData.dateOfBirth);
    const age = today.getFullYear() - dob.getFullYear();
    ageError.value = age < 18;
  };

  const validatePhone = (event) => {
    if (!event || !event.target) return;

    let phoneNumber = event.target.value;

    // Remove any non-numeric characters
    phoneNumber = phoneNumber.replace(/\D/g, '');

    // // If user enters 10 digits (without leading 0), add leading 0 to make it 11 digits
    // if (phoneNumber.length === 10 && phoneNumber.startsWith('9')) {
    //   phoneNumber = '0' + phoneNumber;
    // }

    // Update the model with cleaned value
    formData.phone = phoneNumber;

    // Validate length and format
    if (phoneNumber.length === 0) {
      phoneError.value = "";
      return;
    }

    if (phoneNumber.length !== 10) {
      phoneError.value = "Phone number must be exactly 11 digits (or 10 digits starting with 9)";
    } else if (!/^\d{10}$/.test(phoneNumber)) {
      phoneError.value = "Phone number must contain only numbers";
    } else if (!phoneNumber.startsWith('9')) {
      phoneError.value = "Philippine mobile numbers should start with 09";
    } else {
      phoneError.value = "";
    }
  };

  const handleNavigate = (path) => {
    router.push(path);
  };

  const handleResumeChange = (event) => {
    const file = event.target.files[0];
    if (!file) {
      formData.resume = null;
      resumeError.value = "";
      return;
    }

    // Validate file size (2MB = 2 * 1024 * 1024 bytes)
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
      resumeError.value = "File size must be less than 2MB";
      formData.resume = null;
      event.target.value = ""; // Clear the input
      return;
    }

    // Validate file type
    const allowedExtensions = ['pdf', 'doc', 'docx'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(fileExtension)) {
      resumeError.value = "Invalid file type. Accepted formats: PDF, DOC, DOCX";
      formData.resume = null;
      event.target.value = ""; // Clear the input
      return;
    }

    // File is valid
    formData.resume = file;
    resumeError.value = "";
  };

  const handleSubmit = async () => {
    submitError.value = "";

    if (ageError.value) {
      ElNotification({
        title: "Age validation failed",
        message: "You must be at least 18 years old to register.",
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      return;
    }

    // Validate phone number
    let phoneNumber = (formData.phone || '').replace(/\D/g, '').trim();

    // If user enters 10 digits (without leading 0), add leading 0 to make it 11 digits
    if (phoneNumber.length === 10 && phoneNumber.startsWith('9')) {
      phoneNumber = '0' + phoneNumber;
    }

    if (!phoneNumber) {
      phoneError.value = "Phone number is required";
      ElNotification({
        title: "Validation failed",
        message: "Please enter a valid Philippine mobile number (+63 9123456789).",
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      return;
    }

    if (!/^\d{11}$/.test(phoneNumber)) {
      phoneError.value = phoneNumber.length !== 11
        ? "Phone number must be exactly 11 digits (or 10 digits starting with 9)"
        : "Phone number must contain only numbers";
      ElNotification({
        title: "Validation failed",
        message: phoneError.value,
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      return;
    }

    if (!phoneNumber.startsWith('09')) {
      phoneError.value = "Philippine mobile numbers should start with 09";
      ElNotification({
        title: "Validation failed",
        message: "Please enter a valid Philippine mobile number starting with 09.",
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      return;
    }

    // Validate resume
    if (!formData.resume) {
      resumeError.value = "Resume is required";
      ElNotification({
        title: "Validation failed",
        message: "Please upload your resume.",
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      return;
    }

    if (resumeError.value) {
      ElNotification({
        title: "Validation failed",
        message: resumeError.value,
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      return;
    }

    const payload = new FormData();
    payload.append("first_name", formData.firstName);
    payload.append("middle_name", formData.middleName);
    payload.append("last_name", formData.lastName);
    payload.append("birth_date", formData.dateOfBirth);
    payload.append("gender", 0);
    payload.append("age", computedAge.value);
    payload.append("address", ""); // Address removed from registration form
    payload.append("mobile_no", phoneNumber);
    payload.append("email", formData.email);
    payload.append("employee_no", "");

    if (formData.resume) {
      payload.append("resume", formData.resume);
    }

    try {
      isSubmitting.value = true;
      const response = await ApiService.submitRegistration(payload);

      const isSuccessStatus = response.status >= 200 && response.status < 300;
      const hasSuccessFlag = response.data && response.data.success === true;

      if (isSuccessStatus && (hasSuccessFlag || response.data)) {
        ElNotification({
          title: "Registration successful",
          message:
            "Please check your email for your temporary credentials, then log in to your applicant dashboard.",
          type: "success",
          duration: 5000,
          position: "top-right",
        });
        router.push("/login");
      } else {
        const message =
          response.data?.message ||
          "Registration failed. Please review your information and try again.";
        submitError.value = message;
        ElNotification({
          title: "Registration failed",
          message,
          type: "error",
          duration: 5000,
          position: "top-right",
        });
      }
    } catch (error) {
      const resp = error.response?.data;
      const status = error.response?.status;
      const isTimeout =
        error.code === "ECONNABORTED" || error.message?.includes("timeout");

      if (!isTimeout) {
        console.error("Registration error:", error);
      }

      if (status >= 200 && status < 300) {
        if (
          resp?.success === true ||
          resp?.message?.toLowerCase().includes("success")
        ) {
          ElNotification({
            title: "Registration successful",
            message:
              "Please check your email for your temporary credentials, then log in to your applicant dashboard.",
            type: "success",
            duration: 5000,
            position: "top-right",
          });
          router.push("/login");
          return;
        }
      }

      if (isTimeout) {
        ElNotification({
          title: "Registration may have succeeded",
          message:
            "The request took longer than expected, but your registration may have been successful. Please check your email for your temporary credentials. If you don't receive an email, please try again.",
          type: "warning",
          duration: 8000,
          position: "top-right",
        });
        return;
      }

      let message =
        resp?.message || "Registration failed. Please try again later.";
      if (resp?.errors) {
        const errors = resp.errors;
        message = Object.values(errors).flat().join(", ");
      }
      submitError.value = message;
      ElNotification({
        title: "Registration failed",
        message,
        type: "error",
        duration: 5000,
        position: "top-right",
      });
    } finally {
      isSubmitting.value = false;
    }
  };

  return {
    formData,
    isSubmitting,
    submitError,
    ageError,
    phoneError,
    computedAge,
    validateAge,
    validatePhone,
    handleNavigate,
    handleResumeChange,
    resumeError,
    handleSubmit,
  };
}
