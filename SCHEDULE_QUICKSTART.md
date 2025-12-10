# Quick Start Guide - Schedule Module

## Initial Setup Steps

### 1. Update Database
Run this SQL command in phpMyAdmin to create the schedules table:

```sql
USE vetconnect;

CREATE TABLE IF NOT EXISTS schedules (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    vet_id INT(11) DEFAULT NULL,
    pet_name VARCHAR(100) NOT NULL,
    schedule_type ENUM('clinic_visit', 'vaccination', 'medication') NOT NULL,
    schedule_date DATETIME NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    notified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vet_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_user_id ON schedules(user_id);
CREATE INDEX idx_schedule_date ON schedules(schedule_date);
CREATE INDEX idx_status ON schedules(status);
```

Or simply run the entire `database_setup.sql` file.

### 2. Test the Schedule Module

1. **Start XAMPP** and ensure Apache and MySQL are running

2. **Login as a Pet Owner**:
   - Go to `http://localhost/VetConnect/login.php`
   - Register or login with a Pet Owner account

3. **Create a Schedule**:
   - Click the **"+ Add Schedule"** button (orange button in the top right)
   - Fill in the form:
     - Pet Name: e.g., "Max"
     - Schedule Type: Choose "Vaccination"
     - Date & Time: Select a future date
     - Title: e.g., "Annual Rabies Vaccination"
     - Description: e.g., "Yearly rabies shot required"
   - Click **"Save Schedule"**

4. **View Notifications**:
   - Check the right sidebar for the new schedule
   - Schedules due within 2 days will have an orange highlight

5. **Test Clinic Visit**:
   - Click **"+ Add Schedule"** again
   - Select "Clinic Visit" as the type
   - A veterinarian dropdown will appear
   - Select a vet and complete the form

## Testing Different Schedule Types

### Vaccination Schedule
```
Pet Name: Buddy
Type: Vaccination
Date: [Tomorrow's date + time]
Title: Distemper Vaccine
Description: Annual distemper vaccination
```

### Medication Reminder
```
Pet Name: Luna
Type: Medication
Date: [3 days from now + time]
Title: Heartworm Prevention
Description: Monthly heartworm medication
```

### Clinic Visit
```
Pet Name: Charlie
Type: Clinic Visit
Veterinarian: [Select from dropdown]
Date: [Next week + time]
Title: General Checkup
Description: Routine health examination
```

## Expected Behavior

✅ **Modal appears** when clicking "+ Add Schedule"  
✅ **Veterinarian field** shows only for "Clinic Visit" type  
✅ **Form validation** prevents empty required fields  
✅ **Success message** appears after saving  
✅ **Page reloads** and new schedule appears in notifications  
✅ **Urgent schedules** (within 2 days) have orange highlighting  
✅ **Icons change** based on schedule type:
   - 🏥 Clinic Visit
   - 💉 Vaccination
   - 💊 Medication

## Common Issues & Solutions

### Issue: Modal doesn't open
**Solution**: Check browser console, ensure `schedule.js` is loaded

### Issue: "Schedule not saved"
**Solution**: 
- Verify `schedule_handler.php` exists
- Check database connection settings
- Ensure schedules table exists

### Issue: No notifications showing
**Solution**: 
- Create schedules with future dates
- Check that schedules status is 'pending'
- Verify database query in Pet_OwnerDashboard.php

### Issue: Veterinarian dropdown is empty
**Solution**: 
- Ensure you have users with role='Veterinarian' in database
- Check the SQL query in Pet_OwnerDashboard.php

## Sample Data to Insert

If you need sample veterinarians for testing:

```sql
INSERT INTO users (role, name, email, password, profile_name) VALUES
('Veterinarian', 'Dr. John Smith', 'john@vetconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. John Smith'),
('Veterinarian', 'Dr. Sarah Johnson', 'sarah@vetconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Sarah Johnson');
```
(Password for both: `password123`)

## Next Steps

After basic testing:
1. Try creating multiple schedules
2. Test with different pet names
3. Create schedules with varying due dates
4. Check urgent notification highlighting
5. Test the responsive design on mobile

Enjoy your new schedule management system! 🎉
