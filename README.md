# emag-archiver
Download copies of magazine issue PDFs from eMagazines.com-based online platforms

This script uses PHP's cURL implementation to download PDF versions of publications that host on the eMagazines platform. It does not grant access that doesn't otherwise exist, and is purely a time-saver to prevent having to open each issue and then download each individual PDF. 

This is for educational/personal/archival use only. **Do not use this for unauthorized distrubtion or other purposes that might violate the law**.

## How It Works
This script uses your unique access link to load the main library page of your publicaton (probably at something like `https://archive.your-magazine.com/library?plid=123`) and processes that page source into an array containing info on every issue stored there. The script then works its way through that array to identify and download each PDF file.

### What you definitely need
You need the to update the following things in `emag_archive.php` for this to work. They're all located in the commented code under "SETTINGS"
  1. the **Unique URL** that grants you access the publication you want to download.
  2. the **Publication String** that represents your publication's name in a more computer-friendly format 
  3. a PHP installation with cURL
  
### What you probably need
  1. The ability to look at page source and URLs on your eMagazines-hosted site and determine how use them in modifying this script
  2. a server/computer with a ~4GB of RAM and a few hundred GB of hard-drive space

## FAQ
### how can I tell if the issues I want to archive are hosted on eMagazines.com?
View the page source and search for "emagazines.com" 

### how can I find my Unique URL?
Probably by logging in at your publication's website, then right-click/save on a button or link that says "View Digital Archive" (or similar)

### how can I find my Publication String
Check the URL and page source on the page you want to download from. Comments in `emag_archive.php` may help as well.  For a publication called "NewsMagazine" probably something like `newsmagazine`.

### how have you tested this?
I have only tested this with one publication at one URL. I have no idea if it works with, or is even readily adaptable others
