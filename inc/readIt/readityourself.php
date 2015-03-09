<?php
 libxml_use_internal_errors(true);

// Ajout des includes
require_once dirname(__FILE__).'/inc/config.php';
require_once dirname(__FILE__).'/inc/Readability.php';
require_once dirname(__FILE__).'/inc/Encoding.php';
require_once dirname(__FILE__).'/class/Article.php';
require_once dirname(__FILE__).'/class/Utils.php';
require_once dirname(__FILE__).'/class/Readityourself.php';


// fonction qui sauvegarde la page
function savePage($url)
{
    $article = new Article;
    $article->setUrl($url);

    if(!$article->isAlreadyExists()) 
    {
        if($article->retrieveContent())
        {
            if($article->readiIt(isset($debug)))
            {
                $article->modifyContent();
                $article->saveContent();
                return md5($article->getUrl());
            }
        }
    } 
    else 
    {
        $article = Article::getArticle($url);
        // only for debug
        if (isset($debug) && $debug==True)
        {
           if($article->readiIt(isset($debug)))
            {
                $article->modifyContent();
                $article->saveContent();
            } 
        }
        return  md5($article->getUrl()); 
    }  
    return false;
}

// renvoie la page en json
function getPage($url)
{
    $article = Article::getArticle($url);
    if($article && $article->isLoaded()) 
    {
        $return=array('url'=>$article->getUrl(),'urlmin'=>md5($article->getUrl()),'title'=>$article->getTitle(),'content'=>$article->getFinalContent());
        $texte= json_encode($return);
        return $texte;
    } 
    else
    {
        return false;
    }  
}

// Vérifie l'url et vire le https

function verifUrl($url)
{
    if(isset($url) && $url != null && trim($url) != "") 
    {
        // get url link
        if(strlen(trim($url)) > 2048) 
        {
            return false;
        } 
        else 
        {
            $url = trim($url);

            if(!Utils::isValidMd5($url)) 
            {
                // decode it
                $url = html_entity_decode($url);
                // if url use https protocol change it to http
                if (!preg_match('!^https?://!i', $url)) 
                {
                    $url = 'http://'.$url;
                }
                return $url;
            }
            else
            {
                return $url;
            }
        }
    }
    return false;
}

// supprimer l'archive
function delPage($url)
{   
    $article = new Article;
    $article->setUrl($url);
    if(!$article->isAlreadyExists()) 
    {
        echo 'del pas ok';
        return False;
    }
    else
    {
        
        $dir=Utils::create_assets_directory($article->getUrl());
        file_put_contents('log.txt', $dir);
        Utils::delDir($dir);
        echo 'del ok';
        return True;
    }
}

//On vérifie qu'on appel bien le script avec la page principal de shaarli
if (!function_exists ( 'isLoggedIn' ))
{
    echo 'Error, but why ???!!';
    exit();
}

// on verifie si on est logguer ou si shaarli libre
if (isLoggedIn())
{
    // fonction pour sauvegader la page
    if(isset($_GET['saveurl']) && $_GET['saveurl'] != null && trim($_GET['saveurl']) != "") 
    {
        if ($url=verifUrl($_GET['saveurl']))
        {       
            if(!savePage($url))
            {
                echo "Error unable to get link : ".$url;
                exit();
            }
        }    
    }
    // fonction pour supprimer la page
    if(isset($_GET['delurl']) && $_GET['delurl'] != null && trim($_GET['delurl']) != "") 
    {
        if ($url=verifUrl($_GET['delurl']))
        {       
            if(!delPage($url))
            {
                echo "Error unable to del link : ".$url;
                exit();
            }
            else
            {
                return True;
            }
        }    
    }
}

//fonction pour récup la page, en json, ou normal
if(isset($_GET['genurl']) && $_GET['genurl'] != null && trim($_GET['genurl']) != "") 
{
    if ($url=verifUrl($_GET['genurl']))
    {
        if (!$texte=getPage($url))
        {
            echo "Error unable to get page : ".$url;
            exit();   
        }
        else
        {
            if(isset($_GET['json']) && $_GET['json'] == true)
            {
              echo $texte;  
              exit();  
            }
            
        }

    }    
}

