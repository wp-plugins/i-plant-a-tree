<?php
/*
Plugin Name: I Plant A Tree
Text Domain: i-plant-a-tree
Plugin URI: https://lightframefx.de
Description: This plugin shows the count of planted trees via *I Plant A Tree*, as well as saved CO2.
Author: Micha
Version: 1.1
Author URI: https://lightframefx.de
URI: https://lightframefx.de
Tags: ipat,widget,i plant a tree
Requires at least: 4.2.2
Tested up to: 4.2.3
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0
*/

/*
Copyright (C) 2015 Michael Roth

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License Version 3 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the	GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function ipat_saveLog($logText) {
	$logText=date('Ymd H:i:s')." ".$logText."\r\n";
	$handle = fopen ( plugin_dir_path( __FILE__ ) . "ipat.log", "a" );
	fwrite ($handle,$logText);
	fclose ($handle);
}

if (!class_exists('ipat_widget')) {
    class ipat_widget {
        var $settings;
		var $log;

		function ipat_widget() {
			$this->getOptions();
			if (function_exists('load_plugin_textdomain')) load_plugin_textdomain('i-plant-a-tree', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages', dirname(plugin_basename( __FILE__ )).'/languages');

			// Example Shortcode [date] add with: add_shortcode('date', array(&$this,'show_date'));
			add_shortcode('ipat_widget', array(&$this,'ipat_widgetShortcode'));

			if(is_admin()) {
				add_action('admin_menu', array(&$this, 'add_menupages'));
			}
			// Example Filter add_filter( 'user_can_richedit', array(&$this, 'disable_wysiwyg') );
		}
		function getOptions() {
			$this->settings = get_option('ipat_widget');
			$this->log=json_encode($this->settings);
			//ipat_saveLog("getOptions: ".$this->log);
		}
		function activate() {
			// Create Tables if needed or generate whatever on installation
			$basicSettings = get_option('ipat_widget');
			$log=json_encode($basicSettings);
			ipat_saveLog("Aktivierung startet: ".$log);
			if (!($basicSettings)) {
				// Plugin wurde noch nie aktiviert
				$basicSettings['userID']=0;
				$basicSettings['lastUpdate']=0;
				$basicSettings['remoteHost']=get_bloginfo(wpurl);
				$basicSettings['widgetType']=2;
				$basicSettings['lang']='de';
				$basicSettings['refreshInterval']=60;
				$basicSettings['treeCount']=0;
				$basicSettings['co2Saving']=0;
				$basicSettings['widgetControlTitle']='I Plant A Tree';
				$basicSettings['widgetControlAlign']='center';
				$basicSettings['widgetControlTextBefore']='Dieses Blog ist CO<sub>2</sub>-neutral.';
				$basicSettings['widgetControlTextAfter']='';
				//$basicSettings['token']=wp_generate_password(8,false);
				update_option('ipat_widget', $basicSettings);
				ipat_saveLog("Plugin wurde zum ersten Mal aktiviert: ", $basicSettings);
			} else {
				// Plugin wurde früher schon mal aktiviert
				if (!array_key_exists('remoteHost',$basicSettings)) {
					$basicSettings['remoteHost']=get_bloginfo(wpurl);
				}
				if (!array_key_exists('widgetType',$basicSettings)) {
					$basicSettings['widgetType']=2;
				}
				if (!array_key_exists('userID',$basicSettings)) {
					$basicSettings['userID']=0;
				}
				if (!array_key_exists('lang',$basicSettings)) {
					$basicSettings['lang']='de';
				}
				if (!array_key_exists('lastUpdate',$basicSettings)) {
					$basicSettings['lastUpdate']=0;
				}
				if (!array_key_exists('refreshInterval',$basicSettings)) {
					$basicSettings['refreshInterval']=60;
				}
				if (!array_key_exists('treeCount',$basicSettings)) {
					$basicSettings['treeCount']=0;
				}
				if (!array_key_exists('co2Saving',$basicSettings)) {
					$basicSettings['co2Saving']=0;
				}
				if (!array_key_exists('widgetControlTitle',$basicSettings)) {
					$basicSettings['widgetControlTitle']='I Plant A Tree';
				}
				if (!array_key_exists('widgetControlAlign',$basicSettings)) {
					$basicSettings['widgetControlAlign']='center';
				}
				if (!array_key_exists('widgetControlTextBefore',$basicSettings)) {
					$basicSettings['widgetControlTextBefore']='Dieses Blog ist CO<sub>2</sub>-neutral.';
				}
				if (!array_key_exists('widgetControlTextAfter',$basicSettings)) {
					$basicSettings['widgetControlTextAfter']='';
				}
				// Der Token wird auch bei erneuter Aktivierung neu generiert
				//$basicSettings['token']=wp_generate_password(8,false);
				update_option('ipat_widget', $basicSettings);
				ipat_saveLog("Plugin wurde früher schon mal aktiviert: ", $basicSettings);
			}
			$basicSettings = get_option('ipat_widget');
			$log=json_encode($basicSettings);
			ipat_saveLog("Aktivierung beendet: ".$log);
		}
		function uninstall() {
			// Delete Tables or settings if needed be deinstallation
		}
		function add_menupages() {
			// For Option Pages, see WordPress function: add_options_page()
			// For own Menu Pages, see WordPress function: add_menu_page() and add_submenu_page()
		}
		function ipat_widgetShortcode($atts) {
			$atts = shortcode_atts( array(
				'align' => '',
				'class' => ''
			), $atts, 'ipat_widget' );
			switch ($atts['align']) {
				case 'right': $ipat_extraStyle='ipat_alignRight '.$atts['class']; break;
				case 'left': $ipat_extraStyle='ipat_alignLeft '.$atts['class']; break;
				case 'center': $ipat_extraStyle='ipat_alignCenter '.$atts['class']; break;
				default: $ipat_extraStyle=$atts['class']; break;
			}
			ipat_updateIfNecessary();
			$ipat_settings = get_option('ipat_widget');
			$widgetHTML='';
			switch ($ipat_settings['widgetType']) {
				case 2: $widgetImageSize='220x90'; break;
				case 3: $widgetImageSize='100x150'; break;
				case 4: $widgetImageSize='180x80'; break;
				default: $widgetImageSize='120x190'; break;
			}
			$widgetHTML.='<span class="ipat_widget ipat_widgetType'.$ipat_settings['widgetType'].' '.$ipat_extraStyle.'">';
			$widgetHTML.='<a href="https://www.iplantatree.org/user/'.$ipat_settings['userID'].'" target="_blank">';
			$widgetHTML.='<img src="'.plugins_url().'/'.dirname(plugin_basename(__FILE__)).'/assets/image/widget-'.$widgetImageSize.'_'. $ipat_settings['lang'].'.png"/>';
			$widgetHTML.='<span class="ipat_widgetTreeCount">'.$ipat_settings['treeCount'].'</span>';
			$widgetHTML.='<span class="ipat_widgetCo2Saving">'.number_format(round($ipat_settings['co2Saving'],6),6).'</span>';
			$widgetHTML.="</a>";
			$widgetHTML.="</span>";
			return $widgetHTML;
		}
    }
    add_action('init', 'ipat_widget1');

    function ipat_widget1() {
        global $ipat_widget;
        $ipat_widget = new ipat_widget();
    }
}

add_action('wp_print_styles', 'ipat_addStylesheet');
add_action('admin_head', 'ipat_addStylesheet');
function ipat_addStylesheet() {
	$myStyleFile = WP_PLUGIN_URL .'/'.dirname(plugin_basename(__FILE__)).'/assets/css/ipat_style.css';
	wp_register_style('ipat_styleSheet', $myStyleFile);
	wp_enqueue_style( 'ipat_styleSheet');
}

if (function_exists('register_activation_hook')) { register_activation_hook(__FILE__, array('ipat_widget', 'activate')); }
if (function_exists('register_uninstall_hook')) { register_uninstall_hook(__FILE__, array('ipat_widget', 'uninstall')); }


add_action( 'admin_menu', 'ipat_plugin_menu' );
function ipat_plugin_menu() {
	add_options_page( 'I plant a tree Options', 'I plant a tree', 'manage_options', 'i_plant_a_tree', 'ipat_plugin_options' );
}

function ipat_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$ipat_settings = get_option('ipat_widget');
	$ipat_remoteUpdateSuccessful=true;
	if (isset($_POST['ipat_submit'])) {
		// gespeicherte Änderungen übernehmen
		$ipat_settings['userID']=intval($_POST['ipat_userID'],10);
		$ipat_settings['remoteHost']=get_bloginfo('wpurl');
		$ipat_settings['widgetType']=intval($_POST['ipat_widgetType'],10);
		if ($ipat_settings['widgetType']==0) $ipat_settings['widgetType']=1;
		$ipat_language=sanitize_text_field($_POST['ipat_language']);
		$ipat_supportedLanguages=array('de','en','it');
		if (!in_array($ipat_language,$ipat_supportedLanguages)) $ipat_language='de';
		$ipat_settings['lang']=$ipat_language;
		$ipat_settings['refreshInterval']=intval($_POST['ipat_refreshInterval'],10);
		if ($ipat_settings['refreshInterval']==0 || $ipat_settings['refreshInterval']==1) $ipat_settings['refreshInterval']=1440;
		//echo "<pre>";
		//echo print_r($ipat_settings);
		//echo "</pre>";
		update_option('ipat_widget', $ipat_settings);
		$ipat_remoteUpdateSuccessful=ipat_updateIfNecessary(true);
		$ipat_settings = get_option('ipat_widget');
	}
	$ipat_userID=$ipat_settings['userID'];
	?>
	<div class="wrap">
		<h2><?php _e('Settings','i-plant-a-tree');?> › I Plant A Tree</h2>
		<form novalidate="novalidate" method="post">
			<table class="form-table ipatSettings">
				<tr>
					<th scope="row">
						<label for="ipat_userID">IPAT <?php _e('user-ID','i-plant-a-tree');?></label>
					</th>
					<td colspan="4">
						<input id="ipat_userID" class="regular-text" type="text" value="<?php echo $ipat_userID; ?>" name="ipat_userID">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ipat_widgetType"><?php _e('language','i-plant-a-tree');?></label>
					</th>
					<td colspan="4">
						<input type="radio" <?php if ($ipat_settings['lang']=="de") echo 'checked="checked"';?> value="de" name="ipat_language"><span class="ipat_language"><?php _e('german','i-plant-a-tree');?></span>
						<input type="radio" <?php if ($ipat_settings['lang']=="en") echo 'checked="checked"';?> value="en" name="ipat_language"><span class="ipat_language"><?php _e('english','i-plant-a-tree');?></span>
						<input type="radio" <?php if ($ipat_settings['lang']=="it") echo 'checked="checked"';?> value="it" name="ipat_language"><span class="ipat_language"><?php _e('italian','i-plant-a-tree');?></span>
					</td>
				</tr>
				<tr class="ipat_widgetType">
					<th scope="row">
						<label for="ipat_widgetType"><?php _e('widget type','i-plant-a-tree');?></label>
					</th>
					<td>
						<input type="radio" <?php if ($ipat_settings['widgetType']==1) echo 'checked="checked"';?> value="1" name="ipat_widgetType"><span class="m-l">120 x 190 px</span><br/>
						<?php
							echo '<div class="ipat_widget ipat_widgetType1">';
							echo '<img src="'.plugins_url().'/'.dirname(plugin_basename(__FILE__)).'/assets/image/widget-120x190_'. $ipat_settings['lang'].'.png"/>';
							echo '<div class="ipat_widgetTreeCount">'.$ipat_settings['treeCount'].'</div>';
							echo '<div class="ipat_widgetCo2Saving">'.number_format(round($ipat_settings['co2Saving'],6),6).'</div>';
							echo '</div>';
						?>
					</td>
					<td>
						<input type="radio" <?php if ($ipat_settings['widgetType']==2) echo 'checked="checked"';?> value="2" name="ipat_widgetType"><span class="m-l">220 x 90 px</span><br/>
						<?php
							echo '<div class="ipat_widget ipat_widgetType2">';
							echo '<img src="'.plugins_url().'/'.dirname(plugin_basename(__FILE__)).'/assets/image/widget-220x90_'. $ipat_settings['lang'].'.png"/>';
							echo '<div class="ipat_widgetTreeCount">'.$ipat_settings['treeCount'].'</div>';
							echo '<div class="ipat_widgetCo2Saving">'.number_format(round($ipat_settings['co2Saving'],6),6).'</div>';
							echo '</div>';
						?>
					</td>
					<td>
						<input type="radio" <?php if ($ipat_settings['widgetType']==3) echo 'checked="checked"';?> value="3" name="ipat_widgetType"><span class="m-l">100 x 150 px</span><br/>
						<?php
							echo '<div class="ipat_widget ipat_widgetType3">';
							echo '<img src="'.plugins_url().'/'.dirname(plugin_basename(__FILE__)).'/assets/image/widget-100x150_'. $ipat_settings['lang'].'.png"/>';
							echo '<div class="ipat_widgetTreeCount">'.$ipat_settings['treeCount'].'</div>';
							echo '<div class="ipat_widgetCo2Saving">'.number_format(round($ipat_settings['co2Saving'],6),6).'</div>';
							echo '</div>';
						?>
					</td>
					<td>
						<input type="radio" <?php if ($ipat_settings['widgetType']==4) echo 'checked="checked"';?> value="4" name="ipat_widgetType"><span class="m-l">180 x 80 px</span><br/>
						<?php
							echo '<div class="ipat_widget ipat_widgetType4">';
							echo '<img src="'.plugins_url().'/'.dirname(plugin_basename(__FILE__)).'/assets/image/widget-180x80_'. $ipat_settings['lang'].'.png"/>';
							echo '<div class="ipat_widgetTreeCount">'.$ipat_settings['treeCount'].'</div>';
							echo '<div class="ipat_widgetCo2Saving">'.number_format(round($ipat_settings['co2Saving'],6),6).'</div>';
							echo '</div>';
						?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="ipat_refreshInterval"><?php _e('update interval','i-plant-a-tree');?></label>
					</th>
					<td colspan="4">
						<?php $ipat_intervalChecked=false; ?>
						<input type="radio" <?php if ($ipat_settings['refreshInterval']==240) {echo 'checked="checked"'; $ipat_intervalChecked=true;} ?> value="240" name="ipat_refreshInterval"><span class="m-l"><?php _e('4 hours','i-plant-a-tree');?></span>
						<input type="radio" <?php if ($ipat_settings['refreshInterval']==720) {echo 'checked="checked"'; $ipat_intervalChecked=true;}?> value="720" name="ipat_refreshInterval"><span class="m-l"><?php _e('12 hours','i-plant-a-tree');?></span>
						<input type="radio" <?php if ($ipat_settings['refreshInterval']==1440 || !$ipat_intervalChecked) echo 'checked="checked"';?> value="1440" name="ipat_refreshInterval"><span class="m-l"><?php _e('24 hours','i-plant-a-tree');?></span>
						<p class="description"><?php _e('The update interval determines how often new data will be gotten from server. Usually a daily update will do.','i-plant-a-tree');?></p>
						<!--Das Update-Interval bestimmt, wie häufig aktuelle Daten vom Server geholt werden. Im Normalfall genügt ein tägliches Update.-->
					</td>
				</tr>
			</table>
			<p class="submit">
				<input id="ipat_submit" class="button button-primary" type="submit" value="<?php _e('save changes','i-plant-a-tree');?>" name="ipat_submit">
			</p>
			<?php
				if (!$ipat_remoteUpdateSuccessful) {
					echo '<h3>';
					_e('Error: Server not accessible.','i-plant-a-tree');
					echo '</h3>';
					echo '<p>';
					_e('Your settings were saved, but no actual data could be retrieved from the IPAT-server.','i-plant-a-tree');
					echo '</p>';
				}
			?>
		</form>
	</div>
	<?php
}

function ipat_updateIfNecessary($forced=false) {
	$ipat_remoteUpdateSuccessful=false;
	$ipat_settings = get_option('ipat_widget');
	if (($ipat_settings['lastUpdate']+$ipat_settings['refreshInterval']*60)<time() || $forced) {
		$ipat_remoteUpdateSuccessful=ipat_getRemoteWidgetData ($ipat_settings);}
	return $ipat_remoteUpdateSuccessful;
}

function ipat_getRemoteWidgetData ($ipat_settings) {
	//$url = "https://www.iplantatree.org/widget/ipatWidget.html?uid=3070&wt=2&rh=https%3a%2f%2fwww.vollkornkartoffeln.de&lang=de";
	$url = "https://www.iplantatree.org/widget/ipatWidget.html?uid=".$ipat_settings['userID']."&wt=".$ipat_settings['widgetType']."&lang=".$ipat_settings['lang']."";
	if (!@get_headers($url)) {
		ipat_saveLog("Error getting data: ".$url);
		return false;
	} else {
		$fileText=file_get_contents($url);
		$fileText = str_replace(" ", '', $fileText);
		$fileText = str_replace(array("\r\n","\r","\n","\t","\v","\f","\e"), '', $fileText);
		$fileText = str_replace("<br>", '', $fileText);
		preg_match_all("/{.+}/s",$fileText,$jsonPart);
		$widget=json_decode($jsonPart[0][0]);
		$treeCount=$widget->{"Widget"}->{"Data"}->{"treeCount"};
		$co2Saving=$widget->{"Widget"}->{"Data"}->{"co2Saving"};
		$ipat_settings['lastUpdate']=time();
		if ($ipat_settings['userID']==0) {
			$ipat_settings['treeCount']=0;
			$ipat_settings['co2Saving']=0;
		} else {
			$ipat_settings['treeCount']=intval($treeCount,10);
			$ipat_settings['co2Saving']=floatval($co2Saving);
		}
		update_option('ipat_widget', $ipat_settings);
		return true;
	}
}

function ipat_sidebarDisplay($args) {
	ipat_updateIfNecessary();
	$ipat_settings = get_option('ipat_widget');
	echo $args['before_widget'];
	echo $args['before_title'].$ipat_settings['widgetControlTitle'].$args['after_title'];
	echo '<div class="textwidget">';

	switch ($ipat_settings['widgetType']) {
		case 2: $widgetImageSize='220x90'; break;
		case 3: $widgetImageSize='100x150'; break;
		case 4: $widgetImageSize='180x80'; break;
		default: $widgetImageSize='120x190'; break;
	}
	echo '<p>'.$ipat_settings['widgetControlTextBefore'].'</p>';
	echo '<p><div class="ipat_widget ipat_widgetType'.$ipat_settings['widgetType'];
	switch ($ipat_settings['widgetControlAlign']) {
		case 'left': echo ' ipat_alignSidebarLeft'; break;
		case 'right': echo ' ipat_alignSidebarRight'; break;
		default: echo ' ipat_alignSidebarCenter'; break;
	}
	echo '">';
	echo '<a href="https://www.iplantatree.org/user/'.$ipat_settings['userID'].'" target="_blank">';
	echo '<img src="'.plugins_url().'/'.dirname(plugin_basename(__FILE__)).'/assets/image/widget-'.$widgetImageSize.'_'. $ipat_settings['lang'].'.png"/>';
	echo '<div class="ipat_widgetTreeCount">'.$ipat_settings['treeCount'].'</div>';
	echo '<div class="ipat_widgetCo2Saving">'.number_format(round($ipat_settings['co2Saving'],6),6).'</div>';
	echo "</div>";
	echo '</a>';
	echo "</p>";
	echo '<p>'.$ipat_settings['widgetControlTextAfter'].'</p>';
	echo "</div>";
	echo $args['after_widget'];
}

wp_register_sidebar_widget(
    'ipat_sidebar',				// your unique widget id
    'I Plant A Tree',			// widget name
    'ipat_sidebarDisplay',		// callback function
    array(						// options
        'description' => 'Shows the saved CO2 in your sidebar.')
);
wp_register_widget_control(
	'ipat_sidebar',				// your unique widget id
	'I Plant A Tree',			// widget name
	'ipat_widgetControl'		// Callback function
);


function ipat_widgetControl($args=array(), $params=array()) {
	//the form is submitted, save into database
	$ipat_settings=get_option('ipat_widget');
	if (isset($_POST['submitted'])) {
		$ipat_settings['widgetControlTitle']=sanitize_text_field($_POST['ipat_widgetControlTitle']);
		$ipat_settings['widgetControlAlign']=sanitize_text_field($_POST['ipat_widgetControlAlign']);
		$ipat_settings['widgetControlTextBefore']=sanitize_text_field($_POST['ipat_widgetControlTextBefore']);
		$ipat_settings['widgetControlTextAfter']=sanitize_text_field($_POST['ipat_widgetControlTextAfter']);
		update_option('ipat_widget',$ipat_settings);
	}
	?>

	<p>
		<label for="ipat_widgetControlTitle"><?php _e('Title','i-plant-a-tree'); ?></label>
		<input id="ipat_widgetControlTitle" class="widefat" type="text" value="<?php echo $ipat_settings['widgetControlTitle']; ?>" name="ipat_widgetControlTitle">
	</p>
	<p>
		<label for="ipat_widgetControlAlign"><?php _e('Widget alignment','i-plant-a-tree'); ?></label>
		<input type="radio" <?php if ($ipat_settings['widgetControlAlign']=="left") echo 'checked="checked"';?> value="left" name="ipat_widgetControlAlign"><?php _e('left','i-plant-a-tree');?>
		<input type="radio" <?php if ($ipat_settings['widgetControlAlign']=="center") echo 'checked="checked"';?> value="center" name="ipat_widgetControlAlign"><?php _e('centered','i-plant-a-tree');?>
		<input type="radio" <?php if ($ipat_settings['widgetControlAlign']=="right") echo 'checked="checked"';?> value="right" name="ipat_widgetControlAlign"><?php _e('right','i-plant-a-tree');?>
	</p>
	<p>
		<label for="ipat_widgetControlTextBefore"><?php _e('Text above widget','i-plant-a-tree'); ?></label>
		<textarea id="ipat_widgetControlTextBefore" class="widefat" name="ipat_widgetControlTextBefore" cols="20" rows="2"><?php echo $ipat_settings['widgetControlTextBefore']; ?></textarea>
	</p>
	<p>
		<label for="ipat_widgetControlTextAfter"><?php _e('Text below widget','i-plant-a-tree'); ?></label>
		<textarea id="ipat_widgetControlTextAfter" class="widefat" name="ipat_widgetControlTextAfter" cols="20" rows="2"><?php echo $ipat_settings['widgetControlTextAfter']; ?></textarea>
	</p>
	<input type="hidden" name="ipat_widgetControlSubmitted" value="1" />
	<input type="hidden" name="submitted" value="1" />

	<?php
}
?>
