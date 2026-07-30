// Mock data for 201 file system testing
export const mockEmployeeData = {
    employee: {
        id: 1,
        name: 'John Doe',
        employee_no: 'EMP-2024-001',
        email: 'john.doe@company.com',
        mobile_no: '+63 912 345 6789',
        telephone_no: '+63 2 8123 4567',
        birthdate: '1985-03-15',
        age: 39,
        gender: 'Male',
        height: 1.75,
        weight: 70,
        blood_type: 'O+',
        citizenship: 'Filipino',
        civil_status: 'Married',
        religion: 'Roman Catholic',
        photo: null, // Base64 encoded photo would go here
        ra_house_no: '123',
        ra_village: 'Village A',
        ra_street: 'Main Street',
        pa_house_no: '456',
        pa_village: 'Village B',
        pa_street: 'Secondary Street',
        position: 'HR Officer',
        department: 'Human Resources',
        spouse_name: 'Jane Doe',
        spouse_occupation: 'Teacher',
        spouse_employer: 'Public School',
        spouse_business_address: 'School District, Manila'
    },
    address: {
        ra_brgy: 'Barangay 1',
        ra_city: 'Manila',
        ra_province: 'Metro Manila',
        ra_region: 'NCR',
        pa_brgy: 'Barangay 2',
        pa_city: 'Quezon City',
        pa_province: 'Metro Manila',
        pa_region: 'NCR'
    },
    work_info: {
        company: 'Company Name',
        branch: 'Main Office',
        department: 'Human Resources',
        division: 'Administration',
        employment_type: 'Regular',
        position: 'HR Officer',
        plantilla: 'HR-OFF-001',
        grade: '15',
        step: '1',
        section: 'Recruitment'
    },
    payroll_info: {
        interval: 'Monthly',
        gsis_no: '1234567890',
        sss_no: '1234567890',
        pagibig_no: '1234567890',
        philhealth_no: '1234567890',
        tin_no: '123456789',
        salary: 45000.00,
        gsis_amount: 4500.00,
        sss_amount: 1200.00,
        pagibig_amount: 100.00,
        philhealth_amount: 900.00,
        tax_amount: 5000.00
    },
    family_info: {
        father_name: 'Juan Doe',
        mother_name: 'Maria Doe',
        spouse_name: 'Jane Doe',
        spouse_occupation: 'Teacher',
        spouse_employer: 'Public School',
        spouse_business_address: 'School District, Manila'
    },
    incomes: [
        { id: 1, name: 'Basic Salary', amount: 45000.00 },
        { id: 2, name: 'Transportation Allowance', amount: 2000.00 },
        { id: 3, name: 'Meal Allowance', amount: 1500.00 }
    ],
    loans: [
        { id: 1, name: 'GSIS Loan', loan_amount: 50000.00, payment: 2000.00, balance: 30000.00 },
        { id: 2, name: 'Pag-Ibig Loan', loan_amount: 100000.00, payment: 5000.00, balance: 80000.00 }
    ],
    children: [
        { id: 1, child_name: 'John Jr.', child_middlename: 'Smith', child_lastname: 'Doe', child_birthdate: '2010-05-20' },
        { id: 2, child_name: 'Jane', child_middlename: 'Marie', child_lastname: 'Doe', child_birthdate: '2012-08-15' }
    ],
    educations: [
        { id: 1, academic_level_id: 3, school_name: 'University of the Philippines', program: 'Bachelor of Science in Business Administration', from: '2003', to: '2007', graduated_year: '2007', units_earned: '120', honors: 'Cum Laude' },
        { id: 2, academic_level_id: 1, school_name: 'Manila High School', program: 'Secondary Education', from: '1999', to: '2003', graduated_year: '2003', units_earned: 'N/A', honors: 'With Honors' }
    ],
    service_records: [
        { id: 1, start_date: '2020-01-15', end_date: '2023-12-31', designation: 'HR Officer', employment_type: 'Regular', annual_salary: 540000.00, place_of_assignment: 'Main Office', leave_without_pay: 'None', separation_date: null, cause: null, branch: 'Main Office' }
    ],
    work_experiences: [
        { id: 1, work_start_date: '2018-06-01', work_end_date: '2019-12-31', work_company: 'ABC Corporation', monthly_salary: 35000.00, salary_grade_step: 'SG-12-1', status_of_appointment: 'Contractual', position: 'HR Assistant', government_service_id: 0 }
    ],
    eligibilities: [
        { id: 1, eligibility: 'Civil Service Professional', exam_rating: '85.50', exam_date: '2019-03-15', place_of_exam: 'Manila', license_number: 'CS-123456', date_released: '2019-05-20' }
    ],
    trainings: [
        { id: 1, training: 'HR Management Seminar', training_from: '2023-06-15', training_to: '2023-06-17', hours: 24, sponsored_by: 'Civil Service Commission', learning: 'Leadership and Management' }
    ],
    voluntary_works: [
        { id: 1, organization: 'Rotary Club', organization_address: 'Manila', org_from: '2020-01-01', org_to: '2023-12-31', org_hours: 100, org_position: 'Member' }
    ],
    ipcr_results: [
        { id: 1, rating: '4.5', adjectival_rating: 'Outstanding', attachment: 'IPCR_2023.pdf' }
    ],
    recognitions: [
        { id: 1, recognation: 'Employee of the Year 2023' },
        { id: 2, recognation: 'Best HR Practitioner Award 2022' }
    ],
    skills: [
        { id: 1, skill: 'Human Resource Management' },
        { id: 2, skill: 'Microsoft Office Suite' },
        { id: 3, skill: 'Employee Relations' }
    ],
    memberships: [
        { id: 1, membership: 'Philippine Society for Human Resource Management' },
        { id: 2, membership: 'International Public Management Association' }
    ],
    references: [
        { id: 1, ref_name: 'Dr. Maria Santos', ref_address: 'Manila', ref_occupation: 'Professor', ref_contact_no: '+63 912 345 6789', ref_email: 'maria.santos@university.edu' }
    ],
    can_update: true,
    days_remaining: 15,
    last_update_date: 'December 15, 2023'
}

// Mock SALN data
export const mockSALNData = {
    employee: {
        id: 1,
        name: 'John Doe',
        position: 'HR Officer',
        department: 'Human Resources',
        spouse_name: 'Jane Doe',
        spouse_occupation: 'Teacher',
        spouse_employer: 'Public School',
        spouse_business_address: 'School District, Manila',
        pa_house_no: '456',
        pa_village: 'Village B',
        pa_street: 'Secondary Street'
    },
    address: {
        pa_brgy: 'Barangay 2',
        pa_city: 'Quezon City',
        pa_province: 'Metro Manila',
        pa_region: 'NCR'
    },
    children: [
        { id: 1, child_name: 'John Jr.', child_middlename: 'Smith', child_lastname: 'Doe', child_birthdate: '2010-05-20' },
        { id: 2, child_name: 'Jane', child_middlename: 'Marie', child_lastname: 'Doe', child_birthdate: '2012-08-15' }
    ],
    realProperties: [
        {
            id: 1,
            description: 'Residential House',
            kind: 'House and Lot',
            exact_location: 'Quezon City, Metro Manila',
            assessed_value: 2500000.00,
            current_fair_market_value: 3500000.00,
            acquisition_year: 2015,
            acquisition_mode: 'Purchase',
            acquisition_cost: 2500000.00
        },
        {
            id: 2,
            description: 'Commercial Lot',
            kind: 'Lot',
            exact_location: 'Makati City, Metro Manila',
            assessed_value: 5000000.00,
            current_fair_market_value: 8000000.00,
            acquisition_year: 2018,
            acquisition_mode: 'Inheritance',
            acquisition_cost: 0.00
        }
    ],
    personalProperties: [
        {
            id: 1,
            description: 'Toyota Innova',
            year_acquired: 2020,
            acquisition_cost: 1200000.00
        },
        {
            id: 2,
            description: 'Samsung TV 55"',
            year_acquired: 2021,
            acquisition_cost: 45000.00
        }
    ],
    liabilities: [
        {
            id: 1,
            nature: 'Housing Loan',
            creditor_name: 'Bank of the Philippine Islands',
            outstanding_balance: 1500000.00
        },
        {
            id: 2,
            nature: 'Car Loan',
            creditor_name: 'Toyota Financial Services',
            outstanding_balance: 800000.00
        }
    ],
    businessInterests: [
        {
            id: 1,
            entity_name: 'Doe Family Restaurant',
            business_address: 'Manila, Philippines',
            nature_of_business: 'Food and Beverage',
            date_acquired: '2020-01-15'
        }
    ],
    relatives: [
        {
            id: 1,
            relatives_name: 'Maria Santos',
            relationship: 'Sister',
            position: 'Administrative Officer',
            office_address: 'Department of Education, Manila'
        },
        {
            id: 2,
            relatives_name: 'Pedro Santos',
            relationship: 'Brother-in-law',
            position: 'Police Officer',
            office_address: 'Philippine National Police, Quezon City'
        }
    ]
}

// Mock Competencies data
export const mockCompetenciesData = {
    competencies: [
        {
            id: 1,
            name: 'Technical Skills',
            active: true,
            subCompetencies: [
                { id: 1, code: 'TS001', name: 'Programming', description: 'Ability to write and debug code', required_level: 4, level_attained: 3 },
                { id: 2, code: 'TS002', name: 'Database Management', description: 'Knowledge of database systems', required_level: 3, level_attained: 4 },
                { id: 3, code: 'TS003', name: 'System Analysis', description: 'Analyzing system requirements', required_level: 4, level_attained: 2 }
            ]
        },
        {
            id: 2,
            name: 'Leadership Skills',
            active: true,
            subCompetencies: [
                { id: 4, code: 'LS001', name: 'Team Management', description: 'Leading and motivating teams', required_level: 4, level_attained: 3 },
                { id: 5, code: 'LS002', name: 'Decision Making', description: 'Making sound decisions', required_level: 4, level_attained: 4 },
                { id: 6, code: 'LS003', name: 'Communication', description: 'Effective communication skills', required_level: 5, level_attained: 4 }
            ]
        },
        {
            id: 3,
            name: 'Administrative Skills',
            active: false,
            subCompetencies: [
                { id: 7, code: 'AS001', name: 'Documentation', description: 'Creating and maintaining documents', required_level: 3, level_attained: 4 },
                { id: 8, code: 'AS002', name: 'Time Management', description: 'Managing time effectively', required_level: 4, level_attained: 3 }
            ]
        }
    ],
    employees: [
        {
            id: 1,
            name: 'John Doe',
            employee_no: 'EMP-2024-001',
            employment_type: 'Regular',
            plantilla_code: 'HR-OFF-001',
            position: 'HR Officer',
            branch: 'Main Office',
            department: 'Human Resources',
            salary_grade_id: '15',
            photo: null
        },
        {
            id: 2,
            name: 'Jane Smith',
            employee_no: 'EMP-2024-002',
            employment_type: 'Regular',
            plantilla_code: 'IT-SPEC-001',
            position: 'IT Specialist',
            branch: 'Main Office',
            department: 'Information Technology',
            salary_grade_id: '16',
            photo: null
        },
        {
            id: 3,
            name: 'Mike Johnson',
            employee_no: 'EMP-2024-003',
            employment_type: 'Contractual',
            plantilla_code: 'ADMIN-001',
            position: 'Administrative Assistant',
            branch: 'Branch Office',
            department: 'Administration',
            salary_grade_id: '12',
            photo: null
        }
    ],
    employeeCompetencies: [
        {
            employee_id: 1,
            competencies: [
                {
                    id: 1,
                    name: 'Technical Skills',
                    subCompetencies: [
                        { id: 1, code: 'TS001', name: 'Programming', description: 'Ability to write and debug code', required_level: 4, level_attained: 3, competency_id: 1 },
                        { id: 2, code: 'TS002', name: 'Database Management', description: 'Knowledge of database systems', required_level: 3, level_attained: 4, competency_id: 1 },
                        { id: 3, code: 'TS003', name: 'System Analysis', description: 'Analyzing system requirements', required_level: 4, level_attained: 2, competency_id: 1 }
                    ]
                },
                {
                    id: 2,
                    name: 'Leadership Skills',
                    subCompetencies: [
                        { id: 4, code: 'LS001', name: 'Team Management', description: 'Leading and motivating teams', required_level: 4, level_attained: 3, competency_id: 2 },
                        { id: 5, code: 'LS002', name: 'Decision Making', description: 'Making sound decisions', required_level: 4, level_attained: 4, competency_id: 2 },
                        { id: 6, code: 'LS003', name: 'Communication', description: 'Effective communication skills', required_level: 5, level_attained: 4, competency_id: 2 }
                    ]
                }
            ]
        }
    ]
}

// Mock API service
export const mockApiService = {
    async getEmployee201File() {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: mockEmployeeData }
    },

    async createUpdateRequest() {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Update request created successfully' } }
    },

    async getSALNData() {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: mockSALNData }
    },

    async updateSALNData(type, data) {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'SALN data updated successfully' } }
    },

    // Competencies API methods
    async getCompetencies() {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { competencies: mockCompetenciesData.competencies } }
    },

    async getCompetency(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        const competency = mockCompetenciesData.competencies.find(c => c.id == id)
        return { data: { competency } }
    },

    async createCompetency(data) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Competency created successfully' } }
    },

    async updateCompetency(id, data) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Competency updated successfully' } }
    },

    async deleteCompetency(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Competency deleted successfully' } }
    },

    async getEmployeeCompetencies() {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { employees: mockCompetenciesData.employees } }
    },

    async getEmployeeCompetency(employeeId) {
        await new Promise(resolve => setTimeout(resolve, 500))
        const employee = mockCompetenciesData.employees.find(e => e.id == employeeId)
        const employeeCompetency = mockCompetenciesData.employeeCompetencies.find(ec => ec.employee_id == employeeId)
        return {
            data: {
                employee,
                competencies: employeeCompetency ? employeeCompetency.competencies : [],
                subCompetencies: employeeCompetency ? employeeCompetency.competencies.flatMap(c => c.subCompetencies) : []
            }
        }
    },

    async updateEmployeeCompetency(employeeId, data) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Employee competency updated successfully' } }
    },

    // Leave Management API methods
    async getLeaveData() {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return {
            data: {
                allowed: true,
                isApprover: true,
                leaveBalances: [
                    { type: 'Vacation Leave', balance: 15.5 },
                    { type: 'Sick Leave', balance: 15.0 },
                    { type: 'Maternity Leave', balance: 105.0 },
                    { type: 'Paternity Leave', balance: 7.0 },
                    { type: 'Study Leave', balance: 6.0 },
                    { type: 'Special Leave', balance: 3.0 }
                ],
                pendingLeaves: [
                    {
                        id: 1,
                        leave_type: 'Vacation Leave',
                        day_type: 'Whole Day',
                        date_covered: 'Dec 25-27, 2024',
                        reason: 'Christmas vacation with family',
                        approved: false,
                        disapproved: false,
                        is_cancel: false,
                        date_to: '2024-12-27'
                    }
                ],
                approvedLeaves: [
                    {
                        id: 2,
                        leave_type: 'Sick Leave',
                        day_type: 'Whole Day',
                        date_covered: 'Dec 20, 2024',
                        reason: 'Medical checkup',
                        approved: true,
                        disapproved: false,
                        is_cancel: false,
                        approver_1: 'John Manager',
                        approver_2: 'Jane Director',
                        processed_date: '2024-12-18',
                        processed_date_2: '2024-12-19'
                    }
                ],
                disapprovedLeaves: [
                    {
                        id: 3,
                        leave_type: 'Study Leave',
                        day_type: 'Whole Day',
                        date_covered: 'Dec 30, 2024',
                        reason: 'Board exam review',
                        approved: false,
                        disapproved: true,
                        is_cancel: false,
                        approver_1: 'John Manager',
                        processed_date: '2024-12-22'
                    }
                ],
                cancelledLeaves: [
                    {
                        id: 4,
                        leave_type: 'Vacation Leave',
                        day_type: 'Half Day',
                        date_covered: 'Dec 24, 2024',
                        reason: 'Personal errands',
                        approved: true,
                        disapproved: false,
                        is_cancel: true,
                        cancelled_by_name: 'John Manager',
                        canceled_date: '2024-12-23',
                        canceled_remarks: 'Emergency work requirement',
                        attachment_name: 'cancellation_document.pdf'
                    }
                ],
                pendingApprovals: [
                    {
                        id: 5,
                        name: 'Jane Smith',
                        photo: null,
                        leave_type: 'Vacation Leave',
                        balance: 12.5,
                        day_type: 'Whole Day',
                        date_covered: 'Jan 15-17, 2025',
                        reason: 'Family vacation',
                        approved: false,
                        disapproved: false,
                        is_cancel: false,
                        date_to: '2025-01-17'
                    }
                ],
                approvedByApprover: [
                    {
                        id: 6,
                        name: 'Mike Johnson',
                        photo: null,
                        leave_type: 'Sick Leave',
                        day_type: 'Whole Day',
                        date_covered: 'Jan 10, 2025',
                        reason: 'Dental appointment',
                        approved: true,
                        disapproved: false,
                        is_cancel: false,
                        approver_1: 'John Manager',
                        processed_date: '2025-01-08'
                    }
                ],
                disapprovedByApprover: [
                    {
                        id: 7,
                        name: 'Sarah Wilson',
                        photo: null,
                        leave_type: 'Study Leave',
                        day_type: 'Whole Day',
                        date_covered: 'Jan 20, 2025',
                        reason: 'Master\'s thesis completion',
                        approved: false,
                        disapproved: true,
                        is_cancel: false,
                        approver_1: 'John Manager',
                        processed_date: '2025-01-15'
                    }
                ],
                cancelledByApprover: [
                    {
                        id: 8,
                        name: 'Tom Brown',
                        photo: null,
                        leave_type: 'Vacation Leave',
                        day_type: 'Whole Day',
                        date_covered: 'Jan 25, 2025',
                        reason: 'Beach vacation',
                        approved: true,
                        disapproved: false,
                        is_cancel: true,
                        cancelled_by_name: 'John Manager',
                        canceled_date: '2025-01-20',
                        canceled_remarks: 'Critical project deadline'
                    }
                ]
            }
        }
    },

    async getLeaveTypes() {
        await new Promise(resolve => setTimeout(resolve, 500))
        return {
            data: {
                leaveTypes: [
                    { id: 1, name: 'Vacation Leave', allows_force_leave: true },
                    { id: 2, name: 'Sick Leave', allows_force_leave: false },
                    { id: 3, name: 'Maternity Leave', allows_force_leave: false },
                    { id: 4, name: 'Paternity Leave', allows_force_leave: false },
                    { id: 5, name: 'Study Leave', allows_force_leave: false },
                    { id: 6, name: 'Special Leave', allows_force_leave: false },
                    { id: 7, name: 'Force Leave', allows_force_leave: false }
                ]
            }
        }
    },

    async getLeave(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return {
            data: {
                leave: {
                    id: id,
                    leave_type_id: 1,
                    day_type_id: 1,
                    date_from: '2024-12-25',
                    date_to: '2024-12-27',
                    reason: 'Christmas vacation with family',
                    is_force_leave: false,
                    incase_vacation_leave_id: '1',
                    incase_vacation_leave_specify: 'Manila',
                    incase_sick_leave_id: '0',
                    incase_sick_leave_specify: '',
                    incase_special_leave_specify: '',
                    incase_study_leave_id: '0',
                    other_purpose_id: '0',
                    commutation_id: '0',
                    leave_details: [
                        { leave_date: '2024-12-25', with_pay: '1.0', without_pay: '0.0' },
                        { leave_date: '2024-12-26', with_pay: '1.0', without_pay: '0.0' },
                        { leave_date: '2024-12-27', with_pay: '1.0', without_pay: '0.0' }
                    ],
                    attachments: [
                        { name: 'vacation_request.pdf' },
                        { name: 'travel_itinerary.pdf' }
                    ]
                }
            }
        }
    },

    async createLeave(data) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'Leave application submitted successfully' } }
    },

    async updateLeave(id, data) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'Leave updated successfully' } }
    },

    async deleteLeave(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Leave deleted successfully' } }
    },

    async approveLeave(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Leave approved successfully' } }
    },

    async disapproveLeave(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Leave disapproved' } }
    },

    async cancelLeave(id, data) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Leave cancelled successfully' } }
    },

    // Monetization API methods
    async getMonetizationData() {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return {
            data: {
                allowed: true,
                isSupervisor: true,
                leaveBalances: [
                    { type: 'Vacation Leave', balance: 15.000, leave_type_id: 16 },
                    { type: 'Sick Leave', balance: 10.000, leave_type_id: 3 },
                    { type: 'Maternity Leave', balance: 0.000, leave_type_id: 4 },
                    { type: 'Paternity Leave', balance: 0.000, leave_type_id: 5 },
                    { type: 'Study Leave', balance: 0.000, leave_type_id: 6 },
                    { type: 'Special Leave', balance: 0.000, leave_type_id: 7 }
                ],
                pendingMonetizations: [
                    {
                        id: 1,
                        name: 'John Doe',
                        vl_credit: 15.000,
                        sl_credit: 10.000,
                        vl_to_monetize: 5.000,
                        sl_to_monetize: 0.000,
                        total_days: 5.000,
                        amount: 5000.00,
                        type_id: 1,
                        created_at: '2024-01-15',
                        approve_1: false,
                        approve_2: false,
                        disapprove_1: false,
                        disapprove_2: false,
                        attachments: [
                            { name: 'document1.pdf', download_url: '#' },
                            { name: 'document2.docx', download_url: '#' }
                        ]
                    }
                ],
                approvedMonetizations: [
                    {
                        id: 2,
                        name: 'Jane Smith',
                        vl_credit: 12.000,
                        sl_credit: 8.000,
                        vl_to_monetize: 3.000,
                        sl_to_monetize: 2.000,
                        total_days: 5.000,
                        amount: 5000.00,
                        type_id: 2,
                        created_at: '2024-01-10',
                        approve_1: true,
                        approve_2: true,
                        disapprove_1: false,
                        disapprove_2: false,
                        level_1_approver: 'Manager A',
                        level_2_approver: 'Director B',
                        approve_date_1: '2024-01-12',
                        approve_date_2: '2024-01-13',
                        attachments: [
                            { name: 'approved_doc.pdf', download_url: '#' }
                        ]
                    }
                ],
                disapprovedMonetizations: [
                    {
                        id: 3,
                        name: 'Bob Wilson',
                        vl_credit: 8.000,
                        sl_credit: 5.000,
                        vl_to_monetize: 2.000,
                        sl_to_monetize: 0.000,
                        total_days: 2.000,
                        amount: 2000.00,
                        type_id: 1,
                        created_at: '2024-01-05',
                        approve_1: false,
                        approve_2: false,
                        disapprove_1: true,
                        disapprove_2: false,
                        level_1_disapprover: 'Manager C',
                        disapprove_date_1: '2024-01-07',
                        attachments: [
                            { name: 'rejected_doc.pdf', download_url: '#' }
                        ]
                    }
                ],
                pendingApprovals: [
                    {
                        id: 4,
                        name: 'Alice Johnson',
                        vl_credit: 20.000,
                        sl_credit: 15.000,
                        vl_to_monetize: 7.000,
                        sl_to_monetize: 3.000,
                        total_days: 10.000,
                        amount: 10000.00,
                        type_id: 2,
                        created_at: '2024-01-20',
                        approve_1: false,
                        approve_2: false,
                        disapprove_1: false,
                        disapprove_2: false,
                        attachments: [
                            { name: 'pending_doc1.pdf', download_url: '#' },
                            { name: 'pending_doc2.xlsx', download_url: '#' }
                        ]
                    }
                ],
                approvedByApprover: [
                    {
                        id: 5,
                        name: 'Charlie Brown',
                        vl_credit: 18.000,
                        sl_credit: 12.000,
                        vl_to_monetize: 4.000,
                        sl_to_monetize: 1.000,
                        total_days: 5.000,
                        amount: 5000.00,
                        type_id: 1,
                        created_at: '2024-01-18',
                        approve_1: true,
                        approve_2: true,
                        disapprove_1: false,
                        disapprove_2: false,
                        level_1_approver: 'Manager D',
                        level_2_approver: 'Director E',
                        approve_date_1: '2024-01-19',
                        approve_date_2: '2024-01-20',
                        attachments: [
                            { name: 'approved_by_approver.pdf', download_url: '#' }
                        ]
                    }
                ],
                disapprovedByApprover: [
                    {
                        id: 6,
                        name: 'Diana Prince',
                        vl_credit: 14.000,
                        sl_credit: 9.000,
                        vl_to_monetize: 3.000,
                        sl_to_monetize: 0.000,
                        total_days: 3.000,
                        amount: 3000.00,
                        type_id: 1,
                        created_at: '2024-01-16',
                        approve_1: false,
                        approve_2: false,
                        disapprove_1: true,
                        disapprove_2: false,
                        level_1_disapprover: 'Manager F',
                        disapprove_date_1: '2024-01-17',
                        attachments: [
                            { name: 'disapproved_by_approver.pdf', download_url: '#' }
                        ]
                    }
                ]
            }
        }
    },

    async getLeaveBalances() {
        await new Promise(resolve => setTimeout(resolve, 500))
        return {
            data: {
                balances: [
                    { type: 'Vacation Leave', balance: 15.000, leave_type_id: 16 },
                    { type: 'Sick Leave', balance: 10.000, leave_type_id: 3 },
                    { type: 'Maternity Leave', balance: 0.000, leave_type_id: 4 },
                    { type: 'Paternity Leave', balance: 0.000, leave_type_id: 5 },
                    { type: 'Study Leave', balance: 0.000, leave_type_id: 6 },
                    { type: 'Special Leave', balance: 0.000, leave_type_id: 7 }
                ]
            }
        }
    },

    async createMonetization(data) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'Monetization created successfully' } }
    },

    async updateMonetization(id, data) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'Monetization updated successfully' } }
    },

    async deleteMonetization(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Monetization deleted successfully' } }
    },

    async approveMonetization(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Monetization approved successfully' } }
    },

    async disapproveMonetization(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'Monetization disapproved' } }
    },

    // OB Application API methods
    async getOBData() {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return {
            data: {
                allowed: true,
                isSupervisor: true,
                pendingOB: [
                    {
                        id: 1,
                        date: '2024-01-15',
                        date_time_from: '2024-01-20T08:00',
                        date_time_to: '2024-01-20T17:00',
                        client: 'Department of Trade and Industry',
                        purpose: 'Attend training workshop on trade policies',
                        ob_type: 1,
                        created_at: '2024-01-15',
                        approve_1: false,
                        approve_2: false,
                        disapprove_1: false,
                        disapprove_2: false,
                        is_cancel: false
                    }
                ],
                approvedOB: [
                    {
                        id: 2,
                        date: '2024-01-10',
                        date_time_from: '2024-01-25T09:00',
                        date_time_to: '2024-01-25T16:00',
                        client: 'Philippine Chamber of Commerce',
                        purpose: 'Business meeting and networking event',
                        ob_type: 1,
                        created_at: '2024-01-10',
                        approve_1: true,
                        approve_2: true,
                        disapprove_1: false,
                        disapprove_2: false,
                        approver_1: 'Manager A',
                        approver_2: 'Director B',
                        processed_date: '2024-01-12',
                        processed_date_2: '2024-01-13'
                    }
                ],
                disapprovedOB: [
                    {
                        id: 3,
                        date: '2024-01-05',
                        date_time_from: '2024-01-30T10:00',
                        date_time_to: '2024-01-30T15:00',
                        client: 'Local Business Association',
                        purpose: 'Industry conference attendance',
                        ob_type: 2,
                        created_at: '2024-01-05',
                        approve_1: false,
                        approve_2: false,
                        disapprove_1: true,
                        disapprove_2: false,
                        approver_1: 'Manager C',
                        processed_date: '2024-01-07'
                    }
                ],
                cancelledOB: [
                    {
                        id: 4,
                        date: '2024-01-08',
                        date_time_from: '2024-01-28T08:00',
                        date_time_to: '2024-01-28T17:00',
                        client: 'Government Office',
                        purpose: 'Official meeting with stakeholders',
                        ob_type: 3,
                        created_at: '2024-01-08',
                        approve_1: true,
                        approve_2: true,
                        disapprove_1: false,
                        disapprove_2: false,
                        is_cancel: true,
                        cancelled_by: 'Manager D',
                        canceled_date: '2024-01-25',
                        canceled_remarks: 'Emergency work requirement',
                        attachment_name: 'cancellation_document.pdf'
                    }
                ],
                pendingApprovals: [
                    {
                        id: 5,
                        name: 'Jane Smith',
                        last_name: 'Smith',
                        first_name: 'Jane',
                        date: '2024-01-20',
                        date_time_from: '2024-01-25T09:00',
                        date_time_to: '2024-01-25T17:00',
                        client: 'International Trade Center',
                        purpose: 'International trade seminar',
                        ob_type: 2,
                        created_at: '2024-01-20',
                        approve_1: false,
                        approve_2: false,
                        disapprove_1: false,
                        disapprove_2: false,
                        is_cancel: false,
                        attachments: [
                            { id: 1, attachment_name: 'seminar_invitation.pdf', download_url: '#' }
                        ]
                    }
                ],
                approvedByApprover: [
                    {
                        id: 6,
                        name: 'Mike Johnson',
                        last_name: 'Johnson',
                        first_name: 'Mike',
                        date: '2024-01-18',
                        date_time_from: '2024-01-22T08:00',
                        date_time_to: '2024-01-22T16:00',
                        client: 'Regional Development Council',
                        purpose: 'Regional development planning meeting',
                        ob_type: 3,
                        created_at: '2024-01-18',
                        approve_1: true,
                        approve_2: true,
                        disapprove_1: false,
                        disapprove_2: false,
                        is_cancel: false
                    }
                ],
                disapprovedByApprover: [
                    {
                        id: 7,
                        name: 'Sarah Wilson',
                        last_name: 'Wilson',
                        first_name: 'Sarah',
                        date: '2024-01-16',
                        date_time_from: '2024-01-24T10:00',
                        date_time_to: '2024-01-24T15:00',
                        client: 'Professional Association',
                        purpose: 'Professional development workshop',
                        ob_type: 1,
                        created_at: '2024-01-16',
                        approve_1: false,
                        approve_2: false,
                        disapprove_1: true,
                        disapprove_2: false,
                        is_cancel: false,
                        remarks: 'Insufficient justification for travel'
                    }
                ],
                cancelledByApprover: [
                    {
                        id: 8,
                        name: 'Tom Brown',
                        last_name: 'Brown',
                        first_name: 'Tom',
                        date: '2024-01-14',
                        date_time_from: '2024-01-26T09:00',
                        date_time_to: '2024-01-26T17:00',
                        client: 'Industry Conference',
                        purpose: 'Annual industry conference attendance',
                        ob_type: 2,
                        created_at: '2024-01-14',
                        approve_1: true,
                        approve_2: true,
                        disapprove_1: false,
                        disapprove_2: false,
                        is_cancel: true,
                        cancelled_by: 'Manager E',
                        canceled_date: '2024-01-23',
                        canceled_remarks: 'Critical project deadline'
                    }
                ]
            }
        }
    },

    async createOB(data) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'OB application created successfully' } }
    },

    async updateOB(id, data) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'OB application updated successfully' } }
    },

    async deleteOB(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'OB application deleted successfully' } }
    },

    async approveOB(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'OB application approved successfully' } }
    },

    async disapproveOB(id) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'OB application disapproved' } }
    },

    async cancelOB(id, data) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return { data: { message: 'OB application cancelled successfully' } }
    },

    // DTR API methods
    async getDTRData() {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return {
            data: {
                employeeInfo: {
                    employee_id: 1,
                    name: 'John Doe',
                    employee_no: 'EMP-2024-001',
                    employment_type: 'Regular',
                    department: 'Human Resources',
                    position: 'HR Officer',
                    photo: null
                },
                dtrRecords: [
                    {
                        id: 1,
                        payroll_interval: 'Monthly',
                        cut_off: '1st Cut-off',
                        attendance_start_date: '2024-01-01',
                        attendance_end_date: '2024-01-15',
                        employee_id: 1,
                        payroll_period_id: 1
                    },
                    {
                        id: 2,
                        payroll_interval: 'Monthly',
                        cut_off: '2nd Cut-off',
                        attendance_start_date: '2024-01-16',
                        attendance_end_date: '2024-01-31',
                        employee_id: 1,
                        payroll_period_id: 2
                    }
                ]
            }
        }
    },

    async getDTRDetail(employeeId, dtrId) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return {
            data: {
                employeeInfo: {
                    name: 'John Doe',
                    employee_no: 'EMP-2024-001',
                    employment_type: 'Regular',
                    department: 'Human Resources',
                    position: 'HR Officer',
                    photo: null
                },
                periodInfo: {
                    attendance_start_date: '2024-01-01',
                    attendance_end_date: '2024-01-15'
                },
                dtrRecords: [
                    {
                        id: 1,
                        date: '2024-01-01',
                        am_in: '08:00',
                        am_out: '12:00',
                        break_in: '12:00',
                        break_out: '13:00',
                        pm_in: '13:00',
                        pm_out: '17:00',
                        ot_hours: 0,
                        late: 0,
                        undertime: 0,
                        leave: 0,
                        absent: 0,
                        work_hours: 8,
                        remarks: ''
                    },
                    {
                        id: 2,
                        date: '2024-01-02',
                        am_in: '08:15',
                        am_out: '12:00',
                        break_in: '12:00',
                        break_out: '13:00',
                        pm_in: '13:00',
                        pm_out: '17:00',
                        ot_hours: 0,
                        late: 0.25,
                        undertime: 0,
                        leave: 0,
                        absent: 0,
                        work_hours: 7.75,
                        remarks: 'Late arrival'
                    }
                ],
                totals: {
                    ot: 0,
                    late: 0.25,
                    undertime: 0,
                    leave: 0,
                    absent: 0,
                    work_hours: 15.75
                }
            }
        }
    },

    async getEmployeeInfo(employeeId) {
        await new Promise(resolve => setTimeout(resolve, 500))
        return {
            data: {
                name: 'John Doe',
                employee_no: 'EMP-2024-001',
                employment_type: 'Regular',
                department: 'Human Resources',
                position: 'HR Officer',
                photo: null
            }
        }
    },

    async getTimeLogs(employeeId, startDate, endDate) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return {
            data: [
                {
                    id: 1,
                    date: '2024-01-01',
                    am_in: '08:00',
                    am_out: '12:00',
                    break_in: '12:00',
                    break_out: '13:00',
                    pm_in: '13:00',
                    pm_out: '17:00',
                    attachment: null
                },
                {
                    id: 2,
                    date: '2024-01-02',
                    am_in: '08:15',
                    am_out: '12:00',
                    break_in: '12:00',
                    break_out: '13:00',
                    pm_in: '13:00',
                    pm_out: '17:00',
                    attachment: null
                }
            ]
        }
    },

    async saveDTRLogs(employeeId, timeLogs) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'DTR logs saved successfully' } }
    },

    // Overtime Application API methods
    async getOvertimeData() {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return {
            data: {
                employeeInfo: {
                    id: 1,
                    name: 'John Doe',
                    employee_no: 'EMP-2024-001'
                },
                allowed: true,
                overtimeTaxCode: [
                    { id: 1, name: 'Regular Overtime' },
                    { id: 2, name: 'Holiday Overtime' }
                ],
                serviceCredit: 10,
                supervisorId: 2,
                overtimeTypes: [
                    { id: 1, name: 'Regular Overtime' },
                    { id: 2, name: 'Holiday Overtime' },
                    { id: 3, name: 'Weekend Overtime' }
                ],
                pendingOvertime: [
                    {
                        id: 1,
                        created_at: '2024-01-15T10:00:00',
                        date: '2024-01-20',
                        date_time_from: '2024-01-20T18:00:00',
                        date_time_to: '2024-01-20T22:00:00',
                        total_hours: 4,
                        remarks: 'Additional work on project',
                        overtime_type_name: 'Regular Overtime',
                        payroll: 1,
                        service_credits: 0
                    }
                ],
                approvedOvertime: [
                    {
                        id: 2,
                        created_at: '2024-01-10T09:00:00',
                        date: '2024-01-15',
                        date_time_from: '2024-01-15T17:00:00',
                        date_time_to: '2024-01-15T21:00:00',
                        total_hours: 4,
                        remarks: 'Emergency work',
                        overtime_type_name: 'Regular Overtime',
                        payroll: 1,
                        service_credits: 0
                    }
                ],
                disapprovedOvertime: [],
                cancelledOvertime: [],
                cocOvertime: [],
                forApprovalOvertime: [
                    {
                        id: 3,
                        employee_name: 'Jane Smith',
                        created_at: '2024-01-16T11:00:00',
                        date: '2024-01-21',
                        date_time_from: '2024-01-21T18:00:00',
                        date_time_to: '2024-01-21T22:00:00',
                        total_hours: 4,
                        remarks: 'Project deadline',
                        overtime_type_name: 'Regular Overtime',
                        payroll: 1,
                        service_credits: 0,
                        employee_id: 2,
                        attachments: [
                            {
                                id: 1,
                                attachment_name: 'overtime_request.pdf',
                                download_url: '/downloads/overtime_request.pdf'
                            }
                        ]
                    }
                ],
                supervisorApprovedOvertime: [],
                supervisorDisapprovedOvertime: [],
                supervisorCancelledOvertime: []
            }
        }
    },

    async addOvertimeApplication(formData) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'Overtime application saved successfully' } }
    },

    async updateOvertimeApplication(id, formData) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'Overtime application updated successfully' } }
    },

    async deleteOvertimeApplication(id) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'Overtime application deleted successfully' } }
    },

    async approveOvertimeApplication(id) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'Overtime application approved successfully' } }
    },

    async disapproveOvertimeApplication(id) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'Overtime application disapproved successfully' } }
    },

    async cancelOvertimeApplication(formData) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return { data: { message: 'Overtime application cancelled successfully' } }
    },

    // Payslip API methods
    async getPayslipList() {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return {
            data: {
                employeeInfo: {
                    employee_id: 1
                },
                payslipRecords: [
                    {
                        id: 1,
                        payroll_interval: 'Monthly',
                        cut_off: '1st Cut-off',
                        payroll_start_date: '2024-01-01',
                        payroll_end_date: '2024-01-15',
                        employee_id: 1
                    },
                    {
                        id: 2,
                        payroll_interval: 'Monthly',
                        cut_off: '2nd Cut-off',
                        payroll_start_date: '2024-01-16',
                        payroll_end_date: '2024-01-31',
                        employee_id: 1
                    }
                ]
            }
        }
    },

    async getPayslipDetail(employeeId, payslipId) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return {
            data: {
                photo: null,
                name: 'John Doe',
                employee_no: 'EMP-2024-001',
                employment_type: 'Regular',
                department: 'Human Resources',
                position: 'HR Officer',
                payroll_period: 'Jan 1-15, 2024',
                salary: 25000,
                ot_pay: 1200,
                nd_pay: 300,
                holiday_pay: 500,
                total_income: 1000,
                late_amount: 100,
                ut_amount: 50,
                absent_amount: 0,
                gsis: 500,
                sss: 0,
                pagibig: 200,
                philhealth: 150,
                tax: 800,
                total_deduction: 300,
                gross_amount: 28000,
                net_pay: 26000
            }
        }
    },

    async getPayslipPrint(employeeId, payslipId) {
        await new Promise(resolve => setTimeout(resolve, 1000))
        return {
            data: {
                payslip: {
                    name: 'John Doe',
                    department: 'Human Resources',
                    payroll_period: 'Jan 1-15, 2024',
                    salary: 25000,
                    holiday_pay: 500,
                    total_income: 1000,
                    tardiness_amount: 100,
                    lwop_amount: 0,
                    tax: 800,
                    gsis: 500,
                    pagibig: 200,
                    philhealth: 150,
                    net_pay: 26000
                },
                company: { name: 'Company Name' },
                logo1: '',
                logo2: '',
                incomes: [
                    { income: 'Bonus', amount: 500 },
                    { income: 'Allowance', amount: 500 }
                ],
                deductions: [
                    { deduction: 'Loan', amount: 200 }
                ],
                signatory: { name: 'ATTY. FARIDA D. ROMILLO-MATEO', position: 'Municipal Accountant' }
            }
        }
    },

    async getPayslipReportFilters() {
        await new Promise(resolve => setTimeout(resolve, 500))
        return {
            data: {
                intervals: [
                    { id: 1, name: 'Monthly' },
                    { id: 2, name: 'Bi-Monthly' }
                ],
                departments: [
                    { id: 1, name: 'Human Resources' },
                    { id: 2, name: 'Finance' }
                ],
                employees: [
                    { id: 1, name: 'John Doe' },
                    { id: 2, name: 'Jane Smith' }
                ]
            }
        }
    }
} 