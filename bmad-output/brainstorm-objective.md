# Brainstorm Objective

## Topic
Audit all UI components across Jawla PWA (Rep App) and Admin Panel (Filament) to identify missing UI control modules, interactions, and patterns that should exist but don't.

## Context
Jawla is a bilingual (AR/EN) field-sales CRM/ERP PWA. The app has:
- **14 Rep PWA pages** (Livewire): Home, Visit Flow, Customers, Add Customer, Stock Search, Sales Flow, Quotation Flow, Collect Payment, Log Return, Log Expense, Log Complaint, Purchase Offer, More
- **22 Admin Panel pages** (Filament): Dashboard, Reports, Activity Log, Collect Payment, Tasks, Alarms, Complaints, Companies, Users, Customers, Daily Visit Assignments, Invoices, Payments, Price Quotation Requests, Proforma Invoices, Purchase Requests, Return Records, Routes, Expenses, Products, Stocks, Van Transfers
- **Shared components**: Tab Bar, Page Header, Success Screen, Form Input System

## Constraints
- Plan only — no implementation
- Focus on missing controls, not visual polish
- Must be actionable and specific per page
- Consider: loading states, empty states, error states, confirmations, inline editing, filtering, sorting, bulk actions, real-time updates, offline behavior

## Techniques Selected
1. **Mind Mapping** — Organize all existing vs. missing UI controls by page
2. **SCAMPER** — Generate variations for each page's missing interactions
3. **Reverse Brainstorming** — What would make the UI fail? What's missing that causes friction?
4. **Starbursting** — Systematic questions about each page's completeness
