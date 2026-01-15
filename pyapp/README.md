# VDraw 📊
### Advanced Statistical Analysis & Visualization Tool

[![Python](https://img.shields.io/badge/Python-3.8%2B-blue?logo=python)](https://www.python.org/)
[![FastAPI](https://img.shields.io/badge/FastAPI-0.95%2B-005571?logo=fastapi)](https://fastapi.tiangolo.com/)
[![TailwindCSS](https://img.shields.io/badge/Tailwind-CSS-38B2AC?logo=tailwind-css)](https://tailwindcss.com/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

**VDraw** is a modern, web-based application designed to make descriptive statistics accessible, interactive, and beautiful. Whether you are a student learning calculations step-by-step or a professional needing quick insights from data, VDraw covers it all.

---

## ✨ Features

### 🔢 Comprehensive Statistics
- **Measures of Center**: Mean, Median, Mode.
- **Measures of Spread**: Variance (Sample & Population), Standard Deviation, Range.
- **Quartile Analysis**: 
  - Choose between **Exclusive**, **Inclusive**, and **Tukey** methods.
  - Automatic **IQR** calculation and **Outlier** detection.

### 📈 Dynamic Visualizations
- **Interactive Charts**: Bar Charts and Line Charts (via Chart.js).
- **Advanced Plots**: Histograms and Box Plots (via Plotly.js).
- **Export Ready**: Download your charts instantly as high-quality **PNG** or **SVG**.

### 🎓 Educational Calculations
- **Step-by-Step Breakdown**: Don't just get the answer—see *how* it was calculated.
- **MathJax Support**: Beautifully rendered mathematical formulas (LaTeX) for Mean, Variance, Quartiles, and more.

### 🎨 Modern UI/UX
- **Glassmorphism Design**: Sleek, transclucent panels (Glass UI).
- **Theming**: One-click toggle between **Dark Mode** (default) and **Light Mode**.
- **Animations**: Silky smooth entry animations using **GSAP** (toggleable via "Advanced" mode).
- **Responsive**: Fully optimized for Desktop and Tablet use.

### 📂 Flexible Data Input
- **Manual Input**: Type or paste raw data directly.
- **File Upload**: Drag & drop support for **CSV** and **Excel (.xlsx)** files.
- **Smart Parsing**: Automatically detects numeric columns for analysis while preserving text columns for labels.

---

## 🛠️ Tech Stack

- **Backend**: Python, FastAPI, Pandas, Uvicorn.
- **Frontend**: Standard HTML5, JavaScript (ES6+), TailwindCSS (CDN).
- **Libraries**:
  - `Chart.js` (Trend visualization)
  - `Plotly.js` (Statistical plotting)
  - `MathJax` (Formula rendering)
  - `GSAP` (Animations)

---

## 🚀 Getting Started

### Prerequisites
- Python 3.8 or higher installed.

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/faisal117a/vdraw.git
   cd vdraw
   ```

2. **Install Backend Dependencies**
   ```bash
   pip install -r requirements.txt
   ```

3. **Run the Backend API**
   ```bash
   python -m uvicorn backend.main:app --reload --host 127.0.0.1 --port 8000
   ```

4. **Run the Frontend**
   You can serve the `frontend` folder using any static server. For example, using Python:
   ```bash
   # In a new terminal window
   python -m http.server 3000 --directory frontend
   ```
   *Note: Using a web server is recommended to avoid CORS issues with local files.*

5. **Access the App**
   Open your browser and navigate to:
   [http://localhost:3000](http://localhost:3000)

---

## 📸 Screenshots

*(Add screenshots of your dashboard here)*

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

---

Made with ❤️ by [Faisal H](https://github.com/faisal117a)
