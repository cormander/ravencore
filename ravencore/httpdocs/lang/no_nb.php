<?php

$lang = array();

// global
$lang['global_search'] = 'S&oslash;k';
$lang['global_go'] = 'Start';
$lang['global_please_enter_search_value'] = 'Vennligst skriv inn en verdi';
$lang['global_show_all'] = 'Vis alle';
$lang['global_back'] = 'Tilbake';
$lang['global_your_search_returned'] = 'Ditt s&oslash;k ga';
$lang['global_results'] = 'treff.';
$lang['global_name'] = 'Navn';
$lang['global_domains'] = 'Domener';
$lang['global_disc_space_usage'] = 'Disk forbruk';
$lang['global_traffic_usage_current_month'] = 'Trafikk forbruk (denne mnd)';
$lang['global_traffic_usage'] = 'Trafikk forbruk';
$lang['global_domain_usage'] = 'Domene forbruk';
$lang['global_totals'] = 'Totalt';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';

// auth.php page
$lang['welcome_and_thank_you'] = 'Velkommen, og takk for att du bruker RavenCore!';
$lang['please_upgrade_config'] = 'Du installerte og/eller oppgraderte noen pakker som krever nye innstillinger. Vennligst bruk litt tid p&aring; se igjennom disse innstiliingene. Vi anbefaller at de benytter standard innstillingene, men vet du hva du driver med s&aring; s&aring; kan du endre de til det som passer ditt system.';
$lang['test_suid_error'] = 'Ditt system har ikke mulighet for &aring; sette uid til root ved hjelp av wrapper. Dette er p&aring;krevd for at RavenCore skal fungere. For &aring; korrigere dette kan du pr&oslash; f&oslash;lgende:<p>
* Installer <b>gcc</b> og pakken som inneholder <b>/usr/include/sys/types.h</b> og restart RavenCore<br />
&nbsp;&nbsp;eller<br />
* Installer <b>perl-suidperl</b> pakken og restart RavenCore<br />
&nbsp;&nbsp;eller<br />
* Kopier wrapper filen fra en annen server med RavenCore installer i RavenCore\'s sbin/ p&aring; denne serveren';

$lang['auth_no_php_mysql'] = 'Unable to call the mysql_connect function. Please install the php-mysql package or recompile PHP with mysql support, and restart the control panel.<p>If php-mysql is installed on the server, check to make sure that the mysql.so extention is getting loaded in your system\'s php.ini file';
$lang['auth_locked_outdated'] = "Login locked because control panel is outdated.";
$lang['auth_api_cmd_failed'] = 'API command failed. This server is configured as a master server.';
$lang['auth_locked_upgrading'] = "Control Panel is being upgraded. Login Locked.";
$lang['auth_no_php_session'] = 'The server doesn\'t have PHP session functions available.<p>Please recompile PHP with sessions enabled';
$lang['auth_no_database_cfg'] = 'You are missing the database configuration file: ' . $CONF[RC_ROOT] . '/database.cfg<p>Please run the following script as root:<p>' . $CONF[RC_ROOT] . '/sbin/database_reconfig';
$lang['auth_no_database_connect'] = 'Unable to get a database connection.';
$lang['auth_must_agree_gpl'] = 'You must agree to the GPL License to use RavenCore';
$lang['auth_please_agree_gpl'] = 'Vennligst les GPL lisensen og kryss av p&aring; "Jeg godtar" boksen under';
$lang['auth_gpl_appear_below'] = 'GPL lisensen kommer i rammen under:';
$lang['auth_i_agree_gpl'] = 'Jeg godtar dette';
$lang['auth_login_locked'] = 'Kontoen er l&aring;st';
$lang['auth_login_failure'] ='Feil logginn';
$lang['auth_cp_userlock_outdated_settings'] = 'Kontrollpanelet er låst grunnet låst hvis utdatert innstillingen er aktiv, og det ser ut til at vi er utdatert.';
$lang['auth_conf_file_configuration'] = 'konfigurasjon';

// login.php
$lang['login_please_login'] = 'Vennligst logg inn';
$lang['login_username'] = 'Brukernavn';
$lang['login_password'] = 'Passord';
$lang['login_language'] = 'Spr&aring;k';
$lang['login_option_default'] = 'Standard';
$lang['login_your_login_is_secure'] = 'Innloggingen er sikker';
$lang['login_go_to_secure_login'] = 'G&aring; til sikker innlogging';
$lang['login_login'] = 'Logg inn';

// ad_db.php
$lang['add_db_adding_a_database_for'] = 'Legger til en database for';
$lang['add_db_add_database'] = 'Legg til database';

// domains.php
$lang['domains_domains_for'] = 'Domener for';
$lang['domains_there_are_no_domains_setup'] = 'Det er ikke satt opp noen domener';
$lang['domains_view_setup_information_for'] = 'Vis oppsettet for';
$lang['domains_you_are_at_domain_limit'] = 'Du har n&aring;dd din grense for antall domener';
$lang['domains_add_a_domain_to_server'] = 'Legger til ett domene';
$lang['domains_add_a_domain'] = 'Legg til domene';
$lang['domains_domain_no_exist'] = 'Domenet eksisterer ikke';
$lang['domains_domain_belongs_to'] = 'Domenet tilh&oslash;rer';
$lang['domains_no_one'] = 'Ingen';
$lang['domains_change'] = 'Endre';
$lang['domains_deletes_this_domain'] = 'Sletter dette domenet fra serveren';
$lang['domains_sure_you_want_to_delete'] = 'Er du sikker p&aring; at du vil slette dette domenet';
$lang['domains_delete'] = 'slett';
$lang['domains_name'] = 'Navn';
$lang['domains_created'] = 'Opprettet';
$lang['domains_status'] = 'Status';
$lang['domains_on'] = 'P&Aring;';
$lang['domains_sure_turn_off_hosting'] = 'Er du sikker p&aring; at du vil sl&aring; av hosting for dette domenet';
$lang['domains_turn_off_hosting'] = 'Sl&aring; AV hosting for dette domenet';
$lang['domains_off'] = 'AV';
$lang['domains_turn_on_hosting'] = 'Sl&aring; P&Aring; hosting for dette domenet';
$lang['domains_physical'] = 'Fysisk hosting';
$lang['domains_view_edit_physical'] = 'Vis/endre fysisk hosting for dette domenet';
$lang['domains_edit'] = 'rediger';
$lang['domains_redirect'] = 'Rediriger';
$lang['domains_view_edit_redirect'] = 'Vis/Endre hvor domenet redirrigeres til';
$lang['domains_alias'] = 'Alias for';
$lang['domains_view_edit_alias'] = 'Vis/Endre hva dette domenet er alias for';
$lang['domains_no_hosting'] = 'Ingen hosting';
$lang['domains_setup_hosting'] = 'Sett opp hosting for dette domenet';
$lang['domains_setup'] = 'oppsett';
$lang['domains_filemanager'] = 'Filbehandler';
$lang['domains_go_to_filemanager'] = 'G&aring; til filbehandleren for dette domenet';
$lang['domains_offline_filemanager'] = 'Filehandleren er ikke aktiv';
$lang['domains_filemanager_currently_offline'] = 'Filbehandleren er ikke aktiv';
$lang['domains_filemanager_offline'] = '( inaktiv )';
$lang['domains_log_manager'] = 'Loggbehandler';
$lang['domains_go_to_log_manager'] = 'G&aring; til loggbehandler for dette domenet';
$lang['domains_error_docs'] = 'Feilkode dokumenter';
$lang['domains_view_edit_ced'] = 'Vis/Endre egendefinerte feilkode dokumenter for dette domenet';
$lang['domains_mail'] = 'E-Post';
$lang['domains_view_edit_mail'] = 'Vis/Endre e-post for dette domenet';
$lang['domains_mail_off'] = '( inaktiv )';
$lang['domains_view_edit_domain_databases'] = 'Vis/Endre databasen(e) for dette domenet';
$lang['domains_databases'] = 'Databaser';
$lang['domains_manage_dns'] = 'Endre DNS postene for dette domenet';
$lang['domains_dns_records'] = 'DNS poster';
$lang['domains_dns_off'] = '( inaktiv )';
$lang['domains_view_webstats'] = 'Vis web statistikk for dette domenet';
$lang['domains_webstats'] = 'Webstatistikk';
$lang[''] = '';
$lang[''] = '';

// functions.php
$lang['menu_users'] = 'Brukere';
$lang['menu_domains'] = 'Domener';
$lang['menu_mail'] = 'E-Post';
$lang['menu_databases'] = 'Databaser';
$lang['menu_dns'] = 'DNS';
$lang['menu_system'] = 'System';
$lang['menu_logout'] = 'Logg ut';
$lang['menu_list_control_panel_users'] = 'List ut kontrollpanel brukere';
$lang['menu_list_domains'] = 'List ut domener';
$lang['menu_list_email_addresses'] = 'List ut e-post adresser';
$lang['menu_list_databases'] = 'List ut databaser';
$lang['menu_dns_for_domains_on_this_server'] = 'DNS for domener p&aring; denne serveren';
$lang['menu_manage_system_settings'] = 'Administrer system innstillinger';
$lang['menu_view_all_server_log_files'] = 'Vis alle server loggfiler';
$lang['functions_unable_to_connect_db'] = 'Klarer ikke &aring; koble til database serveren! Prøver &aring; restarte restart mysql';
$lang['functions_this_server_does_not_have'] = 'Denne serveren har ikke';
$lang['functions_installed_page_cannot_be_displayed'] = 'installert. Siden kan ikke vises';

// users.php
$lang['users_no_users_setup'] = 'Det er ikke satt opp noen brukere';
$lang['users_view_data_for'] = 'Vis data f&aring;r';
$lang['users_add_cp_user'] = 'Legg til en bruker i kontrollpanelet';
$lang['users_add_a_cp_user'] = 'Legg til en kontrollpanel bruker';
$lang['users_user_no_exist'] = 'Brukeren eksisterer ikke.';
$lang['users_failed_login_lockout'] = 'Denne brukeren er utestengt grunnet for mange mislykkede innlogginsfors&oslash;k';
$lang['users_unlock'] = 'Fjern sperre';
$lang['users_company'] = 'Firma';
$lang['users_created'] = 'Opprettet';
$lang['users_contact_email'] = 'E-postadresse';
$lang['users_login_id'] = 'Bruker ID';
$lang['users_edit_account_info'] = 'Rediger kontoinfo';
$lang['users_see_what_you_can_and_not_do'] = 'Se hva du kan og ikke kan gj&oslash;re';
$lang['users_view_perms'] = 'Vis rettigheter';
$lang['users_view_edit_perms'] = 'Vis/rediger rettigheter';
$lang['users_options'] = 'Opsjoner';
$lang['users_you_have_no_domains_setup'] = 'Du har ikke satt opp noen domener';
$lang['users_no_domains_setup'] = 'Ingen domener er opprettet';
$lang['users_for_which_domain'] = 'For hvilket domene';
$lang['users_add_mysql_database'] = 'Legg til MySQL database';
$lang['users_add_email_account'] = 'Legg til E-post konto';
$lang['users_list_domains'] = 'Domene liste';
$lang['users_view_webstats'] = 'Vis statestikk';
$lang['users_add_a_domain'] = 'Legg til domene';
$lang['users_add_edit_dns'] = 'Legg til/redigere DNS';
$lang['users_domain_limit_reached'] = 'Du har n&aring;dd din grense for antall domener';
$lang['users_user_reached_domain_limit'] = 'Denne brukeren har n&aring;dd sin domene grense';
$lang['users_add_one_anyway'] = 'Legg til alikvell';
$lang['users_no_users_setup'] = 'Det er ikke opprettet noen brukere';
$lang['users_view_user_data_for'] = 'Vis bruker data for';
$lang['users_list_all_your_domains'] = 'Lister ut alle dine domener';
$lang['users_add_a_domain_to_the_server'] = 'Legger til et domene til serveren';

/*
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
$lang[''] = '';
*/
?>