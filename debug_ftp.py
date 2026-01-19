import ftplib

try:
    ftp = ftplib.FTP('mail.vdraw.cc')
    ftp.login('antigravity@vdraw.cc', 'Mann$4545@A4')
    # Try relative path
    ftp.cwd('public_html')
    
    files = []
    ftp.dir(files.append)
    print("Files in public_html:")
    for f in files:
        if 'index' in f or 'mt.php' in f:
            print(f)
            
    ftp.quit()
except Exception as e:
    print(f"Error: {e}")
