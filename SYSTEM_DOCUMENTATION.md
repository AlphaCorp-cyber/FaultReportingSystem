
# Redcliff Municipality Fault Reporting System
## Complete System Documentation

**System Version:** 1.0.0  
**Generated Date:** July 17, 2025  
**Technology Stack:** PHP, PostgreSQL, Bootstrap, JavaScript  

---

## Table of Contents

1. [System Overview](#system-overview)
2. [System Architecture](#system-architecture)
3. [Technical Specifications](#technical-specifications)
4. [User Guide](#user-guide)
5. [Login Credentials](#login-credentials)
6. [System Diagrams](#system-diagrams)
7. [Database Design](#database-design)
8. [API Documentation](#api-documentation)
9. [Installation Guide](#installation-guide)
10. [Troubleshooting](#troubleshooting)

---

## 1. System Overview

### 1.1 Purpose
The Redcliff Municipality Fault Reporting System is a web-based platform designed to digitize and streamline the process of reporting, tracking, and managing infrastructure faults within Redcliff Municipality, Zimbabwe.

### 1.2 Key Features
- Online fault reporting with photo evidence
- User verification through payment records
- Real-time status tracking
- Administrative dashboard for fault management
- Predictive analytics for fault prevention
- Department-specific login portals
- Automated notifications and updates
- Comprehensive reporting and analytics

### 1.3 System Objectives
- Allow verified residents to report faults via web interface
- Capture detailed information and location of faults
- Automatically allocate faults to correct municipal departments
- Provide status updates to residents
- Use historical data to predict potential faults

---

## 2. System Architecture

### 2.1 Technology Stack

| Component | Technology | Purpose |
|-----------|------------|---------|
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript | User interface and interactions |
| Backend | PHP 8.2 | Server-side logic and processing |
| Database | PostgreSQL | Data storage and management |
| Web Server | Apache/Nginx | HTTP request handling |
| Deployment | Replit Platform | Cloud hosting and deployment |

### 2.2 System Components
- **Authentication Module:** Handles user login and verification
- **Fault Reporting Module:** Manages fault submission and tracking
- **Admin Dashboard:** Provides management interface for municipal staff
- **Prediction System:** Uses machine learning for fault analytics
- **File Management:** Handles photo evidence and document uploads
- **Notification System:** Manages alerts and status updates

---

## 3. Technical Specifications

### 3.1 Database Schema
**Main Tables:**
- `users`: User accounts and authentication data
- `fault_reports`: Fault submissions and tracking information
- `departments`: Municipal department information
- `fault_progress_updates`: Status change history
- `notifications`: System notifications and alerts
- `activity_log`: User activity tracking

### 3.2 File Upload Specifications
- **Supported formats:** JPG, JPEG, PNG, GIF, PDF
- **Maximum file size:** 5MB
- **Storage location:** `/uploads/evidence/`
- **Security:** File type validation and secure naming

---

## 4. User Guide

### 4.1 Resident Portal
**Step-by-step guide for residents:**

1. Navigate to the system homepage
2. Click "Resident Login" to access the resident portal
3. Enter your registered email and password
4. Upon successful login, access the dashboard
5. Click "Submit New Fault" to report an issue
6. Fill in the fault details form:
   - Select fault category (Water, Roads, Electricity, etc.)
   - Provide descriptive title
   - Enter detailed description
   - Specify exact location
   - Upload photo evidence (optional)
7. Submit the form to generate a reference number
8. Track fault status in "My Faults" section

### 4.2 Admin Portal
**Administrative functions:**

1. Access admin portal via `/admin/login.php`
2. Use admin credentials to log in
3. Dashboard provides overview of system status
4. Manage faults through "Fault Management" section
5. Assign faults to appropriate departments
6. Update fault status and progress
7. Generate reports and analytics
8. Manage user accounts and verifications

---

## 5. Login Credentials

### 5.1 Demo Admin Account
- **Email:** `admin@redcliff.gov.zw`
- **Password:** `admin123`
- **Access URL:** `/admin/login.php`

### 5.2 Demo Resident Account
- **Email:** `john.doe@example.com`
- **Password:** `resident123`
- **Access URL:** `/auth/login.php`

### 5.3 System URLs
- **Homepage:** `/`
- **Resident Login:** `/auth/login.php`
- **Resident Registration:** `/auth/register.php`
- **Admin Login:** `/admin/login.php`
- **Department Dashboard:** `/admin/section_dashboard.php`

---

## 6. System Diagrams

### 6.1 Context Diagram
```
CONTEXT DIAGRAM - Redcliff Fault Reporting System

External Entities:
┌─────────────┐    ┌─────────────────────────────┐    ┌─────────────┐
│  Residents  │◄──►│   Fault Reporting System    │◄──►│ Municipal   │
│             │    │                             │    │ Staff       │
└─────────────┘    │  • User Authentication      │    └─────────────┘
                   │  • Fault Management         │
┌─────────────┐    │  • Status Tracking          │    ┌─────────────┐
│ Department  │◄──►│  • Analytics & Reporting    │◄──►│ System      │
│ Heads       │    │  • File Storage             │    │ Admin       │
└─────────────┘    └─────────────────────────────┘    └─────────────┘
```

### 6.2 Data Flow Diagram (Level 0)
```
DATA FLOW DIAGRAM (Level 0)

        Residents                Municipal Staff
            │                          │
            ▼                          ▼
    ┌───────────────┐          ┌───────────────┐
    │   Login &     │          │   Admin       │
    │Authentication │          │ Dashboard     │
    └───────┬───────┘          └───────┬───────┘
            │                          │
            ▼                          ▼
    ┌───────────────┐          ┌───────────────┐
    │   Submit      │          │   Manage      │
    │   Fault       │◄────────►│   Faults      │
    │   Report      │          │               │
    └───────┬───────┘          └───────┬───────┘
            │                          │
            ▼                          ▼
    ┌─────────────────────────────────────────┐
    │         Central Database                │
    │  • User Data    • Fault Reports         │
    │  • Files        • Status Updates        │
    └─────────────┬───────────────────────────┘
                  │
                  ▼
    ┌─────────────────────────────────────────┐
    │      Analytics & Prediction Engine      │
    │  • Historical Analysis                  │
    │  • Fault Predictions                    │
    └─────────────────────────────────────────┘
```

### 6.3 System Flowchart
```
SYSTEM FLOWCHART

    START
      │
      ▼
   User Access
      │
      ▼
  ┌─Authentication─┐
  │   Required?    │
  └─┬─────────────┬┘
    │ YES         │ NO
    ▼             ▼
  Login         Public
  Process       Pages
    │
    ▼
┌─Credentials─┐
│   Valid?    │
└─┬─────────┬─┘
  │ YES     │ NO
  ▼         ▼
Grant     Display
Access    Error
  │         │
  ▼         ▼
Dashboard   Return
  │       to Login
  ▼
User Actions:
├─ Resident ────► Submit Fault ────► Validate ────► Store
├─ Admin ───────► Manage Faults ───► Update ─────► Notify
└─ Department ──► Process Work ────► Progress ───► Update
  │
  ▼
 END
```

### 6.4 Class Diagram
```
CLASS DIAGRAM

┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│      User       │     │   FaultReport   │     │   Department    │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ +id: int        │     │ +id: int        │     │ +id: int        │
│ +email: string  │────►│ +user_id: int   │     │ +name: string   │
│ +password: hash │     │ +title: string  │     │ +description    │
│ +first_name     │     │ +description    │◄────│ +contact_email  │
│ +last_name      │     │ +location       │     │ +status         │
│ +role: enum     │     │ +category       │     └─────────────────┘
│ +status         │     │ +status         │
├─────────────────┤     │ +priority       │
│ +login()        │     │ +created_at     │
│ +logout()       │     ├─────────────────┤
│ +verify()       │     │ +submit()       │
└─────────────────┘     │ +update()       │
                        │ +assign()       │
                        └─────────────────┘
```

### 6.5 Sequence Diagram - Fault Submission Process
```
SEQUENCE DIAGRAM - Fault Submission Process

Resident    System    Database    Admin    Department
   │          │          │         │         │
   │─ Login ──►│          │         │         │
   │          │─ Verify ─►│         │         │
   │          │◄─ User ───│         │         │
   │◄─ Auth ──│          │         │         │
   │          │          │         │         │
   │─Submit ──►│          │         │         │
   │  Fault   │─ Store ──►│         │         │
   │          │◄─ ID ────│         │         │
   │◄─ Ref# ──│          │         │         │
   │          │─ Notify ─────────►│         │
   │          │          │        │─Assign─►│
   │          │◄─ Update ─────────│         │
   │          │─ Store ──►│         │         │
   │◄─Update──│          │         │         │
```

---

## 7. Database Design

### 7.1 Entity Relationship Diagram
**Main Entities and Relationships:**

**Users Entity:**
- `id` (Primary Key)
- `email` (Unique)
- `password_hash`
- `first_name`, `last_name`
- `role` (admin, resident, department)
- `verification_status`

**Fault_Reports Entity:**
- `id` (Primary Key)
- `reference_number` (Unique)
- `user_id` (Foreign Key → Users)
- `category`, `title`, `description`
- `location`, `latitude`, `longitude`
- `status`, `priority`
- `assigned_department`, `assigned_to`

**Relationships:**
- Users (1) → (Many) Fault_Reports
- Departments (1) → (Many) Fault_Reports
- Fault_Reports (1) → (Many) Progress_Updates

### 7.2 Database Tables
- `users`: 8 columns, stores user authentication and profile data
- `fault_reports`: 20 columns, main fault tracking information
- `departments`: 8 columns, municipal department details
- `fault_progress_updates`: 7 columns, status change history
- `notifications`: 7 columns, system alerts and messages
- `activity_log`: 6 columns, user action tracking

---

## 8. API Documentation

### 8.1 API Endpoints

#### GET /api/get_fault_details.php
- **Purpose:** Retrieve detailed fault information
- **Parameters:** `fault_id`
- **Response:** JSON fault object

#### POST /api/update_fault_status.php
- **Purpose:** Update fault status and progress
- **Parameters:** `fault_id`, `status`, `notes`
- **Response:** Success/error message

#### POST /api/upload_evidence.php
- **Purpose:** Upload photo evidence for faults
- **Parameters:** `fault_id`, file upload
- **Response:** File URL and confirmation

#### GET /api/get_fault_progress.php
- **Purpose:** Get fault progress history
- **Parameters:** `fault_id`
- **Response:** Progress update array

---

## 9. Installation Guide

### 9.1 System Requirements
- **Web Server:** Apache 2.4+ or Nginx
- **PHP:** Version 8.0 or higher
- **Database:** PostgreSQL 12+ or MySQL 8.0+
- **Storage:** Minimum 1GB for application files
- **Memory:** 512MB RAM minimum
- **Extensions:** PDO, GD, mbstring, openssl

### 9.2 Installation Steps
1. Clone or download system files
2. Configure database connection in `config/config.php`
3. Run database setup script: `php setup_database.php`
4. Set file permissions for uploads directory
5. Configure web server virtual host
6. Test system access and functionality
7. Create initial admin user account

---

## 10. Troubleshooting

### 10.1 Common Issues

#### Database Connection Error:
- Check database credentials in `config.php`
- Verify database server is running
- Ensure database exists and is accessible

#### File Upload Failures:
- Check uploads directory permissions (755)
- Verify file size limits in PHP configuration
- Ensure supported file types are being used

#### Login Issues:
- Verify user credentials in database
- Check session configuration
- Clear browser cache and cookies

#### Permission Denied Errors:
- Check file and directory permissions
- Verify web server user has access
- Review security configurations

### 10.2 System Logs
**Important log locations:**
- **PHP Error Log:** Check server error logs
- **Application Log:** Database queries and user actions
- **Web Server Log:** HTTP request and response logs
- **System Activity:** Available in admin dashboard

---

## System Features Summary

### Fault Prediction System
- **Machine Learning:** RandomForest models for fault frequency, category, and resolution time prediction
- **Risk Assessment:** Location-based risk scoring with high/medium/low classifications
- **Seasonal Analysis:** Pattern identification for peak fault periods and locations
- **Automated Recommendations:** AI-generated suggestions for resource allocation and monitoring
- **Predictive Analytics:** Historical data analysis to forecast municipal infrastructure issues

### File Management System
- **Supported Formats:** JPG, JPEG, PNG, GIF, PDF
- **Size Limits:** 5MB maximum file size
- **Security:** File type validation and secure storage

### Real-time Updates
- **Auto-refresh:** 5-minute intervals for dashboard updates
- **Notifications:** Alert system for status changes and new reports
- **Data Synchronization:** Ensures all users see current information

---

**End of Documentation**  
*Generated by Redcliff Municipality Fault Reporting System*
