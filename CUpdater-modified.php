<?php
/**
 * @author Prif163 <Arturas.Juknevicius@stud.vgtu.lt>
 * @copyright    Copyright (C) 2019 VGTU. All rights reserved.
 * @license        GNU General Public License version 2 or later
 */
/**
* \brief no direct access
* used to mark a secure entry point into Joomla. The defined or die check makes sure that _JEXEC has been defined in the pathway to get to the file. It also prevents accidental injection of variables through a register globals attack that trick the PHP file into thinking it is inside the application when it really isn't.
*/
defined('_JEXEC') or die;
/**
 * Joomla! Dead link finder plugin
 * This plugin checks at specific intervals for dead links in admins website
 * It tehn sends emails to the admins default email or a custom address
 * THe email contains the dead link, the HTTP error code taht was found and 
 *
 *
 * @package        Joomla.Plugin
 * @subpackage    System.DeadLinkFinder
 */
class plgSystemCupdater extends JPlugin
{
	
/**
* \brief transforms relative links to absolute links`
* @param $rel linked passed to functioned. assumed to be realtive.
* @param $base URL where the relative linkw as found
* @return  absolute link
* The function checks if the passed, assumed relative, link is actually relative. If it'scandir
* absolute it returns the passed link, otherwise it transforms teh link to an absolute link
*/
public function rel2abs($rel, $base)
{
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;    /**< return if already absolute URL */

    if($rel == '#')return $base; /**< empty section link, can be ignored*/

    if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel; /**< queries and anchors */

    extract(parse_url($base)); /**< parse base URL and convert to local variables: $scheme, $host, $path */

    $path = preg_replace('#/[^/]*$#', '', $path); /**< remove non-directory element from path */

    if ($rel[0] == '/') $path = ''; /**< destroy path if relative url points to root */

    $abs = "$host$path/$rel"; /**< dirty absolute URL */

    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'); /**< replace '//' or '/./' or '/foo/../' with '/' */
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

    return $scheme.'://'.$abs; /**< absolute URL is ready! */
}

/** 
* \brief Takes home url of some site, returns array of dead links
* #param $home_url the home URL where the dead link finding starts. Should be landing page URL
* @return array of deadlinks and info [[deadLink, whereFound, whyDead], [deadLink, whereFound, whyDead], ...]
* The functionn performs a breath-first search trough the provided website
* FInds all links in current URL, tries to download them and if it can't
* adds to deadliink result array
*/
public function bfs($home_url) 
  {
    $Q = new SplQueue(); /**<Queue for storing current urls*/
    $Qfrom = new SplQueue(); /**<Queue for storing url where current url was found*/
    $set = array($home_url => 1); 
    $result = array();
    $Q->enqueue($home_url);
    $Qfrom->enqueue($home_url);

    while(!$Q->isEmpty())
    {
      $cur = $Q->dequeue();
      $from = $Qfrom->dequeue();
      echo $cur . " " . $http_response_header[0] . "\n";

      try
      {
        $html = file_get_contents($cur);
      }
      catch(Exception $e)
      {
        $html = false;
      }

      if($html == false  || !strpos($http_response_header[0], 'OK')) /**<First check if link is dead. */
      {
        $httpHeader = 'No header, failed to fetch';

        if(isset($http_response_header))
          $httpHeader = $http_response_header[0];
        
        if(strpos($httpHeader, 'OK'))
          continue;
        
        array_push($result, array($cur, $from, $httpHeader));

        continue;
      }

      if(substr($cur,0,strlen($home_url)) != $home_url) /**<Checks if prefix of current link matches base URL. If not doesn't check it */
        continue;
      
      $regex = '#(href|src)="[^"]+"#'; 
      preg_match_all($regex, $html, $urls);

      $urls[0] = str_replace ( 'href="', '', $urls[0]);
      $urls[0] = str_replace ( 'src="', '', $urls[0]);

      foreach ($urls[0] as &$url)
      {
        $url = substr($url, 0, -1); /**< removes trailing "*/

        $url = preg_replace('#\?.+#', '', $url); /**<removes querry string */

        $url = rel2abs($url, $cur);
        
        if(!array_key_exists($url, $set))
        {
          $Q->enqueue($url);
          $Qfrom->enqueue($cur);
          $set[$url]=1;
        }
      }
    }

    return $result;
  }
	/**
	* #param recipient array 
	*/
	
    private $recipients = array();
    /**
     * @return bool
     */
    public function onAfterRender()
    {
        if (!$this->doIHaveToRun()) { /**< checks if plugin needs to run */
            return false;
        }
        $lang = JFactory::getLanguage();
        $lang->load('plg_system_cupdater', JPATH_ADMINISTRATOR, 'en-GB', true);
        $lang->load('plg_system_cupdater', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
        $lang->load('plg_system_cupdater', JPATH_ADMINISTRATOR, null, true);
        // clear the cache - otherwise we get problems in backend
        $cache = JFactory::getCache();
        $cache->clean('com_plugins');
		try {
			$deadLinks = $this->bfs(JURI::base()); 
		} catch (UnexpectedValueException $e) {
			$title = JText::_('PLG_CUPDATER_ERROR_UPDATE');
			$body = JText::_('PLG_CUPDATER_ERROR_UPDATE_DESC');
			$body .= "\n" . JText::_('PLG_CUPDATER_DISCLAIMER') . "\n";
			$this->sendMail($title, $body);
			$this->setLastRunTimestamp();
			return false;
		}
        if (count($deadLinks)) {
            if (count($deadLinks) == 1) {
                $title = JText::_('PLG_CUPDATER_FOUND_ONE_DEAD_LINK');
            } else {
                $title = JText::sprintf('PLG_CUPDATER_FOUND_DEAD_LINKS', count($deadLinks));
            }
            $body = '';
            foreach ($deadLinks as $value) {
                $body .= JText::_('Dead-Link') . ': '
                    . $value[0] . ' ' . JText::_('Dead-Link') . ': '
                    . $value[1] . ' ' . JText::_('Dead-Link-Where-Found') . ": "
					. $value[2] . ' ' . JText::_('Dead-Link-HTTP-Error') . "\n";
            }
            $body .= "\n" . JText::_('.') . ': '
                . JURI::base() . ' ' . JText::_('.') . "\n";
        } else if ((!count($deadLinks)) && (($this->params->get('mailto_noresult') == 0))) {
            $title = JText::_('FOUND_NO_UPDATE_TITLE');
            $body = JText::_('FOUND_NO_UPDATE') . ' ' . JURI::base() . "\n";
        }
        if (count($deadLinks) ||
            ((!count($deadLinks)) && (($this->params->get('mailto_noresult') == 0)))) {
            $body .= $this->nextUpdateText();
            $body .= "\n" . JText::_('PLG_CUPDATER_DISCLAIMER') . "\n";
            $this->sendMail($title, $body);
        }
        $this->setLastRunTimestamp();
        return true;
    }
    /**
	* \brief gets text for config on next update time
     * @return string - our next update text
     */
    private function nextUpdateText()
    {
        $body = JText::_("PLG_CUPDATER_NEXT_UPDATE_CHECK_WILL_BE") . ' ';
        switch ($this->params->get('notification_period')) {
            case "24":
                $body .= JText::_('PLG_CUPDATER_TOMORROW') . '.';
                break;
            case "168":
                $body .= JText::_('PLG_CUPDATER_IN_ONE_WEEK') . '.';
                break;
            case "336":
                $body .= JText::sprintf('PLG_CUPDATER_IN_WEEKS', 2) . '.';
                break;
            case "672":
                $body .= JText::sprintf('PLG_CUPDATER_WEEKS', 4) . '.';
                break;
        }
        return $body;
    }
    /**
	* \bried sets up email
     * @param $title - the title of the mail
     * @param $body - the body of the mail
     */
    private function sendMail($title, $body)
    {
        $app = JFactory::getApplication();
        $recipients = $this->getRecipients();
        $mail = JFactory::getMailer();
        $mail->addRecipient($recipients);
        $mail->setSender(array($app->getCfg('mailfrom'), $app->getCfg('fromname')));
        $mail->setSubject($title);
        $mail->setBody($body);
        $mail->Send();
    }
    /**
     * \brief gets recipients of email
	 * Checks config to see if theres one admin recipient or a custom list.
	 * Gets list from config.
     * @return array - all recepients
     */
    private function getRecipients()
    {
        $emails = array();
        if (!count($this->recipients)) {
            if ($this->params->get('mailto_admins', 0)) {
                $groups = $this->params->get('mailto_admins');
                $tmp_emails = $this->getEmailsForUsersInGroups($groups);
                if (!is_array($emails)) {
                    $emails = array();
                }
                $emails = array_merge($tmp_emails, $emails);
            }
            if ((int)$this->params->get('mailto_custom') == 1 && $this->params->get('custom_email') != "") {
                $tmp_emails = explode(';', $this->params->get('custom_email'));
                if (!is_array($emails)) {
                    $emails = array();
                }
                $emails = array_merge($tmp_emails, $emails);
            }
            $tmp = array();
            foreach ($emails AS $r)
            {
                if (in_array($r, $tmp) || trim($r) == "") {
                    continue;
                }
                else {
                    $this->recipients[] = $r;
                    $tmp[] = $r;
                }
            }
        }
        return $this->recipients;
    }
    /**
     * \brief checks the last execution timestamp, stored in the component's configuration.
     *
     * @return bool
     */
    private function doIHaveToRun()
    {
        $params = $this->params;
        $lastRunUnix = $params->get('plg_cupdate_timestamp', 0);
        $dateInfo = getdate($lastRunUnix);
        $nextRunUnix = mktime(0, 0, 0, $dateInfo['mon'], $dateInfo['mday'], $dateInfo['year']);
        $nextRunUnix += $params->get('notification_period', 24) * 3600;
        $now = time();
        return ($now >= $nextRunUnix);
    }
    /**
     * \brief Saves the timestamp of this plugin's last run
     */
    private function setLastRunTimestamp()
    {
        $lastRun = time();
        $params = $this->params;
        $params->set('plg_cupdate_timestamp', $lastRun);
        $db = JFactory::getDBO();
        $data = $params->toString('JSON');
        $query = $db->getQuery(true);
        $query->update('#__extensions');
        $query->set('params = ' . $db->Quote($data));
        $query->where('element = "cupdater" AND type = "plugin"');
        $db->setQuery($query);
        $db->query();
    }
    /**
     *
     * @param array $groups - the user group
     * @return mixed
     */
    private function getEmailsForUsersInGroups(array $groups)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('a.email');
        $query->from('#__user_usergroup_map AS map');
        $query->leftJoin('#__users AS a ON a.id = map.user_id');
        $query->where('map.group_id IN (' . implode(',', $groups) . ')');
        $db->setQuery($query);
        return $db->loadColumn();
    }
}
