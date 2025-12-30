from typing import List
import math
from .mean import calculate_mean

def calculate_range(data: List[float]) -> float:
    """
    Calculates the range of the dataset.
    Formula: Max - Min
    """
    if not data:
        return 0.0
    return max(data) - min(data)

def calculate_variance(data: List[float], is_sample: bool = True) -> float:
    """
    Calculates variance.
    Population variance: Σ(x - μ)² / N
    Sample variance: Σ(x - x̄)² / (n - 1)
    """
    if not data:
        return 0.0
    n = len(data)
    if is_sample and n < 2:
        return 0.0 # Cannot calculate sample variance with significant degrees of freedom for n < 2
        
    mean = calculate_mean(data)
    squared_diff_sum = sum((x - mean) ** 2 for x in data)
    
    divisor = n - 1 if is_sample else n
    return squared_diff_sum / divisor

def calculate_std_dev(data: List[float], is_sample: bool = True) -> float:
    """
    Calculates standard deviation.
    Formula: sqrt(Variance)
    """
    variance = calculate_variance(data, is_sample)
    return math.sqrt(variance)
