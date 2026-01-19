import ftplib
import os

try:
    print("Connecting to FTP...")
    ftp = ftplib.FTP('mail.vdraw.cc')
    ftp.login('antigravity@vdraw.cc', 'Mann$4545@A4')
    
    # Path to upload to
    remote_dir = '/home/vdrawcc/domains/vdraw.cc/public_html'
    
    print(f"Changing directory to {remote_dir}...")
    try:
        ftp.cwd(remote_dir)
    except Exception as e:
        # Fallback to just public_html if absolute path fails (common in chrooted envs)
        try:
            print("Absolute path invalid, trying 'public_html'...")
            ftp.cwd('public_html')
        except Exception as e2:
             print(f"Directory Error: {e2}")
             ftp.quit()
             exit(1)
             
    print(f"Current PWD: {ftp.pwd()}")
    
    filename = 'rd.txt'
    if os.path.exists(filename):
        print(f"Uploading {filename}...")
        with open(filename, 'rb') as f:
            ftp.storbinary(f'STOR {filename}', f)
        print("Upload successful.")
    else:
        print(f"Local file {filename} not found.")
        
    ftp.quit()
except Exception as e:
    print(f"FTP Error: {e}")
