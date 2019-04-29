<?php

/**
 * Domain Crawler
 *
 * A class that crawls links of a page you enter.
 * It only crawls links of the same domain.
 *
 * PHP version 7
 *
 * @category   crawler
 * @package    htagetter
 * @author     Original Author <justin.inw@hotmail.com>
 * @author     Another Author <>
 * @copyright  Justin Thiede
 * @license    http://creativecommons.org/licenses/by-nc-sa/3.0/
 */

class Crawler
{
    private $allowedSchemes = ['http', 'https'];
    private $allowedFiles = ['.html', '.htm'];
    private $crawledLinks = [];
    private $domain = '';
    private $domainScheme = '';
    private static $crawlerCalls = 0;
    
    public function __construct(){}
    
    public function crawl($url)
    {
        self::$crawlerCalls++;
          
        // First call sets the domain to the host of the given url
        if (self::$crawlerCalls == 1) {
            $domainParts = parse_url($url);
            $this->domain = str_replace('www.', '', $domainParts['host']);
            $this->domainScheme = $domainParts['scheme'];
        }
        
        $dom = new DOMDocument('1.0');
        @$dom->loadHTMLFile($url);      
        
        $base = $dom->getElementsByTagName('base');
        $anchors = $dom->getElementsByTagName('a');

        // Checks if there is a basepath
        foreach ($base as $element) {
            $basePath = $element->getAttribute('href');
            break;
        }
        
        foreach ($anchors as $element) {
            $href = $element->getAttribute('href');
            $schemeAllowed = true;
            
            $urlParts = parse_url($url);
            $hrefParts = parse_url($href);
            
            $urlHost = $urlParts['host'];
            $urlPath = $urlParts['path'];
            $hrefScheme = $hrefParts['scheme'];
            $hrefHost = $hrefParts['host'];
            $hrefPath = $hrefParts['path'];
            
            $urlPathParts = explode('/', $urlPath);
            $urlPathParts = array_filter($urlPathParts);
            $urlPathParts = array_values($urlPathParts);
            
            $hrefPathParts = explode('/', $hrefPath);
            $hrefPathParts = array_filter($hrefPathParts);
            $hrefPathParts = array_values($hrefPathParts);
            
            // Href checks
            $domainAllowed = $this->domainAllowed($href);
            $linkAllowed = $this->linkAllowed($href);
            $fileAllowed = $this->fileAllowed($href);
            $schemeAllowed =  $this->schemeAllowed($hrefScheme);

            // Returns of checks all have to be true
            if ($domainAllowed && $linkAllowed && $schemeAllowed && $fileAllowed && !$this->linkRootOccured) {
                
                /*
                 *  Sometime link has / at end sometimes not. 
                 *  Causes double entry in output array.
                 */
                if (!$this->hasFileEnding($href)) {
                    $href = rtrim($href, '/');
                    $hrefPath = rtrim($hrefPath, '/');
                }
                
                /*
                 * // = use same protocoll means that href already has full link
                 * ../ = back one directory
                 * Has scheme = href already full link
                 * No slash = href link to file in same folder
                 */
                if ($href{0} == '/' && $href{1} == '/') {
                    $urlOutput = $this->domainScheme . ':' . $href;
                    $pathOutput = '/' . ltrim($href, '/');;
                } elseif (substr($href, 0, 3) == '../') {
                    // Count levels to go up
                    $levelsUp = substr_count($href, '../');
                    array_splice($urlPathParts, count($urlPathParts)-$levelsUp-1);
                    $hrefPath = $href == '../' ? '' : str_replace('../', '', $hrefPath);
                    $endPath = implode('/', $urlPathParts) . '/' . ltrim($hrefPath, '/');
                    $pathOutput = '/' . ltrim($endPath, '/');
                    $urlOutput = $this->domainScheme . '://' . $urlHost . '/' . ltrim($endPath, '/');
                } elseif ($this->hasScheme($hrefScheme)) {
                    $pathOutput = '/' . ltrim($hrefPath, '/');
                    $urlOutput = $this->domainScheme . '://' . $hrefHost . $hrefPath;
                } elseif (!isset($basePath) && $href{0} != '/') {
                    // Kill last url part if it has file at the end
                    $this->hasFileEnding($urlPathParts[count($urlPathParts)-1], true, $urlPathParts);
                    $endPath = implode('/', $urlPathParts) . '/' . ltrim($href, '/');
                    $pathOutput = '/' . ltrim($endPath, '/');
                    $urlOutput = $this->domainScheme . '://' . $urlHost . '/' . ltrim($endPath, '/');
                } elseif (isset($basePath)) {
                    $pathOutput = '/' . ltrim($href, '/');
                    $urlOutput = $basePath . ltrim($href, '/');  
                } else {
                    $pathOutput = '/' . ltrim($href, '/');
                    $urlOutput = $this->domainScheme . '://' . $urlHost . '/' . ltrim($href, '/');
                }
                $this->setCrawledLinks($urlOutput, $pathOutput);
            }
        }
    }
    
    // Writes crawled links in array
    private function setCrawledLinks($urlOutput, $pathOutput)
    {
        if (!in_array($pathOutput, $this->crawledLinks) && $pathOutput != '/') {
            array_push($this->crawledLinks, $pathOutput);
            $this->crawl($urlOutput);
        }
    }
    
    // Return crawled links
    public function getCrawledLinks() 
    {
        return $this->crawledLinks;
    }
    
    // Checks if the link is to this domain
    private function domainAllowed($href)
    {
        $hasWww = strpos($href, 'www') !== false ? true : false;
        $hasHttp = strpos($href, 'http') !== false
                   || ($href{0} == '/' && $href{1} == '/') ? true : false;
        $hrefCut = str_replace($this->domainScheme. '://', '', $href);
        
        // Some links have 2 domains
        $links = explode('/', $hrefCut);
        // Make shure link is same as $this->domain without www.
        $links[0] = str_replace('www.', '', $links[0]);
        
        /*
         * Doesnt matter how many domains in link first field always has to be
         * Crawled domain not link to other page.
         */
        $otherDomain = $links[0] !== $this->domain ? true : false;
        
        // Filter out links to other domains
        if (($hasWww || $hasHttp) && $otherDomain) {
            return false;
        } else {
            return true;
        }
    }
    
    // Checks link for anchors and link to self
    private function linkAllowed($href)
    {
        $anchor = strpos($href, '#') !== false ? true : false;
        $selfRef = $href === './' ? true : false; 
        $normalLink = preg_match('/^[a-zA-z]|^\/|^\./', $href) ? true : false;
        
        if (!$anchor && !$rootRef && !$selfRef && $normalLink) {
            return true;
        } else {
            return false;
        }
    }
    
    // Checks if scheme is allowed or not mentioned in href
    private function schemeAllowed($scheme)
    {
        $hasScheme = $this->hasScheme($scheme);
        $schemeAllow = in_array($scheme, $this->allowedSchemes) ? true : false;
        
        if (($hasScheme && $schemeAllow) || !$hasScheme) {
            return true;
        }
        
        return false;
    }
    
    // Checks if file has allowed ending
    private function fileAllowed($href)
    {
        $allowFile = true;

        $ending = $this->hasFileEnding($href);
        if ($ending !== false) {
            $allowFile = in_array($ending, $this->allowedFiles) ? true : false;
        }
       
        return $allowFile;
    }
    
    /* 
     * Checks if link ends with a file
     * If param killEnding is true then it also kills the ending
     * Else it only gives back pos of dot
     */
    private function hasFileEnding($href, $killEnding = false, &$urlPathParts) 
    {
        $ending = strrev($href);
        $lastSlash = strpos($ending, '/');
        
        if ($lastSlash !== false) {
            $ending = strrev(substr($ending, 0, $lastSlash));
        } else {
            $ending = strrev($ending);
        }

        $posDot = strpos($ending, '.');
       
        if (strpos($ending, '.') !== false) {
            $ending = substr($ending, $posDot);
            
            if ($killEnding) {
                unset($urlPathParts[count($urlPathParts)-1]);
            } else {
                return $ending;
            }
        } else {
            return false;
        }
    }
    
    // Checks if link has scheme
    private function hasScheme($scheme) 
    {
        $hasScheme = empty($scheme) ? false : true;
        
        return $hasScheme;
    }
    
    /*
     * Get, set or remove rules of the file extension and scheme arrays
     * As many rules as wanted can be passed
     */
    public function allowed($type, $arrayName, ...$rules)
    {
        $addDot = $arrayName == 'allowedFiles' ? true : false;

        switch (strtolower($type)) {
            case 'get':
                return $this->getAllowed($arrayName);
                break;
            case 'set':
                $this->setAllowed($arrayName, $rules, $addDot);
                break;
            case 'remove':
                $this->removeAllowed($arrayName, $rules, $addDot);
                break;
        } 
    }
    
    // Get arrays with rules
    private function getAllowed($arrayName) {
        return $this->{$arrayName};
    }
    
    // Sets arrays with rules
    private function setAllowed($arrayName, $rules, $addDot)
    {
        foreach ($rules as $rule) {
            $rule = $addDot ? '.' . ltrim($rule, '.') : $rule;
            array_push($this->{$arrayName}, $rule);
        }
    }
    
    // Removes rules of arrays with rules
    private function removeAllowed($arrayName, $rules, $addDot)
    {
        foreach ($rules as $rule) {
            $rule = $addDot ? '.'.ltrim($rule, '.') : $rule;
            $key = array_search($rule, $this->{$arrayName});
            unset($this->{$arrayName}[$key]);
            $this->{$arrayName} = array_values($this->{$arrayName});
        }
    }
}
