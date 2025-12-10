# VetConnect Schedule Module Setup Guide

## Database Setup

1. **Run the updated database setup script:**
   - Open phpMyAdmin or your MySQL client
   - Execute the `database_setup.sql` file to create the schedules table
   - This will add a new `schedules` table to your database

## Features Implemented

### 1. Schedule Management System
- **Add Schedule Button**: Located in the Pet Owner Dashboard header
- **Schedule Modal**: Pop-up form to create new schedules
- **Three Schedule Types**:
  - **Clinic Visit**: Schedule appointments at vet clinics (requires selecting a veterinarian)
  - **Vaccination**: Track pet vaccination schedules
  - **Medication**: Set reminders for pet medication

### 2. Real-time Notifications
- **Dynamic Notification Panel**: Shows upcoming schedules in the right sidebar
- **Urgent Alerts**: Schedules due within 2 days are highlighted with special styling
- **Auto-refresh**: Page reloads after creating a schedule to show updated notifications

### 3. Schedule Features
- **Pet Name**: Track which pet the schedule is for
- **Date & Time**: Set exact date and time for appointments
- **Title & Description**: Add custom details
- **Veterinarian Selection**: Choose specific vets for clinic visits
- **Status Tracking**: Pending, Completed, or Cancelled

## Files Added/Modified

### New Files:
1. **schedule.js** - Frontend JavaScript for modal functionality
2. **schedule_handler.php** - Backend API for CRUD operations
3. **check_schedules.php** - Notification checker (for cron jobs)

### Modified Files:
1. **Pet_OwnerDashboard.php** - Added schedule modal and dynamic notifications
2. **style.css** - Added modal and schedule button styling
3. **database_setup.sql** - Added schedules table schema

## How to Use

### Creating a Schedule:
1. Log in as a Pet Owner
2. Click the **"+ Add Schedule"** button in the dashboard
3. Fill in the form:
   - Enter your pet's name
   - Select schedule type (Clinic Visit, Vaccination, or Medication)
   - If Clinic Visit, select a veterinarian
   - Choose date and time
   - Add a title and optional description
4. Click **"Save Schedule"**
5. The page will refresh and show the new schedule in notifications

### Viewing Schedules:
- All upcoming schedules appear in the **Notifications** panel on the right
- Schedules show:
  - Icon based on type (🏥 Clinic, 💉 Vaccination, 💊 Medication)
  - Pet name and schedule details
  - Time until the scheduled date
  - Urgent schedules (within 2 days) are highlighted

### Notification System:
- Schedules due within 24 hours can be tracked
- The `check_schedules.php` script can be set up as a cron job to send email notifications
- Schedules are automatically marked as notified once processed

## Optional: Setting Up Automated Notifications

To enable automated email notifications for upcoming schedules:

1. **Set up a cron job** (on Linux/Mac) or **Task Scheduler** (on Windows):
   ```bash
   # Run every hour
   0 * * * * php /path/to/VetConnect/check_schedules.php
   ```

2. **Or manually run** the notification checker:
   ```bash
   php check_schedules.php
   ```

## Database Schema

### schedules table:
```sql
- id: Auto-increment primary key
- user_id: Foreign key to users table
- vet_id: Foreign key to users table (optional, for clinic visits)
- pet_name: VARCHAR(100) - Name of the pet
- schedule_type: ENUM('clinic_visit', 'vaccination', 'medication')
- schedule_date: DATETIME - When the schedule is due
- title: VARCHAR(200) - Schedule title
- description: TEXT - Additional details
- status: ENUM('pending', 'completed', 'cancelled')
- notified: BOOLEAN - Whether notification was sent
- created_at: Timestamp
- updated_at: Timestamp
```

## API Endpoints (schedule_handler.php)

### Create Schedule
- **Action**: `create`
- **Method**: POST
- **Parameters**: petName, scheduleType, vetId (optional), scheduleDate, title, description

### List Schedules
- **Action**: `list`
- **Method**: GET
- **Returns**: Array of upcoming schedules

### Update Schedule
- **Action**: `update`
- **Method**: POST
- **Parameters**: scheduleId, petName, scheduleType, vetId, scheduleDate, title, description

### Delete Schedule
- **Action**: `delete`
- **Method**: POST
- **Parameters**: scheduleId

### Mark as Completed
- **Action**: `mark_completed`
- **Method**: POST
- **Parameters**: scheduleId

## Troubleshooting

1. **Modal not opening**: Check browser console for JavaScript errors
2. **Schedule not saving**: Verify database connection in schedule_handler.php
3. **Notifications not showing**: Ensure schedules table exists and has data
4. **Date/time not working**: Check that datetime-local is supported in your browser

## Future Enhancements

Possible additions:
- Email notification integration
- SMS reminders
- Calendar view of schedules
- Recurring schedules
- Schedule sharing with veterinarians
- Export schedules to ICS format

## Support

For issues or questions, refer to the main README.md or check the project documentation.
