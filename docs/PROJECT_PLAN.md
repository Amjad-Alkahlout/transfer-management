# Money Transfer & Capital Management System

## Project Goal

A production-ready internal system used daily to manage money transfers
between UAE and Gaza.

The system focuses on:

- Customer Transfers
- Capital Management
- Payment Tracking
- Reporting
- Employee Workflow

---

# System Workflow

Customer visits UAE office

↓

Coordinator creates transfer request

↓

Executor reviews request

↓

Executor sets:

- Exchange rate
- Commission
- Source bank account

↓

Transfer waits for approval

↓

Coordinator approves or rejects

↓

If approved

↓

Executor uploads transfer proof

↓

Transfer becomes completed

↓

Customer payment is tracked separately.

---

# Completed

## Authentication

- Login
- Logout

## Users

- Roles
- Active status

## Bank Accounts

- Create
- List
- Activate
- Deactivate

## Transfer

- Create
- Receiver Method
- Pricing
- Approval
- Cancellation
- Execution
- Upload Proof

---

# Current Database

Users

Bank Accounts

Transfers

Enums

---

# Phase 2

## Payment System ⭐⭐⭐⭐⭐

Transfer Status

- Pending Pricing
- Awaiting Approval
- Approved
- Completed
- Cancelled

Payment Status

- Unpaid
- Partial
- Paid

Features

- Register payment
- Remaining balance
- Payment history

---

## Capital Management ⭐⭐⭐⭐⭐

Purpose

Manage company liquidity.

Capital always moves:

UAE

↓

Gaza

using a selected USD bank account.

Transfers decrease Gaza liquidity.

Customer payments increase UAE liquidity.

When enough capital accumulates again in UAE,
a new capital transfer is sent to Gaza.

Features

Capital Transfer

- Source Bank Account
- Amount
- Currency (USD)
- Date
- Notes
- Created By

Capital Balance

- UAE Balance
- Gaza Balance

History

All capital movements.

---

## Dashboard

Cards

Pending Pricing

Awaiting Approval

Completed Today

Outstanding Payments

Current UAE Capital

Current Gaza Capital

Today's Commission

---

## Reports

Transfers

Capital

Payments

Commissions

Currencies

Employee Performance

Outstanding Balances

---

## Roles

Coordinator

- Create Transfer
- Edit Transfer
- View Transfers
- Approve
- Cancel
- Register Customer Payment

Executor

- View Transfers
- Price Transfer
- Select Bank Account
- Upload Proof
- Execute Transfer

---

## Business Rules

Transfer cannot be approved before pricing.

Transfer cannot be executed before approval.

Completed transfers cannot be edited.

Proof image is required before completion.

Only active bank accounts may be selected.

Customer payment is independent from transfer completion.

Capital transfers always use a company USD bank account.

---

# Future

Exchange Rate API

Notifications

Audit Log

Printing

Export Excel/PDF

Advanced Search

Statistics

Backups

# Sprint 1 - Transfer Module ✅

## Completed

- [x] Create Transfer
- [x] List Transfers
- [x] View Transfer Details
- [x] Price Transfer
- [x] Approve Transfer
- [x] Cancel Transfer
- [x] Execute Transfer
- [x] Upload Transfer Proof
- [x] Edit Transfer
- [x] Workflow Protection
- [x] Receiver Method (Bank / Wallet)
- [x] Transfer Reference Number

---

## Business Rules Implemented

- Only Pending Pricing transfers can be priced.
- Only Awaiting Approval transfers can be approved.
- Only Approved transfers can be completed.
- Only Pending Pricing and Awaiting Approval transfers can be edited.
- Editing an Awaiting Approval transfer resets all pricing information.
- Transfer proof is required before completion.
- Only active bank accounts can be selected during pricing.

---

Status

✅ COMPLETED

