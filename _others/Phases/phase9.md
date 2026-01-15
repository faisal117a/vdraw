# Phase 9 – My Programs Feature

This document contains **full instructions for Antigravity** to implement the **My Programs** feature and the **predefined Python programs** used by this feature.

---

## 1. Feature Overview

Add a new button named **My Programs** in the ToolBox.

- Position: **After the "Dry Run" button**
- Purpose: Allow students to insert predefined basic Python programs into the playground

Target users:
- Grade 10 and Grade 11 students
- Beginners in Python

---

## 2. UI Requirements

### 2.1 ToolBox Button

- Label: **My Programs**
- Location: ToolBox (after Dry Run)
- Action: Opens a modal or side panel

---

### 2.2 My Programs Panel

The panel must contain:

1. **Search Box** (top)
2. **Programs List**
3. **Insert Button** for each program
4. **Pagination Controls**

---

### 2.3 Pagination Rules

- Maximum programs: **30**
- Programs per page: **10**
- Total pages: **3**
- Pagination must update dynamically

---

### 2.4 Search Rules

- Search input filters programs by:
  - Title
  - Keywords
  - Code content

Example:
- Searching `for` shows all programs that use a `for` loop

Search must work across **all pages**.

---

## 3. Insert Behavior

- When user clicks **Insert**:
  - Program code is fetched from a **fixed JavaScript data file**
  - Code is **appended at the end** of the playground editor
  - If playground already has content, insert **one blank line** before adding code

No confirmation popup is required.

---

## 4. Data Architecture (Important)

Programs must be stored as **data**, not hardcoded logic.

### 4.1 Fixed JavaScript Data File

Each program must be stored as an object with the following structure:

- `id` (string, unique)
- `title` (string)
- `category` (string)
- `keywords` (array of strings)
- `code` (string, raw Python code)

This structure must allow **easy addition of new programs later** without UI changes.

---

## 5. Python Coding Rules (Strict)

All predefined programs must follow these rules:

- Python only
- Plain script (NO functions)
- Simple variable names
- No comments
- No advanced syntax
- Use only:
  - `input()`
  - `print()`
  - `if / elif / else`
  - `for`
  - `while`
- Type casting allowed using `int(input())`
- Output text must be **simple English words only**

---

## 6. Predefined Programs List (20 Total)

Below are the **exact Python programs** to be included.

---

### 1. Check Vowel or Consonant

```python
a = input()
if a == 'a' or a == 'e' or a == 'i' or a == 'o' or a == 'u':
    print("Vowel")
else:
    print("Consonant")
```

---

### 2. Largest of Three Numbers

```python
a = int(input())
b = int(input())
c = int(input())
if a >= b and a >= c:
    print(a)
elif b >= a and b >= c:
    print(b)
else:
    print(c)
```

---

### 3. Check Leap Year

```python
y = int(input())
if y % 400 == 0 or (y % 4 == 0 and y % 100 != 0):
    print("Leap Year")
else:
    print("Not Leap Year")
```

---

### 4. Check Positive, Negative or Zero

```python
n = int(input())
if n > 0:
    print("Positive")
elif n < 0:
    print("Negative")
else:
    print("Zero")
```

---

### 5. Divisible by 5 and 11

```python
n = int(input())
if n % 5 == 0 and n % 11 == 0:
    print("Divisible")
else:
    print("Not Divisible")
```

---

### 6. Check Square

```python
a = int(input())
b = int(input())
if b == a * a:
    print("Yes")
else:
    print("No")
```

---

### 7. Swap Two Numbers

```python
a = int(input())
b = int(input())
print(a, b)
temp = a
a = b
b = temp
print(a, b)
```

---

### 8. Area of Triangle

```python
b = int(input())
h = int(input())
area = (b * h) / 2
print(area)
```

---

### 9. Factorial Using While Loop

```python
n = int(input())
f = 1
i = 1
while i <= n:
    f = f * i
    i = i + 1
print(f)
```

---

### 10. Table of a Number

```python
n = int(input())
i = 1
while i <= 10:
    print(n * i)
    i = i + 1
```

---

### 11. First 10 Even Numbers, Sum and Average

```python
s = 0
count = 0
i = 2
while count < 10:
    print(i)
    s = s + i
    i = i + 2
    count = count + 1
print(s)
print(s / 10)
```

---

### 12. Pattern Using For Loop

```python
for i in range(1, 10, 2):
    print(i, i * i)
```

---

### 13. Display Series 3 6 9 12 15 18 21

```python
for i in range(3, 22, 3):
    print(i)
```

---

### 14. Sum of First N Natural Numbers

```python
n = int(input())
s = 0
for i in range(1, n + 1):
    s = s + i
print(s)
```

---

### 15. Count Digits in a Number

```python
n = int(input())
count = 0
while n > 0:
    count = count + 1
    n = n // 10
print(count)
```

---

### 16. Reverse a Number

```python
n = int(input())
rev = 0
while n > 0:
    r = n % 10
    rev = rev * 10 + r
    n = n // 10
print(rev)
```

---

### 17. Check Even or Odd

```python
n = int(input())
if n % 2 == 0:
    print("Even")
else:
    print("Odd")
```

---

### 18. Print First N Numbers

```python
n = int(input())
for i in range(1, n + 1):
    print(i)
```

---

### 19. Find Smallest of Two Numbers

```python
a = int(input())
b = int(input())
if a < b:
    print(a)
else:
    print(b)
```

---

### 20. Sum of Digits

```python
n = int(input())
s = 0
while n > 0:
    r = n % 10
    s = s + r
    n = n // 10
print(s)
```

---

## 7. Completion Criteria

Phase 9 is complete when:

- My Programs button is visible
- Programs list loads from JS data file
- Search works correctly
- Pagination works correctly
- Insert appends code into playground
- New programs can be added without UI changes

---

## 8. Next Recommended Phase

**Phase 10: Playground Safety Rules**
- Allow only safe keywords
- Block imports, eval, exec, system access
- Block suspicious instructions

