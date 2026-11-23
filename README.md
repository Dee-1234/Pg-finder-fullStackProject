# Pg-finder-fullStackProject
PG Finder is a responsive full-stack web application designed to help students search, filter, inspect, and shortlist Paying Guest (PG) and student hostel accommodations across major student hubs. The platform simulates a real-world housing discovery experience with mobile-first Bootstrap 5 layouts, normalized relational data storage in MySQL, session-based authentication, asynchronous AJAX/JSON endpoints, and a component-driven React listing interface.

# PG Finder — Student Accommodation Website
A responsive full-stack student accommodation portal for exploring, filtering, viewing, and shortlisting Paying Guest (PG) properties across major student hubs.

## Tech Stack
* **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript (ES6+), React 18
* **Backend:** PHP 8+ (PDO)
* **Database:** MySQL (`InnoDB`, 3NF normalized schema)
* **Asynchronous Layer:** Fetch API / AJAX JSON endpoints

## Features
* **City & Multi-Criteria Filtering:** Filter PG listings dynamically by city, gender preference (`Male`, `Female`, `Unisex`), and maximum monthly budget without page reloads.
* **Property Details Page:** View multi-slide photo galleries, categorized amenities, student ratings, and property descriptions.
* **Student Authentication:** Modal-based signup and login with `password_hash()` security and session management.
* **Interactive Shortlist (Interested PGs):** Toggle interest on any property via AJAX and manage shortlisted accommodations in the Student Dashboard.
* **React Component Architecture:** Modular `PropertyListWidget` and `PropertyCard` components powering the interactive property listing experience.

## Project Structure
```text
pg-finder/
├── api/
│   ├── get_properties.php
│   ├── login_submit.php
│   ├── signup_submit.php
│   └── toggle_interest.php
├── css/
│   └── style.css
├── includes/
│   ├── database_connect.php
│   ├── footer.php
│   └── header.php
├── js/
│   ├── property_list.js
│   └── react_app.js
├── img/
├── Screenshots/
├── dashboard.php
├── database.sql
├── index.php
├── logout.php
├── property_detail.php
└── property_list.php

# Database Schema
  The database (pg_finder) is normalized to Third Normal Form (3NF) across five tables:
  - users — Student profile and hashed credentials
  - properties — PG details, city, monthly rent, gender type, and rating
  - amenities — Master list of PG facilities
  - property_amenities — Many-to-many mapping between properties and amenities
  - interested_users — Many-to-many shortlist mapping between users and properties


live URL - http://localhost/pg-finder/index.php
