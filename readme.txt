=== Paid Memberships Pro - Member Directory Add On ===
Contributors: strangerstudios, eighty20results
Tags: pmpro, paid memberships pro, members, directory
Requires at least: 4.4
Tested up to: 4.9.1
Stable tag: 2.2

Add a robust Member Directory and Profiles to Your Membership Site - with attributes to customize the display.

== Description ==
The Member Directory Add On enhances your membership site with a public or private, searchable directory and member profiles.

This plugin creates 2 shortcodes for a Member Directory and Member Profile pages, which can be defined in Memberships > Page Settings of the WordPress admin.

Shortcode attributes for `[pmpro_member_directory]` include:

1. avatar_size: The square pixel dimensions of the avatar to display. Requires the "show_avatar" attribute to be set to 'true'. default: '128' (accepts any numerical value).
1. fields: Display additional user meta fields. default: none (accepts a list of label names and field IDs, i.e. fields="Company,company;Website,user_url").
1. layout: The format of the directory. default: div (accepts 'table', 'div', '2col', '3col', and '4col').
1. levels: The level ID or a comma-separated list of level IDs to include in the directory. default: all levels (accepts a single level ID or a comma-separated list of IDs).
1. limit: the number of members to display per page
1. link: Optionally link the member directory item to the single member profile page. default: true (accepts 'true' or 'false').
1. order: Sort the results based on the order_by attribute in ascending or descending order. default: ASC (accepts 'DESC' or 'ASC'). 
1. order_by: The sort order for the results. default: 'u.display_name' (accepts 'u.user_email', 'u.user_email', 'u.display_name', 'u.user_login', 'u.user_registered', 'mu.membership_id', 'mu.startdate', 'joindate')
1. show_avatar: Display the user's avatar generated via Gravatar (https://en.gravatar.com) or user-submitted using a plugin like Simple Local Avatars (https://wordpress.org/plugins/simple-local-avatars/); default: true (accepts 'true' or 'false').
1. show_email: Display the user's email address; default: true (accepts 'true' or 'false').
1. show_level: Display the user's membership level; default: true  (accepts 'true' or 'false').
1. show_search: Display a search form (searches on member display name or email address); default: true (accepts 'true' or 'false').
1. show_startdate: Display the user's membership start date for their current level; default: true (accepts 'true' or 'false').
1. show_roles: Display the users if they have been assigned the role(s) listed (default: null, accepts comma separated list of role names)
1. members_only_link: Show the link to the profile details page to logged in and active members only (default: 'false', accepts 'true' or 'false')
1. editable_profile: If the user is logged in, they are shown both a view and edit link for their profile page (default: 'false', accepts 'true' or 'false'). Caution: The edit link may direct the user to the WordPress backend unless a front-end profile plugin is installed (For example: Theme My Login)

Shortcode attributes for `[pmpro_member_profile]` include:	

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
1. billing_address: Show the PMPro Billing information in a separate section of the profile page. Requires the presence of the 'pmpro_b*' user metadata fields in the 'fields=""' attribute (see above). default: 'false' (accepts 'true' or 'false'). The default heading for the section is "Billing Address", but can be modified with the 'pmpro-member-profile-billing-header' filter. The filter returns the text of the heading.
1. shipping_address: Show the PMPro Shipping information in a separate section of the profile page. Requires the presence of the 'pmpro_s*' user metadata fields in the 'fields=""' attribute (see above). default: 'false' (accepts 'true' or 'false'). The default heading for the section is "Shippping Address", but it can be modified with the 'pmpro-member-profile-shipping-header' filter. The filter returns the text of the heading.

== Installation ==

1. Upload the `pmpro-extended-membership-directory` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the `Plugins` menu in WordPress.
1. Create a page for your directory and set the appropriate shortcode attributes and `Require Membership` settings per your needs.
1. Create a page for your profile and set the appropriate shortcode attributes and `Require Membership` settings per your needs.
1. Navigate to Memberships > Page Settings to assign your pages to the Directory and Profile page settings.

== Examples ==
Show only level IDs 1 and 4, hide avatars and email address:
[pmpro_member_directory levels="1,4" show_avatar="false" show_email="false"]

Show all level IDs, hide level name and start date:
[pmpro_member_directory show_level="false" show_startdate="false"]

Show a unique member directory by level. Level 1 Members can only see other Level 1 Members...:
[membership level="1"]
[pmpro_member_directory levels="1"]
[/membership]

[membership level="2"]
[pmpro_member_directory levels="2"]
[/membership]

[membership level="3"]
[pmpro_member_directory levels="3"]
[/membership]

Show unique member profiles based on level - hide user phone number and email address.
[membership level="1"]
[pmpro_member_profile show_email="false" show_phone="false"]
[/membership]

[membership level="2"]
[pmpro_member_profile show_email="true" show_phone="true"]
[/membership]

== Hooks & Filters ==
=== Filters ===
1. pmpro_member_profile_shipping_header - Set the heading for the Shipping Information section (if applicable). default: 'Billing Address' - string
1. pmpro_member_profile_billing_header - Set the heading for the Billing Information section (if applicable). default: 'Shipping Address' - string
1. pmpromd_extra_search_fields - List (array) of user meta data keys that will have added search input sections on the directory page - default: array() (empty array)
1. pmpromd_exact_search_values - Whether to wrap the search input in wildcard during DB operation, or use the extact string as typed - default: false (boolean)
1. pmpromd_membership_statuses - Membership statuses to include in the search result. Default behavior is to only included active member(s) - default: array( 'active' )
1. pmpro_member_directory_set_order - Directly change the directory search result order (SQL).
1. pmpro_member_directory_sql - Modify the SQL statement used to search for whom to include in the directory listing
1. pmpro_member_directory_search_class - Add extra CSS class(es) to the directory listing - default: null
1. pmpromd_search_placeholder_text - Change the placeholder text in the Search input field - default: "Search Members" - string
1. pmpro_member_directory_extra_search_input - Add array of HTML to add extra search input fields (and field types) below the main "Search" input.
1. pmpro_member_profile_fields - Allow user to remove/add additional usermetadata fields & labels programatically
1. pmpro_member_directory_non_admin_profile_settings - Set to false in order to hide the "Hide from Member Directory?" setting on the user's WordPress profile page, unless they're assigned the administrator role.

=== Action hooks ===
1. pmpro_member_directory_extra_search_output - Output HTML so a user can provide input for the specified pmpromd_extra_search_fields search fields
1. pmproemd_add_extra_profile_output - By default used to output the Shipping & Billing information sections on the user profile page, but can be used to add more data to the profile page for the user. Passes the current user's WP_User object as well as the array of entries from the 'fields=""' attribute (as it was prior to having been passed through the 'pmpro_member_profile_fields' filter).
1. pmproemd_add_extra_profile_output - Output HTML at the bottom of the profile page entry for the selected member. Accepts 2 arguments: $real_fields_array (array of fields from Register Helper) and $profile_user (WP_User object for the member/user)
1. pmproemd_add_extra_directory_output - Output HTML at the bottom of the directory entry for the current user. Accepts 2 arguments: $real_fields_array (array of fields from Register Helper) and $the_user (WP_User object for the member/user)


NOTE: pmpro_member_directory_extra_search_input (filter hook) and pmpro_member_directory_extra_search_output (action hook) are two ways - hooks - of achieving the same thing (the filter is for backwards compatibility reasons). The preferred approach at this point is to use the pmpro_member_directory_extra_search_output action hook.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/eighty20results.com/pmpro-extended-membership-directory/issues

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at http://www.eighty20results.com for more documentation and our support forums.

== Changelog ==
= 2.0.2 =

* BUG FIX: Didn't remove slashes from escaped characters when displaying the field contents from Register Helper

= 2.0.1 =

* BUG FIX: Not enough space around the Billing/Shipping Info section

= 2.0 =

* BUG FIX: Didn't handle zip codes in billing/shipping info
* BUG FIX: Don't embed a website if there's a parameter containing the 'url' string in the fields attribute
* ENHANCEMENT: Add pmporemd_true_false() function to check input values & return true if they're true, false if false.
* ENHANCEMENT: Set default shortcode attributes
* ENHANCEMENT: Use pmproemd_true_false for shortcode attributes
* ENHANCEMENT: Add 'detailed profile page' link optionally activated for current and logged in members only (members_only_link='true', false by default)
* ENHANCEMENT: Have a `pmpro_member_directory_extra_search_output` action in addition to the filter
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
* ENHANCEMENT: Supports better/more extensive search based on user metadata in [pmpro_member_directory] shortcode

= .4.4 =
* Added the pmpro_member_directory_sql filter (passes $sqlQuery, $levels, $s, $pn, $limit, $start, $end) that can be used to filter the SQL used to lookup members for the directory page.

= .4.3 =
* BUG: Fixed bug where the Address 1 text was appearing under Address 2 on profiles.

= .4.2 =
* BUG/ENHANCEMENT: Now passing ?pu={user_nicename} in the profile link. The profile page will accept a numerical ID or alphanumerical nicename/slug to lookup the user.

= .4.1 =
* ENHANCEMENT: Added sorting by first_name and last_name.
* ENHANCEMENT: Now checking for Register Helper labels for arrays of custom fields on the profile and directory templates.
* BUG: Fixed broken profile links on directory page for certain usernames.

= .4 =
* Added pmpro_member_profile_fields filter to set or override fields available on the profile pages.

= .3.1 =
* BUG: Fixed css declaration that was affecting elements outside of the pmpro_member_directory div/table
* ENHANCEMENT: Added ability to load the theme's (child or parent) custom pmpro-member-directory.css in place of default

= .3 = 
* FEATURE: Added [pmpro_member_profile] shortcode
* ENHANCEMENT: Added additional attributes to the [pmpro_member_directory]
* ENHANCEMENT: Added ability to define Directory and Profile page under Memberships > Pge Settings 
* ENHANCEMENT: Added user option to hide profile from diretory.

= .2 =
* SECURITY: Protecting against SQL injections and XSS on the directory search form/etc.
* ENHANCEMENT: Added pagination to the directory page with a 15 members per page limit. You can override the limit by setting a limit parameter on the shortcode or by passing &limit=... to the URL.

= .1 =
* Initial commit.