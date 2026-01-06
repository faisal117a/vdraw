/**
 * Predefined Python Programs Data
 * Phase 9 Requirement
 */

window.MY_PROGRAMS_DATA = [
    {
        id: "prog_01",
        title: "Check Vowel or Consonant",
        category: "Conditionals",
        keywords: ["vowel", "consonant", "if", "else", "input"],
        code: `a = input()
if a == 'a' or a == 'e' or a == 'i' or a == 'o' or a == 'u' or a == 'A' or a == 'E' or a == 'I' or a == 'O' or a == 'U':
    print("Vowel")
else:
    print("Consonant")`
    },
    {
        id: "prog_02",
        title: "Largest of Three Numbers",
        category: "Conditionals",
        keywords: ["largest", "max", "compare", "if", "elif", "else"],
        code: `a = int(input())
b = int(input())
c = int(input())
if a >= b and a >= c:
    print(a)
elif b >= a and b >= c:
    print(b)
else:
    print(c)`
    },
    {
        id: "prog_03",
        title: "Check Leap Year",
        category: "Conditionals",
        keywords: ["leap", "year", "date", "modulo", "if"],
        code: `y = int(input())
if y % 400 == 0 or (y % 4 == 0 and y % 100 != 0):
    print("Leap Year")
else:
    print("Not Leap Year")`
    },
    {
        id: "prog_04",
        title: "Check Positive, Negative or Zero",
        category: "Conditionals",
        keywords: ["positive", "negative", "zero", "sign", "check"],
        code: `n = int(input())
if n > 0:
    print("Positive")
elif n < 0:
    print("Negative")
else:
    print("Zero")`
    },
    {
        id: "prog_05",
        title: "Divisible by 5 and 11",
        category: "Conditionals",
        keywords: ["divisible", "modulo", "check", "math"],
        code: `n = int(input())
if n % 5 == 0 and n % 11 == 0:
    print("Divisible")
else:
    print("Not Divisible")`
    },
    {
        id: "prog_06",
        title: "Check Square",
        category: "Conditionals",
        keywords: ["square", "rectangle", "shape", "check"],
        code: `a = int(input())
b = int(input())
if b == a * a:
    print("Yes")
else:
    print("No")`
    },
    {
        id: "prog_07",
        title: "Swap Two Numbers",
        category: "Basic",
        keywords: ["swap", "temp", "exchange", "variable"],
        code: `a = int(input())
b = int(input())
print(a, b)
temp = a
a = b
b = temp
print(a, b)`
    },
    {
        id: "prog_08",
        title: "Area of Triangle",
        category: "Basic",
        keywords: ["area", "triangle", "geometry", "math"],
        code: `b = int(input())
h = int(input())
area = (b * h) / 2
print(area)`
    },
    {
        id: "prog_09",
        title: "Factorial Using While Loop",
        category: "Loops",
        keywords: ["factorial", "while", "loop", "math"],
        code: `n = int(input())
f = 1
i = 1
while i <= n:
    f = f * i
    i = i + 1
print(f)`
    },
    {
        id: "prog_10",
        title: "Table of a Number",
        category: "Loops",
        keywords: ["table", "multiplication", "while", "loop"],
        code: `n = int(input())
i = 1
while i <= 10:
    print(n * i)
    i = i + 1`
    },
    {
        id: "prog_11",
        title: "First 10 Even Numbers, Sum and Average",
        category: "Loops",
        keywords: ["even", "sum", "average", "while", "loop"],
        code: `s = 0
count = 0
i = 2
while count < 10:
    print(i)
    s = s + i
    i = i + 2
    count = count + 1
print(s)
print(s / 10)`
    },
    {
        id: "prog_12",
        title: "Pattern Using For Loop",
        category: "Loops",
        keywords: ["pattern", "for", "loop", "range", "square"],
        code: `for i in range(1, 10, 2):
    print(i, i * i)`
    },
    {
        id: "prog_13",
        title: "Display Series 3 6 9 12 15 18 21",
        category: "Loops",
        keywords: ["series", "sequence", "for", "loop", "step"],
        code: `for i in range(3, 22, 3):
    print(i)`
    },
    {
        id: "prog_14",
        title: "Sum of First N Natural Numbers",
        category: "Loops",
        keywords: ["sum", "natural", "numbers", "for", "loop"],
        code: `n = int(input())
s = 0
for i in range(1, n + 1):
    s = s + i
print(s)`
    },
    {
        id: "prog_15",
        title: "Count Digits in a Number",
        category: "Loops",
        keywords: ["count", "digits", "while", "loop", "number"],
        code: `n = int(input())
count = 0
while n > 0:
    count = count + 1
    n = n // 10
print(count)`
    },
    {
        id: "prog_16",
        title: "Reverse a Number",
        category: "Loops",
        keywords: ["reverse", "number", "while", "loop", "digits"],
        code: `n = int(input())
rev = 0
while n > 0:
    r = n % 10
    rev = rev * 10 + r
    n = n // 10
print(rev)`
    },
    {
        id: "prog_17",
        title: "Check Even or Odd",
        category: "Conditionals",
        keywords: ["even", "odd", "modulo", "check"],
        code: `n = int(input())
if n % 2 == 0:
    print("Even")
else:
    print("Odd")`
    },
    {
        id: "prog_18",
        title: "Print First N Numbers",
        category: "Loops",
        keywords: ["print", "numbers", "range", "for", "loop"],
        code: `n = int(input())
for i in range(1, n + 1):
    print(i)`
    },
    {
        id: "prog_19",
        title: "Find Smallest of Two Numbers",
        category: "Conditionals",
        keywords: ["smallest", "minimum", "compare", "if", "else"],
        code: `a = int(input())
b = int(input())
if a < b:
    print(a)
else:
    print(b)`
    },
    {
        id: "prog_20",
        title: "Sum of Digits",
        category: "Loops",
        keywords: ["sum", "digits", "while", "loop", "number"],
        code: `n = int(input())
s = 0
while n > 0:
    r = n % 10
    s = s + r
    n = n // 10
print(s)`
    }
];
