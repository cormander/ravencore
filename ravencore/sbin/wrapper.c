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

// path to the location of the scripts we can execute
#define RC_ROOT_BIN "/usr/local/ravencore/bin"

// global variable, the previous character passed thru valid_input_char
char prev;

// a function to test the input given to the wrapper, to prevent exploits running commands we shouldn't
// be running at all
void valid_input_char(char c) {

  // unsafe characters:
  if(c == '|' || /* pipe, or */
     c == '&' || /* background, and */
     c == ';' || /* command seperator */
     c == '`' || /* execute */
     ( prev == '$' && c == '(' ) || /* execute */
     ( prev == '.' && c == '.' ) || /* going below the current directory */
     c == '>' || c == '<' || /* output redirect */
     c < '\040' || c > '\176'  /* keyboard signals */
     ) {
    
    printf("Unsafe command\n");
    exit(1);
    
  }

  // set the global variable to what we just checked, so we can use it the next time we run this function
  prev = c;
  
}

int main(int argc, char *const argv[])
{

  int i, e, len, nu;
  char poss_cmd[1024] = RC_ROOT_BIN;

  // we need atleast one argument to run
  if(argc < 1) {

    printf("Usage: wrapper <program> [options]\n");
    exit(1);

  }

  // make sure that the command length isn't too long
  if(strlen(argv[1]) > 25) {

    printf("Unsafe command\n");
    exit(1);

  }

  // figure out when poss_cmd is terminated 
  for(i = 0; poss_cmd[i] != '\0'; i++);
  
  // replace the null character with a slash
  poss_cmd[i] = '/';

  // insert the argument at the end of the path
  for(len = 0; len < strlen(argv[1]); len++) poss_cmd[i+1+len] = argv[1][len];

  // put null characters to end this string
  for(nu = i+1+len; nu < 1024; nu++) poss_cmd[nu] = '\0';
  
  // walk down our array of arguments
  for( i = 0; i < argc; i++ ) {

    // walk down this argument. strings are terminated with a null character
    // we start from one rather than zero, because zero is the call to the wrapper, which is ../sbin/wrapper
    // and .. is considered unsafe ( but isn't in this case )
    for ( e = 1; argv[i][e] != '\0'; e++ ) {
      
      // validate this as a safe character
      valid_input_char(argv[i][e]);
      
    }

  }

  // become root. we need the suid bit set for this to work
  setgid(0);
  setuid(0);

  // execute this command with the given arguments and a NULL enviroment. print any error in running
  if (execve(poss_cmd, argv+1, NULL) < 0) printf("%s: %s\n", argv[1], strerror(errno));
  
  // if we get here, we are exiting in error
  exit(1);
  
}
