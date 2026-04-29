# Movie Analytics Platform - DBMS Project Presentation

---

## Slide 1: Title Slide

# Movie Analytics Platform

### A DBMS-Based Movie Analytics Web Application

**Course:** Database Management Systems (DBMS)  
**Instructor:** [To be filled]  
**Date:** April 2026  
**Project:** Cinematic Lens - Movie Data Analytics

---

## Slide 2: System Overview

### Platform Overview

A comprehensive movie analytics web application built using PHP and MySQL that enables users to:

- Browse and search movies, actors, and directors
- Manage personal watchlists for later viewing
- Post comments and ratings on movies
- Explore data insights through dashboards
- Compare movies side-by-side
- View actor-director collaborations

### Core Entities

| Entity | Description |
|--------|-------------|
| **Users** | Registered users for authentication and personalized features |
| **Movies** | Movie catalog with title, year, revenue, language, ratings |
| **Actors** | Actor profiles with filmography information |
| **Directors** | Director profiles with career statistics |
| **Genres** | Movie genre classifications |
| **Watchlist** | User-specific movie collections |
| **Comments** | User reviews, ratings, and feedback |

### Role of DBMS

- **Data Integrity:** Foreign key constraints maintain relational consistency across all tables
- **Query Optimization:** Indexed columns (release_year, revenue, rating) for fast retrieval
- **Security:** User authentication with password hashing and session management
- **Scalability:** Normalized schema up to 3NF supports future growth
- **Concurrency:** Multiple users can simultaneously access and modify data

---

## Slide 3: User Authentication (Login/Signup)

### Feature Explanation

The authentication system provides secure user registration and login functionality:

- **Signup:** Users create an account with a unique username and email address
- **Login:** Credentials are validated against stored password hashes
- **Session Management:** PHP sessions track authenticated users across pages
- **Password Security:** Passwords stored as hashed values using PHP's password_hash()

### Tables Used

**Users Table**
| Attribute | Type | Constraints |
|-----------|------|-------------|
| user_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| username | VARCHAR(50) | NOT NULL, UNIQUE |
| email | VARCHAR(100) | NOT NULL, UNIQUE |
| password_hash | VARCHAR(255) | NOT NULL |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

### Relationships

- **Users → Watchlist:** One-to-Many — Each user can have multiple watchlist items
- **Users → Comments:** One-to-Many — Each user can post multiple comments

### Key Features

- Unique constraints on username and email prevent duplicate accounts
- Password hashing ensures credentials are never stored in plain text
- Session-based authentication provides secure access control

---

## Slide 4: Watchlist & Comments (User Interaction)

### Feature Explanation

**My List Page (User Profile):**
- **Watchlist Display:** Shows all movies the user has saved for later viewing
- **Comment History:** Displays all comments and ratings posted by the user
- **Quick Management:** Users can remove items from watchlist or delete comments
- **Chronological Ordering:** Most recently added items appear first

**Movie Interaction:**
- **Add to Watchlist:** Save movies to personal watchlist with one click
- **Remove from Watchlist:** Delete movies from watchlist
- **Post Comments:** Add text reviews to any movie
- **Add Ratings:** Rate movies on IMDB scale (1-10)
- **Anonymous Feedback:** Users can comment without logging in

### Tables Used

**Watchlist Table**
| Attribute | Type | Constraints |
|-----------|------|-------------|
| watchlist_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| user_id | INT | NOT NULL, FOREIGN KEY → Users |
| movie_id | INT | NOT NULL, FOREIGN KEY → Movies |
| added_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Comments Table**
| Attribute | Type | Constraints |
|-----------|------|-------------|
| comment_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| user_id | INT | FOREIGN KEY → Users (nullable) |
| movie_id | INT | NOT NULL, FOREIGN KEY → Movies |
| comment_text | TEXT | User review content |
| rating | DECIMAL(2,1) | Movie rating (1-10 scale) |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

### Relationships

- **Users ↔ Movies (via Watchlist):** Many-to-Many — Users can watch multiple movies; movies can be in multiple users' watchlists
- **Users → Comments:** One-to-Many — Each user can post multiple comments
- **Movies → Comments:** One-to-Many — Each movie can have multiple comments

### Key Features

- Unique constraint on (user_id, movie_id) prevents duplicate watchlist entries
- Foreign key with CASCADE DELETE removes watchlist items when movie is deleted
- User_id can be NULL to support anonymous comments
- Prevents duplicate watchlist entries for same movie
- Rating field supports decimal values for precise movie ratings

---

## Slide 5: Browse + Dedicated Pages (Movies, Actors, Directors)

### Feature Explanation

**Browse Pages:**
- Paginated lists of Movies, Actors, and Directors
- Sorting options: by year, rating, revenue, title
- Filter by genre, language, year range, rating range

**Detail Pages:**
- **Movie Details:** Shows cast, director, genre, revenue, ratings, and user comments
- **Actor Details:** Displays filmography with movies worked in
- **Director Details:** Shows all movies directed with career statistics

**Insights & Examples:**

*Movies Page Insights:*
- Top 10 highest-grossing movies by region
- Movies with highest rating in each genre
- Year-wise movie release trends

*Actors Page Insights:*
- Actors with most movies in a specific genre
- Actor's average rating across all films
- Most frequent collaborations with directors

*Directors Page Insights:*
- Director's total box office revenue
- Average rating of director's filmography
- Career span and peak performance years

**Collaborations Page (Custom Feature):**
- View actor-director collaboration history
- Find actors who frequently work with specific directors
- Analyze genre preferences for actor-director pairs
- Custom collaboration metrics:
  - Number of films together
  - Average rating of collaborations
  - Total revenue generated

### Tables Used

| Table | Key Attributes |
|-------|----------------|
| **Movies** | movie_id, title, release_year, revenue, language, rating_imdb, director_id, genre_id |
| **Actors** | actor_id, first_name, last_name |
| **Directors** | director_id, first_name, last_name |
| **Genres** | genre_id, genre_name |
| **Movie_Actors** | movie_id, actor_id (junction table) |

### Relationships

- **Movies → Directors:** Many-to-One — Each movie has exactly one director
- **Movies → Genres:** Many-to-One — Each movie belongs to one genre
- **Movies ↔ Actors:** Many-to-Many — Actors can star in multiple movies; movies can have multiple actors

### Key Features

- Junction table (Movie_Actors) enables many-to-many actor-movie relationships
- JOIN queries combine data from multiple related tables
- Pagination ensures performance with large datasets

---

## Slide 6: Dashboard & Insights Pages

### Feature Explanation

**Dashboard Page:**
- Genre-wise movie distribution with counts
- Average ratings per genre
- Total revenue by genre
- Top-rated movies overview

**Insights Page:**
- Regional/language-based movie statistics
- Actor-director collaboration analysis
- Year-by-year performance trends
- Genre popularity over time

### Tables Used

| Table | Purpose |
|-------|---------|
| **Movies** | Contains genre_id, release_year, revenue, language for analytics |
| **Genres** | Genre classifications for grouping |
| **Movie_Unique_Genres** | Supports multiple genres per movie for detailed analysis |
| **Views** | Pre-computed genre_performance, director_rankings, actor_filmography |

### Key Features

- Pre-computed views optimize dashboard query performance
- Multiple genre support enables trend analysis across categories
- Aggregated statistics provide instant insights
- GROUP BY operations aggregate data by genre, language, and year

---

## Slide 7: Search & Filtering System

### Feature Explanation

**Global Search Bar:**
- Real-time search suggestions as users type
- Searches across Movies, Actors, and Directors simultaneously
- Type filtering (search movies only, actors only, or all)

**Advanced Filters:**
- Filter by genre, language
- Filter by rating range (minimum/maximum)
- Filter by release year range
- Sort results by year, rating, revenue, or title

### Tables Used

| Table | Search Fields |
|-------|---------------|
| **Movies** | title, release_year |
| **Actors** | first_name, last_name |
| **Directors** | first_name, last_name |

### Key Features

- Pattern matching with wildcards for partial name matches
- UNION combines results from multiple entity types
- Parameterized queries prevent SQL injection
- Minimum 2-character input prevents overly broad results
- Results limited to 4-6 per category for performance

---

## Slide 8: Explore Data + Compare Page

### Feature Explanation

**Explore Data:**
- Custom insight generator for user-defined queries
- Dynamic data visualization with charts
- Filter by year range to analyze trends
- View aggregate statistics (total movies, avg rating, total revenue)

**Compare Page:**
- Side-by-side movie comparison
- Select any two movies to compare metrics
- Compare revenue, ratings, release years
- Actor-director collaboration statistics

### Tables Used

| Table | Key Attributes |
|-------|----------------|
| **Movies** | title, revenue, rating_imdb, release_year, director_id |
| **Actors** | actor_id, first_name, last_name |
| **Directors** | director_id, first_name, last_name |
| **Movie_Actors** | movie_id, actor_id |

### Key Features

- Self-JOIN technique compares two movies from same table
- Dual selection queries enable movie comparison
- Dynamic year range filtering for trend analysis
- Collaboration stats show actor-director pairings

---

## Slide 9: Database Design & ER Diagram

### Full ER Diagram

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│   Users     │       │   Movies    │       │   Actors    │
├─────────────┤       ├─────────────┤       ├─────────────┤
│ user_id (PK)│◄──────│ movie_id(PK)│       │ actor_id(PK)│
│ username    │  1:N  │ title       │       │ first_name  │
│ email       │       │ release_year│       │ last_name   │
│ password_hash     │ revenue     │       └──────┬──────┘
└──────┬──────┘       │ language    │              │
       │              │ rating_imdb │              │
       │              │ director_id │──────────────┤
       │              │ genre_id   │       ┌──────▼──────┐
       │              └──────┬──────┘       │Movie_Actors │
       │                     │              │(Junction)   │
       │                     │              ├─────────────┤
       │                     │              │ movie_id(FK) │
┌──────▼──────┐              │              │ actor_id(FK) │
│  Watchlist  │              │              └─────────────┘
├─────────────┤       ┌──────▼──────┐
│ watchlist_id│       │  Directors  │
│ user_id(FK) │       ├─────────────┤
│ movie_id(FK)│       │ director_id │
│ added_at    │       │ first_name  │
└──────┬──────┘       │ last_name   │
       │              └──────┬──────┘
       │                     │
       │              ┌──────▼──────┐
       │              │   Genres    │
       │              ├─────────────┤
       │              │ genre_id    │
       └──────────────│ genre_name  │
                      └─────────────┘
```

### Entity Summary

| Entity | Primary Key | Foreign Keys | Relationships |
|--------|-------------|--------------|---------------|
| **Users** | user_id | — | 1:N → Watchlist, 1:N → Comments |
| **Movies** | movie_id | director_id, genre_id | N:1 ← Watchlist, N:1 ← Comments, M:N ↔ Actors |
| **Actors** | actor_id | — | M:N ↔ Movies |
| **Directors** | director_id | — | 1:N → Movies |
| **Genres** | genre_id | — | 1:N → Movies |
| **Watchlist** | watchlist_id | user_id, movie_id | N:1 → Users, N:1 → Movies |
| **Comments** | comment_id | user_id, movie_id | N:1 → Users, N:1 → Movies |
| **Movie_Actors** | (movie_id, actor_id) | movie_id, actor_id | N:1 → Movies, N:1 → Actors |

### Normalization (3NF)

**First Normal Form (1NF):**
- All columns contain atomic, indivisible values
- No repeating groups or arrays

**Second Normal Form (2NF):**
- All non-key attributes fully functional dependent on primary key
- No partial dependencies on composite keys

**Third Normal Form (3NF):**
- No transitive dependencies between non-key attributes
- Example: Movie revenue depends only on movie_id, not on other attributes

### Junction Tables

| Table | Purpose |
|-------|---------|
| **Movie_Actors** | Enables many-to-many relationship between Movies and Actors |
| **Movie_Unique_Genres** | Supports multiple genres per movie for trend analysis |

### Schema Feature Coverage

| Feature | Database Support |
|---------|------------------|
| User Authentication | Users table with unique constraints on username and email |
| My List | Watchlist and Comments tables linked to user_id |
| Movie Interaction | Foreign key relationships enable cascading operations |
| Browse Pages | JOIN queries combine Movies with Directors, Genres, Actors |
| Dashboard | Pre-computed views optimize aggregation queries |
| Search | Pattern matching across multiple tables |
| Compare | Self-JOIN and dual selection enable movie comparison |
| Data Insights | GROUP BY aggregations provide statistical analysis |

---

### End of Presentation

**Thank You!**

*Questions?*