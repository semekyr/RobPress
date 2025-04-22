# Web Application Vulnerability Fixing Coursework

This group project is part of the COMP3226 Web Security coursework at the University of Southampton. The aim was to identify and fix a range of security vulnerabilities in a large-scale PHP-based web application. The application mimics a realistic web environment with multiple pages, user roles, and dynamic content.
The vulnerabilities explored and resolved include:
- SQL Injection
- Cross-Site Scripting (XSS)
- Information Exposure
- Insecure File Uploads
- Authorization Bypass
- Internal Information Disclosure
- Parameter Manipulation
- Application Logic Flaws
- File Inclusion
- Insecure Cookies and Sessions
- Open Redirects

## Note on Implementation Language:

Although the web application contains some JavaScript files provided by the initial website source code, all vulnerability fixes mentioned in this report were implemented in the PHP backend code. The vulnerable components (e.g., BlogController, AuthHelper, GenericModel) were part of the PHP logic, and all security improvements—such as prepared statements, parameterized queries, and input validation—were applied directly in the PHP files responsible for server-side operations.

---


## Vulnerability 1: SQL Injection


**What I found and where:**

**Find #1:**  
The search function in the Blog Controller class was vulnerable to SQL injection because user input was directly concatenated into the SQL query string without any sanitization or parameterization. This allows for attackers to inject malicious SQL code through the search input field. 
**Find #2:**  
The login function in the AuthHelper class was vulnerable as the username and password parameter were also directly concatenated into the SQL query without any sanitization or parameterization. 
**Find #3:**  
The fetch function in the GenericModel class was vulnerable to SQL injection because it directly accepted numeric conditions without proper validation or sanitization. If this parameter was manipulated, the attackers could inject malicious input into dynamic query conditions.

**How I resolved it:**

- **Solution to Find #1:** We replaced raw query construction with a prepared statement using parameterized queries. This ensures that user input is treated as data rather than executable SQL code. The query now uses placeholders (?) for the search term and input values are bound to these placeholders. 
- **Solution to Find #2:**  We replaced the direct concatenation of the username parameter with a placeholder. The input value is now safely bound to the placeholder. 
- **Solution to Find #3:** The fetch function was updated to check whether the conditions parameter is an array. If it isn't, it is explicitly converted into an associative array with an ID key, ensuring the format is consistent and controlled for all inputs. We applied the prepare() method to sanitize conditions, in order to mitigate the possibility of an SQL injection.  

---


## Vulnerability 2: Information Exposure


**What I found and where:**

**Find #1:**  
-The server contained backup and temporary files such as .bak, .swp, .swo files, as well as files needed to maintain the infrastructure of the website such as .sql and .cfg files. These files exposed sensitive information, such as database credentials, page logic etc., which could be exploited by attackers to gain unauthorized access or valuable insights into the application. 


**How I resolved it:**

- To address this vulnerability, we removed any unnecessary files from the server. For files deemed necessary to retain but critical to restrict from client-side access, we added rules to the .htaccess file to block unauthorized access.  

---


## Vulnerability 3: XSS


**What I found and where:**
XSS was present in multiple locations where the user input was being processed without being correctly sanitized. Following is a list of locations where vulnerability to XSS was found:
1. Search Feature: The search() function in the controllers/blog.php class and Blog/search.htm in view.
2. Contact Feature: The index() function in the controllers/contact.php class and Contact/index.htm in view.
3. Adding and Editing Posts: The add() and edit() functions in controllers/admin/blog.php, Blog/admin_add.htm and Blog/admin_edit.htm in view.
4. Adding and Editing Categories: The add() and edit() functions in controllers/admin/category.php, Category/admin_edit.htm and Category/admin_index.htm in view.
5. Leaving and Editing a Comment: The comment() function in controllers/blog.php and the edit() function in controllers/admin/comment.php, Comment/admin_edit.htm and Comment/admin_index.htm in view.
6. Adding and Editing Pages: The add() and edit() functions in controllers/admin/page.php, Page/admin_edit.htm and Page/admin_index.htm in view.
7. Registering and Editing Users: The add() function in controllers/user.php and the edit() function in controllers/admin/user.php, User/add.htm and User/admin_edit.htm in view.
8. Logging in: The login() function in helpers/authhelper.php and User/login.htm in view.
9. Editing Settings: The index() function in controllers/admin/settings.php and Settings/admin_index.htm in view.



**How I resolved it:**
To resolve XSS in the locations mentioned, a combination of htmlspecialchars, HTMLpurifier and the pre-defined h() function was used. For all the fixes, HTMLPurifier was configured such that no HTML tags are allowed, and no external resources like images, videos, scripts, etc. are allowed.

1. Search Feature: Purified the search input using htmlpurifier and htmspecialchars before passing it to view in the search() function and also sanitised the output for all the fields in Blog/search.htm using htmlspecialchars before it gets displayed.

2. Contact Feature: Purified the "Email", "Subject" and "Message" fields using htmlspecialchars before passing it to view. We did the same for the output in Contact/index.htm before rendering the input fields.

3. Adding and Editing Posts: Used HTMLPurifier to  sanitise the "Title", "Content" and "Summary" fields before passing it to view in the add() function. We sanitised the "Title", "Content" and "Summary" the same way for the edit() function. In the view templates for posts, we purified the fields using HTMLPurifer before rendering.

4. Adding and Editing Categories: Used HTMLPurifier with the before mentioned configuration to purify and validate the "Title" input so that it doesn't process any malicious inputs and can't be left blank in the add() and edit() functions. We used the h() function to purify the output in admin_edit.htm and admin_index.htm before displaying the output.

5. Leaving and Editing a Comment: We used the h() function to sanitise the comment message in the comment() function before passing it to view. Also, sanitised the input for editing a comment message in the edit() function using h(). The view templates for adding and editing comments were also purified using the h() function before being rendered.

6. Adding and Editing Pages: The title and content were sanitised using HTMLPurifer before being passed on to view. The fields were then also purified before being rendered in the view templates, i.e., Page/admin_edit.htm and Page/admin_index.htm.

7. Registering and Editing Users: For registering users HTMLPurifier was used to sanitise the username, email, display name, password and password2 fields in the add() function, the same was then done in view for User/add.htm For editing users, the h() function was used to sanitise the fields in the edit() function and then consecutively for the output the same thing was done in admin_edit.htm.

8. Logging In: The username and password fields were sanitised using the h() function in the login() function in the AuthHelper class. The output was then escaped using the h() function in login.htm before being rendered.

9. Editing Settings: The input for settings was sanitised using the h() in the index() function and then before rendering the output in view it was again escaped using h().


---


## Vulnerability 4: Insecure Upload


**What I found and where:**
**Find #1:**
The original file upload function in the File class did not check the file type or file content, allowing any file to be uploaded, including malicious files. 
**Find #2:**
Uploaded files were assigned overly permissive 0666 permissions, allowing anyone on the system to read and write to these files. 
**Find #3:**
The file name was not sanitized which could lead to directory traversal attacks. 
**Find #4:**
There was no restriction on the size of the uploaded files, potentially allowing excessively large files to harm the application's resources. 
**How I resolved it:**
**Solution to Find #1:**
-We added a method to validate the file extension and MIME type, ensuring only specific file extensions and MIME types are allowed. 
-We also added rules to the .htaccess file to allow only specific file types. 
-We  added an additional check to ensure the file is a valid image. 
**Solution to Find #2:**
We changed file permissions to 0644, restricting write access to the owner only and preventing unauthorized and unnecessary modifications. 
**Solution to Find #3:**
We generated a unique file name using uniqid() to avoid overwriting existing files. 
**Solution to Find #4:**
Implemented a maximum file size of 69MB, rejecting larger files. 

---


## Vulnerability 6: Authorisation Bypass
**What I found and where:**
**Fix #1:** 
We identified an Authorization Bypass vulnerability in the beforeRoute function within the controller of the application. This allowed users with insufficient permissions to access administrative pages such as 'admin/user', 'admin/blog', 'admin/category' etc., which should have been restricted to administrators. The issue occurred because the initial authorization check only validated if the user was logged in but did not verify what their permission level was. 
**Fix #2:**
The reset function in the blog.php controller lacked proper authentication checks, allowing any user regardless of their privilege level, to reset the blog. This created an authorization bypass vulnerability, as any user could invoke the function and erase all posts, categories, comments and post-category mappings. 
**Fix #3:**
The application used a function called promote in the User class, allowing any normal user to promote themselves to administrators. (../user/promote/2). 


**How I resolved it:**
**Solution to Fix #1:** 
To resolve this, we updated the beforeRoute function to include a check against the user's access level. The new implementation ensures that both login status and permission levels are verified. This change prevents unauthorized users from accessing restricted resources by comparing their access level against the required level. As a result, only users with sufficient permissions can access the administrative pages. 
**Solution to Fix #2:** 
To address this issue, we added a check to verify if the logged-in user has administrative privileges before allowing the blog to be reset. If the user does not have administrative privileges, they are redirected to the home page.
**Solution to Fix #3:**
- We removed the promote function from the User class in the Controllers, so no normal user has the ability to promote themselves and gain administrative privileges. 

---


## Vulnerability 7: Internal Information

**What I found and where:**
**Fix #1:**
The view files responsible for outputting errors on the application (errorer.htm and error.htm) revealed sensitive, internal information, such as the stack trace to the users when an error occurred. This exposes critical details of the implementations (e.g file paths) making the system more vulnerable to attacks. 

**How I resolved it:**
**Solution to Fix #1:**
We added a condition to ensure the stack trace is only displayed when the website is in debug mode. When the debug mode is disabled, the stack trace is hidden from users, preventing the exposure of sensitive internal details. 


---


## Vulnerability 8: Parameter Manipulation


**What I found and where:**
**Find #1:** 
The URL manipulation allowed users to view details of non-existent users by altering the userid parameter in the URL. For example, accessing /user/view/9999 would attempt to fetch user details for a user ID that does not exist, leading to unintended behaviour or error messages being displayed to the user.
**Find #2:**
The application allowed users to manipulate the URL to access pages that did not exist. For instance, accessing /page/display/nonexistent_page would bypass proper validation checks and attempt to retrieve a non-existent page, exposing error messages or potentially unintended behaviour.
**Find #3:** 
- The application allowed any user to view unpublished posts by directly accessing their URLs ('../blog/view/'_'). 
- This posed an application logic vulnerability as well as a parameter manipulation vulnerability as unpublished content could be accessed without proper authorization. 


**How I resolved it:**
**Solution to Find #1:** 
We added a condition to verify the existence of a user with the provided userid before retrieving their details. If the user does not exist, an appropriate error message is displayed, and the user is redirected to the home page. 

**Solution to Find #2:** 
We implemented a similar validation to check if the requested page exists in the database. If the page does not exist, an error message is displayed, and the user is redirected to the homepage.

**Solution to Find #3:**
We implemented a check in the view method in the Blog Controller class to ensure that unpublished posts can be viewed only by users with administrative privileges. Specifically, we added a condition to verify if the published date of the post is in the past. If the post is unpublished and the user's authorization level is below 2, access is denied, and the user is redirected with an appropriate error message. 

---


## Vulnerability 9: Application Logic
**What I found and where:**
**Find #1:**
- The email function allowed users to send emails to any arbitrary recipient by altering the hidden ‘to’ field in the form.
- This posed an application logic risk, as it was possible to send emails to unauthorized recipients by modifying the form input.
**Find #2:** 
- The application allowed any user to view unpublished posts by directly accessing their URLs ('../blog/view/'_'). 
-This posed an application logic vulnerability as well as a parameter manipulation vulnerability as unpublished content could be accessed without proper authorization. 



**How I resolved it:**
**Solution to Find #1:** 
- We ensured that the email could only be sent to the website’s configured email address by hardcoding the recipient address instead of relying on user-provided input from the hidden ‘to’ field. Moreover, we hard coded the sender's email address to be the email address configured to the user trying to send an email. We also added a check to never allow a user to be able to sent an email to themselves, as well as ensuring that all fields are filled out and the email format is correct. 
- We also implemented a validation step to ensure the ‘from’ email address provided by the user is not the same as the ‘to’ email address. If they are identical, the email is rejected to prevent potential misuse.
- The function now uses a fixed recipient address and validates email fields properly to eliminate any chance of changing the recipient dynamically through manipulation.
**Solution to Find #2:**
We implemented a check in the view method in the Blog Controller class to ensure that unpublished posts can be viewed only by users with administrative privileges. Specifically, we added a condition to verify if the published date of the post is in the past. If the post is unpublished and the user's authorization level is below 2, access is denied, and the user is redirected with an appropriate error message. 
---


## Vulnerability 10: Out of Date Software


**What I found and where:**
The application was using outdated versions of several software components, including FatFree Framework (version 3.8.2) and CKEditor (version 4.20.1). These outdated versions posed a security risk due to the potential presence of unpatched vulnerabilities. 

**How I resolved it:**
This vulnerability was resolved by downloading and applying the latest updates for these components. Specifically, the FatFree Framework was updated to version 3.9, and CKEditor was updated to version 4.22.1. Furthermore, security enhancements were implemented, such as the removal of insecure pages and improvements to error handling. This ensures the system is better protected and its performance is improved. 
---


## Vulnerability 11: File Inclusion


**What I found and where:**
**Find #1:**
The fetch function in the PagesModel class was vulnerable to directory traversal and arbitrary file inclusion attacks. It directly accepted the pagename parameter without sanitization, allowing attackers to manipulate the file path (e.g .../../../../../../../../../../../../etc/passwd) to access sensitive files outside the intended pages directory. 

**How I resolved it:**
**Solution to Find #1:** 
The pagename parameter is now sanitized using basename() to string any directory traversal sequences or unexpected paths, ensuring only file names within the intended directory can be accessed.

---


## Vulnerability 12: Insecure Cookies and Sessions


**What I found and where:**
**Find #1:**
The RobPress_User cookie was insecurely implemented in a few ways:
- The cookie was poorly encrypted, using only serialize and base64_encode, which could easily be reversed by decoding and unserializing the value of the cookie.
- The cookie value was not stored in the database, meaning attackers could manipulate the cookie on the client side of the application.
- The cookie did not have the HttpOnly or Secure flags set, leaving it vulnerable to XSS attacks and exposure over non-HTTPS connections.
**Find #2:** 
The session ID was generated using the md5 hash of the user's ID, which is considered weak and vulnerable to tampering and collision attacks.

**How I resolved it:**
**Solution to Find #1:**
To mitigate the RobPress_User cookie issues:
- We encrypted the cookie value using AES-256-CBC with a secure encryption key. An Initialization Vector (IV) is generated for each cookie and securely included with the encrypted data. This ensures confidentiality and prevents tampering. The secure encryption key and secure IV are both stored in a new file called security.php. 
- The cookie is now stored in a secure table in our database, with an associated expiration date. Each cookie is now validated against the database. We also remove expired cookies during session resumption, in order to be more efficient.
- The HttpOnly and Secure flags are now set on the cookie. This prevents client-side access and makes sure the cookie is only transmitted over HTTPS connections.
- By encrypting and storing the cookie in the database, any unauthorized modification of the cookie by an attacker will render it invalid, as the true cookie value resides in the database.
- Upon logout, the cookie is deleted from both the client-side browser and the database, preventing any risk of misuse.

**Solution to Find #2:**
The new implementation now uses the hash function with the sha256 algorithm, combining the user's ID with a unique identifier (uniqid()). This ensures that session IDs are more secure, harder to predict, and resistant to tampering. 

---


## Vulnerability 13: Open Redirects


**What I found and where:**
**Find #1:** 
The application was vulnerable to open redirects due to the improper use of the $_GET['from'] parameter for redirection after login, in the User Controller of the application. The application redirected users to a URL specified in this parameter without validating or sanitizing the input, allowing attackers to craft malicious links that redirected users to unauthorized external websites. 

**How I resolved it:**
**Solution to Find #1:** 
- We resolved this vulnerability by implementing a secure mechanism to manage redirect URLs.
- Specifically, we introduced the previousUrl cookie, which securely stores the last visited URL from the website. We used it to redirect the user back to their previous location after login.
- The previousUrl value is encrypted using AES-256-CBC encryption with a secure key (SECURE_KEY) and an initialization vector (SECURE_IV) before being stored in a cookie to prevent tampering.
- Upon login, the encrypted previousUrl is retrieved from the cookie, decrypted and validated before redirecting the user to ensure the URL is legitimate.
- If the previousUrl is missing, invalid or cannot be decrypted, the user is safely redirected to the home page (/).
- The previousUrl cookie is cleared immediately after use to avoid reuse or exploitation. 
- Cookies storing the previousUrl value are secured with HttpOnly and Secure flags to protect against client-side manipulation and ensure secure transmission over HTTPS.
- This ensures that the previousUrl cookie mechanism operates successfully and securely, fixing the open redirect vulnerability the original code posed. 


---


## Vulnerability 14: Anything Else


**What I found and where:**
**Find #1:**
- The vulnerability was related to the lack of tracking for failed login attempts, which allowed brute force attacks on user accounts.
- There was no mechanism in the application blocking malicious actors after exceeding a certain amount of failed login attempts.
- This vulnerability was present in the login functionality of the AuthHelper, which is part of the application's authentication helper logic.
**Find #2:**
The export function in the admin section of the User controller class was leaking internal information by including users' hashed passwords in the exported CSV file. While hashed passwords are generally secure, exposing them in an external file download is unnecessary and poses security risks. 
**Find #3:**
-The old implementation of the API class relied on directly comparing the provided token with the API key stored in the framework's configuration. 
-There was also no functionality to allow users to securely input or manage their own API keys.  
**Find #4:**
The passwords in the user table in the database were all stored in plaintext, leaving them vulnerable to exposure and misuse in the event of a data breach or unauthorized access.

**How I resolved it:**
**Solution to Find #1:**
- We resolved the vulnerability by implementing an IP tracking and blocking system and integrating it into the AuthHelper class.
- We added two new tables in the database to track login attempts and block malicious IP addresses. The table login_attempts tracks the number of failed login attempts for each IP address and records the timestamp of the last attempt. The table blocked_ips maintains a list of blocked IPs, along with the timestamp of when the block was applied.
- We implemented a function named trackLogin in the AuthHelper class to monitor failed login attempts and update the login_attempts table. If a user exceeds five failed login attempts within ten minutes, their IP address is added to the blocked_ips table.
- The isBlocked function was added to check if an IP is currently blocked by querying the corresponding table in the database. Blocked IPs are automatically unblocked after a few minutes to allow legitimate users to retry logging in.
- The login method was modified to invoke the trackLogin and isBlocked methods. Blocked IPs are prevented from logging in, whereas successful logins reset the failed attempts count for the corresponding IP address.
- These changes ensure that malicious actors are restricted after multiple failed attempts, mitigating the risk of brute force attacks.

**Solutions to Find #2:**
We excluded the password field from the exported CSV file to prevent sensitive information from being included in the export. The method handling the export was found in the administrative User controller class and the fields being exported were changed to just 'ID', 'Username', 'Display Name', 'Email', 'Level', 'Created'.
 
**Solutions to Find #3:**
- We added a new column to the settings table in our database to store each user's unique API key securely. 
- The API key is now retrieved from the settings table, enabling dynamic management of API keys. 
- The beforeRoute function in the Controller class was updated to dynamically fetch the API key from the database, ensuring better flexibility.
- If no API key is found, the parameter is set to an empty string. ensuring the application handles missing keys. 
- If an API key does exist, it is stored in the framework's configuration. 
- In the API class, the authentication of the token is now done using the API key which was added by the user in the Settings. 

**Solution to Find #4:**
- To enhance security and prevent plaintext passwords from being stored in the database, we implemented password hashing using PHP's password_hash function with the PASSWORD_DEFAULT algorithm. This ensures that passwords are securely hashed before being stored, mitigating the risk of exposure in the event of a data breach.
- For the passwords already stored in the database in plaintext, we added a new function called updatePlainTextPasswords() to hash these existing passwords using the password_hash function and securely store the updated hashes in the database.

---
