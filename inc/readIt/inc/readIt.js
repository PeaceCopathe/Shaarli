// vérifie la présence d'un paramètre dans l'url de la page et renvoie ça valeur
function getQuerystring(key, default_) 
{
    if (default_==null) default_="";
    key = key.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    var regex = new RegExp("[\\?&]"+key+"=([^&#]*)");
    var qs = regex.exec(window.location.href);
    if(qs == null) return default_; else return qs[1];

}



function getPage(e)
{
    killWindowModal();
    adresse='';
    mesparametres='genurl='+e.id+'&json=true';
    maRequete = new Ajax_request(adresse, 
                { 
                    method : 'get',
                    onSuccess : getPageSucess, /* fonction appeler si succé sans les parenthese*/
                    onError : getPageError, /* fonction appeler si erreur sans les parenthese*/
                    params : mesparametres, /*  mes paramètres si il y en as */
                });
}

function killWindowModal()
{
    try 
    {
      document.getElementById('readityourselfcontent').parentNode.removeChild(document.getElementById('readityourselfcontent'));
    } 
    catch(e) 
    {
     return;
    }
    window.location.href='test';
}

// génère la fenetre modal si réception
function getPageSucess(xhr)
{
    var maPage = JSON.parse(xhr.responseText);
    archive=document.createElement('div');
    archive.id="readityourselfcontent";
    btnclose=document.createElement('a');
    btnclose.id='btnclose';
    btnclose.href='#';
    btnclose.innerHTML='X';
    btnclose.onclick=killWindowModal;//function(){document.getElementById('readityourselfcontent').parentNode.removeChild(document.getElementById('readityourselfcontent'));};
    archive.appendChild(btnclose);
    
    urlOrigin=document.createElement('a');
    urlOrigin.href=maPage['url'];
    urlOrigin.innerHTML=maPage['title'];
    archiveTitle=document.createElement('h1');
    archiveTitle.appendChild(urlOrigin);
    archive.appendChild(archiveTitle);

    urlMin=document.createElement('a');
    urlMin.href='?genurl='+maPage['urlmin'];
    urlMin.innerHTML='Lien direct vers cette version.';
    archiveMinUrl=document.createElement('span');
    archiveMinUrl.appendChild(urlMin);
    archive.appendChild(archiveMinUrl);

    
    archiveContent=document.createElement('div');
    archiveContent.innerHTML=maPage['content'];
    archive.appendChild(archiveContent);
    document.body.appendChild(archive);
}

// affiche la réponse du serveur si erreur
function getPageError(xhr)
{
    alert('error'+xhr.responseText);
}
// si on accede à la page par un lien direct on ouvre la modal direct
if(page=getQuerystring('genurl'))
{
    pageId=document.createElement('div');
    pageId.id=page;
    getPage(pageId);
}