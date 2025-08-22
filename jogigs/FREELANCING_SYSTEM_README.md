# JoGigs Freelancing System

## Overview

The JoGigs freelancing system implements a complete workflow for managing client bookings, quote generation, job assignments, and freelancer management. The system follows the specified logic:

1. **Client Booking**: Clients fill out booking forms on the main JLM website
2. **Quote Generation**: Admins create quotes from bookings
3. **Quote Acceptance**: When clients accept quotes, jobs are created
4. **Job Assignment**: Admins can assign jobs to freelancers (open market or direct assignment)
5. **Freelancer Response**: Freelancers can accept or decline job offers
6. **Job Execution**: Freelancers work on accepted jobs and mark them complete
7. **Penalty System**: Penalties are applied for backing out after acceptance

## System Architecture

### Database Tables

#### Core Tables
- `jogigs_users` - Freelancer accounts
- `jogigs_quotes` - Quotes generated from client bookings
- `jogigs_jobs` - Jobs created from accepted quotes
- `jogigs_job_assignments` - Job assignments to freelancers
- `jogigs_earnings` - Freelancer earnings from completed jobs

#### Supporting Tables
- `jogigs_penalties` - Penalties for backing out or poor performance
- `jogigs_job_progress` - Job progress tracking
- `jogigs_job_communications` - Communication between parties
- `jogigs_payout_accounts` - Freelancer payout account management
- `jogigs_portfolio_uploads` - Freelancer portfolio files

### Key Features

#### 1. Quote Management
- **Automatic Quote Generation**: Quotes are calculated based on service type and project complexity
- **Quote Expiration**: Quotes expire after 7 days
- **Quote Acceptance**: When accepted, jobs are automatically created

#### 2. Job Assignment System
- **Open Market Assignment**: Jobs can be offered to all matching freelancers
- **Direct Assignment**: Jobs can be assigned directly to specific freelancers
- **First-Come-First-Served**: First freelancer to accept gets the job

#### 3. Freelancer Workflow
- **Job Offers**: Freelancers receive job offers based on their skills
- **Accept/Decline**: Freelancers can accept or decline job offers
- **Job Execution**: Freelancers can start work and mark jobs complete
- **Backout Penalties**: Penalties apply for backing out after acceptance

#### 4. Penalty System
- **Backout Penalty**: 20% of quote amount for backing out after acceptance
- **Late Delivery**: Penalties for missing deadlines
- **Poor Quality**: Penalties for substandard work
- **No Communication**: Penalties for lack of communication

#### 5. Payment System
- **Freelancer Payment**: 80% of quote amount goes to freelancer
- **Platform Fee**: 20% retained by platform
- **Payout Accounts**: Multiple payout methods supported

## Workflow

### 1. Client Booking → Quote Generation
```
Client submits booking form
    ↓
Admin creates quote (manual or via integration)
    ↓
Quote is generated with calculated amount
    ↓
Quote sent to client for acceptance
```

### 2. Quote Acceptance → Job Creation
```
Client accepts quote
    ↓
Job is automatically created
    ↓
Job is ready for assignment
```

### 3. Job Assignment
```
Admin assigns job to freelancers
    ↓
Option 1: Open Market (all matching freelancers)
Option 2: Direct Assignment (specific freelancer)
    ↓
Freelancers receive job offers
```

### 4. Freelancer Response
```
Freelancer receives job offer
    ↓
Freelancer can Accept or Decline
    ↓
If Accepted: Job is locked to that freelancer
If Declined: Job remains available
```

### 5. Job Execution
```
Freelancer accepts job
    ↓
Freelancer starts work
    ↓
Freelancer marks job complete
    ↓
Payment is processed
```

## Admin Interface

### Dashboard (`admin/dashboard.php`)
- Overview of quotes, jobs, freelancers, and earnings
- Quick statistics and recent activity
- Navigation to all admin functions

### Quotes Management (`admin/quotes.php`)
- View all quotes with status and details
- Create new quotes from booking data
- Accept quotes to create jobs
- Assign jobs to freelancers

### Job Assignments (`admin/jobs.php`)
- View all job assignments
- Monitor freelancer progress
- Manage job status and deadlines

### Freelancer Management (`admin/freelancers.php`)
- View all freelancer profiles
- Manage freelancer status
- View freelancer performance

### Penalty Management (`admin/penalties.php`)
- View and manage penalties
- Apply or waive penalties
- Track penalty payments

## Freelancer Interface

### Dashboard (`dashboard.php`)
- Overview of earnings and job assignments
- Pending job offers
- Active and completed jobs
- Payout account management

### Job Actions (`job_actions.php`)
- Accept/decline job offers
- Start work on accepted jobs
- Mark jobs as complete
- Handle job backouts (with penalties)

## Integration

### Main JLM Website Integration
The system can be integrated with the main JLM booking system:

```php
// Example integration call
$booking_data = [
    'booking_id' => 'BK-2024-001',
    'client_name' => 'John Doe',
    'client_email' => 'john@example.com',
    'services' => 'Web Development',
    'project_description' => 'E-commerce website development'
];

$response = file_get_contents('http://localhost/jlm/jogigs/integration/create_quote_from_booking.php', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($booking_data)
    ]
]));

$result = json_decode($response, true);
```

## Security Features

### Authentication
- Session-based authentication
- Admin-only access to admin functions
- Freelancer-only access to freelancer functions

### Data Validation
- Input sanitization for all user inputs
- CSRF protection for forms
- Rate limiting for API calls

### Penalty Protection
- Automatic penalty calculation
- Penalty tracking and management
- Backout prevention after acceptance

## Configuration

### Database Configuration
- Uses existing JLM database
- JoGigs tables are created automatically
- Separate user system from main JLM

### Email Integration
- Quote notifications to clients
- Job assignment notifications to freelancers
- Payment notifications

### File Upload
- Portfolio file uploads for freelancers
- File size and type validation
- Secure file storage

## Usage Examples

### Creating a Quote
```php
$booking_data = [
    'booking_id' => 'BK-2024-001',
    'client_name' => 'John Doe',
    'client_email' => 'john@example.com',
    'services' => 'Web Development',
    'project_description' => 'E-commerce website development'
];

$result = create_quote_from_booking($booking_data);
// Returns: ['success' => true, 'quote_id' => 1, 'quote_number' => 'QT-2024-0001', 'amount' => 50000]
```

### Assigning a Job
```php
// Open market assignment
$result = assign_job_to_freelancers($job_id, $quote_id);

// Direct assignment
$result = assign_job_directly($job_id, $quote_id, $freelancer_id);
```

### Accepting a Job
```php
$result = accept_job_assignment($assignment_id, $freelancer_id);
```

### Applying Penalty
```php
$result = apply_backout_penalty($assignment_id, $freelancer_id, 'Unable to complete job');
```

## Future Enhancements

1. **Real-time Notifications**: WebSocket-based notifications
2. **Advanced Matching**: AI-powered job-freelancer matching
3. **Escrow System**: Secure payment escrow
4. **Dispute Resolution**: Built-in dispute handling
5. **Mobile App**: Native mobile applications
6. **Analytics Dashboard**: Advanced reporting and analytics

## Support

For technical support or questions about the freelancing system, please contact the development team.

---

**Note**: This system is designed to work alongside the existing JLM website and booking system, providing a complete freelancing platform for managing client projects and freelancer assignments.


