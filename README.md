DEPLOYMENT README
=================

=== 11/07/2025 ===
LARAVEL UPDATES
-------------
Minor:
--- update ---
* Removed app/Http/Kernel
** Laravel 11 does not use this configuration, it is done on bootstrap/app.php

--- update ---
* Removed stateful api to remove all SPA CSRF token. -- System is all api based, no one uses web routes, CSRF verifier is for SPA Laravel Frontend
** This removes the CSRF mismatch error and treats all calls, regardless of domain an api call, to be verified by Laravel Sanctum for APIs.

--- update ---
* Moved the global middleware to specific routes:: current implementation includes all routing except the '/' endpoint which is for testing.
** This makes testing easier without the need to remove the whole middleware. 
** Useful for new implementations, API testing, deployment testing.
** Take note that this route group has no encryption for easier testing, after testing make sure to put back the routes to the secured group.


=== 08/07/2025 ===
LARAVEL UPDATES
--------------
Added encryption and decryption middleware
* Middleware to apply to all communication
* Decryption will encrypt the ml variable, files would be sent as is

=== 03/07/2025 === 
LARAVEL UPDATES
---------------

CORS Configuration:

- File location: config/cors.php
- Fields to update:
  - allowed_methods: currently set to allow GET, POST, PATCH, DELETE (all allowed currently for convenience)
  - allowed_origins, allowed_headers, supports_credentials, etc.

Note: Laravel 11 uses native CORS support. No external packages are needed.

DATABASE UPDATES
----------------

- Database tables retain test data for development and QA.
- The database structure remains unchanged.

ACCOUNT MANAGEMENT
------------------

- Account creation, deletion, and updates are to be disregarded.
- Authentication and user access will be handled differently in deployment, following Sir Melner's instructions.

NOTES FOR MAINTENANCE/DEPLOYMENT
------------------

Authentication used: Laravel Sanctum

Logging used: Laravel Built in Log
File path: storage/logs/

Controller File Convention:
Main Controllers are found in each respective folders;
Shared Controllers are in main Controllers/ directory

File Saving:
Uses Laravel Storage
Changing of domain/host provider may alter the way it works,
one thing to make it work is to reboot the link;

How to reboot link:
1. Delete folder public/storage
2. Use artisan command "php artisan storage:link",
This would create a new folder and link from the storage/public to public/storage

Student login function on top of AuthController:
Is tested to fetch and function with an external api as per advisers' instructions


