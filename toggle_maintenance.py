import ftplib
import sys

# Configuration
FTP_HOST = 'mail.vdraw.cc'
FTP_USER = 'antigravity@vdraw.cc'
FTP_PASS = 'Mann$4545@A4'
REMOTE_PATH = '/home/vdrawcc/domains/vdraw.cc/public_html'

def set_maintenance_mode():
    try:
        print(f"Connecting to {FTP_HOST}...")
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        
        print(f"Changing directory to {REMOTE_PATH}...")
        try:
            ftp.cwd(REMOTE_PATH)
        except Exception:
            print("Absolute path failed, trying relative 'public_html'...")
            ftp.cwd('public_html')
            
        # 1. Rename index.php -> index1.php
        try:
            print("Renaming index.php to index1.php...")
            ftp.rename('index.php', 'index1.php')
            print("Successfully renamed index.php")
        except Exception as e:
            print(f"Error renaming index.php: {e}")
            # It might not exist if already in maintenance mode or other issue
            
        # 2. Rename mt.php -> index.php
        try:
            print("Renaming mt.php to index.php...")
            ftp.rename('mt.php', 'index.php')
            print("Successfully renamed mt.php")
        except Exception as e:
            print(f"Error renaming mt.php: {e}")
            
        ftp.quit()
        
    except Exception as e:
        print(f"FTP Error: {e}")
        sys.exit(1)

if __name__ == "__main__":
    set_maintenance_mode()
