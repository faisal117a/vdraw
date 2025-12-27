from fastapi import FastAPI, UploadFile, File, HTTPException, Body
from fastapi.middleware.cors import CORSMiddleware
import pandas as pd
import io
from typing import List, Dict

from .schemas import StatsRequest, StatsResponse, ParseDataResponse
from .stats.mean import calculate_mean, calculate_median, calculate_mode
from .stats.variance import calculate_variance, calculate_std_dev, calculate_range
from .stats.quartiles import calculate_quartiles, calculate_iqr, detect_outliers

app = FastAPI(title="VDraw API", version="1.0.0")

# Enable CORS for frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/")
def read_root():
    return {"message": "VDraw API is running"}

from .stats.steps import explain_mean, explain_median, explain_variance_sample, explain_variance_population, explain_std_dev, explain_quartiles, explain_iqr

@app.post("/api/stats/calculate", response_model=StatsResponse)
def calculate_stats(request: StatsRequest):
    data = request.data
    
    # Calculate Central Tendency
    mean = calculate_mean(data)
    median = calculate_median(data)
    mode = calculate_mode(data)
    
    # Calculate Dispersion
    var = calculate_variance(data, request.is_sample)
    std = calculate_std_dev(data, request.is_sample)
    rng = calculate_range(data)
    
    # Calculate Quartiles & Outliers
    qs = calculate_quartiles(data, request.quartile_method)
    iqr = calculate_iqr(qs)
    outliers = detect_outliers(data, iqr, qs['q1'], qs['q3'])
    
    # Generate Explanations
    explanations = []
    explanations.append(explain_mean(data))
    explanations.append(explain_median(data))
    
    if request.is_sample:
        explanations.append(explain_variance_sample(data, mean))
    else:
        explanations.append(explain_variance_population(data, mean))
        
    explanations.append(explain_std_dev(var, request.is_sample))
    explanations.append(explain_quartiles(data, request.quartile_method, qs))
    explanations.append(explain_iqr(qs['q1'], qs['q3']))
    
    return StatsResponse(
        mean=mean,
        median=median,
        mode=mode,
        variance=var,
        std_dev=std,
        range=rng,
        quartiles=qs,
        iqr=iqr,
        outliers=outliers,
        explanations=explanations
    )

@app.post("/api/data/parse", response_model=ParseDataResponse)
async def parse_data_file(file: UploadFile = File(...)):
    # Validate file extension
    filename = file.filename.lower()
    if not (filename.endswith('.csv') or filename.endswith('.xlsx')):
        raise HTTPException(status_code=400, detail="Invalid file format. Please upload a CSV or Excel file.")
    
    try:
        contents = await file.read()
        if filename.endswith('.csv'):
            # Try parsing with standard settings, then handle variants if needed
            # For MVP, assuming comma or standard delimiters.
            # We filter only numeric columns.
            df = pd.read_csv(io.BytesIO(contents))
        else:
            df = pd.read_excel(io.BytesIO(contents))
            
        # 1. Identify Numeric Columns for Analysis
        numeric_df = df.select_dtypes(include=['number'])
        if numeric_df.empty:
            raise HTTPException(status_code=400, detail="No numeric data found in the file.")
            
        numeric_cols = numeric_df.columns.tolist()
        
        # 2. Keep All Columns for Visualization (Labels)
        # Convert non-numeric columns to string to avoid serialization issues
        # But for 'full_data', we want the original DF content (handling NaNs)
        
        # Replace NaNs with None for JSON compatibility
        df_clean = df.where(pd.notnull(df), None)
        
        # Get all columns
        all_columns = df.columns.tolist()
        
        if not all_columns:
             raise HTTPException(status_code=400, detail="Could not detect any columns/headers.")

        # Prepare a preview (first 5 rows of ALL data)
        preview = df_clean.head(5).to_dict(orient='records')
        
        # Prepare full data (Dict of lists)
        full_data = df_clean.to_dict(orient='list')
        
        return ParseDataResponse(
            columns=all_columns,
            numeric_columns=numeric_cols,
            preview=preview,
            full_data=full_data,
            summary={"rows": len(df), "cols": len(all_columns)}
        )
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Failed to parse file: {str(e)}")

@app.post("/api/data/parse-manual")
def parse_manual_data(input_text: str = Body(..., embed=True)):
    """
    Parses a string of multiline or comma-separated values into a list of numbers.
    """
    # Replace common separators with spaces
    cleaned = input_text.replace(',', ' ').replace(';', ' ').replace('\n', ' ')
    
    try:
        # Split and convert to float
        data = [float(x) for x in cleaned.split() if x.strip()]
        if not data:
            raise ValueError("Empty data")
        return {"data": data}
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid data format. Please only input numbers.")
