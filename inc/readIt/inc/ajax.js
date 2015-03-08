   //
    // Librairie by The Rubik's Man
    // © 2005-2006
    //
    ///////////////////////////////////////////////////////////////////// Debut de la pseudo classe //////////////////////////////////////////////////////////////////////////////////
    /**
    * Classe principal de la librairie d'aide aux requetes AJAX
    *
    * Contient les methodes utilisees pour la communication
    * Syntaxe : pour lancer une requete
    * => new Ajax_request('url', { method : '....', onSuccess : ...., onError : ...., params : '....', async : ....});
    * url : url du fichier genrant la reponse
    * method : ' post ' ou ' get ' || default : post
    * onSuccess : nom de la fonction executee lors de la reussite de la requete ( nom de la fonction sans les parentheses )
    * onError : nom de la fonction executee lors d'une erreur ( nom de la fonction sans les parentheses )
    * params : 'param1=XXX&param2=XXX' : parametres passes en post ou get au fichier designe par l'url
    * async : true ou false : mode asynchrone ou synchrone || default : true
    *
    * Les variables ' responseText ' et ' responseXML ' permettent ensuite dans votre page HTML de recuperer la reponse AJAX dans le format approprie lorsqu'un seule requete est effectuee en meme temps
    * Si plusiseurs requetes doivent etre lancees en meme temps alors " var MonOnbjetAjax = new Ajax_request( .....).. " et ensuite "MonObjetAjax.responseText" ou "MonObjetAjax.responseXML" pour chaque objet
    */
    var Ajax_request = function() {
    this.request.apply(this, arguments);
    }
    // Definition des methodes de l'objet ( classe )
    //{
    Ajax_request.prototype = {
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////// FONCTION POUR L'UTLISATEUR /////////////////////////////////////////////////////////////////////
    /**
    * Fonction principale permettant d'envoyer des requetes dynamiques par le module AJAX
    * Cette fonction gere toute seule toutes les etapes d'envoi ( initialisation , affectation des fonctions erreur et success , envoi )
    *
    * ### C'est seulement cette fonction qui sera utilisee par l'utilisateur ###
    *
    * @ param url : (String): parametre obligatoire, url du fichier qui generera la reponse dynamiquement
    * @ param options : (Array) : parametres optionnels [ method (get ou post), onSuccess (fonction), onError (fonction), params (parametres envoyes), async (true ou false) }
    */
    request: function (url, options) {
    this.getObject();
    if ( typeof(options) == 'undefined' ) options = new Array();
    if ( typeof(options) == 'object' ) {
    method = options['method'] || 'post';
    successEnd = options['onSuccess'] || this.debugSuccessMessage.bind(this);
    errorEnd = options['onError'] || this.defaultErrorMessage.bind(this);
    paramString = typeof(options['params']) == 'undefined' ? '' : (options['method'] == 'get' ? '?'+options['params'] : options['params']);
    if ( typeof(options['async']) != 'undefined' && typeof(options['async']) != 'boolean' ) {
    alert('si la valeur de \'async\' est definie, elle doit etre de type booleen');
    return;
    }
    else async = typeof(options['async']) == 'undefined' || typeof(options['async']) != 'boolean'? true : options['async'];
    this.setProperties(method,successEnd,errorEnd,paramString,async);
    }
    if ( typeof(url) != 'string' ) {
    alert('url de format invalide ( l\'url est obligatoire )');
    return;
    }
    else this.properties['url'] = url;
    this.setReadyProcess();
    this.processRequest();
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ,
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////// FONCTIONS INTERNES /////////////////////////////////////////////////////////////////////
    /**
    * Fonction d'initialisation de l'objet principal AJAX
    * => Cree l'objet AJAX
    * => Prepare le tableau des proprietes
    */
    getObject: function() {
    // FF / OPERA / NETSCAPE
    if(window.XMLHttpRequest) this.Ajax_object = new XMLHttpRequest();
    // Internet Explorer
    else if (window.ActiveXObject) {
    try {
    this.Ajax_object = new ActiveXObject("Msxml2.XMLHTTP");
    }
    catch (e) {
    try {
    this.Ajax_object = new ActiveXObject("Microsoft.XMLHTTP");
    }
    catch (e1) {
    this.Ajax_object = null;
    }
    }
    }
    else {
    alert("AJAX impossible sur votre navigateur");
    }
    this.properties = new Array();
    },
    /**
    * Fonction permettant d'enregistrer les proprietes passees en parametres et traitees dans le tableau principal des proprietes
    *
    * @ param method (String)
    * @ param successEnd (function)
    * @ param errorEnd (function)
    * @ param paramString (String)
    * @ param async (boolean)
    */
    setProperties: function() {
    this.properties = {
    method : arguments[0],
    successEnd : arguments[1],
    errorEnd : arguments[2],
    paramString : arguments[3],
    async : arguments[4]
    }
    },
    /**
    * Fonction permettant d'enregistrer les fonctions executees lors du succes ou d'une erreur
    */
    setReadyProcess: function() {
    this.Ajax_object.onreadystatechange = function() {
    if (this.Ajax_object.readyState == 4 ) {
    responseText = this.Ajax_object.responseText;
    responseXML = this.Ajax_object.responseXML;
    this.responseText = this.Ajax_object.responseText;
    this.responseXML = this.Ajax_object.responseXML;
    if ( this.Ajax_object.status == 200 ) this.properties['successEnd'](this.Ajax_object);
    else this.properties['errorEnd'](this.Ajax_object);
    }
    }.bind(this);
    },
    /**
    * Fonction d'affichage des erreurs par defaut
    * => Affiche le code d'erreur et le message de statut de l'erreur
    *
    * Si dans l'appel a la requete l'option ' onError ' n'est pas definie alors cette fonction sera prise par defaut
    */
    defaultErrorMessage: function(xhr) {
    alert('Error ' + xhr.status + ' -- ' + xhr.statusText);
    },
    /**
    * Fonction d'affichage du succes de la requete par defaut
    * => Affiche la chaine de caractere de la reponse AJAX ( sorte de mode debug )
    *
    * Si dans l'appel a la requete l'option ' onSuccess ' n'est pas definie alors cette fonction sera prise par defaut
    */
    debugSuccessMessage: function(xhr) {
    alert('Reponse texte\n\n'+xhr.responseText);
    },
    /**
    * Fonction permettant l'envoi de la requete en utilisant les proprietes deja traitees et enregistrees
    */
    processRequest: function() {
    this.Ajax_object.open(
    this.properties['method'],
    this.properties['method'] == 'post' ?
    /*this.properties['url'] : this.properties['url']+this.properties['paramString'],*/
    this.properties['url'] : this.properties['url']+this.properties['paramString'],
    this.properties['async']
    );
    if ( this.properties['method'] == 'post' )
    this.Ajax_object.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    this.Ajax_object.send(this.properties['method'] == 'post' ? this.properties['paramString'] : null);
    }
    ////////////////////////////////////////////////////////////// FIN DES FONCTIONS INTERNES //////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    }
    //}
    ///////////////////////////////////////////////////////////////////// Fin de la pseudo classe /////////////////////////////////////////////////////////////////////////////////
    // Permet de contrer le moteur javascript dans un bug de reference de fonction
    // Generalement utilisee dans les methodes onComplete... onLoad... etc pour faire reference a l'objet en cours
    Function.prototype.bind = function(object) {
    var __method = this;
    return function() {
    return __method.apply(object, arguments);
    }
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Variables contenant les reponses Texte ou XML => permet dans une page HTML d'avoir acces facilement a la reponse de la requete AJAX //
    var responseText = new String();
    var responseXML = new Object();
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    // Librairie by The Rubik's Man
    // © 2005-2006
    //

