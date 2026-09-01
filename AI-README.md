# Dental Practice Management System

This is a custom dental clinic management system built for local clinic use, with support for both local offline operation and online/local-network access.

## Overview

The system helps a dental clinic manage:

- patients
- daily queue
- appointments
- treatment records
- payment plans and installment tracking
- outstanding debt
- inventory and purchase orders
- vendor management
- expenses and cash flow
- WhatsApp reminders for due or overdue installments

## Working Mode

### Offline / local use
The app is designed to run in a clinic environment using a local server or local machine setup. This makes it suitable for daily clinic work without depending on a public cloud service.

### Online / local network use
The same system can also be used over a local network or internet-connected environment when the clinic wants shared access from multiple devices.

In short: the system supports both local/offline usage and online/local-network usage depending on how the clinic deploys it.

## Main Features

### Patient management
- add, edit, and manage patient records
- phone numbers with WhatsApp-friendly links
- appointment dates
- medical notes
- outstanding debt tracking

### Queue system
- daily patient queue management
- walk-in support
- quick patient handling and filtering

### Payment plans
- installment-based plans
- due and overdue installment tracking
- WhatsApp reminder links for customers
- payment recording and repayment tracking

### Inventory and purchasing
- stock items
- stock movement tracking
- supplier/vendor records
- purchase orders and receiving workflow

### Finance
- cash flow overview
- expense tracking
- dashboard summaries for revenue and debt

## Customer Reminder Feature

The system includes a WhatsApp reminder feature for payment plans.

When a payment installment is due or overdue, the receptionist or admin can open the reminder from the payment plan screen and send a prewritten WhatsApp message to the patient.

This is a frontend reminder action and does not require a backend notification service unless the clinic chooses to add one later.

## Languages

The system supports multiple languages, including:

- English
- Kurdish
- Arabic

The interface also supports RTL layout for Arabic and Kurdish languages.

## Tech Stack

- Laravel backend
- Vue.js frontend
- MySQL database
- Vite build tooling
- Tailwind CSS

## Deployment Notes

This project can be deployed in a clinic environment in either of these ways:

1. local single-machine installation
2. local network server installation
3. online hosted deployment if needed

For most dental clinics, the local or local-network setup is the most practical option.

## Recommended Use

This system is suitable for:

- dental clinics
- multi-chair dental practices
- local practice management without heavy cloud dependency
- clinics that want simple WhatsApp communication and installment tracking

## Support

For deployment, setup, training, and customization, the local clinic or project owner can run the app in the environment that best matches their network and device needs.

---

This document is intended as a customer-friendly overview of the system and its capabilities.
