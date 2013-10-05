#Scraper
Basic PHP utility to scrape a site and save it to an archive
----------------------------------------------------------
Basic script that takes a domain, crawls it for all linked pages, stylesheets, and images, and saves it all as a ZIP archive. Helpful for front-end backup needs.


Requirements
------------------
 - PHP (version 5 or better)
 - SPL (usually bundled in PHP5)
 - ZipArchive (another normal extension in PHP5)
 - cURL (usually bundled in PHP since 4)
 - DomDocument (usually bundled in PHP since 4)


Usage
------------------
Plop the archiver.php in some directory and run with PHP. Modify the four parameters as needed...
 - domain = the domain you want to scrape
 - directory = a temp directory to hold on to the scraped files pre-archive (to save on memory)
 - archive_name = the name of the archive that will hold the files
 - time_limit = how long you're willing to let the script run (-1 for unlimited)


Possible Future Enhancements
------------------
 - javascript capturing and parsing for more links
 - svg parsing
 - wider support (there are a lot ways to save a website)
 - checks for common unlinked files (robots.txt, sitemap.xml, etc)
 - comparisons between crawled links and sitemap.xml to get more links


Changelog
------------------
v1.0 (2013-10-05)
 - initial release


------------------
 - Project at GitHub [jacobemerck/scraper](https://github.com/jacobemerick/scraper)
 - Jacob Emerick [@jpemeric](http://twitter.com/jpemeric) [jacobemerick.com](http://home.jacobemerick.com/)