<?php
/*  Copyright 2006 Vincent Prat  (email : vpratfr@yahoo.fr)

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
?>

<?php 
	if (!current_user_can(MAILUSERS_NOTIFY_USERS_CAP)) {
		wp_die(__("You are not allowed to notify users about posts and pages.", MAILUSERS_I18N_DOMAIN));
	}

	// Send the email if it has been requested
	if($_POST['send']=="true") {
	    get_currentuserinfo();
	    $from_name = $user_identity;
	    $from_address = $user_email;
	    $mail_format = mailusers_get_default_mail_format();

	    // Analyse form input, check for blank fields
	    if ( isset( $_POST['post_id'] ) ) {
		    $post_id = $_POST['post_id'];
	    } else {
		$err_msg = $err_msg . __('You must select a post or page to notify users about.', MAILUSERS_I18N_DOMAIN) . '<br/>';
	    }

	    if ( !isset( $_POST['send_roles'] ) && !isset( $_POST['send_users'] ) ) {
		    $err_msg = $err_msg . __('You must select at least a recipient.', MAILUSERS_I18N_DOMAIN) . '<br/>';
	    } else {
		    $send_roles = isset($_POST['send_roles']) ? $_POST['send_roles'] : array();
		    $send_users = isset($_POST['send_users']) ? $_POST['send_users'] : array();
	    }

	    if ( !isset( $_POST['subject'] ) || trim($_POST['subject'])=='' ) {
		    $err_msg = $err_msg . __('You must enter a subject.', MAILUSERS_I18N_DOMAIN) . '<br/>';
	    } else {
		    $original_subject = $_POST['subject'];
	    }

	    if ( !isset( $_POST['mailContent'] ) || trim($_POST['mailContent'])=='' ) {
		    $err_msg = $err_msg . __('You must enter a content.', MAILUSERS_I18N_DOMAIN) . '<br/>';
	    } else {
		    $original_mail_content = $_POST['mailContent'];
	    }

	    // If no error, we send the mail
	    if ( $err_msg=='' ) {
			// Fetch users
			// --
			$users_from_roles = mailusers_get_recipients_from_roles($send_roles, $user_ID, MAILUSERS_ACCEPT_NOTIFICATION_USER_META);
			$users_from_ids = mailusers_get_recipients_from_ids($send_users, $user_ID, MAILUSERS_ACCEPT_NOTIFICATION_USER_META);
			$recipients = array_merge($users_from_roles, $users_from_ids);

			if (empty($recipients)) {
				$err_msg = $err_msg . _e('No recipients were found.', MAILUSERS_I18N_DOMAIN) . '<br/>';
			} else {
				$num_sent = mailusers_send_mail($recipients, format_to_post($original_subject), $original_mail_content, $mail_format, $from_name, $from_address);
				if (false === $num_sent) {
					$err_msg = $err_msg . _e('There was a problem trying to send email to users.', MAILUSERS_I18N_DOMAIN) . '<br/>';
				} else if (0 === $num_sent) {
					$err_msg = $err_msg .  _e('No email has been sent to other users. This may be because no valid email addresses were found.', MAILUSERS_I18N_DOMAIN) . '<br/>';
				} else if ($num_sent > 0 && $num_sent == count($recipients)){
		?>
			    <div class="wrap">
				<div class="updated">
					<p><?php echo sprintf(__("Notification sent to %s user(s).", MAILUSERS_I18N_DOMAIN), $num_sent); ?></p>
				</div>
			    </div>
		<?php
				} else if ($num_sent > count($recipients)) {
					$err_msg = $err_msg .  _e('WARNING: More email has been sent than the number of recipients found.', MAILUSERS_I18N_DOMAIN) . '<br/>';
				} else {
					?>
			    <div class="wrap">
				<div class="updated">
				    <p class="updated">Email has been sent to <?php echo $num_sent; ?> users, but <?php echo count($recipients);?> recipients were originally found. Perhaps some users don't have valid email addresses?
				    </p>
				</div>
			    </div>
		<?php
				}
			}
	    }
	}
		
	if (!isset($send_roles)) {
		$send_roles = array();
	}	
	if (!isset($send_users)) {
		$send_users = array();
	}

	$mail_format = mailusers_get_default_mail_format()=='html' ? 
						__('HTML', MAILUSERS_I18N_DOMAIN) 
					:	__('Plain text', MAILUSERS_I18N_DOMAIN);
					
	$subject = mailusers_get_default_subject();
	$mail_content = mailusers_get_default_body();

	// Replace the template variables concerning the blog details
	// --
	$subject = mailusers_replace_blog_templates($subject);
	$mail_content = mailusers_replace_blog_templates($mail_content);
		
	// Replace the template variables concerning the sender details
	// --	
	get_currentuserinfo();
	$from_name = $user_identity;
	$from_address = $user_email;
	$subject = mailusers_replace_sender_templates($subject, $from_name);
	$mail_content = mailusers_replace_sender_templates($mail_content, $from_name);
		
	// Replace the template variables concerning the post details
	// --
	if ( isset($_GET['post_id']) ) {
		$post_id = $_GET['post_id'];
	} elseif ( isset($_POST['post_id']) ) {
		$post_id = $_POST['post_id'];
	}
	$post = get_post( $post_id );
	$post_title = $post->post_title;
	$post_url = get_permalink( $post_id );
	$post_content = $post->post_content;
	$post_excerpt = ( mailusers_get_excerpt_alt() == 'full' && trim($post->post_excerpt) == '') ? $post->post_content : $post->post_excerpt;
	
	$subject = mailusers_replace_post_templates($subject, $post_title, $post_excerpt, $post_url);
	$mail_content = mailusers_replace_post_templates($mail_content, $post_title, $post_excerpt, $post_url);
?>

<div class="wrap">
	<h2><?php _e('Notify users of a post or page', MAILUSERS_I18N_DOMAIN); ?></h2>
		
	<?php 	if (isset($err_msg) && $err_msg!='') { ?>
			<p class="error"><?php echo $err_msg; ?></p>
			<p><?php _e('Please correct the errors displayed above and try again.', MAILUSERS_I18N_DOMAIN); ?></p>
	<?php	}
	if (!isset($post_id)) { ?>
	<form name="SetPost" action="admin.php?page=email-users/email_users_notify_form.php" method="post">
	    <p>Please select the post that you wish to notify users about.</p>
	    <select name="post_id">
		<?php
		 $lastposts = get_posts('numberposts=0');
		 foreach($lastposts as $post) :
		    setup_postdata($post);
		 ?>
		<option value="<?php the_ID(); ?>"><?php the_title(); ?></option>
		 <?php endforeach; ?>
	    </select>

	    <p class="submit">
		    <input type="submit" name="Submit" value="<?php _e('Select post', MAILUSERS_I18N_DOMAIN); ?> &raquo;" />
	    </p>
	</form>
	<?php } else { ?>

	<form name="SendEmail" action="admin.php?page=email-users/email_users_notify_form.php" method="post">
		<input type="hidden" name="send" value="true" />
		<input type="hidden" name="post_id" value="<?php echo $post_id; ?>" />
		<input type="hidden" name="mail_format" value="<?php echo mailusers_get_default_mail_format(); ?>" />
		<input type="hidden" name="fromName" value="<?php echo $from_name;?>" />
		<input type="hidden" name="fromAddress" value="<?php echo $from_address;?>" />
		<input type="hidden" name="subject" value="<?php echo format_to_edit($subject);?>" />
		
		<table class="form-table" width="100%" cellspacing="2" cellpadding="5">
		<tr>
			<th scope="row" valign="top"></th>
			<td><strong><?php _e('Mail will be sent as:', MAILUSERS_I18N_DOMAIN); ?> <?php echo $mail_format; ?></strong></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="fromName"><?php _e('Sender', MAILUSERS_I18N_DOMAIN); ?></label></th>
			<td><?php echo $from_name;?> &lt;<?php echo $from_address;?>&gt;</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="send_roles"><?php _e('Recipients', MAILUSERS_I18N_DOMAIN); ?>
			<br/><br/>
			<small><?php _e('Use CTRL key to select/deselect multiple items', MAILUSERS_I18N_DOMAIN); ?></small>
			<br/><br/>
			<small><?php _e('The users that did not agree to recieve notifications do not appear here.', MAILUSERS_I18N_DOMAIN); ?></small></label></th>
			<td>
				<select name="send_roles[]" multiple="yes" size="8" style="width: 250px; height: 250px;">
				<?php 
					$roles = mailusers_get_roles($user_ID, MAILUSERS_ACCEPT_NOTIFICATION_USER_META);
					foreach ($roles as $key => $value) { 
				?>
					<option value="<?php echo $key; ?>"	<?php 
						echo (in_array($key, $send_roles) ? ' selected="yes"' : '');?>>
						<?php echo __('Role', MAILUSERS_I18N_DOMAIN) . ' - ' . $value; ?>
					</option>
				<?php 
					}
				?>
				</select> 
				<select name="send_users[]" multiple="yes" size="8" style="width: 400px; height: 250px;">
				<?php 
					$users = mailusers_get_users($user_ID, MAILUSERS_ACCEPT_NOTIFICATION_USER_META);
					foreach ($users as $user) { 
				?>
					<option value="<?php echo $user->id; ?>" <?php 
						echo (in_array($user->id, $send_users) ? ' selected="yes"' : '');?>>
						<?php echo __('User', MAILUSERS_I18N_DOMAIN) . ' - ' . $user->display_name; ?>
					</option>
				<?php 
					}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="subject"><?php _e('Subject', MAILUSERS_I18N_DOMAIN); ?></label></th>
			<td><?php echo mailusers_get_default_mail_format()=='html' ? $subject : '<pre>' . format_to_edit($subject) . '</pre>';?></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="mailContent"><?php _e('Message', MAILUSERS_I18N_DOMAIN); ?></label></th>
			<td><?php echo mailusers_get_default_mail_format()=='html' ? $mail_content : '<pre>' . wordwrap(strip_tags($mail_content), 80, "\n") . '</pre>';?>
				<textarea rows="10" cols="80" name="mailContent" id="mailContent" style="width: 647px; display: none;" readonly="yes"><?php echo $mail_content;?></textarea>
			</td>
		</tr>
		</table>
		
		<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Send Email', MAILUSERS_I18N_DOMAIN); ?> &raquo;" />
		</p>	
	</form>
	<?php } ?>
</div>
