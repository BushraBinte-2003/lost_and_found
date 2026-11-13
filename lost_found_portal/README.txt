==============================================================
  CAMPUS LOST & FOUND PORTAL  -  Setup Guide (Windows + XAMPP)
==============================================================
Technology: HTML5, CSS3, JavaScript, PHP (PDO), MySQL, Apache (XAMPP)
No frameworks. No Composer. No Node.js.

--------------------------------------------------------------
IMPORTANT - TESTING STATUS
--------------------------------------------------------------
This project was written without access to PHP, Apache or MySQL,
so it has NOT been executed or tested yet. Please follow the
"TEST CHECKLIST" at the bottom of this file after installing.
If something fails, copy the exact error message and ask for help.

--------------------------------------------------------------
1. INSTALL XAMPP
--------------------------------------------------------------
1. Download XAMPP for Windows from https://www.apachefriends.org
   (choose a version with PHP 8.x).
2. Run the installer. Keep the defaults (Apache, MySQL, PHP, phpMyAdmin).
3. Open "XAMPP Control Panel" from the Start menu.

--------------------------------------------------------------
2. START APACHE AND MYSQL
--------------------------------------------------------------
In the XAMPP Control Panel click "Start" next to:
   - Apache
   - MySQL
Both names should turn green. (If Apache will not start, another
program may use port 80 - close Skype/IIS or change Apache's port.)

--------------------------------------------------------------
3. COPY THE PROJECT FOLDER
--------------------------------------------------------------
1. Extract Lost_and_Found_Portal_Complete.zip.
2. Copy the folder  lost_found_portal  to:
        C:\xampp\htdocs\
   Final result:  C:\xampp\htdocs\lost_found_portal\index.php
   (The folder name must stay exactly "lost_found_portal".)

--------------------------------------------------------------
4. CREATE THE DATABASE (phpMyAdmin)
--------------------------------------------------------------
1. Open  http://localhost/phpmyadmin  in your browser.
2. Click the "Import" tab at the top.
3. Click "Choose File" and select:
        C:\xampp\htdocs\lost_found_portal\database\lost_found_portal.sql
4. Scroll down and click "Import" (or "Go").
5. In the left list you should now see the database
   "lost_found_portal" with 3 tables: users, reports, claims.

--------------------------------------------------------------
5. DATABASE CONNECTION SETTINGS
--------------------------------------------------------------
Open  config\database.php  in a text editor (Notepad, VS Code).
The default values match a normal XAMPP installation:
        user: root     password: (empty)     host: localhost
Only change them if you set a MySQL password yourself.

--------------------------------------------------------------
6. CREATE THE FIRST ADMIN ACCOUNT (secure)
--------------------------------------------------------------
1. Open  http://localhost/lost_found_portal/setup/create_admin.php
2. Fill in the admin name, email, login ID and a strong password.
3. Click "Create Admin Account".
   (The password is stored hashed with password_hash(). It is not
   written in any file.)
4. IMPORTANT: afterwards DELETE the folder
        C:\xampp\htdocs\lost_found_portal\setup
   The page also disables itself once an admin exists.

If you forget the admin password: open phpMyAdmin > lost_found_portal >
users, delete the row whose role is ADMIN, then run the setup page again
(restore the "setup" folder from the ZIP first).

--------------------------------------------------------------
7. OPEN THE WEBSITE
--------------------------------------------------------------
        http://localhost/lost_found_portal/

--------------------------------------------------------------
8. REGISTER A NORMAL USER
--------------------------------------------------------------
1. On the login page click "Create an account".
2. Fill in: Full Name, Student ID, Email, Phone, Login ID, Password,
   Confirm Password. Click "Register".
3. Public registration ALWAYS creates a normal User, never an Admin.

--------------------------------------------------------------
9. LOG IN
--------------------------------------------------------------
USER :  http://localhost/lost_found_portal/
        (Login ID or email + password)
ADMIN:  http://localhost/lost_found_portal/admin/login.php
        (the admin account you created in step 6)

--------------------------------------------------------------
HOW THE PROJECT WORKS (to explain to your teacher)
--------------------------------------------------------------
* Report workflow: a user submits a LOST or FOUND report -> it is saved
  in MySQL with status PENDING -> only the owner and the admin can see
  it -> the admin APPROVES or REJECTS -> only APPROVED reports appear in
  "Browse Items" and show contact details.
* FOUND reports need a photo; LOST reports can be submitted without one
  (a placeholder icon is shown instead).
* Claims: on an approved FOUND item a user can click "Claim this item".
  The reporter can read the claim, the admin approves/rejects it.
  Approving a claim changes the item status to MATCHED.
* Security: password_hash()/password_verify(), PDO prepared statements,
  CSRF tokens on all POST forms, output escaping with e() (htmlspecialchars),
  session_regenerate_id() after login, role check on every page
  (require_user() / require_admin() in includes/auth.php), safe image
  upload (real type check, 5 MB limit, random file names, PHP execution
  blocked in uploads/.htaccess), config/includes/database folders blocked
  from the browser with .htaccess.

FOLDER GUIDE
  index.php, register.php, logout.php ... public pages
  config/database.php ................... database settings + PDO connection
  includes/functions.php ................ helpers (CSRF, upload, escaping)
  includes/auth.php ..................... login/logout and page protection
  includes/header.php, footer.php ....... shared page layout
  includes/report-form.php .............. form shared by lost/found reports
  user/ ................................. all user pages
  admin/ ................................ all admin pages
  setup/create_admin.php ................ one-time admin creation
  database/lost_found_portal.sql ........ database structure
  uploads/ .............................. uploaded photos
  assets/css, assets/js ................. styling and JavaScript (lightbox)

TROUBLESHOOTING
* "Database connection failed": MySQL is not started, the SQL file was not
  imported, or the password in config/database.php is wrong.
* Blank page or 404: the folder is not at C:\xampp\htdocs\lost_found_portal.
  If you rename the folder, also change BASE_URL in config/database.php.
* Image upload fails: make sure the "uploads" folder exists (the project does not need
  any special PHP extension for uploads). Restart Apache after changing
  php.ini.
* Pictures not showing: check that the uploads folder contains the files.

--------------------------------------------------------------
TEST CHECKLIST (do this once after installing)
--------------------------------------------------------------
 1. DB connection: open http://localhost/lost_found_portal/ - page loads
    without a "Database connection failed" message.
 2. Register a user; check phpMyAdmin > users shows the new row. Register
    again with the same Login ID -> must be rejected.
 3. Log in with that user -> user dashboard. Log out. Log in again.
 4. Run setup/create_admin.php; log in at /admin/login.php -> admin
    dashboard. While logged in as a normal user, open
    /admin/dashboard.php -> "403 Access denied".
 5. As user A: Report a Found Item WITH a photo -> status PENDING in
    My Reports. As user B: it must not appear in Browse Items.
 6. Report a Lost Item without a photo (works), and another with a photo.
 7. As admin: Pending > Approve one report, Reject another. User B now
    sees only the approved one in Browse Items.
 8. Open an approved item with a photo, click the photo: full-screen
    viewer opens; test the X button, clicking outside, and the ESC key.
 9. Browse Items: search by name, filter Lost/Found, category, location.
10. Approved FOUND item details: contact name/phone shown, "Call Now"
    (tel:) and "Email" (mailto:) buttons work.
11. My Reports shows your own statuses. Type another user's pending report
    URL (user/item-details.php?id=N) -> "Report not found".
12. Claims: as user B click "Claim this item" and submit; as admin open
    Claims > Approve -> item becomes MATCHED in the database.
