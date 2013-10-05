<?php

/**
 * Scraper - a simple script to scrapes an entire site and saves it locally
 * @see https://github.com/jacobemerick/scraper
 *
 * @author jacobemerick (http://home.jacobemerick.com/)
 * @version 1.0 (2013-10-04)
**/


/* parameters that you can set */

// domain that you want to scrape
$domain = 'DOMAIN HERE';

// temp directory created to hold onto scraped files, relative to cwd
$directory = 'temp-archive';

// final archive name that will be saved, relative to the scraper directory
$archive_name = 'archive.zip';

// how long you're willing to let this run in seconds (-1 for unlimited)
$time_limit = -1;


/* should not have to change anything below this line */
set_time_limit($time_limit);

$directory_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR;
if (!is_dir($directory_path))
    mkdir($directory_path);

// basic cleaning up of url for scrape
function get_scrapeable_link($link, $parent_url)
{
    global $domain;
    
    $link = preg_replace('/#.*/', '', $link); // don't care about anchors
    $link = preg_replace('/\?.*/', '', $link); // don't care about get parameters
    
    if (parse_url($link, PHP_URL_HOST) === null) {
        if (substr($link, 0, 1) == '/')
            $link = $domain . substr($link, 1);
        else
            $link = $parent_url . $link;
    }
    
    return $link;
}

// check to make sure this link should be scraped
function should_add_to_scrape_list($link, $link_list)
{
    global $domain;
    
    if (in_array($link, $link_list))
        return false;
    if (preg_match('/^' . preg_quote($domain, '/') . '/i', $link) !== 1)
        return false;
    if (strpos($link, 'mailto:') !== false)
        return false;
    
    return true;
}

// first, we scrape and save locally
$curl_handle = curl_init();
curl_setopt($curl_handle, CURLOPT_HEADER, false);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);

$link_array[] = $domain;
for ($i = 0; $i < count($link_array); $i++) {
    
    // step 1 - grab the file
    curl_setopt($curl_handle, CURLOPT_URL, $link_array[$i]);
    $curl_result = curl_exec($curl_handle);
    $curl_header = curl_getinfo($curl_handle, CURLINFO_CONTENT_TYPE);
    if (strpos($curl_header, ';') !== false)
        $curl_header = substr($curl_header, 0, strpos($curl_header, ';'));
    
    // step 2 - parse the file
    switch ($curl_header) {
        case 'text/css' :
            // for css, we grab everything in url()
            preg_match_all('/url\([\'"]?(.+?)[\'"]?\)/i', $curl_result, $matches);
            
            foreach ($matches[1] as $link) {
                $link = get_scrapeable_link($link, $link_array[$i]);
                if(should_add_to_scrape_list($link, $link_array))
                    $link_array[] = $link;
            }
            break;
        
        case 'text/html' :
            $document = new DOMDocument();
            @($document->loadHTML($curl_result)); // darn you invalid html
            
            // grab all normal 'a' links
            $a_node_list = $document->getElementsByTagName('a');
            foreach ($a_node_list as $a_node) {
                $link = $a_node->attributes->getNamedItem('href')->nodeValue;
                $link = get_scrapeable_link($link, $link_array[$i]);
                if(should_add_to_scrape_list($link, $link_array))
                    $link_array[] = $link;
            }
            
            // grab css file links
            $link_node_list = $document->getElementsByTagName('link');
            foreach ($link_node_list as $link_node) {
                $link = $link_node->attributes->getNamedItem('href')->nodeValue;
                $link = get_scrapeable_link($link, $link_array[$i]);
                if(should_add_to_scrape_list($link, $link_array))
                    $link_array[] = $link;
            }
            
            // grab image links
            $image_node_list = $document->getElementsByTagName('img');
            foreach ($image_node_list as $image_node) {
                $link = $image_node->attributes->getNamedItem('src')->nodeValue;
                $link = get_scrapeable_link($link, $link_array[$i]);
                if(should_add_to_scrape_list($link, $link_array))
                    $link_array[] = $link;
            }
            
            break;
        
        default :
            break;
    }
    
    // step 3 - figure out what to name the file locally and save
    $local_path = $link_array[$i];
    if (substr($link_array[$i], -1) == '/')
        $local_path .= 'index.html';
    $local_path = str_replace($domain, '', $local_path);
    
    $local_path_list = explode('/', $local_path);
    $local_file = array_pop($local_path_list);
    $path = $directory_path;
    
    foreach ($local_path_list as $local_path_piece) {
        $path .= $local_path_piece . DIRECTORY_SEPARATOR;
        if (!is_dir($path))
            mkdir($path);
    }
    
    $file_handle = fopen($path . $local_file, 'w');
    fwrite($file_handle, $curl_result);
    fclose($file_handle);
}

// okay, time to save as a zip file
$archive_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . $archive_name;

$directoryIterator = new RecursiveDirectoryIterator($directory_path);
$iteratorIterator = new RecursiveIteratorIterator($directoryIterator); // giggle again at name

$archive = new ZipArchive();
$archive_open_result = $archive->open($archive_path, ZipArchive::OVERWRITE); // overwrite > create

if ($archive_open_result === true) {
    foreach ($iteratorIterator as $file) {
        $local_path = str_replace($directory_path, '', $file->getPathname());
        
        if ($archive->addFile($file->getPathname(), $local_path) === false)
            echo "Could not add a file: {$local_path}\n";
    }
    
    $archive->close();
} else {
    echo 'Unable to archive the directory. Error: ';
    switch ($archive_open_result) {
        case ZipArchive::ER_EXISTS :
            echo 'File already exists.';
            break;
        case ZipArchive::ER_INCONS :
            echo 'Zip archive inconsistent.';
            break;
        case ZipArchive::ER_INVAL :
            echo 'Invalid argument.';
            break;
        case ZipArchive::ER_MEMORY :
            echo 'Malloc failure.';
            break;
        case ZipArchive::ER_NOENT :
            echo 'No such file.';
            break;
        case ZipArchive::ER_NOZIP :
            echo 'Not a zip archive.';
            break;
        case ZipArchive::ER_OPEN :
            echo "Can't open file.";
            break;
        case ZipArchive::ER_READ :
            echo 'Read error.';
            break;
        case ZipArchive::ER_SEEK :
            echo 'Seek error.';
            break;
        default :
            echo "Undefined error: {$archive_open_result}";
            break;
    }
}

// finally, we remove the temp directory
$iteratorIterator = new RecursiveIteratorIterator(
    $directoryIterator,
    RecursiveIteratorIterator::CHILD_FIRST);

foreach ($iteratorIterator as $file) {
    if(is_dir($file->getPathname()))
        rmdir($file->getPathname());
    else
        unlink($file->getPathname());
}

rmdir($directory_path);