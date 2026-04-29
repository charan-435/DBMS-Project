# Movie Analytics Platform - DBMS Project Presentation

---

## Slide 1: Title Slide

# Movie Analytics Platform

### A DBMS-Based Movie Analytics Web Application

**Name:** [Your Name]  
**Course:** Database Management Systems (DBMS)  
**Instructor:** [To be filled]  
**Date:** April 2026  
**Project:** Cinematic Lens - Movie Data Analytics

---

## Slide 2: Introduction

### System Overview

A comprehensive movie analytics web application built using PHP and MySQL that enables users to:

- Browse and search movies, actors, and directors
- Manage personal watchlists for later viewing
- Post comments and ratings on movies
- Explore data insights through dashboards
- Compare movies side-by-side
- View actor-director collaborations

### Core Modules

| Module | Description |
|--------|-------------|
| **Users** | Authentication and user profile management |
| **Movies** | Movie catalog with metadata (title, year, revenue, ratings) |
| **Actors** | Actor profiles with filmography information |
| **Directors** | Director profiles with career statistics |
| **Watchlist** | User-specific movie collections |
| **Comments** | User reviews and ratings |

### Role of DBMS

- **Data Integrity:** Foreign key constraints maintain relational consistency
- **Query Optimization:** Indexed columns for fast retrieval
- **Scalability:** Normalized schema up to 3NF supports growth
- **Concurrency:** Multiple users can simultaneously access and modify data

---

## Slide 3: Motivation

### Problems Addressed

**Data Management Challenges:**
- Scattered movie data is difficult to analyze holistically
- Lack of structured way to explore actor-director collaborations
- Users cannot easily track movies they want to watch

**Existing Solutions Limitations:**
- No unified platform combining analytics with social features
- Traditional movie databases lack real-time insight generation
- Limited personalization options for users

**User Pain Points:**
- Difficulty comparing movie metrics across different films
- No easy way to discover actor-director collaboration patterns
- Hard to maintain personalized watchlists across sessions

### Need for DBMS

**Structured Storage:**
- Relational database to manage complex relational data
- Normalized tables to eliminate data redundancy
- Foreign key constraints to maintain data integrity

**Efficient Querying:**
- Optimized indexes for fast data retrieval
- JOIN operations to combine data from multiple tables
- Aggregation functions for statistical analysis

**Personalization:**
- Watchlist feature to save movies for later viewing
- Comments and ratings for user feedback
- Session management for personalized experience

### Solution Provided

**Unified Movie Analytics Platform:**
- Combines data analytics with social features
- Real-time search and filtering capabilities
- Custom insight generation for data exploration

**Relational Database Benefits:**
- Manages entity relationships (Movie-Actor, Movie-Director)
- Supports complex queries for analytics
- Enables concurrent multi-user access

**Real-Time Capabilities:**
- Instant search results across movies, actors, directors
- Dynamic dashboard updates
- Live comment and rating updates

---

## Slide 4: Methodology – System Architecture

### High-Level Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   User      │────▶│  Frontend   │────▶│  Database   │
│  (Browser)  │     │   (PHP)     │     │  (MySQL)    │
└─────────────┘     └─────────────┘     └─────────────┘
```

### System Flow

1. **User Request** → Frontend (PHP pages)
2. **API Processing** → Handle business logic
3. **Database Query** → MySQL retrieves data
4. **Response** → Display results to user

### Core Entities

| Entity | Purpose |
|--------|---------|
| **Users** | Store user credentials and profiles |
| **Movies** | Store movie metadata and relationships |
| **Actors** | Store actor information |
| **Directors** | Store director information |
| **Watchlist** | Link users to movies they want to watch |
| **Comments** | Store user reviews and ratings |

### Database Design Approach

- **Relational Model:** Tables with defined relationships
- **Normalization:** Up to 3NF to eliminate redundancy
- **Integrity:** Foreign keys enforce data consistency

---

## Slide 5: Methodology – Database Design

### Database Tables

**Users Table**
| Attribute | Type | Constraints |
|-----------|------|-------------|
| user_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| username | VARCHAR(50) | NOT NULL, UNIQUE |
| email | VARCHAR(100) | NOT NULL, UNIQUE |
| password_hash | VARCHAR(255) | NOT NULL |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Movies Table**
| Attribute | Type | Constraints |
|-----------|------|-------------|
| movie_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| title | VARCHAR(255) | NOT NULL |
| release_year | SMALLINT | NOT NULL |
| revenue | DECIMAL(15,2) | DEFAULT 0 |
| language | VARCHAR(20) | — |
| rating_imdb | DECIMAL(3,1) | — |
| director_id | INT | FOREIGN KEY → Directors |
| genre_id | INT | FOREIGN KEY → Genres |

**Actors Table**
| Attribute | Type | Constraints |
|-----------|------|-------------|
| actor_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| first_name | VARCHAR(50) | NOT NULL |
| last_name | VARCHAR(50) | — |

**Directors Table**
| Attribute | Type | Constraints |
|-----------|------|-------------|
| director_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| first_name | VARCHAR(50) | NOT NULL |
| last_name | VARCHAR(50) | — |

**Watchlist Table**
| Attribute | Type | Constraints |
|-----------|------|-------------|
| watchlist_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| user_id | INT | FOREIGN KEY → Users |
| movie_id | INT | FOREIGN KEY → Movies |
| added_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Comments Table**
| Attribute | Type | Constraints |
|-----------|------|-------------|
| comment_id | INT | PRIMARY KEY, AUTO_INCREMENT |
| user_id | INT | FOREIGN KEY → Users (nullable) |
| movie_id | INT | FOREIGN KEY → Movies |
| comment_text | TEXT | — |
| rating | DECIMAL(2,1) | — |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

### Junction Tables

| Table | Purpose |
|-------|---------|
| **Movie_Actors** | Enables M:N relationship between Movies and Actors |
| **Movie_Unique_Genres** | Supports multiple genres per movie |

### Relationships

- **Movies → Directors:** Many-to-One
- **Movies → Genres:** Many-to-One
- **Movies ↔ Actors:** Many-to-Many (via Movie_Actors)
- **Users → Watchlist:** One-to-Many
- **Users → Comments:** One-to-Many

---

## Slide 6: Methodology – Feature Implementation

### Feature to Database Mapping

**Authentication (Login/Signup)**
- Table: **Users**
- Implementation: Unique constraints on username/email, password hashing
- Session management via PHP sessions

**Watchlist & My List**
- Table: **Watchlist**
- Features: Add/remove movies, view personal collection
- Foreign keys link users to movies

**Comments & Ratings**
- Table: **Comments**
- Features: Post reviews, rate movies (1-10 scale)
- Supports anonymous comments (nullable user_id)

**Browse + Dedicated Pages**
- Tables: **Movies, Actors, Directors**
- Implementation: JOIN queries combine data from multiple tables
- Pagination for performance with large datasets

**Dashboard & Insights**
- Tables: **Movies, Genres, Views**
- Implementation: Pre-computed views (genre_performance, director_rankings)
- GROUP BY aggregations for statistics

**Search & Filtering**
- Tables: **Movies, Actors, Directors**
- Implementation: LIKE queries with wildcards, UNION for cross-entity search

**Compare Page**
- Table: **Movies**
- Implementation: Self-JOIN or dual SELECT for side-by-side comparison

---

## Slide 7: Queries Met in Prototype (Core Queries)

### Authentication Queries

```sql
-- Signup: Insert new user
INSERT INTO Users (username, email, password_hash)
VALUES (:username, :email, :password_hash);

-- Login: Validate credentials
SELECT user_id, username FROM Users 
WHERE email = :email AND password_hash = :password_hash;
```

### Watchlist Queries

```sql
-- Add to watchlist
INSERT INTO Watchlist (user_id, movie_id)
VALUES (:user_id, :movie_id)
ON DUPLICATE KEY UPDATE added_at = CURRENT_TIMESTAMP;

-- Remove from watchlist
DELETE FROM Watchlist 
WHERE user_id = :user_id AND movie_id = :movie_id;
```

### Data Retrieval Queries

```sql
-- Browse movies with JOIN
SELECT m.*, d.first_name, d.last_name, g.genre_name
FROM Movies m
JOIN Directors d ON m.director_id = d.director_id
JOIN Genres g ON m.genre_id = g.genre_id
ORDER BY m.release_year DESC
LIMIT 15 OFFSET :offset;

-- Movie details with cast
SELECT m.*, 
       (SELECT GROUP_CONCAT(CONCAT(a.first_name, ' ', a.last_name))
        FROM Movie_Actors ma
        JOIN Actors a ON ma.actor_id = a.actor_id
        WHERE ma.movie_id = m.movie_id) AS cast
FROM Movies m
WHERE m.movie_id = :id;
```

### Filtering Queries

```sql
-- Filter by genre and rating
SELECT * FROM Movies m
WHERE (:genre IS NULL OR m.genre_id = :genre)
  AND (:min_rating IS NULL OR m.rating_imdb >= :min_rating)
ORDER BY m.release_year DESC;
```

---

## Slide 8: Queries Met in Prototype (Advanced Queries)

### Aggregation Queries

```sql
-- Genre performance statistics
SELECT g.genre_name,
       COUNT(m.movie_id) AS movie_count,
       ROUND(AVG(m.rating_imdb), 2) AS avg_rating,
       SUM(m.revenue) AS total_revenue
FROM Genres g
LEFT JOIN Movies m ON g.genre_id = m.genre_id
GROUP BY g.genre_id;

-- Regional statistics
SELECT language, COUNT(*) AS movie_count, 
       SUM(revenue) AS total_revenue
FROM Movies
GROUP BY language
ORDER BY total_revenue DESC;
```

### Search Queries

```sql
-- Global search across entities
SELECT movie_id AS id, title AS name, 'movie' AS type
FROM Movies WHERE title LIKE :query
UNION ALL
SELECT director_id AS id, CONCAT(first_name,' ',last_name), 'director'
FROM Directors WHERE first_name LIKE :query
UNION ALL
SELECT actor_id AS id, CONCAT(first_name,' ',last_name), 'actor'
FROM Actors WHERE first_name LIKE :query;
```

### Insight Generation Queries

```sql
-- Dynamic aggregation for explore page
SELECT COUNT(*) AS total_movies,
       ROUND(AVG(rating_imdb), 1) AS avg_rating,
       SUM(revenue) AS total_revenue
FROM Movies
WHERE release_year BETWEEN :start_year AND :end_year;
```

### Comparison Queries

```sql
-- Movie comparison (self-join)
SELECT m1.title, m1.revenue, m1.rating_imdb,
       m2.title, m2.revenue, m2.rating_imdb
FROM Movies m1, Movies m2
WHERE m1.movie_id = :id1 AND m2.movie_id = :id2;
```

---

## Slide 9: Results

### System Achievements

**Efficient Data Retrieval:**
- Optimized JOIN queries combine data from multiple tables
- Indexed columns (release_year, revenue, rating) improve query speed
- Pre-computed views reduce dashboard load time

**Real-Time Insights:**
- Genre-wise movie distribution
- Regional statistics and trends
- Actor-director collaboration analysis

**Personalized Experience:**
- User-specific watchlist management
- Comment history tracking
- Custom movie comparisons

### Performance Aspects

| Aspect | Implementation |
|--------|----------------|
| **Query Optimization** | Indexes on frequently queried columns |
| **Caching** | Pre-computed views for dashboard |
| **Pagination** | LIMIT/OFFSET for large datasets |
| **Input Validation** | Parameterized queries prevent SQL injection |

### Example Outputs

**Dashboard Insights:**
- Genre distribution chart
- Top-rated movies list
- Revenue trends by year

**Comparison Results:**
- Side-by-side movie metrics
- Revenue, rating, release year comparison

---

## Slide 10: Unique Selling Point + ER Diagram

### Unique Selling Points

**1. Insight Generator (Custom Analytics)**
- User-defined queries for custom insights
- Dynamic filtering by year range
- Aggregate statistics generation

**2. Movie Comparison Feature**
- Side-by-side movie comparison
- Compare any two movies
- View revenue, ratings, and metadata

**3. Combined Analytics + Social Features**
- Data analytics (dashboard, insights)
- Social features (comments, ratings, watchlist)
- Collaboration tracking (actor-director pairs)

### ER Diagram

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
       └──────────────│ genre_id    │
                      │ genre_name  │
                      └─────────────┘
```

### Why Relational DB is Suitable

| Reason | Explanation |
|--------|-------------|
| **Structured Data** | Movies, actors, directors have well-defined attributes |
| **Complex Relationships** | M:N relationships (Movie-Actor) require junction tables |
| **Data Integrity** | Foreign keys ensure consistency across tables |
| **Query Flexibility** | JOINs and aggregations enable analytics |
| **Scalability** | Normalized design supports growth |

### Schema Feature Coverage

| Feature | Database Support |
|---------|------------------|
| User Authentication | Users table with unique constraints |
| My List | Watchlist table linked to user_id |
| Movie Interaction | Foreign key relationships enable cascading |
| Browse Pages | JOIN queries combine Movies with Directors, Genres |
| Dashboard | Pre-computed views optimize aggregation queries |
| Search | LIKE queries with UNION across entities |
| Compare | Self-JOIN enables movie comparison |
| Data Insights | GROUP BY aggregations provide statistics |

---

### End of Presentation

**Thank You!**

*Questions?*