import pandas as pd
import numpy as np

# 1. Load the dataset (Assuming you saved it as a CSV)
df = pd.read_csv('indian_movies.csv')

print(f"Original shape: {df.shape}")

# 2. Drop rows that are completely missing crucial identifiers or ratings
# If it has no IMDb ID or IMDb rating, it doesn't help our IMDb portal
df = df.dropna(subset=['tmdb_id', 'rating_imdb', 'title'])

# 3. Handle missing Cast and Directors
df['director'] = df['director'].fillna('Unknown')
df['cast'] = df['cast'].fillna('Unknown')

print((df['revenue']==0).sum())
# 4. Handle Revenue (Box Office)
# Fill NaNs with 0.0. (In SQL, we will exclude 0.0 from average calculations)
df[df['revenue']==0] =df[df['revenue']==0]['rating_imdb']*1e5

# 5. Process the Release Date
# Convert to datetime, coerce errors to NaT (Not a Time)
df['release_date'] = pd.to_datetime(df['release_date'], errors='coerce')

# Create a specific 'year' column for easier SQL grouping later
df['release_year'] = df['release_date'].dt.year

# Drop rows where the year couldn't be parsed
df = df.dropna(subset=['release_year'])

# Convert year to integer (it becomes float when NaNs are present)
df['release_year'] = df['release_year'].astype(int)

# 6. Filter for the last 20 years (2004 - 2024)
df = df[(df['release_year'] >= 2004) & (df['release_year'] <= 2024)]

print(f"Cleaned shape: {df.shape}")

# 7. Save the cleaned dataset for the Database team
df.to_csv('movies_cleaned.csv', index=False)
print("Data cleaning complete. Saved to movies_cleaned.csv")
print((df['revenue']==0.0).sum())
df.sort_values(by="release_date").head()