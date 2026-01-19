import ftplib
import os

FTP_HOST = 'mail.vdraw.cc'
FTP_USER = 'antigravity@vdraw.cc'
FTP_PASS = 'Mann$4545@A4'

def upload_maintenance():
    try:
        ftp = ftplib.FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        try:
             ftp.cwd('public_html')
        except:
             pass 

        # Upload mt.php -> index.php
        if os.path.exists('mt.php'):
            print("Uploading mt.php as index.php...")
            with open('mt.php', 'rb') as f:
                ftp.storbinary('STOR index.php', f)
            
            print("Uploading mt.php as mt.php...")
            with open('mt.php', 'rb') as f:
                ftp.storbinary('STOR mt.php', f)
                
            print("Maintenance mode enabled successfully.")
        else:
            print("Local mt.php missing.")

        ftp.quit()
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    upload_maintenance()
