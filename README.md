# ChikondiCare – Clinic Management System

## 1. Project Overview

**ChikondiCare** is a web-based Clinic Management System designed to computerise and simplify the daily operations of ChikondiCare Community Health Centre.

The system aims to improve the management of patient records, appointments, medical consultations, prescriptions and payments by replacing manual processes with a centralised digital platform.

It provides healthcare staff with an organised way of accessing and managing information while ensuring that each staff member can only access functionalities permitted by their assigned role.

The system is developed as part of a Web Programming assignment using PHP and MySQL.

## 2. System User Roles

The system supports four main categories of users:

### 2.1 Administrator
- Creates and manages staff accounts.
- Assigns and manages user roles.
- Registers and manages doctors.
- Manages the medicine catalogue.
- Configures consultation fees.
- Views system audit logs.
- Generates administrative reports.

### 2.2 Receptionist
- Searches and retrieves patient records.
- Views available appointment slots.
- Books appointments for patients.
- Cancels existing appointments.
- Issues appointment confirmations.
- Views relevant appointment schedules.

### 2.3 Health Care Attendant
- Registers new patients.
- Updates patient registration information.
- Verifies patient appointments.
- Checks patients in upon arrival.
- Records patient payments.
- Generates payment receipts.

### 2.4 Doctor
- Views personal appointment schedules.
- Views patients who have checked in.
- Records consultation diagnoses and observations.
- Prescribes medicines to patients.
- Views historical patient prescriptions and medical records.
- Generates relevant medical reports.

**Note:** Patients do not log in to the system. They interact with healthcare staff directly when registering, booking appointments and receiving healthcare services.

## 3. Main System Features and Modules

The system consists of the following functional modules:

### 3.1 Authentication and Access Control
- Staff login and logout.
- Password management.
- Role-Based Access Control (RBAC).
- Restricted access to authorised functionalities.
- Automatic session termination after inactivity.

### 3.2 System Administration
- Staff account management.
- Doctor registration and management.
- Full-time and visiting doctor management.
- Medicine catalogue management.
- Consultation fee configuration.
- Audit log management.

### 3.3 Patient Registration and Management
- Registration of new patients.
- Automatic generation of unique patient identification numbers.
- Recording of personal and contact information.
- Assignment of primary doctors.
- Patient search and record updates.

### 3.4 Appointment Management
- Viewing available appointment slots.
- Booking appointments with assigned doctors.
- Booking with alternative available doctors.
- Managing visiting doctor schedules.
- Appointment cancellation.
- Appointment confirmation generation.

### 3.5 Patient Check-in Management
- Retrieval of daily appointments.
- Verification of patient appointments.
- Recording patient check-in.
- Updating appointment status.

### 3.6 Medical Consultation Management
- Viewing checked-in patients.
- Recording diagnoses and consultation notes.
- Recording medical observations.
- Prescribing medicines.
- Recording dosage, frequency and medication duration.
- Maintaining prescription history.

### 3.7 Payment Management
- Recording consultation payments.
- Recording payment amounts and methods.
- Generating unique payment transaction numbers.
- Generating printable payment receipts.
- Retrieving payment records.

### 3.8 Search, Filtering and Reporting
- Searching patient records.
- Searching and filtering appointments.
- Filtering appointments by date, doctor and status.
- Searching payment records.
- Pagination of records.
- Generating PDF and Excel reports.

The system supports payment receipts, prescription records, daily appointment lists, patient lists, revenue summaries and visiting doctor schedules.

## 4. Technologies Used

The following technologies are used in developing the application:

| Technology | Purpose |
|---|---|
| PHP | Server-side programming and application logic |
| MySQL | Database management and data storage |
| PDO | Secure database connectivity and prepared statements |
| HTML | Structuring application pages |
| CSS | Styling and designing the user interface |
| JavaScript | Client-side validation and interactive functionality |

Additional implementation techniques include Object-Oriented Programming (OOP), Git version control and database normalisation up to Third Normal Form (3NF).

## 5. Application Design

The application follows an object-oriented design approach to promote modularity, reusability and maintainability.

The system is organised around the following design principles:

- **Object-Oriented Programming:** Uses classes and objects to represent system entities such as users, doctors, patients, appointments, prescriptions, medicines and payments.
- **Encapsulation:** Keeps related data and operations within appropriate classes.
- **Inheritance:** Uses inheritance to represent specialised entities such as full-time and visiting doctors.
- **Abstraction:** Uses abstract classes where appropriate to define common behaviours.
- **Database Design:** Uses a relational MySQL database structured and normalised to Third Normal Form (3NF).
- **Database Connectivity:** Uses PDO with a Singleton database connection approach.
- **Role-Based Design:** Provides different interfaces, menus and functionalities depending on the authenticated user's role.
- **Exception Handling:** Uses custom exceptions and try-catch blocks to handle application errors.

The application is designed to provide a consistent, user-friendly interface with clear navigation and role-specific dashboards.

## 6. Security and Privacy

Since the system handles sensitive patient and medical information, security and privacy are important considerations throughout its development.

The application incorporates the following security measures:

1. **Password Hashing:** Passwords are securely hashed before being stored in the database.
2. **SQL Injection Prevention:** PDO prepared statements are used to prevent SQL injection attacks.
3. **Cross-Site Scripting (XSS) Prevention:** User-generated content is escaped before being displayed.
4. **Cross-Site Request Forgery (CSRF) Protection:** CSRF tokens are implemented on forms that perform state-changing operations.
5. **Secure Sessions:** Session management is implemented to protect authenticated users.
6. **Brute-Force Protection:** Temporary account lockout is applied after repeated unsuccessful login attempts.
7. **Input Validation and Sanitisation:** Both client-side and server-side validation are used to ensure that submitted data meets the required conditions.
8. **Secure Configuration:** Sensitive configuration details and database credentials are kept outside the public web root or in environment configuration files and excluded from Git.
9. **Audit Logging:** Important activities, including access to patient records and prescriptions, are logged for accountability.
10. **Role-Based Access Control:** Access to sensitive information and system functionalities is restricted according to assigned user permissions.

These measures aim to protect patient confidentiality, maintain data integrity and prevent unauthorised access to the system.

---

## Project Information

**Project Name:** ChikondiCare Clinic Management System

**Project Type:** Web-Based Application

**Course:** Web Programming

**Backend:** PHP

**Database:** MySQL

**Version Control:** Git and GitHub

**Project Contributors:** Group of Four Students

**Project Status:** Under Development
