# Jawla (جولة) - Data Model

## Overview

This document describes the core data model for the Jawla system, including entities, relationships, attributes, and constraints. The model is designed to support multi-tenancy, offline synchronization, auditability, and extensibility.

## Core Principles

### Multi-Tenancy

- Every business-relevant entity includes a `company_id` foreign key
- Global scopes automatically apply tenant isolation
- Cross-company access requires explicit authorization and audit logging

### Immutability and Auditability

- Financial transactions use append-only ledger patterns
- Approved records cannot be modified directly; changes require compensating transactions
- Every significant change creates an audit record in the `activities` table
- Soft deletes preserve historical data while marking records as inactive

### Offline Synchronization Support

- Entities include synchronization metadata (timestamps, version vectors)
- Conflict resolution strategies defined per entity type
- Idempotency keys prevent duplicate processing
- Tombstone records track deletions in offline scenarios

### Extensibility

- JSONB columns for flexible attributes where appropriate
- Extension models via polymorphism for specialized types
- Event-driven architecture for loose coupling
- Versioned API contracts for backward compatibility

## Core Entities

### Tenant and Organization

#### companies

- **Purpose**: Represents a tenant/business entity using the system
- **Key Attributes**:
  - `id`: UUID primary key
  - `name_ar`, `name_en`: Company name in Arabic and English
  - `tax_number`: Tax identification number
  - `commercial_registration`: Business registration number
  - `address`: Primary address
  - `phone`, `email`: Contact information
  - `vat_percent`: Default VAT rate
  - `bank_name`, `bank_iban`: Banking information
  - `currency`: Base currency (ISO 4217)
  - `timezone`: Default timezone
  - `geofence_radius_m`: Default geofence radius for check-in
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Has many: users, warehouses, customers, products, etc.
  - Belongs to many: roles (through pivot table)

#### users

- **Purpose**: Represents a system user (employee, agent, etc.)
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `first_name_ar`, `first_name_en`: First name
  - `last_name_ar`, `last_name_en`: Last name
  - `email`: Unique email address
  - `phone`: Mobile phone number
  - `password`: Hashed password (argon2id)
  - `job_title`: Position within company
  - `employee_id`: Internal employee identifier
  - `is_active`: Soft delete flag
  - `email_verified_at`: Email verification timestamp
  - `remember_token`: For "remember me" functionality
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to many: roles (through pivot table)
  - Has one: profile (optional extended information)
  - Has many: visits, invoices, payments, expenses, etc.
  - Belongs to many: companies (through company_user pivot for multi-company users)

#### roles

- **Purpose**: Defines permission sets that can be assigned to users
- **Key Attributes**:
  - `id`: UUID primary key
  - `name`: Role name (unique)
  - `guard_name`: Authentication guard (typically 'web' or 'api')
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to many: users (through pivot table)
  - Belongs to many: permissions (through pivot table)

#### permissions

- **Purpose**: Defines specific atomic permissions that can be granted to roles
- **Key Attributes**:
  - `id`: UUID primary key
  - `name`: Permission name (unique)
  - `guard_name`: Authentication guard
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to many: roles (through pivot table)

### Core Business Entities

#### customers

- **Purpose**: Represents a business customer or client
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `code`: Unique customer code within company
  - `name_ar`, `name_en`: Legal name in Arabic and English
  - `trade_name_ar`, `trade_name_en`: Trading name (if different)
  - `tax_number`: Tax identification number
  - `commercial_registration`: Business registration number
  - `industry_id`: Foreign key to industry classification
  - `customer_type`: Category (strategic, key_account, regular, etc.)
  - `credit_limit`: Maximum credit amount
  - `payment_terms`: Standard payment terms (net 30, etc.)
  - `discount_percent`: Standard discount percentage
  - `price_list_id`: Default price list
  - `visit_frequency`: Expected visit frequency (daily, weekly, etc.)
  - `preferred_visit_days`: Bitmask or JSON array of preferred days
  - `address`: Primary address
  - `latitude`, `longitude`: GPS coordinates for navigation
  - `is_active`: Soft delete flag
  - `balance`: Current account balance (denormalized for performance)
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to: industry
  - Belongs to: price_list (default)
  - Has many: contacts (customer_contacts)
  - Has many: addresses (customer_addresses)
  - Has many: invoices
  - Has many: payments
  - Has many: credit_notes
  - Has many: visits
  - Has many: customer_credits
  - Belongs to many: price_lists (allowed price lists)
  - Belongs to: sales_rep (assigned representative, optional)
  - Belongs to: territory (assigned territory, optional)

#### customer_contacts

- **Purpose**: Represents contact persons at a customer
- **Key Attributes**:
  - `id`: UUID primary key
  - `customer_id`: Foreign key to customers
  - `first_name_ar`, `first_name_en`: First name
  - `last_name_ar`, `last_name_en`: Last name
  - `job_title`: Position at customer
  - `phone`: Direct phone number
  - `mobile`: Mobile phone number
  - `email`: Email address
  - `is_primary`: Boolean flag for primary contact
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: customer

#### customer_addresses

- **Purpose**: Represents additional addresses for a customer
- **Key Attributes**:
  - `id`: UUID primary key
  - `customer_id`: Foreign key to customers
  - `address_type`: Type (billing, shipping, headquarters, etc.)
  - `address_line_1`, `address_line_2`: Address lines
  - `city`: City
  - `state`: State/province
  - `postal_code`: Postal/ZIP code
  - `country`: Country (ISO 3166-1 alpha-2)
  - `latitude`, `longitude`: GPS coordinates
  - `is_default`: Boolean flag for default address of this type
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: customer

#### industries

- **Purpose**: Classifies customers by business sector
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies (allows company-specific taxonomies)
  - `name_ar`, `name_en`: Industry name in Arabic and English
  - `description`: Detailed description
  - `parent_id`: Self-referencing for hierarchical taxonomy
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: company
  - Belongs to: parent (industry, for hierarchy)
  - Has many: children (industries, for hierarchy)
  - Has many: customers

#### products

- **Purpose**: Represents a product or service offered by the company
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `sku`: Stock Keeping Unit (unique within company)
  - `barcode`: UPC/EAN or custom barcode
  - `name_ar`, `name_en`: Product name in Arabic and English
  - `description_ar`, `description_en`: Detailed description
  - `category_id`: Foreign key to product_categories
  - `brand`: Brand name
  - `model`: Model number/name
  - `unit_of_measure`: Base unit (each, kg, liter, box, etc.)
  - `weight`: Weight per unit (in kg)
  - `volume`: Volume per unit (in liters)
  - `length`, `width`, `height`: Dimensions (in cm)
  - `is_active`: Soft delete flag
  - `is_taxable`: Boolean for tax applicability
  - `is_hazardous`: Boolean for hazardous materials
  - `has_batch_tracking`: Boolean for lot/batch tracking requirement
  - `has_serial_tracking`: Boolean for serial number tracking requirement
  - `shelf_life_days`: Shelf life in days (for perishable items)
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to: product_category
  - Has many: product_prices (through product_prices pivot)
  - Has many: stocks
  - Has many: stock_movements
  - Has many: invoice_items
  - Has many: return_items
  - Has many: purchase_order_items
  - Belongs to many: price_lists (through product_prices pivot)

#### product_categories

- **Purpose**: Classifies products for organization and reporting
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies (allows company-specific taxonomies)
  - `name_ar`, `name_en`: Category name in Arabic and English
  - `description`: Detailed description
  - `parent_id`: Self-referencing for hierarchical taxonomy
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: company
  - Belongs to: parent (category, for hierarchy)
  - Has many: children (categories, for hierarchy)
  - Has many: products

#### price_lists

- **Purpose**: Defines pricing structures for different customer segments or conditions
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `name_ar`, `name_en`: Name in Arabic and English
  - `description`: Description of purpose/use case
  - `is_active`: Boolean flag
  - `is_default`: Whether this is the default price list for new customers
  - `valid_from`, `valid_to`: Date range for validity
  - `priority`: Priority for price list selection (higher = higher priority)
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to many: products (through product_prices pivot)
  - Belongs to many: customers (allowed price lists)
  - Has many: product_prices

#### product_prices

- **Purpose**: Junction table defining specific product prices in price lists
- **Key Attributes**:
  - `id`: UUID primary key
  - `product_id`: Foreign key to products
  - `price_list_id`: Foreign key to price_lists
  - `price`: Price per unit
  - `min_quantity`: Minimum quantity for this price to apply
  - `max_quantity`: Maximum quantity for this price to apply (null = unlimited)
  - `currency`: Currency ISO code (if different from company base)
  - `effective_from`, `effective_to`: Date range for this price
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: product
  - Belongs to: price_list

### Transactional Entities

#### visits

- **Purpose**: Represents a planned or completed customer visit
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `user_id`: Foreign key to users (representative)
  - `customer_id`: Foreign key to customers
  - `work_session_id`: Foreign key to work_sessions
  - `daily_visit_assignment_id`: Foreign key to daily_visit_assignments
  - `route_id`: Foreign key to routes (optional)
  - `purpose`: Visit purpose (enumerated: sales, service, delivery, pickup, etc.)
  - `status`: Current status (planned, en_route, checked_in, in_progress, completed, reported, closed, cancelled, no_show, etc.)
  - `is_out_of_route`: Boolean indicating if visit deviates from planned route
  - `scheduled_date`: Planned date for visit
  - `scheduled_time_start`: Planned start time
  - `scheduled_time_end`: Planned end time
  - `actual_arrival_time`: Actual arrival timestamp
  - `actual_departure_time`: Actual departure timestamp
  - `checkin_latitude`, `checkin_longitude`: GPS coordinates at check-in
  - `checkin_accuracy_m`: Accuracy of checkin GPS (meters)
  - `checkin_distance_m`: Distance from customer location at check-in (meters)
  - `checkout_latitude`, `checkin_longitude`: GPS coordinates at checkout
  - `checkout_accuracy_m`: Accuracy of checkout GPS (meters)
  - `notes`: Free-form notes from representative
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to: user (representative)
  - Belongs to: customer
  - Belongs to: work_session
  - Belongs to: daily_visit_assignment
  - Belongs to: route (optional)
  - Has one: visit_report
  - Has many: invoices
  - Has many: payments
  - Has many: returns
  - Has many: expenses
  - Has many: visit_attachments

#### visit_reports

- **Purpose**: Captures the outcome and details of a completed visit
- **Key Attributes**:
  - `id`: UUID primary key
  - `visit_id`: Foreign key to visits
  - `summary`: Brief summary of visit (minimum length enforced)
  - `customer_feedback`: Feedback from customer (optional)
  - `action_taken`: Actions taken during visit (optional)
  - `follow_up_needed`: Boolean indicating if follow-up is required
  - `follow_up_note`: Details of required follow-up (if needed)
  - `submitted_at`: Timestamp when report was submitted
  - `signature_path`: Path to stored signature image (if captured)
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: visit
  - Has many: visit_attachments

#### visit_attachments

- **Purpose**: Stores files attached to visit reports
- **Key Attributes**:
  - `id`: UUID primary key
  - `visit_report_id`: Foreign key to visit_reports
  - `file_name`: Original filename
  - `file_path`: Storage path
  - `file_type`: MIME type
  - `file_size`: Size in bytes
  - `description`: Optional description
  - `uploaded_at`: Timestamp
  - `uploaded_by`: User who uploaded
- **Relationships**:
  - Belongs to: visit_report

#### work_sessions

- **Purpose**: Represents a representative's work shift
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `user_id`: Foreign key to users (representative)
  - `start_time`: Timestamp when shift started
  - `end_time`: Timestamp when shift ended (null if active)
  - `start_latitude`, `start_longitude`: GPS coordinates at shift start
  - `end_latitude`, `end_longitude`: GPS coordinates at shift end
  - `total_distance_m`: Total distance traveled during shift (meters)
  - `total_duration_s`: Total duration of shift in seconds
  - `is_active`: Boolean indicating if shift is currently active
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: company
  - Belongs to: user (representative)
  - Has many: visits
  - Has many: location_pings

#### location_pings

- **Purpose**: Stores GPS tracking points during work sessions
- **Key Attributes**:
  - `id`: UUID primary key
  - `work_session_id`: Foreign key to work_sessions
  - `latitude`: Latitude coordinate
  - `longitude`: Longitude coordinate
  - `accuracy_m`: Accuracy of GPS reading (meters)
  - `speed_mps`: Speed in meters per second (calculated)
  - `heading_degrees`: Direction of travel in degrees
  - `altitude_m`: Altitude in meters (if available)
  - `timestamp`: When the reading was taken
  - `battery_level_percent`: Battery percentage at time of reading
  - `is_moving`: Boolean indicating if device was moving
  - `signal_strength_dbm`: Cellular signal strength
  - `network_type`: Type of network (wifi, 4g, 5g, none)
- **Relationships**:
  - Belongs to: work_session

#### daily_visit_assignments

- **Purpose**: Represents a customer visit assigned to a representative for a specific day
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `user_id`: Foreign key to users (representative)
  - `customer_id`: Foreign key to customers
  - `scheduled_date`: Date the visit is scheduled for
  - `scheduled_time_start`: Planned start time
  - `scheduled_time_end`: Planned end time
  - `priority`: Priority level (low, medium, high, urgent)
  - `visit_purpose`: Purpose of the visit
  - `is_completed`: Boolean indicating if visit was completed
  - `completed_at`: Timestamp when visit was marked complete
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: company
  - Belongs to: user (representative)
  - Belongs to: customer
  - Has one: visit (when visit is started)

#### routes

- **Purpose**: Defines a sequence of customer visits for efficiency
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `name_ar`, `name_en`: Route name in Arabic and English
  - `description_ar`, `description_en`: Route description
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: company
  - Has many: route_stops
  - Has many: visits (through route_stops)

#### route_stops

- **Purpose**: Defines the order of stops within a route
- **Key Attributes**:
  - `id`: UUID primary key
  - `route_id`: Foreign key to routes
  - `customer_id`: Foreign key to customers
  - `sequence_number`: Order in the route (1, 2, 3, ...)
  - `estimated_arrival_time`: Expected time of arrival
  - `estimated_departure_time`: Expected time of departure
  - `estimated_duration_min`: Expected duration at stop (minutes)
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: route
  - Belongs to: customer

### Financial Entities

#### invoices

- **Purpose**: Represents a sales invoice for products or services
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `customer_id`: Foreign key to customers
  - `user_id`: Foreign key to users (sales representative)
  - `visit_id`: Foreign key to visits (optional, if visit-related)
  - `proforma_invoice_id`: Foreign key to proforma_invoices (optional)
  - `invoice_number`: Unique sequential number
  - `status`: Current status (draft, issued, sent, viewed, paid, partially_paid, overdue, cancelled, etc.)
  - `issue_date`: Date invoice was issued
  - `due_date`: Date payment is due
  - `po_number`: Customer purchase order number
  - `reference_number`: Additional reference
  - `currency`: Currency ISO code
  - `exchange_rate`: Rate to convert to company currency
  - `subtotal`: Sum of line items before tax
  - `tax_amount`: Total tax amount
  - `total_amount`: Total amount due
  - `paid_amount`: Amount already paid
  - `remaining_amount`: Amount still due
  - `discount_percent`: Overall discount percentage
  - `discount_amount`: Overall discount amount
  - `notes`: Terms and conditions or special notes
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to: customer
  - Belongs to: user (representative)
  - Belongs to: visit (optional)
  - Belongs to: proforma_invoice (optional)
  - Has many: invoice_items
  - Has many: payments
  - Has many: credit_notes
  - Has many: reversals
  - Has one: invoice_snapshot (denormalized data for audit)

#### invoice_items

- **Purpose**: Represents a line item within an invoice
- **Key Attributes**:
  - `id`: UUID primary key
  - `invoice_id`: Foreign key to invoices
  - `product_id`: Foreign key to products
  - `description_ar`, `description_en`: Item description (can override product)
  - `quantity`: Quantity of items
  - `unit_of_measure`: Unit of measure
  - `unit_price`: Price per unit
  - `line_total`: Quantity × unit_price
  - `tax_rate`: Tax rate applied to this line
  - `tax_amount`: Tax amount for this line
  - `line_total_with_tax`: Line total including tax
  - `discount_percent`: Discount percentage for this line
  - `discount_amount`: Discount amount for this line
  - `net_amount`: Final amount for this line after discount
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: invoice
  - Belongs to: product
  - Has one: invoice_item_snapshot (denormalized data for audit)

#### payments

- **Purpose**: Represents a payment received from a customer
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `customer_id`: Foreign key to customers
  - `invoice_id`: Foreign key to invoices (optional, if not allocated to specific invoice)
  - `user_id`: Foreign key to users (user who recorded payment)
  - `visit_id`: Foreign key to visits (optional, if visit-related)
  - `payment_number`: Unique sequential number
  - `payment_date`: Date payment was received
  - `amount`: Amount received
  - `currency`: Currency ISO code
  - `exchange_rate`: Rate to convert to company currency
  - `method`: Payment method (cash, check, bank_transfer, credit_card, digital_wallet, etc.)
  - `reference_number`: Transaction reference from bank/payment processor
  - `check_number`: Check number (if method is check)
  - `check_bank`: Bank name (if method is check)
  - `card_last_four`: Last four digits of card (if card payment)
  - `card_type`: Type of card (visa, mastercard, etc.)
  - `auth_code`: Authorization code from processor
  - `fees`: Processing fees deducted
  - `net_amount`: Amount after fees
  - `allocated_amount`: Amount applied to invoices
  - `unallocated_amount`: Amount not yet applied (held as credit)
  - `status`: Status (pending, cleared, failed, reversed, etc.)
  - `notes`: Memo or description
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to: customer
  - Belongs to: invoice (optional)
  - Belongs to: user (recorder)
  - Belongs to: visit (optional)
  - Has one: payment_snapshot (denormalized data for audit)
  - Has many: customer_credits (for unallocated amounts)

#### customer_credits

- **Purpose**: Tracks customer credits from overpayments or adjustments
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `customer_id`: Foreign key to customers
  - `payment_id`: Foreign key to payments (source payment)
  - `invoice_id`: Foreign key to invoices (if applied to specific invoice)
  - `credit_number`: Unique identifier
  - `amount`: Original credit amount
  - `remaining_amount`: Amount still available for use
  - `currency`: Currency ISO code
  - `status`: Status (available, used, expired, transferred, etc.)
  - `expires_at`: Expiration date (if applicable)
  - `reason`: Reason for credit (overpayment, refund, promotion, etc.)
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to: customer
  - Belongs to: payment (optional)
  - Belongs to: invoice (optional)

#### credit_notes

- **Purpose**: Represents a credit issued to a customer (often for returns or adjustments)
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `customer_id`: Foreign key to customers
  - `invoice_id`: Foreign key to invoices (original invoice being credited)
  - `user_id`: Foreign key to users (user who created credit)
  - `credit_number`: Unique sequential number
  - `issue_date`: Date credit was issued
  - `reason`: Reason for credit (return, overcharge, etc.)
  - `description`: Detailed description
  - `subtotal`: Sum of line items before tax
  - `tax_amount`: Total tax amount
  - `total_amount`: Total credit amount
  - `status`: Status (draft, issued, applied, etc.)
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to: customer
  - Belongs to: invoice (original)
  - Belongs to: user (creator)
  - Has many: credit_note_items
  - Has one: credit_note_snapshot (denormalized data for audit)

#### credit_note_items

- **Purpose**: Represents a line item within a credit note
- **Key Attributes**:
  - `id`: UUID primary key
  - `credit_note_id`: Foreign key to credit_notes
  - `product_id`: Foreign key to products
  - `description_ar`, `description_en`: Item description
  - `quantity`: Quantity of items
  - `unit_of_measure`: Unit of measure
  - `unit_price`: Price per unit
  - `line_total`: Quantity × unit_price
  - `tax_rate`: Tax rate applied to this line
  - `tax_amount`: Tax amount for this line
  - `line_total_with_tax`: Line total including tax
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: credit_note
  - Belongs to: product

#### reversals

- **Purpose**: Represents a reversal of a financial transaction (invoice, payment, etc.)
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `original_type`: Model class being reversed (Invoice, Payment, etc.)
  - `original_id`: ID of the original record
  - `reversal_type`: Type of reversal (void, refund, correction, etc.)
  - `user_id`: Foreign key to users (user who initiated reversal)
  - `reversal_number`: Unique sequential number
  - `reversal_date`: Date reversal was processed
  - `reason`: Reason for reversal
  - `amount`: Amount being reversed
  - `currency`: Currency ISO code
  - `status`: Status (draft, posted, etc.)
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Morph to: original (polymorphic relationship to Invoice, Payment, etc.)
  - Belongs to: user (initiator)
  - Has one: reversal_snapshot (denormalized data for audit)

### Inventory Management Entities

#### warehouses

- **Purpose**: Represents a physical storage location
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `name_ar`, `name_en`: Warehouse name in Arabic and English
  - `code`: Unique code within company
  - `warehouse_type`: Type (central, regional, distribution, van, consignment, quarantine, etc.)
  - `address`: Physical address
  - `latitude`, `longitude`: GPS coordinates
  - `is_active`: Boolean indicating if warehouse is operational
  - `is_default`: Whether this is the default warehouse for new stock
  - `capacity_volume_m3`: Volumetric capacity (cubic meters)
  - `capacity_weight_kg`: Weight capacity (kilograms)
  - `temperature_controlled`: Boolean indicating temperature control
  - `temperature_min_c`, `temperature_max_c`: Temperature range (Celsius)
  - `humidity_controlled`: Boolean indicating humidity control
  - `security_level`: Security level (low, medium, high, maximum)
  - `is_bonded`: Boolean indicating bonded warehouse status
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Has many: stocks
  - Has many: stock_movements
  - Has many: inventory_transactions
  - Has one: van_details (if warehouse_type = 'van')

#### van_details

- **Purpose**: Stores vehicle-specific information for van warehouses
- **Key Attributes**:
  - `id`: UUID primary key
  - `warehouse_id`: Foreign key to warehouses (where warehouse_type = 'van')
  - `vehicle_identification_number`: VIN
  - `license_plate`: License plate number
  - `make`: Vehicle manufacturer
  - `model`: Vehicle model
  - `year`: Model year
  - `color`: Vehicle color
  - `fuel_type`: Fuel type (gasoline, diesel, electric, hybrid)
  - `tank_capacity_liters`: Fuel tank capacity
  - `mileage_km`: Current odometer reading
  - `last_service_date`: Date of last service
  - `next_service_due`: Date next service is due
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: warehouse (where warehouse_type = 'van')
  - Has one: van_transfers (as source or destination)

#### stocks

- **Purpose**: Represents the quantity of a specific product at a specific location
- **Key Attributes**:
  - `id`: UUID primary key
  - `warehouse_id`: Foreign key to warehouses
  - `product_id`: Foreign key to products
  - `batch_id`: Foreign key to batches (nullable for non-batch tracked products)
  - `quantity`: Quantity on hand
  - `reserved_quantity`: Quantity allocated to pending orders/transfers
  - `available_quantity`: Quantity available for immediate use (quantity - reserved_quantity)
  - `unit_cost`: Average cost per unit
  - `total_value`: Total inventory value (quantity × unit_cost)
  - `last_counted_date`: Date of last physical count
  - `last_counted_by`: User who performed last count
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: warehouse
  - Belongs to: product
  - Belongs to: batch (optional)
  - Has many: stock_movements

#### batches

- **Purpose**: Represents a specific batch or lot of a product
- **Key Attributes**:
  - `id`: UUID primary key
  - `product_id`: Foreign key to products
  - `batch_number`: Manufacturer's batch/lot number
  - `lot_number`: Internal lot number (if different)
  - `serial_number_range_start`: Starting serial number (if applicable)
  - `serial_number_range_end`: Ending serial number (if applicable)
  - `quantity`: Total quantity in batch
  - `unit_of_measure`: Unit of measure
  - `manufacture_date`: Date of manufacture
  - `expiration_date`: Date of expiration
  - `use_by_date`: Date by which should be used
  - `quality_status`: Quality status (passed, failed, quarantine, etc.)
  - `test_results`: JSON field for test results
  - `origin_country`: Country of origin
  - `supplier_id`: Foreign key to suppliers
  - `purchase_order_id`: Related purchase order
  - `received_date`: Date received into inventory
  - `received_by`: User who received the batch
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: product
  - Belongs to: supplier (optional)
  - Belongs to: purchase_order (optional)
  - Has many: stocks
  - Has many: stock_movements

#### stock_movements

- **Purpose**: Represents a change in stock quantity (the audit trail for inventory)
- **Key Attributes**:
  - `id`: UUID primary key
  - `warehouse_id`: Foreign key to warehouses
  - `product_id`: Foreign key to products
  - `batch_id`: Foreign key to batches (nullable)
  - `quantity_change`: Positive for increase, negative for decrease
  - `reason_code`: Code indicating reason for movement (receipt, issue, transfer, adjustment, etc.)
  - `reason_description`: Detailed description of reason
  - `reference_type`: Model class of related entity (PurchaseOrder, SalesOrder, etc.)
  - `reference_id`: ID of related entity
  - `user_id`: Foreign key to users (user who caused the movement)
  - `cost_per_unit`: Cost per unit at time of movement
  - `total_cost`: Total cost impact of movement
  - `reference_price`: Reference price (if applicable)
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: warehouse
  - Belongs to: product
  - Belongs to: batch (optional)
  - Morph to: reference (polymorphic relationship to PurchaseOrder, SalesOrder, TransferRequest, etc.)
  - Belongs to: user (responsible party)

#### inventory_transactions

- **Purpose**: Represents a logical inventory operation that may involve multiple stock movements
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `transaction_type`: Type (receipt, issue, transfer, adjustment, count, etc.)
  - `reference_type`: Model class of related entity
  - `reference_id`: ID of related entity
  - `transaction_number`: Unique identifier
  - `transaction_date`: Date of transaction
  - `posting_date`: Date for accounting purposes
  - `status`: Status (draft, posted, cancelled, etc.)
  - `total_items`: Number of distinct line items
  - `total_quantity`: Total quantity moved
  - `total_cost`: Total cost impact
  - `remarks`: Additional notes
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Morph to: reference (polymorphic relationship to PurchaseOrder, SalesOrder, TransferRequest, InventoryCount, etc.)
  - Has many: inventory_transaction_lines
  - Has one: inventory_transaction_snapshot (denormalized data for audit)

#### inventory_transaction_lines

- **Purpose**: Line items within an inventory transaction
- **Key Attributes**:
  - `id`: UUID primary key
  - `inventory_transaction_id`: Foreign key to inventory_transactions
  - `warehouse_id`: Foreign key to warehouses
  - `product_id`: Frontend: products
  - `batch_id`: Foreign key to batches (nullable)
  - `quantity`: Quantity of this line item
  - `unit_cost`: Cost per unit
  - `line_total`: Quantity × unit_cost
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: inventory_transaction
  - Belongs to: warehouse
  - Belongs to: product
  - Belongs to: batch (optional)

### Returns Management Entities

#### returns

- **Purpose**: Represents a customer return request
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `customer_id`: Foreign key to customers
  - `user_id`: Foreign key to users (representative who initiated)
  - `visit_id`: Foreign key to visits (if visit-related)
  - `original_invoice_id`: Foreign key to invoices (original invoice, if known)
  - `return_number`: Unique sequential number
  - `return_date`: Date return was initiated
  - `required_by_date`: Date by which customer needs resolution
  - `reason_code`: Standardized reason for return
  - `reason_description`: Detailed description of reason
  - `disposition`: What should happen with returned items (restock, repair, replace, refund, scrap, etc.)
  - `status`: Status (draft, submitted, approved, rejected, received, inspected, completed, closed)
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to: customer
  - Belongs to: user (representative)
  - Belongs to: visit (optional)
  - Belongs to: invoice (optional, original)
  - Has many: return_items
  - Has one: return_snapshot (denormalized data for audit)

#### return_items

- **Purpose**: Represents an item within a return request
- **Key Attributes**:
  - `id`: UUID primary key
  - `return_id`: Foreign key to returns
  - `product_id`: Foreign key to products
  - `description_ar`, `description_en`: Item description
  - `quantity`: Quantity being returned
  - `unit_of_measure`: Unit of measure
  - `condition`: Condition of item (new, like_new, good, fair, poor, damaged, defective, etc.)
  - `reason_code`: Reason for return of this item
  - `reason_description`: Detailed description
  - `disposition`: What was done with this item
  - `refund_amount`: Amount refunded for this item (if applicable)
  - `replacement_provided`: Whether replacement was provided
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: return
  - Belongs to: product

### Expense Management Entities

#### expenses

- **Purpose**: Represents an expense incurred by a representative
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `user_id`: Foreign key to users (who incurred expense)
  - `visit_id`: Foreign key to visits (if visit-related)
  - `customer_id`: Foreign key to customers (if customer-related)
  - `expense_number`: Unique sequential number
  - `expense_date`: Date expense was incurred
  - `expense_type`: Category (transport, meals, accommodation, etc.)
  - `description`: Description of purpose
  - `amount`: Amount spent
  - `currency`: Currency ISO code
  - `exchange_rate`: Rate to convert to company currency
  - `payment_method`: How expense was paid (cash, personal_card, company_card, etc.)
  - `receipt_required`: Whether receipt is required per policy
  - `receipt_attached`: Boolean indicating if receipt is attached
  - `receipt_path`: Path to stored receipt image
  - `is_billable`: Whether expense can be billed to customer
  - `billable_to_customer_id`: Customer to bill (if different from associated customer)
  - `is_reimbursable`: Whether expense is eligible for reimbursement
  - `reimbursement_amount`: Amount to be reimbursed
  - `status`: Status (draft, submitted, approved, rejected, reimbursed, paid)
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
  - `created_by`, `updated_by`: Audit trail
- **Relationships**:
  - Belongs to: company
  - Belongs to: user (who incurred)
  - Belongs to: visit (optional)
  - Belongs to: customer (optional)
  - Has one: expense_snapshot (denormalized data for audit)

### Notification and Communication Entities

#### notifications

- **Purpose**: Represents a notification sent to a user
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `user_id`: Foreign key to users (recipient)
  - `notification_type`: Type (system_alert, approval_required, payment_received, etc.)
  - `title`: Short title
  - `message`: Full message body
  - `data`: JSON field for structured data
  - `related_type`: Model class of related entity (optional)
  - `related_id`: ID of related entity (optional)
  - `is_read`: Boolean indicating if notification has been read
  - `read_at`: Timestamp when read
  - `priority`: Priority level (low, medium, high, urgent)
  - `delivery_channels`: JSON array of channels (in_app, email, sms, push, whatsapp)
  - `sent_at`: Timestamp when sent
  - `delivered_at`: Timestamp when delivered (if trackable)
  - `read_at`: Timestamp when read
  - `expires_at`: Expiration date (if applicable)
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: company
  - Belongs to: user (recipient)
  - Morph to: related (polymorphic relationship to any entity)

#### notification_templates

- **Purpose**: Defines templates for generating notifications
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `template_name`: Unique name within company
  - `notification_type`: Type of notification
  - `language`: Language code (ar, en, etc.)
  - `subject_template`: Template for subject/title
  - `body_template`: Template for body
  - `available_variables`: JSON array of variable names that can be used
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: company

### Synchronization and Offline Entities

#### sync_receipts

- **Purpose**: Tracks synchronization status for offline operations
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `user_id`: Foreign key to users
  - `device_id`: Unique device identifier
  - `protocol_version`: Version of synchronization protocol used
  - `last_sync_at`: Timestamp of last successful synchronization
  - `last_successful_sync_at`: Timestamp of last successful synchronization
  - `pending_operations_count`: Number of operations waiting to be synced
  - `failed_operations_count`: Number of operations that failed during sync
  - `conflict_count`: Number of conflicts detected during sync
  - `is_syncing`: Boolean indicating if synchronization is currently in progress
  - `last_error_message`: Error message from last failed sync
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: company
  - Belongs to: user
  - Has one: device

#### sync_operations

- **Purpose**: Individual operations waiting to be synchronized
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `user_id`: Foreign key to users
  - `operation_type`: Type of operation (create, update, delete)
  - `entity_type`: Model class of entity
  - `entity_id`: ID of entity (for update/delete) or null (for create)
  - `payload`: JSON representation of entity data
  - `timestamp`: When operation was created
  - `attempt_count`: Number of times attempted
  - `last_attempt_at`: Timestamp of last attempt
  - `last_error_message`: Error message from last attempt
  - `is_processed`: Boolean indicating if successfully processed
  - `processed_at`: Timestamp when processed
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: company
  - Belongs to: user

#### sync_conflicts

- **Purpose**: Records conflicts detected during synchronization
- **Key Attributes**:
  - `id`: UUID primary key
  - `company_id`: Foreign key to companies
  - `user_id`: Foreign key to users
  - `device_id`: Conflicting device identifier
  - `server_version`: Version from server
  - `client_version`: Version from client
  - `entity_type`: Model class of entity
  - `entity_id`: ID of entity
  - `server_data`: JSON representation of server data
  - `client_data`: JSON representation of client data
  - `conflict_fields`: JSON array of fields in conflict
  - `resolution_strategy`: How conflict was resolved (server_wins, client_wins, merge, manual)
  - `resolved_at`: Timestamp when resolved
  - `resolved_by`: User who resolved (if manual)
  - `is_active`: Soft delete flag
  - `created_at`, `updated_at`: Timestamps
- **Relationships**:
  - Belongs to: company
  - Belongs to: user
  - Belongs to: device

## Extension Patterns

### JSONB Fields

Several entities use JSONB columns for flexible attributes:

- `users.preferences`: User interface and behavior preferences
- `products.attributes`: Product-specific attributes (color, size, material, etc.)
- `customers.attributes`: Customer-specific attributes (loyalty tier, preferences, etc.)
- `orders.metadata`: Order-specific metadata (source channel, campaign, etc.)
- `payments.gateway_response`: Raw response from payment gateway
- `inventory_transaction_lines.attributes`: Line item-specific attributes

### Polymorphic Relationships

Used when an entity can relate to multiple different types:

- `activities.subject`: Can point to visits, invoices, payments, users, etc.
- `inventory_transactions.reference`: Can point to purchase_orders, sales_orders, transfer_requests, etc.
- `stock_movements.reference`: Can point to purchase_orders, sales_orders, transfer_requests, inventory_counts, etc.
- `notifications.related`: Can point to any entity for context

### Model Traits

Reusable functionality encapsulated in traits:

- `BelongsToCompany`: Automatically adds `company_id` and scope
- `SoftDeletes`: Adds `deleted_at` column for soft delete functionality
- `Timestamps`: Adds `created_at` and `updated_at` columns
- `Uuid`: Uses UUIDs instead of auto-incrementing IDs
- `AuditTrail`: Automatically populates `created_by` and `updated_by`
- `Encryptable`: Automatically encrypts/decrypts specified fields
- `SoftDeletesGlobalScope`: Applies soft delete scope globally

## Indexing Strategy

### Primary Keys

- All tables use UUID primary keys (`id` column)
- Indexed by default in PostgreSQL

### Foreign Keys

- All foreign key columns are indexed
- Composite foreign keys (where applicable) are indexed

### Common Query Patterns

- `company_id` + `status` + date ranges: Indexed for dashboard queries
- `user_id` + date ranges: Indexed for representative performance queries
- `customer_id` + date ranges: Indexed for customer history queries
- `sku` or `barcode`: Unique indexes for product lookups
- `email`: Unique index for user authentication
- `tax_number`: Unique index for customer/vendor lookups
- `latitude`, `longitude`: Indexed (with appropriate operators) for geospatial queries
- `created_at`: Indexed for time-based queries and partitioning

### Specialized Indexes

- **Full-text search**:
  - `name_ar`, `name_en` on customers, products
  - `description_ar`, `description_en` on products
  - `address` on customers, warehouses
- **JSONB indexing**:
  - For frequently queried JSONB paths (using GIN indexes)
  - Example: `preferences ->> 'theme'` for user interface preferences
- **Partial indexes**:
  - For soft-deleted records: `WHERE deleted_at IS NULL`
  - For active records: `WHERE is_active = true`
  - For status-based queries: `WHERE status IN ('active', 'pending')`

## Constraints and Validation

### Database Constraints

- **Foreign Key Constraints**: Ensure referential integrity
- **Unique Constraints**:
  - `users.email` per company
  - `customers.code` per company
  - `products.sku` per company
  - `products.barcode` (global unique, optionally nullable)
  - `warehouses.code` per company
  - `invoices.invoice_number` per company
  - `payments.payment_number` per company
- **Check Constraints**:
  - Quantity fields >= 0 (where applicable)
  - Percentage fields between 0 and 100
  - Date ranges (valid_from <= valid_to)
  - Status values constrained to allowed sets
  - Boolean fields properly constrained
- **Exclusion Constraints**:
  - Prevent overlapping date ranges for reservations
  - Prevent conflicting schedules for resources

### Application-Level Validation

- **Form Requests**: Laravel form request validation for API endpoints
- **Model Events**: Observers for automatic validation and normalization
- **Service Layer**: Business rule validation in service methods
- **Database Triggers**: For complex constraints that cannot be expressed declaratively
- **Middleware**: For cross-cutting concerns like rate limiting and security checks

## Migration Strategy

### Versioning

- All schema changes via migration files
- Migration naming: `YYYY_MM_DD_HHSS_description.php`
- Irreversible migrations clearly marked and documented
- Data migration scripts for complex transformations

### Data Seeding

- **Factory Classes**: Generate realistic test data
- **Seeder Classes**: Populate reference data (industries, product categories, etc.)
- **Development Seeds**: Larger datasets for performance testing
- **Production Seeds**: Minimal required data for system operation

### Backup and Recovery

- **Logical Backups**: `pg_dump` for schema and data
- **Physical Backups**: File system snapshots for point-in-time recovery
- **Export Formats**: CSV, JSON, XML for specific entity types
- **Recovery Testing**: Regular restore tests to verify procedures

## Performance Considerations

### Denormalization for Performance

- Selected fields denormalized for read-heavy scenarios:
  - `customers.balance`: Current account balance
  - `stocks.available_quantity`: Readily available stock
  - `invoices.total_amount`: Invoice total (avoids calculation)
  - `work_sessions.total_distance_m`: Pre-calculated distance
  - `visit_reports.submitted_at`: Submission timestamp for reporting

### Caching Strategies

- **Entity Caching**: Frequently accessed reference data (products, customers)
- **Query Caching**: Expensive aggregate queries (dashboards, reports)
- **Computed Values**: Expensive calculations with low change frequency
- **Session Data**: User session information in Redis
- **Template Caching**: Compiled Blade/Twig templates

### Partitioning Strategies

- **Time-Based**: Large tables partitioned by date (visits, payments, stock_movements)
- **Tenant-Based**: Consideration for multi-tenant partitioning at scale
- **Access Pattern-Based**: Hot/cold data separation based on usage frequency

## Security and Privacy

### Data Classification

- **Public**: Information suitable for public display (product names, general descriptions)
- **Internal**: Company-internal information (internal pricing, employee directories)
- **Confidential**: Personally identifiable information (PII), financial data
- **Restricted**: Highly sensitive data (bank account numbers, tax IDs, health information)

### Protection Measures

- **Encryption at Rest**:
  - Transparent Data Encryption (TDE) at database level
  - Application-level encryption for highly sensitive fields (SSN, bank accounts)
  - File-level encryption for stored attachments
- **Encryption in Transit**:
  - TLS 1.3 for all network communications
  - Certificate pinning for mobile applications
  - SSH for administrative access
- **Access Controls**:
  - Row-level security (RLS) for multi-tenant isolation
  - Column-level encryption for sensitive fields
  - Database roles and permissions principle of least privilege
  - Schema separation for different data classifications
- **Audit and Monitoring**:
  - Immutable audit trail for all sensitive operations
  - Data access logging for PII and financial data
  - Database activity monitoring for anomalous queries
  - Regular permission review and cleanup

### Retention and Disposal

- **Retention Policies**:
  - Transactional data: 7 years (financial regulations)
  - Customer data: 5 years after last activity or contract end
  - Employee data: 7 years after termination
  - Log data: 1 year (operational) or 7 years (security/audit)
  - Backup data: 30 days (daily), 12 weeks (weekly), 12 months (monthly)
- **Disposal Procedures**:
  - Secure deletion of electronic data (cryptographic erasure)
  - Physical destruction of storage media
  - Certification of destruction for compliance
  - Exception handling for legal holds and litigation

## Future Extensions

### Planned Entity Additions

- **marketing_campaigns**: Track marketing initiatives and ROI
- **sales_opportunities**: Sales pipeline management
- **service_contracts**: Ongoing service agreements with customers
- **work_orders**: Maintenance and repair work orders
- **purchase_orders**: Procurement and purchasing processes
- **expense_categories**: Configurable expense categorization
- **payment_gateways**: Integration with payment processors
- **shipping_carriers**: Integration with logistics providers
- **tax_authorities**: Integration with tax calculation services
- **loyalty_programs**: Customer loyalty and rewards programs
- **commission_plans**: Sales compensation structures
- **training_records**: Employee training and certification tracking
- **equipment**: Tracking of company-owned equipment
- **facilities**: Management of company facilities and properties
- **incidents**: Safety, security, and environmental incidents
- **documents**: Document management system
- **projects**: Cross-functional initiatives and projects

### Planned Relationship Enhancements

- **Many-to-many with attributes**:
  - Enrollments (users to training programs)
  - Memberships (customers to loyalty programs)
  - Assignments (users to projects or teams)
- **Hierarchical improvements**:
  - Materialized path for category hierarchies
  - Closure table for efficient tree queries
  - Adjacency list with path enumeration
- **Temporal tables**:
  - System-versioned tables for historical tracking
  - Application-time tables for forward-looking scheduling
  - Bitemporal tables for both system and application time

### Planned Indexing Improvements

- **Bloom filters**: For expensive existence checks
- **Covering indexes**: For frequent query patterns
- **Filtered indexes**: For sparse data scenarios
- **Expression indexes**: For computed values
- **Partition-wise indexes**: For partitioned tables
- **BRIN indexes**: For very large tables with natural ordering

### Planned Constraint Enhancements

- **Exclusion constraints**: For preventing overlapping reservations
- **Check constraints using functions**: For complex business rules
- **Foreign key actions**: For cascade and set-null behaviors
- **Deferrable constraints**: For transaction batching
- **Constraint exclusion**: For partitioning scenarios

This data model provides a solid foundation for the Jawla system while allowing for extensibility and evolution to meet future requirements. The design balances normalization for data integrity with selective denormalization for performance, and incorporates modern approaches to handling complex business relationships and temporal data.
