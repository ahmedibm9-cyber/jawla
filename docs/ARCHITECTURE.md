# Jawla (جولة) - Architecture

## System Overview

Jawla is a bilingual (Arabic/English) field sales automation platform built as a modular monolith using Laravel 13. The system consists of:

- **Admin Panel**: Built with Filament 3, accessible at `/admin`
- **Field Representative PWA**: Built with Livewire 3 and Blade, accessible at `/app`
- **Shared Services**: Business logic encapsulated in service classes
- **Data Layer**: Eloquent ORM with PostgreSQL
- **Infrastructure**: Deployed on Railway with PostgreSQL and S3-compatible storage

## Core Architectural Principles

1. **Separation of Concerns**: Clear division between presentation, application, and data layers
2. **Service Layer Pattern**: All business logic resides in `app/Services/` - controllers and components delegate to services
3. **Tenancy Awareness**: Multi-tenancy enforced at the model level with automatic `company_id` scoping
4. **Offline-First Design**: Field operations designed to work intermittently connected environments
5. **Event-Driven Extensibility**: Loose coupling through Laravel events and observers
6. **Security by Design**: Defense-in-depth with multiple validation layers

## Key Boundaries

### Trust Boundaries

- **Browser ↔ Laravel**: All authorization decisions made server-side; client state never trusted for access control
- **Service Worker → Public Cache Only**: Service worker caches only public assets; never caches authenticated responses or user data
- **Browser IndexedDB → Sync Endpoint**: Outbox encrypted and partitioned by HMAC-derived identity; cleared on logout
- **Sync Endpoint → Database**: Each operation scoped to company and recorded with idempotency receipt in same transaction
- **Laravel → External Services**: All external calls treated as potentially failing; financial/stock mutations never appear successful on external failure

### Service Boundaries

- **HTTP Layer**: Routes, middleware, request validation
- **Service Layer**: Business logic, transaction boundaries, validation
- **Data Layer**: Eloquent models, relationships, scopes
- **Infrastructure Layer**: Database, queue, cache, storage, external APIs

## Current Architecture Layers

### Presentation Layer

- **Admin Panel** (`/admin`): Filament 3 PHP templates
- **Field PWA** (`/app`): Livewire 3 components with Blade views
- **API Endpoints** (`/api/v1/*`): RESTful JSON resources
- **Static Assets**: Compiled with Vite (JS, CSS, images)

### Application Layer

- **Service Classes**: All business logic in `app/Services/`
  - Transaction management (`DB::transaction`)
  - Validation and business rule enforcement
  - Event dispatching
  - External service integration
- **Events & Listeners**: Decoupled communication between components
- **Jobs & Queues**: Background processing for non-time-sensitive operations
- **Notifications**: Multi-channel alerting system (database, mail, broadcast)

### Data Layer

- **Eloquent Models**: Database table representations with relationships
- **Model Traits**: Shared functionality (BelongsToCompany, SoftDeletes, etc.)
- **Database Services**: Read replicas, connection pooling, transaction handling
- **Cache Layer**: Redis for frequently accessed data, session storage

### Infrastructure Layer

- **Database**: PostgreSQL 16 with read replicas
- **Object Storage**: S3-compatible (Railway) for attachments and backups
- **Queue System**: Database-backed with horizontal worker scaling
- **Cache System**: Redis for application caching and session storage
- **Search**: Database-native full-text search (planned migration to Elasticsearch/TNTSearch)
- **Observability**: Logging, metrics, tracing, health checks

## Data Flow Patterns

### Synchronous Request Flow

1. HTTP request enters through web/api routes
2. Middleware applies (authentication, company context, throttling)
3. Controller/Form Request validates input
4. Controller delegates to Service class
5. Service executes business logic
6. Service begins database transaction
7. Service validates business rules and permissions
8. Service performs data operations through Eloquent models
9. Service dispatches domain events
10. Service commits transaction
11. Controller returns response (view, redirect, JSON)

### Asynchronous Processing Flow

1. Service dispatches event or queues job
2. Worker processes job from queue
3. Job executes within its own transaction scope
4. Job may dispatch further events or trigger notifications
5. Results stored or communicated via appropriate channels

### Offline Synchronization Flow

1. User performs action while offline
2. Action recorded in IndexedDB outbox with metadata
3. On connectivity restoration:
   - Outbox sorted by dependency
   - Batched POST to `/app/sync` endpoint
   - Server validates authenticity and company scope
   - Each operation processed in individual transaction
   - Idempotency checked via `(company_id, idempotency_key)` constraint
   - Successful operations stored with response for replay
   - Failed operations retained in outbox with error status
4. UI updates to reflect synchronization status
5. Conflicts resolved per conflict resolution policy

## Domain-Specific Architectures

### Financial Transactions Architecture

- **Atomicity**: All money movements in database transactions
- **Immutability**: Approved transactions never modified; reversed via compensating entries
- **Audit Trail**: Every financial change linked to user, timestamp, and justification
- **Validation Layers**:
  - Service layer: Business rule validation
  - Database constraints: Referential integrity, check constraints
  - Application state: Periodic reconciliation processes
- **Extensions**:
  - Multi-currency support (planned)
  - Complex tax engines (planned)
  - Payment gateway abstractions (planned)

### Inventory Management Architecture

- **Stock Tracking**:
  - Primary table: `stocks` (warehouse_id, product_id, batch_id → quantity)
  - Movements table: `stock_movements` (all changes with reasons and references)
- **Reservation System**:
  - Separate tracking of allocated vs available quantities
  - Automatic reservation on order approval
  - Release on order cancellation or modification
- **Batch/Lot Tracking**:
  - Optional batch tracking per product
  - Expiration date management and FEFO/FIFO enforcement
  - Quality status tracking
- **Location Types**:
  - Warehouses (fixed locations)
  - Vehicles (mobile locations)
  - Consignment (customer premises)
  - Quarantine/Returns processing
  - Damage/Defect isolation

### Approval Workflow Architecture

- **Workflow Engine**:
  - Flexible workflow definition (sequential, parallel, conditional)
  - Runtime workflow instantiation from templates
  - Persistent state tracking for long-running workflows
- **Execution Engine**:
  - Atomic step execution with compensation capability
  - Timeout and escalation mechanisms
  - Delegation and substitution support
  - Audit trail for all decisions and actions
- **Integration Points**:
  - Entity lifecycle events trigger workflow instantiation
  - Manual initiation for ad-hoc approvals
  - Scheduled triggers for periodic reviews
  - Webhook notifications for external systems

## Planned Architectural Enhancements (Addressing Gaps)

### 1. Bluetooth Printing Integration

**Current State**: PDF generation exists via `PdfService` but no direct printer communication  
**Planned Enhancement**:

- **Hardware Abstraction Layer**: Printer interface in `app/Services/Contracts/PrintService.php`
- **Platform Implementations**:
  - Web Print API (for modern browsers)
  - Bluetooth Serial (for Cordova/Capacitor mobile apps)
  - ESC/POS Command Generator (for thermal printers)
- **Discovery Service**:
  - Bluetooth device scanning and pairing
  - Printer capability detection
  - Default printer selection
- **Print Job Queue**:
  - Prioritization and retry mechanisms
  - Status tracking and error reporting
  - Preview and confirmation before printing

### 2. Customer Statement Generation

**Current State**: Basic balance and transaction views exist but no formal statement generation  
**Planned Enhancement**:

- **Statement Service**:
  - `StatementService` for generating period-based customer statements
  - Template engine for customizable statement layouts
  - Multiple format outputs (PDF, HTML, CSV, XML)
- **Statement Types**:
  - Periodic statements (monthly/quarterly)
  - On-demand statements
  - Consignment statements
  - Tax documentation statements
- **Delivery Mechanisms**:
  - Portal download
  - Email delivery with tracking
  - Secure messaging (WhatsApp Business)
  - Electronic data interchange (EDI) formats

### 3. Advanced Batch/Expiry Tracking

**Current State**: Basic batch fields exist in migrations but limited service implementation  
**Planned Enhancement**:

- **Enhanced Batch Model**:
  - Production and expiration dates
  - Quality test results and certifications
  - Origin and supplier traceability
  - Memory items (temperature history, handling conditions)
- **Tracking Strategies**:
  - Lot-based tracking for bulk materials
  - Serial number tracking for high-value items
  - Hybrid approaches for complex products
- **FEFO/FIFO Enforcement**:
  - Automatic batch selection based on expiry dates
  - Configurable policies per product/category
  - Override capabilities with justification requirements
- **Expiration Management**:
  - Proactive expiration alerts
  - Automated quarantine of expired items
  - Return-to-vendor or disposal workflows
  - Donation or repurposing options for near-expiry

### 4. Commission and Bonus Systems

**Current State**: Basic attainment tracking exists in `AttainmentService` but no compensation calculation  
**Planned Enhancement**:

- **Compensation Service**:
  - `CommissionService` for calculating earnings based on performance
  - Multiple compensation plan types (salary + commission, pure commission, tiered, etc.)
  - Complex rule engines for bonuses and spiffs
- **Performance Metrics**:
  - Configurable KPIs for compensation calculation
  - Temporal aggregation (daily, weekly, monthly, quarterly, yearly)
  - Team and individual performance combinations
  - Adjustments for territory difficulty, market conditions, etc.
- **Payment Integration**:
  - Integration with payroll systems
  - Special payment runs for commissions/bonuses
  - Tax withholding and reporting
  - Statement generation for earnings detail
- **Plan Management**:
  - Versioned compensation plans
  - Grandfathering and transition rules
  - Plan modeling and forecasting capabilities
  - A/B testing for compensation structures

### 5. Customer Self-Service Portal

**Current State**: No customer-facing portal exists  
**Planned Implementation**:

- **Customer Portal**:
  - Secure authentication (email/password, SSO options)
  - Account overview (balance, invoices, payment history)
  - Self-service capabilities:
    - Invoice viewing and download
    - Payment submission and tracking
    - Dispute initiation and tracking
    - Contact information updates
    - Communication preferences management
  - Document library (contracts, statements, certificates)
  - Announcement and messaging center
- **Integration Points**:
  - Read-only access to relevant customer data
  - Webhook notifications for account events
  - API access for mobile app companions
  - Single sign-on (SSO) capabilities
- **Security Considerations**:
  - Strict data isolation between customers
  - Session management and timeout policies
  - Audit trail of all customer actions
  - Consent management for communications and data usage

### 6. Advanced Regional Tax Integrations

**Current State**: Basic VAT handling exists but no specialized tax service connectors  
**Planned Enhancement**:

- **Tax Service Abstraction**:
  - `TaxService` interface for pluggable tax providers
  - Support for major tax calculation services (Avalara, Vertex, Thomson Reuters)
  - Local tax authority integrations where available
  - Fallback to manual rate tables for simple jurisdictions
- **Tax Calculation Features**:
  - Real-time tax determination at point of sale
  - Location-based tax rules (origin vs destination)
  - Product taxability matrices
  - Customer exemption certificate management
  - Special handling for holidays, thresholds, and caps
- **Compliance and Reporting**:
  - Automated tax return preparation
  - Electronic filing capabilities
  - Audit trail and documentation generation
  - Nexus tracking and registration alerts
- **Tax Inclusive/Exclusive Handling**:
  - Customer preference storage and application
  - Clear display of tax-inclusive vs exclusive pricing
  - Proper rounding and allocation procedures
  - Reverse calculation from total to net amounts

### 7. AI-Powered Features

**Current State**: Basic analytics exist but no machine learning or AI capabilities  
**Planned Enhancement**:

- **Analytics Foundation**:
  - Feature store for cleaned, engineered attributes
  - Model training pipeline with versioning
  - A/B testing framework for model validation
  - Monitoring for model drift and performance degradation
- **Predictive Models**:
  - **Visit Risk Scoring**: Probability of successful outcome based on history, timing, rep performance, customer attributes
  - **Inventory Demand Forecasting**: Time series forecasting for stock replenishment
  - **Payment Default Probability**: Likelihood of late or non-payment based on customer and economic factors
  - **Optimal Next Action**: Recommendation engine for next best sales or service action
  - **Route Optimization**: Traveling salesman problem solutions with time windows
- **Intelligent Assistants**:
  - Natural language interface for common operations
  - Guided selling scripts based on customer profile and history
  - Automated follow-up suggestion generation
  - Anomaly detection for unusual patterns requiring human review
- **Implementation Approach**:
  - Python-based ML services communicating via API
  - Feature extraction pipelines from operational data
  - Model registry and deployment automation
  - Feedback loops for continuous improvement
  - Explainability features for transparency and trust

## Security Architecture Updates

### Enhanced Authentication

- **Multi-Factor Authentication**:
  - TOTP (Google Authenticator, Authy) support
  - SMS and email-based one-time codes
  - Hardware token compatibility (YubiKey, etc.)
  - Adaptive authentication based on risk factors
- **Session Management**:
  - Device fingerprinting and binding
  - Geographic and IP-based access controls
  - Concurrent session limits with intelligent conflict resolution
  - Automatic logout on suspicious activity detection

### Data Protection Enhancements

- **Field-Level Encryption**:
  - Sensitive PII (national ID, bank account numbers) encrypted at rest
  - Automatic encryption/decryption in model accessors/mutators
  - Key management through secure vault (HashiCorp Vault or AWS KMS)
- **Dynamic Data Masking**:
  - Role-based data masking in non-production environments
  - Real-time masking for read-only access scenarios
  - Consistent masking algorithms for referential integrity
- **Secrets Management**:
  - Centralized secrets storage
  - Automatic rotation and versioning
  - Environment-specific secret injection
  - Audit trail of secret access and usage

### API Security Improvements

- **Rate Limiting and Quotas**:
  - Per-client and per-endpoint limits
  - Burst allowance with sustained rate limits
  - Adaptive throttling based on system load
  - Geographic and behavioral anomaly detection
- **Input Validation Enhancements**:
  - Comprehensive schema validation (JSON Schema or similar)
  - Context-aware validation (business rule integration)
  - XML and SOAP attack prevention (where applicable)
  - Deserialization security for object-based APIs
- **Output Protection**:
  - Context-aware encoding (HTML, JS, URL, CSS)
  - JSON hijacking protection
  - Information leakage prevention through careful error messaging

## Resilience and Scalability Enhancements

### Improved Caching Strategy

- **Multi-Level Caching**:
  - CDN for static assets (global edge distribution)
  - Application Redis for frequently accessed computed data
  - Database query result caching for deterministic results
  - HTTP caching headers for client-side reuse
  - Local-first caching for mobile offline scenarios
- **Intelligent Cache Warming**:
  - Predictive loading based on usage patterns and time of day
  - Pre-computation of expensive aggregations during off-peak
  - Event-based cache population for related data updates
  - Geographic distribution alignment for edge caching

### Database Scaling Approaches

- **Read Scaling**:
  - Read replicas for distributing query load
  - Geographic distribution of replicas for local access
  - Load balancing and connection pooling
  - Query routing based on data locality and affinity
- **Write Scaling**:
  - Partitioning strategies for high-volume tables
  - Event sourcing for append-heavy workloads (financial ledgers, audit trails)
  - CQRS (Command Query Responsibility Segregation) for read/write separation
  - Microservice extraction for bounded contexts with high independent scaling needs
- **Multi-Tenant Optimization**:
  - Tenant-aware indexing strategies
  - Resource allocation and quality of service tiers
  - Cross-tenant noisy neighbor protection
  - Efficient tenant provisioning and deprovisioning

### Failure Mode Improvements

- **Circuit Breaker Pattern**:
  - Protection against cascading failures from external dependencies
  - Automatic failover to degraded modes or cached data
  - Gradual recovery testing and restoration
  - Metrics and alerting on circuit state transitions
- **Bulkhead Isolation**:
  - Resource allocation limits per service or tenant
  - Queue isolation to prevent head-of-line blocking
  - Thread and connection pool limits
  - Memory and CPU limits per processing unit
- **Graceful Degradation**:
  - Feature toggles for non-critical functionality
  - Service-level agreements with clear degradation paths
  - User communication during reduced capacity modes
  - Data consistency modes (strong vs eventual) based on operation criticality

## Observability and Monitoring

### Enhanced Logging

- **Structured Logging**:
  - Consistent JSON logging across all services
  - Correlation IDs for request tracing
  - Structured context for machine analysis
  - Sampling strategies for high-volume scenarios
- **Log Levels and Categories**:
  - Trace: Extremely detailed diagnostic information
  - Debug: Developer-focused debugging information
  - Info: General operational information
  - Warn: Potential issues requiring attention
  - Error: Actual errors requiring intervention
  - Fatal: Critical errors causing service termination
- **Specialized Log Types**:
  - Audit logs: Immutable record of significant actions
  - Security logs: Authentication, authorization, and security events
  - Performance logs: Timing and resource utilization data
  - Business logs: Domain-specific events and metrics

### Metrics and Tracing

- **Application Metrics**:
  - Business KPIs (conversion rates, order values, etc.)
  - System health (response times, error rates, throughput)
  - Resource utilization (CPU, memory, disk, network)
  - Queue depths and processing latencies
  - Cache hit ratios and effectiveness
- **Distributed Tracing**:
  - End-to-end request tracking across services
  - Span attributes for contextual information
  - Performance bottleneck identification
  - Error propagation and root cause analysis
  - Integration with third-party APM tools
- **Health Checks**:
  - Liveness: Is the application running?
  - Readiness: Is the application ready to serve traffic?
  - Startup: Has the application completed initialization?
  - Dependency: Are critical dependencies available?
  - Business Logic: Are core functions working correctly?

## Deployment and Operations

### Deployment Strategy

- **Infrastructure as Code**:
  - Declarative infrastructure definitions
  - Version-controlled environment configurations
  - Automated provisioning and deprovisioning
  - Drift detection and correction
- **Deployment Patterns**:
  - Blue-green deployment for zero-downtime releases
  - Canary release for risk mitigation
  - Feature flags for gradual rollout and testing
  - Rolling updates for scalable components
- **Rollback Procedures**:
  - Point-in-time database recovery
  - Immutable infrastructure replacement
  - Feature flag toggles for instant rollback
  - Coordinated service rollback for microservices

### Environment Management

- **Environment Hierarchy**:
  - Development: Individual developer sandboxes
  - Testing: Shared integration testing environment
  - Staging: Production mirror for final validation
  - Production: Live customer-facing system
  - Experiments: Isolated environments for A/B testing and spikes
- **Configuration Management**:
  - Environment-specific configuration inheritance
  - Secret management separate from configuration
  - Feature flags for runtime behavior control
  - Configuration validation and drift detection

## Technology Decisions and Rationales

### Chosen Technologies

- **Laravel 13**:
  - Mature PHP ecosystem with excellent documentation
  - Eloquent ORM for productive database interaction
  - Built-in authentication, authorization, and validation
  - Strong testing support and community packages
  - Laravel Octane for high-performance scenarios
- **Filament 3**:
  - Rapid admin panel development
  - Excellent customization and extension capabilities
  - Built-in accessibility and responsiveness
  - Active development and community support
- **Livewire 3**:
  - Minimal JavaScript requirement for dynamic interfaces
  - Server-side state management reducing client-side complexity
  - Excellent Laravel integration
  - Good performance characteristics
- **PostgreSQL**:
  - Advanced features (JSONB, geographic types, window functions)
  - Strong ACID compliance and data integrity
  - Excellent performance and scalability
  - Rich extension ecosystem (PostGIS, TimescaleDB, etc.)
- **Vue.js 3** (for future SPA components):
  - Progressive framework adoption
  - Excellent performance and bundle size
  - Strong TypeScript support
  - Vibrant ecosystem and tooling

### Rejected Alternatives and Reasons

- **Microservices Architecture**:
  - Rejected for MVP due to operational complexity
  - Data consistency challenges for financial transactions
  - Team expertise and development velocity considerations
  - Planned for future evolution as system scales
- **Node.js/Express Backend**:
  - Rejected due to team expertise in PHP/Laravel
  - Ecosystem maturity for business applications
  - Performance comparable with PHP 8+ and Swoole/Octane
  - Rich Laravel ecosystem for rapid development
- **MongoDB NoSQL Database**:
  - Rejected due to transaction requirements for financial data
  - Consistency guarantees critical for business operations
  - Relational nature of business data (customers, orders, products)
  - PostgreSQL JSONB provides document storage when needed
- **Single Page Application (SPA) for Admin**:
  - Rejected due to SEO requirements for documentation/pages
  - Initial development velocity with traditional server-side rendering
  - Hybrid approach planned (SPA for complex widgets, traditional for forms)
  - TurboDrive and Stimulus considerations evaluated but not selected

## Evolution Path

### Near-Term (0-6 months)

- Complete core MVP functionality per PRD and SPEC
- Establish solid foundation with current architecture
- Implement basic monitoring and observability
- Create deployment and operational procedures
- Conduct security audit and penetration testing

### Medium-Term (6-18 months)

- Implement planned architectural enhancements (Bluetooth printing, statements, etc.)
- Enhance scalability features (caching, read replicas, etc.)
- Develop advanced reporting and analytics capabilities
- Implement initial AI/ML features for predictive analytics
- Expand integration capabilities with major ERP/accounting systems

### Long-Term (18+ months)

- Consider selective microservice extraction for high-scale boundaries
- Advanced AI/ML integration for prescriptive recommendations
- Enhanced internationalization and localization capabilities
- Advanced compliance and regulatory features for global expansion
- Platform extensibility through marketplace and plugin architecture

This architecture document provides a comprehensive view of the current system state and planned evolution to address the identified gaps while maintaining core architectural principles and ensuring system integrity, security, and scalability.
