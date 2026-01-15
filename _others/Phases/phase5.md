# Phase 5 — Dviz (Document Visualization)

This document defines **Phase 5** in a clear, implementation‑ready manner based on finalized decisions.

---

## ✅ Finalized Technical Decisions

| Item | Decision |
|----|----|
| UI Framework | Tailwind CSS (allowed & recommended) |
| Data Loading | Static build‑time scan → JSON index |
| Navigation | URL‑based routing |
| Access | Public (no auth for now) |
| PDF Rendering | iframe (inline, no new tab) |
| Chapter Naming | Real chapter names (derived from data/index) |

---

## 1. Physical Folder Structure (Source of Truth)

```
frontend/
 └── data/
     ├── part1/
     │   ├── chapter1/
     │   │   ├── slides/
     │   │   ├── images/
     │   │   └── videos/
     │   ├── chapter2/
     │   └── ...
     │   └── chapter9/
     ├── part2/
     │   ├── chapter1/
     │   └── ...
     │   └── chapter14/
```

No hardcoding. Everything is auto‑discovered from this structure.

---

## 2. Dashboard Terminology (User‑Facing)

| Backend Folder | Dviz Label |
|--------------|-----------|
| part* | **Level** |
| chapter* | **Visual** |
| slides | **Presentations** |
| images | **Ideas** |
| videos | **Videos** |

---

## 3. URL‑Based Navigation Structure

```
/                     → Dashboard Home
/level/1              → All Visuals in Level 1
/level/1/visual/1     → Visual detail page
/level/1/visual/1/short-questions
/level/1/visual/1/mcqs
/level/1/visual/1/summary
/level/1/visual/1/videos
/level/1/visual/1/ideas
```

Deep‑linking supported for all views.

---

## 4. Sidebar Behavior

Left Sidebar (persistent):

- Explore Level 1
- Explore Level 2
- (future‑ready for Level 3+)

Clicking a level loads its visuals in main content area.

---

## 5. Level View (Visual Grid)

When a user selects **Explore Level X**:

- Main area shows all **Visuals (chapters)** in that level
- Grid layout: **3 Visual cards per row**
- Each card contains:
  - Icon
  - Title: Real chapter name
  - Subtitle (optional): Presentations • Videos • Ideas

---

## 6. Visual Detail View (Category Cards)

Clicking a Visual opens 5 category cards:

| Card Title | Source Folder |
|----------|--------------|
| Short Questions Presentation | slides/s*.pdf |
| MCQs Presentation | slides/m*.pdf |
| Visual Summary | slides/i*.pdf |
| Videos | videos/*.mp4 |
| Ideas | images/*.png / *.jpg |

Each card shows count badge and icon.

---

## 7. Filename Rules & Intelligence

### 7.1 Prefix Meaning (Extensible)

```
s* → Short Questions Presentation
m* → MCQs Presentation
i* → Visual Summary
```

Future prefixes can be added without UI changes.

---

### 7.2 Display Name Extraction Formula

All these filenames resolve to the same display name:

```
s_Software_Development.pdf
s1_Software_Development.pdf
s Software Development.pdf
s-Software-Development.pdf
```

➡ Displayed as:

```
Software Development
```

#### Cleaning Logic (Universal):
1. Remove prefix (s, s1, m, m2, i, etc.)
2. Remove `_`, `-`
3. Remove extension
4. Trim spaces
5. Convert to Title Case

Applies to **PDFs, Images, Videos**.

---

## 8. Asset Rendering Rules

### 8.1 PDF Viewer

- Render **inside main container**
- Uses iframe (no new browser tab)
- Controls:
  - Page navigation
  - Zoom
  - Fullscreen
  - Download

---

### 8.2 Images (Ideas)

- Open in **lightbox overlay**
- Features:
  - Next / Previous
  - Fullscreen
  - Close button
  - Keyboard navigation (optional)

---

### 8.3 Videos

- Render inline in main container
- Native video controls:
  - Play / Pause
  - Seek bar
  - Volume
  - Fullscreen

---

## 9. Search & Filtering

Available on final listing pages:

- Short Questions
- MCQs
- Videos
- Ideas

### Behavior:
- Client‑side filtering
- Real‑time search
- Matches against cleaned display names

---

## 10. Static Build‑Time JSON Index

At build time, folder scan generates:

```
/frontend/index.json
```

### Responsibilities:
- Map Levels → Visuals → Categories → Files
- Store real chapter names
- Store cleaned display titles
- Store relative asset paths

Frontend consumes only this index.

---

## 11. Extensibility Principles

- No hardcoded filenames
- No hardcoded categories
- Prefix‑driven behavior
- Folder‑driven discovery

Future additions (example):

```
p* → Practical Presentation
a* → Assignments
r* → Revision Slides
```

UI adapts automatically.

---

## 12. Design Direction

- Tailwind‑based
- Clean, modern, card‑driven
- Subtle animations
- Icon‑first UX
- Dark / Light mode ready
- Educational but not boring

---

## 13. Phase 5 Deliverables

✔ Architecture finalized
✔ UX flow finalized
✔ Naming & rules locked
✔ Ready for implementation

Next:

**Phase 5.1 — JSON Index Generator**  
**Phase 5.2 — UI Components & Routing**  
**Phase 5.3 — Viewer & Search Implementation**

---

_End of Phase 5 specification._

