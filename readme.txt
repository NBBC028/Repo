# NEUST Repository System

## Overview
The NEUST Repository System is a web-based application designed to manage and provide access to research papers and academic works from Nueva Ecija University of Science and Technology. The system supports different user roles (admin, faculty, student, guest) with varying levels of access and functionality.

## Features
- **Role-based Access Control**: Different dashboards and permissions for admin, faculty, student, and guest users
- **Research Upload**: Faculty and admin can upload research papers in PDF format
- **Advanced Search**: Search research papers by title, author, department, year, and keywords
- **Reporting**: Generate statistical reports on research papers (admin only)
- **User Management**: Admin can manage user accounts and permissions
- **Responsive Design**: Works on desktop and mobile devices

## System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation
1. Clone or extract the repository files to your web server directory
2. Create a MySQL database named 'neust_repository'
3. Import the database schema (automatically created on first run)
4. Configure database connection in 'includes/db.php' if needed
5. Access the system through your web browser

## Default Admin Account
- Username: admin
- Password: admin123
- Note: Please change the default password after first login

## Directory Structure
```
NEUST_REPOSITORY_SYSTEM/
│── admin/                  # Admin-specific pages
│── faculty/                # Faculty-specific pages
│── student/                # Student-specific pages
│── guest/                  # Guest-specific pages
│── assets/                 # CSS, JavaScript, and images
│── includes/               # Common PHP includes
│── uploads/                # Stored research files
│── views/                  # Shared view pages
│── index.php               # Landing page
│── login.php               # Login page
│── register.php            # Registration page
│── logout.php              # Logout functionality
```

## User Roles
1. **Admin**
   - Manage all research papers
   - Manage user accounts
   - Generate reports
   - Full system access

2. **Faculty**
   - Upload and manage own research papers
   - Search all research papers
   - View statistics

3. **Student**
   - Search and view public research papers
   - View research papers from their department
   - Limited access to restricted papers

4. **Guest**
   - View only public research abstracts
   - Basic search functionality
   - No access to full papers

## Usage Guide
1. **Login/Registration**
   - Use the login page to access your account
   - New users can register with appropriate role

2. **Dashboard**
   - Each role has a customized dashboard
   - Quick access to common functions

3. **Uploading Research**
   - Faculty/Admin: Use "Upload Research" button
   - Fill in required metadata and attach PDF file
   - Set access status (public/restricted)

4. **Searching**
   - Use quick search on dashboard
   - Advanced search available with multiple filters
   - Results displayed based on user role permissions

5. **Reports (Admin)**
   - Generate statistical reports
   - Filter by date range
   - Export to CSV or PDF

## Security Features
- Password hashing
- Input sanitization
- Session management
- Role-based access control
- File type validation

## Troubleshooting
- If database connection fails, check credentials in db.php
- Ensure 'uploads' directory has write permissions
- For PDF viewing issues, check browser PDF plugin settings

## Credits
Developed for Nueva Ecija University of Science and Technology