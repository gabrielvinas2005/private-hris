import { ElNotification } from "element-plus";

const defaultsByType = {
  success: { duration: 4000 },
  error: { duration: 5000 },
  info: { duration: 4000 },
  warning: { duration: 4000 },
};

export function useAppNotification() {
  const notify = (type, title, message, options = {}) => {
    const base = defaultsByType[type] || {};
    ElNotification({
      title,
      message,
      type,
      position: "top-right",
      ...base,
      ...options,
    });
  };

  return {
    success: (title, message, options) => notify("success", title, message, options),
    error: (title, message, options) => notify("error", title, message, options),
    info: (title, message, options) => notify("info", title, message, options),
    warning: (title, message, options) => notify("warning", title, message, options),
  };
}

