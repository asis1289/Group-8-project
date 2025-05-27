 HMS Installation Manual

This manual explains how to install and run the Hospital Management System (HMS) on your local computer using XAMPP or WAMP.

---

✅ Requirements

- PHP 7.x or later
- MySQL or MariaDB
- XAMPP or WAMP installed
- Browser (Chrome, Firefox)

---

 📂 Step-by-Step Installation

 1. Start Local Server
- Open XAMPP or WAMP
- Start **Apache** and **MySQL** services

 2. Copy Files
- Copy the full HMS project folder (named `GP8` or `HMS`) into:
  - `C:\xampp\htdocs\` (for XAMPP)
  - `C:\wamp64\www\` (for WAMP)

 3. Import the Database
- Open your browser and go to: `http://localhost/phpmyadmin`
- Click "New" to create a database named: `hms_db`
- Click "Import" and upload the file `hms_db.sql` from your project folder
- Click "Go" to import

 4. Run the Application
- Open your browser and go to: `http://localhost/GP8/index.php`

---

 🧪 Default Login Details (for testing)

| Role        | Email             | Password  |
|-------------|------------------|------------|
| Admin       | admin             | admin     |
| Employee    | emp1              | emp1      |
| Patient     | Register manually | N/A       |

---

⚠️ Troubleshooting

- Apache/MySQL not starting: Check if ports 80/3306 are in use
- Database import fails: Ensure `hms_db.sql` has no syntax issues
- PDF not generating: Make sure the `fpdf/` folder is included and path is correct

---

📂 Folder Checklist



GP8/
├── index.php                  Homepage
├── register.php               Patient registration page
├── login.php                  Login page for all users
├── appointment.php            Appointment booking logic
├── view_bills.php             Bill viewing and PDF generation
├── patient_dashboard.php      Patient's dashboard
├── doctor_dashboard.php       Doctor/staff dashboard
├── admin_dashboard.php        Admin dashboard
├── generate_bill.php          Generates and downloads bill as PDF
├── fpdf/                      PDF library folder
│   └── fpdf.php
├── includes/                  Configuration and session files
│   ├── db_config.php          MySQL connection settings
│   └── session.php            Session management
├── css/                       Stylesheets
│   └── style.css
├── js/                        JavaScript files
│   └── main.js
├── hms_db.sql                 Database SQL import file
└── README.md                  Optional: Project overview for GitHub
