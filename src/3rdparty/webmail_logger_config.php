<?php

   global $sl_logs, $sl_log_events, $data_dir, $sl_logfile, $sl_namelookups,
          $sl_dateformat, $sl_send_alerts, $sl_mass_mail_limit,
          $sl_alert_to, $sl_alert_cc, $sl_alert_bcc,
          $sl_alert_subject_template, $sl_log_mass_mailing_show_recipients,
          $sl_log_mass_mailing_show_message_body, $sl_dsn, $sl_insert_event_query,
          $sl_use_GMT, $sl_fail_silently, $skip_domains, $only_log_domains,
          $sl_log_outgoing_messages_show_recipients,
          $sl_log_outgoing_messages_show_message_body, $sl_useSendmail,
          $sl_smtpServerAddress, $sl_smtpPort, $sl_sendmail_path,
          $sl_sendmail_args, $sl_pop_before_smtp,
          $sl_log_mass_mailing_show_reply_to,
          $sl_log_mass_mailing_show_from, $sl_alert_from,
          $sl_log_mass_mailing_show_subject,
          $sl_log_outgoing_messages_show_reply_to,
          $sl_log_outgoing_messages_show_from,
          $sl_log_outgoing_messages_show_subject,
          $sl_encode_header_key, $sl_smtp_auth_mech,
          $sl_smtp_sitewide_user, $sl_smtp_sitewide_pass;



   // This is a list of the types of events you would like 
   // to log.  The supported log events are:
   //
   //    LOGIN           Successful user login event
   //    LOGOUT          Successful user logout event 
   //    TIMEOUT         User session timeout
   //    OUTGOING_MAIL   Message sent
   //    MASS_MAILING    Message sent with more than $sl_mass_mail_limit recipients
   //    LOGIN_ERROR     Failed login attempt
   //    ERROR           Other system errors
   //
   // Note that other plugins or custom code might add their
   // own event types as well.  For example, the CAPTCHA plugin
   // has an optional "CAPTCHA" event type (an example of how to
   // log that kind of event can be found below under $sl_logs).
   // Other known plugin event types: "RESTRICT_SENDERS", "LOCKOUT"
   //
   $sl_log_events = array(
                             'LOGIN',
                             'LOGOUT',
                             'TIMEOUT',
//                             'OUTGOING_MAIL',
//                             'MASS_MAILING',
                             'LOGIN_ERROR',
//                             'ERROR',
//                             'CAPTCHA',
//                             'RESTRICT_SENDERS',
   );



   // This is a list of the log types you want to use and the types
   // of events  that are to be logged to each log destination.
   // It is also where you define the exact text of log messages
   // for each log type/event type.  Note that SQL log type formatting
   // is set in the $sl_insert_event_query setting and should not be
   // contained here; instead, you may only change the text of the
   // event name.
   //
   // You may use any (more than one is OK) of the following log
   // types:
   //
   //    SYSTEM:<priority>:<facility>:<ident>:<options>
   //    FILE
   //    SQL
   //
   // You can have multiple SYSTEM log types listed - for as many
   // different combinations of priority, log facility, ident, and
   // options you want to use.  Note that you may omit any or all
   // of these elements.  When you do, the default PHP log facility
   // (LOG_SYSLOG) will be used with LOG_INFO priority.  Some examples:
   //
   //    SYSTEM
   //    SYSTEM:LOG_INFO
   //    SYSTEM:LOG_INFO::squirrelmail
   //    SYSTEM:LOG_INFO:LOG_MAIL
   //    SYSTEM:LOG_INFO:LOG_MAIL:squirrelmail
   //    SYSTEM:LOG_WARNING:LOG_MAIL:squirrelmail:LOG_CONS | LOG_NDELAY | LOG_PID
   //
   // Again, you may use more than one such SYSTEM log, which allows
   // you to log different events with different priority or to a
   // different log facility.  For more details about the possible
   // priority types (LOG_EMERG, LOG_ALERT, LOG_CRIT, LOG_ERR,
   // LOG_WARNING, LOG_NOTICE, LOG_INFO, LOG_DEBUG), see:
   //
   //    http://php.net/manual/function.syslog.php
   //
   // For more details and a list of the available facilities and options,
   // consult:
   //
   //    http://php.net/manual/function.openlog.php
   //
   // The event types that should be listed for each log type should
   // be selected from the choices you have for $sl_log_events (note
   // that if you have an event type turned off in the $sl_log_events
   // setting, you will not have that event type logged no matter
   // what you put here).
   //
   //   %1 in the log format strings will be replaced with the event name
   //   %2 in the log format strings will be replaced with the user name
   //   %3 in the log format strings will be replaced with the domain name
   //   %4 in the log format strings will be replaced with the remote address value
   //   %5 in the log format strings will be replaced with the timestamp
   //   %6 in the log format strings will be replaced with the formatted date
   //   %7 in the log format strings will be replaced with any (event-specific) comments
   //
   $sl_logs = array(
      'SYSTEM:LOG_INFO:LOG_MAIL' => array(
         'LOGIN'            => "Successful webmail login: by %2 (%3) at %4 on %6: %7",
         'LOGOUT'           => "Webmail logout: by %2 (%3) at %4 on %6: %7",
         'TIMEOUT'          => "Webmail session timed out: by %2 (%3) at %4 on %6: %7",
         'OUTGOING_MAIL'    => "Message sent via webmail: by %2 (%3) at %4 on %6: %7",
         'MASS_MAILING'     => "Possible outgoing spam: by %2 (%3) at %4 on %6: %7",
         'LOGIN_ERROR'      => "Failed webmail login: by %2 (%3) at %4 on %6: %7",
         'ERROR'            => "Webmail error: by %2 (%3) at %4 on %6: %7",
//         'CAPTCHA'          => "Webmail CAPTCHA litmus: by %2 (%3) at %4 on %6: %7",
//         'RESTRICT_SENDERS' => "Failed recipient limit: by %2 (%3) at %4 on %6: %7",
      ),
      'SYSTEM:LOG_ALERT:LOG_AUTH' => array(
//         'MASS_MAILING'  =>  "Possible outgoing spam: by %2 (%3) at %4 on %6: %7",
      ),
      'FILE'   => array(
//         'LOGIN'         =>  "%6 [%1] %2 (%3) from %4: %7\n",
//         'LOGOUT'        =>  "%6 [%1] %2 (%3) from %4: %7\n",
//         'TIMEOUT'       =>  "%6 [%1] %2 (%3) from %4: %7\n",
//         'MASS_MAILING'  =>  "%6 [%1] %2 (%3) from %4: %7\n",
//         'LOGIN_ERROR'   =>  "%6 [%1] %2 (%3) from %4: %7\n",
//         'LOGIN_ERROR'   =>  "%6 [INVALID] %2 (%3) from %4: %7\n",
//         'ERROR'         =>  "%6 [%1] %2 (%3) from %4: %7\n",
      ),
      'SQL'    => array(
//         'LOGIN'         =>  'LOGIN',
//         'LOGOUT'        =>  'LOGOUT',
//         'TIMEOUT'       =>  'TIMEOUT',
//         'MASS_MAILING'  =>  'MASS_MAILING',
//         'LOGIN_ERROR'   =>  'INVALID',
//         'ERROR'         =>  'ERROR',
      ),
   );



   // This is a list of the types of events you would like
   // to have trigger an administrative alert message for.
   // It is also where you define the exact text of the
   // email messages for each event type.
   //
   // The supported event types are:
   //
   //    MASS_MAILING
   //    LOGIN_ERROR
   //    ERROR
   //
   //   %1 in the message format strings will be replaced with the event name
   //   %2 in the message format strings will be replaced with the user name
   //   %3 in the message format strings will be replaced with the domain name
   //   %4 in the message format strings will be replaced with the remote address value
   //   %5 in the message format strings will be replaced with the timestamp
   //   %6 in the message format strings will be replaced with the formatted date
   //   %7 in the message format strings will be replaced with any comments
   //
   $sl_send_alerts = array(
//      'MASS_MAILING'  =>  "Possible outgoing spam: by %2 (%3) at %4 on %6: %7",
//      'LOGIN_ERROR'   =>  "Failed webmail login: by %2 (%3) at %4 on %6: %7",
//      'ERROR'         =>  "Webmail error: by %2 (%3) at %4 on %6: %7",
   );



   // When monitoring outgoing mails for too many recipients,
   // this number of recipients must be defined before an 
   // alert will be triggered (see $sl_send_alerts).
   //
   $sl_mass_mail_limit = 20;



   // Configure the email addresses that alerts are sent to 
   // for each kind of alert (see $sl_send_alerts).
   //
   // Each array key is the name of an alert event, and 
   // the corresponding value is a comma-separated list of
   // destination email addresses for the alert.
   //
   $sl_alert_to  = array(
                            'MASS_MAILING' => 'postmaster',
                            'LOGIN_ERROR'  => 'postmaster',
                            'ERROR'        => 'postmaster',
   );
   $sl_alert_cc  = array(
   );
   $sl_alert_bcc = array(
   );



   // This is the address that is placed in the From
   // header of alert messages sent ala $sl_send_alerts
   //
   // You may include the special string "%1" (without
   // quotes) if you want the user's current domain to
   // be placed in the address.
   //
   $sl_alert_from = 'noreply@%1';



   // This is the subject line of the alert emails, where
   // "%1" will be replaced with the name of the event
   // and "%2" will be replaced with the username.
   //
   $sl_alert_subject_template = '[WEBMAIL ALERT] %1 - %2';



   // When sending administrative alert messages, you may
   // want to send them using different SMTP authentication
   // credentials or change any of the other Sendmail or
   // SMTP settings used normally in SquirrelMail's normal
   // use for sending mail.  If so, change the appropriate
   // setting here.  These values MUST be set to "NULL" to
   // indicate that the normal SquirrelMail configuration
   // values are to be used.
   //
   $sl_useSendmail = NULL;
   $sl_smtpServerAddress = NULL;
   $sl_smtpPort = NULL;
   $sl_sendmail_path = NULL;
   $sl_sendmail_args = NULL;
   $sl_pop_before_smtp = NULL;
   $sl_encode_header_key = NULL;
   $sl_smtp_auth_mech = NULL;
   $sl_smtp_sitewide_user = NULL;
   $sl_smtp_sitewide_pass = NULL;



   // When MASS_MAILING events occur, should the log message
   // include recipient addresses?  The Reply-To header?  The
   // From header?  The message subject?  The message body?
   //
   //    1 = yes
   //    0 = no
   //
   // PLEASE NOTE that some of these may be considered
   // invasive to your users' privacy and if you turn them on,
   // BE SURE your users understand that their messages may be
   // subject to review.  You are encouraged to have an
   // appropriate privacy policy and terms of service agreement
   // before you use these.
   //
   $sl_log_mass_mailing_show_recipients = 0;
   $sl_log_mass_mailing_show_from = 0;
   $sl_log_mass_mailing_show_reply_to = 0;
   $sl_log_mass_mailing_show_subject = 0;
   $sl_log_mass_mailing_show_message_body = 0;



   // When OUTGOING_MAIL events occur, should the log message
   // include recipient addresses?  The Reply-To header?  The
   // From header?  The message subject?  The message body?
   //
   //    1 = yes
   //    0 = no
   //
   // PLEASE NOTE that some of these may be considered
   // invasive to your users' privacy and if you turn them on,
   // BE SURE your users understand that their messages may be
   // subject to review.  You are encouraged to have an
   // appropriate privacy policy and terms of service agreement
   // before you use these.
   //
   $sl_log_outgoing_messages_show_recipients = 0;
   $sl_log_outgoing_messages_show_from = 0;
   $sl_log_outgoing_messages_show_reply_to = 0;
   $sl_log_outgoing_messages_show_subject = 0;
   $sl_log_outgoing_messages_show_message_body = 0;



   // The location of your log file when logging to file.  
   // Make sure the user your webserver runs as can write 
   // to this file.  Use the $data_dir variable if you 
   // want to place the log file in the SquirrelMail data
   // directory.  
   //
   // Only applicable when $sl_logs includes "file".
   //
   $sl_logfile = $data_dir . 'squirrelmail_access_log';



   // Specify what date format you want
   //
   // See the PHP manual for the date function for help
   // at http://www.php.net/manual/function.date.php
   //
   // examples:  
   //
   //    'm/d/y H:i:s'     ==  03/10/2001 05:16:08
   //    'F j, Y, g:i a'   ==  March 10, 2001, 5:16 am
   //    'D M j Y H:i:s T' ==  Sat Mar 10 2001 15:16:08 CDT
   //
   $sl_dateformat = 'm/d/Y H:i:s';



   // Log dates in GMT?  If you do not do this, dates will
   // be logged in whatever timezone each user is in (or
   // has set in their personal preferences)
   //
   //    1 = yes
   //    0 = no
   //
   $sl_use_GMT = 1;



   // Turn hostname lookups on or off
   // 
   //    1 = on
   //    0 = off
   //
   $sl_namelookups = 0;



   // If using SQL logging and the database becomes
   // unavailable, or your chosen system log facility
   // cannot be opened, should the plugin put up an error
   // message or should it ignore the error and continue?
   //
   //    1 = ignore errors
   //    0 = show error message and stop
   //
   $sl_fail_silently = 1;



   // Theoretically, any SQL database supported by Pear should be supported
   // here.  The DSN (data source name) must contain the information needed
   // to connect to your database backend. A MySQL example is included below.
   // For more details about DSN syntax and list of supported database types,
   // please see:
   //   http://pear.php.net/manual/en/package.database.db.intro-dsn.php
   //
   $sl_dsn = 'mysql://user:password@localhost/squirrelmail_logging';



   // This is the query used to insert a log entry into the database (only
   // used if you are using SQL type logging).  Adjust to fit your data
   // schema as needed
   //
   //   %1 in this query will be replaced with the event name
   //   %2 in this query will be replaced with the user name
   //   %3 in this query will be replaced with the domain name
   //   %4 in this query will be replaced with the remote address value
   //   %5 in this query will be replaced with the date
   //   %6 in this query will be replaced with any comments
   //
   $sl_insert_event_query = 'INSERT INTO user_activity (event, username, domain, remote_address, date, comments) VALUES ("%1", "%2", "%3", "%4", "%5", "%6")';



   // You can log to (and alert) only some of the domains
   // you may host by using one of the following settings.
   //
   // $skip_domains provides a list of domains that should
   // not be logged to for each event type; all other domains
   // will be logged.
   //
   // $only_log_domains specifies a list of domains that
   // should be logged for a given event type; all other
   // domains will NOT be logged.
   //
   // You can use these two settings in tandem, but they may
   // NOT have entries for the same event type (doesn't make
   // much sense, does it?).
   //
   // Both of these settings is a list keyed by the event
   // types defined in $sl_log_events, where values are lists
   // of the domains you want to/don't want to log.
   //
   // Note that for any of these settings to take effect,
   // you must already have turned on logging for the same
   // event types in $sl_log_events and $sl_logs.
   //
   $only_log_domains = array(
//         'LOGIN'         =>  array(),
//         'LOGOUT'        =>  array(),
//         'TIMEOUT'       =>  array(),
//         'OUTGOING_MAIL' =>  array(),
//         'MASS_MAILING'  =>  array(),
//         'LOGIN_ERROR'   =>  array(),
         'ERROR'         =>  array('example.com'),
   );
   $skip_domains = array(
         'LOGIN'         =>  array('example.com', 'example2.org'),
         'LOGOUT'        =>  array('example.com', 'example2.org'),
//         'TIMEOUT'       =>  array(),
//         'OUTGOING_MAIL' =>  array(),
//         'MASS_MAILING'  =>  array(),
//         'LOGIN_ERROR'   =>  array(),
//         'ERROR'         =>  array(),
   );



