/*
 *    Copyright (c) 2019. - Eighty / 20 Results by Wicked Strong Chicks. ALL RIGHTS RESERVED
 *
 *     This program is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
(function ($) {
    "use strict";
    var addDirectoryProfilePair = {
        init: function () {
            this.addButton = $('button.e20r-directory-add-directory-pair');
            this.saveButton = $('input[name="savesettings"]');
            this.deleteButton = $('button.e20r-directory-pair-delete' );

            var self = this;

            self.addButton.on('click', function () {
                event.preventDefault();
                window.console.log("Adding new pair from AJAX call");
                self.loadNewRow();
            });

            self.saveButton.on('click', function () {
                self.changeIDs(this);
            });

            self.deleteButton.on('click', function() {
                self.deleteRow(this);
            });
        },
        deleteRow: function( del_button ) {

            del_button = $(del_button);
            del_button.closest( 'tr.e20r-directory-page-pair-setting' ).remove();
        },
        changeIDs: function (button) {

            var self = this;
            button = $(button);

            var form = button.closest('form');

            var directory_select = form.find('select.e20r-directory-setting');

            directory_select.each(function () {

                var select = $(this);

                self.updateSelectNames( select );
            });
        },
        updateSelectNames: function( directory_select ) {

            var directory_page_id;
            var $directory_name = directory_select.attr('name');

            var profile_select = directory_select.closest( 'td.e20r-directory-page-pair' ).find('select.e20r-profile-setting');
            var $profile_name = profile_select.attr('name');

            window.console.log("Directory Select Name: " + $directory_name);
            window.console.log("Profile Select Name: " + $profile_name);

            if ( false === $directory_name.match(/--1]$/)) {
                window.console.log("Doesn't have the '-1' (none) ID in its name");
                return;
            }

            directory_page_id = directory_select.val();
            $directory_name = $directory_name.replace( '--1]', '-' + directory_page_id + ']' );
            $profile_name = $profile_name.replace( '--1]', '-' + directory_page_id + ']');

            window.console.log('New directory attribute name: ' + $directory_name );
            window.console.log('New profile attribute name: ' + $profile_name );

            directory_select.attr( 'name', $directory_name );
            profile_select.attr( 'name', $profile_name );
        },
        loadNewRow: function () {
            var self = this;
            var $row = '';

            $.ajax({
                url: e20rdir.ajax.url,
                type: 'POST',
                dataType: 'html',
                timeout: (parseInt(e20rdir.ajax.timeout) * 1000),
                data: {
                    action: 'e20r_directory_load_new_row',
                    pmpro_pagesettings_nonce: $('#pmpro_pagesettings_nonce').val(),
                },
                success: function (data, $status, $qjXHR) {

                    window.console.log(data);
                    if ('undefined' !== typeof data) {
                        self.lastRow = $('tr.e20r-directory-page-pair-setting').last();
                        self.lastRow.after(data);
                    }
                },
                error: function ($jqXHR, $status, $errorThrown) {

                    window.console.log("Status: " + $status);
                    window.console.log("Error: " + $errorThrown);

                },
            });

            return $row;
        }
    };

    $(document).ready(function () {
        addDirectoryProfilePair.init();
    });
})(jQuery);
