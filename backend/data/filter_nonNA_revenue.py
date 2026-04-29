import pandas as pd
import os

# Paths relative to this script's location
script_dir = os.path.dirname(os.path.abspath(__file__))
input_path  = os.path.join(script_dir, "genre_added.csv")
output_path = os.path.join(script_dir, "genre_nonNA_revenue.csv")

# Load the dataset
df = pd.read_csv(input_path)

print(f"Total rows in genre_added.csv : {len(df)}")
print(f"Rows with NA revenue          : {df['revenue'].isna().sum()}")

# Keep only rows where revenue is NOT NA
df_filtered = df[df["revenue"].notna()]

print(f"Rows with non-NA revenue      : {len(df_filtered)}")

# Save the filtered dataset
df_filtered.to_csv(output_path, index=False)

print(f"\nFiltered dataset saved to: {output_path}")
