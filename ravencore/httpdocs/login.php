<?php

//We shouldn't call this script directly from the web

if($_SERVER[PHP_SELF] == "/login.php") goto("users.php");

//We should never be logout.php

if($_SERVER[PHP_SELF] == "/logout.php") goto("users.php");

//If we're in a subdirectory, send us back to the web root
if(preg_match('/^\/.*\//',$_SERVER[PHP_SELF])) goto("/users.php");

if($_GET[form_action]) $form_action = $_GET[form_action];

nav_top();

?>

<form <?php if($form_action) print 'action="' . $form_action . '" '; ?>method="POST" name="f">

<div align="center">
<?php

if($login_error) print "<br><b><font color=red>" . $login_error . "</font></b>";

?>
</div>
<div align=center>
      <table>
<tr><th colspan="2">Please Login</th></tr>
<tr>
<td>Username:</td>
<td><input name="user" size="15" value="<?php print $_POST[user]; ?>"></td>
</tr><tr>
<td>Password:</td>
<td><input name="pass" TYPE="PASSWORD" size="15" value=""></td>
</tr>
<!-- language support is not done
<tr>
<td>Language:</font></td>
<td><select name="lang"><option value="default">Default</option>
</select>
</td>
</tr>
-->
<tr>
<td colspan="2" align="right">
<input type="hidden" name="action" value="login">
<input type="submit" name="submit" value="Login">
</td>
</tr>
</table>

</form>

<script type="text/javascript">

<!--

f.user.focus();

-->

</script>

<?php

nav_bottom();

?>