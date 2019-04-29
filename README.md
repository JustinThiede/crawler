# Crawler
A crawler to get all links of a page. It only crawls the targets domain. 
If you crawl example.com which has a link to whatever.com, whatever.com will not be crawled.

## Instantiation
``` php
$crawl = new Crawler();
```

## Set target to crawl
``` php
$crawl->crawl('https://example.com');
```

## Get crawled links
Gets all the crawled links of that domain as a one dimensional array.
``` php
$crawl->getCrawledLinks();
```

## Defaults
### Scheme allowed
* http
* https

### Extensions allowed
* html
* htm

## Options
You can set and get allowed schemes and file extensions.

### Setting allowed file extensions
``` php
$crawl->allowed('set', 'allowedFiles', '.pdf', '.png');
```

### Removing allowed file extensions
``` php
$crawl->allowed('remove', 'allowedFiles', '.pdf', '.png');
```

### Removing allowed schemes
``` php
$crawl->allowed('remove', 'allowedSchemes', 'http');
```

### Getting allowed file extensions
``` php
$crawl->allowed('get', 'allowedFiles');
```

### Getting allowed schemes
``` php
$crawl->allowed('get', 'allowedSchemes');
```
