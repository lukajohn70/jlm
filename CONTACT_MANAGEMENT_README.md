# Contact Management & Subscription System

## Overview
This system provides comprehensive contact message management and email subscription functionality for the JLM website admin panel.

## Features Implemented

### Contact Message Management
- **View Messages**: Admin can view all contact form submissions with detailed information
- **Sort & Filter**: Messages can be sorted by date, priority, and status
- **Status Management**: Mark messages as new, read, replied, archived, or deleted
- **Priority Levels**: Set message priority (urgent, high, medium, low)
- **Reply System**: Send email replies directly from the admin panel
- **Admin Notes**: Add internal notes to messages
- **Search**: Search through messages by name, email, subject, or content
- **Pagination**: Handle large numbers of messages efficiently

### Email Subscription System
- **Subscriber Management**: Add, remove, and manage email subscribers
- **Campaign Creation**: Create and send email campaigns to subscribers
- **Subscription Forms**: Frontend newsletter signup with AJAX
- **Unsubscribe Functionality**: One-click unsubscribe links
- **Email Templates**: Professional email templates for campaigns
- **Delivery Tracking**: Track email delivery status

## Database Schema

### New Tables Created
1. **contact_replies** - Stores admin replies to contact messages
2. **email_subscribers** - Newsletter subscriber list
3. **email_campaigns** - Email campaign management
4. **email_campaign_recipients** - Campaign delivery tracking

### Updated Tables
1. **contact_submissions** - Added status, priority, admin notes, and tracking fields
2. **booking_submissions** - Added status and admin notes fields

## Files Created/Modified

### New Files
- `contact_manage.php` - Main contact message management interface
- `contact_view.php` - Individual message view and reply interface
- `subscription_manage.php` - Email subscription and campaign management
- `subscribe.php` - AJAX handler for newsletter subscriptions
- `unsubscribe.php` - Unsubscribe page for newsletter recipients
- `sql/update_schema.sql` - Database schema updates

### Modified Files
- `admin_dashboard.php` - Added subscription tab and statistics
- `config/email_utils.php` - Added email functions for replies and campaigns
- `index.php` - Made newsletter subscription form functional

## Admin Panel Access

### Contact Management
- **URL**: `/contact_manage.php`
- **Features**: View, filter, sort, reply, archive, delete messages
- **Access**: Admin login required

### Subscription Management
- **URL**: `/subscription_manage.php`
- **Features**: Manage subscribers, create campaigns, send newsletters
- **Access**: Admin login required

### Dashboard Integration
- **New Tab**: "Subscriptions" tab in admin dashboard
- **Statistics**: Real-time stats for messages and subscribers
- **Quick Actions**: Direct links to management pages

## Frontend Integration

### Newsletter Subscription
- **Location**: Website footer
- **Functionality**: AJAX form submission
- **Features**: Email validation, success/error messages
- **Handler**: `subscribe.php`

### Unsubscribe Links
- **URL Format**: `/unsubscribe.php?email=user@example.com`
- **Features**: One-click unsubscribe, confirmation page

## Email System

### Email Functions Added
- `sendContactReply()` - Send replies to contact form submissions
- `sendNewsletterEmail()` - Send campaign emails to subscribers
- `sendSubscriptionConfirmation()` - Welcome emails for new subscribers

### Email Templates
- Professional HTML templates
- Personalization support (`{{first_name}}` placeholder)
- Unsubscribe links included
- Mobile-responsive design

## Usage Instructions

### For Admins
1. **Access Contact Management**:
   - Login to admin panel
   - Go to "Subscriptions" tab
   - Click "Contact Messages" button

2. **Manage Messages**:
   - Use filters to find specific messages
   - Click "View" to see full message details
   - Use "Reply" to send email responses
   - Set priority levels for urgent messages
   - Archive or delete processed messages

3. **Manage Subscriptions**:
   - Go to "Subscriptions" tab
   - Click "Manage Subscriptions" button
   - Add/remove subscribers
   - Create and send email campaigns

### For Users
1. **Subscribe to Newsletter**:
   - Scroll to footer on any page
   - Enter email address
   - Click "Subscribe"

2. **Unsubscribe**:
   - Click unsubscribe link in any newsletter email
   - Or visit `/unsubscribe.php?email=your@email.com`

## Security Features
- Admin authentication required for all management pages
- SQL injection prevention with prepared statements
- XSS protection with proper output escaping
- CSRF protection for form submissions
- Input validation and sanitization

## Technical Requirements
- PHP 7.4+
- MySQL 5.7+
- PHPMailer for email functionality
- Modern web browser with JavaScript enabled

## Database Setup
1. Run the original schema: `mysql -u root -p jlm_test_db -e "source sql/schema.sql"`
2. Run the updates: `mysql -u root -p jlm_test_db -e "source sql/update_schema.sql"`

## Email Configuration
Ensure SMTP settings are properly configured in `config/email_utils.php`:
- SMTP host, port, username, password
- From email and name
- Admin notification email

## Future Enhancements
- Email campaign analytics and reporting
- Advanced message filtering and search
- Bulk message operations
- Email template editor
- Automated email sequences
- Integration with CRM systems 