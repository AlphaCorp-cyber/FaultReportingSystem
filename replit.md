# Redcliff Municipality Fault Reporting System

## Overview

This is a web-based fault reporting system designed for Redcliff Municipality in Zimbabwe's Midlands Province. The system digitizes the traditional manual fault reporting process, enabling residents to report infrastructure issues (water pipes, potholes, streetlights) online while providing municipal staff with tools to efficiently manage, track, and resolve these reports.

The system aims to enhance municipal service delivery through improved accountability, real-time tracking, and data-driven decision making to support Redcliff's vision of becoming a city by 2030.

## System Architecture

### Frontend Architecture
- **Framework**: Traditional HTML/CSS/JavaScript with Bootstrap for responsive design
- **Structure**: Multi-page web application with server-side rendering
- **UI Components**: Bootstrap-based responsive interface optimized for both desktop and mobile devices
- **JavaScript**: Vanilla JavaScript with jQuery for DOM manipulation and AJAX operations

### Backend Architecture
- **Architecture Pattern**: Not explicitly defined in current files, but appears to follow a traditional MVC pattern
- **API Structure**: REST API endpoints under `/api/` namespace
- **File Upload**: Supports image and PDF uploads with 5MB size limit
- **Authentication**: Payment record verification system to ensure only rate-paying residents can submit reports

### Data Storage
- **Database**: PostgreSQL relational database with proper indexing and constraints
- **Data Types**: Fault reports, user authentication data, payment records, file attachments, notifications, activity logs
- **Storage**: Centralized database for all system data with secure file storage for attachments
- **Security**: Prepared statements to prevent SQL injection, proper password hashing

## Key Components

### 1. Fault Reporting Module
- **Purpose**: Allow residents to submit fault reports with descriptions, location, and photo evidence
- **Features**: Form validation, file upload support, geolocation integration
- **Validation**: Client-side and server-side validation for data integrity

### 2. User Authentication System
- **Dual Login Portals**: Separate login interfaces for administrators (`/admin/login.php`) and residents (`/auth/login.php`)
- **Role-Based Access**: Admin users have access to management functions, residents can only report and track faults
- **Verification Method**: Payment record validation ensures only paying residents can report faults
- **Security**: Prevents unauthorized access and spam reports with proper password hashing
- **Integration**: Links with municipal payment systems

### 3. Staff Management Dashboard
- **Purpose**: Municipal staff interface for managing, assigning, and tracking fault reports
- **Features**: Real-time updates, report assignment, status tracking, prioritization tools
- **Analytics**: Data visualization for trend analysis and resource planning

### 4. File Management System
- **Supported Formats**: JPG, JPEG, PNG, GIF, PDF
- **Size Limits**: 5MB maximum file size
- **Security**: File type validation and secure storage

### 5. Real-time Updates
- **Auto-refresh**: 5-minute intervals for dashboard updates
- **Notifications**: Alert system for status changes and new reports
- **Data Synchronization**: Ensures all users see current information

## Data Flow

1. **Report Submission**: Residents submit fault reports through web forms
2. **Verification**: System validates user eligibility through payment records
3. **Processing**: Reports are stored in central database with file attachments
4. **Assignment**: Municipal staff receive and assign reports to appropriate departments
5. **Tracking**: Status updates flow through the system in real-time
6. **Resolution**: Completed reports are marked as resolved with outcome details
7. **Analytics**: Historical data feeds into planning and optimization systems

## External Dependencies

### Frontend Dependencies
- **Bootstrap**: Responsive UI framework
- **jQuery**: DOM manipulation and AJAX operations
- **Font Libraries**: Segoe UI and fallback fonts for consistent typography

### Backend Dependencies
- **Database System**: Relational database (implementation to be determined)
- **File Storage**: Server-side file storage system
- **Payment System Integration**: Municipal payment record verification

### Third-party Services
- **Geolocation Services**: For fault location mapping
- **Email/SMS Services**: For notifications (implied functionality)

## Deployment Strategy

### Current Setup
- **Architecture**: Traditional web application deployment
- **Assets**: Static CSS and JavaScript files served from `/assets/` directory
- **API**: RESTful endpoints under `/api/` namespace
- **File Storage**: Server-side storage for uploaded attachments

### Scalability Considerations
- **Database**: Designed for growth with historical data retention
- **Performance**: Auto-refresh intervals and optimized data loading
- **Mobile Responsiveness**: Bootstrap-based design for multi-device access

## Changelog

- July 08, 2025. Initial setup
- July 08, 2025. Successfully migrated from Replit Agent to standard Replit environment
- July 08, 2025. Implemented separate admin and resident login portals
- July 08, 2025. Added PostgreSQL database integration with proper security measures
- July 08, 2025. Fixed authentication system with role-based access control and migration to Replit completed
  - PostgreSQL database configured and populated with schema
  - Authentication system fixed with proper path handling
  - All core functionality tested and working
  - Admin and resident dashboards operational
  - Fault reporting system with file uploads functional
  - Department assignment and status tracking implemented
- July 08, 2025. Completed comprehensive PostgreSQL syntax fixes and system testing
  - Fixed all MySQL to PostgreSQL conversion issues (DATE_FORMAT, DATEDIFF, INTERVAL syntax)
  - Resolved boolean vs integer comparison issues throughout the system
  - Enhanced CSS with improved text contrast and color visibility
  - Created departments table with sample data
  - Fixed division by zero errors in analytics
  - All 13 main pages tested and working: Admin (Dashboard, Analytics, Reports, Manage Faults, Manage Users, Departments) + Resident (Dashboard, Submit Fault, My Faults, Profile) + Auth (Login pages) + Main Index
- July 11, 2025. Completed migration from Replit Agent to standard Replit environment
  - Successfully migrated PostgreSQL database with proper schema conversion
  - Removed admin/department login links from public landing page for security
  - Made login portals dedicated (no cross-references between admin/resident/department logins)
  - Simplified registration process by removing municipal account number and ID number requirements
  - Updated authentication system to remove payment verification dependencies
  - All login systems working: admin@redcliff.gov.zw, department users, and resident registration
  - System fully operational and meets all requirements from project specification
- July 11, 2025. Fixed all PHP errors and department login redirection issues
  - Created missing activity_log table with proper PostgreSQL schema
  - Fixed department_login.php header issues by removing empty line at start
  - Fixed manage_users.php null value errors with proper null coalescing operators
  - Fixed department login session compatibility with Auth class expectations
  - Fixed department key mapping in database to match DEPARTMENTS constant
  - All login portals now working correctly: admin, department (WATER001, ROADS001, ELEC001), and resident
- July 14, 2025. Implemented comprehensive user verification system with document upload
  - Fixed registration database error by making password column nullable
  - Added user_verification_requests table for document storage and admin review
  - Updated registration to require national ID and current photo upload
  - Added camera functionality for real-time photo capture during registration
  - Created admin interface (verify_users.php) for reviewing and approving/rejecting users
  - Implemented verification status tracking (pending, approved, rejected)
  - Added admin notes system for rejection reasons
  - Updated login system to prevent unverified users from accessing the system
  - Enhanced user authentication with proper verification checks
- July 11, 2025. Completed final migration to Replit environment
  - Fixed database schema with proper PostgreSQL syntax and constraints
  - Added department_code, password_hash, is_active, and department columns to users table
  - Created test users: admin (admin@redcliff.gov.zw), resident (john.doe@example.com), and department users
  - Removed admin/department login links from landing page as requested
  - Made all login portals dedicated without cross-navigation links
  - System fully operational and meets all requirements from the project document
  - All fault reporting categories implemented: water, roads, electricity, streetlights, waste, parks
  - Payment verification system in place for resident registration
  - Real-time tracking and status updates functional

## User Preferences

Preferred communication style: Simple, everyday language.