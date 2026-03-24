# 🎬 Rental Inventory System

<div align="center">

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge)
![Status](https://img.shields.io/badge/Status-Academic%20Project-2563eb?style=for-the-badge)

**A web-based equipment rental management system for the Communication and Design Department at Bilkent University.**

[Features](#-features) · [Installation](#-installation--setup) · [User Roles](#-user-roles) · [Roadmap](#️-roadmap)

</div>

---

## 📖 About the Project

The **Rental Inventory System** is a full-stack web application designed to digitize and streamline the process of borrowing departmental production equipment — cameras, audio gear, lighting rigs, drones, and more — at Bilkent University's Communication and Design (COMD) Department.

Before this system, equipment requests were handled manually, leading to scheduling conflicts, unclear approval chains, and difficulty tracking equipment status. This project addresses those problems through:

- A **structured two-stage approval workflow** involving course instructors and the department chair
- **Real-time equipment status tracking** (available, checked out, maintenance, retired)
- **Role-based access control** so each stakeholder sees only what is relevant to them
- **Email notifications** at every stage of the request lifecycle
- A **comprehensive admin panel** for full oversight of users, equipment, and borrow history

> **Note:** This project was developed as a course requirement for the COMD department at Bilkent University and is intended for instructor review and demonstration purposes. It is not deployed to a live server.

---

## ✨ Features

| Feature | Description |
|---|---|
| 🔐 Role-based Auth | Separate dashboards and permissions for Admin, Chair, Instructor, and Student |
| 📦 Equipment Catalog | Browse items by category with images, specs, and availability status |
| 📋 Rental Requests | Students submit requests; instructors and chairs approve or reject with comments |
| ✅ Two-stage Approval | Requests require sign-off from both the course instructor and department chair |
| 🔔 Notifications | Automated email alerts on approval, rejection, and item availability |
| 📊 Admin Dashboard | Full control over users, equipment, categories, courses, and borrow history |
| 📥 CSV Import | Bulk import equipment records from Excel/CSV files |
| 👤 User Profiles | Each user can update their profile and change their password |
| 📅 Overdue Tracking | Checked-out items past their due date are highlighted automatically |
| 🔍 Guest Browsing | Anyone can browse the equipment catalog without logging in |

---

## 🛠️ Built With

| Technology | Purpose |
|---|---|
| PHP 7.4+ | Backend logic and server-side rendering |
| MySQL 5.7+ | Relational database |
| Bootstrap 5.3 | Responsive frontend UI |
| Bootstrap Icons | Icon library |
| Vanilla JS | Client-side interactions |

---

## 🚀 Installation & Setup

> This project requires a local server environment such as **XAMPP** or **MAMP**.

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP / MAMP / any local PHP server

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/jatuns/rental-inventory.git
   ```

2. **Move to your server's root folder**
   ```
   XAMPP → htdocs/rental-inventory
   MAMP  → htdocs/rental-inventory
   ```

3. **Import the database**
   - Open **phpMyAdmin**
   - Create a new database named `rental_inventory`
   - Import the file: `config/database.sql`

4. **Configure the database connection**
   - Duplicate `config/database.example.php` and rename it to `database.php`
   - Fill in your local credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'rental_inventory');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

5. **Start the server**
   - Start Apache and MySQL via XAMPP
   - Visit: `http://localhost/rental-inventory`

### Demo Credentials

| Role | Email | Password |
|---|---|---|
| Admin | admin@university.edu | password |
| Chair | chair@university.edu | password |
| Instructor | instructor@university.edu | password |
| Student | student@university.edu | password |

---

## 👥 User Roles

| Role | Capabilities |
|---|---|
| **Admin** | Full access — manages users, equipment, categories, courses, checkout/return |
| **Chair** | Reviews and approves/rejects all rental requests at department level |
| **Instructor** | Approves student requests linked to their own courses |
| **Student** | Browses equipment, submits requests, tracks approval status, cancels pending requests |

---

## 📁 Project Structure

```
rental-inventory/
├── admin/              # Admin panel (dashboard, equipment, users, requests, checkout, courses, import)
├── instructor/         # Instructor dashboard and approval interface
├── chair/              # Department chair dashboard and approval interface
├── student/            # Student dashboard, new request, edit, cancel
├── guest/              # Public equipment browsing (no login required)
├── config/
│   ├── database.example.php   # DB config template
│   └── database.sql           # Full schema + seed data
├── includes/
│   ├── auth.php        # Login, logout, session, role checks
│   ├── functions.php   # Email, formatting, upload, status helpers
│   ├── header.php      # Shared HTML header + navbar
│   └── footer.php      # Shared HTML footer
├── assets/
│   ├── css/style.css   # Custom stylesheet
│   ├── js/main.js      # Client-side JS
│   └── images/         # Static assets
├── uploads/            # User-uploaded equipment images (gitignored)
├── index.php           # Landing page + login
├── profile.php         # User profile and password change
├── subscribe.php       # Availability notification handler
└── logout.php          # Session destroy + redirect
```

---

## 🗺️ Roadmap

- [ ] Pagination on equipment and request tables
- [ ] Multi-item requests — one request covering multiple equipment items
- [ ] Calendar view for scheduling and availability conflicts
- [ ] Barcode / QR code scanning for checkout and return
- [ ] XLSX import via PhpSpreadsheet (currently CSV only)
- [ ] Dark mode support
- [ ] Student ID card integration for authentication

---

## 🤝 Contributing

This is an academic project, but feedback and suggestions are welcome.

1. Fork the repository
2. Create a feature branch
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. Commit your changes
   ```bash
   git commit -m "Add: your feature description"
   ```
4. Push to your branch
   ```bash
   git push origin feature/your-feature-name
   ```
5. Open a Pull Request and describe what you changed and why

Please keep commits focused and PRs small — one feature or fix per PR.

---

## 🎓 Academic Context

> **Institution:** Bilkent University  
> **Department:** Communication and Design (COMD)  
> **Project Type:** Course Project  
> **Purpose:** Developed for educational purposes; intended for instructor review, not live deployment

---

## 👤 Author

**Barış** — [@jatuns](https://github.com/jatuns)

---

## 📄 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.
