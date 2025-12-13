# VetConnect Web Project

## Features

VetConnect is a web platform designed to connect pet owners with veterinarians, manage pet care, and streamline veterinary appointments. Below are the main features integrated into this project:

### 1. User Authentication
- Secure login and registration for pet owners and veterinarians
- Session management to protect user data

### 2. Pet Owner Dashboard
- View and manage nearby veterinarians (location-based search)
- Contact veterinarians directly from the dashboard
- View and manage upcoming schedules and appointments
- Receive notifications for upcoming appointments and important events

### 3. Veterinarian Dashboard
- (If implemented) Manage appointments and view pet owner requests
- Profile management for veterinarians, including clinic details and location

### 4. Real-Time Messaging System
- Separate panels for notifications and messages
- Toggle between notifications and messages with a clean, non-overlapping UI
- Responsive design for desktop and mobile devices
- Placeholder for real-time chat with veterinarians (future enhancement)

### 5. Schedule Management
- Add, edit, and view upcoming appointments
- Select veterinarian, appointment type, and date/time
- Receive reminders and urgent notifications for upcoming events

### 6. Profile Customization
- Upload and update profile pictures
- Edit personal and clinic information
- Set and update location for accurate veterinarian search

### 7. Responsive UI
- Mobile and desktop-friendly layout
- Bottom navigation for mobile/tablet users
- Side pop-up for profile management

### 8. Security and Data Handling
- Prepared statements for database queries to prevent SQL injection
- User session validation for protected pages

### 9. File Uploads
- (If implemented) Upload and manage files such as profile pictures

### 10. Modular Code Structure
- Separate files for backend (PHP), frontend (HTML/CSS/JS), and assets
- Organized folder structure for easy maintenance

---

For more details on setup and usage, see the QUICKSTART.md file.
# VetConnect

A web-based platform connecting pet owners with professional veterinarians.

## Features

- **User Authentication**: Secure registration and login system for both Pet Owners and Veterinarians
- **Role-Based Dashboards**: Separate dashboards for Pet Owners and Veterinarians
- **Profile Customization**: Users can upload profile pictures and customize their profile name
- **Responsive Design**: Fully responsive layout that works on desktop, tablet, and mobile devices
- **Session Management**: Secure session handling with proper logout functionality

## Technologies Used

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Styling**: Custom CSS with responsive design

## Installation

1. **Clone or download** this repository to your local server directory (e.g., `htdocs` for XAMPP)

2. **Set up the database**:
   - Create a MySQL database named `vetconnect`
   - Create a `users` table with the following structure:
   ```sql
   CREATE TABLE users (
       id INT(11) AUTO_INCREMENT PRIMARY KEY,
       role VARCHAR(50) NOT NULL,
       name VARCHAR(100) NOT NULL,
       email VARCHAR(100) UNIQUE NOT NULL,
       password VARCHAR(255) NOT NULL,
       profile_name VARCHAR(100) DEFAULT NULL,
       profile_picture VARCHAR(255) DEFAULT 'assets/profile-user-svgrepo-com.svg',
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

3. **Configure database connection**:
   - Update database credentials in:
     - `register.php`
     - `login_backend.php`
     - `Pet_OwnerDashboard.php`
     - `veterinarian_dashboard.php`
     - `uploads/profile_customization.php`

4. **Start your local server** (Apache and MySQL)

5. **Access the application**:
   - Navigate to `http://localhost/VetConnect/landing_page.php`

## File Structure

```
VetConnect/
├── assets/                          # Static assets (images, icons)
│   └── profile-user-svgrepo-com.svg
├── uploads/                         # User uploaded files
│   └── profile_customization.php
├── index.php                        # Registration page
├── login.php                        # Login page
├── login_backend.php                # Login processing
├── register.php                     # Registration processing
├── landing_page.php                 # Landing page
├── Pet_OwnerDashboard.php          # Pet owner dashboard
├── veterinarian_dashboard.php       # Veterinarian dashboard
├── logout.php                       # Logout functionality
├── style.css                        # Main stylesheet (responsive)
├── script.js                        # Landing page scripts
├── register.js                      # Profile popup toggle
├── formValidation.js                # Form validation
└── README.md                        # Documentation
```

## Usage

### For Pet Owners:
1. Visit the landing page
2. Click "Register" and select "Pet Owner" as your role
3. Fill in your details and submit
4. Login with your credentials
5. Access your dashboard to manage appointments and pets
6. Customize your profile by clicking on your profile picture

### For Veterinarians:
1. Visit the landing page
2. Click "Register" and select "Veterinarian" as your role
3. Fill in your details and submit
4. Login with your credentials
5. Access your dashboard to manage appointments and patients
6. Customize your profile by clicking on your profile picture

## Responsive Design

The application is fully responsive with breakpoints for:
- **Desktop**: Full layout with all features visible
- **Tablet** (≤968px): Adjusted navbar and content layout
- **Mobile** (≤768px): Stacked navigation and optimized spacing
- **Small Mobile** (≤480px): Compact layout with touch-friendly elements

## Security Features

- Password hashing using `PASSWORD_BCRYPT`
- SQL injection prevention using prepared statements
- Session-based authentication
- XSS prevention using `htmlspecialchars()`
- Secure file upload validation

## Recent Updates

### Version 2.0 - December 2024
- ✅ Complete responsive design overhaul
- ✅ Enhanced UI/UX with modern styling
- ✅ Fixed navbar layout and functionality
- ✅ Improved profile popup system
- ✅ Added proper logout functionality
- ✅ Enhanced form validation and error messages
- ✅ Fixed profile customization redirect issues
- ✅ Added default profile pictures for new users
- ✅ Mobile-friendly navigation and layouts
- ✅ Improved database integration

## Future Enhancements

- Appointment scheduling system
- Real-time messaging between pet owners and veterinarians
- Pet medical records management
- Payment integration
- Email notifications
- Advanced search and filtering
- Reviews and ratings system

## License

This project is for educational purposes.

## Support

For issues or questions, please contact the development team.

A linkedin like website for pet owners and veterenarians to connect
