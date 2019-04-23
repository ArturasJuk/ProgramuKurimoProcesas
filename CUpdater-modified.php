<?php
/**
 * @author Daniel Dimitrov <daniel@compojoom.com>
 * @copyright    Copyright (C) 2008 - 2013 compojoom.com. All rights reserved.
 * @license        GNU General Public License version 2 or later
 */
// no direct access
defined('_JEXEC') or die;
/**
 * Joomla! Update notification plugin
 * This plugin checks at specific intervals for new updates
 * and sends a notification if an update is found
 *
 * Special thanks to O.Schwab <service@castle4us.de> for coming with the idea
 * in the first place :)
 *
 * @package        Joomla.Plugin
 * @subpackage    System.cupdater
 */
class plgSystemCupdater extends JPlugin
{
	
//relative linka transformuoja i absoliutu
public function rel2abs($rel, $base)
{
    /* return if already absolute URL */
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;

    /* empty section link, can be ignored*/
    if($rel == '#')return $base;

    /* queries and anchors */
    if ($rel[0]=='#' || $rel[0]=='?') return $base.$rel;

    /* parse base URL and convert to local variables: $scheme, $host, $path */
    extract(parse_url($base));

    /* remove non-directory element from path */
    $path = preg_replace('#/[^/]*$#', '', $path);

    /* destroy path if relative url points to root */
    if ($rel[0] == '/') $path = '';

    /* dirty absolute URL */
    $abs = "$host$path/$rel";

    /* replace '//' or '/./' or '/foo/../' with '/' */
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

    /* absolute URL is ready! */
    return $scheme.'://'.$abs;
}


// Takes home url of some site, returns array of dead links
//returns array [[deadLink, whereFound, whyDead], [deadLink, whereFound, whyDead], ...]

public function bfs($home_url) 
  {
    $Q = new SplQueue(); //Queue for storing current urls
    $Qfrom = new SplQueue(); //Queue for storing url where current url was found
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

      //linkas mires, ar isvis neegzistuoja domain
      if($html == false  || !strpos($http_response_header[0], 'OK')) 
      {
        $httpHeader = 'No header, failed to fetch';

        if(isset($http_response_header))
          $httpHeader = $http_response_header[0];
        
        if(strpos($httpHeader, 'OK'))
          continue;
        
        array_push($result, array($cur, $from, $httpHeader));

        continue;
      }

      //jei prefixas neatitinka home/root page tai tame puslapyje linku neieskom
      if(substr($cur,0,strlen($home_url)) != $home_url) 
        continue;
      
      $regex = '#(href|src)="[^"]+"#'; 
      preg_match_all($regex, $html, $urls);

      $urls[0] = str_replace ( 'href="', '', $urls[0]);
      $urls[0] = str_replace ( 'src="', '', $urls[0]);

      foreach ($urls[0] as &$url)
      {
        //pasalinam trailing kabute "
        $url = substr($url, 0, -1);

        //pasalinam query string
        $url = preg_replace('#\?.+#', '', $url);

        //gaunam absoliutu linka
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
	
    private $recipients = array();
    /**
     * @return bool
     */
    public function onAfterRender()
    {
        if (!$this->doIHaveToRun()) {
            return false;
        }
//      so we are running??? Then let us load some languages
        $lang = JFactory::getLanguage();
        $lang->load('plg_system_cupdater', JPATH_ADMINISTRATOR, 'en-GB', true);
        $lang->load('plg_system_cupdater', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
        $lang->load('plg_system_cupdater', JPATH_ADMINISTRATOR, null, true);
        // clear the cache - otherwise we get problems in backend
        $cache = JFactory::getCache();
        $cache->clean('com_plugins');
		// note for Joomla 3
		// the JHttpTransportCurl class is throwing an exception when it is unable to get the content out
		// of an url. The exception is not caught in the Joomla updater class and this leads to a nasty
		// exception thrown out at the user. That is why we catch it here and send an email to the
		// people that should be notified about that
		// normally joomla should disable such urls, but since they don't react on the Exception this will
		// happen each time the update script is run
		// that is why the administrator will need to find the url causing the problem and disable it for the server
		try {
			$deadLinks = $this->bfs(JURI::base()); 
		} catch (UnexpectedValueException $e) {
			// let us send a mail to the user
			$title = JText::_('PLG_CUPDATER_ERROR_UPDATE');
			$body = JText::_('PLG_CUPDATER_ERROR_UPDATE_DESC');
			$body .= "\n" . JText::_('PLG_CUPDATER_DISCLAIMER') . "\n";
			$this->sendMail($title, $body);
			$this->setLastRunTimestamp();
			// and exit...
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
     *
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
     * "Do I have to run?" - the age old question. Let it be answered by checking the
     * last execution timestamp, stored in the component's configuration.
     *
     * this function is copied from the asexpirationnotify plugin so all credits go to
     * Nicholas K. Dionysopoulos / AkeebaBackup.com
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
     * Saves the timestamp of this plugin's last run
     * this function is copied from the asexpirationnotify plugin so all credits go to
     * Nicholas K. Dionysopoulos / AkeebaBackup.com
     *
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