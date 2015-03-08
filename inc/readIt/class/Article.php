<?php

class Article {

    private $url = false;
    private $original;
    private $date;
    private $title;
    private $readIt;
    private $mimeType;
    private $finalContent;
    private $loaded = false;

    public function getUrl() {
        return base64_decode($this->url);
    }

    public function setUrl($url) {
        $this->url = base64_encode($url);
    }

    public function getOriginal() {
        return base64_decode($this->original);
    }

    public function setOriginal($original) {
        $this->original = base64_encode($original);
    }

    public function getDate() {
        if (!isset($this->date)) {
            if ($this->isAlreadyExists()) {
                $this->setDate(date("Y-m-d", filemtime(Utils::create_assets_directory($this->getUrl()) . '/article.ser')));
            } else {
                $this->setDate(date("Y-m-d"));
            }
        }
        return $this->date;
    }

    public function setDate($date) {

        $this->date = $date;
    }

    public function getFinalContent() {
        return base64_decode($this->finalContent);
    }

    public function setFinalContent($finalContent) {
        $this->finalContent = base64_encode($finalContent);
    }

    public function getTitle() {
        return base64_decode($this->title);
    }

    public function setTitle($title) {
        $this->title = base64_encode($title);
    }

    public function isLoaded() {
        return $this->loaded;
    }

    public function retrieveContent() {
        // convert page to utf-8
        $this->setOriginal(Encoding::toUTF8(Utils::get_external_file($this->getUrl(), 15)));
        return ($this->getOriginal() != null and strlen($this->getOriginal()) > 0);
    }

    public function saveContent() {
        $directory = Utils::create_assets_directory($this->getUrl(),true);
        if (is_dir($directory)) {
            $article = serialize($this);
            // save article in directory
            file_put_contents($directory . '/article.ser', $article);
            file_put_contents($directory . '/original.html', $this->getOriginal());
            file_put_contents($directory . '/final.html', $this->getFinalContent());
        } else {
            return false;
        }
    }

    public function isAlreadyExists() {
        $article_file = Utils::create_assets_directory($this->getUrl()) . '/article.ser';
        return file_exists($article_file);
    }

    public static function getArticle($url) {
        $article_file = Utils::create_assets_directory($url) . '/article.ser';
        if (file_exists($article_file)) {
            // read article from file
            return unserialize(file_get_contents($article_file));
        } else {
            return false;
        }
    }

    public static function getArticleFromFile($article_file) {
        if (file_exists($article_file)) {
            // read article from file
            return unserialize(file_get_contents($article_file));
        } else {
            return false;
        }
    }

    public function readiIt($debug=false) {
        if ($this->getOriginal() != null and strlen($this->getOriginal()) > 0) {

            // send result to readability library
            $readIt = new Readityourself($this->getOriginal(), $this->getUrl());
            if($debug) {
                $readIt->setDebugMode(true);
            }
            $this->loaded = $readIt->init();
            $this->setTitle($readIt->articleTitle->innerHTML);
            $this->setFinalContent($readIt->articleContent->innerHTML);
            $this->setDate(date("Y-m-d"));
        }
        return $this->loaded;
    }

    public function modifyContent() {
        global $PICTURES_DOWNLOAD, $PICTURES_BASE64;
        $this->setFinalContent(utils::absolutes_links($this->getFinalContent(), $this->getUrl()));
        if ($PICTURES_DOWNLOAD == true || $PICTURES_BASE64 == true) {
            $this->setFinalContent($this->picture_filtre($this->getFinalContent(), $this->getUrl()));
        }
    }

    /**
     * On modifie les URLS des images dans le corps de l'article
     */
    private function picture_filtre($content, $url) {
        global $PICTURES_DOWNLOAD, $PICTURES_BASE64;

        $matches = array();
        preg_match_all('#<\s*(img)[^>]+src=["|\']([^"\']*)["|\'][^>]*>#Si', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $i => $link) {
            $link[1] = trim($link[1]);
            if (!preg_match('#^(([a-z]+://)|(\#))#', $link[1])) {
                $absolute_path = Utils::rel2abs($link[2], $url);
                $filename = basename(parse_url($absolute_path, PHP_URL_PATH));
                $directory = Utils::create_assets_directory($url) . '/images/';
                if (!is_dir($directory)) {
                    mkdir($directory, 0705);
                }

                $fullpath = $directory . '/' . $filename;
                if ($PICTURES_DOWNLOAD) {
                    Utils::download_resources($absolute_path, $fullpath);
                    $content = str_replace($matches[$i][2], $fullpath, $content);
                } else if ($PICTURES_BASE64) {
                    $srcb64 = Utils::pictures_base64($absolute_path, $fullpath);
                    $content = str_replace($matches[$i][2], $srcb64, $content);
                }
            }
        }

        return $content;
    }

//    public static function findPublicArticle() {
//        return Article::findArticle("public");
//    }

    // public static function findArticle($pattern) {
    //     $items = glob("./" . SAVED_PATH . $pattern . "/*/article.ser");
    //     $articles = array();
    //     foreach ($items as $item) {
    //         $article = Article::getArticleFromFile($item);
    //         array_push($articles, $article);
    //     }
    //     return $articles;
    // }

}
