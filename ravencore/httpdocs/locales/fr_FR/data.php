<?php

// the $trans array key is first two letters as ISO 639 language code, underscore,
// and the last two letters ISO 3166 country code.
// ex: en_US  ru_RU  nb_NO  etc

$trans['fr_FR'] = array(

   'Name' =>
   'Nom',
   'Add Database' =>
   'Ajouter BDD',
   'Invalid password. Must only contain letters and numbers, must be atleast 5 characters, and not a dictionary word' =>
   'Mot de passe incorrect. (seul. des lettres et nombres, au moins 5 caract. et ne pas être un mot du dictionnaire).',
   'Adding a user for database' =>
   'Ajouter un usagé pour la BDD',
   'Login' =>
   'Identification',
   'Password' =>
   'Mot de passe',
   'Add User' =>
   'Ajouter un usager',
   'Your record name and target cannot be the same.' =>
   'Le nom RECORD et la destination ne peuvent être le même.',
   'You cannot enter in a full domain as the record name.' =>
   'Vous ne pouvez entrer un domaine entier comme nom RECORD.',
   'You already have a default SOA record set' =>
   'Vous avez déjà un SOA enregistré',
   'Default Start of Authority' =>
   'Start of Authority par défaut',
   'Record Name' =>
   'Nom RECORD',
   'Target IP' =>
   'IP de destination',
   'Nameserver' =>
   'Serveur de nom',
   'Mail for the domain' =>
   'Courriel pour le domaine',
   'MX Preference' =>
   'Préférence MX',
   'Mail Server' =>
   'Serveur de courriel',
   'Alias name' =>
   'Nom d\'alias',
   'Target name' =>
   'Nom de destination',
   'Reverse pointer records are not yet available' =>
   'Les pointeurs inversés ne sont pas encore disponibles',
   'Invalid DNS record type' =>
   'Type de RECORD DNS invalide',
   'Add Record' =>
   'Ajouter un RECORD',
   'Start of Authority for' =>
   'Start of Authority pour',
   'Mail for' =>
   'Courriel pour',
   'must not be an IP!' =>
   '',
   'The server doesn\'t have PHP session functions available.<p>Please recompile PHP with sessions enabled.' =>
   'Le serveur n\'a pas les fonctions PHP. Veuillez recompiler PHP avec les sessions activées.',
   'You are missing the database configuration file:' =>
   'Il manque le fichier de configuration de la BDD',
   '/database.cfg<p>Please run the following script as root:<p>' =>
   '/database.php<p>Veuillez, en ROOT, exécuter:<p>',
   '/sbin/database_reconfig' =>
   '/sbin/database_reconfig',
   '' =>
   '',
   'Your system is unable to set uid to root with the wrapper. This is required for ravencore to function. To correct this:<p>' =>
   'Votre système ne peut, même avec le WRAPPER, changer le UID à root. Ceci est obligatoire pour Ravencore. Pour corriger:<p>',
   'the file: <b>/usr/local/ravencore/sbin/wrapper</b><p>' =>
   'le fichier <b>/usr/local/ravencore/sbin/wrapper</b><p>',
   'do one of the following:<p>' =>
   'faite l\'un des choix suivants',
   'Install <b>gcc</b> and the package that includes <b>/usr/include/sys/types.h</b> and restart ravencore<br />\n' =>
   'Installer <b>GCC</b>, le paquetage qui inclue <b>/usr/include/sys/types.h</b> et redémarrez Ravencore',
   '/>\n' =>
   '/>\n',
   'Install the <b>perl-suidperl</b> package and restart ravencore<br />\n' =>
   'Installer le paquetage <b>perl-suidperl</b> et redémarrer Ravencore<br />\n',
   '/>\n' =>
   '/>\n',
   'Copy the wrapper binary from another server with ravencore installed into ravencore\'s sbin/ on this server' =>
   'Copiez le binaire du WRAPPER d\'un autre serveur Ravencore dans le dossier sbin de Ravencore sur le présent serveur',
   '' =>
   '',
   'to call the mysql_connect function. \n' =>
   'pour appeler la fonction mysql_connect.',
   '\t\t\tPlease install the php-mysql package or recompile PHP with mysql support, and restart the control panel.<p>\n' =>
   '\t\t\tVeuillez installer php-mysql ou recompiler PHP avec le support MySQL. Ensuite, redémarrez Ravencore.<p>\n',
   'php-mysql is installed on the server, check to make sure that the mysql.so extention is getting loaded in your system\'s php.ini file' =>
   'php-mysql est installé sur le serveur: Vérifiez que l\'extension mysql.so est chargée par votre php.ini',
   'Unable to get a database connection.' =>
   'Incapable de connecter à une BDD.',
   'Login locked.' =>
   'Identification vérouillée.',
   'Login failure.' =>
   'Échec de l\'identification',
   'Control panel is locked for users, because your \"lock if outdated\" setting is active, and we appear to be outdated.' =>
   'Panneau de contrôle verrouillé pour les usagers pcq "Vérouillé si plus à jour" activé. Et nous ne sommes plus à jour',
   'Login locked because control panel is outdated.' =>
   'Identification impossible Panneau de contrôle plus à jour.',
   'API command failed. This server is configured as a master server.' =>
   'Échec de commande API. Ce serveur est configuré comme maître.',
   'You must agree to the GPL License to use RavenCore.' =>
   'Vous devez accepter la license GPL pour utiliser Ravencore.',
   'Please read the GPL License and select the \"I agree\" checkbox below' =>
   'Veuillez lire la license GPL et cocher \"J\'accepte\" ci-dessous',
   'The GPL License should appear in the frame below' =>
   'La license GPL devrait paraître dans le câdre ci-dessous',
   'I agree to these terms and conditions.' =>
   'J\'accepte ces termes et conditions.',
   'Welcome, and thank you for using RavenCore!' =>
   'Bienvenue et merci d\'utiliser Ravencore!',
   '' =>
   '',
   'installed and/or upgraded some packages that require new configuration settings. \n' =>
   'a installé/mis à jour des paquetages qui requierent des nouvelles configurations. \n',
   'take a moment to review these settings. We recomend that you keep the default values, \n' =>
   'prennez un moment pour revoir ces configs. Nous recommandons de garder les valeurs par défaut, \n',
   'if you know what you are doing, you may adjust them to your liking.\n' =>
   'si vous savez ce que vous faites, vous pouvez les ajuster à souhait.\n',
   '' =>
   '',
   'configuration' =>
   'configuration',
   'Submit' =>
   'Soumettre',
   'Control Panel is being upgraded. Login Locked.' =>
   'Panneau de contrôle en cours de mise à niveau. Identification verrouillée.',
   'The password is incorrect!' =>
   'Mot de passe incorrect!',
   'The new password must be greater than 4 characters and not a dictionary word' =>
   'Le nouveau MDP doit avoir + de 4 caractères et ne pas être un mot de dictionnaire',
   'Cannot select MySQL database' =>
   'Ne peut connecter à MySQL DB',
   'Cannot change database password' =>
   'Ne peut changer mot de passe de DB',
   'Unable to flush database privileges' =>
   'Incapable de flusher les privilèges de DB',
   'Cannot open .shadow file' =>
   'Ne peut ouvrir le fichier shadow',
   'Your passwords are not the same!' =>
   'Vos MDP diffèrent',
   'Please change the password for' =>
   'Veuillez changer le MDP pour',
   'Changing' =>
   'Change',
   'password!' =>
   'mot de passe!',
   'Old Password' =>
   'Ancien MDP',
   'New Password' =>
   'Nouveau MDP',
   'Confirm New' =>
   'Confirmez nouveau',
   'Change Password' =>
   'Changer MDP',
   'Add a crontab' =>
   'Ajouter un CRONTAB',
   'There are no crontabs.' =>
   'Il n\'y a pas de CRONTAB.',
   'User' =>
   'Usagé',
   'Choose a user' =>
   'Choisissez un usagé',
   'Delete Selected' =>
   'Effacer la sélection',
   'Entry' =>
   'Entrée',
   'Add Crontab' =>
   'Ajouter CRONTAB',
   'Unable to use mysql database' =>
   'Incapable d\'utilise la DB MySQL',
   'That database does not exist' =>
   'La DB n\'existe pas',
   'Add a Database' =>
   'Ajouter un DB',
   'No databases setup' =>
   'Aucune DB configurée',
   'Databases for' =>
   'DB pour',
   'Are you sure you wish to delete this database?' =>
   'Vous désirez vraiment effacer cette DB?',
   'delete' =>
   'effacer',
   'Users for the' =>
   'Usagers pour la',
   'database' =>
   'DB',
   'Add a database user' =>
   'Ajouter un usager pour cette DB',
   'No users for this database' =>
   'Aucun usagé pour cette DB',
   'Delete' =>
   'Effacer',
   'Are you sure you wish to delete this database user?' =>
   'Désirez-vous vraiment effacer cet usagé de DB?',
   'Note: You may only manage one database user at a time with the phpmyadmin' =>
   'Note: phpmyadmin ne permet le gestion que d\'un usagé à la fois',
   'Search' =>
   'Recherche',
   'Please enter in a search value!' =>
   'Entrez une valeur à chercher!',
   'Show All' =>
   'Montrer tout',
   'There are no databases setup' =>
   'Il n\'y a pas de DB configurée',
   'Your search returned' =>
   'Votre recherche a retourné',
   'results' =>
   'résultats',
   'Domain' =>
   'Domaine',
   'Database' =>
   'BDD',
   'No DNS records setup on the server' =>
   'aucune entrée DNS configurée sur le serveur',
   'The following domains are setup for DNS' =>
   'Les domaines suivants ont des entrées DNS',
   'Records' =>
   'Entrées',
   'No SOA record setup for this domain' =>
   'Aucun SOA configuré pour ce domaine',
   'Add SOA record' =>
   'Ajouter un SOA',
   'DNS for' =>
   'DNS pour',
   'Start of Authority for' =>
   'Start of Authority pour',
   'is' =>
   'est',
   'No DNS records setup for this domain' =>
   'Aucun DNS configuré pour ce domaine',
   'Record Type' =>
   'Type de RECORD',
   'Record Target' =>
   'Destination RECORD',
   'Add record' =>
   'Ajouter RECORD',
   'Add' =>
   'Ajouter',
   'No default DNS records setup for this server' =>
   'Aucun DNS par défaut n\'est configuré pour ce serveur',
   'Default DNS for domains setup on this server' =>
   'DNS par défaut configurés sur ce serveur',
   'Domains for' =>
   'Domaines pour',
   'There are no domains setup' =>
   'Il n\'y a pas de domaine',
   'Add a Domain' =>
   'Ajouter un domaine',
   'Go' =>
   'Envoyer',
   'Please enter a search value!' =>
   'Entrez une valeur à chercher!',
   'Space usage' =>
   'Espace utilisé',
   'Traffic usage' =>
   'Bande passante utilisée',
   'View setup information for' =>
   'Voir les infos de config pour',
   'Totals' =>
   'Totaux',
   'You are at your limit for the number of domains you can have' =>
   'Vous avez atteint la limite permise pour le nombre de domaines',
   'Add a domain to the server' =>
   'Ajouter un domaine',
   'Domain does not exist' =>
   'Domaine inexistant',
   'This domain belongs to' =>
   'Ce domaine appartient à',
   'No One' =>
   'Personne',
   'Change' =>
   'Changer',
   'Info for' =>
   'Infos pour',
   'Delete this domain off the server' =>
   'Effacer ce domaine du serveur',
   'Are you sure you wish to delete this domain' =>
   'Désirez-vous vraiement effacer ce domaine',
   'Created' =>
   'Créé le',
   'Status' =>
   'Statut',
   'ON' =>
   'ON',
   'Are you sure you wish to turn off hosting for this domain' =>
   'Désirez-vous vraiment désactiver l\'hébergement de ce domaine',
   'Turn OFF hosting for this domain' =>
   'Désactiver l\'hébergement du domaine',
   'OFF' =>
   'OFF',
   'Turn ON hosting for this domain' =>
   'Activer l\'hébergement du domaine',
   'Physical Hosting' =>
   'Hébergement physique',
   'View/Edit Physical hosting for this domain' =>
   'Voir/modifier l\'hébergement physique du domaine',
   'edit' =>
   'éditer',
   'Redirect' =>
   'Redirection',
   'View/Edit where this domain redirects to' =>
   'Voir/modifier la redirection du domaine',
   'Alias of' =>
   'Alias de',
   'View/Edit what this domain is a server alias of' =>
   'Voir/modifier l\'alias du domaine',
   'No Hosting' =>
   'Pas d\'hébergement',
   'Setup hosting for this domain' =>
   'Configurer l\'hébergement de ce domaine',
   'Go to the File Manager for this domain' =>
   'Aller au gestionnaire de fichiers du domaine',
   'The file manager is currently offline' =>
   'Gestionnaire de fichiers désactivé',
   'File Manager' =>
   'Gestionnaire de fichiers',
   'View/Edit Custom Error Documents for this domain' =>
   'Voir/modifier les documents d\'erreurs personnalisés',
   'Error Documents' =>
   'Documents d\'erreurs',
   'View/Edit Mail for this domain' =>
   'Voir/modifier courriel du domaine',
   'Mail' =>
   'Courriel',
   '( off )' =>
   '( off )',
   'View/Edit databases for this domain' =>
   'Voir/modifier les BDD du domaine',
   'Databases' =>
   'BDD',
   'Manage DNS for this domain' =>
   'Configurer les DNS du domaine',
   'DNS Records' =>
   'Entrées DNS',
   'View Webstats for this domain' =>
   'Voir statistiques d\'achalandages',
   'Webstats' =>
   'Stats Web',
   'Domain Usage' =>
   'Statistiques d\'utilisation du domaine',
   'Disk space usage' =>
   'Espace disque utilisé',
   'This month\'s bandwidth' =>
   'Bande passante du mois',
   'Illegal argument' =>
   'Argument illégal',
   'Please enter the domain name you wish to setup' =>
   'Veuillez entrer le nom de domaine à configurer',
   'Invalid domain name. Please re-enter the domain name without the www.' =>
   'Nom de domaine invalide. Veuillez recommencer sans le www.',
   'Invalid domain name. May only contain letters, numbers, dashes and dots. Must not start or end with a dash or a dot, and a dash and a dot cannot be next to each other' =>
   'Nom de domaine invalide. Ne peut contenir que: lettres, nombres, traits d\'union et points. Doit commencer par une lettre/nombre. 2 traits ou points ne peuvent se suivre',
   'Control Panel User' =>
   'Usagé du panneau de contrôle',
   'Select One' =>
   'Choisissez',
   'Add domain' =>
   'Ajouter domaine',
   'Add Domain' =>
   'Ajouter domaine',
   'Proceed to hosting setup' =>
   'Exécuter les configs d\'hébergement',
   'Add default DNS to this domain' =>
   'Ajouter les DNS par défaut',
   'That email address already exists' =>
   'L\'adresse courriel existe déjà',
   'Your passwords do not match' =>
   'Vos mots de passe diffèrent',
   'You selected you wanted a redirect, but left the address blank' =>
   'Vous désirez une redirection, mais le champ destination est vide',
   'Invalid password. Must only contain letters and numbers.' =>
   'MDP incorrect. Doit contenir lettres et nombres seulement.',
   'The redirect list contains an invalid email address.' =>
   'La liste contient une adresse courriel invalide.',
   'Invalid mailname. It may only contain letters, number, dashes, dots, and underscores. Must both start and end with either a letter or number.' =>
   'MAILNAME invalide. Doit contenir lettres, nombres, trait d\'union ou soulignement et point. Doit débuter ET terminer par une lettre ou un nombre.',
   'Mail is disabled for' =>
   'Courriel désactivé pour',
   '. You can not add an email address for it.' =>
   '. Vous ne pouvez ajouter un courriel pour lui.',
   'Edit' =>
   'Modifier',
   'mail' =>
   'courriel',
   'Mail Name' =>
   'Nom courriel',
   'Confirm' =>
   'Confirmez',
   'Mailbox' =>
   'Boîte de courriel',
   'Mail will not be stored on the server if you disable this option. Are you sure you wish to do this?' =>
   'Les courriels ne seront pas livré sur ce serveir avec cette option. Ètes-vous certain de vouloir ceci?',
   'List email addresses here, seperate each with a comma and a space' =>
   'Listez les courriels ici, séparés chacun d\une virgule ET d\'un espace',
   'Add Mail' =>
   'Ajouter courriel',
   'Update' =>
   'Mise à jour',
   'You must enter a name for this user' =>
   'Vous devez entrer un nom d\'usagé',
   'You must enter a password for this user' =>
   'Vous devez entrer un MDP pour cet usagé',
   'Your password must be atleast 5 characters long, and not a dictionary word.' =>
   'Votre MDP doit contenir au moins 5 caract. et ne pas être un mot du dictionnaire.',
   'The email address entered is invalid' =>
   'Le courriel saisi est invalide',
   'info' =>
   'infos',
   'Full Name' =>
   'Nom complet',
   'Email Address' =>
   'Adresse courriel',
   'Edit Info' =>
   'Modifier infos',
   'Proceed to Permissions Setup' =>
   'Continuer avec les permissions',
   'Required fields' =>
   'Champs requis',
   'Are you sure you wish to delete this user?' =>
   'Désirez-vous vraiment effacet cet usagé?',
   'No custom error documents setup.' =>
   'Aucun documents d\'erreurs personnalisés.',
   'Add Custom Error Document' =>
   'Ajouter un document d\'erreur personnalisé',
   'Code' =>
   'Code',
   'File' =>
   'Fichier',
   'List HTTP Status Codes' =>
   'Lister les codes HTTP',
   'This server does not have' =>
   'Ce serveur n\'a pas',
   'installed. Page cannot be displayed.' =>
   'installé. Impossible d\'afficher la page.',
   'Unable to connect to DB server! Attempting to restart mysql' =>
   'Incapable de connecter à la BDD! Essai de redémarrage de MySQL',
   'Restart command completed. Please refresh the page.' =>
   'Redémarrage complété. Veuillez rafraîchir la page.',
   'If the problem persists, contact the system administrator' =>
   'Si le problème persiste, contactez l\'administrateur',
   'You are not authorized to view this page' =>
   'Vous n\'êtes pas autorisé à voir cette page',
   'List control panel users' =>
   'Lister les usagés du panneau de contrôle',
   'Users' =>
   'Usagers',
   'List domains' =>
   'Lister les domaines',
   'Domains' =>
   'Domaines',
   'List email addresses' =>
   'Lister les adresses courriel',
   'List databases' =>
   'Lister les BDD',
   'DNS for domains on this server' =>
   'Domaines avec DNS sur ce serveur',
   'DNS' =>
   'DNS',
   'Manage system settings' =>
   'Gérer les préférences système',
   'System' =>
   'Système',
   'Goto main server index page' =>
   'Aller à la page d\'accueil',
   'Main Menu' =>
   'Menu principal',
   'List your domains' =>
   'Lister vos domaines',
   'My Domains' =>
   'Mes domaines',
   'List all your email accounts' =>
   'Lister tous les comptes courriels',
   'My email accounts' =>
   'Mes comptes courriels',
   'Logout' =>
   'Quitter',
   'Are you sure you wish to logout?' =>
   'Désirez-vous vraiment quitter?',
   'Are you sure you wish to delete hosting for this domain?' =>
   'Désirez-vous vraiment effacer l\'hébergement de ce domaine?',
   'delete hosting' =>
   'effacer hébergement',
   'www prefix' =>
   'préfixe www',
   'Yes' =>
   'Oui',
   'No' =>
   'Non',
   'FTP Username' =>
   'Usagé FTP',
   'FTP Password' =>
   'MDP FTP',
   'Shell' =>
   'Console',
   'SSL Support' =>
   'Support SSL',
   'If you disable ssl support, you will not be able to enable it again.\\rAre you sure you wish to do this?' =>
   'Si vous désactivez le support SSL, vous ne pourrez le remettre.\\rÊtes-vous certains de vouloir ceci?',
   'PHP Support' =>
   'Support PHP',
   'If you disable php support, you will not be able to enable it again.\\rAre you sure you wish to do this?' =>
   'Si vous désactivez le support PHP, vous ne pourrez le remettre.\\rÊtes-vous certains de vouloir ceci?',
   'CGI Support' =>
   'Support CGI',
   'If you disable cgi support, you will not be able to enable it again.\\rAre you sure you wish to do this?' =>
   'Si vous désactivez le support CGI, vous ne pourrez le remettre.\\rÊtes-vous certains de vouloir ceci?',
   'Directory indexing' =>
   'Index des répertoires',
   'This domain is an alias of' =>
   'Ce domaine est un alias de',
   'Host on this server' =>
   'Héberger sur ce serveur',
   'Redirect to another domain' =>
   'Rediriger vers un autre domaine',
   'Show contents of another site on this server' =>
   'Afficher le contenu d\'un autre domaine sur ce serveur',
   'Continue' =>
   'Continuer',
   'Are you sure you wish to delete this log file?' =>
   'Désirez-vous vraiment effacer ce ficher LOG?',
   'Log files for' =>
   'Fichiers LOG pour',
   'Manage' =>
   'Gérer',
   'Go to log rotation manager for' =>
   'Aller à la gestion de rotation des LOGS pour',
   'Log Rotation' =>
   'Rotation des LOGS',
   'Log Name' =>
   'Nom du LOG',
   'Compression' =>
   'Compression',
   'File Size' =>
   'Taille du fichier',
   'Download the' =>
   'Télécharger le',
   'Custom log rotation for' =>
   'Rotation perso des LGOS pour',
   'is' =>
   'est',
   'Are you sure you wish to turn off the custom log rotation for' =>
   'Désirez-vous vraiment désactiver la rotation perso des LOGS pour',
   'Turn OFF log rotation for' =>
   'Désactiver la rotation des LOGS pour',
   'Turn ON log rotation for' =>
   'Activer la rotation des LOGS pour',
   'You must choose how many log files you wish to keep!' =>
   'Vous devez indiquer combien de ficheirs LOGS garder!',
   'You must make a rotation selection: filesize, date, or both' =>
   'Vous devez dire comment faire la rotation: taille, date ou les 2',
   'Keep' =>
   'Garder',
   'log files' =>
   'fichiers LOGS',
   'Rotate by' =>
   'Faire la rotation par',
   'Filesize' =>
   'Taille de fichier',
   'Date' =>
   'Date',
   'Daily' =>
   'Quotidiennement',
   'Weekly' =>
   'Hebdomadairement',
   'Monthly' =>
   'Mensuellement',
   'Email about-to-expire files to' =>
   'Envoyer les fichier à expirer par courriel à',
   'Compress log files' =>
   'Compresser les LOGS',
   'No domains setup, so there are no Log files' =>
   'Aucun domaine configuré... Donc, aucun LOG',
   'Please Login' =>
   'Veuillez vous identifier',
   'Username' =>
   'Usagé',
   'Language' =>
   'Langue',
   'English' =>
   'Anglais',
   'Your login is secure' =>
   'Identification sécurisée',
   'Go to Secure Login' =>
   'Aller à l\'identification sécurisée',
   'Goto' =>
   'Aller à',
   'Turn ON mail for' =>
   'Activer le courriel pour',
   'Turn OFF mail for' =>
   'Désactiver le courriel pour',
   'Are you sure you wish to disable mail for this domain?' =>
   'Désirez-vous vraiment désactiver le courriel pour ce domaine?',
   'Mail sent to email accounts not set up for this domain ( catchall address )' =>
   'Courriels envoyés aux adresses inexistantes de ce domaine (attrape tout)',
   'Send to' =>
   'Envoyer à',
   'Bounce with' =>
   'Rebondir avec',
   'Delete it' =>
   'Effacer',
   'Forward to that user' =>
   'Rediriger vers',
   'You need at least two domains in the account with mail turned on to be able to alias mail' =>
   'Vous devez avour au moins 2 domaines votre compte ayant les courriels activés pour pouvoir aliasser',
   'No mail for this domain.' =>
   'Aucun courriel pour ce domaine.',
   'Mail for this domain' =>
   'Courriel pour ce domaine',
   'Webmail' =>
   'Courriel Web',
   'Webmail is currently offline' =>
   'Courriel Web désactivé',
   'offline' =>
   'désactivé',
   'If you delete this email, you may not be able to add it again.\\rAre you sure you wish to do this?' =>
   'Si vous effacez ce courriel, vous nepourez le ré-ajouter.\\rDésirez-vous vraiment ceci?',
   'Are you sure you wish to delete this email?' =>
   'Désirez-vous vraiment effacer ce courriel?',
   'This user is only allowed to create' =>
   'Ce compte ne peut créer que',
   'email accounts. Are you sure you want to add another?' =>
   'compte de courriels. Désirez-vous en ajouter un autre?',
   'Add an email account' =>
   'Ajouter un compte courriel',
   'You have no domains setup.' =>
   'Aucun domaine configuré.',
   'Create a new email account' =>
   'Créer un compte courriel',
   'Add an email address' =>
   'Ajouter une adresse courriel',
   'There are no mail users setup' =>
   'Aucun usagé courriel configuré',
   'Email Addresses' =>
   'Adresses courriel',
   'Service' =>
   'Service',
   'Running' =>
   'Activé',
   'Start' =>
   'Démarrer',
   'Stop' =>
   'Arrêter',
   'Restart' =>
   'Redémarrer',
   'IP Address' =>
   'Adresse IP',
   'Session Time' =>
   'Durée de session',
   'Idle Time' =>
   'Durée inactive',
   'Remove' =>
   'Détruire',
   'Stop/Start system services such as httpd, mail, etc' =>
   'Arrêter/démarrer les services tels HTTP, courriel, etc',
   'System Services' =>
   'Services système',
   'View who is logged into the server, and where from' =>
   'Voyez qui est identifié au serveur, et d\'où',
   'Login Sessions' =>
   'Sessions identifiées',
   'Services that automatically start when the server boots up' =>
   'Services démarrant automatiquement avec le serveur',
   'Startup Services' =>
   'Démarrage automatique',
   'The DNS records that are setup for a domain by default when one is added to the server' =>
   'Les entrées DNS par défaut étant configurées lors de la création d\'un nouveau domaine',
   'Default DNS' =>
   'DNS par défaut',
   'Change the admin password' =>
   'Changer le MDP admin',
   'Change Admin Password' =>
   'Changer le MDP admin',
   'Load phpMyAdmin for all with MySQL admin user' =>
   'Charger phpMyAdmin pour tous avec les privilèges admin MySQL',
   'Admin MySQL Databases' =>
   'Administrer les BDD MySQL',
   'View general system information' =>
   'Infos système générales',
   'System Info' =>
   'Infos système',
   'View output from the phpinfo() function' =>
   'Voir phpinfo()',
   'PHP Info' =>
   'PHP info',
   'View Mail Queue' =>
   'Voir la queue de courriel',
   'Are you sure you wish to reboot the system?' =>
   'Désirez-vous vraiment redémarrer le serveur?',
   'Reboot the server' =>
   'Redémarrer le serveur',
   'Reboot Server' =>
   'Redémarrer le serveur',
   'You are about to shutdown the system. There is no way to bring the server back online with this software. Are you sure you wish to shutdown the system?' =>
   'Vous allez éteindre le serveur. Il n\'existe aucun moyen de le rallumer avec ce logiciel. Désirez-vous vraiment mettre le serveur hors tension?',
   'Shutdown the server' =>
   'Éteindre le serveur',
   'Shutdown Server' =>
   'Éteindre le serveur',
   'This user can' =>
   'Cet usager peut',
   'Create' =>
   'Créer',
   'Note: A negative limit mean unlimited' =>
   'Note: un nombre négatif signifie sans limite',
   'You can\'t add domains' =>
   'Vous ne pouvez pas ajouter un domaine',
   'You can\'t add databases' =>
   'Vous ne pouvez pas ajouter une BDD',
   'You can\'t add cron jobs' =>
   'Vous ne pouvez pas ajouter une tâche CRON',
   'You can\'t add email addresses' =>
   'Vous ne pouvez pas ajouter des comptes courriel',
   'You can\'t add DNS records' =>
   'Vous ne pouvez pas ajouter des entrées DNS',
   'You can\'t add cgi to hosting on any domains' =>
   'Vous ne pouvez pas activer CGI sur un domaine',
   'You can\'t add php to hosting on any domains' =>
   'Vous ne pouvez pas activer PHP sur un domaine',
   'You can\'t add ssl to hosting on any domains' =>
   'Vous ne pouvez pas activer le SSL sur un domaine',
   'You can\'t add shell users' =>
   'Vous ne pouvez pas ajouter un usagé de console',
   'There are no users setup' =>
   'Il n\'y a pas d\'usager',
   'View user data for' =>
   'Voyez les infos pour',
   'Add a user to the control panel' =>
   'Ajouter un usager au système',
   'Add a Control Panel user' =>
   'Ajouter un usager au système',
   'User does not exist' =>
   'Usagé inexistant',
   'This user is locked out due to failed login attempts' =>
   'Usagé verrouillé pour cause d\'échec d\'identification',
   'Unlock' =>
   'Dévérouiller',
   'Company' =>
   'Compagnie',
   'Contact email' =>
   'Courriel de contact',
   'Login ID' =>
   'Compte d\'usagé',
   'Edit account info' =>
   'Modifier le compte',
   'See what you can and can not do' =>
   'Voyez ce que vous pouvez et ne pouvez faire',
   'View/Edit Permissions' =>
   'Voir/modifier les permissions',
   'View Permissions' =>
   'Voir les permissions',
   'Options' =>
   'Options',
   'You have no domains setup' =>
   'Aucun domaine configuré',
   'No domains setup' =>
   'Aucun domaine configuré',
   'For which domain' =>
   'Pour quel domaine',
   'Back' =>
   'Retour',
   'Add a MySQL database' =>
   'Ajouter une BDD MySQL',
   'Add E-Mail Account' =>
   'Ajouter un compte courriel',
   'Add/Edit DNS records' =>
   'Ajouter/modifier une entrée DNS',
   'View Webstatistics' =>
   'Voir les statistiques d\'achalandages',
   'List all of your domain names' =>
   'Lister tous vos domaines',
   'List Domains' =>
   'Lister les domaines',
   'This user is at his/her domain limit' =>
   'Cet usagé a atteint sa limite de domaines',
   'Add one anyway' =>
   'Ajouter quand même',
   'Domain usage' =>
   'Statistiques d\'utilisation du domaine',
   'Traffic usage (This month)' =>
   'Bande passante ce mois-ci',
   'is not setup for physical hosting. Webstats are not available' =>
   'n\'est pas configuré pour hébergement physique. Aucune Statistique disponible',
   'OK' =>
   'OK'

   );

?>
