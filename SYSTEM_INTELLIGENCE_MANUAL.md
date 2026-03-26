# System Intelligence & Operational Manual

## Warehouse Management System — Complete Architectural Analysis

> **Purpose of this document:** To give anyone — developer, project manager, client, or QA tester — a complete understanding of how this system works, how its parts communicate, and how to safely extend or maintain it.

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Architecture Overview](#2-architecture-overview)
3. [Folder Structure & Purpose](#3-folder-structure--purpose)
4. [Request Lifecycle — How a Click Becomes Data](#4-request-lifecycle--how-a-click-becomes-data)
5. [Data Model & Relationships](#5-data-model--relationships)
6. [Controller → Service → Model Map](#6-controller--service--model-map)
7. [Authentication & Authorization Flow](#7-authentication--authorization-flow)
8. [Role-Based Permission System](#8-role-based-permission-system)
9. [Audit Log System](#9-audit-log-system)
10. [Stock Ledger & Inventory Movement](#10-stock-ledger--inventory-movement)
11. [Purchase Order Lifecycle](#11-purchase-order-lifecycle)
12. [Sales Order Lifecycle](#12-sales-order-lifecycle)
13. [Notification System (Real-Time)](#13-notification-system-real-time)
14. [Report & Analytics Engine](#14-report--analytics-engine)
15. [Caching Strategy](#15-caching-strategy)
16. [Frontend Architecture](#16-frontend-architecture)
17. [Event → Listener → Queue Flow](#17-event--listener--queue-flow)
18. [Email System](#18-email-system)
19. [Export System (CSV / Excel / PDF)](#19-export-system-csv--excel--pdf)
20. [Hidden System Mechanics](#20-hidden-system-mechanics)
21. [Command Surface Reference](#21-command-surface-reference)
22. [Infrastructure & Environment](#22-infrastructure--environment)
23. [Database Schema & Migration Timeline](#23-database-schema--migration-timeline)
24. [Deployment & Runtime](#24-deployment--runtime)
25. [Performance & Safety Considerations](#25-performance--safety-considerations)

---

## 1. Executive Summary

### What is this system?

This is a **Warehouse Management System (WMS)** — a web application that helps a business track its products, suppliers, customers, purchase orders, and sales orders. Think of it like the brain of a warehouse: it knows what came in, what went out, how much is left, and who did what.

### What technologies power it?

| Layer | Technology | Analogy |
|-------|-----------|---------|
| **Backend framework** | Laravel 12 (PHP 8.2+) | The engine of a car |
| **Frontend framework** | Vue 3 + TypeScript | The dashboard and steering wheel |
| **Connector (Backend↔Frontend)** | Inertia.js v2 | The driveshaft connecting engine to wheels |
| **Styling** | Tailwind CSS 3 | The paint and interior design |
| **Database** | SQLite (dev) / MySQL-ready | The filing cabinet |
| **Real-time** | Laravel Reverb (WebSockets) | A live intercom system |
| **Queue** | Database-backed | A to-do clipboard for background tasks |
| **Build tool** | Vite 7 | The factory that packages the frontend |
| **Charts** | Chart.js via vue-chartjs | The graphs and dashboards |
| **PDF generation** | DomPDF | The printer |
| **Named routing** | Ziggy | GPS for URL generation |

### Architecture Pattern

The system follows an **MVC + Inertia Hybrid** pattern:

- **M**odel (Laravel Eloquent) → Talks to the database
- **V**iew (Vue 3 SFCs via Inertia) → What the user sees in their browser
- **C**ontroller (Laravel Controllers) → Receives requests, processes logic, returns responses

**Inertia.js** acts as the glue — it eliminates the need for a traditional REST API by letting the backend send data directly to Vue components as props. The user gets a Single Page Application (SPA) experience while the backend keeps full control of routing and data.

> **Simple analogy:** Traditional web apps are like sending letters (full page reloads). REST APIs + SPAs are like phone calls (separate frontend/backend). Inertia is like an intercom — the frontend and backend live in the same building and communicate through a shared wall.

---

## 2. Architecture Overview

### System Architecture Diagram (Conceptual)

```
┌────────────────────────────────────────────────────────────────────┐
│                         USER'S BROWSER                             │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  Vue 3 Application (SPA-like)                                │  │
│  │  ├── AuthenticatedLayout (sidebar, header, notifications)    │  │
│  │  ├── Pages (Dashboard, Products, Orders, etc.)               │  │
│  │  ├── Components (Charts, Forms, Tables, Modals)              │  │
│  │  ├── Composables (reusable logic hooks)                      │  │
│  │  ├── Pinia Store (dashboard layout state)                    │  │
│  │  └── Laravel Echo (WebSocket client for real-time)           │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                              ▲                                     │
│                              │ Inertia.js Protocol                 │
│                              │ (JSON props, not full HTML)         │
│                              ▼                                     │
└────────────────────────────────────────────────────────────────────┘
                               │
                    ┌──────────┼──────────┐
                    │   HTTP / HTTPS      │
                    │   WebSocket (ws://) │
                    └──────────┼──────────┘
                               │
┌────────────────────────────────────────────────────────────────────┐
│                       LARAVEL BACKEND                              │
│                                                                    │
│  ┌─── Entry Points ──────────────────────────────────────────────┐ │
│  │  • HTTP (routes/web.php, routes/auth.php, routes/api.php)     │ │
│  │  • CLI  (php artisan commands)                                │ │
│  │  • Queue Worker (background jobs)                             │ │
│  │  • WebSocket (Laravel Reverb broadcasting)                    │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                               │                                    │
│  ┌─── Middleware Pipeline ───────────────────────────────────────┐ │
│  │  HandleInertiaRequests → RoleMiddleware → Throttle            │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                               │                                    │
│  ┌─── Controllers ──────────────────────────────────────────────┐ │
│  │  Admin: Dashboard, Product, PO, SO, User, AuditLog, etc.     │ │
│  │  Staff: StaffProduct, StaffSalesOrder, StaffCustomer, etc.   │ │
│  │  Shared: Profile, Notification, Report                        │ │
│  │  Auth: Login, Register, Password, Email Verification          │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                               │                                    │
│  ┌─── Services ─────────────────────────────────────────────────┐ │
│  │  CacheService (domain-versioned caching)                      │ │
│  │  ExportService (CSV, Excel, PDF generation)                   │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                               │                                    │
│  ┌─── Models (Eloquent ORM) ────────────────────────────────────┐ │
│  │  User, Products, Category, Supplier, Customer                 │ │
│  │  Purchase_Order, PO_Item, Sales_Order, SO_Item                │ │
│  │  Stock_Ledger, Audit_Logs, AppNotification                    │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                               │                                    │
│  ┌─── Cross-Cutting Concerns ───────────────────────────────────┐ │
│  │  Traits: Auditable, HasExport, HasIndexFilters, ApiResponse   │ │
│  │  Events: AuditLogEvent, NotificationPushed                    │ │
│  │  Listeners: AuditLogListener, LogLogin, LogLogout             │ │
│  │  Observers: Product, SO, PO, Supplier, Customer Observers     │ │
│  │  Jobs: SendSalesOrderCompletedEmail                           │ │
│  │  Policy: UserPolicy                                           │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                               │                                    │
│  ┌─── Database ─────────────────────────────────────────────────┐ │
│  │  SQLite (dev) / MySQL (production-ready)                      │ │
│  │  Tables: users, products, categories, suppliers, customers,   │ │
│  │  purchase_orders, purchase_order_items, sales_orders,         │ │
│  │  sales_order_items, stock_ledgers, audit_logs,                │ │
│  │  app_notifications, sessions, cache, jobs, failed_jobs        │ │
│  └───────────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────────┘
```

### How the Four Technologies Interact

```
                   BROWSER                         SERVER
              ┌──────────────┐              ┌──────────────────┐
              │   Vue 3      │◄────────────►│   Laravel 12     │
              │   + TypeScript│  Inertia.js  │   (PHP 8.2)     │
              │   + Tailwind │  Protocol    │                  │
              └──────────────┘              └──────────────────┘
                     │                              │
              Vite bundles                   Eloquent ORM
              Vue → JS/CSS                   talks to DB
```

1. **Laravel** handles all routing, validation, business logic, and database operations
2. **Inertia.js** receives Laravel's response and passes it directly to Vue as component props — no REST API needed for page navigation
3. **Vue 3** renders the UI using those props, handles user interactions, and sends form submissions back through Inertia
4. **TypeScript** adds type safety to the frontend code
5. **Tailwind CSS** provides utility-first styling with dark mode support

---

## 3. Folder Structure & Purpose

```
warehouse-management/
│
├── app/                          # 🧠 APPLICATION CORE — All PHP business logic
│   ├── Enums/                    #   Typed constants (AuditAction, BlockReason, UserRole)
│   ├── Events/                   #   Event classes that broadcast system happenings
│   ├── Http/
│   │   ├── Controllers/          #   Request handlers — the "traffic directors"
│   │   │   ├── Auth/             #     Login, Register, Password flows (Laravel Breeze)
│   │   │   ├── *Controller.php   #     Admin controllers (full access)
│   │   │   └── Staff*Controller  #     Staff controllers (limited access)
│   │   ├── Middleware/           #   Request filters (auth check, role check, Inertia data)
│   │   ├── Requests/            #   Form validation rules (one class per form)
│   │   └── Resources/           #   Data transformers (model → JSON shape)
│   ├── Jobs/                     #   Background tasks (email sending)
│   ├── Listeners/                #   React to events (audit logging, login tracking)
│   ├── Mail/                     #   Email templates (sales order confirmation)
│   ├── Models/                   #   Database table representations
│   ├── Observers/                #   Auto-react to model changes (cache invalidation)
│   ├── Policies/                 #   Authorization rules (who can do what)
│   ├── Providers/                #   Service bootstrapping (wiring events, observers)
│   ├── Services/                 #   Reusable business logic (caching, exporting)
│   └── Traits/                   #   Shared behaviors (auditing, filtering, exporting)
│
├── bootstrap/                    # 🔧 APPLICATION BOOTSTRAP
│   ├── app.php                   #   Middleware registration & exception handling
│   └── providers.php             #   Service provider registration
│
├── config/                       # ⚙️ CONFIGURATION — Environment-specific settings
│   ├── auth.php                  #   Authentication guards & providers
│   ├── database.php             #   Database connections
│   ├── queue.php                #   Background job processing
│   ├── cache.php                #   Caching configuration
│   ├── mail.php                 #   Email driver settings
│   ├── broadcasting.php         #   WebSocket/real-time settings
│   └── session.php              #   Session management
│
├── database/                     # 🗄️ DATABASE MANAGEMENT
│   ├── migrations/               #   Schema version history (27 migrations)
│   ├── seeders/                  #   Default data (3 test users)
│   └── factories/                #   Test data generators
│
├── resources/                    # 🎨 FRONTEND ASSETS
│   ├── js/
│   │   ├── app.ts               #   Vue application entry point
│   │   ├── bootstrap.ts         #   Axios + Echo setup
│   │   ├── echo.ts              #   WebSocket connection
│   │   ├── Components/          #   Reusable Vue components (~30)
│   │   ├── composables/         #   Reusable logic hooks (9)
│   │   ├── config/              #   Navigation registry
│   │   ├── Layouts/             #   Page layout wrappers (2)
│   │   ├── Pages/               #   Full-page Vue components
│   │   │   ├── Admin/           #     Admin views (11 modules)
│   │   │   ├── Staff/           #     Staff views (4 modules)
│   │   │   ├── Auth/            #     Login/Register views (6)
│   │   │   └── Profile/         #     Profile settings (4)
│   │   ├── stores/              #   Pinia state management (1 store)
│   │   └── types/               #   TypeScript type definitions (4)
│   ├── css/                     #   Tailwind CSS entry point
│   └── views/                   #   Blade templates (email, PDF reports)
│
├── routes/                       # 🛣️ URL ROUTING
│   ├── web.php                  #   Main application routes (admin, staff, shared)
│   ├── auth.php                 #   Authentication routes (login, register, etc.)
│   ├── api.php                  #   External API routes (v2 placeholder)
│   ├── channels.php             #   WebSocket channel authorization
│   └── console.php              #   CLI command registration
│
├── public/                       # 🌐 WEB ROOT — Served directly by web server
│   ├── index.php                #   Single entry point for all HTTP requests
│   ├── build/                   #   Compiled frontend assets (Vite output)
│   └── storage → storage/app    #   Symlink for uploaded files
│
├── storage/                      # 📦 APPLICATION STORAGE
│   ├── app/                     #   Uploaded files (product images, etc.)
│   ├── framework/               #   Cache, sessions, views (internal)
│   └── logs/                    #   Application log files
│
├── tests/                        # 🧪 TESTING
│   ├── Feature/                 #   Integration tests
│   └── Unit/                    #   Unit tests
│
├── vendor/                       # 📚 PHP DEPENDENCIES (auto-managed by Composer)
│
├── composer.json                 # PHP dependency manifest
├── package.json                  # JavaScript dependency manifest
├── vite.config.js               # Frontend build configuration
├── tailwind.config.js           # CSS framework configuration
├── tsconfig.json                # TypeScript compiler configuration
└── phpunit.xml                  # Test runner configuration
```

---

## 4. Request Lifecycle — How a Click Becomes Data

### The Journey of a Request

Imagine a user clicks "Create Product" and submits a form. Here's exactly what happens:

```
USER                    BROWSER                     SERVER                       DATABASE
 │                        │                           │                            │
 │  1. Fills form         │                           │                            │
 │  clicks "Save"         │                           │                            │
 │ ──────────────────────►│                           │                            │
 │                        │ 2. Inertia sends          │                            │
 │                        │    POST /admin/products   │                            │
 │                        │ ──────────────────────────►│                            │
 │                        │                           │ 3. Middleware Pipeline      │
 │                        │                           │    ├── Session check        │
 │                        │                           │    ├── Auth check           │
 │                        │                           │    ├── Role: admin?         │
 │                        │                           │    └── Inertia headers      │
 │                        │                           │                            │
 │                        │                           │ 4. StoreProductRequest      │
 │                        │                           │    validates all fields     │
 │                        │                           │    (name, price, stock)     │
 │                        │                           │                            │
 │                        │                           │ 5. ProductController@store  │
 │                        │                           │    ├── Generate SKU         │
 │                        │                           │    ├── Handle image upload  │
 │                        │                           │    └── DB::transaction {   │
 │                        │                           │         Product::create()───────────►│ INSERT product
 │                        │                           │         StockLedger::create()───────►│ INSERT ledger
 │                        │                           │        }                   │
 │                        │                           │                            │
 │                        │                           │ 6. MODEL EVENTS FIRE:      │
 │                        │                           │    ├── Auditable trait      │
 │                        │                           │    │   └── AuditLogEvent────────────►│ INSERT audit_log
 │                        │                           │    └── ProductObserver      │
 │                        │                           │        └── CacheService    │
 │                        │                           │            .flushProducts() │
 │                        │                           │                            │
 │                        │ 7. Inertia redirect       │                            │
 │                        │◄───────────────────────────│                            │
 │                        │    to products.index       │                            │
 │                        │    + flash "Created!"      │                            │
 │                        │                            │                            │
 │  8. Vue re-renders     │                            │                            │
 │     products list      │                            │                            │
 │◄───────────────────────│                            │                            │
```

### Step-by-Step Breakdown

| Step | What Happens | Simple Analogy |
|------|-------------|----------------|
| **1. User action** | User fills in product details and clicks Save | Writing a form at the post office |
| **2. HTTP request** | Inertia sends form data as a POST request | Handing the form to the clerk |
| **3. Middleware** | System checks: Are you logged in? Are you an admin? | Security guard checks your ID |
| **4. Validation** | `StoreProductRequest` checks all fields are valid | The clerk checks all fields are filled in |
| **5. Controller** | `ProductController@store` runs the business logic inside a database transaction | The clerk processes your request |
| **6. Side effects** | The `Auditable` trait logs who did what; the `ProductObserver` clears stale cache | Security camera records the transaction; old price lists are shredded |
| **7. Response** | Laravel redirects back with a success message | The clerk gives you a receipt |
| **8. UI update** | Vue receives the new data and re-renders the page | The display board updates |

### How Data Flows Through Each Layer

```
DATA ENTERS          DATA IS            DATA IS              DATA IS           DATA IS          DATA IS
THE SYSTEM    →     VALIDATED     →    TRANSFORMED    →     PERSISTED    →   RETRIEVED    →   RENDERED
                                                                                              
HTTP Request        FormRequest        Controller           Eloquent         Controller       Inertia +
(form fields,       classes check      logic: generate      Model::create()  Model::query()   Vue SFCs
file uploads,       types, ranges,     SKUs, calculate      with fillable    with eager       display via
query params)       uniqueness,        totals, handle       protection       loading,         props,
                    relationships      images, wrap in      and casts        scopes, and      Resource
                                       transactions                          pagination       classes
```

---

## 5. Data Model & Relationships

### Entity Relationship Overview

> **Simple analogy:** Think of the database like a warehouse filing system. Each table is a filing cabinet drawer. Relationships are the cross-references written on file folders that point you to related folders in other drawers.

### The 12 Models

| Model | Table | Purpose | Soft Delete? | Audited? |
|-------|-------|---------|:---:|:---:|
| `User` | `users` | System users (admin/staff) | ✅ | ✅ |
| `Products` | `products` | Inventory items | ✅ | ✅ |
| `Category` | `categories` | Product groupings (custom string PK) | ❌ | ❌ |
| `Supplier` | `suppliers` | Vendors who supply products | ✅ | ✅ |
| `Customer` | `customers` | Buyers who place sales orders | ✅ | ✅ |
| `Purchase_Order` | `purchase_orders` | Orders to suppliers (incoming stock) | ✅ | ✅ |
| `Purchase_Order_Item` | `purchase_order_items` | Line items on purchase orders | ❌ | ❌ |
| `Sales_Order` | `sales_orders` | Orders from customers (outgoing stock) | ✅ | ✅ |
| `Sales_Order_Item` | `sales_order_items` | Line items on sales orders | ❌ | ❌ |
| `Stock_Ledger` | `stock_ledgers` | Immutable inventory movement log | ❌ | ❌ |
| `Audit_Logs` | `audit_logs` | Immutable activity trail | ❌ | ❌ |
| `AppNotification` | `app_notifications` | In-app notifications | ❌ | ❌ |

### Relationship Diagram

```
                          ┌──────────┐
                          │   User   │
                          │  (admin/ │
                          │   staff) │
                          └────┬─────┘
                    ┌──────────┼──────────────┐
                    │          │              │
              created_by  created_by    created_by
                    │          │              │
                    ▼          ▼              ▼
           ┌──────────┐ ┌──────────┐  ┌─────────────┐
           │ Purchase  │ │  Sales   │  │   Stock     │
           │  Order    │ │  Order   │  │   Ledger    │
           └────┬──┬───┘ └──┬──┬───┘  └──────┬──────┘
                │  │        │  │             │
         items  │  │ supp   │  │ cust    product
                │  │ lier   │  │ omer       │
                ▼  ▼        ▼  ▼            ▼
        ┌──────┐ ┌────────┐ ┌────────┐ ┌──────────┐
        │ PO   │ │Supplier│ │Customer│ │ Products │
        │ Items│ └────────┘ └────────┘ └────┬─────┘
        └──┬───┘                            │
           │     ┌──────┐              category
           └────►│Produc│◄─────────────────│
                 │  ts  │              ┌────┴─────┐
                 └──────┘              │ Category │
                    ▲                  └──────────┘
                    │
                 ┌──┴───┐
                 │ SO   │
                 │ Items│
                 └──────┘
```

### Detailed Relationships

| Parent | Relationship | Child | Type |
|--------|-------------|-------|------|
| **User** | → creates | Purchase_Order | One-to-Many |
| **User** | → creates | Sales_Order | One-to-Many |
| **User** | → has | Audit_Logs | One-to-Many |
| **User** | → receives | AppNotification | One-to-Many |
| **User** | → creates | Stock_Ledger | One-to-Many |
| **Category** | → contains | Products | One-to-Many |
| **Products** | → appears in | Purchase_Order_Item | One-to-Many |
| **Products** | → appears in | Sales_Order_Item | One-to-Many |
| **Products** | → has history in | Stock_Ledger | One-to-Many |
| **Supplier** | → receives | Purchase_Order | One-to-Many |
| **Customer** | → places | Sales_Order | One-to-Many |
| **Purchase_Order** | → contains | Purchase_Order_Item | One-to-Many |
| **Sales_Order** | → contains | Sales_Order_Item | One-to-Many |
| **Audit_Logs** | → references any model | (polymorphic) | MorphTo |

> **Simple explanation:** A Supplier has many Purchase Orders. Each Purchase Order has many Items. Each Item references a Product. It's like a restaurant: a vendor (Supplier) delivers ingredients (Products) listed on a delivery slip (PO) with line items (PO Items).

---

## 6. Controller → Service → Model Map

### Admin Controllers

| Controller | Models Used | Services | Traits | Inertia Page |
|-----------|-------------|----------|--------|-------------|
| `DashboardController` | User (auth) | — | — | `Admin/Dashboard/Index` |
| `ProductController` | Products, Category, Stock_Ledger | — | HasIndexFilters, HasExport | `Admin/Products/*` |
| `CategoryController` | Category, Products | — | HasIndexFilters | `Admin/Categories/Index` |
| `SupplierController` | Supplier, Purchase_Order | — | HasIndexFilters, HasExport | `Admin/Suppliers/Index` |
| `CustomerController` | Customer, Sales_Order | — | HasIndexFilters, HasExport | `Admin/Customers/Index` |
| `PurchaseOrderController` | Purchase_Order, PO_Item, Products, Stock_Ledger, AppNotification | — | HasIndexFilters, HasExport | `Admin/PurchaseOrders/Index` |
| `SalesOrderController` | Sales_Order, SO_Item, Products, Stock_Ledger, AppNotification | — | HasIndexFilters, HasExport | `Admin/SalesOrders/Index` |
| `UserController` | User | — | HasIndexFilters | `Admin/Users/*` |
| `AuditLogController` | Audit_Logs | — | HasIndexFilters | `Admin/AuditLogs/Index` |
| `StockLedgerController` | Stock_Ledger, Products, User | — | — | `Admin/StockLedger/Index` |
| `ReportController` | Products, PO, SO, SO_Item, Supplier, User | CacheService | — | `Admin/Reports/Index` |
| `NotificationController` | AppNotification | — | — | — (JSON only) |
| `ProfileController` | User | — | — | `Profile/Edit` |

### Staff Controllers

| Controller | Mirrors | Differences |
|-----------|---------|-------------|
| `StaffProductController` | `ProductController` | No export; renders `Staff/Products/*` |
| `StaffSalesOrderController` | `SalesOrderController` | Only sees own orders; can only reject (not complete); notifies admins only |
| `StaffCustomerController` | `CustomerController` | No export; renders `Staff/Customers/Index` |
| `StaffSupplierController` | `SupplierController` | No export; renders `Staff/Suppliers/Index` |

> **Why separate controllers?** Admin and staff have different permissions and see different data. Rather than cluttering one controller with if/else checks, the system uses separate controller classes — cleaner and easier to maintain. Routes enforce which controller handles which role.

---

## 7. Authentication & Authorization Flow

### Login Flow

```
User enters email + password
        │
        ▼
   LoginRequest validates & rate-limits (5 attempts per email+IP)
        │
        ▼
   Is user blocked? ──── YES ──► AuditLogEvent("blocked login attempt") → Reject
        │
        NO
        │
        ▼
   Auth::attempt() ──── FAILS ──► Increment failed_login_attempts
        │                              │
        │                         ≥ 3 failures? ──► Auto-block user
        │                              │              AuditLogEvent("auto-blocked")
        │                              │              BlockReason::FAILED_LOGIN_ATTEMPTS
        │                              │
        │                         < 3 failures? ──► AuditLogEvent("failed_login")
        │                                           Show error
        SUCCESS
        │
        ▼
   Reset failed_login_attempts to 0
   Regenerate session
   AuditLogEvent("login")
        │
        ▼
   Redirect by role:
   ├── admin → /admin/dashboard
   └── staff → /staff/dashboard
```

### Session Management

| Setting | Value | Meaning |
|---------|-------|---------|
| Driver | `database` | Sessions stored in a database table (survives server restarts) |
| Lifetime | 120 minutes | User stays logged in for 2 hours of inactivity |
| Cookie | `{app-name}-session` | Browser cookie name |
| Same-Site | `lax` | Prevents cross-site request forgery |
| HTTP Only | `true` | JavaScript cannot read the session cookie |

### Auth Guard

The system uses a **single guard**: `web` (session-based). There are no API tokens (Sanctum is installed but not configured as a guard). All authentication is cookie/session-based.

---

## 8. Role-Based Permission System

### Two Roles

| Role | Code | Access Level |
|------|------|-------------|
| **Admin** | `admin` | Full access to everything |
| **Staff** | `staff` | Limited access — own orders, no user management, no audit logs |

### How Roles Are Enforced

The permission system operates at **three levels**:

#### Level 1: Route Middleware (Broad Access Control)

```php
// In routes/web.php
Route::middleware(['auth', 'role:admin'])->prefix('admin')  // Only admins
Route::middleware(['auth', 'role:staff'])->prefix('staff')  // Only staff
Route::middleware(['auth'])->group(...)                      // Any logged-in user
```

The `RoleMiddleware` checks `auth()->user()->role` against the required role. Non-matching users get a 403 Forbidden error.

#### Level 2: Gate/Policy (Fine-Grained Authorization)

Only `UserController` uses Laravel's Gate/Policy system:

| Action | Rule |
|--------|------|
| View users | Admin only |
| Create user | Admin only |
| Update user | Admin only |
| Delete user | Admin + not self + not last admin |
| Block user | Admin + not self |
| Unblock user | Admin only |

#### Level 3: Controller-Level Scoping (Data Visibility)

Staff controllers scope data to the current user:

```php
// StaffSalesOrderController — staff only sees their own orders
$query->where('created_by', Auth::id());
```

### What Each Role Can Access

| Feature | Admin | Staff |
|---------|:-----:|:-----:|
| Dashboard | ✅ (full analytics) | ✅ (limited analytics) |
| Products CRUD | ✅ | ✅ |
| Categories CRUD | ✅ | ❌ |
| Suppliers CRUD | ✅ | ✅ |
| Customers CRUD | ✅ | ✅ |
| Purchase Orders | ✅ (all POs, full lifecycle) | ❌ |
| Sales Orders | ✅ (all SOs, full lifecycle) | ✅ (own only, create + reject only) |
| User Management | ✅ | ❌ |
| Audit Logs | ✅ | ❌ |
| Stock Ledger | ✅ | ❌ |
| Reports (full) | ✅ | ✅ (limited — own data) |
| Exports (CSV/Excel/PDF) | ✅ | ❌ |

---

## 9. Audit Log System

### Overview

Every important action in the system is recorded in an immutable audit log — like a security camera that records everything.

### How It Works

```
Model change (create/update/delete/restore)
        │
        ▼
   Auditable trait (boot lifecycle hook)
        │
        ├── Captures: who, what, when, old values, new values
        ├── Generates human-readable event label
        │   ("Product Updated (Name, Price)")
        ├── Detects status transitions
        │   ("Sales Order Completed")
        └── Dispatches AuditLogEvent
                │
                ▼
        EventServiceProvider routes to AuditLogListener
                │
                ▼
        AuditLogListener persists to audit_logs table
                │
     ┌──────────┴──────────┐
     │  Immutable Record   │
     │  • User ID/Name     │
     │  • Action (enum)    │
     │  • Event Label      │
     │  • Old/New Values   │
     │  • IP Address       │
     │  • Timestamp        │
     └─────────────────────┘
```

### What Gets Audited

| Event | Trigger | Action Enum |
|-------|---------|-------------|
| User logs in | `Login` event → `LogSuccessfulLogin` | `LOGIN` |
| User logs out | `Logout` event → `LogSuccessfulLogout` | `LOGOUT` |
| Any model created | `Auditable` trait on `created` | `CREATED` |
| Any model updated | `Auditable` trait on `updated` | `UPDATED` |
| Any model soft-deleted | `Auditable` trait on `deleted` | `ARCHIVED` |
| Any model force-deleted | `Auditable` trait on `deleted` | `DELETED` |
| Any model restored | `Auditable` trait on `restored` | `RESTORED` |
| User blocked | `UserController` manual dispatch | `BLOCKED` |
| Failed login | `AuthenticatedSessionController` dispatch | `FAILED_LOGIN` |

### Immutability

The `Audit_Logs` model **blocks updates and deletes** in its `boot()` method:

```php
static::updating(fn() => false);  // Cannot modify audit logs
static::deleting(fn() => false);  // Cannot delete audit logs
```

This ensures the audit trail cannot be tampered with.

### Models Using the Auditable Trait

`User`, `Products`, `Customer`, `Supplier`, `Purchase_Order`, `Sales_Order`

---

## 10. Stock Ledger & Inventory Movement

### Overview

The Stock Ledger is the **single source of truth for inventory movements**. Every time stock changes — whether products arrive (purchase) or leave (sale) — a ledger entry is created.

> **Analogy:** The stock ledger is like a bank statement for your warehouse. Every deposit (stock in) and withdrawal (stock out) is recorded with who did it, when, and why.

### How Stock Moves

```
PURCHASE ORDER COMPLETED                    SALES ORDER COMPLETED
        │                                          │
        ▼                                          ▼
  lockForUpdate() on Product              lockForUpdate() on Product
        │                                          │
        ▼                                          ▼
  product.current_stock += qty            product.current_stock -= qty
        │                                     (reject if < 0)
        ▼                                          │
  Stock_Ledger::create({                          ▼
    movement_type: 'in',                   Stock_Ledger::create({
    quantity: +qty,                          movement_type: 'out',
    balance_after: new_stock,               quantity: -qty,
    reference: 'PO-20260225-0001'           balance_after: new_stock,
  })                                        reference: 'SO-20260225-0001'
                                           })
```

### Stock Ledger Entry Fields

| Field | Description | Example |
|-------|------------|---------|
| `product_id` | Which product moved | Product #42 |
| `reference_type` | What caused the movement | `purchase` or `sale` |
| `reference_id` | The order ID | PO #7 or SO #12 |
| `movement_type` | Direction of movement | `in` or `out` |
| `quantity` | How many units | +50 or -10 |
| `balance_after` | Stock after this movement | 150 |
| `created_by` | Who triggered it | User #1 |

### Concurrency Safety

Both `completePurchaseOrder()` and `completeSalesOrder()` use:

1. **`DB::transaction()`** — All-or-nothing. If any step fails, everything rolls back.
2. **`lockForUpdate()`** — Row-level database lock. Prevents two people from completing orders on the same product simultaneously.
3. **Negative stock prevention** — Sales orders check `current_stock >= quantity` before deducting.

---

## 11. Purchase Order Lifecycle

### Status State Machine

```
                    ┌──────────┐
                    │ PENDING  │ ◄── Created here
                    └────┬─────┘
                ┌────────┼────────┐
                ▼        ▼        ▼
          ┌──────────┐ ┌─────────┐ ┌───────────┐
          │ APPROVED │ │REJECTED │ │ CANCELLED │
          └────┬─────┘ └─────────┘ └───────────┘
               │
               ▼
          ┌──────────┐
          │PROCESSING│
          └────┬─────┘
               │
               ▼
          ┌────────────┐
          │FOR_SHIPMENT│
          └──────┬─────┘
                 │
                 ▼
          ┌───────────┐
          │ COMPLETED │ ──► Stock IN + Ledger entries
          └───────────┘
```

### Lifecycle Steps

| Step | Action | Effect |
|------|--------|--------|
| 1. Create PO | Select supplier, add products + quantities | PO saved as `pending`, PO number auto-generated (`PO-YYYYMMDD-XXXX`) |
| 2. Approve | Admin changes status to `approved` | Status updated, notification sent |
| 3. Process | Admin changes to `processing` | Status updated |
| 4. Ship | Admin changes to `for_shipment` | Status updated |
| 5. Complete | Admin changes to `completed` | **Stock increases** for each product; ledger entries created; notification sent |
| Alt: Reject | Admin changes to `rejected` | PO killed; no stock effect |
| Alt: Cancel | Admin changes to `cancelled` | PO killed; no stock effect |

---

## 12. Sales Order Lifecycle

### Status State Machine

```
              ┌───────┐
              │ DRAFT │ ◄── Created here
              └───┬───┘
           ┌──────┼──────┐
           ▼      ▼      ▼
    ┌──────────┐ ┌─────────┐
    │   FOR    │ │REJECTED │
    │PROCESSING│ └─────────┘
    └────┬─────┘
         │
         ▼
    ┌────────────┐
    │FOR_SHIPMENT│
    └──────┬─────┘
           │
           ▼
    ┌───────────┐
    │ COMPLETED │ ──► Stock OUT + Ledger entries + Email to customer
    └───────────┘
```

### Lifecycle Steps

| Step | Action | Effect |
|------|--------|--------|
| 1. Create SO | Select customer, add products + quantities | SO saved as `draft`, SO number auto-generated (`SO-YYYYMMDD-XXXX`). **Stock validated** — cannot add more than available. |
| 2. Process | Admin moves to `for_processing` | Status updated |
| 3. Ship | Admin moves to `for_shipment` | Status updated |
| 4. Complete | Admin moves to `completed` | **Stock decreases**; ledger "out" entries; email job queued |
| Alt: Reject | Admin or Staff rejects | No stock effect |

### Staff Restrictions

Staff members can only:
- **Create** sales orders (as `draft`)
- **View** their own orders only
- **Reject** their own orders
- They **cannot** approve, process, ship, or complete orders

---

## 13. Notification System (Real-Time)

### Two Notification Layers

| Layer | Purpose | Technology |
|-------|---------|-----------|
| **UI Toasts** | Temporary success/error/warning messages after actions | `useNotification` composable (Vue-only, no backend) |
| **Push Notifications** | Persistent in-app notifications with a bell icon | `AppNotification` model + WebSocket broadcasting |

### Push Notification Flow

```
BACKEND ACTION (e.g., PO created)
        │
        ▼
  AppNotification::notifyAll() or notifyAdmins()
        │
        ├── Creates AppNotification records in DB
        │   (one per recipient user)
        │
        └── Each record fires NotificationPushed event
                │
                ▼
         ShouldBroadcastNow (instant, no queue)
                │
                ▼
         Private WebSocket channel: notifications.{userId}
                │
                ▼
    FRONTEND (useAppNotifications composable)
        │
        ├── Laravel Echo receives event
        ├── Prepends notification to reactive array
        ├── Increments unread count
        └── Bell icon updates instantly
```

### Fallback Mechanism

If WebSocket connection fails, the composable automatically switches to **HTTP polling** with exponential backoff:
- Starts at 2-second intervals
- Backs off to 4s → 8s → 16s → max 30s
- When WebSocket reconnects, polling stops

### When Notifications Are Sent

| Event | Recipients |
|-------|-----------|
| Purchase Order created | All users |
| Purchase Order status changed | All users |
| Sales Order created (by admin) | All users |
| Sales Order created (by staff) | Admins only |
| Sales Order status changed | All users |

---

## 14. Report & Analytics Engine

### Architecture

The reporting system is a **client-fetched, server-cached** architecture:

```
Vue Dashboard Page
        │
        ├── onMounted: useReport('/api/v1/reports/daily-sales')
        │       │
        │       ▼
        │   axios.get() ───────────► ReportController@dailySales
        │                                    │
        │                            CacheService.remember()
        │                                    │
        │                            Cache HIT? ──► Return cached data
        │                            Cache MISS? ──► Query DB
        │                                           Transform via Resource
        │                                           Store in cache (TTL)
        │                                           Return fresh data
        │       ◄────────────────────────────┘
        │   
        └── Render Chart.js component with data
```

### Available Reports

| Report | Endpoint | Cache TTL | Access |
|--------|---------|-----------|--------|
| Summary Cards (KPIs) | `/api/v1/reports/summary-cards` | 3 min | All (staff scoped to own data) |
| Daily Sales | `/api/v1/reports/daily-sales` | 5 min | All (staff scoped) |
| Top Products | `/api/v1/reports/top-products` | 5 min | All (staff scoped) |
| Low Stock Items | `/api/v1/reports/low-stock-items` | 2 min | All |
| Inventory Overview | `/api/v1/reports/inventory-overview` | 5 min | Admin only |
| User Analytics | `/api/v1/reports/user-analytics` | 5 min | Admin only |
| Purchase Order Analytics | `/api/v1/reports/purchase-order-analytics` | 5 min | Admin only |
| Supplier Procurement | `/api/v1/reports/supplier-procurement` | 5 min | Admin only |

### Dashboard Layout System

The dashboard uses a sophisticated **slot-based layout** managed by a Pinia store:

- 4 card slots (analytics-left, analytics-right, operational-left, operational-right)
- Cards can be **resized** by dragging edges (grid-snapped)
- Cards can be **swapped** within zones
- Cards can be **expanded** to full width
- KPI cards can be **reordered** via drag-and-drop
- Layout persists in `localStorage`

---

## 15. Caching Strategy

### Domain-Versioned Caching

> **Analogy:** Imagine each department (Products, Sales, etc.) has a version number stamped on their documents. When anything changes in a department, the version number increments. Anyone asking for old-version documents gets told "sorry, outdated" and has to wait for fresh copies to be generated.

```
CacheService Architecture:

Domain: "products"  Version: 7
┌────────────────────────────────────────┐
│  Key: products_v7_report_inventory     │  ◄── Valid (current version)
│  Key: products_v6_report_inventory     │  ◄── Stale (ignored, expires via TTL)
└────────────────────────────────────────┘

When a product is created/updated/deleted:
  ProductObserver → CacheService::flushProducts()
  → Increments version to 8
  → All v7 keys become unreachable
  → Next request generates fresh v8 data
```

### Cache Invalidation Mapping

| Model Changed | Observers Trigger | Domains Flushed |
|---------------|------------------|-----------------|
| Product | `ProductObserver` | products, reports |
| Sales Order | `SalesOrderObserver` | sales, reports (+ products if completed) |
| Purchase Order | `PurchaseOrderObserver` | purchases, reports (+ products if completed) |
| Supplier | `SupplierObserver` | suppliers, reports |
| Customer | `CustomerObserver` | customers, reports |

---

## 16. Frontend Architecture

### Technology Stack

| Technology | Version | Purpose |
|-----------|---------|---------|
| Vue 3 | 3.4+ | Reactive UI framework |
| TypeScript | 5.6+ | Type-safe JavaScript |
| Inertia.js Vue | 2.0 | SPA-like navigation without REST API |
| Pinia | 3.0 | State management (dashboard layout) |
| Tailwind CSS | 3.2+ | Utility-first styling with dark mode |
| Chart.js + vue-chartjs | 4.5 / 5.3 | Data visualization |
| Heroicons | 2.2 | Icon library |
| vuedraggable | 4.1 | Drag-and-drop for KPI cards |
| Ziggy | 2.0 | Use Laravel named routes in JS |
| Laravel Echo | 2.3 | WebSocket client |
| Pusher.js | 8.4 | WebSocket transport |

### Layout Hierarchy

```
App Entry Point (app.ts)
├── creates Inertia App
├── installs Pinia
├── installs ZiggyVue
└── resolves Pages from ./Pages/**/*.vue
    │
    ├── GuestLayout.vue (unauthenticated pages)
    │   └── <slot> → Auth/Login, Auth/Register, etc.
    │
    └── AuthenticatedLayout.vue (authenticated pages)
        ├── Collapsible Sidebar
        │   ├── Logo + Brand ("Jounteux")
        │   ├── User Avatar
        │   ├── Navigation Items (role-based from navigation.ts)
        │   └── Logout Button
        ├── Sticky Header
        │   ├── Greeting + Clock (useGreeting)
        │   ├── ThemeToggle (dark/light mode)
        │   └── NotificationDropdown (real-time bell)
        ├── <slot> → Page content
        ├── NotificationToast (global, Teleported to body)
        └── ConfirmDialog (global, Teleported to body)
```

### Navigation System

The navigation is **declarative and role-based**:

```typescript
// config/navigation.ts
ALL_NAV_ITEMS = [
  { feature: 'dashboard', label: 'Dashboard', icon: HomeIcon,
    routes: { admin: 'admin.dashboard', staff: 'staff.dashboard' } },
  { feature: 'users', label: 'Users', icon: UsersIcon,
    routes: { admin: 'admin.users.index' } },  // No staff route = hidden for staff
  // ...
];

ROLE_FEATURE_MAP = {
  admin: ['dashboard', 'users', 'products', 'categories', ...],
  staff: ['dashboard', 'products', 'suppliers', 'customers', 'sales-orders'],
};
```

This means adding a new feature to a role is a one-line change in the config file.

### Composables (Reusable Logic)

| Composable | What It Does |
|-----------|-------------|
| `useAppNotifications` | WebSocket notifications + HTTP polling fallback |
| `useNotification` | UI toast messages + confirmation dialogs |
| `useGreeting` | Reactive greeting based on time of day |
| `useTheme` | Dark/light mode toggle, persisted |
| `useReport<T>` | Fetch, cache, and display report data with loading states |
| `useRouteLoading` | Track Inertia navigation for skeleton screens |
| `useCardResize` | Drag-to-resize dashboard cards |
| `useLayoutPersistence` | Save/restore dashboard layout from localStorage |
| `useResizeObserver` | Debounced container size tracking for chart re-rendering |

### Form Handling Patterns

**Pattern 1: Inertia `useForm`** (for CRUD mutations)

```typescript
const form = useForm({ name: '', price: 0, image: null });
form.post(route('admin.products.store'), {
    forceFormData: true,      // Required for file uploads
    onSuccess: () => success('Product created!'),
    onError: () => error('Validation failed'),
});
```

**Pattern 2: Inertia `router.get`** (for server-side filtering)

```typescript
router.get(route('admin.products.index'), {
    search: searchTerm.value,
    sort_by: 'name',
}, { preserveState: true, replace: true });
```

**Pattern 3: Direct Axios/fetch** (for sub-flows like product search in modals)

```typescript
const response = await fetch(route('admin.products.search') + '?' + params);
```

### Vue Component Categories (~70 SFCs)

| Category | Count | Examples |
|----------|-------|---------|
| Page Components | ~25 | Dashboard/Index, Products/Index, Products/Create |
| Dashboard Widgets | 6 | DashboardCard, KpiCard, KpiStrip, SpanHandle |
| Report Charts | 9 | DailySalesChart, TopProductsChart, LowStockTable |
| Skeleton Loaders | 5 | TableSkeleton, ChartSkeleton, CardSkeleton |
| Shared UI | 14 | Pagination, Modal, ThemeToggle, ConfirmDialog |
| Auth Forms | 6 | Login, Register, ForgotPassword |
| Profile Partials | 4 | UpdateProfileInfo, UpdatePassword, DeleteUser |

---

## 17. Event → Listener → Queue Flow

### Event System Overview

```
EVENT SOURCE                    EVENT                    LISTENER                  EFFECT
─────────────────────────────── ──────────────────────── ────────────────────────── ─────────────────────
Model CRUD                      AuditLogEvent            AuditLogListener           → Write to audit_logs
  (via Auditable trait)                                                               (synchronous)

Laravel Auth Login              Illuminate\Login         LogSuccessfulLogin         → Dispatch AuditLogEvent
                                                                                      (synchronous)

Laravel Auth Logout             Illuminate\Logout        LogSuccessfulLogout        → Dispatch AuditLogEvent
                                                                                      (synchronous)

User Registration               Illuminate\Registered    SendEmailVerification      → Send verification email
                                                         Notification                 (built-in)

AppNotification created         NotificationPushed       (none — ShouldBroadcast)   → Push to WebSocket
                                                                                      (instant, no queue)

Sales Order completed           (dispatched directly)    ---                        → SendSalesOrderCompleted
                                                                                      Email job (queued)
```

### Event Registration (Source of Truth)

All events are wired in `EventServiceProvider`:

```php
protected $listen = [
    Registered::class => [SendEmailVerificationNotification::class],
    Login::class      => [LogSuccessfulLogin::class],
    Logout::class     => [LogSuccessfulLogout::class],
    AuditLogEvent::class => [AuditLogListener::class],
];
```

Event auto-discovery is **disabled** (`shouldDiscoverEvents() → false`), so this map is the single source of truth.

### Queue System

| Component | Configuration |
|-----------|--------------|
| Driver | `database` (jobs table) |
| Retry after | 90 seconds |
| Failed jobs | Logged to `failed_jobs` table |

**Only one job exists:** `SendSalesOrderCompletedEmail`
- Dispatched `afterCommit()` — only runs after DB transaction succeeds
- Queue name: `emails`
- Retries: 3 attempts
- Backoff: 30 seconds between retries

---

## 18. Email System

### Configuration

| Setting | Value |
|---------|-------|
| Default mailer | `log` (writes to laravel.log, not actually sent) |
| SMTP ready | Yes (port 2525, env-driven) |
| From address | `hello@example.com` |

### The Only Email

**`SalesOrderCompletedMail`** — Sent to customers when their sales order is completed.

```
Sales Order status → "completed"
        │
        ▼
  SalesOrderController::completeSalesOrder()
        │
        ▼
  SendSalesOrderCompletedEmail::dispatch($order)
    ->afterCommit()
    ->onQueue('emails')
        │
        ▼
  Queue Worker picks up job
        │
        ▼
  SalesOrderCompletedMail renders Blade template
    (emails.sales_order_completed)
        │
        ▼
  Email sent (or logged, depending on driver)
```

---

## 19. Export System (CSV / Excel / PDF)

### How Exports Work

Controllers that support exporting use the `HasExport` trait, which delegates to `ExportService`:

| Format | Method | Technology |
|--------|--------|-----------|
| CSV | `exportCsv()` | Streamed response with UTF-8 BOM |
| Excel | `exportExcel()` | XML Spreadsheet 2003 format (.xlsx) |
| PDF/Print | `report()` | Blade view rendered as printable HTML (DomPDF-ready) |

### Models with Export Support

| Module | Exportable? | Columns |
|--------|:-----------:|---------|
| Products | ✅ | SKU, Name, Category, Unit Price, Current Stock, Description, Created At |
| Customers | ✅ | Name, Email, Phone, Address, Sales Orders count, Created At |
| Suppliers | ✅ | Company Name, Contact, Email, Phone, Address, Notes, PO count, Created At |
| Purchase Orders | ✅ | PO Number, Supplier, Date, Status, Total, Items, Creator, Created At |
| Sales Orders | ✅ | SO Number, Customer, Date, Delivery Date, Status, Total, Items, Creator, Created At |
| Stock Ledger | ✅ | Custom export (not via HasExport trait — streams directly) |

---

## 20. Hidden System Mechanics

### 1. Implicit Eloquent Model Events

When you call `Product::create()`, Laravel automatically fires:
- `creating` → `created` events
- The `Auditable` trait's `bootAuditable()` hooks into these
- The `ProductObserver` also hooks into `created`

**Order:** Trait boot hooks run first, then registered observers.

### 2. Global Middleware Effects

`HandleInertiaRequests` runs on **every authenticated request** and shares:
- `auth.user` (id, name, email, role, profile photo URL)
- `flash` (success/error messages from previous request)

This means every Vue page automatically has access to the current user without explicitly passing it.

### 3. Route Model Binding

`UserController` uses implicit route model binding:
```php
Route::put('/users/{user}', [UserController::class, 'update']);
// Laravel auto-resolves {user} to a User model instance
```

Other controllers use manual `findOrFail($id)` instead.

### 4. Soft Delete Mechanics

6 models use `SoftDeletes`: User, Products, Customer, Supplier, Purchase_Order, Sales_Order.

- `delete()` → Sets `deleted_at` timestamp (record hidden from queries)
- `restore()` → Clears `deleted_at` (record visible again)
- `forceDelete()` → Permanently removes from database
- `withTrashed()` → Query includes soft-deleted records (used in index pages with "Show deleted" toggle)

### 5. Transaction & Rollback Patterns

Critical operations use `DB::transaction()`:

| Operation | What's Inside the Transaction |
|-----------|------------------------------|
| Product creation | Create product + create initial stock ledger entry |
| PO creation | Create PO + create all PO items + calculate total |
| PO completion | Lock products + increase stock + create ledger entries + update status |
| SO creation | Create SO + create all SO items + calculate total |
| SO completion | Lock products + decrease stock (with negative prevention) + create ledger entries + update status |

If **any** step fails, **all** steps are rolled back — the database stays consistent.

### 6. Potential Duplication Triggers

⚠️ **Audit log duplication risk:** The `UserController` uses `User::withoutEvents()` when blocking/unblocking, then manually dispatches `AuditLogEvent`. This is intentional — it prevents the `Auditable` trait from firing a generic "updated" event that would conflict with the specific "blocked" event.

### 7. Phone Number Sanitization

Customer and Supplier form requests strip non-digit characters and leading zeros in `prepareForValidation()`. This affects the data before validation rules run.

### 8. Category Primary Key

The `Category` model uses a **non-incrementing string primary key** (e.g., "ELEC", "FOOD"). This means:
- Categories have custom IDs (not auto-increment integers)
- Changing a category ID requires deleting the old record and creating a new one
- Products reference categories by this string ID

### 9. Stock Validation on Sales Orders

`StoreSalesOrderRequest` includes a **custom validator** that checks each line item's quantity against `Products::current_stock`. If requested quantity exceeds available stock, the validation fails with a specific error per item.

---

## 21. Command Surface Reference

### PHP Artisan Commands

| Command | Purpose | When to Use |
|---------|---------|-------------|
| `php artisan serve` | Start local development server | During development |
| `php artisan migrate` | Run pending database migrations | After pulling new code or initial setup |
| `php artisan migrate:fresh` | Drop all tables and re-migrate | Reset database to clean state (destructive) |
| `php artisan migrate:rollback` | Undo last batch of migrations | Fix a bad migration |
| `php artisan db:seed` | Run database seeders | Populate initial data (3 test users) |
| `php artisan queue:listen` | Process background jobs | Required for email sending |
| `php artisan queue:work` | Process jobs (stops after queue empty) | Production queue worker |
| `php artisan cache:clear` | Clear application cache | After config changes |
| `php artisan config:clear` | Clear config cache | After .env changes |
| `php artisan route:list` | Show all registered routes | Debugging routing |
| `php artisan route:cache` | Cache route definitions | Production optimization |
| `php artisan event:list` | List events and listeners | Debugging event system |
| `php artisan make:model Name` | Generate a new model | Creating new entities |
| `php artisan make:controller Name` | Generate a new controller | Creating new controllers |
| `php artisan make:migration name` | Generate a new migration | Schema changes |
| `php artisan make:event Name` | Generate a new event | Creating new events |
| `php artisan make:listener Name` | Generate a new listener | Creating new listeners |
| `php artisan make:resource Name` | Generate a new API resource | Creating new transformers |
| `php artisan make:request Name` | Generate a form request | Creating new validators |
| `php artisan tinker` | Interactive PHP REPL | Debugging, testing queries |
| `php artisan pail` | Real-time log viewer | Monitoring logs during development |
| `php artisan storage:link` | Create public/storage symlink | Initial setup (uploaded files) |
| `php artisan key:generate` | Generate application encryption key | Initial setup |
| `php artisan reverb:start` | Start WebSocket server | Required for real-time notifications |
| `php artisan package:discover` | Discover Laravel packages | After `composer install` |
| `php artisan vendor:publish` | Publish package assets/configs | After installing packages |

### Composer Scripts (Shortcuts)

| Command | What It Does |
|---------|-------------|
| `composer setup` | Full project setup: install deps, copy .env, generate key, migrate, build assets |
| `composer dev` | Start all dev services concurrently: server, queue, logs, Vite (via `concurrently`) |
| `composer test` | Clear config cache + run PHPUnit tests |

### NPM Commands

| Command | Purpose | When to Use |
|---------|---------|-------------|
| `npm install` | Install JavaScript dependencies | After cloning or updating package.json |
| `npm run dev` | Start Vite dev server with HMR | During development (hot module replacement) |
| `npm run build` | TypeScript check + production build | Before deployment |

### NPX Usage (via `composer dev`)

```bash
npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" \
    "php artisan serve" \
    "php artisan queue:listen --tries=1 --timeout=0" \
    "php artisan pail --timeout=0" \
    "npm run dev" \
    --names=server,queue,logs,vite --kill-others
```

This runs 4 processes simultaneously:
1. **server** — PHP development server (blue)
2. **queue** — Background job processor (purple)
3. **logs** — Real-time log viewer (pink)
4. **vite** — Frontend build server with HMR (orange)

---

## 22. Infrastructure & Environment

### Environment Configuration (.env)

The `.env` file contains all environment-specific settings. Key variables:

| Variable | Purpose | Example |
|----------|---------|---------|
| `APP_NAME` | Application name | `WarehouseManagement` |
| `APP_ENV` | Environment mode | `local` / `production` |
| `APP_KEY` | Encryption key | `base64:...` (auto-generated) |
| `APP_DEBUG` | Show detailed errors | `true` (dev) / `false` (prod) |
| `APP_URL` | Base URL | `http://localhost:8000` |
| `DB_CONNECTION` | Database driver | `sqlite` / `mysql` |
| `DB_DATABASE` | Database path/name | `database/database.sqlite` |
| `QUEUE_CONNECTION` | Queue driver | `database` |
| `CACHE_STORE` | Cache driver | `database` |
| `SESSION_DRIVER` | Session storage | `database` |
| `MAIL_MAILER` | Email driver | `log` / `smtp` |
| `BROADCAST_CONNECTION` | WebSocket driver | `null` / `reverb` |
| `REVERB_APP_ID` | Reverb WebSocket app ID | (auto-generated) |
| `REVERB_APP_KEY` | Reverb WebSocket key | (auto-generated) |
| `REVERB_APP_SECRET` | Reverb WebSocket secret | (auto-generated) |

### Database Connection Flow

```
Controller → Eloquent Model → PDO Driver → SQLite/MySQL Database
                                    ↑
                              config/database.php
                                    ↑
                                .env file
```

- **Development:** SQLite (file-based, zero configuration)
- **Production-ready:** MySQL/MariaDB/PostgreSQL (env-driven switch)

### Queue Driver

```
Controller dispatches Job → Database "jobs" table → Queue Worker picks up → Job executes
                                                          ↓
                                                    Success? → Remove from table
                                                    Failure? → Retry (up to 3x)
                                                              → Move to "failed_jobs" table
```

### Mail Driver

- **Development:** `log` driver (emails written to `storage/logs/laravel.log`)
- **Production:** SMTP (port 2525, configurable via .env)
- **Failover:** smtp → log (if SMTP server unreachable)

### Authentication Behavior

- **Type:** Session-based (cookie authentication)
- **Storage:** Database-backed sessions (`sessions` table)
- **No API tokens** — Sanctum is installed but not configured as an auth guard
- **Session lifetime:** 120 minutes
- **Password confirmation timeout:** 3 hours

### API Middleware Structure

The web routes use this middleware stack:

```
Every request:
  ├── EncryptCookies
  ├── StartSession
  ├── VerifyCsrfToken
  ├── HandleInertiaRequests  ← Shares auth.user + flash data
  └── SubstituteBindings

Admin routes add:
  ├── auth                   ← Must be logged in
  └── role:admin             ← Must be admin role

Staff routes add:
  ├── auth                   ← Must be logged in
  └── role:staff             ← Must be staff role

Report API routes add:
  ├── auth                   ← Must be logged in
  └── throttle:60,1          ← Max 60 requests per minute
```

### Cache Usage

| What's Cached | TTL | Invalidation Trigger |
|--------------|-----|---------------------|
| Report data (charts, analytics) | 5 min | Model observers flush domain versions |
| Summary cards (KPIs) | 3 min | Same |
| Low stock items | 2 min | Same |

---

## 23. Database Schema & Migration Timeline

### Evolution Timeline

The database has evolved through **27 migrations**:

| Phase | Migrations | What Happened |
|-------|-----------|---------------|
| **Foundation** (1-3) | Users, Cache, Jobs | Core Laravel tables |
| **Domain Models** (4-11) | Customers → Sales Order Items | All business entity tables |
| **Feature Enhancement** (12-16) | Audit logs, Images, Categories | New features added |
| **Schema Refinement** (17-22) | Supplier constraints, PO/SO status expansion, Audit enhancements | Evolving requirements |
| **Real-time & UX** (23-26) | User management columns, Event labels, Notifications, Profile photos | Modern features |
| **Performance** (27) | Performance indexes | Optimization |

### Complete Table List

| Table | Purpose | Key Indexes |
|-------|---------|-------------|
| `users` | System users | email (unique), role |
| `products` | Inventory items | sku (unique), category_id, name |
| `categories` | Product groups | name (unique), custom varchar PK |
| `suppliers` | Vendors | email (unique), phone (unique) |
| `customers` | Buyers | email (unique), phone (unique) |
| `purchase_orders` | Incoming stock orders | po_number (unique), supplier_id, status |
| `purchase_order_items` | PO line items | purchase_order_id, product_id |
| `sales_orders` | Outgoing stock orders | so_number (unique), customer_id, status |
| `sales_order_items` | SO line items | sales_order_id, product_id |
| `stock_ledgers` | Inventory movement log | product_id, reference_type, movement_type |
| `audit_logs` | Activity trail | user_id, action, auditable_type/id |
| `app_notifications` | In-app notifications | user_id, is_read |
| `sessions` | Active sessions | user_id, last_activity |
| `cache` | Application cache | key |
| `cache_locks` | Cache locking | key |
| `jobs` | Queued jobs | queue |
| `job_batches` | Job batches | — |
| `failed_jobs` | Failed queue jobs | uuid (unique) |
| `password_reset_tokens` | Password reset | email (PK) |

### Seeded Data

3 default users are created by `UserSeeder`:

| Name | Email | Role | Password |
|------|-------|------|----------|
| Admin User | `admin@warehouse.com` | admin | `password` |
| Staff User | `staff@warehouse.com` | staff | `password` |
| John Doe | `john@warehouse.com` | staff | `password` |

---

## 24. Deployment & Runtime

### What Happens During `npm run build`

```
1. vue-tsc runs
   → Type-checks all TypeScript files
   → Fails build if type errors exist

2. vite build runs
   → Resolves all imports (Vue, Pinia, Tailwind, etc.)
   → Compiles .vue SFCs → JavaScript
   → Processes Tailwind CSS → optimized CSS
   → Tree-shakes unused code
   → Minifies everything
   → Generates hashed filenames (cache-busting)
   → Outputs to public/build/
   → Creates manifest.json (maps source → built files)
```

### What Happens During `php artisan migrate`

```
1. Laravel reads database/migrations/ directory
2. Checks "migrations" table for already-run migrations
3. Runs any pending migrations in chronological order
4. Each migration:
   → Creates/alters/drops database tables
   → Adds columns, indexes, foreign keys
   → Records itself in the "migrations" table
5. If any migration fails, that specific migration is rolled back
```

### What Happens When the Queue Worker Runs

```
1. Worker connects to the "jobs" database table
2. Polls for available jobs
3. For each job:
   → Deserializes the job class
   → Executes the handle() method
   → On success: deletes from jobs table
   → On failure: increments attempts
     → Under max retries? → Waits backoff period → Retries
     → Over max retries? → Moves to failed_jobs table
4. Continues polling for new jobs
```

### What Happens When the App is Served

```
1. Web server (Apache/Nginx) receives HTTP request
2. All requests routed to public/index.php
3. Laravel bootstrap loads:
   → Composer autoloader
   → Application container
   → Service providers
   → Middleware
4. Request passes through middleware pipeline
5. Router matches URL to controller method
6. Controller processes request
7. For Inertia requests:
   → First visit: full HTML page with embedded page data
   → Subsequent visits: JSON-only response (partial reload)
8. Browser receives response
9. Vue hydrates/updates the component
```

### How Frontend Assets Connect to Backend

```
resources/js/app.ts  ──(Vite build)──►  public/build/assets/app-[hash].js
resources/css/app.css ──(Vite build)──► public/build/assets/app-[hash].css
                                              │
                                              ▼
                                     public/build/manifest.json
                                              │
                                              ▼
                              resources/views/app.blade.php
                              uses @vite('resources/js/app.ts')
                              to load the correct hashed file
```

### API Versioning

- **v1** routes (in `web.php`): Session-authenticated internal endpoints for the dashboard (`/api/v1/reports/*`)
- **v2** routes (in `api.php`): Placeholder for future external/mobile API with token-based auth (Sanctum)

The v1 endpoints are not traditional API routes — they're web routes that return JSON, protected by session cookies. The v2 namespace is reserved for future external consumers.

---

## 25. Performance & Safety Considerations

### Performance-Sensitive Areas

| Area | Risk | Mitigation |
|------|------|-----------|
| Report queries | Complex aggregations on large datasets | CacheService with domain-versioned TTLs |
| Audit log writes | Every model change writes an audit log | AuditLogListener uses try/catch (failures don't crash requests) |
| Stock operations | Concurrent orders could cause race conditions | `lockForUpdate()` + `DB::transaction()` |
| Notification broadcasting | Many notifications could overwhelm WebSocket | `ShouldBroadcastNow` (bypasses queue for instant delivery) |
| Product search in modals | Frequent API calls during typing | Client-side debounce (300ms) |
| Dashboard charts | Multiple API calls on page load | Parallel `useReport()` calls with independent loading states |
| Export large datasets | Memory usage for CSV/Excel generation | Streamed responses (data sent in chunks) |

### Security Measures

| Measure | Implementation |
|---------|---------------|
| CSRF protection | Automatic via Laravel middleware |
| Password hashing | Bcrypt via `hashed` cast |
| Rate limiting | Login: 5/email+IP; Reports API: 60/min |
| Auto-blocking | 3 failed login attempts → auto-block user |
| Sensitive field filtering | Passwords excluded from audit logs |
| SQL injection prevention | Eloquent ORM parameterized queries |
| File upload validation | Type (jpg/png/webp), size (max 2MB) |
| Session security | HTTP-only cookies, same-site lax |
| Input sanitization | Phone numbers stripped of non-digits |
| Authorization gates | UserPolicy for user management |
| Data scoping | Staff only sees own sales orders |

### Architectural Strengths

1. **Separation of concerns** — Each model, controller, service, and trait has a single responsibility
2. **Event-driven architecture** — Side effects (audit logs, cache invalidation) are decoupled from main logic
3. **Domain-versioned caching** — O(1) invalidation that works with any cache driver
4. **Immutable audit trail** — Cannot be tampered with (blocks update/delete)
5. **Transaction safety** — Critical operations are atomic
6. **Progressive enhancement** — WebSocket with HTTP polling fallback
7. **Type safety** — TypeScript on the frontend catches errors at build time
8. **Consistent API responses** — `ApiResponse` trait ensures uniform JSON structure

### Areas for Potential Improvement

1. **No formal API authentication** — Sanctum is installed but unused; external API consumers would need this
2. **Duplicate controller logic** — Admin/Staff controllers share significant code; a base controller or service layer could reduce duplication
3. **Limited test coverage** — Tests directory exists but test files are sparse
4. **Single policy** — Only Users have a formal policy; other modules rely solely on route middleware
5. **Mixed TypeScript adoption** — Some Vue files use TypeScript, others don't
6. **No database indexing review** — Performance indexes were added late (migration #27); earlier migrations may have missed optimization opportunities

---

## Appendix: Quick Reference Cards

### For a New Developer

1. Clone the repo
2. Run `composer setup` (installs everything, migrates, builds)
3. Run `composer dev` (starts all 4 services)
4. Visit `http://localhost:8000`
5. Login: `admin@warehouse.com` / `password`

### For a QA Tester

| Test Area | Where to Look |
|-----------|--------------|
| Login flow | `/login` — test blocked users, failed attempts, auto-blocking |
| Product CRUD | `/admin/products` — create, edit, soft delete, restore, force delete |
| Purchase Orders | `/admin/purchase-orders` — create, status transitions, stock increase on completion |
| Sales Orders | `/admin/sales-orders` — create (stock validation), status transitions, stock decrease on completion |
| User Management | `/admin/users` — create, edit, block, unblock, delete (requires admin password) |
| Audit Trail | `/admin/audit-logs` — verify all actions are logged |
| Stock Ledger | `/admin/stock-ledger` — verify movements match order completions |
| Reports | `/reports` — verify charts display correct data |
| Staff restrictions | Login as `staff@warehouse.com` — verify limited access |
| Exports | Download CSV/Excel/PDF from Products, Customers, Suppliers, POs, SOs |

### For a Project Manager

This system manages the complete warehouse lifecycle:
- **Who** supplies products (Suppliers)
- **What** products exist and how many (Products + Stock)
- **How** products come in (Purchase Orders)
- **Who** buys them (Customers)
- **How** products go out (Sales Orders)
- **Everything** is tracked (Audit Logs + Stock Ledger)
- **Everyone** has appropriate access (Role-based: Admin vs Staff)
- **Reports** show business performance (Dashboard + Charts)

---

*Document generated: February 2026*  
*System: Warehouse Management System ("Jounteux")*  
*Stack: Laravel 12 + Inertia.js 2 + Vue 3 + TypeScript + Tailwind CSS 3*
