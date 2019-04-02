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
(function($){
    "use strict";
    var addDirectoryProfilePair = {
        init: function() {
            this.addButton = $('button.e20r-directory-add-directory-pair' );

            var self = this;

            this.addButton.on('click', function() {
                event.preventDefault();
                window.console.log("Adding new pair from AJAX call");
                self.loadNewRow();
            });
        },
        loadNewRow: function() {
            var self = this;
            var $row = '';

            $.ajax({
                url: e20rdir.ajax.url,
                type: 'POST',
                dataType: 'html',
                timeout: ( parseInt( e20rdir.ajax.timeout ) * 1000),
                data: {
                    action: 'e20r_directory_load_new_row',
                    pmpro_pagesettings_nonce: $('#pmpro_pagesettings_nonce').val(),
                },
                success: function( data, $status, $qjXHR ) {

                    window.console.log( data );
                    if ( 'undefined' !== typeof data ) {
                        self.lastRow = $( 'tr.e20r-directory-page-pair-setting' ).last();
                        self.lastRow.after( data );
                    }
                },
                error: function( $jqXHR, $status, $errorThrown ) {

                    window.console.log("Status: " + $status );
                    window.console.log("Error: " + $errorThrown );

                },
            });

            return $row;
        }
    };

    $(document).ready( function(){
        addDirectoryProfilePair.init();
    } );
})(jQuery);
