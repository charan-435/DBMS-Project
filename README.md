# The Cinematic Lens - DBMS Project

The Cinematic Lens is a no-code analytics engine and database management system project built to explore, analyze, and visualize movie data. The platform provides a rich user interface to perform complex analytical queries, visualize box office trends, and manage personalized features like watchlists and community discussions.

##  Features

*   **Dashboard**: Dynamically build custom insights by grouping dimensions (Genre, Director, Year, etc.) and measuring metrics (Revenue, IMDb Rating, Count) with sorting and filtering.
*   **Visual Analytics**: Integrated Chart.js visualizations for intuitive data exploration (Bar, Line, Pie, Radar charts).
*   **Curated Deep Dives**: Pre-calculated industry insights such as "Flop Masterpieces" (critical hits that bombed commercially) and "Commercial Disasters" (commercial hits with terrible ratings).
*   **Personalization & Engagement**: 
    *   Secure User Authentication (Login / Signup).
    *   Personalized Movie Watchlists.
    *   Community Discussion Boards (Anonymous comments on movies).
*   **Robust Database Architecture**: Designed with MySQL views, junction tables, and stored procedures for efficient data querying.

##  Screenshots

![Intelligence Dashboard](/screenshots/img.png)
![Visual Analytics](/screenshots/img-5.png)
![Browse Movie](/screenshots/img-1.png)
![Actors Details](/screenshots/img-2.png)
![Directors Details](/screenshots/img-3.png)
![Compare Movies](/screenshots/img-4.png)
![Genre](/screenshots/img-6.png)
![Collaborations](/screenshots/img-7.png)
![Industry Intelligence](/screenshots/img-8.png)

##  Technology Stack

*   **Frontend**: HTML5, Vanilla CSS (Custom Design System), JavaScript (Chart.js for visualizations).
*   **Backend**: PHP (PDO) and REST-like API endpoints.
*   **Database**: MySQL (Relational Schema, Views, Stored Procedures).

## Project Structure

```text
DBMS-Project/
├── backend/
│   ├── Database.php        # PDO Database connection singleton
│   ├── DataService.php     # Core data retrieval and logic (queries, views)
│   ├── DB_Schema.sql       # Complete SQL schema, Views, and Procedures
│   └── DataReading.php     # Data ingestion and population script
├── frontend/
│   └── src/
│       ├── components/     # Shared UI components (sidebar, topbar, session)
│       ├── css/            # Vanilla CSS stylesheets
│       ├── api_explore.php # API endpoint for dynamic insight building
│       ├── api_user.php    # API endpoint for user features (watchlist, comments)
│       ├── explore.php     # Main analytics dashboard UI
│       ├── movie_details.php # Detailed movie view with stats and charts
│       ├── mylist.php      # User watchlist interface
│       ├── login.php       # User authentication UI
│       └── ...
└── README.md
```

## Installation & Setup

1. **Prerequisites**: Ensure you have a local server environment like XAMPP, WAMP, or MAMP installed with PHP and MySQL running.
2. **Clone/Move the Project**: Place the `DBMS-Project` folder inside your web server's root directory (e.g., `C:\xampp\htdocs\`).
3. **Database Configuration**:
    *   Open phpMyAdmin (usually `http://localhost/phpmyadmin`).
    *   Create a new database named `cinematic_lens_db`.
    *   Import the SQL schema file located at `backend/DB_Schema.sql` into this new database to create all tables, views, and procedures.
4. **Data Ingestion**:
    *   Navigate to `http://localhost/DBMS-Project/backend/DataReading.php` in your browser. This will parse your dataset and populate the database tables.
5. **Database Connection**:
    *   If your MySQL username/password differs from the default `root` and empty password, update the credentials inside `backend/Database.php`.
6. **Access the Application**:
    *   Navigate to `http://localhost/DBMS-Project/frontend/src/login.php`.
    *   Create a new account or log in to start exploring!
