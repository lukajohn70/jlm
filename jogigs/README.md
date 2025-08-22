# JoGigs Web App

A modern PHP-based freelancer platform for JLM Creative Media, converted from React Native mobile app to a web application.

## Features

### 🔐 Authentication
- User registration and login
- Secure password hashing with bcrypt
- Session management with database tracking
- Password reset functionality
- CSRF protection

### 📊 Dashboard
- Real-time earnings overview
- Available jobs display
- Recent activity tracking
- User statistics and metrics

### 💼 Job Management
- Browse available jobs
- Job details and requirements
- Application system
- Job status tracking

### 💰 Earnings Tracking
- Payment history
- Earnings analytics
- Payment status monitoring
- Financial reporting

### 👤 Profile Management
- User profile editing
- Skills and hourly rate management
- Bio and portfolio
- Profile image upload

## Technology Stack

- **Backend**: Pure PHP (No frameworks)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Styling**: Tailwind CSS
- **Security**: bcrypt, CSRF tokens, SQL injection prevention

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- WAMP/XAMPP (for local development)

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   # Place in your web server directory
   # e.g., C:\wamp64\www\jlm\jogigs\
   ```

2. **Database Configuration**
   - The app will automatically create the database and tables on first run
   - Default database name: `jlm_freelancer`
   - Default credentials: `root` (no password)

3. **Access the Application**
   - Open your web browser
   - Navigate to: `http://localhost/jlm/jogigs/`

4. **Default Admin Account**
   - Email: `admin@jogigs.com`
   - Password: `admin123`

## File Structure

```
jogigs/
├── index.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # Main dashboard
├── logout.php             # Logout handler
├── includes/
│   ├── config.php         # Database and app configuration
│   ├── functions.php      # Utility functions
│   └── auth.php           # Authentication functions
├── uploads/               # File uploads directory
├── logs/                  # Application logs
├── cache/                 # Cache files
└── README.md             # This file
```

## Database Schema

### JoGigs Users Table
- `id` - Primary key
- `email` - Unique email address
- `password` - Hashed password
- `first_name`, `last_name` - User names
- `phone` - Contact number
- `skills` - User skills
- `hourly_rate` - Hourly rate
- `bio` - User biography
- `status` - Account status
- `created_at`, `updated_at` - Timestamps

### JoGigs Jobs Table
- `id` - Primary key
- `title` - Job title
- `description` - Job description
- `client_id` - Client reference
- `budget_min`, `budget_max` - Budget range
- `skills_required` - Required skills
- `status` - Job status
- `deadline` - Project deadline

### JoGigs Applications Table
- `id` - Primary key
- `job_id` - Job reference
- `freelancer_id` - Freelancer reference
- `proposal` - Application proposal
- `bid_amount` - Proposed amount
- `status` - Application status

### JoGigs Earnings Table
- `id` - Primary key
- `user_id` - User reference
- `job_id` - Job reference
- `amount` - Payment amount
- `description` - Payment description
- `type` - Payment type
- `status` - Payment status

## Security Features

- **Password Security**: bcrypt hashing with configurable cost
- **Session Management**: Database-tracked sessions with expiry
- **CSRF Protection**: Token-based CSRF protection
- **SQL Injection Prevention**: Prepared statements
- **XSS Prevention**: Input sanitization and output escaping
- **File Upload Security**: Type and size validation

## Customization

### Styling
- Modify Tailwind CSS classes in the HTML files
- Custom CSS can be added to the `<style>` sections
- Color scheme can be changed in the gradient classes

### Configuration
- Edit `includes/config.php` for database settings
- Modify constants for file upload limits
- Adjust session timeout settings

### Features
- Add new pages by following the existing structure
- Extend database schema as needed
- Implement additional security measures

## Development Notes

### Converting from React Native
This web app was converted from a React Native mobile application. Key changes made:

1. **UI Framework**: React Native components → HTML/CSS
2. **State Management**: Redux → PHP sessions and database
3. **Navigation**: React Navigation → PHP page routing
4. **Storage**: AsyncStorage → MySQL database
5. **Styling**: React Native StyleSheet → Tailwind CSS

### Performance Optimizations
- Database queries optimized with proper indexing
- Session management with automatic cleanup
- File upload size limits
- Error logging and monitoring

## Support

For technical support or questions:
- Check the error logs in the `logs/` directory
- Review the database connection settings
- Ensure proper file permissions on upload directories

## License

© 2024 JLM Creative Media. All rights reserved. 