
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS Movie_Actors, Movies, Actors, Directors, Genres, Genre_Statistics;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE Genres (
    genre_id   INT           PRIMARY KEY AUTO_INCREMENT,
    genre_name VARCHAR(50)   NOT NULL UNIQUE
);

CREATE TABLE Directors (
    director_id INT           PRIMARY KEY AUTO_INCREMENT,
    first_name  VARCHAR(50)   NOT NULL,
    last_name   VARCHAR(50)
);

CREATE TABLE Actors (
    actor_id   INT           PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50)   NOT NULL,
    last_name  VARCHAR(50)
);

CREATE TABLE Movies (
    movie_id     INT           PRIMARY KEY AUTO_INCREMENT,
    title        VARCHAR(255)  NOT NULL,
    release_year SMALLINT      NOT NULL,
    revenue      DECIMAL(15,2) DEFAULT 0,
    language     VARCHAR(20),
    rating_imdb  DECIMAL(3,1),
    director_id  INT           NOT NULL,
    genre_id     INT           NOT NULL,
    FOREIGN KEY (director_id) REFERENCES Directors(director_id),
    FOREIGN KEY (genre_id)    REFERENCES Genres(genre_id)
);

-- Junction table: Movies <-> Actors (M:N)
CREATE TABLE Movie_Actors (
    movie_id INT NOT NULL,
    actor_id INT NOT NULL,
    PRIMARY KEY (movie_id, actor_id),
    FOREIGN KEY (movie_id) REFERENCES Movies(movie_id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES Actors(actor_id) ON DELETE CASCADE
);

-- Summary table maintained by trigger
CREATE TABLE Genre_Statistics (
    genre_id      INT PRIMARY KEY,
    movie_count   INT DEFAULT 0,
    avg_rating    DECIMAL(4,2) DEFAULT 0,
    total_revenue DECIMAL(18,2) DEFAULT 0,
    last_updated  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES Genres(genre_id) ON DELETE CASCADE
);


-- ────────────────────────────────────────────────────────────
-- 2. VIEWS
-- ────────────────────────────────────────────────────────────

-- Full movie info in a single view (joins Movies + Directors + Genres)
CREATE OR REPLACE VIEW movie_full_details AS
SELECT
    m.movie_id,
    m.title,
    m.release_year,
    m.revenue,
    m.language,
    m.rating_imdb,
    CONCAT(d.first_name, ' ', d.last_name) AS director_name,
    g.genre_name,
    g.genre_id,
    d.director_id
FROM Movies m
JOIN Directors d ON m.director_id = d.director_id
JOIN Genres g    ON m.genre_id    = g.genre_id;

-- Pre-aggregated genre performance
CREATE OR REPLACE VIEW genre_performance AS
SELECT
    g.genre_id,
    g.genre_name,
    COUNT(m.movie_id)    AS movie_count,
    ROUND(AVG(m.rating_imdb), 2) AS avg_rating,
    SUM(m.revenue)       AS total_revenue,
    MAX(m.rating_imdb)   AS highest_rating,
    MIN(m.release_year)  AS earliest_year,
    MAX(m.release_year)  AS latest_year
FROM Genres g
LEFT JOIN Movies m ON g.genre_id = m.genre_id
GROUP BY g.genre_id, g.genre_name;

-- Director rankings by weighted score
CREATE OR REPLACE VIEW director_rankings AS
SELECT
    d.director_id,
    CONCAT(d.first_name, ' ', d.last_name) AS director_name,
    COUNT(m.movie_id)    AS total_films,
    ROUND(AVG(m.rating_imdb), 2) AS avg_rating,
    SUM(m.revenue)       AS total_revenue,
    MAX(m.rating_imdb)   AS best_rating,
    MIN(m.release_year)  AS career_start,
    MAX(m.release_year)  AS career_latest
FROM Directors d
JOIN Movies m ON d.director_id = m.director_id
WHERE m.rating_imdb > 0
GROUP BY d.director_id
HAVING COUNT(m.movie_id) >= 2
ORDER BY avg_rating DESC;

-- Actor filmography overview
CREATE OR REPLACE VIEW actor_filmography AS
SELECT
    a.actor_id,
    CONCAT(a.first_name, ' ', a.last_name) AS actor_name,
    COUNT(ma.movie_id) AS total_films,
    GROUP_CONCAT(DISTINCT g.genre_name ORDER BY g.genre_name SEPARATOR ', ') AS genres_acted_in
FROM Actors a
JOIN Movie_Actors ma ON a.actor_id = ma.actor_id
JOIN Movies m        ON ma.movie_id = m.movie_id
JOIN Genres g        ON m.genre_id  = g.genre_id
GROUP BY a.actor_id;


-- ────────────────────────────────────────────────────────────
-- 3. STORED PROCEDURES
-- ────────────────────────────────────────────────────────────

DELIMITER //

-- Get summary report for a specific director
CREATE PROCEDURE GetDirectorReport(IN p_director_id INT)
BEGIN
    -- 1. General performance
    SELECT 
        CONCAT(d.first_name, ' ', d.last_name) AS director_name,
        COUNT(m.movie_id) AS total_films,
        AVG(m.rating_imdb) AS avg_rating,
        SUM(m.revenue) AS total_revenue
    FROM Directors d
    JOIN Movies m ON d.director_id = m.director_id
    WHERE d.director_id = p_director_id;

    -- 2. List of their films
    SELECT title, release_year, rating_imdb
    FROM Movies
    WHERE director_id = p_director_id
    ORDER BY release_year DESC;
END //

-- Compare two genres head-to-head
CREATE PROCEDURE CompareGenres(IN p_genre1 VARCHAR(50), IN p_genre2 VARCHAR(50))
BEGIN
    SELECT
        g.genre_name,
        COUNT(m.movie_id)    AS total_films,
        ROUND(AVG(m.rating_imdb), 2) AS avg_rating,
        SUM(m.revenue)       AS total_revenue,
        MAX(m.rating_imdb)   AS best_rating
    FROM Genres g
    JOIN Movies m ON g.genre_id = m.genre_id
    WHERE g.genre_name IN (p_genre1, p_genre2)
    GROUP BY g.genre_id;
END //

-- Search movies with basic filters
CREATE PROCEDURE SearchMovies(
    IN p_title VARCHAR(255),
    IN p_genre VARCHAR(50)
)
BEGIN
    SELECT m.title, m.release_year, m.rating_imdb, g.genre_name
    FROM Movies m
    JOIN Genres g ON m.genre_id = g.genre_id
    WHERE m.title LIKE CONCAT('%', p_title, '%')
      AND (p_genre = '' OR g.genre_name = p_genre);
END //


-- ────────────────────────────────────────────────────────────
-- 4. FUNCTIONS
-- ────────────────────────────────────────────────────────────

-- Classify a movie's rating into a tier label
CREATE FUNCTION GetRatingTier(p_rating DECIMAL(3,1))
RETURNS VARCHAR(30)
BEGIN
    IF p_rating >= 8.0 THEN RETURN 'Masterpiece';
    ELSEIF p_rating >= 6.5 THEN RETURN 'Above Average';
    ELSEIF p_rating >= 5.0 THEN RETURN 'Average';
    ELSEIF p_rating > 0 THEN RETURN 'Below Average';
    ELSE RETURN 'Unrated';
    END IF;
END //

-- Format revenue into Crores
CREATE FUNCTION FormatRevenueCr(p_revenue DECIMAL(15,2))
RETURNS VARCHAR(30)
BEGIN
    RETURN CONCAT(ROUND(p_revenue / 10000000, 2), ' Cr');
END //


-- ────────────────────────────────────────────────────────────
-- 5. TRIGGERS
-- ────────────────────────────────────────────────────────────

-- Update stats after inserting a movie
CREATE TRIGGER trg_after_movie_insert
AFTER INSERT ON Movies
FOR EACH ROW
BEGIN
    REPLACE INTO Genre_Statistics (genre_id, movie_count, avg_rating, total_revenue)
    SELECT genre_id, COUNT(*), AVG(rating_imdb), SUM(revenue)
    FROM Movies
    WHERE genre_id = NEW.genre_id
    GROUP BY genre_id;
END //

-- Update stats after deleting a movie
CREATE TRIGGER trg_after_movie_delete
AFTER DELETE ON Movies
FOR EACH ROW
BEGIN
    REPLACE INTO Genre_Statistics (genre_id, movie_count, avg_rating, total_revenue)
    SELECT genre_id, COUNT(*), AVG(rating_imdb), SUM(revenue)
    FROM Movies
    WHERE genre_id = OLD.genre_id
    GROUP BY genre_id;
END //

-- Update stats after updating a movie
CREATE TRIGGER trg_after_movie_update
AFTER UPDATE ON Movies
FOR EACH ROW
BEGIN
    -- Update stats for old genre
    REPLACE INTO Genre_Statistics (genre_id, movie_count, avg_rating, total_revenue)
    SELECT genre_id, COUNT(*), AVG(rating_imdb), SUM(revenue)
    FROM Movies
    WHERE genre_id = OLD.genre_id
    GROUP BY genre_id;
    
    -- Update stats for new genre (if changed)
    IF OLD.genre_id <> NEW.genre_id THEN
        REPLACE INTO Genre_Statistics (genre_id, movie_count, avg_rating, total_revenue)
        SELECT genre_id, COUNT(*), AVG(rating_imdb), SUM(revenue)
        FROM Movies
        WHERE genre_id = NEW.genre_id
        GROUP BY genre_id;
    END IF;
END //

DELIMITER ;
