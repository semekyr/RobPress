# Web Application Vulnerability Report

This document outlines a series of vulnerabilities discovered in a web application, along with the exact locations where they were found and the steps taken to remediate each issue. The vulnerabilities addressed include SQL injection, XSS, insecure uploads, parameter manipulation, and more. All fixes follow secure coding practices such as input sanitization, access control validation, file permission tightening, and secure session handling.

---


## Vulnerability 1: SQL Injection


**What I found and where:**

**Find #1:**  
The search function in the Blog Controller class was vulnerable to SQL injection because user input was directly concatenated into the SQL query string without any sanitization or parameterization.  
**Find #2:**  
The login function in the AuthHelper class was vulnerable as the username and password parameter were also directly concatenated into the SQL query without any sanitization or parameterization.  
**Find #3:**  
The fetch function in the GenericModel class was vulnerable to SQL injection because it directly accepted numeric conditions without proper validation or sanitization.

**How I resolved it:**

- **Solution to Find #1:** Replaced raw query with a prepared statement using parameterized queries.  
- **Solution to Find #2:** Used placeholders for input values.  
- **Solution to Find #3:** Ensured the condition is formatted as an associative array and sanitized using the `prepare()` method.

---


## Vulnerability 2: Information Exposure


**What I found and where:**

**Find #1:**  
Backup and temp files (.bak, .swp, .cfg, etc.) were accessible on the server and exposed sensitive info.

**How I resolved it:**

- Deleted unnecessary files and added `.htaccess` rules to restrict access to critical ones.

---


## Vulnerability 3: XSS


**What I found and where:**

XSS was found in 9 different modules including Search, Contact, Blog Post, Categories, Comments, Pages, User Registration, Login, and Settings.

**How I resolved it:**

- Used `htmlspecialchars`, `HTMLPurifier`, and `h()` function to sanitize all user input and output across both controller and view files.

---


## Vulnerability 4: Insecure Upload


**What I found and where:**

- No file type/content checks  
- Insecure file permissions (0666)  
- Unsanitized filenames  
- No file size restrictions  

**How I resolved it:**

- Validated file types/extensions and MIME  
- Set permissions to 0644  
- Generated unique filenames  
- Capped file size to 69MB

---


## Vulnerability 6: Authorisation Bypass


**What I found and where:**

- `beforeRoute()` lacked permission checks  
- `reset()` allowed blog reset without auth  
- `promote()` allowed self-promotion to admin  

**How I resolved it:**

- Enforced role-based access control in `beforeRoute()`  
- Verified admin privilege before reset  
- Removed `promote()` functionality

---


## Vulnerability 7: Internal Information


**What I found and where:**

Error views (`error.htm`, `errorer.htm`) exposed stack traces and file paths.

**How I resolved it:**

- Stack traces now show only in debug mode.

---


## Vulnerability 8: Parameter Manipulation


**What I found and where:**

- URL manipulation could access non-existent or unpublished resources.

**How I resolved it:**

- Added existence and authorization checks before fetching data.

---


## Vulnerability 9: Application Logic


**What I found and where:**

- Hidden 'to' field in emails could be altered  
- Unpublished posts were viewable via URL  

**How I resolved it:**

- Hardcoded email addresses and validated fields  
- Checked publish date and role level before viewing posts

---


## Vulnerability 10: Out of Date Software


**What I found and where:**

Used outdated versions of FatFree (v3.8.2) and CKEditor (v4.20.1)

**How I resolved it:**

- Updated FatFree to 3.9 and CKEditor to 4.22.1  
- Removed insecure pages and improved error handling

---


## Vulnerability 11: File Inclusion


**What I found and where:**

- `fetch()` in PagesModel allowed directory traversal via unsanitized `pagename`.

**How I resolved it:**

- Used `basename()` to strip dangerous path components.

---


## Vulnerability 12: Insecure Cookies and Sessions


**What I found and where:**

- Weak cookie encryption  
- No HttpOnly/Secure flags  
- Session IDs generated with `md5(user_id)`

**How I resolved it:**

- AES-256-CBC encryption for cookies  
- Stored cookies in DB with expiry and validation  
- Added HttpOnly and Secure flags  
- Used `sha256(uniqid(user_id))` for session IDs

---


## Vulnerability 13: Open Redirects


**What I found and where:**

- Used `$_GET['from']` for redirects without validation

**How I resolved it:**

- Used encrypted `previousUrl` cookie with AES-256-CBC  
- Redirects only to internal validated locations  
- Cookie is removed after redirection

---


## Vulnerability 15: Anything Else


**What I found and where:**

1. No brute-force protection  
2. Password hashes exposed in CSV exports  
3. Poor API key management  
4. Passwords stored in plaintext  

**How I resolved it:**

- Added login attempt tracker + IP blocking in `AuthHelper`  
- Removed password from CSV export  
- Stored user API keys in DB, fetched dynamically  
- Hashed all passwords with `password_hash()`, including legacy ones with `updatePlainTextPasswords()`

---
