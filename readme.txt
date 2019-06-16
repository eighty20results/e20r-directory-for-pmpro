=== E20R Directory for PMPro ===
Contributors: eighty20results
Tags: pmpro, paid memberships pro, members, directory, eighty/20 results
Requires at least: 4.4
Tested up to: 5.2.1
Stable tag: 3.6.3

Add a enhanced and more robust Member Directory and Profiles to your Membership Site - with attributes to customize the display.

== Description ==

The E20R Directory for PMPro plugin enhances your membership site with a public or private, searchable member directory and member profiles.

This plugin creates 2 short codes for a Member Directory and Member Profile pages, which can be defined in Memberships > Settings > Pages of the WordPress admin.

It has support for a number of additional attributes/features beyond what the PMPro add-on offers (see list).

Unlike the PMPro add-on, this version of the member directory supports multiple directory/profile page combinations. As a result,
it's possible to configure more than one "Directory" page and have it linked to it's own "Profile" page. This means you can
create multiple directories with different settings. One use case is to have People and Business memberships where the People directory consists solely of users/members. The "Business directory", on the other hand, consists solely of businesses.

With the activation of this plugin, you will see a Billing Address and Shipping Address section on the user's WordPress
profile page (backend). This section will let your members update their PMPro billing and shipping address(es).

The shipping address section is only available if the PMPro Shipping Address add-on is installed and active (or has been).

The [pmpro_member_directory] and [pmpro_member_profile] short codes are fully supported.

Credit: Loosely based on the PMPro Member Directory and Profiles add-on from the Paid Memberships Team.

=== Shortcodes ===
Shortcode attributes for `[e20r-member-directory]` include:

1. avatar_size: The square pixel dimensions of the avatar to display. Requires the "show_avatar" attribute to be set to 'true'. default: '128' (accepts any numerical value).
1. fields: Display additional user meta fields. default: none (accepts a list of label names and field IDs, i.e. fields="Company,company;Website,user_url").
1. layout: The format of the directory. default: div (accepts 'table', 'div', '2col', '3col', and '4col').
1. levels: The level ID or a comma-separated list of level IDs to include in the directory. default: all levels (accepts a single level ID or a comma-separated list of IDs).
1. paginated: Whether to use pagination or not (default: true )
1. page_size: The number of records per page in a paginated display (default: 15)
1. link: Optionally link the member directory item to the single member profile page. default: true (accepts 'true' or 'false').
1. order: Sort the results based on the order_by attribute in ascending or descending order. default: ASC (accepts 'DESC' or 'ASC'). 
1. order_by: The sort order for the results. default: 'display_name' (accepts 'user_email', 'display_name', 'user_login', 'user_registered', 'membership_id', 'startdate', 'joindate', 'last_name', 'first_name' )
1. show_avatar: Display the user's avatar generated via Gravatar (https://en.gravatar.com) or user-submitted using a plugin like Simple Local Avatars (https://wordpress.org/plugins/simple-local-avatars/); default: true (accepts 'true' or 'false').
1. show_email: Display the user's email address; default: true (accepts 'true' or 'false').
1. show_level: Display the user's membership level; default: true  (accepts 'true' or 'false').
1. show_search: Display a search form (searches on member display name or email address); default: true (accepts 'true' or 'false').
1. show_startdate: Display the user's membership start date for their current level; default: true (accepts 'true' or 'false').
1. show_roles: Display the users if they have been assigned the role(s) listed (default: null, accepts comma separated list of role names)
1. members_only_link: Show the link to the profile details page to logged in and active members only (default: 'false', accepts 'true' or 'false')
1. editable_profile: If the user is logged in, they are shown both a view and edit link for their profile page (default: 'false', accepts 'true' or 'false'). Caution: The edit link may direct the user to the WordPress backend unless a front-end profile plugin is installed (For example: Theme My Login)
1. profile_page_slug: The page slug for the profile page to send the viewer to if they click on the profile for a user. (This will also allow multiple profile and directory pages on the same site).
1. filter_key_name: Allows filtering of the directory based on the User Meta key and value (for instance a Register Helper field name and value). This attribute must be used together with the 'filter_key_value' attribute.
1. filter_key_value: Allows filtering of the directory based on the User Meta key and value (for instance a Register Helper field name and value). This attribute must be use together with the 'filter_key_name' attribute.


Shortcode attributes for `[e20r-member-profile]` include:

1. avatar_size: The square pixel dimensions of the avatar to display. Requires the "show_avatar" attribute to be set to 'true'. default: '128' (accepts any numerical value).
1. fields: Display additional user meta fields. default: none (accepts a list of label names and field IDs, i.e. fields="Company,company;Website,user_url").
1. show_avatar: Display the user's avatar generated via Gravatar (https://en.gravatar.com) or user-submitted using a plugin like Simple Local Avatars (https://wordpress.org/plugins/simple-local-avatars/); default: true (accepts 'true' or 'false').
1. show_bio: Display the user's bio (if available); default: true (accepts 'true' or 'false').
1. show_billing: Display the user's billing address (if available); default: true (accepts 'true' or 'false').
1. show_email: Display the user's email address; default: true (accepts 'true' or 'false').
1. show_level: Display the user's membership level; default: true  (accepts 'true' or 'false').
1. show_phone: Display the user's billing phone (if available); default: true (accepts 'true' or 'false').
1. show_search: Display a search form (searches on member display name or email address); default: true (accepts 'true' or 'false').
1. show_startdate: Display the user's membership start date for their current level; default: true (accepts 'true' or 'false').
1. user_id: Show a specific member's profile; default: none (accepts any numeric uesr id, i.e. user_id="125").
1. billing_address: Show the PMPro Billing information in a separate section of the profile page. Default: 'false' (accepts 'true' or 'false'). The default heading for the section is "Billing Address", but can be modified with the 'e20r-directory-profile-billing-header' filter. The filter returns the text of the heading.
1. shipping_address: Show the PMPro Shipping information in a separate section of the profile page. Default: 'false' (accepts 'true' or 'false'). The default heading for the section is "Shippping Address", but it can be modified with the 'e20r-directory-profile-shipping-header' filter. The filter returns the text of the heading.
1. directory_page_slug: The page slug for the directory page to use when allowing a user to return (This will also allow multiple profile and directory pages on the same site)

== Installation ==

1. Upload the `e20r-directory-for-pmpro` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the `Plugins` menu in WordPress.
1. Create a page for your directory and set the appropriate shortcode attributes and `Require Membership` settings per your needs.
1. Create a page for your profile and set the appropriate shortcode attributes and `Require Membership` settings per your needs.
1. Navigate to Memberships > Page Settings to assign your pages to the Directory and Profile page settings.

== Examples ==

Show only level IDs 1 and 4, hide avatars and email address:
[e20r-member-directory levels="1,4" show_avatar="false" show_email="false"]

Show all level IDs, hide level name and start date:
[e20r-member-directory show_level="false" show_startdate="false"]

Show a unique member directory by level. Level 1 Members can only see other Level 1 Members...:
[membership level="1"]
[e20r-member-directory levels="1"]
[/membership]

[membership level="2"]
[e20r-member-directory levels="2"]
[/membership]

[membership level="3"]
[e20r-member-directory levels="3"]
[/membership]

Show unique member profiles based on level - hide user phone number and email address for member listings belonging to
 membership level ID 1. Show the same data for membership levels belonging to membership level ID 2.

Using a single page, but with the [membership] short code, you can modify views for logged in users

 On (fake) Page #1 (show to visitors who haven't logged in):
[membership level="-l"]
[e20r-member-directory levels="1" show_email="false" show_phone="false"]
[/membership]

On Page #2 (show to logged in users):
[membership level="l"]
[e20r-member-profile levels="2" show_email="true" show_phone="true"]
/[membership]

OR, you can use completely separate page(s) for the two level specific directory short codes and their corresponding
member profile pages to display member level specific directories. See the "Membership" -> "Settings" ->
"Page Settings" page to configure multiple directories/profiles for the site.

== Hooks & Filters ==
=== Filters ===
1. e20r-member-profile_shipping_header - Set the heading for the Shipping Information section (if applicable). default: 'Billing Address' - string
1. e20r-member-profile_billing_header - Set the heading for the Billing Information section (if applicable). default: 'Shipping Address' - string
1. pmpromd_extra_search_fields - List (array) of user meta data keys that will have added search input sections on the directory page - default: array() (empty array)
1. pmpromd_exact_search_values - Whether to wrap the search input in wildcard during DB operation, or use the extact string as typed - default: false (boolean)
1. pmpromd_membership_statuses - Membership statuses to include in the search result. Default behavior is to only included active member(s) - default: array( 'active' )
1. e20r-directory-for-pmpro_set_order - Directly change the directory search result order (SQL).
1. e20r-directory-for-pmpro_sql - Modify the SQL statement used to search for whom to include in the directory listing
1. e20r-directory-for-pmpro_search_class - Add extra CSS class(es) to the directory listing - default: null
1. pmpromd_search_placeholder_text - Change the placeholder text in the Search input field - default: "Search Members" - string
1. e20r-directory-for-pmpro_extra_search_input - Add array of HTML to add extra search input fields (and field types) below the main "Search" input.
1. e20r-member-profile_fields - Allow user to remove/add additional usermetadata fields & labels programatically
1. e20r-directory-for-pmpro_non_admin_profile_settings - Set to false in order to hide the "Hide from Member Directory?" setting on the user's WordPress profile page, unless they're assigned the administrator role.
1. 'e20r-directory-for-pmpro_included_levels' - Allow admin to configure which membership level(s) to include for directory
1. 'e20r-directory-for-pmpro_metafield_value' - Used to format/modify the displayed usermeta value (Register Helper field value). Useful when using select/select2 fields and you need to translate a value to a label or if the stored value consists of an array. In that case, the default behavior is to let the value to display as a comma-separated list).
1. e20r-directory-for-pmpro_profile_show_return_link - Allow hiding the "View All Members" link on/from the Member Profile page (template).

=== Action hooks ===
1. e20r-directory-for-pmpro_extra_search_output - Output HTML so a user can provide input for the specified pmpromd_extra_search_fields search fields
1. e20rmd_add_extra_profile_output - By default used to output the Shipping & Billing information sections on the user profile page, but can be used to add more data to the profile page for the user. Passes the current user's WP_User object as well as the array of entries from the 'fields=""' attribute (as it was prior to having been passed through the 'e20r-member-profile_fields' filter).
1. e20rmd_add_extra_profile_output - Output HTML at the bottom of the profile page entry for the selected member. Accepts 2 arguments: $real_fields_array (array of fields from Register Helper) and $profile_user (WP_User object for the member/user)
1. e20rmd_add_extra_directory_output - Output HTML at the bottom of the directory entry for the current user. Accepts 2 arguments: $real_fields_array (array of fields from Register Helper) and $the_user (WP_User object for the member/user)


NOTE: e20r-directory-for-pmpro_extra_search_input (filter hook) and e20r-directory-for-pmpro_extra_search_output (action hook) are two ways - hooks - of achieving the same thing (the filter is for backwards compatibility reasons). The preferred approach at this point is to use the e20r-directory-for-pmpro_extra_search_output action hook.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/eighty20results/e20r-directory-for-pmpro/issues

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at http://www.eighty20results.com for more documentation and our support forums.

== Changelog ==

= 3.6.3 =

* BUG FIX: Redirecting a profile view to the directory page for the wrong reasons

= 3.6.2 =

* BUG FIX: Attempted to process profile preheader on a non-page
* BUG FIX: Refactored profile shortcode check
* BUG FIX: Error in regex for level(s)

= 3.6.1 =

* BUG FIX: Load utils library early in profilePreHeader()
* BUG FIX: Improved debugging
* BUG FIX: User slug and login isn't the same value!
* BUG FIX: Not checking for email address as search value for user

= 3.6 =

* BUG FIX: Error while handling multiple page pairs
* BUG FIX: Error while processing multiple page pairs and the PMPro directory/profile ID settings

= 3.5 =

* ENHANCEMENT: Attempt to deactivate page caching when displaying directory or profile page/post(s)

= 3.4 =

* BUG FIX: Didn't include javascript files in plugin build
* BUG FIX: Didn't escape the add_query_arg() result
* BUG FIX: Incorrect URL for edit link on PMPro Settings -> Pages page
* BUG FIX: Didn't load the CSS file when editing profile w/TML Profile plugin
* BUG FIX: Didn't list pages hierarchically when showing drop-down of pages for settings
* BUG FIX: Not using the preferred shortcode for the generate functions
* BUG FIX: Included TML in docker environment (home)
* BUG FIX: Load TML support as module (licensed)
* BUG FIX: Don't attempt to load plugin-update-checker in development environment

= 3.3.1 =

* BUG FIX: Incorrect path when loading the Plugin Updates Checker component

= 3.3 =

* ENHANCEMENT: Added filter 'e20r-directory-load-admin-css-on-page' to let custom plugins load billing/shipping address CSS
* ENHANCEMENT: Add Template_Page base class for page templates in the plugin
* ENHANCEMENT: Added PHPDoc blocks for variables & some of the functions
* ENHANCEMENT: Add support for filterable list of supported profile short codes (have to be compatible w/PMPro or this plugin's profile page template)
* ENHANCEMENT: Added 'e20r-directory-supported-shortcodes' filter representing list of profile/directory short codes we support
* ENHANCEMENT: Directory_Page now extends the Template_Page class
* ENHANCEMENT: Improved PHPDoc blocks for class variables
* ENHANCEMENT: Using filter to identify all supported short codes for the directory page
* BUG FIX: Occasional fatal error during plugin activation
* BUG FIX: Returning too many results during search
* BUG FIX: Didn't handle all cases of yes/true/no/false values
* BUG FIX: addURL() function didn't always save the right URL
* BUG FIX: Use the Template_Page::hasShortcode() function instead of page specific functions
* BUG FIX: Didn't load the billing/shipping address CSS on back/front end editable profile page(s)\
* BUG FIX: Bad/incorrect short codes for extra page settings in PMPro (for this plugin)
* BUG FIX: Didn't load the plugin updater functionality
* BUG FIX: Didn't always return the page size variable to the directory when on the Profile page
* BUG FIX: Incomplete list of supported shortcode templates in Profile_Page class
* BUG FIX: Didn't use the attribute value for use_precise_values when passing to filter(s)
* BUG FIX: Could return duplicate results when user did a search
* BUG FIX: Didn't always activate the plugin
* BUG FIX: Template_Page is an incorrectly declared class
* BUG FIX: Instantiation error in E20R_Directory_For_PMPro class
* BUG FIX: Confusing instantiation of the has_shortcode variable
* BUG FIX: Extra save operation for billing/shipping address updates on WP_User profile page

= 3.2.1 =

* BUG FIX: Occasional fatal PHP error when activating the plugin

= 3.2 =

* BUG FIX: Using constants for language slug
* BUG FIX: Use E20R_Directory_For_PMPro::getURL() in place of get_permalink() for profile/directory URLs
* BUG FIX: Could sometimes trigger fatal error in Directory_Page() class
* BUG FIX: Would add link to editable profile for all profile(s) in some circumstances
* ENHANCEMENT: Added @uses to document filters used in the Directory_Page::readFromDB() method
* ENHANCEMENT: Added @uses to document filters used in the Directory_Page::defaultColumns() method
* ENHANCEMENT: Expanded on documentation blocks to some of the member functions in Directory_Page() class
* ENHANCEMENT: Refactored and added Directory_Page::displayLinks() to generate edit/view profile link in a user's directory listing(s)
* ENHANCEMENT: Additional old/prior plugin shortcode possibilities supported by the 'e20r-member-directory' shortcode for compatibility

= 3.1 =

* ENHANCEMENT: Add filter to show/hide the Billing address info
* ENHANCEMENT: Add filter to show/hide the Shipping address info
* BUG FIX: PHP Notice when logging in from the front-end

= 3.0 =

* ENHANCEMENT: Initial release of v3.0 of the E20R Directory for PMPro plugin

= 2.9.1 =

* BUG FIX: Deactivate namespace use
* BUG FIX: Not loading directory or profile short code contents
* BUG FIX: Include class directory in build

= 2.9 =

* ENHANCEMENT: Move to E20R\MemberDirectory namespace
* ENHANCEMENT: Start transition and start using Select() class to build SQL queries
* ENHANCEMENT: Use pmpro_loadTemplate() function to load directory & profile pages
* BUG FIX: Properly escape status values from DB in Directory

= 2.8 =

* ENHANCEMENT: Added PHPDoc blocks for address-section.php
* BUG FIX: User meta data search triggered SQL error
* BUG FIX: Would debug log when WP_DEBUG wasn't defined

= 2.7 =

* ENHANCEMENT: More precise search for meta values
* ENHANCEMENT: Use filter to determine whether to include the 'Show all members' directory link ('e20r-directory-for-pmpro_profile_show_return_link')

= 2.6 =

* ENHANCEMENT: Added 'e20r-directory-for-pmpro_metafield_value' filter - Used to format/modify the displayed usermeta value(s). Basically a Register Helper field value 'translator' filter.

= 2.5 =

* BUG FIX: Didn't return any records

= 2.4 =

* BUG FIX: Sanitize the level & levels attributes to avoid SQL injections
* ENHANCEMENT: Add 'pmpromd_included_levels' filter to configure the level(s) to include members for.
* ENHANCEMENT: Renamed 'pmpromd_included_levels' to 'e20r-directory-for-pmpro_included_levels'

= 2.3 =

* BUG FIX: Generate proper pagination links for Directory page
* BUG FIX: Didn't preserve extra search field settings when generating pagination value(s)
* ENHANCEMENT: Adding PHPDoc blocks for functions

= 2.2 =

* ENHANCEMENT: Add e20rmd_add_extra_directory_output action hook in directory template (Include stuff at bottom of directory entry for user/entity)
* BUG FIX: Didn't include extra search input fields/selections is search portion of profile page

= 2.0.2 =

* BUG FIX: Didn't remove slashes from escaped characters when displaying the field contents from Register Helper

= 2.0.1 =

* BUG FIX: Not enough space around the Billing/Shipping Info section

= 2.0 =

* BUG FIX: Didn't handle zip codes in billing/shipping info
* BUG FIX: Don't embed a website if there's a parameter containing the 'url' string in the fields attribute
* ENHANCEMENT: Add pmporemd_true_false() function to check input values & return true if they're true, false if false.
* ENHANCEMENT: Set default shortcode attributes
* ENHANCEMENT: Use e20rmd_true_false for shortcode attributes
* ENHANCEMENT: Add 'detailed profile page' link optionally activated for current and logged in members only (members_only_link='true', false by default)
* ENHANCEMENT: Have a `e20r-directory-for-pmpro_extra_search_output` action in addition to the filter
* ENHANCEMENT: Grant a logged in user access to their own (editable) profile and a link to look at the read-only (editable_profile='true', false by default)
* ENHANCEMENT: Type check the show attributes
* ENHANCEMENT: Let the current user either view or edit their profile
* ENHANCEMENT: Support for separate billing and shipping address section. The PMPro Billing & Shipping information metadata fields must be included defined in the 'fields' attribute of the shortcode, and the 'billing_address' and/or the 'shipping_address' attributes have to be set to 'true' ('false' by default. Valid 'true' values are '1', 'true', 'yes'. Valid 'false' values are '0', 'no', 'false' )
* ENHANCEMENT: Simplified the header filter names (more descriptive) for the Billing & Shipping info sections
* ENHANCEMENT: Removed debug output
* ENHANCEMENT: Add basic alignment styles for the shipping/billing sections

= 1.6 =

* ENHANCEMENT: Add check for the other (standard) Member Directory add-on presence
* ENHANCEMENT: Added partial French translation
* BUG FIX: Avoid namespace collisions with standard directory

= 1.5.1 =

* BUG FIX: Only attempt to load extra search fields if they've been defined
* BUG FIX: Typo in $orderby variable (should be $order_by)
* BUG FIX: Sanitize search info when looking at it
* ENHANCEMENT: Add reset link for search form (only if search is active)
* ENHANCEMENT: Remove debug info

= 1.5 =

* BUG FIX: Make sure Search button doesn't overlay the Search field
* BUG FIX: Search gets clobbered by header
* BUG FIX: Escape URLs when printing to front end
* BUG FIX: Better escaping of data on front-end pages
* BUG FIX: Sanitize values better when printing
* ENHANCEMENT: Initialize variables before use
* ENHANCEMENT: Remove extra whitespace
* ENHANCEMENT: Add localization
* ENHANCEMENT: Force search to use precise values in query
* ENHANCEMENT: Allow user to specify WordPress Role(s) to use as filter for directory
* ENHANCEMENT: Added documentation for extra filters (pmpromd_extra_search_filters, pmpromd_exact_search_values)
* ENHANCEMENT: Load localization function(s)
* ENHANCEMENT: Use plugin slug (for standard version) in localization
* ENHANCEMENT: Fixed code style
* ENHANCEMENT: Explicit priority for all action hooks
* ENHANCEMENT: Added locatlization loader to build script

= 1.4 =

* ENH: Add wrapper around Search form components for more direct placement control

= 1.3.1 =

* ENHANCEMENT: Make sure search area is big enough.

= 1.3 =

* ENHANCEMENT: Add plugin specific constant
* ENHANCEMENT: Clean up the SQL statement (escape variables)
* ENHANCEMENT: Refactor CSS
* ENHANCEMENT: Fix placement of Search button
* ENHANCEMENT: Fix layout of Search button
* ENHANCEMENT: Fix placeholder text width for Select2 inputs
* ENHANCEMENT: Add support for one-click plugin upgrade
* ENHANCEMENT: Add support for build tools
* ENHANCEMENT: Add versioned CSS file support

= 1.1 =
* FIX: Didn't always handle pagination for the directory correctly

= 1.0 =
* ENHANCEMENT: Supports better/more extensive search based on user metadata in [e20r-directory-for-pmpro] shortcode

= .4.4 =
* Added the e20r-directory-for-pmpro_sql filter (passes $sqlQuery, $levels, $s, $pn, $limit, $start, $end) that can be used to filter the SQL used to lookup members for the directory page.

= .4.3 =
* BUG: Fixed bug where the Address 1 text was appearing under Address 2 on profiles.

= .4.2 =
* BUG/ENHANCEMENT: Now passing ?pu={user_nicename} in the profile link. The profile page will accept a numerical ID or alphanumerical nicename/slug to lookup the user.

= .4.1 =
* ENHANCEMENT: Added sorting by first_name and last_name.
* ENHANCEMENT: Now checking for Register Helper labels for arrays of custom fields on the profile and directory templates.
* BUG: Fixed broken profile links on directory page for certain usernames.

= .4 =
* Added e20r-member-profile_fields filter to set or override fields available on the profile pages.

= .3.1 =
* BUG: Fixed css declaration that was affecting elements outside of the e20r-directory-for-pmpro div/table
* ENHANCEMENT: Added ability to load the theme's (child or parent) custom e20r-directory-for-pmpro.css in place of default

= .3 = 
* FEATURE: Added [e20r-member-profile] shortcode
* ENHANCEMENT: Added additional attributes to the [e20r-directory-for-pmpro]
* ENHANCEMENT: Added ability to define Directory and Profile page under Memberships > Pge Settings 
* ENHANCEMENT: Added user option to hide profile from diretory.

= .2 =
* SECURITY: Protecting against SQL injections and XSS on the directory search form/etc.
* ENHANCEMENT: Added pagination to the directory page with a 15 members per page limit. You can override the limit by setting a limit parameter on the shortcode or by passing &limit=... to the URL.

= .1 =
* Initial commit.