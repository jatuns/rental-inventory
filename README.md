# 🎬 Rental Inventory System

A web-based equipment rental management system developed for the **Communication and Design Department at Bilkent University**. This system allows students to request equipment rentals, instructors to manage approvals, and administrators to oversee the entire inventory.

---

## 📌 About the Project

This project was built as part of a course requirement for the **Communication and Design (COMD) Department at Bilkent University**. The goal was to design and develop a functional inventory management system that streamlines the process of borrowing and returning departmental equipment such as cameras, audio gear, and other production tools.

---

## ✨ Features

- 🔐 **Role-based authentication** — Separate dashboards for Admin, Instructor, Chair, and Student
- 📦 **Equipment management** — Browse available items by category with images
- 📋 **Rental requests** — Students can submit, edit, and cancel requests
- ✅ **Approval workflow** — Instructors and chairs review and approve/reject requests
- 👤 **User profiles** — Each user has a personal profile page
- 📊 **Admin dashboard** — Full control over users, equipment, and categories

---

## 🛠️ Built With

| Technology | Purpose |
|---|---|
| PHP | Backend logic |
| MySQL | Database |
| Bootstrap 5 | Frontend UI |
| Bootstrap Icons | Icon library |

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
   git clone https://github.com/YOUR_USERNAME/rental-inventory.git
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
   - Update with your local credentials:
   ```php
   $host = 'localhost';
   $dbname = 'rental_inventory';
   $username = 'root';
   $password = '';
   ```

5. **Run the project**
   - Start Apache and MySQL in XAMPP
   - Visit: `http://localhost/rental-inventory`

---

## 👥 User Roles

| Role | Description |
|---|---|
| **Admin** | Manages all users, equipment, and categories |
| **Chair** | Reviews and approves rental requests at department level |
| **Instructor** | Approves student requests within their courses |
| **Student** | Browses equipment and submits rental requests |

---

## 📁 Project Structure

```
rental-inventory/
├── admin/          # Admin panel pages
├── instructor/     # Instructor dashboard
├── chair/          # Chair dashboard
├── student/        # Student dashboard
├── guest/          # Guest/public pages
├── config/         # Database configuration & SQL
├── includes/       # Shared functions and auth
├── assets/         # CSS, JS, images
├── uploads/        # User-uploaded equipment images
├── index.php       # Landing page & login
├── profile.php     # User profile page
└── logout.php      # Logout handler
```

---

## 🎓 Academic Context

> **Institution:** Bilkent University  
> **Department:** Communication and Design (COMD)  
> **Project Type:** Course Project  

---

## 📄 License

This project was developed for educational purposes at Bilkent University. All rights reserved.
