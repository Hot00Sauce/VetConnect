# VetConnect - Quick Start Guide

## 🚀 Getting Started in 5 Minutes

### Prerequisites
- XAMPP, WAMP, or similar local server (Apache + MySQL + PHP)
- Web browser (Chrome, Firefox, Edge, Safari)

---

## Step 1: Setup Database

1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Click "Import" tab
3. Choose file: `database_setup.sql`
4. Click "Go" to execute

**OR** manually create the database:
```sql
CREATE DATABASE vetconnect;
```
Then run the SQL in `database_setup.sql`

---

## Step 2: Configure Database Connection

Update these files with your database credentials (if different from defaults):

**Files to update:**
- `register.php` (lines 2-5)
- `login_backend.php` (lines 3-6)
- `Pet_OwnerDashboard.php` (lines 11-14)
- `veterinarian_dashboard.php` (lines 11-14)
- `uploads/profile_customization.php` (lines 11-14)

**Default credentials:**
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vetconnect";
```

---

## Step 3: Start Server

1. Start Apache and MySQL from XAMPP/WAMP control panel
2. Ensure both services are running (green indicators)

---

## Step 4: Access Application

Open your browser and navigate to:
```
http://localhost/VetConnect/landing_page.php
```

---

## Step 5: Test the Application

### Create a Pet Owner Account:
1. Click "Register"
2. Select role: "Pet Owner"
3. Fill in details:
   - Name: Test Owner
   - Email: owner@test.com
   - Password: password123
4. Click "Register"

### Create a Veterinarian Account:
1. Click "Register"
2. Select role: "Veterinarian"
3. Fill in details:
   - Name: Test Vet
   - Email: vet@test.com
   - Password: password123
4. Click "Register"

### Login and Test:
1. Login with one of the accounts
2. Click on profile picture to open sidebar
3. Try "Edit Profile" to customize profile
4. Test "Logout" functionality

---

## 📱 Test Responsive Design

### Desktop Testing:
- Open in full browser window
- Check navbar layout
- Test popup sidebar

### Mobile Testing:
- Press F12 (Developer Tools)
- Click device toggle icon
- Select different devices:
  - iPhone SE (375px)
  - iPad (768px)
  - Desktop (1920px)
- Test navigation on each size

---

## 🎯 Key Features to Test

- ✅ User Registration (both roles)
- ✅ User Login
- ✅ Dashboard Access
- ✅ Profile Popup Toggle
- ✅ Profile Customization
- ✅ Logout
- ✅ Responsive Navigation
- ✅ Mobile Menu

---

## 📂 File Structure Reference

```
VetConnect/
├── assets/                      # Images and icons
├── uploads/                     # User uploads & profile page
├── landing_page.php             # START HERE
├── index.php                    # Registration
├── login.php                    # Login
├── Pet_OwnerDashboard.php      # Pet owner dashboard
├── veterinarian_dashboard.php   # Vet dashboard
├── logout.php                   # Logout handler
├── style.css                    # Main stylesheet
└── database_setup.sql           # Database setup
```

---

## 🔧 Troubleshooting

### Can't connect to database?
- Check MySQL is running in XAMPP/WAMP
- Verify database credentials in PHP files
- Ensure `vetconnect` database exists

### Registration not working?
- Check database table `users` exists
- Verify all required columns are present
- Check PHP error logs

### Images not showing?
- Ensure `assets` folder exists
- Check file path in database
- Verify upload permissions on `uploads/` folder

### Responsive design not working?
- Clear browser cache (Ctrl + Shift + R)
- Check `style.css` is loaded
- Verify viewport meta tag in HTML

---

## 🎨 Customization Tips

### Change Color Scheme:
Edit `style.css` - search for `#FFAD60` (main orange color)

### Add More Features:
- Appointment scheduling
- Messaging system
- Pet profiles
- Payment integration

### Modify User Roles:
Edit registration form in `index.php`

---

## 📖 Documentation

- **README.md** - Full project documentation
- **IMPROVEMENTS.md** - Detailed changelog
- **This file** - Quick start guide

---

## ✅ Success Checklist

- [ ] Database created and configured
- [ ] Apache and MySQL running
- [ ] Application loads at localhost
- [ ] Can register new users
- [ ] Can login successfully
- [ ] Dashboard displays correctly
- [ ] Profile popup works
- [ ] Logout works
- [ ] Responsive on mobile
- [ ] Profile customization works

---

## 🎉 You're All Set!

Your VetConnect application is now ready to use!

For more details, check `README.md` and `IMPROVEMENTS.md`

---

**Need Help?**
- Check PHP error logs: `xampp/apache/logs/error.log`
- Enable error display in PHP files: `ini_set('display_errors', 1);`
- Verify database structure matches `database_setup.sql`

**Happy Coding! 🚀**
