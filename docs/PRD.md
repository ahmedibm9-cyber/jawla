# Jawla (جولة) - Product Requirements Document

## Users

- **Field Representatives**: Sales reps, delivery personnel, service technicians who visit customers daily
- **Field Supervisors**: Team leads who oversee representative performance and approve activities
- **Operations Managers**: Mid-level managers responsible for regional performance and resource allocation
- **Sales Managers**: Responsible for sales targets, pricing, and customer assignments
- **Finance Team**: Handles collections, payments, and financial reconciliation
- **Warehouse Staff**: Manages inventory, stock transfers, and preparation of representative loads
- **Company Owners/Executives**: Strategic decision-makers requiring performance oversight
- **Customers**: Business clients who place orders and make payments (limited self-service portal)

## Problem Statement

Field sales operations in Egypt, Saudi Arabia, and other Arabic-speaking markets suffer from:

1. Limited visibility into representative activities and performance
2. Manual, error-prone processes for order taking, payment collection, and inventory management
3. Lack of real-time data leading to delayed decision-making
4. Inconsistent customer visit execution and missed sales opportunities
5. Poor inventory accuracy causing stockouts or overstock situations
6. Delayed payment collection and reconciliation processes
7. Language barriers in predominantly Arabic-speaking markets with need for English support
8. Limited offline capabilities disrupting field operations in areas with poor connectivity
9. Manual approval processes causing delays in order fulfillment and customer service
10. Difficulty tracking field expenses and ensuring policy compliance

## Outcomes & Success Metrics

Upon successful implementation, the system will achieve:

### Operational Efficiency

- 80% weekly active usage among licensed representatives within 6 months
- 90% of planned visits recorded digitally
- 95% successful synchronization of field records without support intervention
- Less than 1% duplicated financial transactions
- 50% reduction in average approval turnaround time
- 40% reduction in unresolved stock differences
- 30% increase in productive-visit rate (visits resulting in orders, collections, or other valuable outcomes)
- 25% increase in on-time task completion
- 60% reduction in manual daily-report preparation time

### Financial Impact

- Improved cash flow through faster collections and reduced DSO (Days Sales Outstanding)
- Decreased inventory carrying costs through better stock management
- Increased sales conversion rates through better visit planning and execution
- Reduced operational costs through automation of manual processes

### User Experience

- Representative satisfaction score of 4.0/5.0 or higher
- Manager satisfaction score of 4.0/5.0 or higher
- Adoption rate of 85% among target user groups within 3 months of rollout
- Positive feedback on bilingual (Arabic/English) interface usability

## Scope

### In Scope (MVP Phase)

1. **Core Field Operations**
   - Representative shift management (start/end shift)
   - GPS-based visit tracking with geofenced check-in/check-out
   - Visit reporting with summary, feedback, and action items
   - Digital signature capture for visit reports and invoices

2. **Customer & Product Management**
   - Customer profile management (Arabic/English names, contact info, locations)
   - Product catalog with pricing, barcodes, and categorization
   - Price lists and customer-specific pricing
   - Inventory management (warehouse and vehicle stock)

3. **Sales & Order Processing**
   - Proforma invoice creation and conversion to formal invoices
   - Sales order creation with pricing validation and discount controls
   - Order status tracking (draft, submitted, approved, fulfilled, delivered)
   - Price override workflows with approval requirements

4. **Financial Transactions**
   - Payment collection (cash, check, bank transfer, digital wallet)
   - Payment reconciliation with invoice matching
   - Cash box management and reconciliation
   - Basic financial reporting (sales, collections, outstanding receivables)

5. **Inventory Management**
   - Stock transfers between warehouse and representative vehicles
   - Stock adjustments (damage, loss, correction)
   - Basic stock level tracking and alerts
   - Stock counting procedures (scheduled and surprise counts)

6. **Approval Workflows**
   - Configurable approval chains for orders, discounts, and exceptions
   - Role-based approval routing (supervisor → sales manager → finance)
   - Audit trail for all approval decisions
   - Escalation procedures for overdue approvals

7. **Offline-First Capabilities**
   - Local data storage for continued operation without connectivity
   - Queued synchronization with conflict resolution
   - Offline-capable core functions (visit reporting, order creation, payment collection)
   - Synchronization status visibility and manual retry options

8. **Reporting & Analytics**
   - Representative performance dashboards (visits, orders, collections)
   - Managerial oversight tools (team performance, visit compliance)
   - Financial reports (sales trends, collection efficiency, receivables aging)
   - Inventory reports (stock levels, movement, variances)
   - Export capabilities (PDF, Excel/CSV)

9. **Integration Foundation**
   - RESTful API for external system integration
   - Webhook framework for event notifications
   - Data import/export capabilities (CSV/Excel)
   - Authentication and authorization framework (API keys, OAuth ready)

### Out of Scope (Future Phases)

1. **Advanced Financial Management**
   - Full general ledger accounting
   - Accounts payable and vendor management
   - Fixed asset management
   - Complex tax calculations and multi-jurisdiction compliance

2. **Advanced HR & Payroll**
   - Employee time tracking and attendance
   - Payroll processing and tax deductions
   - Benefits administration
   - Performance management and compensation planning

3. **Manufacturing & Production**
   - Bill of materials management
   - Production planning and scheduling
   - Quality control and traceability
   - Work order management

4. **Advanced CRM & Marketing**
   - Marketing campaign management
   - Lead generation and nurturing
   - Customer segmentation and scoring
   - Loyalty program management

5. **Specialized Industry Features**
   - Pharmaceutical serialization and compliance
   - Food and beverage traceability (lot tracking, expiration)
   - Hazardous materials handling and reporting
   - Equipment maintenance and service contracts

6. **Advanced Technologies (Future Phases)**
   - AI-powered visit risk scoring and recommendations
   - Predictive inventory replenishment
   - Automated route optimization
   - Computer vision for product recognition and shelf analysis
   - IoT integration for smart vehicle monitoring

## Success Measures

### Quantitative Metrics

| Metric                                 | Target                 | Measurement Method            |
| -------------------------------------- | ---------------------- | ----------------------------- |
| Weekly Active Users (WAU)              | ≥80% of licensed users | System analytics              |
| Planned Visit Completion Rate          | ≥90%                   | Visit records vs. assignments |
| Synchronization Success Rate           | ≥95%                   | Sync log analysis             |
| Financial Transaction Duplication Rate | <1%                    | Audit trail review            |
| Average Approval Turnaround            | ≤4 hours               | Timestamp analysis            |
| Stock Variance Reduction               | ≥40%                   | Physical count vs system      |
| Productive Visit Rate                  | ≥35%                   | Visit outcome analysis        |
| On-time Task Completion                | ≥75%                   | Task deadline tracking        |
| Manual Report Reduction                | ≥60%                   | User survey & time tracking   |

### Qualitative Metrics

| Measure                     | Target              | Assessment Method                    |
| --------------------------- | ------------------- | ------------------------------------ |
| Representative Satisfaction | ≥4.0/5.0            | Quarterly surveys                    |
| Manager Satisfaction        | ≥4.0/5.0            | Quarterly surveys                    |
| System Usability Score      | ≥80                 | Standardized usability testing       |
| Language Quality (AR/EN)    | Native-level        | Linguistic review                    |
| Offline Reliability         | Seamless transition | Field testing in varied connectivity |
| Support Ticket Volume       | Decreasing trend    | Support system analytics             |
