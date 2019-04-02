/*
 *
 * Copyright (c) 2018. - Eighty / 20 Results by Wicked Strong Chicks.
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
 */

jQuery( document ).ready( function( $ ) {
    "use strict";
    var all_select2s = $('.e20r-select2' );

    all_select2s.each(function() {

        var imageOption = $(this).data('imgoption');

        window.console.log("Image option: " + imageOption );

        if ( 'image' === imageOption ) {

            $(this).select2({
                allowClear: true,
                templateResult: formatForImage,
                templateSelection: function(option) {
                    if ( option.id.length > 0 ) {
                        return option.text;
                    } else {
                        return option.text;
                    }
                },
                escapeMarkup: function (m) {
                    return m;
                }
            });
        } else {
            $(this).select2();
        }

    });

    function formatForImage( option ) {

        window.console.log( option );

        if ( !option.id ) { return option.text; }

        var ob = option.text + option.img;
        return option.text;
    }
});