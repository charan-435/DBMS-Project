import requests
import mysql.connector
import time
import gzip
import pandas as pd

API_KEY = "ENTER YOUR API KEY HERE"

conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="indian_movies",
    autocommit=True
)
cursor = conn.cursor()

def safe_request(url):
    for _ in range(3):
        try:
            return requests.get(url, timeout=5)
        except:
            time.sleep(1)
    return None

def fetch_movies():
    languages = ["hi", "ta", "te", "ml", "kn"]

    for year in range(2006, 2026):
        print(f"\n{year} ")

        for lang in languages:
            page = 1

            while page <= 10:

                url = f"https://api.themoviedb.org/3/discover/movie?api_key={API_KEY}" \
                      f"&primary_release_year={year}" \
                      f"&with_origin_country=IN" \
                      f"&with_original_language={lang}" \
                      f"&page={page}"

                res = safe_request(url)
                if res is None:
                    page += 1
                    continue

                data = res.json()

                for movie in data.get("results", []):
                    cursor.execute("""
                    INSERT IGNORE INTO movies 
                    (tmdb_id, title, release_date, language, rating_tmdb)
                    VALUES (%s, %s, %s, %s, %s)
                    """, (
                        movie["id"],
                        movie.get("title"),
                        movie.get("release_date"),
                        movie.get("original_language"),
                        movie.get("vote_average")
                    ))

                page += 1
                time.sleep(0.3)

def enrich_movies():
    cursor.execute("SELECT tmdb_id FROM movies WHERE imdb_id IS NULL")
    rows = cursor.fetchall()

    for (tmdb_id,) in rows:

        url = f"https://api.themoviedb.org/3/movie/{tmdb_id}?api_key={API_KEY}&append_to_response=credits,external_ids"

        res = safe_request(url)
        if res is None:
            continue

        data = res.json()

        imdb_id = data.get("external_ids", {}).get("imdb_id")
        revenue = data.get("revenue", 0)

        director = ""
        for crew in data.get("credits", {}).get("crew", []):
            if crew["job"] == "Director":
                director = crew["name"]
                break

        cast_list = [
            c["name"] for c in data.get("credits", {}).get("cast", [])[:5]
        ]
        cast_str = ", ".join(cast_list)

        cursor.execute("""
        UPDATE movies 
        SET imdb_id=%s, revenue=%s, director=%s, cast=%s
        WHERE tmdb_id=%s
        """, (imdb_id, revenue, director, cast_str, tmdb_id))

        print(f"Updated: {tmdb_id}")

        time.sleep(0.3)

def load_imdb_ratings():
    print("Loading IMDb ratings...")

    df = pd.read_csv(
        "title.ratings.tsv.gz",
        sep="\t",
        compression="gzip"
    )

    ratings = dict(zip(df["tconst"], df["averageRating"]))
    votes = dict(zip(df["tconst"], df["numVotes"]))

    cursor.execute("SELECT tmdb_id, imdb_id FROM movies WHERE imdb_id IS NOT NULL")
    rows = cursor.fetchall()

    for tmdb_id, imdb_id in rows:

        if imdb_id in ratings:
            cursor.execute("""
            UPDATE movies
            SET rating_imdb=%s, votes_imdb=%s
            WHERE tmdb_id=%s
            """, (
                ratings[imdb_id],
                votes[imdb_id],
                tmdb_id
            ))

def export_csv():
    df = pd.read_sql("SELECT * FROM movies", conn)
    df.to_csv("indian_movies_20years.csv", index=False)
    print("CSV exported!")

if __name__ == "__main__":
    fetch_movies()
    enrich_movies()
    load_imdb_ratings()
    export_csv()

    cursor.close()
    conn.close()