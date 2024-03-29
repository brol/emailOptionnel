<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of Email Optionnel, a plugin for Dotclear.
#
# Copyright (c) 2007-2020 Oleksandr Syenchuk, Pierre Van Glabeke, Gvx
#
# Licensed under the GPL version 2.0 license.
# A copy is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------
if (!defined('DC_RC_PATH')) {return;}

class emailOptionnelBehaviors {

	const DEFAULT_EMAIL	= 'invalid@invalid.fr';
	const PLUGIN_NAME	= 'emailoptionnel';

	public static function adminBlogPreferencesForm($core) {
		$core->blog->settings->addNamespace(self::PLUGIN_NAME);
		$emailOptionnel = $core->blog->settings->emailoptionnel->enabled ? true : false;
		echo '<div class="fieldset"><h4 id="emailOptionnelParam">'.__('Optional e-mail address').'</h4>'."\n".
			'<p><label class="classic" for="emailOptionnel">'.form::checkbox(self::PLUGIN_NAME,'1',$emailOptionnel)."\n".
			__('Make e-mail address optional in comments').'</label></p>'."\n".
			'</div>'."\n";
	}

	public static function adminBeforeBlogSettingsUpdate($blog_settings) {
		$emailOptionnel = empty($_POST[self::PLUGIN_NAME]) ? false : true;
		$blog_settings->addNamespace(self::PLUGIN_NAME);
		$blog_settings->emailoptionnel->put('enabled', $emailOptionnel, 'boolean', __('Make e-mail address optional in comments'));
	}

	public static function publicPrepend($core) {
		$core->blog->settings->addNamespace(self::PLUGIN_NAME);
		if (!isset($_POST['c_content']) || !empty($_POST['preview']) || !empty($_POST['c_mail']) || !$core->blog->settings->emailoptionnel->enabled) {
			return;
		}
		$_POST['c_mail'] = self::DEFAULT_EMAIL;
	}

	public static function publicBeforeCommentCreate($cur) {
		global $core;
		$core->blog->settings->addNamespace(self::PLUGIN_NAME);
		$emailOptionnel = $core->blog->settings->emailoptionnel->enabled ? true : false;
		if ($emailOptionnel && $cur->comment_email == self::DEFAULT_EMAIL) {
			$_ctx = &$GLOBALS['_ctx'];
			# désactive l'affichage du mail dans le template
			$_ctx->comment_preview['mail'] = '';
			# n'enregistre pas de mail dans la BDD
			$cur->comment_email = '';
			# n'enregistre pas le mail dans le cookie
			if (empty($_POST['c_remember'])) { return; }
			if (!empty($_COOKIE['comment_info'])) {
				$cookie_info = explode("\n",$_COOKIE['comment_info']);
				if (count($cookie_info) == 3) { return; }
			}
			$c_cookie = array('name' => $cur->comment_author, 'mail' => $cur->comment_email, 'site' => $cur->comment_site);
			$c_cookie = serialize($c_cookie);
			setcookie('comment_info',$c_cookie,strtotime('+3 month'),'/');
		}
	}

	public static function publicHeadContent($core, $_ctx) {
		$core->blog->settings->addNamespace(self::PLUGIN_NAME);
		$emailOptionnel = $core->blog->settings->emailoptionnel->enabled ? true : false;
		if ($emailOptionnel) {
			$plugin_id = basename(dirname(__FILE__));
			$file = $plugin_id.'/public.js';
			$version = $core->plugins->moduleInfo($plugin_id, 'version');
			echo dcUtils::jsLoad($core->blog->getPF($file), $version);
		}
	}

}
