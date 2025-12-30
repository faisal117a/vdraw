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

from .stats.steps import explain_mean, explain_median, explain_variance_sample, explain_variance_population, explain_std_dev, explain_quartiles, explain_iqr, explain_linear_regression, explain_logistic_regression
from sklearn.linear_model import LinearRegression, LogisticRegression
from sklearn.metrics import r2_score, accuracy_score
import numpy as np

# ... (Previous imports) ...

@app.post("/api/stats/calculate", response_model=StatsResponse)
def calculate_stats(request: StatsRequest):
    data = request.data
    
    # Validation for Descriptive Stats
    # If data contains strings, we can't do math. Modes might work, but mean/std won't.
    # We will try to convert to float, if fail, we assume it's categorical.
    # User Request: "Show friendly errors". 
    # Strategy: Filter numeric for stats. If empty, raise error or return empty stats.
    
    numeric_data = []
    try:
        numeric_data = [float(x) for x in data if x is not None and str(x).strip() != '']
    except ValueError:
        pass # Contains strings
        
    stats_valid = len(numeric_data) > 0
    
    if stats_valid:
        mean = calculate_mean(numeric_data)
        median = calculate_median(numeric_data)
        mode = calculate_mode(numeric_data)
        var = calculate_variance(numeric_data, request.is_sample)
        std = calculate_std_dev(numeric_data, request.is_sample)
        rng = calculate_range(numeric_data)
        qs = calculate_quartiles(numeric_data, request.quartile_method)
        iqr = calculate_iqr(qs)
        outliers = detect_outliers(numeric_data, iqr, qs['q1'], qs['q3'])
        
        explanations = []
        explanations.append(explain_mean(numeric_data))
        explanations.append(explain_median(numeric_data))
        if request.is_sample: explanations.append(explain_variance_sample(numeric_data, mean))
        else: explanations.append(explain_variance_population(numeric_data, mean))
        explanations.append(explain_std_dev(var, request.is_sample))
        explanations.append(explain_quartiles(numeric_data, request.quartile_method, qs))
        explanations.append(explain_iqr(qs['q1'], qs['q3']))
    else:
        # Return empty/null stats if data is not numeric (e.g. only categorical)
        # But we might still want to do regression!
        mean = median = var = std = rng = iqr = 0.0
        mode = []
        outliers = []
        qs = {'q1': 0.0, 'median': 0.0, 'q3': 0.0}
        explanations = [{"title": "Notice", "steps": [{"text": "Descriptive statistics (Mean, Median, etc.) skipped because the selected 'Stats Column' contains non-numeric data."}], "result": "N/A"}]

    # Optional Regression
    regression_result = None
    if request.regression_type:
        # Use explicit Y if provided, else fallback to data (which might be the categorical one)
        raw_y = request.y_data if request.y_data else data
        y = np.array(raw_y)
        
        # Use provided X or fallback to index
        if request.x_data and len(request.x_data) == len(y):
            X = np.array(request.x_data).reshape(-1, 1)
        else:
            X = np.array(range(len(y))).reshape(-1, 1)

        if request.regression_type == "linear":
            # Linear requires Numeric Y
            try:
                y_float = y.astype(float)
                model = LinearRegression()
                model.fit(X, y_float)
                r2 = model.score(X, y_float)
                regression_result = {
                    "slope": float(model.coef_[0]),
                    "intercept": float(model.intercept_),
                    "r2_score": float(r2),
                    "formula": f"y = {model.coef_[0]:.2f}x + {model.intercept_:.2f}",
                    "x_mean": float(np.mean(X)),
                    "y_mean": float(np.mean(y_float))
                }
                explanations.append(explain_linear_regression(model.coef_[0], model.intercept_, r2))
            except ValueError:
                 # Y is string
                 raise HTTPException(status_code=400, detail="Linear Regression requires a numeric Target/Y column.")
            
        elif request.regression_type == "logistic":
            # For Logistic, support binary or multiclass with Text Labels
            from sklearn.preprocessing import LabelEncoder
            le = LabelEncoder()
            
            # Encode Y
            try:
                # Force string conversion for labels to handle mixed types/None safely
                y_str = [str(val) for val in y.flatten()]
                y_encoded = le.fit_transform(y_str)
            except Exception as e:
                # Last resort
                print(f"Encoding failed: {e}")
                raise HTTPException(status_code=400, detail=f"Could not encode Target/Y labels: {str(e)}")

            # Validation for X (Must be numeric)
            try:
                X = X.astype(float)
            except ValueError:
                 raise HTTPException(status_code=400, detail="Independent Variable (X) must be numeric.")

            model = LogisticRegression(max_iter=5000) # Increased max_iter
            model.fit(X, y_encoded)
            acc = model.score(X, y_encoded)
            
            classes_str = ", ".join([str(c) for c in le.classes_])

            # Extract coefficients
            if len(le.classes_) == 2:
                # Binary: 1 coeff per feature (for the Positive class)
                # Sklearn returns shape (1, n_features)
                intercept_val = float(model.intercept_[0])
                coefs = [float(c) for c in model.coef_[0]]
            else:
                # Multiclass: One-vs-Rest or Multinomial
                # Shape (n_classes, n_features)
                # We have 1 feature (X), so coefs is list of slopes for each class
                # Intercepts is list of intercepts for each class
                intercept_val = model.intercept_.tolist() # List of floats
                coefs = model.coef_.flatten().tolist()    # List of floats (m1, m2, m3...)

            regression_result = {
                "accuracy": float(acc),
                "intercept": intercept_val, # Float (Binary) or List[float] (Multicls)
                "coefficients": coefs,      # List[float]
                "formula": f"Classes: [{classes_str}]",
                "classes": [str(c) for c in le.classes_]
            }
            # Update explanation function
            explanations.append(explain_logistic_regression(acc, coefs, intercept_val))

            regression_result = {
                "accuracy": float(acc),
                "intercept": intercept_val,
                "coefficients": coefs,
                "formula": f"Classes: [{classes_str}]",
                "classes": [str(c) for c in le.classes_] if hasattr(le, 'classes_') else []
            }
            # Update explanation function to accept class names if needed
            explanations.append(explain_logistic_regression(acc, coefs, intercept_val))

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
        explanations=explanations,
        regression=regression_result
    )

@app.post("/api/data/parse", response_model=ParseDataResponse)
async def parse_data_file(file: UploadFile = File(...)):
    # ... (Validation code) ...
    filename = file.filename.lower()
    if not (filename.endswith('.csv') or filename.endswith('.xlsx')):
         raise HTTPException(status_code=400, detail="Invalid file format. Please upload a CSV or Excel file.")
         
    try:
        contents = await file.read()
        if filename.endswith('.csv'):
            df = pd.read_csv(io.BytesIO(contents))
        else:
            df = pd.read_excel(io.BytesIO(contents))
            
        # Hard Limit for Performance
        if len(df) > 10000:
            raise HTTPException(status_code=400, detail="File too large. Maximum 10000 rows allowed for this version.")
            
        # ... (Rest of existing parsing logic) ...

            
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

# --- PDraw (Phase 2) Routes ---
from backend.pdraw.schemas import SimulationRequest, SimulationResponse, CatalogResponse
from backend.pdraw.catalog import get_pdraw_catalog
from backend.pdraw.simulator import simulate_pdraw

@app.get("/api/pdraw/catalog", response_model=CatalogResponse)
def get_catalog():
    return get_pdraw_catalog()

@app.post("/api/pdraw/simulate", response_model=SimulationResponse)
def simulate_ds(request: SimulationRequest):
    return simulate_pdraw(request)

# --- TGDraw (Phase 3) Routes ---
from backend.tgdraw.router import router as tgdraw_router
app.include_router(tgdraw_router)
