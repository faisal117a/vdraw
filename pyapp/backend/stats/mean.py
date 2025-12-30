from typing import List, Union, Dict

def calculate_mean(data: List[float]) -> float:
    """
    Calculates the arithmetic mean of a list of numbers.
    Formula: Σx / N
    """
    if not data:
        return 0.0
    return sum(data) / len(data)

def calculate_median(data: List[float]) -> float:
    """
    Calculates the median of a list of numbers.
    If N is odd, returns the middle element.
    If N is even, returns the average of the two middle elements.
    """
    if not data:
        return 0.0
    
    sorted_data = sorted(data)
    n = len(sorted_data)
    mid_index = n // 2
    
    if n % 2 == 1:
        return sorted_data[mid_index]
    else:
        return (sorted_data[mid_index - 1] + sorted_data[mid_index]) / 2

def calculate_mode(data: List[float]) -> List[float]:
    """
    Calculates the mode(s) of a list of numbers.
    Returns a list of modes. If all values are unique, returns an empty list (no mode).
    """
    if not data:
        return []
        
    frequency = {}
    for value in data:
        frequency[value] = frequency.get(value, 0) + 1
        
    max_freq = max(frequency.values())
    
    # If all values appear only once, there is no mode
    if max_freq == 1:
        return []
        
    modes = [key for key, val in frequency.items() if val == max_freq]
    return sorted(modes)
