<?php


/**
  * SquirrelMail Sent Confirmation Plugin
  * Copyright (C) 2004 Paul Lesneiwski <pdontthink@angrynerds.com>
  * This program is licensed under GPL. See COPYING for details
  *
  */


   global $sent_conf_message_style, $sent_conf_include_recip_addr,
          $sent_conf_show_only_first_recip_addr, $sent_conf_allow_user_override,
          $emailAddressDelimiter, $sent_logo, $sent_logo_width, $sent_logo_height,
          $sent_conf_show_headers, $sent_conf_enable_orig_msg_options;



   // setting this to 1 gives the user the ability to change 
   // any of the settings below for their own account
   //
   $sent_conf_allow_user_override = 1;



   // message confirmation style:
   //
   //   'off' :    No message shown
   //       1 :    "Message Sent" (above mailbox listing)
   //       2 :    "Your message has been sent"  (centered) 
   //              (above mailbox listing)
   //       3 :    Allow user to add addresses to address
   //              book (with optional image) (separate, 
   //              intermediary screen)
   //       4 :    "Message Sent To:"  (with user list and
   //              optional image) (separate, intermediary screen)
   //
   $sent_conf_message_style = 'off';
   //$sent_conf_message_style = 3;



   // this allows you to indicate the email address to which
   // the message was sent in the confirmation message (0 = off,
   // 1 = on)
   //
   $sent_conf_include_recip_addr = 1;



   // when using the "$sent_conf_include_recip_addr" setting
   // above, this setting will determine if all of the "To:"
   // addresses will be shown or not (0 = off, 1 = on)
   //
   $sent_conf_show_only_first_recip_addr = 1;



   // when using the "$sent_conf_include_recip_addr" setting
   // above, this setting will also include any addresses in
   // the "Cc:" field (0 = off, 1 = on)
   //
   $sent_conf_include_cc = 0;



   // when using the "$sent_conf_include_recip_addr" setting
   // above, this setting will also include any addresses in
   // the "Bcc:" field (0 = off, 1 = on)
   //
   $sent_conf_include_bcc = 0;



   // this allows you to place a logo above the confirmation
   // message when the confirmation style is 3 or 4.  No logo
   // will be shown if $sent_logo is empty/blank.  Width
   // and height values are optional
   //
   $sent_logo = '';
   //$sent_logo = '../images/sm_logo.png';
   $sent_logo_width = '';
   $sent_logo_height = '';



   // set this to 1 in order to include "To:", "Cc:", and "Bcc:"
   // in the list of recipients (so you know which address was 
   // in which header field)
   //
   $sent_conf_show_headers = 0;



   // this allows users to delete, move, or return to the 
   // original message after sending a reply
   //
   $sent_conf_enable_orig_msg_options = 1;



   // the delimiter between account name and domain used in
   // email addresses on your system... it is rarely anything
   // except '@'
   //
   $emailAddressDelimiter = '@';



?>
