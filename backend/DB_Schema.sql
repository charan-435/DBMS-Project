-- Core lookup tables (no dependencies)
CREATE TABLE Genres (
    genre_id     INT           PRIMARY KEY AUTO_INCREMENT,
    genre_name   VARCHAR(50)   NOT NULL UNIQUE,
    description  TEXT
);

CREATE TABLE Directors (
    director_id  INT           PRIMARY KEY AUTO_INCREMENT,
    first_name   VARCHAR(50)   NOT NULL,
    last_name    VARCHAR(50)   NOT NULL,
    birth_date   DATE,
    nationality  VARCHAR(50)
);

CREATE TABLE Actors (
    actor_id     INT           PRIMARY KEY AUTO_INCREMENT,
    first_name   VARCHAR(50)   NOT NULL,
    last_name    VARCHAR(50)   NOT NULL,
    birth_date   DATE,
    nationality  VARCHAR(50)
);

-- Main entity (depends on Genres and Directors)
CREATE TABLE Movies (
    movie_id     INT           PRIMARY KEY AUTO_INCREMENT,
    title        VARCHAR(255)  NOT NULL,
    release_year SMALLINT      NOT NULL,
    budget       DECIMAL(15,2),
    director_id  INT           NOT NULL,
    genre_id     INT           NOT NULL,
    FOREIGN KEY (director_id) REFERENCES Directors(director_id),
    FOREIGN KEY (genre_id)    REFERENCES Genres(genre_id)
);

-- Junction table: resolves Movies <-> Actors (M:N)
CREATE TABLE Movie_Actors (
    movie_id      INT          NOT NULL,
    actor_id      INT          NOT NULL,
    role_name     VARCHAR(100),
    billing_order TINYINT,
    PRIMARY KEY (movie_id, actor_id),
    FOREIGN KEY (movie_id)  REFERENCES Movies(movie_id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id)  REFERENCES Actors(actor_id) ON DELETE CASCADE
);
