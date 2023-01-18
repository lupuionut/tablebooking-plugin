<?php

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die;

class PlgContentTablebooking extends JPlugin
{
    protected $autoloadLanguage = true;
    protected static $isNewInstance = true;

    /**
    * @param   string   $context  The context of the content being passed to the plugin.
    * @param   mixed    &$row     An object with a "text" property or the string to be cloaked.
    * @param   mixed    &$params  Additional parameters. See {@see PlgContentEmailcloak()}.
    * @param   integer  $page     Optional page number. Unused. Defaults to zero.
    *
    * @return  boolean	True on success.
    */
    public function onContentPrepare($context, &$row, &$params, $page = 0) {

        JLoader::register('TbRestaurant', JPATH_CONFIGURATION . '/components/com_tablebooking/helpers/tbrestaurant.php');

        // Don't run this plugin when the content is being indexed
        if ($context === 'com_finder.indexer') {
            return true;
        }

        // if component is not installed or disabled do not continue
        if (!$this->componentInstalled()) {
            return true;
        }

        $expressions = $this->getExpressions($row->text);

        foreach ($expressions as $key => $expression) {

            // init the default arguments for the booking form
            $arguments = array("id" => 0, "lang" => "en");
            $this->parseExpression($expression, $arguments);

            if ($this->isValidRestaurant($arguments['id'])) {
                $replacement = $this->generateForm($key, $arguments);
            } else {
                $replacement = '';
            }
            $row->text = preg_replace('#{{tablebooking .*?}}#', $replacement, $row->text, 1);
        }

        return true;
    }


    protected function getExpressions($content) {
        $regex = '#{{tablebooking .*?}}#';
        preg_match_all($regex, $content, $matches);
        return $matches[0];
    }


    /*
    * Parse a string {{tablebooking x=22 y=uk p=again}} into a corresponding array
    * ["x": "22", "y": "uk", "p": "again"]
    * This allows adding to the plugin a variable number of arguments
    */
    protected function parseExpression($expression, &$arguments) {
        $substring = trim(substr($expression, 14, -2));
        $i = 0;
        $max = strlen($substring);
        $key = '';
        $value = '';
        $direction = 'left';
        while ($i <= $max) {
            if ($i == $max) {
                $arguments[$key] = $value;
                break;
            }
            if ($substring[$i] == "=") {
                $arguments[$key] = "";
                $direction = 'right';
                $i++;
                continue;
            }
            if ((ord($substring[$i]) >= 97 && ord($substring[$i]) <= 122) ||
                (ord($substring[$i]) >= 48 && ord($substring[$i])<= 57)) {
                if ($direction == 'left') {
                    $key .= $substring[$i];
                } else {
                    $value .= $substring[$i];
                }
            } else {
                if ($value != '') {
                    $arguments[$key] = $value;
                }
                $direction = 'left';
                $key = '';
                $value = '';
            }
            $i++;
        }
    }


    // returns boolean, true if TableBooking component is installed and activated
    // false if the component is not installed or is not activated
    protected function componentInstalled() {

        if (ComponentHelper::getComponent('com_tablebooking')->id) {
            return true;
        }
        return false;
    }


    protected function generateForm($key, $arguments) {

        Factory::getDocument()->addScript(Uri::root() . 'plugins/content/tablebooking/assets/vue.global.prod.js');
        Factory::getDocument()->addScript(Uri::root() . 'plugins/content/tablebooking/assets/vue-datepicker.js');
        Factory::getDocument()->addStyleSheet(Uri::root() . 'plugins/content/tablebooking/assets/main.css');
        Factory::getDocument()->addStyleSheet(Uri::root() . 'plugins/content/tablebooking/assets/tablebooking.css?t=' . time());
        $isNew = self::$isNewInstance;
        self::$isNewInstance = false;
        $path = PluginHelper::getLayoutPath('content', 'tablebooking', 'form');
        $displayData = array('key' => $key, 'id' => $arguments['id'], 'isNew' => $isNew, 'lang' => $arguments['lang']);
        ob_start();
        include $path;
        $data = ob_get_clean();
        return $data;
    }


    protected function isValidRestaurant($id) {
        $restaurant = TbRestaurant::getInstance($id);
        return $restaurant->id == 0 ? false : true;
    }
}

