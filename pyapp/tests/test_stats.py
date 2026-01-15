import unittest
import sys
import os

# Add the project root to the python path
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))

from backend.stats.mean import calculate_mean, calculate_median, calculate_mode
from backend.stats.variance import calculate_variance, calculate_std_dev, calculate_range
from backend.stats.quartiles import calculate_quartiles, calculate_iqr, detect_outliers

class TestStats(unittest.TestCase):
    
    def test_central_tendency(self):
        data = [1, 2, 2, 3, 4]
        self.assertEqual(calculate_mean(data), 2.4)
        self.assertEqual(calculate_median(data), 2.0)
        self.assertEqual(calculate_mode(data), [2])
        
        data_even = [1, 2, 3, 4]
        self.assertEqual(calculate_median(data_even), 2.5)
        
        data_no_mode = [1, 2, 3]
        self.assertEqual(calculate_mode(data_no_mode), [])
        
        data_multi_mode = [1, 1, 2, 2, 3]
        self.assertEqual(calculate_mode(data_multi_mode), [1, 2])

    def test_dispersion(self):
        data = [2, 4, 4, 4, 5, 5, 7, 9]
        # Mean = 5
        # Sum((x-mean)^2) = 9 + 1 + 1 + 1 + 0 + 0 + 4 + 16 = 32
        
        # Population Variance = 32 / 8 = 4
        self.assertEqual(calculate_variance(data, is_sample=False), 4.0)
        self.assertEqual(calculate_std_dev(data, is_sample=False), 2.0)
        
        # Sample Variance = 32 / 7 = 4.5714...
        self.assertAlmostEqual(calculate_variance(data, is_sample=True), 4.57142857, places=5)
        
        self.assertEqual(calculate_range(data), 7)

    def test_quartiles_exclusive(self):
        # 1, 3, 5, 7, 9, 11, 13, 15 (n=8)
        # Exclusive: (n+1) * 0.25 = 2.25 -> index 1.25 (0-based) -> val at 1 is 3, val at 2 is 5 -> 3 + 0.25*(2) = 3.5
        data = [1, 3, 5, 7, 9, 11, 13, 15]
        quartiles = calculate_quartiles(data, method='exclusive')
        self.assertEqual(quartiles['q2'], 8.0) # Median
        self.assertEqual(quartiles['q1'], 3.5)
        
    def test_quartiles_inclusive(self):
        # 0, 10, 20, 30 (n=4)
        # Inclusive: (n-1)*0.25 = 0.75 -> index 0.75 -> val at 0 is 0, val at 1 is 10 -> 0 + 0.75*10 = 7.5
        data = [0, 10, 20, 30]
        quartiles = calculate_quartiles(data, method='inclusive')
        self.assertEqual(quartiles['q1'], 7.5)
        
    def test_outliers(self):
        # Q1=2, Q3=10, IQR=8 -> Bounds: -10, 22
        # Outlier: 50
        iqr = 8
        q1 = 2
        q3 = 10
        data = [2, 5, 8, 50]
        self.assertEqual(detect_outliers(data, iqr, q1, q3), [50])

if __name__ == '__main__':
    unittest.main()
