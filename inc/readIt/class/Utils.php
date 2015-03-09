<?php

class Utils {

    /**
     * Create a folder for ressource
     */
    public static function create_assets_directory($url,$create=false) 
    {
        $article_directory = SAVED_PATH . '/';
        
        if (Utils::isValidMd5($url)) 
        {
            $article_directory .= $url;
        } 
        else 
        {
            $article_directory .= md5($url);
        }
        if($create){
        if (!is_dir($article_directory)) 
        {
            mkdir($article_directory, 0705);
        }
        }
        return $article_directory;
    }

    // validate if string is a MD5
    public static function isValidMd5($md5 = '') {
        return preg_match('/^[a-f0-9]{32}$/', $md5);
    }

    /**
     * Convert image to base64 string
     */
    public static function pictures_base64($absolute_path, $fullpath) {
        $rawdata = Utils::get_external_file($absolute_path, 15);
        $type = pathinfo($fullpath, PATHINFO_EXTENSION);

        return 'data:image/' . $type . ';base64,' . base64_encode($rawdata);
    }

    /**
     * Download of ressources
     */
    public static function download_resources($absolute_path, $fullpath) {
        $rawdata = Utils::get_external_file($absolute_path, 15);

        if (file_exists($fullpath)) {
            unlink($fullpath);
        }
        $fp = fopen($fullpath, 'x');
        fwrite($fp, $rawdata);
        fclose($fp);
    }

    public static function absolutes_links($data, $base) {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->encoding = 'UTF-8';

        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $data);
        libxml_use_internal_errors(false);

        // cherche les balises qui contiennent un href ou un src
        $doc = Utils::absolute_for_DOM_and_query($doc, '//*/@src | //*/@href', $base);

        return $doc->saveHTML();
    }


    public static function absolute_for_DOM_and_query($doc, $query, $base) {
        $xpath = new DOMXPath($doc);
        $entries = $xpath->query($query);

        if ($entries != null && count($entries) > 0) {
            foreach ($entries as $entry) {
                $entry->nodeValue = Utils::absolute_for_entry($entry->nodeValue, $base);
            }
        }

        return $doc;
    }

    public static function absolute_for_entry($nodevalue, $base) {
        $nodevalue = htmlentities(html_entity_decode($nodevalue));
        if (!preg_match('%^((http[s]?://)|(www\.)|(#))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%', $nodevalue)) {
            $nodevalue = Utils::rel2abs($nodevalue, $base);
        }
        return $nodevalue;
    }

    // function define to retrieve url content
    public static function get_external_file($url, $timeout) {
        // spoofing FireFox 18.0
        $useragent = "Mozilla/5.0 (Windows NT 5.1; rv:18.0) Gecko/20100101 Firefox/18.0";

        if (in_array('curl', get_loaded_extensions())) {
            // Fetch feed from URL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
         //    		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);

            // FeedBurner requires a proper USER-AGENT...
            curl_setopt($curl, CURL_HTTP_VERSION_1_1, true);
            curl_setopt($curl, CURLOPT_ENCODING, "gzip, deflate");
            curl_setopt($curl, CURLOPT_USERAGENT, $useragent);

            $data = curl_exec($curl);

            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            $httpcodeOK = isset($httpcode) and ( $httpcode == 200 or $httpcode == 301);

            curl_close($curl);
        } else {

            // create http context and add timeout and user-agent
            $context = stream_context_create(
                    array('http' =>
                        array('timeout' => $timeout, // Timeout : time until we stop waiting for the response.
                            'header' => "User-Agent: " . $useragent, // spoot Mozilla Firefox
                            'follow_location' => true
                        )
                    )
            );

            // only download page lesser than 4MB
            $data = @file_get_contents($url, false, $context, -1, 4000000); // We download at most 4 MB from source.
            //	echo "<pre>http_response_header : ".print_r($http_response_header);

            if (isset($http_response_header) and isset($http_response_header[0])) {
                $httpcodeOK = isset($http_response_header) and isset($http_response_header[0])
                        and ( (strpos($http_response_header[0], '200 OK') !== FALSE)
                        or ( strpos($http_response_header[0], '301 Moved Permanently') !== FALSE));
            }
        }

        // if response is not empty and response is OK
        if (isset($data) and isset($httpcodeOK) and $httpcodeOK) {

            // take charset of page and get it
            preg_match('#<meta .*charset=.*>#Usi', $data, $meta);

            // if meta tag is found
            if (!empty($meta[0])) {
                // retrieve encoding in $enc
                preg_match('#charset="?(.*)"#si', $meta[0], $enc);

                // if charset is found set it otherwise, set it to utf-8
                $html_charset = (!empty($enc[1])) ? strtolower($enc[1]) : 'utf-8';
            } else {
                $html_charset = 'utf-8';
                $enc[1] = '';
            }

            // replace charset of url to charset of page
            $data = str_replace('charset=' . $enc[1], 'charset=' . $html_charset, $data);

            return $data;
        } else {
            return FALSE;
        }
         }

    public static function rel2abs($rel, $base) {
        /* return if already absolute URL */
        if (parse_url($rel, PHP_URL_SCHEME) != '')
            return $rel;

        /* queries and anchors */
        if ($rel[0] == '#' || $rel[0] == '?')
            return $base . $rel;

        /* parse base URL and convert to local variables:
          $scheme, $host, $path */
        extract(parse_url($base));

        /* remove non-directory element from path */
        $path = preg_replace('#/[^/]*$#', '', $path);

        /* destroy path if relative url points to root */
        if ($rel[0] == '/')
            $path = '';

        /* dirty absolute URL */
        $abs = "$host$path/$rel";

        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
            
        }

        /* absolute URL is ready! */
        return $scheme . '://' . $abs;
    }

    // supprimer un répertoire et son contenu en php
    public static function delDir($dir)  
      { 
       $current_dir = opendir($dir); 
       
       while($entryname = readdir($current_dir))  
       { 
       
        if(is_dir("$dir/$entryname") and ($entryname != "." and $entryname!=".."))  
        { 
        Utils::delDir("${dir}/${entryname}"); 
        }  
        elseif($entryname != "." and $entryname!="..") 
        { 
        unlink("${dir}/${entryname}"); 
        } 
       
       } //Fin tant que 
       
       closedir($current_dir); 
       rmdir(${dir}); 
      } 

}
