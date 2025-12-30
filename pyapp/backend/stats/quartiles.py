from typing import List, Dict
import math

def calculate_quartiles(data: List[float], method: str = 'exclusive') -> Dict[str, float]:
    """
    Calculates Q1, Q2 (Median), and Q3 based on the selected method.
    Methods: 'inclusive', 'exclusive', 'tukey' (Tukey is usually Exclusive logic 1.5 IQR range related, 
    but for quartiles calculation itself, exclusive/inclusive are standard).
    
    We will strictly implement Exclusive and Inclusive.
    'tukey' here maps to 'exclusive' as it's the standard for boxplots usually.
    """
    if not data:
        return {'q1': 0.0, 'q2': 0.0, 'q3': 0.0}
        
    sorted_data = sorted(data)
    n = len(sorted_data)
    
    def get_element(idx):
        """Helper to get element at float index (interpolated if necessary)"""
        if idx < 0: return sorted_data[0]
        if idx >= n: return sorted_data[-1]
        
        lower = math.floor(idx)
        upper = math.ceil(idx)
        
        if lower == upper:
            return sorted_data[int(idx)]
            
        weight = idx - lower
        return sorted_data[int(lower)] * (1 - weight) + sorted_data[int(upper)] * weight

    # Median (Q2) is same for all except small definitions variations, but we use standard median
    from .mean import calculate_median
    q2 = calculate_median(sorted_data)
    
    if method == 'inclusive':
        # Excel's PERCENTILE.INC
        # Index = p(N-1)
        q1_idx = 0.25 * (n - 1)
        q3_idx = 0.75 * (n - 1)
        q1 = get_element(q1_idx)
        q3 = get_element(q3_idx)
        
    else: # exclusive (default) and 'tukey'
        # Excel's PERCENTILE.EXC
        # Index = p(N+1) - 1 (0-based)
        q1_idx = 0.25 * (n + 1) - 1
        q3_idx = 0.75 * (n + 1) - 1
        q1 = get_element(q1_idx)
        q3 = get_element(q3_idx)

    return {'q1': q1, 'q2': q2, 'q3': q3}

def calculate_iqr(quartiles: Dict[str, float]) -> float:
    """
    Calculates Interquartile Range (IQR).
    Formula: Q3 - Q1
    """
    return quartiles['q3'] - quartiles['q1']

def detect_outliers(data: List[float], iqr: float, q1: float, q3: float) -> List[float]:
    """
    Detects outliers using the 1.5 * IQR rule.
    Lower Bound = Q1 - 1.5 * IQR
    Upper Bound = Q3 + 1.5 * IQR
    """
    lower_bound = q1 - (1.5 * iqr)
    upper_bound = q3 + (1.5 * iqr)
    
    outliers = [x for x in data if x < lower_bound or x > upper_bound]
    return sorted(outliers)
