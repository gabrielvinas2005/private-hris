// Dashboard data constants
export const tabs = [
    { id: 'overview', name: 'Overview', icon: 'fas fa-tachometer-alt' },
    { id: 'profile', name: 'Profile', icon: 'fas fa-user' },
    { id: 'pds', name: 'Personal Data Sheet', icon: 'fas fa-id-card' },
    { id: 'applications', name: 'Applications', icon: 'fas fa-file-alt' },
    { id: 'examinations', name: 'Examinations', icon: 'fas fa-clipboard-check' },
    { id: 'documents', name: 'Documents', icon: 'fas fa-folder-open' }
]

export const pdsTabs = [
    { id: 'personal', name: 'Personal Information', icon: 'fas fa-user' },
    { id: 'family', name: 'Family Background', icon: 'fas fa-users' },
    { id: 'education', name: 'Educational Background', icon: 'fas fa-graduation-cap' },
    { id: 'work', name: 'Work Experience', icon: 'fas fa-briefcase' },
    { id: 'eligibility', name: 'Civil Service Eligibility', icon: 'fas fa-certificate' },
    { id: 'trainings', name: 'Learning & Development', icon: 'fas fa-chalkboard-teacher' },
    { id: 'voluntary', name: 'Voluntary Work', icon: 'fas fa-hands-helping' },
    { id: 'other', name: 'Other Information', icon: 'fas fa-info-circle' }
]

export const initialPdsForm = {
    // Personal Information
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    mobile_no: '',
    employee_no: '',
    birthdate: '',
    age: '',
    gender_id: 0,
    name_prefix_id: 0,
    name_suffix_id: 0,
    birth_place: '',
    civil_status_id: 0,
    citizenship_id: 0,
    religion_id: 0,
    height: '',
    weight: '',
    blood_type_id: 0,

    // Address Information - Residential
    ra_region: '',
    ra_province: '',
    ra_city: '',
    ra_barangay: '',
    ra_house_no: '',
    ra_street: '',
    ra_village: '',

    // Address Information - Permanent
    pa_region: '',
    pa_province: '',
    pa_city: '',
    pa_barangay: '',
    pa_house_no: '',
    pa_street: '',
    pa_village: '',

    // Contact Information
    telephone_no: '',

    // Other Information
    special_skills: '',
    non_academic_distinctions: ''
}

export const initialApplicantData = {
    id: null,
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    mobile_no: '',
    age: null,
    applicant_no: '',
    birth_date: '',
    gender: '',
    address: '',
    photo: null,
    name: ''
}

export const initialStats = {
    applications: 0,
    completedExams: 0,
    pending: 0,
    averageScore: 0
}
