# Requirements Document

## Introduction

This document outlines the requirements for an Internal Workshop Management System - a comprehensive platform for managing internal workshops/courses. The system enables organizers to manage users with role-based permissions, handle the complete workshop lifecycle including information management, ticket types, participants, ticket distribution, check-in processes, and analytics. This is an internal-only system without public registration functionality.

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to create roles and assign route-based permissions to those roles, so that I can control which users can access specific features of the system.

#### Acceptance Criteria

1. WHEN an administrator creates a new role, THEN the system SHALL allow specifying a name and description for the role.
2. WHEN an administrator defines or edits a role, THEN the system SHALL allow assigning one or more permissions, where each permission maps to a specific route name (e.g., users.index, conferences.edit).
3. WHEN an administrator creates or updates a user, THEN the system SHALL allow assigning a role to the user.
4. WHEN a user accesses a route, THEN the system SHALL check the user's role and associated permissions, and SHALL allow or deny access based on whether the current route name exists in the assigned permissions.
5. WHEN an administrator views the user list, THEN the system SHALL allow filtering users by role.
6. WHEN an administrator activates or deactivates a user account, THEN the system SHALL update the user status and control login access accordingly.
7. WHEN a route is accessed without the proper permission, THEN the system SHALL return a 403 Forbidden error with a meaningful message.

### Requirement 2

**User Story:** As an organizer, I want to create and manage workshops with detailed information, so that I can organize events effectively.

#### Acceptance Criteria

1. WHEN an organizer creates a workshop THEN the system SHALL store workshop details including name, description, date/time, location, and status
2. WHEN an organizer edits workshop information THEN the system SHALL update the workshop data and maintain relationships
3. WHEN an organizer deletes a workshop THEN the system SHALL remove the workshop and handle dependent data appropriately
4. WHEN an organizer assigns organizers to a workshop THEN the system SHALL create the many-to-many relationship between users and workshops
5. WHEN viewing workshops THEN the system SHALL display all workshop information with current status

### Requirement 3

**User Story:** As an organizer, I want to create and manage ticket types for each workshop, so that I can offer different participation options with appropriate pricing.

#### Acceptance Criteria

1. WHEN an organizer creates a ticket type for a workshop THEN the system SHALL store the ticket type with name and fee information
2. WHEN an organizer edits a ticket type THEN the system SHALL update the ticket type information
3. WHEN an organizer deletes a ticket type THEN the system SHALL remove it while checking for existing participant assignments
4. WHEN viewing ticket types THEN the system SHALL display all ticket types grouped by workshop
5. IF a ticket type has assigned participants THEN the system SHALL prevent deletion and show appropriate warning

### Requirement 4

**User Story:** As an organizer, I want to manage workshop participants through manual entry or Excel import, so that I can efficiently register attendees.

#### Acceptance Criteria

1. WHEN an organizer adds a participant manually THEN the system SHALL store participant information including name, email, phone, occupation, address, company, and position
2. WHEN an organizer imports participants from Excel THEN the system SHALL process the file and create participant records
3. WHEN a participant is added THEN the system SHALL generate a unique ticket_code and assign them to a workshop and ticket type
4. WHEN an organizer edits participant information THEN the system SHALL update the participant data
5. WHEN an organizer deletes a participant THEN the system SHALL remove the participant record
6. WHEN a participant is created THEN the system SHALL initialize is_paid and is_checked_in status as false
7. WHEN an organizer updates payment status THEN the system SHALL update the is_paid field
8. WHEN a participant checks in THEN the system SHALL update the is_checked_in field

### Requirement 5

**User Story:** As an organizer, I want the system to automatically generate QR codes and send tickets via email, so that participants receive their access credentials efficiently.

#### Acceptance Criteria

1. WHEN a participant is added to a workshop THEN the system SHALL generate a QR code from the ticket_code using simple-qrcode
2. WHEN a QR code is generated THEN the system SHALL automatically send a ticket email using Laravel Mailable and Queue processing
3. WHEN an organizer requests to resend a ticket THEN the system SHALL generate a new email with the QR code
4. WHEN sending emails THEN the system SHALL use the configured email template for the workshop
5. IF email sending fails THEN the system SHALL log the error and allow manual retry

### Requirement 6

**User Story:** As a staff member, I want to check in participants using QR code scanning, so that I can efficiently manage workshop attendance.

#### Acceptance Criteria

1. WHEN a staff member scans a QR code THEN the system SHALL decode the ticket_code and locate the participant
2. WHEN a valid QR code is scanned THEN the system SHALL update the participant's is_checked_in status to true
3. WHEN an invalid QR code is scanned THEN the system SHALL display an appropriate error message
4. WHEN a participant is already checked in THEN the system SHALL display their current status
5. WHEN check-in is successful THEN the system SHALL display participant information for verification

### Requirement 7

**User Story:** As an organizer, I want to customize email templates for each workshop, so that I can send personalized communications to participants.

#### Acceptance Criteria

1. WHEN an organizer creates an email template THEN the system SHALL store the template with type (invite, confirm, ticket, reminder, thank_you), subject, and content
2. WHEN creating email content THEN the system SHALL support dynamic variables like {{ name }}, {{ ticket_code }}, {{ qr_code_url }}
3. WHEN sending emails THEN the system SHALL render templates using Blade or str_replace for variable substitution
4. WHEN an organizer edits an email template THEN the system SHALL update the template content
5. WHEN sending emails THEN the system SHALL use the appropriate template based on email type and workshop

### Requirement 8

**User Story:** As an administrator, I want to view comprehensive statistics and dashboard analytics, so that I can monitor workshop performance and attendance.

#### Acceptance Criteria

1. WHEN viewing workshop statistics THEN the system SHALL display total participants, checked-in count, and total revenue
2. WHEN accessing the dashboard THEN the system SHALL show overview statistics for all workshops
3. WHEN filtering statistics by workshop THEN the system SHALL display workshop-specific metrics
4. WHEN viewing revenue data THEN the system SHALL calculate totals based on ticket types and paid participants
5. WHEN accessing analytics THEN the system SHALL provide real-time data updates

### Requirement 9

**User Story:** As a system administrator, I want the system to maintain data integrity and relationships, so that all workshop data remains consistent and reliable.

#### Acceptance Criteria

1. WHEN creating database relationships THEN the system SHALL enforce foreign key constraints between workshops, ticket_types, participants, and email_templates
2. WHEN deleting records THEN the system SHALL handle cascading deletes appropriately to maintain data integrity
3. WHEN updating related records THEN the system SHALL maintain referential integrity across all tables
4. WHEN performing database operations THEN the system SHALL use proper indexing for performance optimization
5. IF data integrity violations occur THEN the system SHALL prevent the operation and display appropriate error messages

### Requirement 10

**User Story:** As a developer, I want the system to follow Laravel best practices and coding standards, so that the codebase is maintainable and scalable.

#### Acceptance Criteria

1. WHEN implementing features THEN the system SHALL use Route Model Binding for clean URL handling
2. WHEN processing forms THEN the system SHALL use FormRequest classes for validation
3. WHEN handling background tasks THEN the system SHALL use Job classes with Queue processing
4. WHEN sending emails THEN the system SHALL use dedicated Mailable classes
5. WHEN implementing business logic THEN the system SHALL use Service classes to keep controllers thin
6. WHEN creating database migrations THEN the system SHALL include proper foreign keys, indexes, nullable fields, and default values
7. WHEN naming variables and methods THEN the system SHALL use clear, consistent English naming conventions