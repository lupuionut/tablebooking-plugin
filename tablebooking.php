<?php

defined('_JEXEC') or die;

class PlgContentTablebooking extends JPlugin
{
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

        $regex = '#\{\{tablebooking(?=( id=([0-9]{1,})\}\})|\}\})#';
        preg_match($regex, $row->text, $matches);
        $found = count($matches);
        $restaurant_id = 0;

        if ($found > 0) {
            if ($found == 3) {
                $restaurant_id = $matches[2];
            }
        }

        return true;
    }
}