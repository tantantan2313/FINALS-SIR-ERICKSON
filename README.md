# School Service Transportation Monitoring and Student Safety Confirmation System

## Overview
This is a comprehensive web-based system for monitoring school transportation services at Sta. Rita College, Pampanga. It provides real-time tracking, notifications, payment management, and complete administrative oversight for parents, drivers, and administrators.

## Features

### Admin Features
- **Dashboard**: Overview of system statistics and recent activities
- **User Management**: Manage parents, drivers, and students
- **Driver Approval**: Review and approve driver applications
- **Student Management**: View and manage all student records
- **Reports**: Comprehensive analytics and monthly summaries
- **Payment Generation**: Automated monthly billing system

### Parent Features
- **Student Registration**: Add and manage children
- **Driver Selection**: Choose transportation providers
- **Real-time Tracking**: GPS monitoring of trips (with Google Maps integration)
- **Trip History**: Complete record of all transportation activities
- **Payment Management**: View and pay transportation fees
- **Notifications**: Real-time alerts for trip status updates

### Driver Features
- **Trip Management**: Create and schedule transportation routes
- **Status Updates**: Mark pickup, in-transit, and drop-off events
- **Photo Proof**: Upload images confirming safe student delivery
- **Trip History**: Record of all completed trips and ratings
- **Student Management**: View assigned students and contact information

## Database Schema
- `users`: User accounts (admin, parents, drivers)
- `students`: Student information and assignments
- `drivers`: Driver details and vehicle information
- `trips`: Trip records with GPS coordinates and status
- `notifications`: System notifications and alerts
- `payments`: Payment tracking and billing

## Installation & Setup

### Prerequisites
- XAMPP (or similar with PHP 7.4+ and MySQL 5.7+)
- Web browser with JavaScript enabled
- Google Maps API key (for GPS tracking)

### Setup Steps

1. **Environment Setup**
   - Copy project to `C:\xampp\htdocs\lts_system`
   - Start XAMPP (Apache and MySQL services)
   - Open phpMyAdmin: http://localhost/phpmyadmin

2. **Database Creation**
   - Create database: `school_transport`
   - Import `database.sql` file

3. **Configuration**
   - For GPS tracking: Get Google Maps API key from [Google Cloud Console](https://console.cloud.google.com/google/maps-apis)
   - Replace `YOUR_GOOGLE_MAPS_API_KEY` in `parent/track_trip.php`

4. **Access System**
   - Main URL: http://localhost/lts_system
   - Default admin login: `admin` / `password`
   - Main URL: http://localhost/lts_system
   - Default admin login: `admin` / `password`

## Usage Guide

### For Administrators
1. **Login** with admin credentials
2. **Approve Drivers** in the driver management section
3. **Monitor System** through dashboard and reports
4. **Generate Payments** monthly for billing
5. **Manage Users** and resolve any issues

### For Parents
1. **Register** an account or login
2. **Add Students** with their information
3. **Select Drivers** for transportation
4. **Track Trips** in real-time
5. **Make Payments** for services
6. **View History** of all trips

### For Drivers
1. **Register** and wait for admin approval
2. **Create Trips** for assigned students
3. **Update Status** as trips progress
4. **Upload Photos** for safety confirmation
5. **View History** and performance metrics

## Technologies Used
- **Backend**: PHP 7.4+, MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Mapping**: Google Maps JavaScript API
- **Security**: Password hashing, SQL injection prevention
- **File Uploads**: Image handling for photo proofs

## Security Features
- Password hashing with bcrypt
- SQL injection prevention
- Session-based authentication
- Role-based access control
- File upload validation

## API Integration
- Google Maps API for GPS tracking
- Responsive design for mobile devices
- AJAX-ready for future enhancements

## Future Enhancements
- Real-time GPS updates from mobile apps
- SMS notifications integration
- Payment gateway integration
- Mobile application development
- Advanced reporting and analytics

## Support
For technical support or feature requests, please contact the system administrator.
- PHP 7+
- MySQL
- HTML/CSS/JavaScript
- Google Maps API (for GPS tracking)

## Security Notes
- Passwords are hashed using bcrypt
- Input sanitization implemented
- Role-based access control

## Future Enhancements
- Mobile app integration
- Real-time GPS updates
- Advanced analytics
- Emergency alert system