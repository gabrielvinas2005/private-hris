const FIELD_LABELS = {
  signatory: "Authorized Representative",
  accsignatory: "OIC-Municipal Accountant",
  position: "Position/Designation",
  date: "Date",
  payroll_period: "Payroll Period",
  payroll_period_id: "Payroll Period",
  payroll_interval_id: "Payroll Interval",
  membership_program: "Loan Type",
  deduction: "Loan Type",
  department: "Division",
  department_id: "Division",
  division_id: "Division",
  extra_bonus_type_id: "Extra Bonus Type",
  year_id: "Year",
  years: "Year",
  signatory_1: "Signatory 1 (Name)",
  signatory_position_1: "Signatory 1 (Position)",
  signatory_2: "Signatory 2 (Name)",
  signatory_position_2: "Signatory 2 (Position)",
  signatory_3: "Signatory 3 (Name)",
  signatory_position_3: "Signatory 3 (Position)",
  signatory_4: "Signatory 4 (Name)",
  signatory_position_4: "Signatory 4 (Position)",
  signatory_5: "Signatory 5 (Name)",
  signatory_position_5: "Signatory 5 (Position)",
};

const isGenericValidationMessage = (msg) =>
  typeof msg === "string" &&
  /^(validation failed|the given data was invalid\.?)$/i.test(msg.trim());

const isAxiosStatusMessage = (msg) =>
  typeof msg === "string" &&
  /^Request failed with status code \d+$/i.test(msg.trim());

/**
 * Turn an Axios/API error into a user-readable message (including Laravel 422 validation).
 */
export function formatApiError(err, fallback = "Something went wrong. Please try again.") {
  const data = err?.response?.data;
  const status = err?.response?.status;

  if (typeof data === "string" && data.trim()) {
    return data;
  }

  if (data && typeof data === "object") {
    const parts = [];

    const errors = data.errors;
    const fieldMessages = [];
    if (errors && typeof errors === "object") {
      Object.entries(errors).forEach(([field, messages]) => {
        const label = FIELD_LABELS[field] || field.replace(/_/g, " ");
        const list = Array.isArray(messages) ? messages : [messages];
        list.filter(Boolean).forEach((msg) => {
          const text = String(msg);
          fieldMessages.push(
            text.toLowerCase().startsWith(label.toLowerCase())
              ? text
              : `${label}: ${text}`,
          );
        });
      });
    }

    if (fieldMessages.length > 0) {
      parts.push(...fieldMessages);
    } else if (
      data.message &&
      !isAxiosStatusMessage(data.message) &&
      !isGenericValidationMessage(data.message)
    ) {
      parts.push(data.message);
    } else if (
      data.message &&
      isGenericValidationMessage(data.message) &&
      fieldMessages.length === 0
    ) {
      // skip bare "Validation failed" when no field detail exists
    }

    if (parts.length > 0) {
      return parts.join(" ");
    }
  }

  if (status === 422) {
    return "Please complete all required fields before generating the report.";
  }
  if (status === 400) {
    return (
      (typeof data === "object" && data?.message) ||
      fallback ||
      "No report data found for your selection. Check payroll period, loan type, and encoded deductions."
    );
  }
  if (status === 401) {
    return "Your session has expired. Please sign in again.";
  }
  if (status === 404) {
    return "The report service could not be reached. Please contact your administrator.";
  }

  const msg = err?.message;
  if (msg && !isAxiosStatusMessage(msg)) {
    return msg;
  }

  return fallback;
}
