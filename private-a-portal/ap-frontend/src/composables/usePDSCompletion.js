/**
 * PDS (Personal Data Sheet) completion validation.
 * Must match dashboard "PDS Status" and gate job applications.
 * Required: Personal Info, Contact, Educational Background, Work Experience, Required Documents, Eligibility, Character References.
 */

const SECTION_LABELS = {
  personalInfo: "• Personal Information",
  contactInfo: "• Contact Information",
  education: "• Educational Background",
  workExperience: "• Work Experience",
  documents: "• Required Documents",
  eligibility: "• Eligibility",
  references: "• Character References",
};

/**
 * Check PDS completion using the same 7-section logic as the dashboard.
 * @param {Object} pageData - Applicant-page response shape: { applicant, educations, employments, documents, examinations, references }
 * @returns {{ isComplete: boolean, missingSections: string[] }}
 */
export function checkPDSCompletion(pageData) {
  const applicant = pageData?.applicant?.[0] || pageData?.applicant || {};
  const educations = pageData?.educations || [];
  const employments = pageData?.employments || [];
  const documents = pageData?.documents || [];
  const examinations = pageData?.examinations || [];
  const references = pageData?.references || [];

  const missingSections = [];

  if (!applicant.first_name || !applicant.last_name || !applicant.email) {
    missingSections.push(SECTION_LABELS.personalInfo);
  }
  if (!applicant.mobile_no) {
    missingSections.push(SECTION_LABELS.contactInfo);
  }
  if (!Array.isArray(educations) || educations.length === 0) {
    missingSections.push(SECTION_LABELS.education);
  }
  const workExp = pageData?.work_experience || [];
  const hasWork =
    (Array.isArray(employments) && employments.length > 0) ||
    (Array.isArray(workExp) && workExp.length > 0);
  if (!hasWork) {
    missingSections.push(SECTION_LABELS.workExperience);
  }
  const hasPhoto = !!(applicant.photo && String(applicant.photo).trim());
  const hasDocuments = Array.isArray(documents) && documents.length > 0;
  if (!hasPhoto && !hasDocuments) {
    missingSections.push(SECTION_LABELS.documents);
  }
  // Check Eligibility
  if (!Array.isArray(examinations) || examinations.length === 0) {
    missingSections.push(SECTION_LABELS.eligibility);
  }
  // Check Character References
  if (!Array.isArray(references) || references.length === 0) {
    missingSections.push(SECTION_LABELS.references);
  }

  return {
    isComplete: missingSections.length === 0,
    missingSections,
  };
}
