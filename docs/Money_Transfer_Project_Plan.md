# Money Transfer & Capital Management System

## Project Goal

A production-ready internal system for managing money transfers between
**UAE** and **Gaza**.

The system is designed to manage:

-   Customer Transfers
-   Currency Conversion
-   Commission Management
-   Customer Payments
-   Capital Management
-   Profit Tracking
-   Internal Capital Transfers
-   Reporting
-   Employee Workflow

------------------------------------------------------------------------

# Current Workflow

Customer requests a transfer.

↓

Coordinator creates the transfer.

↓

System automatically calculates:

-   Transfer Amount
-   Customer Payable Amount
-   Commission
-   Exchange Rate Conversion

↓

Customer may pay: - Fully - Partially - Later

↓

Transfer remains Pending.

↓

Executor completes the transfer.

↓

Transfer proof is uploaded.

↓

Capital and profit are updated automatically.

------------------------------------------------------------------------

# Completed

## Authentication

-   [x] Login
-   [x] Logout

## Authorization

-   [ ] Gates
-   [ ] Policies
-   [ ] Role-based permissions

## Users

-   [x] Roles
-   [x] Active Status

## Bank Accounts

-   [x] Create
-   [x] List
-   [x] Activate
-   [x] Deactivate

## Exchange Rates

-   [x] Exchange Rate Management
-   [x] CurrencyConverterService
-   [x] Automatic Currency Conversion

## Commission Rules

-   [x] Range-based Commission Rules
-   [x] Automatic Commission Selection
-   [x] CommissionService

## Transfer Module

-   [x] Create Transfer
-   [x] Edit Transfer
-   [x] Cancel Transfer
-   [x] Execute Transfer
-   [x] Upload Proof
-   [x] Receiver Method (Bank / Wallet)
-   [x] Reference Number
-   [x] Automatic Calculations
-   [x] Included / Excluded Fee Mode
-   [x] Customer Payable Calculation
-   [x] Transfer Amount Calculation
-   [x] Commission Snapshot

## Payment Module

-   [x] Initial Payment
-   [x] Partial Payments
-   [x] Multiple Payments
-   [x] Remaining Balance
-   [x] Payment History
-   [x] Automatic Payment Status

## Dashboard

-   [x] Transfer Statistics
-   [x] Payment Statistics
-   [x] Exchange Rates Summary
-   [x] Commission Summary
-   [x] Quick Actions

------------------------------------------------------------------------

# Business Rules Implemented

-   Customer payment is independent from transfer execution.
-   Customer may pay before or after transfer execution.
-   Multiple customer payments are supported.
-   Commission is selected automatically based on transfer amount.
-   Currency conversion is calculated automatically.
-   Included commission decreases the actual transfer amount.
-   Excluded commission increases the amount payable by the customer.
-   Transfers cannot be edited after customer payments begin.
-   Proof is required before completing a transfer.

------------------------------------------------------------------------

# Current Database

-   Users
-   Transfers
-   Payments
-   Bank Accounts
-   Exchange Rates
-   Commission Rules

------------------------------------------------------------------------

# Current Sprint

## Financial Module

### Capital Accounts

-   [ ] Create Capital Accounts
-   [ ] Branch Management
-   [ ] Capital Balance
-   [ ] Account Activation

### Capital Ledger

-   [ ] Capital Transactions
-   [ ] Transaction History
-   [ ] Audit Trail
-   [ ] Balance Before / After

### Internal Capital Transfers

-   [ ] Transfer Between Branches
-   [ ] Automatic Currency Conversion
-   [ ] Transfer Costs
-   [ ] Automatic Balance Updates
-   [ ] Ledger Entries

### Profit Ledger

-   [ ] Customer Commission Tracking
-   [ ] Daily Profit
-   [ ] Monthly Profit
-   [ ] Profit History

### Financial Automation

When a customer transfer is completed:

-   Decrease Gaza Capital.
-   Register company profit.
-   Increase UAE Capital.
-   Create ledger transactions automatically.

------------------------------------------------------------------------

# Reports

## Transfers

-   Transfer Report
-   Outstanding Transfers

## Payments

-   Payment Report
-   Outstanding Balances

## Capital

-   Capital Accounts
-   Capital Transfers
-   Capital Ledger

## Profit

-   Profit Report
-   Commission Report

## Employees

-   Employee Performance

------------------------------------------------------------------------

# Dashboard (Next Version)

-   Pending Transfers
-   Completed Today
-   Outstanding Payments
-   UAE Capital
-   Gaza Capital
-   Today's Profit
-   Internal Transfers
-   Today's Expenses

------------------------------------------------------------------------

# Future Enhancements

-   Exchange Rate API Integration
-   Notifications
-   Audit Log
-   Export Excel
-   Export PDF
-   Printing
-   Advanced Search
-   Activity Timeline
-   Scheduled Reports
-   Backups
