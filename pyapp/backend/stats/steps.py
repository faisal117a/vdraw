from typing import List, Dict, Union
import math

def explain_mean(data: List[float]) -> Dict:
    n = len(data)
    total = sum(data)
    mean = total / n if n > 0 else 0
    
    steps = [
        {"text": "1. Sum all the values (Σx).", "latex": r"\sum x"},
        {"text": f"   { ' + '.join(map(str, data[:5])) }{' + ...' if n > 5 else ''} = {total}", "latex": ""},
        {"text": "2. Count the number of values (N).", "latex": "N"},
        {"text": f"   N = {n}", "latex": ""},
        {"text": "3. Divide the sum by N.", "latex": r"\bar{x} = \frac{\sum x}{N}"},
        {"text": f"   {total} / {n} = {mean:.4f}", "latex": f"{total} / {n}"}
    ]
    
    return {
        "title": "Mean Calculation",
        "result": str(mean),
        "steps": steps
    }

def explain_median(data: List[float]) -> Dict:
    n = len(data)
    sorted_data = sorted(data)
    
    steps = [{"text": "1. Sort the data in ascending order.", "latex": ""}]
    
    # Show truncated sorted list if too long
    sorted_str = ', '.join(map(str, sorted_data[:10]))
    if n > 10:
        sorted_str += ", ..."
    steps.append({"text": f"   {sorted_str}", "latex": ""})
    
    mid_index = n // 2
    
    if n % 2 == 1:
        median = sorted_data[mid_index]
        steps.append({"text": f"2. Since N ({n}) is odd, the median is the middle value at position (N+1)/2.", "latex": r"\frac{N+1}{2}"})
        steps.append({"text": f"   Position {mid_index + 1}: {median}", "latex": ""})
    else:
        val1 = sorted_data[mid_index - 1]
        val2 = sorted_data[mid_index]
        median = (val1 + val2) / 2
        steps.append({"text": f"2. Since N ({n}) is even, the median is the average of the two middle values.", "latex": ""})
        steps.append({"text": f"   Values at positions {mid_index} and {mid_index + 1} are {val1} and {val2}.", "latex": ""})
        steps.append({"text": f"   ({val1} + {val2}) / 2 = {median}", "latex": r"\frac{x_{n/2} + x_{n/2+1}}{2}"})
        
    return {
        "title": "Median Calculation",
        "result": str(median),
        "steps": steps
    }

def explain_variance_sample(data: List[float], mean: float) -> Dict:
    n = len(data)
    if n < 2:
        return {"title": "Variance", "steps": [{"text": "Sample variance requires at least 2 data points."}]}
        
    diffs = []
    sq_diffs = []
    for x in data[:3]:
        d = x - mean
        diffs.append(f"({x} - {mean:.2f})")
        sq_diffs.append(f"{d:.2f}^2")
    
    sum_sq_diff = sum((x - mean)**2 for x in data)
    variance = sum_sq_diff / (n - 1)
    
    steps = [
        {"text": "1. Calculate the mean (x̄).", "latex": r"\bar{x}"},
        {"text": f"   x̄ = {mean:.2f}", "latex": ""},
        {"text": "2. Calculate squared deviations from the mean for each point.", "latex": r"(x - \bar{x})^2"},
        {"text": f"   e.g., {', '.join(diffs)} ...", "latex": ""},
        {"text": "3. Sum all squared deviations.", "latex": r"\sum (x - \bar{x})^2"},
        {"text": f"   Sum = {sum_sq_diff:.2f}", "latex": ""},
        {"text": "4. Divide by (n - 1) for sample variance.", "latex": r"s^2 = \frac{\sum (x - \bar{x})^2}{n - 1}"},
        {"text": f"   {sum_sq_diff:.2f} / {n - 1} = {variance:.4f}", "latex": ""}
    ]
    
    return {
        "title": "Sample Variance",
        "result": f"{variance:.4f}",
        "steps": steps
    }

def explain_variance_population(data: List[float], mean: float) -> Dict:
    n = len(data)
    sum_sq_diff = sum((x - mean)**2 for x in data)
    variance = sum_sq_diff / n
    
    steps = [
        {"text": "1. Calculate the mean (μ).", "latex": r"\mu"},
        {"text": f"   μ = {mean:.2f}", "latex": ""},
        {"text": "2. Sum all squared deviations.", "latex": r"\sum (x - \mu)^2"},
        {"text": f"   Sum = {sum_sq_diff:.2f}", "latex": ""},
        {"text": "3. Divide by N for population variance.", "latex": r"\sigma^2 = \frac{\sum (x - \mu)^2}{N}"},
        {"text": f"   {sum_sq_diff:.2f} / {n} = {variance:.4f}", "latex": ""}
    ]
    
    return {
        "title": "Population Variance",
        "result": f"{variance:.4f}",
        "steps": steps
    }

def explain_std_dev(variance: float, is_sample: bool) -> Dict:
    symbol = "s" if is_sample else "\\sigma"
    var_symbol = "s^2" if is_sample else "\\sigma^2"
    
    steps = [
        {"text": "1. Start with the calculated variance.", "latex": f"{var_symbol} = {variance:.4f}"},
        {"text": "2. Take the square root of the variance.", "latex": f"{symbol} = \\sqrt{{{var_symbol}}}"},
        {"text": f"   √{variance:.4f} = {math.sqrt(variance):.4f}", "latex": ""}
    ]
    
    return {
        "title": "Standard Deviation",
        "result": f"{math.sqrt(variance):.4f}",
        "steps": steps
    }

def explain_quartiles(data: List[float], method: str, quartiles: Dict[str, float]) -> Dict:
    n = len(data)
    sorted_data = sorted(data)
    
    steps = [{"text": f"1. Sort data (N={n})", "latex": ""}]
    
    if method == 'inclusive':
        # Simple explanation for Inclusive (Index logic)
        steps.append({"text": "Method: Inclusive (Excel PERCENTILE.INC)", "latex": ""})
        steps.append({"text": "Index = p(N-1)", "latex": ""})
        steps.append({"text": f"Q1 Index = 0.25 * ({n}-1) = {0.25*(n-1)}", "latex": ""})
        steps.append({"text": f"Q3 Index = 0.75 * ({n}-1) = {0.75*(n-1)}", "latex": ""})
    else:
        # Simple explanation for Exclusive
        steps.append({"text": "Method: Exclusive (Excel PERCENTILE.EXC)", "latex": ""})
        steps.append({"text": "Index = p(N+1) - 1 (0-based)", "latex": ""})
        steps.append({"text": f"Q1 Index = 0.25 * ({n}+1) - 1 = {0.25*(n+1)-1}", "latex": ""})
        steps.append({"text": f"Q3 Index = 0.75 * ({n}+1) - 1 = {0.75*(n+1)-1}", "latex": ""})
        
    steps.append({"text": "Interpolate values at calculated indices.", "latex": ""})
    
    return {
        "title": f"Quartiles ({method.title()})",
        "result": f"Q1={quartiles['q1']:.2f}, Q3={quartiles['q3']:.2f}",
        "steps": steps
    }

def explain_iqr(q1: float, q3: float) -> Dict:
    steps = [
        {"text": "1. Subtract Q1 from Q3.", "latex": "IQR = Q3 - Q1"},
        {"text": f"   {q3:.2f} - {q1:.2f} = {q3 - q1:.2f}", "latex": ""}
    ]
    return {
        "title": "Interquartile Range (IQR)",
        "result": f"{q3 - q1:.2f}",
        "steps": steps
    }

def explain_linear_regression(slope: float, intercept: float, r2: float) -> Dict:
    steps = [
        {"text": "1. The linear regression model fits a line to minimize squared errors.", "latex": "y = mx + b"},
        {"text": f"2. Calculated Slope (m) = {slope:.4f}", "latex": ""},
        {"text": f"3. Calculated Intercept (b) = {intercept:.4f}", "latex": ""},
        {"text": f"4. Equation of the line:", "latex": f"y = {slope:.4f}x + {intercept:.4f}"},
        {"text": f"5. R-squared value (Goodness of fit): {r2:.4f}", "latex": f"R^2 = {r2:.4f}"}
    ]
    return {
        "title": "Linear Regression",
        "result": f"y = {slope:.2f}x + {intercept:.2f}",
        "steps": steps
    }

def explain_logistic_regression(accuracy: float, coef: List[float], intercept: Union[float, List[float]]) -> Dict:
    steps = [
        {"text": "1. Logistic regression models the probability of class membership.", "latex": r"P(y=k) = \text{softmax}(z_k)"},
        {"text": f"2. Model Accuracy on training data: {accuracy*100:.2f}%", "latex": ""}
    ]
    
    is_multiclass = isinstance(intercept, list)
    
    if is_multiclass:
        steps.append({"text": "3. Multiclass Model (One-vs-Rest or Multinomial).", "latex": ""})
        steps.append({"text": f"   Number of classes: {len(intercept)}", "latex": ""})
        steps.append({"text": "   Coefficients are calculated for each class relative to others.", "latex": ""})
    else:
        # Binary
        c_val = coef[0] if len(coef) > 0 else 0
        steps.append({"text": "3. Binary Model (Sigmoid Function).", "latex": r"P(y=1) = \frac{1}{1 + e^{-(mx + b)}}"})
        steps.append({"text": f"   Model Parameters: m = {c_val:.4f}, b = {intercept:.4f}", "latex": ""})
        
    return {
        "title": "Logistic Regression",
        "result": f"Acc: {accuracy*100:.1f}%",
        "steps": steps
    }
