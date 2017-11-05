<?php
/**
 * Copyright (c) 2017 - Eighty / 20 Results by Wicked Strong Chicks, LLC
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @credit https://www.paidmembershipspro.com
 */
function pmproemd_load_textdomain()
{
    //get the locale
    $locale = apply_filters("plugin_locale", get_locale(), "pmpro-member-directory");
    $mofile = "pmpro-" . $locale . ".mo";
    
    //paths to local (plugin) and global (WP) language files
    $mofile_local  = dirname(__FILE__)."/../languages/" . $mofile;
    $mofile_global = WP_LANG_DIR . '/pmpro/' . $mofile;
    
    //load global first
    load_textdomain("pmpro-member-directory", $mofile_global);
    
    //load local second
    load_textdomain("pmpro-member-directory", $mofile_local);
}
add_action('init', 'pmproemd_load_textdomain', 1 );