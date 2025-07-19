# Implementation Plan

- [x] 1. Set up database schema and core models



  - Create database migrations for roles, permissions, workshops, ticket_types, participants, and email_templates tables
  - Extend User model with role relationship and is_active field
  - Implement proper foreign key constraints and indexes
  - _Requirements: 9.1, 9.4, 10.6_

- [x] 1.1 Create roles and permissions migration


  - Write migration for roles table with name and description fields
  - Write migration for permissions table with role_id and route_name fields
  - Add unique constraint on role_id and route_name combination
  - _Requirements: 1.1, 1.2, 9.1_


- [x] 1.2 Create workshops migration

  - Write migration for workshops table with name, description, date_time, location, status fields
  - Add enum constraint for status field (draft, published, ongoing, completed, cancelled)
  - Create user_workshop pivot table for many-to-many relationship
  - _Requirements: 2.1, 2.4, 9.1_

- [x] 1.3 Create ticket_types migration


  - Write migration for ticket_types table with workshop_id, name, fee fields
  - Add foreign key constraint to workshops table
  - Add validation for fee field to be non-negative
  - _Requirements: 3.1, 9.1_

- [x] 1.4 Create participants migration


  - Write migration for participants table with all required fields (workshop_id, ticket_type_id, name, email, phone, occupation, address, company, position, ticket_code, is_paid, is_checked_in)
  - Add foreign key constraints to workshops and ticket_types tables
  - Add unique constraint on ticket_code field
  - Set default values for is_paid and is_checked_in as false
  - _Requirements: 4.1, 4.6, 9.1_

- [x] 1.5 Create email_templates migration


  - Write migration for email_templates table with workshop_id, type, subject, content fields
  - Add enum constraint for type field (invite, confirm, ticket, reminder, thank_you)
  - Add foreign key constraint to workshops table
  - _Requirements: 7.1, 9.1_

- [x] 1.6 Extend User model and create core models


  - Modify User model to include role relationship and is_active field
  - Create Role model with hasMany User and hasMany Permission relationships
  - Create Permission model with belongsTo Role relationship
  - Add hasPermission() method to Role model for route checking
  - _Requirements: 1.3, 1.4, 10.1_

- [x] 2. Implement role-based permission system



  - Create middleware for route permission checking
  - Implement role and permission management controllers
  - Create form requests for validation
  - Write unit tests for permission checking logic
  - _Requirements: 1.4, 1.7, 10.2_

- [x] 2.1 Create CheckRoutePermissionMiddleware


  - Write middleware to check user's role permissions against current route
  - Implement logic to return 403 Forbidden for unauthorized access
  - Add meaningful error messages for permission denials
  - Register middleware in HTTP kernel
  - _Requirements: 1.4, 1.7_

- [x] 2.2 Create RoleController with CRUD operations


  - Implement index, create, store, show, edit, update, destroy methods
  - Add permission assignment functionality
  - Create RoleRequest for form validation
  - Implement route permission management interface
  - _Requirements: 1.1, 1.2, 10.2_

- [x] 2.3 Create RolePermissionService


  - Implement hasRoutePermission() method for checking user permissions
  - Create methods for assigning and removing permissions from roles
  - Add caching for permission lookups to improve performance
  - Write unit tests for all service methods
  - _Requirements: 1.4, 10.5_

- [x] 3. Implement workshop management system



  - Create Workshop model with relationships
  - Implement WorkshopController with CRUD operations
  - Create workshop management views
  - Add organizer assignment functionality
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 3.1 Create Workshop model


  - Define Workshop model with fillable fields and relationships
  - Add belongsToMany relationship to User (organizers)
  - Add hasMany relationships to TicketType, Participant, EmailTemplate
  - Create scopes for active, upcoming, and past workshops
  - _Requirements: 2.1, 2.4, 10.1_

- [x] 3.2 Create WorkshopController


  - Implement CRUD operations (index, create, store, show, edit, update, destroy)
  - Add organizer assignment and removal functionality
  - Implement workshop status management
  - Create WorkshopRequest for form validation
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 10.2_

- [x] 3.3 Create WorkshopService


  - Implement business logic for workshop lifecycle management
  - Add methods for organizer assignment and status transitions
  - Handle dependent data when deleting workshops
  - Write unit tests for all service methods
  - _Requirements: 2.3, 2.4, 10.5_

- [x] 4. Implement ticket type management



  - Create TicketType model with Workshop relationship
  - Implement TicketTypeController with CRUD operations
  - Add validation to prevent deletion of ticket types with participants
  - Create ticket type management interface
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 4.1 Create TicketType model


  - Define TicketType model with workshop_id, name, fee fields
  - Add belongsTo Workshop and hasMany Participant relationships
  - Implement validation for fee field (non-negative)
  - Add scope for ticket types by workshop
  - _Requirements: 3.1, 10.1_

- [x] 4.2 Create TicketTypeController


  - Implement CRUD operations with workshop context
  - Add validation to prevent deletion when participants exist
  - Create TicketTypeRequest for form validation
  - Implement proper error handling and user feedback
  - _Requirements: 3.2, 3.3, 3.5, 10.2_

- [x] 5. Implement participant management system



  - Create Participant model with relationships
  - Implement ParticipantController with CRUD and import functionality
  - Add ticket code generation and QR code creation
  - Implement Excel import processing
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8_

- [x] 5.1 Create Participant model


  - Define Participant model with all required fields
  - Add belongsTo relationships to Workshop and TicketType
  - Implement ticket code generation in model mutator
  - Add scopes for paid and checked-in participants
  - _Requirements: 4.1, 4.3, 4.6, 10.1_

- [x] 5.2 Create ParticipantController


  - Implement CRUD operations for participants
  - Add manual participant creation with form validation
  - Implement payment status update functionality
  - Create ParticipantRequest for form validation
  - _Requirements: 4.1, 4.4, 4.5, 4.7, 10.2_

- [x] 5.3 Implement Excel import functionality


  - Create ProcessParticipantImportJob for background processing
  - Add Excel file validation and parsing logic
  - Implement batch participant creation from Excel data
  - Add progress tracking and error reporting for imports
  - _Requirements: 4.2, 10.3_


- [x] 5.4 Create ParticipantService

  - Implement business logic for participant management
  - Add methods for ticket code generation and validation
  - Handle participant creation workflow including QR code generation
  - Write unit tests for all service methods
  - _Requirements: 4.3, 4.6, 10.5_

- [x] 6. Implement QR code generation and email system



  - Install and configure SimpleSoftwareIO/simple-qrcode package
  - Create QRCodeService for code generation
  - Implement email templates and mailable classes
  - Set up queue processing for email sending
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 6.1 Install QR code package and create QRCodeService


  - Install SimpleSoftwareIO/simple-qrcode via composer
  - Create QRCodeService with generateQRCode() method
  - Implement QR code image generation from ticket codes
  - Add QR code validation and decoding methods
  - _Requirements: 5.1, 6.1_

- [x] 6.2 Create TicketMailable class


  - Create TicketMailable extending Laravel Mailable
  - Implement email template rendering with QR code attachment
  - Add support for dynamic variable substitution
  - Configure email styling and layout
  - _Requirements: 5.2, 5.4, 10.4_

- [x] 6.3 Create SendTicketEmailJob


  - Create queued job for sending ticket emails
  - Implement error handling and retry logic for failed emails
  - Add logging for email sending status
  - Configure job queue processing
  - _Requirements: 5.2, 5.5, 10.3_

- [x] 6.4 Create EmailService


  - Implement email dispatch coordination
  - Add methods for resending tickets and bulk email operations
  - Integrate with email templates for personalized content
  - Write unit tests for email service methods
  - _Requirements: 5.3, 5.4, 10.5_

- [ ] 7. Implement email template management
  - Create EmailTemplate model with Workshop relationship
  - Implement EmailTemplateController with CRUD operations
  - Add template variable substitution functionality
  - Create template preview and testing features
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 7.1 Create EmailTemplate model
  - Define EmailTemplate model with workshop_id, type, subject, content fields
  - Add belongsTo Workshop relationship
  - Implement renderContent() method for variable substitution
  - Add validation for template variables and syntax
  - _Requirements: 7.1, 7.2, 10.1_

- [ ] 7.2 Create EmailTemplateController
  - Implement CRUD operations for email templates
  - Add template preview functionality with sample data
  - Create EmailTemplateRequest for form validation
  - Implement template variable testing and validation
  - _Requirements: 7.1, 7.4, 10.2_

- [ ] 7.3 Implement template rendering system
  - Create template variable substitution using Blade or str_replace
  - Support dynamic variables like {{ name }}, {{ ticket_code }}, {{ qr_code_url }}
  - Add template validation to ensure proper variable syntax
  - Integrate with email sending workflow
  - _Requirements: 7.2, 7.3, 7.5_

- [ ] 8. Implement check-in system with QR code scanning
  - Create CheckInController for QR code processing
  - Implement QR code scanning and validation
  - Add participant verification and check-in workflow
  - Create check-in interface for staff members
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 8.1 Create CheckInController
  - Implement QR code scanning endpoint
  - Add participant lookup by ticket code
  - Create check-in processing with status updates
  - Handle invalid QR codes and already checked-in participants
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 10.2_

- [ ] 8.2 Create CheckInService
  - Implement QR code decoding and validation logic
  - Add participant verification and status checking
  - Handle check-in workflow and database updates
  - Write unit tests for check-in service methods
  - _Requirements: 6.1, 6.2, 6.5, 10.5_

- [ ] 8.3 Create check-in interface
  - Build web interface for QR code scanning
  - Add participant information display after successful scan
  - Implement error handling for invalid codes
  - Create mobile-responsive design for staff use
  - _Requirements: 6.4, 6.5_

- [ ] 9. Implement dashboard and analytics system
  - Create DashboardController for statistics
  - Implement StatisticsService for data aggregation
  - Build dashboard views with workshop metrics
  - Add revenue calculation and reporting
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 9.1 Create StatisticsService
  - Implement workshop metrics calculation (participants, check-ins, revenue)
  - Add methods for aggregating data across all workshops
  - Create revenue calculation based on ticket types and payments
  - Write unit tests for all statistics calculations
  - _Requirements: 8.1, 8.4, 10.5_

- [ ] 9.2 Create DashboardController
  - Implement dashboard data aggregation
  - Add filtering by workshop and date ranges
  - Create API endpoints for real-time data updates
  - Implement caching for performance optimization
  - _Requirements: 8.2, 8.3, 8.5, 10.2_

- [ ] 9.3 Build dashboard interface
  - Create dashboard views with charts and statistics
  - Implement workshop-specific metrics display
  - Add real-time data updates using AJAX
  - Create responsive design for mobile access
  - _Requirements: 8.1, 8.2, 8.3, 8.5_

- [ ] 10. Implement user management with role assignment
  - Extend UserController for role-based user management
  - Add user activation/deactivation functionality
  - Implement user filtering by role
  - Create user management interface
  - _Requirements: 1.3, 1.5, 1.6_

- [ ] 10.1 Extend UserController
  - Add role assignment functionality to user CRUD operations
  - Implement user activation and deactivation methods
  - Add filtering by role in user listing
  - Create UserRequest for validation including role assignment
  - _Requirements: 1.3, 1.5, 1.6, 10.2_

- [ ] 10.2 Create user management interface
  - Build user listing with role filtering
  - Add user creation and editing forms with role selection
  - Implement user activation/deactivation controls
  - Create responsive design for user management
  - _Requirements: 1.3, 1.5, 1.6_

- [ ] 11. Create comprehensive test suite
  - Write unit tests for all models and services
  - Create feature tests for controllers and workflows
  - Implement integration tests for email and QR code systems
  - Add browser tests for complete user workflows
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [ ] 11.1 Write model and service unit tests
  - Create tests for all model relationships and methods
  - Write comprehensive tests for service class business logic
  - Test validation rules and constraints
  - Add tests for permission checking and role management
  - _Requirements: 10.1, 10.5_

- [ ] 11.2 Create controller feature tests
  - Write tests for all controller endpoints and CRUD operations
  - Test authentication and authorization workflows
  - Add tests for form submissions and validation
  - Test API responses and error handling
  - _Requirements: 10.2_

- [ ] 11.3 Implement integration tests
  - Create tests for complete email sending workflow
  - Test QR code generation and scanning processes
  - Add tests for Excel import functionality
  - Test permission middleware integration
  - _Requirements: 10.3, 10.4_

- [ ] 12. Set up application routing and middleware
  - Define all application routes with proper naming
  - Apply authentication and permission middleware
  - Implement route model binding for clean URLs
  - Create route groups for different user roles
  - _Requirements: 1.4, 1.7, 10.1_

- [ ] 12.1 Create application routes
  - Define resource routes for all controllers
  - Implement route model binding for workshops, participants, etc.
  - Add custom routes for special functionality (check-in, import, etc.)
  - Apply proper route naming conventions
  - _Requirements: 10.1_

- [ ] 12.2 Apply middleware and route protection
  - Apply authentication middleware to protected routes
  - Implement permission middleware on role-specific routes
  - Create route groups for different access levels
  - Add CSRF protection to all form routes
  - _Requirements: 1.4, 1.7_

- [ ] 13. Create database seeders and factories
  - Create factory classes for all models
  - Implement database seeders for roles and permissions
  - Add sample data seeders for development
  - Create test data factories for automated testing
  - _Requirements: 9.1, 10.6_

- [ ] 13.1 Create model factories
  - Write factory classes for User, Role, Workshop, Participant, etc.
  - Implement realistic fake data generation
  - Add factory states for different scenarios
  - Create relationship factories for complex data structures
  - _Requirements: 10.6_

- [ ] 13.2 Create database seeders
  - Write seeder for default roles and permissions
  - Create sample workshop and participant data seeder
  - Implement admin user seeder for initial setup
  - Add development environment data seeder
  - _Requirements: 9.1, 10.6_

- [ ] 14. Set up Metronic theme integration
  - Create base Blade layouts using Metronic theme structure
  - Configure asset management for Metronic CSS/JS files
  - Implement responsive navigation with role-based menu items
  - Set up theme components and partials
  - _Requirements: Frontend Integration_

- [ ] 14.1 Create main layout structure
  - Create app.blade.php layout extending Metronic's dark-sidebar layout
  - Implement header partial with user profile and notifications
  - Create sidebar partial with role-based navigation menu
  - Add footer partial with system information
  - _Requirements: Frontend Integration_

- [ ] 14.2 Configure asset management
  - Set up Vite configuration to include Metronic assets
  - Create asset helper for Metronic CSS/JS bundle files
  - Configure public path for Metronic assets in demo1 folder
  - Add custom CSS/JS compilation for application-specific styles
  - _Requirements: Frontend Integration_

- [ ] 14.3 Create authentication layouts
  - Create auth.blade.php layout for login/register pages
  - Implement Metronic authentication page styling
  - Add form validation styling and error display
  - Create responsive authentication interface
  - _Requirements: Frontend Integration_

- [ ] 15. Implement dashboard interface with Metronic components
  - Create dashboard view using Metronic cards and widgets
  - Integrate Chart.js for analytics visualization
  - Implement statistics cards with real-time data
  - Add quick action buttons and recent activity sections
  - _Requirements: 8.1, 8.2, 8.3, Frontend Integration_

- [ ] 15.1 Create dashboard layout and cards
  - Build dashboard view with Metronic card components
  - Implement overview statistics cards (workshops, participants, revenue)
  - Add recent workshops table with Metronic styling
  - Create responsive grid layout for dashboard widgets
  - _Requirements: 8.1, 8.2, Frontend Integration_

- [ ] 15.2 Integrate charts and analytics
  - Set up Chart.js with Metronic theme colors
  - Create attendance trend charts and revenue analytics
  - Implement interactive dashboard widgets
  - Add real-time data updates using AJAX
  - _Requirements: 8.3, 8.5, Frontend Integration_

- [ ] 16. Create workshop management interface
  - Build workshop CRUD views using Metronic components
  - Implement DataTables for workshop listing with advanced features
  - Create workshop forms with Metronic form styling
  - Add calendar view for workshop scheduling
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, Frontend Integration_

- [ ] 16.1 Create workshop listing interface
  - Build workshop index view with Metronic DataTable
  - Implement search, filter, and pagination functionality
  - Add status badges and quick action buttons
  - Create responsive table design for mobile devices
  - _Requirements: 2.5, Frontend Integration_

- [ ] 16.2 Create workshop forms
  - Build create/edit workshop forms using Metronic form components
  - Implement date/time pickers and rich text editors
  - Add form validation with Metronic styling
  - Create organizer assignment interface with Select2
  - _Requirements: 2.1, 2.2, 2.4, Frontend Integration_

- [ ] 17. Implement participant management interface
  - Create participant CRUD views with Metronic styling
  - Build Excel import interface with progress indicators
  - Implement bulk actions and advanced filtering
  - Add QR code display and check-in interface
  - _Requirements: 4.1, 4.2, 4.4, 4.5, 6.4, 6.5, Frontend Integration_

- [ ] 17.1 Create participant listing and management
  - Build participant index view with advanced DataTable
  - Implement bulk selection and actions (payment, check-in, email)
  - Add export functionality for participant data
  - Create responsive participant cards for mobile view
  - _Requirements: 4.4, 4.5, 4.7, 4.8, Frontend Integration_

- [ ] 17.2 Create Excel import interface
  - Build file upload interface using Dropzone.js
  - Implement progress bar for import processing
  - Add import validation and error reporting
  - Create import preview and confirmation dialog
  - _Requirements: 4.2, Frontend Integration_

- [ ] 17.3 Create check-in interface
  - Build QR code scanning interface for staff
  - Implement participant verification display
  - Add check-in status updates with real-time feedback
  - Create mobile-optimized check-in workflow
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, Frontend Integration_

- [ ] 18. Implement user and role management interface
  - Create user management views with role assignment
  - Build role and permission management interface
  - Implement user activation/deactivation controls
  - Add permission tree view for role configuration
  - _Requirements: 1.1, 1.2, 1.3, 1.5, 1.6, Frontend Integration_

- [ ] 18.1 Create user management interface
  - Build user CRUD views with Metronic styling
  - Implement role assignment dropdown with Select2
  - Add user status toggle and filtering options
  - Create user profile and activity tracking
  - _Requirements: 1.3, 1.5, 1.6, Frontend Integration_

- [ ] 18.2 Create role and permission management
  - Build role management interface with permission assignment
  - Implement permission tree view with checkboxes
  - Add role-based route permission configuration
  - Create permission testing and validation interface
  - _Requirements: 1.1, 1.2, 1.4, Frontend Integration_

- [ ] 19. Add notification and alert systems
  - Implement Toastr notifications for user feedback
  - Add SweetAlert2 for confirmation dialogs
  - Create real-time notifications for system events
  - Implement email status notifications and alerts
  - _Requirements: Frontend Integration, User Experience_

- [ ] 19.1 Set up notification system
  - Configure Toastr for success/error/info notifications
  - Implement SweetAlert2 for delete confirmations
  - Add notification helpers for consistent messaging
  - Create notification queue for multiple messages
  - _Requirements: Frontend Integration_

- [ ] 19.2 Create real-time updates
  - Implement AJAX for real-time dashboard updates
  - Add live status updates for workshop and participant changes
  - Create notification badges for new activities
  - Implement auto-refresh for critical data sections
  - _Requirements: 8.5, Frontend Integration_