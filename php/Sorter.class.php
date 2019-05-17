<?php

/**
 * Sorts arrays
 *
 * Categorizes and sorts an array of links
 * Creates redirects
 *
 * PHP version 7
 *
 * @category   sorter
 * @package    htagetter
 * @author     Original Author <justin.thiede@visions.ch>
 * @author     Another Author <>
 * @copyright  visions.ch GmbH
 * @license    http://creativecommons.org/licenses/by-nc-sa/3.0/
 */

class Sorter
{
    private $crawledLinks = [],
    $inputLinks = [],
    $catLinks = [],
    $host = '';
       
    public function __construct(){}
    
    // Set crawled links
    public function setCrawledLinks($crawledLinks)
    {
        foreach ($crawledLinks as $key => $link) {
            $crawledLinks[$key] = ltrim($link, '/');
        }
        
        $this->crawledLinks = $crawledLinks;
    }
    
    // Set user input links
    public function setInputLinks($inputLinks)
    {
        foreach ($inputLinks as $key => $link) {
            $inputLinks[$key] = ltrim($link, '/');
        }
        
        $this->inputLinks = $inputLinks;    
    }
    
    // Set host
    public function setHost($host)
    {
        $this->host = $host;    
    }
    
    // Sort links
    public function sortLinks() 
    {           
        // Categorize links
        $this->categorizeLinks();
        
        // Allocate links
        $slashArray = $this->allocateLinks();
        
        $minSlash = min($slashArray);
        $maxSlash = max($slashArray);
        
        // Sort links most sub directorys first
        foreach ($this->catLinks as $key => $value) {
            for ($i = $maxSlash; $i >= $minSlash; $i--) {
                foreach ($value as $link) {
                    if (isset($link[$i]) && !is_null($link[$i])) {
                        $sortedLinks[$key][] = $link[$i];
                    }
                }
            }
        }
        
        return $sortedLinks;
    }
    
    // Categorize links
    private function categorizeLinks()
    {
        foreach ($this->crawledLinks as $key => $link) {            
            $cat = substr($link, 0, strpos($link, '/'));
            
            if (!isset($this->catLinks[strtoupper($cat)]) && strpos($link, '/') !== false) {
                $this->catLinks[strtoupper($cat)] = [];
            } else if (strpos($link, '/') === false) {
                $this->catLinks[strtoupper('ROOT')] = [];
            }
        }
    }
    
    // Allocate links
    private function allocateLinks()
    {
        $sortableLinks = empty($this->inputLinks) ? $this->crawledLinks : $this->inputLinks;

        // Allocate links to categories and count slashes
        foreach ($sortableLinks as $key => $link) {
            $slashes = substr_count($link, '/');
            $slashArray[] = $slashes;
            
            // Create redirect
            if (!empty($this->inputLinks)) {        
                $link = 'RewriteRule ^' . $this->crawledLinks[$key] . '$ ' . $link . '? [L,NC,R=301]<br>';
                $linkCat = $this->crawledLinks[$key];
            } else {
                $linkCat = $link;
            }
            
            // Create category
            if (strpos($linkCat, '/') !== false) {
                // Get category of link
                $linkCat = explode('/', $linkCat);
                $linkCat = $linkCat[0];  
            } else {
                $linkCat = 'ROOT';
            }
            
            // Allocation slashes are key and link is value used for sorting
            foreach ($this->catLinks as $key => $value) {
                if (strtoupper($key) == strtoupper($linkCat)) {
                    $this->catLinks[$key][] = [$slashes => $link]; 
                }
            }
        }
        
        return $slashArray;
    }
}


