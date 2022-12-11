<?php

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;

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

        // Don't run this plugin when the content is being indexed
        if ($context === 'com_finder.indexer') {
            return true;
        }

        // if component is not installed or disabled do not continue
        if (!$this->componentInstalled()) {
            return true;
        }

        $ids = $this->extractIds($row->text);

        // generate a booking form for each found instance on the page
        foreach ($ids as $key => $id) {
            if ($id == -1) {
                $replacement = $this->generateForm($key, 0);
                $row->text = preg_replace('#{{tablebooking}}#', $replacement, $row->text, 1);
            } elseif ($id == 0) {
                $replacement = $this->generateForm($key, 0);
                $row->text = preg_replace('#{{tablebooking id=0}}#', $replacement, $row->text, 1);
            } else {
                $replacement = $this->generateForm($key, $id);
                $row->text = preg_replace('#{{tablebooking id=[0-9]{1,}}}#', $replacement, $row->text, 1);
            }
        }

        return true;
    }


    // returns an array with all restaurants id for which we should generate
    // the booking form
    protected function extractIds($content) {

        $ids = array();
        $regex = '#\{\{tablebooking(?=( id=([0-9]{1,})\}\})|\}\})#';
        preg_match_all($regex, $content, $matches);
        if (count($matches) == 3) {
            return array_map(function($el){return $el != '' ? $el : -1;}, $matches[2]);
        }
        return $ids;
    }


    // returns boolean, true if TableBooking component is installed and activated
    // false if the component is not installed or is not activated
    protected function componentInstalled() {

        if (ComponentHelper::getComponent('com_tablebooking')->id) {
            return true;
        }
        return false;
    }


    protected function generateForm($key, $id) {

        Factory::getDocument()->addScript('https://unpkg.com/vue@3/dist/vue.global.js');
        Factory::getDocument()->addScript('components/com_tablebooking/assets/js/calendar.js');
        $isNew = self::$isNewInstance;
        self::$isNewInstance = false;
        return LayoutHelper::render(
            'form', array('key' => $key, 'id' => $id, 'isNew' => $isNew), __DIR__ . '/layouts');
    }
}