**Zerodha Admin Panel - Project Requirements (Laravel Version)**

**Version:** Final
**Date:** May 25, 2025

---

### 1. What We're Building: Project Overview

We are building a backend system called the **Zerodha Admin Panel**, designed for a solo administrator to manage and automate trading on up to five Zerodha accounts.

**Technology Stack:**

* **Backend:** Laravel 12 (PHP)
* **Frontend (Optional UI):** Blade with Velzon theme
* **Database:** MySQL
* **Live Price Caching:** Redis
* **Containerization:** Docker + Docker Compose

---

### 2. Main Goals

* Provide a secure, easy-to-use admin panel
* Automate trade execution based on predefined rules
* Show real-time order and price updates
* Ensure system reliability and maintainability

---

### 3. Who Will Use It?

* A single administrator

---

### 4. What Needs to Be Built (Scope)

* Laravel API backend with all trading logic
* Redis integration for live price streaming
* MySQL database schema
* Excel order processing
* Zerodha API integration
* Dockerized setup

---

### 5. System Architecture

**Docker Services:**

* `caddy`: Web traffic manager (HTTPS, routing)
* `backend`: Laravel 12 app (PHP)
* `ticker`: Python service that fetches live prices from Zerodha and pushes to Redis
* `monitor_orders`: Python worker that places buy/sell orders
* `sync_positions`: Python worker to sync open positions
* `mysql`: Main database
* `redis`: In-memory cache for live ticks
* `phpmyadmin`: (optional) MySQL GUI for developers

**Data Flow:**

* `ticker` pushes live prices to Redis
* `monitor_orders` and `backend` consume Redis ticks
* `backend` handles admin functions and stores to MySQL

---

### 6. Key Features

#### 6.1 Admin Login

* Laravel-based login with hashed password for 1 admin

#### 6.2 Zerodha Account Setup

* Add up to 5 Zerodha accounts (name, API key/secret, access token)
* Enable/disable account

#### 6.3 General Settings (admin\_settings table)

* Buy logic: `fixed_percent` or `offset_ltp`
* Buy %, Stoploss %
* Auto-sell cutoff time (e.g., 15:20)

#### 6.4 Order Management

* Upload Excel with: Symbol, Target %, Zerodha Account ID, Qty, Product (MIS/CNC)
* Laravel parses file, fetches LTP via Redis, calculates target/SL, stores as `pending`
* Reject duplicate `pending` orders for same symbol+account

#### 6.5 Order Monitoring (`monitor_orders`)

* Buys if live price <= target price
* Sells all bought positions after cutoff time
* Logs every action (success/failure) to `order_logs`
* Halts if Redis/ticker issues

#### 6.6 GTT Orders

* View-only fetch from Zerodha API
* Not stored locally

#### 6.7 Watchlist & Live Prices

* `watchlist_symbols`: admin-managed list
* `instruments`: maps symbol → Zerodha token
* `ticker` fetches prices, stores as Redis key `tick:<token>` with JSON: `{ ltp, time }`
* `tick_streamer.py`: simulates prices offline

#### 6.8 Background Logs

* All worker scripts log status + timestamps to `cron_logs`

---

### 7. Database Tables

* `users`: Admin login
* `admin_settings`: System-level config
* `zerodha_accounts`: Trading credentials
* `instruments`: Token mappings
* `watchlist_symbols`: Tracked symbols
* `orders`: Uploaded orders with status
* `order_logs`: Order history
* `positions`: Zerodha open positions
* `cron_logs`: Background job results

---

### 8. Non-Functional Requirements

* **Speed:** < 500ms from price to order
* **Scalability:** Up to 5 Zerodha accounts
* **Security:** Store API secrets securely
* **Reliability:** Pause tasks on failure
* **Maintainability:** Clean Laravel + Docker setup

---

### 9. Laravel Web Routes (Blade UI)

* `GET /login` — Show login page
* `POST /login` — Submit login form
* `POST /logout` — Logout
* `GET /accounts` — View Zerodha accounts
* `GET /accounts/create` — Add Zerodha account
* `POST /accounts` — Save new account
* `GET /accounts/{id}/edit` — Edit account
* `PUT /accounts/{id}` — Update account
* `GET /settings` — View admin settings
* `PUT /settings` — Update settings
* `GET /orders` — View all orders
* `GET /orders/upload` — Upload Excel
* `POST /orders/import` — Process Excel upload
* `DELETE /orders/{id}` — Cancel an order
* `GET /gtt-orders` — View GTT orders
* `GET /watchlist` — View watchlist
* `POST /watchlist` — Add symbol
* `DELETE /watchlist/{id}` — Remove symbol
* `GET /positions` — View open positions
* `GET /order-logs` — View order logs
* `GET /cron-logs` — View background task logs

**WebSocket Channels:**

* `/ws/ticks` — price updates
* `/ws/notifications` — logs, trade alerts

---

### 10. Important Notes

* Excel must include: Symbol, Target %, Zerodha Account ID, Qty, Product
* Orders are immutable after upload
* No retry logic (yet)
* Redis must be healthy; else, monitor\_orders halts
* No historical ticks stored — only live data

---

### 11. Deliverables

* Laravel source code (API + Blade)
* Blade + Velzon UI
* Dockerfile + docker-compose.yml
* init.sql (for MySQL tables)
* API reference or Swagger JSON
* Readme with setup guide

---

### 12. Assumptions

* Client will provide Zerodha API credentials
* Frontend uses Laravel Blade views and controller routes
* instruments table will be pre-filled or populated via script

---

### 13. Future Enhancements (Out of Scope)

* Retry failed orders
* Admin UI order placement (manual)
* Support more than 5 accounts
* Historical performance reporting
* User roles/permissions
