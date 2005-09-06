/*
                 RavenCore Hosting Control Panel
               Copyright (C) 2005  Corey Henderson

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

#include <errno.h>
#include <stdio.h>
#include <stdlib.h>

#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>

// path to the location of the scripts we can execute
#define RC_ROOT_BIN "/usr/local/ravencore/bin"
// the size of the poss_cmd array
#define CMD_STR_LIM 1024
// the gid of the rcadmin group
#define RCADMIN_GID 147

// global variable, the previous character passed thru the valid_input_char function
char prev;

// a function to say why we're exting, and exit. later we'll have this disable the wrapper and alert
// the admin
void die_unsafe_command() {

  // say we're unsafe
  printf("Unsafe command\n");
  // exit non-zero
  exit(1);

}

// a function to test the input given to the wrapper, to prevent exploits from running commands
// we shouldn't be running at all anyway
void valid_input_char(char c) {

  // unsafe characters:
  if(c == '|' || /* pipe, or */
     c == '&' || /* background, and */
     c == ';' || /* command seperator */
     c == '`' || /* execute */
     ( prev == '$' && c == '(' ) || /* execute */
     ( prev == '.' && c == '.' ) || /* going below the current directory */
     c == '>' || c == '<' || /* output redirect */
     c < '\040' || c > '\176'  /* other ascii characters */
     ) {
    
    // we get here if we caught an unsafe character in argv
    die_unsafe_command();
    
  }

  // set the global variable to what we just checked, so we can use it the next time we run this function
  prev = c;
  
}

// start our process and recieve in our command line arguments
int main(int argc, char *const argv[])
{

  int i, e, len, nu;
  // initilize our command to run with the path to the directory of the allowable scripts
  char poss_cmd[CMD_STR_LIM] = RC_ROOT_BIN;
  struct stat f_info;

  // we need atleast one argument to run
  if(argc < 1) {

    printf("Usage: wrapper <program> [options]\n");
    exit(1);

  }

  // make sure that the command length isn't too long
  if(strlen(argv[1]) > 25) die_unsafe_command();

  // figure out when poss_cmd is terminated, value is stored in "i"
  for(i = 0; poss_cmd[i] != '\0'; i++);
  
  // replace the null character with a slash
  poss_cmd[i] = '/';

  // walk down the length of argv[1], our command to run, and insert its content to the end of the path
  for(len = 0; len < strlen(argv[1]); len++) poss_cmd[i+1+len] = argv[1][len];

  // fill the rest of this array with null characters. we probably only need just one, but what the hell
  for(nu = i+1+len; nu < CMD_STR_LIM; nu++) poss_cmd[nu] = '\0';
  
  // walk down our array of arguments
  for( i = 0; i < argc; i++ ) {

    // walk down this argument until we find a null character ( string termination )
    // we start from one rather than zero, because zero on argv is the actuall call to the wrapper,
    // which normally would be "../sbin/wrapper" ( decending from the httpdocs directory ), and
    // and since ".." is considered unsafe, but isn't in this case, we don't check argv[0]
    for ( e = 1; argv[i][e] != '\0'; e++ ) {
      
      // validate this as a safe character
      valid_input_char(argv[i][e]);
      
    }

  }

  // get infomation about this command's file
  if( lstat(poss_cmd, &f_info) != -1 ) {

    // the target MUST be owned by root and in the rcadmin group, otherwise it is DEFINATLY something we
    // should not run
    if(f_info.st_uid != 0 || f_info.st_gid != RCADMIN_GID) {
      
      die_unsafe_command();
      
    }
  
  } else {

    // we get here if the target file doesn't exist. print an error message and exit
    printf("%s: %s\n", argv[1], strerror(errno));
    
    exit(errno);

  }

  // become root. we need the suid bit set for this to work
  setgid(0);
  setuid(0);

  // execute this command with the given arguments and a NULL enviroment
  execve(poss_cmd, argv+1, NULL);

  // we will only ever get here if we are in error, such as the file isn't executable, or something
  printf("%s: %s\n", argv[1], strerror(errno));

  exit(errno);
  
}
