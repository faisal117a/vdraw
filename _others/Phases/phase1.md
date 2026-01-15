# 📊 VDraw – Descriptive Statistics Learning App (Plan)

## Version
MVP v1

## Target Audience
College / University students

## Purpose
Primary: Descriptive statistics calculator  
Secondary: Learning aid with optional step-by-step explanations

---

## 1. Core Objectives

- Provide accurate descriptive statistics
- Allow students to understand calculations through steps
- Visualize data interactively
- Offer a clean, modern, dark-first UI
- Work fully without AI, enhanced optionally with AI
- Public app, no authentication

---

## 2. Statistics Supported

- Mean
- Median
- Mode (multi-mode support, show all modes; if all duplicate → no mode)
- Range
- Variance
  - Population
  - Sample
- Standard Deviation
- Quartiles (Q1, Q2, Q3)
  - Inclusive
  - Exclusive
  - Tukey
- Interquartile Range (IQR)
- Outlier detection (1.5 × IQR rule)

---

## 3. Data Input System

### 3.1 Manual Input
- Spreadsheet-like editable grid/table
- Multiline text paste support
- Example input format shown to students

### 3.2 File Upload
- Supported formats:
  - CSV
  - XLSX
- File size limit: **1–3 MB**
- Maximum numeric values allowed: **1000**

#### CSV Handling
- Auto-detect separator
- Manual override (comma, semicolon, tab, etc.)
- Decimal formats supported:
  - 12.5
  - 12,5

#### Missing Values (User Choice)
- Ignore
- Throw error
- Treat as 0
- Ask user

#### Non-numeric Columns
- Automatically hidden

---

## 4. Column Selection & Comparison

- Auto-select first numeric column
- Allow multiple column selection
- Enable comparative statistics
- Grouped comparisons (e.g., Class A vs Class B): **future version**

---

## 5. Visualization System

### Chart Libraries
- Chart.js
- Plotly
(User selectable)

### Chart Types
- Bar chart
- Line chart (user-selectable X-axis)
- Histogram (auto bins only)
- Scatter plot (requires X + Y selection)
- Boxplot (single column only)

### Export Options
- PNG
- SVG

---

## 6. Statistical Engine (Backend)

### Implementation Rules
- Pure Python math logic
- No black-box shortcuts
- Pandas used only for parsing and data cleaning
- All formulas explicitly coded

### Variance Definitions

Population variance:

Σ(x − μ)² / N

Sample variance:

Σ(x − x̄)² / (n − 1)

### Output Precision
- Automatic rounding
- Exact fractions / arithmetic shown in steps

---

## 7. Step-by-Step Explanation Engine

### Display Modes
- Plain text
- LaTeX-style (MathJax rendering)

### Step Structure
1. Formula
2. Substitution
3. Intermediate calculations
4. Final result

### Common Mistakes (Basic Level)
- Sample vs population variance confusion
- Median calculation for even number of values
- No-mode scenarios

---

## 8. Optional AI Integration (DeepSeek)

### Purpose
- Convert mathematical steps into natural-language explanations for students

### Rules
- Disabled by default
- User-controlled toggle
- API key stored in `.env`
- Privacy notice displayed when AI is enabled

### Fallback
- Application works fully without AI

---

## 9. Frontend Architecture

### Layout Structure
- Single-page dashboard
- Left sidebar:
  - Input method
  - Upload / manual entry
  - Column selection
  - Chart selection
  - Statistical options
  - Theme toggle
- Main workspace:
  - Charts
- Right-side panel:
  - Step-by-step output
  - AI explanation (if enabled)

### Theme
- Dark mode by default
- Manual toggle
- Preference stored in localStorage

---

## 10. UI & Animations

### Styling
- Tailwind CSS (CDN-based setup)

### Animations (GSAP)
- Sidebar transitions
- Step-by-step typing effect
- Chart reveal animations
- Modals and toast notifications

### Accessibility
- Keyboard navigation
- Proper contrast ratios
- Clear focus states

---

## 11. Validation & Error Handling

### Input Validation
- Highlight invalid values
- Inline hints with examples

### Edge Cases
- Empty dataset → friendly instructional message
- Single value → explain statistical limitations
- All values same → no variance / no mode explanation

### Limits
- Hard block above 1000 values
- Clear, student-friendly error messages

---

## 12. Backend Stack

- Python
- FastAPI
- Pydantic for validation

### Planned API Endpoints
- POST `/api/parse-data`
- POST `/api/calculate`
- POST `/api/explain` (AI optional)

---

## 13. Project Folder Structure

```
vdraw/
├── backend/
│   ├── main.py
│   ├── stats/
│   │   ├── mean.py
│   │   ├── variance.py
│   │   ├── quartiles.py
│   │   └── helpers.py
│   ├── ai/
│   │   └── deepseek.py
│   ├── validators/
│   └── config.py
│
├── frontend/
│   ├── index.html
│   ├── js/
│   │   ├── app.js
│   │   ├── charts.js
│   │   ├── steps.js
│   │   └── validation.js
│   └── css/
│
├── uploads/
├── .env
└── README.md
```

---

## 14. Deployment

- Apache web server
- Real folder deployment at `/vdraw`
- Upload limits enforced at:
  - Apache level
  - Backend level
- Rate limiting enabled

---

## 15. Development Workflow (Cursor / Zed)

- Brain models used:
  - Codex (structure, refactoring)
  - DeepSeek (logic and explanations)

### Build Order
1. Statistical math engine
2. API layer
3. UI layout
4. Chart integration
5. AI enhancements

---

## 16. Future Enhancements

- Grouped datasets
- Save / load worksheets
- PDF export
- Practice question generator
- Teacher mode

