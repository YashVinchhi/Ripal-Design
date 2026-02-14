# Project Details Page - Dynamic Features Documentation

## Overview
The Project Details page (`dashboard/project_details.php`) is now fully dynamic with real-time updates, AJAX functionality, and database integration. This document outlines all the dynamic features and how they work.

---

## Database Tables

### 1. **projects**
Main project information table.
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- name (VARCHAR 255)
- status (ENUM: planning, ongoing, paused, completed)
- budget (DECIMAL 15,2)
- progress (INT, 0-100)
- due (DATE)
- location (TEXT)
- address (TEXT)
- owner_name (VARCHAR 255)
- owner_contact (VARCHAR 50)
- owner_email (VARCHAR 255)
- created_at (TIMESTAMP)
```

### 2. **project_workers**
Team members assigned to projects.
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- project_id (INT, FOREIGN KEY)
- worker_name (VARCHAR 255)
- worker_role (VARCHAR 100)
- worker_contact (VARCHAR 50)
```

### 3. **project_milestones**
Project milestone tracking.
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- project_id (INT, FOREIGN KEY)
- title (VARCHAR 255)
- target_date (DATE)
- status (ENUM: active, completed, pending)
```

### 4. **project_files**
File attachments for projects.
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- project_id (INT, FOREIGN KEY)
- name (VARCHAR 255)
- type (VARCHAR 50)
- size (VARCHAR 20)
- file_path (VARCHAR 500)
- uploaded_by (VARCHAR 255)
- uploaded_at (TIMESTAMP)
```

### 5. **project_activity**
Activity log for project changes.
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- project_id (INT, FOREIGN KEY)
- user (VARCHAR 255)
- action (VARCHAR 100)
- item (VARCHAR 255)
- created_at (TIMESTAMP)
```

### 6. **project_drawings**
Technical drawings for projects.
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- project_id (INT, FOREIGN KEY)
- name (VARCHAR 255)
- version (VARCHAR 20)
- status (ENUM: Approved, Under Review, Revision Needed)
- file_path (VARCHAR 500)
- uploaded_at (TIMESTAMP)
```

---

## Dynamic Features

### 1. **Overview Tab**
**Features:**
- Real-time project information display
- Dynamic summary cards (Budget, Status, Progress)
- Animated progress bar
- Live form with AJAX submission
- Project owner details with avatar initials
- Upcoming milestones with status indicators
- Interactive map placeholder with geocoding button

**Dynamic Elements:**
- Budget formatting: Displays in lakhs (₹) or formatted numbers
- Status badges: Color-coded based on project status
- Progress bar: Visual representation of completion percentage
- Form auto-population from database
- Owner contact information with icons

### 2. **Team Tab**
**Features:**
- Dynamic team member cards
- Add team member modal with form validation
- Real-time member addition via AJAX
- Contact information display
- Avatar generation with initials
- Empty state with "Add First Member" CTA

**AJAX Functions:**
- `showAddTeamMemberModal()` - Opens add member modal
- `closeAddTeamMemberModal()` - Closes modal
- Form submission with automatic page refresh
- Activity logging when members are added

### 3. **Files Tab**
**Features:**
- File upload with drag-and-drop support
- Real-time file display after upload
- File type icon detection (PDF, Excel, Image, etc.)
- File size formatting
- Download functionality
- Delete with confirmation
- Upload metadata (uploader name, timestamp)
- Empty state with upload CTA

**AJAX Functions:**
- `uploadFile(input)` - Handles file uploads
- `deleteFile(fileId)` - Deletes files with confirmation
- Automatic file type detection and icon assignment
- Activity logging for uploads and deletions

**Supported File Types:**
- PDF documents (red icon)
- Excel/CSV files (green icon)
- Images (blue icon)
- Word documents (blue icon)
- Archive files (yellow icon)
- Generic files (gray icon)

### 4. **Activity Tab**
**Features:**
- Real-time activity feed
- Automatic activity logging
- Time-ago formatting (e.g., "2 hours ago")
- Action-based icon display
- User attribution
- Empty state message

**Activity Types:**
- Project updates
- File uploads
- Drawing uploads
- Team member additions
- Status changes
- Milestone completions

**Dynamic Display:**
- Icons change based on action keywords
- Color coding for different activity types
- Smart time formatting (minutes/hours/days ago)

### 5. **Drawings Tab**
**Features:**
- Technical drawing upload
- Version tracking
- Status management (Approved, Under Review, Revision Needed)
- Drawing preview cards
- View and download functionality
- Delete with confirmation
- Empty state with upload CTA

**AJAX Functions:**
- `uploadDrawing(input)` - Handles drawing uploads
- `deleteDrawing(drawingId)` - Deletes drawings with confirmation
- Activity logging for uploads and deletions

**Status Colors:**
- Approved: Green badge
- Under Review: Yellow badge
- Revision Needed: Red badge

---

## AJAX API Endpoints

### API File Location
`dashboard/api/project_files.php`

### Available Actions

#### 1. **upload_file**
Uploads a file to the project.
- **Method:** POST (multipart/form-data)
- **Parameters:** `file`, `project_id`, `action=upload_file`
- **Returns:** `{success: boolean, message: string, file_id: int}`

#### 2. **upload_drawing**
Uploads a technical drawing.
- **Method:** POST (multipart/form-data)
- **Parameters:** `file`, `project_id`, `action=upload_drawing`
- **Returns:** `{success: boolean, message: string, drawing_id: int}`

#### 3. **delete_file**
Deletes a project file.
- **Method:** POST (JSON)
- **Body:** `{action: "delete_file", file_id: int, project_id: int}`
- **Returns:** `{success: boolean, message: string}`

#### 4. **delete_drawing**
Deletes a project drawing.
- **Method:** POST (JSON)
- **Body:** `{action: "delete_drawing", drawing_id: int, project_id: int}`
- **Returns:** `{success: boolean, message: string}`

#### 5. **log_activity**
Logs an activity to the project.
- **Method:** POST (JSON)
- **Body:** `{action: "log_activity", project_id: int, activity_action: string, item: string}`
- **Returns:** `{success: boolean, message: string}`

#### 6. **add_team_member**
Adds a team member to the project.
- **Method:** POST
- **Parameters:** `project_id`, `worker_name`, `worker_role`, `worker_contact`, `action=add_team_member`
- **Returns:** `{success: boolean, message: string, worker_id: int}`

#### 7. **get_files** (GET)
Retrieves all project files.
- **Method:** GET
- **Parameters:** `action=get_files`, `project_id`
- **Returns:** `{success: boolean, files: array}`

#### 8. **get_activities** (GET)
Retrieves recent project activities.
- **Method:** GET
- **Parameters:** `action=get_activities`, `project_id`
- **Returns:** `{success: boolean, activities: array}`

#### 9. **get_drawings** (GET)
Retrieves all project drawings.
- **Method:** GET
- **Parameters:** `action=get_drawings`, `project_id`
- **Returns:** `{success: boolean, drawings: array}`

---

## JavaScript Functions

### Core Functions

#### **Tab Management**
```javascript
// Automatically handles tab switching
document.querySelectorAll('.tab-link').forEach(...)
```

#### **File Management**
```javascript
uploadFile(input)           // Uploads file via AJAX
deleteFile(fileId)          // Deletes file with confirmation
```

#### **Drawing Management**
```javascript
uploadDrawing(input)        // Uploads drawing via AJAX
deleteDrawing(drawingId)    // Deletes drawing with confirmation
```

#### **Team Management**
```javascript
showAddTeamMemberModal()    // Opens add member modal
closeAddTeamMemberModal()   // Closes add member modal
```

#### **Utility Functions**
```javascript
logActivity(action, item)   // Logs activity to database
showNotification(msg, type) // Shows toast notification
updateProgress(percentage)  // Updates progress bar
```

### Auto-Save Feature
The page includes an optional auto-save mechanism that triggers 2 seconds after form field changes. This is currently disabled but can be enabled by uncommenting the functionality.

---

## File Upload System

### Upload Directory Structure
```
uploads/
└── projects/
    └── {project_id}/
        ├── files/
        │   ├── document1.pdf
        │   ├── budget.xlsx
        │   └── photo.jpg
        └── drawings/
            ├── floor_plan.pdf
            ├── elevation.dwg
            └── section.dxf
```

### File Handling
1. Files are uploaded to project-specific directories
2. Duplicate filenames are automatically renamed (e.g., `file_1.pdf`, `file_2.pdf`)
3. File metadata is stored in database
4. Actual files are stored on server filesystem
5. Relative paths are stored for portability

### Security Features
- User authentication check before upload
- File size limits enforced
- File type validation
- Directory traversal protection
- Session-based user attribution

---

## Notification System

### Toast Notifications
The page includes a custom notification system that displays feedback for user actions.

**Types:**
- **Success** (green): Operation completed successfully
- **Error** (red): Operation failed
- **Info** (blue): Informational messages

**Features:**
- Auto-dismiss after 3 seconds
- Fade-out animation
- Positioned at top-right
- Dark mode support
- Non-blocking (allows multiple notifications)

---

## Real-Time Updates

### Activity Logging
All major actions are automatically logged to the `project_activity` table:
- Project creation
- Project updates
- File uploads
- Drawing uploads
- Team member additions
- Status changes

### Time Formatting
The `timeAgo()` function automatically formats timestamps:
- Less than 1 minute: "Just now"
- Less than 1 hour: "X minutes ago"
- Less than 24 hours: "X hours ago"
- Less than 7 days: "X days ago"
- Older: Full date format

---

## Empty States

Each tab includes thoughtfully designed empty states:
- **Team Tab:** "No Team Members" with add CTA
- **Files Tab:** "No Files Yet" with upload CTA
- **Activity Tab:** "No Activity Yet" with informational message
- **Drawings Tab:** "No Drawings Yet" with upload CTA

---

## Modal System

### Add Team Member Modal
- **Trigger:** "Add Member" buttons
- **Fields:** Name, Role, Contact
- **Validation:** All fields required
- **Actions:** Cancel or Submit
- **Behavior:** 
  - ESC key closes modal
  - Backdrop click closes modal
  - Form reset on close
  - AJAX submission with loading state

---

## Progressive Enhancement

The page is designed with progressive enhancement:
1. **Base Layer:** Server-rendered PHP content
2. **Enhanced Layer:** AJAX for seamless updates
3. **Fallback:** Page reload if AJAX fails

### Browser Support
- Modern browsers with ES6+ support
- Graceful degradation for older browsers
- CSS Grid and Flexbox for layout
- Tailwind CSS for responsive design

---

## Performance Optimizations

1. **Database Queries:**
   - Efficient JOIN operations
   - Limited activity log results (20 most recent)
   - Indexed foreign keys

2. **File Uploads:**
   - Chunked uploads for large files
   - Progress indication
   - Background processing

3. **Client-Side:**
   - Debounced auto-save
   - Lazy loading for images
   - Minimal DOM manipulation

---

## Error Handling

### Client-Side
- Try-catch blocks for all AJAX calls
- User-friendly error messages
- Console logging for debugging
- Automatic retry option

### Server-Side
- PDO exception handling
- JSON error responses
- HTTP status codes
- Detailed error logging

---

## Usage Examples

### Adding a Team Member
```javascript
// Open modal
showAddTeamMemberModal();

// Fill form and submit
// Form automatically posts via AJAX
// Page refreshes with new member
```

### Uploading a File
```javascript
// Trigger file input
document.getElementById('fileUploadInput').click();

// Select file
// uploadFile() automatically called
// Progress shown in notification
// Page refreshes with new file
```

### Deleting a File
```javascript
// Click delete button
deleteFile(fileId);

// Confirmation dialog appears
// If confirmed, AJAX deletion
// Page refreshes after deletion
```

---

## Customization

### Color Scheme
Primary brand color: `#731209` (maroon)
- All buttons use primary color
- Links use primary color
- Status indicators color-coded

### Icons
Material Icons are used throughout:
- Upload: `upload_file`
- Delete: `delete`
- Add: `add`, `add_circle`
- Check: `check_circle`
- Map: `map`, `location_on`
- Contact: `phone`, `email`
- Architecture: `architecture`

### Notifications
Customize notification behavior in `showNotification()` function:
- Duration: Change timeout value
- Position: Modify CSS classes
- Colors: Update color mappings

---

## Future Enhancements

Potential features to add:
1. **Real-time collaboration** with WebSockets
2. **Drag-and-drop file uploads**
3. **Image preview lightbox**
4. **Milestone management** (add/edit/delete)
5. **Budget tracking** with expense entries
6. **Gantt chart** for timeline visualization
7. **Comment system** for discussions
8. **Notifications** for project updates
9. **Export** project data (PDF, Excel)
10. **Mobile app** integration

---

## Maintenance

### Regular Tasks
- Clean up old uploaded files
- Archive completed projects
- Backup database regularly
- Monitor activity logs
- Update dependencies

### Troubleshooting
- Check file permissions on uploads directory
- Verify database connection
- Review PHP error logs
- Test AJAX endpoints individually
- Validate user sessions

---

## Credits

Developed with:
- PHP 7.4+
- MySQL/MariaDB
- Tailwind CSS 3.x
- Material Icons
- Vanilla JavaScript (ES6+)

---

*Last Updated: February 14, 2026*
