# Pharmacy Management System

A modern, mobile-responsive Pharmacy Management System built with **Laravel** (Backend) and **Vue.js** (Frontend).

## 🚀 Key Features

### 🛒 Sales & Cashier Workflow (New)
The system separates the **Order Creation** (Vendor) from the **Payment Processing** (Cashier) to improve efficiency and security.

1.  **Vendor / Pharmacist**: 
    - Creates an order (`Commande`) by selecting medicines.
    - Generates a **Ticket** with a unique number and **QR Code**.
    - Hands the printed ticket to the customer.

2.  **Cashier (Split-View Interface)**:
    - **Scan Mode**: Scans the customer's ticket QR code using the device camera.
    - **Manual Mode**: Enters the ticket number manually if needed (optimized for touch/desktop).
    - **Payment**: Reviews the order details and processes payment (Cash, Card, Mobile Money).
    - **Receipt**: Prints a thermal receipt (80mm) with item details, payment timestamp, and a "PAYÉ" verification stamp.

## 🛠️ Tech Stack
-   **Backend**: Laravel 11, Sanctum (Auth), MySQL.
-   **Frontend**: Vue 3, TailwindCSS, Lucide Icons.
-   **QR Tools**: `html5-qrcode` (Scanner), `qrcode.vue` (Generation).

## 📦 Installation

### Backend
```bash
cd backend
composer install
php artisan migrate --seed
php artisan serve
```

### Frontend
```bash
cd frontend
npm install
npm run dev
```

## 📱 Mobile First Design
The Cashier interface is fully responsive:
-   **Desktop**: Persistent 2-column layout (Scanner Left, Payment Right).
-   **Mobile**: Tab-based toggle for Scanner/Payment to save screen space.
