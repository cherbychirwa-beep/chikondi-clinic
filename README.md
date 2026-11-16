# Chikondi Clinic Management System (ChikondiCare)

A web-based clinic management system developed as a group project for
**Web Programming Assignment 1**. The system is intended to computerise
the daily operations of ChikondiCare, including staff access, patient
registration, appointment booking, patient check-in, medical
consultations, prescriptions, payments, and reporting.

> **Project status:** In development\
> **Assignment:** Web Programming Assignment 1\
> **Due date:** 5 December 2026

## Project Overview

ChikondiCare is a community health centre serving more than 2,000
registered patients. The proposed application will help authorised
clinic staff manage patient information and clinic activities through a
centralised web-based system.

Patients do not log in to the application. They interact with clinic
staff, while staff members access the system according to their assigned
roles.

## Objectives

-   Computerise patient registration and record management.
-   Manage doctors and their availability.
-   Allow receptionists to book and cancel appointments.
-   Support patient check-in and medical consultations.
-   Record diagnoses, consultation notes, and prescriptions.
-   Record patient payments and generate receipts.
-   Provide search, filtering, pagination, and management reports.
-   Protect sensitive patient information through authentication,
    authorisation, validation, and security controls.

## User Roles

  -----------------------------------------------------------------------
  Role                                Main responsibilities
  ----------------------------------- -----------------------------------
  Administrator                       Manages staff accounts, roles,
                                      doctors, medicines, consultation
                                      fees, and audit logs.

  Receptionist                        Views available appointment slots,
                                      books and cancels appointments, and
                                      issues confirmations.

  Health Care Attendant               Registers patients, checks patients
                                      in, and records payments.

  Doctor                              Views own appointments, records
                                      consultation outcomes and
                                      prescriptions, and views records of
                                      patients they have seen.
  -----------------------------------------------------------------------

## Main System Modules

The application is intended to include the following modules:

1.  **Authentication and access control** -- Staff login and logout,
    password changes, session management, and role-based permissions.
2.  **System administration** -- Staff account and role management,
    doctor management, medicine catalogue management, and consultation
    fee settings.
3.  **Patient management** -- Patient registration, unique patient
    numbers, patient search, and updating patient details.
4.  **Appointment management** -- Viewing doctor availability, booking
    appointments, alternative doctor selection, cancellation, and
    printable confirmations.
5.  **Patient check-in** -- Retrieving appointments and recording
    patient check-in.
6.  **Medical consultation** -- Recording diagnoses, notes,
    observations, and prescriptions.
7.  **Payment management** -- Recording payments and generating
    printable receipts.
8.  **Search and reporting** -- Searching, filtering, and paginating
    records, plus generating the required PDF and Excel reports.

## Technology Stack

The assignment specifies the following technologies and constraints:

-   **Backend:** PHP using object-oriented programming (OOP)
-   **Database:** MySQL
-   **Database access:** PDO with prepared statements
-   **Version control:** Git and GitHub
-   **Reports:** PDF and Excel generation

The frontend technologies and any additional libraries will be
documented here as the project implementation is finalised.

## Object-Oriented Design Requirements

The system is expected to demonstrate:

-   Classes and objects, including `User`, `Doctor`, `FullTimeDoctor`,
    `VisitingDoctor`, `Patient`, `Appointment`, `Prescription`,
    `Medicine`, and `Payment`.
-   Inheritance, including full-time and visiting doctors extending an
    abstract `Doctor` class.
-   Role classes extending `User`.
-   A Singleton database connection.
-   Custom exceptions, such as `ValidationException`,
    `AuthorizationException`, and `SlotUnavailableException`.
-   Namespaces and autoloading.

## Security Requirements

Because the application handles sensitive health information, security
is an important part of the project. The implementation is expected to
include:

-   Password hashing.
-   SQL injection prevention using prepared statements.
-   Output escaping to reduce cross-site scripting (XSS) risks.
-   CSRF tokens on state-changing forms.
-   Secure session handling.
-   Temporary lockout after repeated failed login attempts.
-   Input sanitisation and whitelist validation.
-   Secure configuration, with credentials stored in environment
    configuration or outside the web root and excluded from Git.
-   Audit logging of access to patient records and prescriptions.

## Database

The database will be designed and normalised to **Third Normal Form
(3NF)**. Database scripts and setup instructions will be added to the
repository as development progresses.

## Reports

The required reports are:

  -----------------------------------------------------------------------
  Report                  Format                  Access
  ----------------------- ----------------------- -----------------------
  Payment receipt         PDF                     Health Care Attendant,
                                                  Administrator

  Patient prescription    PDF                     Doctor, Administrator
  record                                          

  Daily appointment list  PDF and Excel           Doctor (own),
  per doctor                                      Receptionist,
                                                  Administrator

  Patient list per doctor Excel                   Administrator, Doctor
                                                  (own)

  Revenue and attendance  PDF with chart or       Administrator
  summary                 table, and Excel        

  Visiting doctor         PDF                     Administrator,
  schedule                                        Receptionist
  -----------------------------------------------------------------------

## Getting Started

The project setup instructions will be completed once the source code
and database structure are in place.

### Prerequisites

-   A web server with PHP support (for example, Apache).
-   A PHP version compatible with the project's dependencies.
-   MySQL server.
-   Git.
-   A web browser.

### Setup

1.  Clone the repository:

    ``` bash
    git clone https://github.com/<your-github-username>/chikondi-clinic.git
    ```

2.  Move into the project directory:

    ``` bash
    cd chikondi-clinic
    ```

3.  Create a MySQL database for the application.

4.  Import the project's SQL script when it is available.

5.  Configure the database connection using the project's environment
    configuration. Do not commit database passwords or other secrets to
    Git.

6.  Configure the web server's document root according to the project
    structure.

7.  Open the application through the local development server.

> Replace or extend these instructions with the exact PHP version,
> database name, environment variables, and commands used by the
> finished project.

## Group Members

Add the names and responsibilities of all four group members below.

    No. Full name      Student ID       Module / responsibility
  ----- -------------- ---------------- -------------------------
      1 \[Member 1\]   \[Student ID\]   \[Module\]
      2 \[Member 2\]   \[Student ID\]   \[Module\]
      3 \[Member 3\]   \[Student ID\]   \[Module\]
      4 \[Member 4\]   \[Student ID\]   \[Module\]

Each group member is expected to actively participate, implement at
least one functional module, and be prepared to demonstrate and present
their contribution.

## Project Deliverables

The assignment requires the group to submit:

1.  Source code through the Git repository.
2.  An SQL script.
3.  PDF documentation containing the case-study analysis, use case
    diagram, ERD, RBAC matrix, security measures, and setup
    instructions.
4.  A 5--7 minute demonstration covering the patient journey:
    registration, appointment booking, check-in, consultation,
    prescription, payment, and reporting.

## Contribution

Group members should use Git for version control and coordinate their
work through the shared GitHub repository. Use clear commit messages and
review changes before merging them into the main branch.

## Assignment Assessment

The project will be assessed on:

-   Quality of work.
-   User interface design.
-   Usability.
-   Functional completeness.
-   Presentation and demonstration.

------------------------------------------------------------------------

**Note:** This README describes the assignment requirements and intended
system scope. Update it as features are implemented so that it
accurately reflects the final application.
